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
            Le paiement sera vérifié par l’équipe organisatrice avant l’envoi des billets.
        </p>
    </section>

    @if ($order->status === 'pending')
        <section class="card">
            <h2>Envoyez la capture de votre paiement</h2>
            <p>
                Effectuez le paiement du montant indiqué avec le moyen choisi, puis joignez ici
                une capture d’écran lisible pour que l’équipe puisse le vérifier.
            </p>

            <div class="payment-instructions">
                <h3>Instructions de paiement</h3>

                @if ($order->payment_method === 'wave')
                    <p>
                        Réglez <strong>{{ $order->formattedTotal() }}</strong> avec Wave,
                        puis joignez la capture d’écran du paiement.
                    </p>
                    <a
                        class="button gold payment-link"
                        href="https://pay.wave.com/m/M_ci_UEm-OFUgcHXy/c/ci/?amount={{ $order->total_amount }}"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        Payer {{ $order->formattedTotal() }} avec Wave
                    </a>
                @elseif ($order->payment_method === 'orange_money')
                    <p>
                        Déposez <strong>{{ $order->formattedTotal() }}</strong> sur ce numéro Orange Money,
                        puis joignez la capture d’écran du paiement.
                    </p>
                    <a class="payment-number" href="tel:+2250787895429">
                        +225 07 87 89 54 29
                    </a>
                @elseif ($order->payment_method === 'moov_money')
                    <p>
                        Déposez <strong>{{ $order->formattedTotal() }}</strong> sur ce numéro Moov Money,
                        puis joignez la capture d’écran du paiement.
                    </p>
                    <a class="payment-number" href="tel:+2250101452506">
                        +225 01 01 45 25 06
                    </a>
                @elseif ($order->payment_method === 'momo')
                    <p>
                        Déposez <strong>{{ $order->formattedTotal() }}</strong> sur ce numéro Momo,
                        puis joignez la capture d’écran du paiement.
                    </p>
                    <a class="payment-number" href="tel:+2250503410066">
                        +225 05 03 41 00 66
                    </a>
                @endif
            </div>

            @if ($order->payment_proof_path)
                <p>Une capture a déjà été reçue. Vous pouvez la remplacer tant que le paiement n’est pas confirmé.</p>
            @endif

            <form
                method="post"
                action="{{ route('orders.payment-proof', $order->reference) }}"
                enctype="multipart/form-data"
            >
                @csrf

                <label for="payment-proof">
                    Capture du paiement (JPG, PNG ou WebP, 5 Mo maximum)
                </label>
                <input
                    id="payment-proof"
                    type="file"
                    name="payment_proof"
                    accept="image/jpeg,image/png,image/webp"
                    required
                >

                <button class="button">
                    {{ $order->payment_proof_path ? 'Remplacer la capture' : 'Envoyer la capture' }}
                </button>
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