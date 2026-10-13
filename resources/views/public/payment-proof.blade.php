@extends('layouts.app')

@section('content')
    <section class="card">
        <small>DERNIÈRE ÉTAPE</small>
        <h1>Envoyez la preuve de paiement</h1>
        <p>
            La commande sera enregistrée et transmise aux organisateurs après l’envoi de votre capture.
        </p>

        <p>
            {{ $draft['buyer_name'] }}<br>
            {{ $draft['quantity'] }} billet(s) {{ $ticketType['label'] }} · {{ $totalFormatted }}<br>
            Paiement par <strong>{{ $paymentMethodLabel }}</strong>
        </p>
    </section>

    <section class="card">
        <h2>Effectuez le paiement</h2>

        <div class="payment-instructions">
            <h3>Instructions de paiement</h3>

            @if ($draft['payment_method'] === 'wave')
                <p>
                    Réglez <strong>{{ $totalFormatted }}</strong> avec Wave,
                    puis revenez sur cette page pour joindre la capture.
                </p>
                <a
                    class="button gold payment-link"
                    href="https://pay.wave.com/m/M_ci_UEm-OFUgcHXy/c/ci/?amount={{ $totalAmount }}"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    Payer {{ $totalFormatted }} avec Wave
                </a>
            @elseif ($draft['payment_method'] === 'orange_money')
                <p>
                    Déposez <strong>{{ $totalFormatted }}</strong> sur ce numéro Orange Money,
                    puis joignez la capture d’écran.
                </p>
                <a class="payment-number" href="tel:+2250787895429">
                    +225 07 87 89 54 29
                </a>
            @elseif ($draft['payment_method'] === 'moov_money')
                <p>
                    Déposez <strong>{{ $totalFormatted }}</strong> sur ce numéro Moov Money,
                    puis joignez la capture d’écran.
                </p>
                <a class="payment-number" href="tel:+2250101452506">
                    +225 01 01 45 25 06
                </a>
            @elseif ($draft['payment_method'] === 'momo')
                <p>
                    Déposez <strong>{{ $totalFormatted }}</strong> sur ce numéro Momo,
                    puis joignez la capture d’écran.
                </p>
                <a class="payment-number" href="tel:+2250503410066">
                    +225 05 03 41 00 66
                </a>
            @endif
        </div>

        <form
            method="post"
            action="{{ route('orders.payment.submit') }}"
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
                Envoyer la preuve et enregistrer la commande
            </button>
        </form>
    </section>
@endsection