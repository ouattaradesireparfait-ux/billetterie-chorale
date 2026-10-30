@extends('layouts.app')

@section('title', 'Billet ' . $ticket->ticket_number)

@section('content')
    @php
        $ticketType = config('ticketing.ticket_types.' . $ticket->order->ticket_type);
        $rectoImage = $ticket->order->ticket_type === 'vip'
            ? 'ticket-vip-recto.png'
            : 'ticket-standard-recto.png';
        $eventDate = config('ticketing.event_date');

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $eventDate)) {
            $eventDate = \Illuminate\Support\Carbon::parse($eventDate)
                ->locale('fr')
                ->translatedFormat('l d F Y');
        }
    @endphp

    <section class="ticket-page">
        <div class="ticket-document">
            <article class="ticket-face ticket-face--front" aria-label="Recto du billet {{ $ticketType['label'] }}">
                <img
                    class="ticket-front__art"
                    src="{{ asset('assets/images/' . $rectoImage) }}"
                    alt="Recto du billet {{ $ticketType['label'] }} de la chorale Divine M&eacute;lodie"
                >
            </article>

            <article class="ticket-face ticket-face--back" aria-label="Verso du billet avec les informations du concert">
                <div class="ticket-back__layout">
                    <section class="ticket-back__identity">
                        <h2>
                            <span>CONCERT</span>
                            <strong>DU 10<sup>e</sup> ANNIVERSAIRE</strong>
                        </h2>
                        <p class="ticket-back__choir">Chorale Divine M&eacute;lodie</p>

                        <div class="ticket-back__logos">
                            <img
                                src="{{ asset('assets/images/logo-paroisse.png') }}"
                                alt="Paroisse Saint Albert le Grand"
                            >
                            <img
                                src="{{ asset('assets/images/logo-chorale.png') }}"
                                alt="Chorale Divine M&eacute;lodie"
                            >
                        </div>

                        <p class="ticket-back__organizer">Organis&eacute; par la Chorale Divine M&eacute;lodie</p>
                    </section>

                    <section class="ticket-back__event" aria-label="Date, heure et lieu">
                        <div class="ticket-back__event-item">
                            <span>DATE</span>
                            <strong>{{ $eventDate }}</strong>
                        </div>
                        <div class="ticket-back__event-item">
                            <span>HEURE</span>
                            <strong>{{ config('ticketing.event_time') }}</strong>
                        </div>
                        <div class="ticket-back__event-item">
                            <span>LIEU</span>
                            <strong>{{ config('ticketing.event_venue') }}</strong>
                        </div>
                    </section>

                    <section class="ticket-back__contacts">
                        <h3>CONTACTS &amp; R&Eacute;SERVATIONS</h3>
                        <a href="tel:+2250171275381">01 71 27 53 81</a>
                        <a href="tel:+2250759277753">07 59 27 77 53</a>

                        <div class="ticket-back__conditions">
                            <strong>CONDITIONS D'ACC&Egrave;S</strong>
                            <span>Ticket obligatoire &agrave; l'entr&eacute;e.</span>
                            <span>Un ticket donne droit &agrave; une seule entr&eacute;e.</span>
                        </div>
                    </section>

                    <aside class="ticket-back__code" aria-label="Num&eacute;ro et QR code du billet">
                        <img
                            class="ticket-back__qr"
                            src="data:image/png;base64,{{ $qrImage }}"
                            width="168"
                            height="168"
                            alt="QR code unique du billet {{ $ticket->ticket_number }}"
                        >
                        <div class="ticket-back__number">
                            <span>N&deg;</span>
                            <strong>{{ $ticket->ticket_number }}</strong>
                        </div>
                    </aside>
                </div>

                <footer class="ticket-back__slogan">
                    Chanter pour Dieu, c'est notre mission
                </footer>
            </article>
        </div>

        <div class="ticket-status ticket-online-status {{ $ticket->status === 'used' ? 'ticket-status--used' : ($ticket->order->status !== 'paid' ? 'ticket-status--blocked' : 'ticket-status--valid') }}">
            <strong>Statut du billet</strong>

            @if ($ticket->order->status !== 'paid')
                <span>Paiement non confirm&eacute; &mdash; entr&eacute;e non autoris&eacute;e.</span>
            @elseif ($ticket->status === 'used' && $ticket->scanned_by)
                <span>D&eacute;j&agrave; scann&eacute; dans l'application.</span>
                <small>Entr&eacute;e enregistr&eacute;e le {{ $ticket->used_at?->format('d/m/Y a H:i') }}.</small>
            @elseif ($ticket->status === 'used')
                <span>Billet d&eacute;j&agrave; marqu&eacute; comme utilis&eacute;.</span>
            @else
                <span>Billet valide &mdash; pas encore scann&eacute; dans l'application.</span>
            @endif

            <small>La consultation en ligne ne marque pas le billet comme utilis&eacute;.</small>
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
