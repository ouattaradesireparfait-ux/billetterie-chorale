@extends('layouts.app')

@section('title', 'Billet ' . $ticket->ticket_number)

@section('content')
    @php
        $ticketType = config('ticketing.ticket_types.' . $ticket->order->ticket_type);
        $eventDate = config('ticketing.event_date');

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $eventDate)) {
            $eventDate = \Illuminate\Support\Carbon::parse($eventDate)
                ->locale('fr')
                ->translatedFormat('l d F Y');
        }
    @endphp

    <section class="ticket-page">
        <div class="ticket-document">
            <article class="ticket-face ticket-face--front" aria-label="Recto du billet">
                <div class="ticket-front__main">
                    <div class="ticket-front__logos">
                        <img
                            class="ticket-front__logo"
                            src="{{ asset('assets/images/logo-chorale.png') }}"
                            alt="Chorale Divine Mélodie"
                        >

                        <div class="ticket-front__brand">
                            <span>LA CHORALE</span>
                            <strong>DIVINE MÉLODIE</strong>
                        </div>

                        <img
                            class="ticket-front__logo"
                            src="{{ asset('assets/images/logo-paroisse.png') }}"
                            alt="Paroisse Saint Albert le Grand de Cocody"
                        >
                    </div>

                    <div class="ticket-front__event">
                        <p class="ticket-front__access">
                            ACCÈS {{ number_format((int) $ticket->order->unit_price, 0, ',', ' ') }} FCFA
                            · {{ $ticketType['label'] }}
                        </p>

                        <h1>{{ config('ticketing.event_name') }}</h1>

                        <p class="ticket-front__subtitle">
                            {{ config('ticketing.event_subtitle') }}
                        </p>
                    </div>

                    <p class="ticket-front__signature">Un concert de la chorale Divine Mélodie</p>
                </div>

                <aside class="ticket-front__stub">
                    <span class="ticket-front__number-label">NUMÉRO DU BILLET</span>
                    <strong class="ticket-front__number">{{ $ticket->ticket_number }}</strong>

                    <img
                        class="ticket-front__qr"
                        src="data:image/png;base64,{{ $qrImage }}"
                        width="168"
                        height="168"
                        alt="QR code unique du billet {{ $ticket->ticket_number }}"
                    >

                    <span class="ticket-front__qr-hint">QR code unique · à présenter à l’entrée</span>
                </aside>
            </article>

            <article class="ticket-face ticket-face--back" aria-label="Verso du billet">
                <header class="ticket-back__header">
                    <div>
                        <span>VERSO DU BILLET</span>
                        <h2>Les informations du concert</h2>
                    </div>

                    <strong>{{ config('ticketing.event_name') }}</strong>
                </header>

                <div class="ticket-back__body">
                    <div class="ticket-back__event-grid">
                        <div>
                            <span>DATE</span>
                            <strong>{{ $eventDate }}</strong>
                        </div>
                        <div>
                            <span>HEURE</span>
                            <strong>{{ config('ticketing.event_time') }}</strong>
                        </div>
                        <div>
                            <span>LIEU</span>
                            <strong>{{ config('ticketing.event_venue') }}</strong>
                        </div>
                    </div>

                    <div class="ticket-back__holder-grid">
                        <div>
                            <span>TITULAIRE</span>
                            <strong>{{ $ticket->order->buyer_name }}</strong>
                        </div>
                        <div>
                            <span>CATÉGORIE</span>
                            <strong>
                                {{ $ticketType['label'] }}
                                · {{ number_format((int) $ticket->order->unit_price, 0, ',', ' ') }} FCFA
                            </strong>
                        </div>
                        <div>
                            <span>NUMÉRO</span>
                            <strong>{{ $ticket->ticket_number }}</strong>
                        </div>
                    </div>

                    <div class="ticket-status {{ $ticket->status === 'used' ? 'ticket-status--used' : ($ticket->order->status !== 'paid' ? 'ticket-status--blocked' : 'ticket-status--valid') }}">
                        <strong>Statut du billet</strong>

                        @if ($ticket->order->status !== 'paid')
                            <span>Paiement non confirmé — entrée non autorisée.</span>
                        @elseif ($ticket->status === 'used' && $ticket->scanned_by)
                            <span>Déjà scanné dans l’application.</span>
                            <small>Entrée enregistrée le {{ $ticket->used_at?->format('d/m/Y à H:i') }}.</small>
                        @elseif ($ticket->status === 'used')
                            <span>Billet déjà marqué comme utilisé.</span>
                        @else
                            <span>Billet valide — pas encore scanné dans l’application.</span>
                        @endif

                        <small>
                            La consultation en ligne n’enregistre pas l’entrée. Seul le scanner de l’application
                            peut marquer ce billet comme utilisé.
                        </small>
                    </div>

                    <p class="ticket-back__instructions">
                        Présentez votre QR code à l’entrée. Ce billet donne droit à une seule entrée.
                        Gardez-le avec vous jusqu’au contrôle.
                    </p>
                </div>

                <footer class="ticket-back__footer">
                    Billetterie officielle · Chorale Divine Mélodie · Paroisse Saint Albert le Grand de Cocody
                </footer>
            </article>
        </div>

        <button class="button gold print-ticket" type="button" onclick="window.print()">
            Imprimer mon billet recto-verso
        </button>
    </section>
@endsection

@if (request()->boolean('imprimer'))
    @push('scripts')
        <script>
            window.addEventListener('load', () => {
                window.setTimeout(() => window.print(), 350);
            });
        </script>
    @endpush
@endif