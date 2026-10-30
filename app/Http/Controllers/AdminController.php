<?php

namespace App\Http\Controllers;

use App\Mail\TicketsIssued;
use App\Models\Order;
use App\Models\Ticket;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public function login()
    {
        return view('admin.login');
    }

    public function authenticate(Request $request)
    {
        $data = $request->validate([
            'password' => 'required|string',
        ]);

        $credentials = [
            'email' => config('admin.email'),
            'password' => $data['password'],
        ];

        if (! $credentials['email'] || ! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['password' => 'Mot de passe incorrect.']);
        }

        $request->session()->regenerate();

        if (! $request->user()->is_admin || ! $request->user()->is_active) {
            Auth::logout();

            return back()->withErrors(['password' => 'Mot de passe incorrect.']);
        }

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    public function dashboard()
    {
        return view('admin.dashboard', [
            'orders' => Order::with('tickets')->latest()->paginate(20),
            'paidTotal' => Order::where('status', 'paid')->sum('total_amount'),
            'paidCount' => Order::where('status', 'paid')->count(),
            'usedCount' => Ticket::where('status', 'used')->count(),
            'pendingCount' => Order::where('status', 'pending')->count(),
        ]);
    }

    public function sale(Request $request, OrderService $orderService)
    {
        $data = $request->validate([
            'buyer_name' => 'required|string|max:120',
            'buyer_email' => 'nullable|email',
            'buyer_phone' => 'required|string|max:40',
            'ticket_type' => 'required|in:standard,vip',
            'quantity' => 'required|integer|min:1|max:10',
            'payment_method' => 'required|in:orange_money,wave,momo,moov_money,cash',
            'payment_reference' => 'nullable|string|max:100',
        ]);

        $order = $orderService->create($data, true);
        $this->mail($order);

        return back()->with('success', 'Vente enregistrée.');
    }

    public function paid(Order $order)
    {
        $wasConfirmed = DB::transaction(function () use ($order) {
            $lockedOrder = Order::whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOrder->status !== 'pending') {
                return false;
            }

            $lockedOrder->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            return true;
        });

        if (! $wasConfirmed) {
            return back()->withErrors(['order' => 'Commande déjà traitée.']);
        }

        $order->refresh()->load('tickets');
        $this->mail($order);

        return back()->with('success', 'Paiement confirmé.');
    }

    public function cancel(Order $order)
    {
        if ($order->status === 'paid') {
            return back()->withErrors(['order' => 'Commande déjà payée.']);
        }

        $order->update(['status' => 'cancelled']);

        return back()->with('success', 'Commande annulée.');
    }

    public function destroyTicket(Ticket $ticket)
    {
        $result = DB::transaction(function () use ($ticket) {
            $lockedTicket = Ticket::whereKey($ticket->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedTicket->status === 'used') {
                return [
                    'deleted' => false,
                    'message' => 'Un billet déjà scanné ne peut pas être supprimé.',
                ];
            }

            $order = Order::whereKey($lockedTicket->order_id)
                ->lockForUpdate()
                ->firstOrFail();
            $ticketNumber = $lockedTicket->ticket_number;

            $lockedTicket->delete();
            $remainingTickets = $order->tickets()->count();

            if ($remainingTickets === 0) {
                $order->update([
                    'quantity' => 0,
                    'total_amount' => 0,
                    'status' => 'cancelled',
                    'paid_at' => null,
                ]);
            } else {
                $order->update([
                    'quantity' => $remainingTickets,
                    'total_amount' => $remainingTickets * (int) $order->unit_price,
                ]);
            }

            return [
                'deleted' => true,
                'ticket_number' => $ticketNumber,
            ];
        });

        if (! $result['deleted']) {
            return back()->withErrors(['ticket' => $result['message']]);
        }

        return back()->with(
            'success',
            'Billet '.$result['ticket_number'].' supprimé. Le montant de la commande a été ajusté.'
        );
    }

    public function paymentProof(Order $order)
    {
        $path = $order->payment_proof_path;

        if (! $path || ! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        return Storage::disk('local')->response(
            $path,
            null,
            ['Cache-Control' => 'private, no-store'],
            'inline'
        );
    }

    public function scanner()
    {
        return view('admin.scanner');
    }

    public function scan(Request $request)
    {
        $data = $request->validate([
            'token' => 'required_without:ticket_number|nullable|string|max:200',
            'ticket_number' => ['required_without:token', 'nullable', 'string', 'regex:/^(?:VIP|STD)-[A-Z0-9]{4}-(?:00[1-9]|0[1-9][0-9]|[1-9][0-9]{2})$/i', 'max:13'],
        ]);
        $token = isset($data['token'])
            ? $this->normalizeScannedToken($data['token'])
            : '';

        $result = DB::transaction(function () use ($data, $token) {
            $ticket = $this->findScannedTicket($data, $token);

            if (! $ticket) {
                return [
                    'ok' => false,
                    'exists' => false,
                    'scanned_in_app' => false,
                    'status' => 'unknown',
                    'message' => 'Aucun billet ne correspond à ce QR code ou à ce numéro.',
                ];
            }

            $order = Order::find($ticket->order_id);

            if ($order->status !== 'paid') {
                return [
                    'ok' => false,
                    'exists' => true,
                    'scanned_in_app' => false,
                    'status' => 'payment_pending',
                    'message' => 'Paiement non confirmé. Entrée refusée.',
                ];
            }

            if ($ticket->status === 'used') {
                $scannedInApp = $ticket->scanned_by !== null;

                return [
                    'ok' => false,
                    'exists' => true,
                    'scanned_in_app' => $scannedInApp,
                    'status' => 'used',
                    'used_at' => $ticket->used_at?->format('d/m/Y à H:i'),
                    'message' => $scannedInApp
                        ? 'Billet valide, mais déjà marqué comme scanné dans l’application.'
                        : 'Billet valide, déjà déclaré utilisé hors application.',
                ];
            }

            return [
                'ok' => true,
                'exists' => true,
                'scanned_in_app' => false,
                'status' => 'valid',
                'ticket_number' => $ticket->ticket_number,
                'message' => 'Billet existant et valide. Pas encore scanné dans cette application. Numéro : '.$ticket->ticket_number.'.',
            ];
        });

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function markAsUsed(Request $request)
    {
        $data = $request->validate([
            'token' => 'required_without:ticket_number|nullable|string|max:200',
            'ticket_number' => ['required_without:token', 'nullable', 'string', 'regex:/^(?:VIP|STD)-[A-Z0-9]{4}-(?:00[1-9]|0[1-9][0-9]|[1-9][0-9]{2})$/i', 'max:13'],
        ]);
        $token = isset($data['token'])
            ? $this->normalizeScannedToken($data['token'])
            : '';

        $result = DB::transaction(function () use ($data, $token, $request) {
            $ticket = $this->findScannedTicket($data, $token);

            if (! $ticket) {
                return [
                    'ok' => false,
                    'exists' => false,
                    'scanned_in_app' => false,
                    'status' => 'unknown',
                    'message' => 'Aucun billet ne correspond à ce QR code ou à ce numéro.',
                ];
            }

            $order = Order::find($ticket->order_id);

            if ($order->status !== 'paid') {
                return [
                    'ok' => false,
                    'exists' => true,
                    'scanned_in_app' => false,
                    'status' => 'payment_pending',
                    'message' => 'Paiement non confirmé. Entrée refusée.',
                ];
            }

            if ($ticket->status === 'used') {
                $scannedInApp = $ticket->scanned_by !== null;

                return [
                    'ok' => false,
                    'exists' => true,
                    'scanned_in_app' => $scannedInApp,
                    'status' => 'used',
                    'used_at' => $ticket->used_at?->format('d/m/Y à H:i'),
                    'message' => $scannedInApp
                        ? 'Billet valide, mais déjà marqué comme scanné dans l’application.'
                        : 'Billet valide, déjà déclaré utilisé hors application.',
                ];
            }

            $ticket->update([
                'status' => 'used',
                'used_at' => now(),
                'scanned_by' => $request->user()->id,
            ]);

            return [
                'ok' => true,
                'exists' => true,
                'scanned_in_app' => true,
                'status' => 'marked',
                'ticket_number' => $ticket->ticket_number,
                'used_at' => $ticket->used_at?->format('d/m/Y à H:i'),
                'message' => 'Billet marqué comme déjà scanné. Entrée enregistrée.',
            ];
        });

        return response()->json($result, $result['ok'] ? 200 : 422);
    }
    private function findScannedTicket(array $data, string $token): ?Ticket
    {
        $query = Ticket::query();

        if (! empty($data['ticket_number'])) {
            $query->where('ticket_number', strtoupper(trim($data['ticket_number'])));
        } else {
            $query->where('token_hash', hash('sha256', $token));
        }

        return $query->lockForUpdate()->first();
    }
    private function normalizeScannedToken(string $value): string
    {
        $value = trim($value);
        $path = parse_url($value, PHP_URL_PATH);

        if (is_string($path) && preg_match('~/(?:b|billet)/([^/]+)/?$~', $path, $matches)) {
            return rawurldecode($matches[1]);
        }

        return $value;
    }

    private function mail(Order $order)
    {
        if (! $order->buyer_email) {
            return;
        }

        try {
            Mail::to($order->buyer_email)->send(new TicketsIssued($order));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}