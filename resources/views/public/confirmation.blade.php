@extends('layouts.app')

@section('content')
    <section class="card">
        <small>COMMANDE ENREGISTRÉE</small>
        <h1>Merci, {{ $order->buyer_name }}.</h1>

        <p>
            Commande {{ $order->reference }}<br>
            {{ $order->quantity }} billet(s) · {{ $order->formattedTotal() }}
        </p>

        <p>
            Moyen de paiement : <strong>{{ $order->paymentMethodLabel() }}</strong><br>
            Votre capture a été reçue. Le paiement sera vérifié par l’équipe organisatrice avant l’envoi des billets.
        </p>
    </section>

    @if ($order->status === 'pending')
        <section class="card">
            <h2>Paiement en attente de vérification</h2>
            <p>
                L’équipe organisatrice vérifie votre paiement. Vous pouvez remplacer la capture
                tant que le paiement n’est pas confirmé.
            </p>

            <form
                method="post"
                action="{{ route('orders.payment-proof', $order->reference) }}"
                enctype="multipart/form-data"
            >
                @csrf

                <label for="payment-proof">
                    Nouvelle capture du paiement (JPG, PNG ou WebP, 5 Mo maximum)
                </label>
                <input
                    id="payment-proof"
                    type="file"
                    name="payment_proof"
                    accept="image/jpeg,image/png,image/webp"
                    required
                >

                <button class="button">Remplacer la capture</button>
            </form>
        </section>
    @elseif ($order->status === 'paid')
        <section class="card">
            <h2>Paiement confirmé</h2>
            <p>Vos billets ont été envoyés à l’adresse e-mail indiquée lors de la réservation.</p>
        </section>
    @else
        <section class="card">
            <h2>Commande annulée</h2>
            <p>Contactez l’équipe organisatrice si vous avez déjà effectué le paiement.</p>
        </section>
    @endif
@endsection