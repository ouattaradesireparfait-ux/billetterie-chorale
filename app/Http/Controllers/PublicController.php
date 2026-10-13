<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Ticket;
use App\Services\OrderService;
use App\Services\TicketQrCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PublicController extends Controller
{
    public function home()
    {
        return view('public.home');
    }

    public function order(Request $request, OrderService $orderService)
    {
        $data = $request->validate([
            'buyer_name' => 'required|string|max:120',
            'buyer_email' => 'required|email|max:190',
            'buyer_phone' => 'required|string|max:40',
            'ticket_type' => 'required|in:standard,vip',
            'quantity' => 'required|integer|min:1|max:10',
            'payment_method' => 'required|in:orange_money,wave,momo,moov_money',
        ]);

        $order = $orderService->create($data);

        return redirect()->route('orders.confirmation', $order->reference);
    }

    public function confirmation(string $reference)
    {
        $order = Order::where('reference', $reference)->firstOrFail();

        return view('public.confirmation', compact('order'));
    }

    public function uploadPaymentProof(Request $request, string $reference)
    {
        $order = Order::where('reference', $reference)->firstOrFail();

        if ($order->status !== 'pending') {
            return back()->withErrors([
                'payment_proof' => 'Cette commande ne peut plus recevoir de preuve de paiement.',
            ]);
        }

        $data = $request->validate([
            'payment_proof' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ], [
            'payment_proof.required' => 'Ajoutez la capture d’écran de votre paiement.',
            'payment_proof.image' => 'Le fichier doit être une image.',
            'payment_proof.mimes' => 'Formats acceptés : JPG, PNG ou WebP.',
            'payment_proof.max' => 'La capture ne doit pas dépasser 5 Mo.',
        ]);

        $previousPath = $order->payment_proof_path;
        $path = $data['payment_proof']->store('payment-proofs', 'local');

        if (! $path) {
            return back()->withErrors([
                'payment_proof' => 'La capture n’a pas pu être enregistrée. Réessayez.',
            ]);
        }

        $order->update([
            'payment_proof_path' => $path,
            'payment_proof_uploaded_at' => now(),
        ]);

        if ($previousPath) {
            Storage::disk('local')->delete($previousPath);
        }

        return redirect()
            ->route('orders.confirmation', $order->reference)
            ->with('success', 'Capture reçue. L’équipe organisatrice va vérifier votre paiement.');
    }

    public function ticket(string $token)
    {
        $ticket = Ticket::with('order')
            ->where('token_hash', hash('sha256', $token))
            ->firstOrFail();

        $ticketUrl = route('tickets.short', ['token' => $ticket->token]);
        $qrImage = base64_encode(
            app(TicketQrCode::class)->png($ticketUrl)
        );

        return view('tickets.show', compact('ticket', 'qrImage', 'ticketUrl'));
    }
}