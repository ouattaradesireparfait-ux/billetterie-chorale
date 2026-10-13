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
        <article class="concert-ticket">
            <header class="concert-ticket__header">
                <img
                    class="concert-ticket__logo"
                    src="{{ asset('assets/images/logo-chorale.png') }}"
                    alt="Chorale Divine Mélodie"
                >

                <div class="concert-ticket__brand">
                    <span>BILLET OFFICIEL</span>
                    <strong>{{ $ticketType['label'] }}</strong>
                </div>

                <img
                    class="concert-ticket__logo"
                    src="{{ asset('assets/images/logo-paroisse.png') }}"
                    alt="Paroisse Saint Albert le Grand de Cocody"
                >
            </header>

            <div class="concert-ticket__content">
                <p class="concert-ticket__eyebrow">Chorale Divine Mélodie présente</p>
                <h1 class="concert-ticket__title">{{ config('ticketing.event_name') }}</h1>

                <p class="concert-ticket__date">
                    {{ $eventDate }} <span>·</span> {{ config('ticketing.event_time') }}
                </p>

                <p class="concert-ticket__venue">{{ config('ticketing.event_venue') }}</p>

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
                        Cette consultation en ligne n’enregistre pas l’entrée. Seul le scanner de l’application
                        peut marquer le billet comme utilisé.
                    </small>
                </div>

                <div class="concert-ticket__perforation"></div>

                <div class="concert-ticket__details">
                    <div>
                        <span>AU NOM DE</span>
                        <strong>{{ $ticket->order->buyer_name }}</strong>
                    </div>
                    <div>
                        <span>NUMÉRO DU BILLET</span>
                        <strong>{{ $ticket->ticket_number }}</strong>
                    </div>
                    <div>
                        <span>TARIF</span>
                        <strong>
                            {{ $ticketType['label'] }}
                            · {{ number_format((int) $ticket->order->unit_price, 0, ',', ' ') }} FCFA
                        </strong>
                    </div>
                </div>

                <div class="concert-ticket__qr">
                    <img
                        src="data:image/png;base64,{{ $qrImage }}"
                        width="176"
                        height="176"
                        alt="QR code du billet {{ $ticket->ticket_number }}"
                    >
                    <span>Présentez ce QR code à l’entrée</span>
                </div>
            </div>

            <footer class="concert-ticket__footer">
                Un billet · Une entrée
            </footer>
        </article>

        <button class="button gold print-ticket" type="button" onclick="window.print()">
            Imprimer mon billet
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