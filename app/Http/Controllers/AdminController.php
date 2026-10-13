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
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (! Auth::attempt($data, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Identifiants incorrects.']);
        }

        $request->session()->regenerate();

        if (! $request->user()->is_admin || ! $request->user()->is_active) {
            Auth::logout();

            return back()->withErrors(['email' => 'Compte non autorisé.']);
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
            'payment_method' => 'required|in:orange_money,wave,momo,moov_money',
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
            'token' => 'required|string|max:200',
        ]);
        $token = $this->normalizeScannedToken($data['token']);

        $result = DB::transaction(function () use ($token, $request) {
            $ticket = Ticket::where('token_hash', hash('sha256', $token))
                ->lockForUpdate()
                ->first();

            if (! $ticket) {
                return [
                    'ok' => false,
                    'exists' => false,
                    'scanned_in_app' => false,
                    'status' => 'unknown',
                    'message' => 'Aucun billet ne correspond à ce code.',
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
                        ? 'Entrée déjà enregistrée. Accès refusé.'
                        : 'Billet déjà marqué comme utilisé. Accès refusé.',
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
                'status' => 'used',
                'used_at' => $ticket->used_at?->format('d/m/Y à H:i'),
                'message' => 'Entrée autorisée. '.$ticket->ticket_number,
            ];
        });

        return response()->json($result, $result['ok'] ? 200 : 422);
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