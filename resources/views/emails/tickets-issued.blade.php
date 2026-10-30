<!doctype html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width">
        <title>{{ config('ticketing.event_name') }} &mdash; vos billets</title>
        <style type="text/css">
            @@media only screen and (max-width: 520px) {
                .email-shell {
                    padding: 14px 8px !important;
                }

                .email-back-scroll {
                    width: 100% !important;
                    overflow-x: auto !important;
                    -webkit-overflow-scrolling: touch !important;
                }

                .email-back-table {
                    width: 680px !important;
                    min-width: 680px !important;
                    table-layout: fixed !important;
                }

                .email-back-column {
                    display: table-cell !important;
                    border-bottom: 0 !important;
                }

                .email-back-column:nth-child(1) {
                    width: 25% !important;
                }

                .email-back-column:nth-child(2) {
                    width: 25% !important;
                }

                .email-back-column:nth-child(3) {
                    width: 30% !important;
                }

                .email-back-column:nth-child(4) {
                    width: 20% !important;
                }
            }
        </style>
    </head>
    <body style="margin:0; padding:0; background-color:#0b0b0b; color:#eee6d3; font-family:Arial, Helvetica, sans-serif;">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#0b0b0b;">
            <tr>
                <td class="email-shell" align="center" style="padding:24px 12px;">
                    <table role="presentation" width="680" cellspacing="0" cellpadding="0" border="0" style="width:100%; max-width:680px;">
                        <tr>
                            <td style="padding:0 8px 18px; color:#ded4bb; font-size:15px; line-height:1.5;">
                                Bonjour {{ $order->buyer_name }}, voici {{ $order->quantity > 1 ? 'vos billets' : 'votre billet' }} recto-verso.
                            </td>
                        </tr>

                        @foreach ($order->tickets as $ticket)
                            @php
                                $rectoImage = $order->ticket_type === 'vip'
                                    ? 'ticket-vip-recto.png'
                                    : 'ticket-standard-recto.png';
                            @endphp

                            <tr>
                                <td style="padding:0 4px 24px;">
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="overflow:hidden; border:2px solid #c99a2e; border-radius:12px; background-color:#050505;">
                                        <tr>
                                            <td style="padding:8px 12px; background-color:#050505; color:#f0d675; font-size:10px; font-weight:bold; letter-spacing:1.4px;">
                                                RECTO &middot; BILLET OFFICIEL
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding:0;">
                                                <img
                                                    src="{{ $message->embed(public_path('assets/images/' . $rectoImage)) }}"
                                                    width="680"
                                                    alt="Recto du billet {{ $order->ticket_type === 'vip' ? 'VIP' : 'Standard' }}"
                                                    style="display:block; width:100%; max-width:680px; height:auto; border:0;"
                                                >
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding:14px 12px 8px; border-top:1px solid #c99a2e; background-color:#050505; color:#f0d675; font-size:10px; font-weight:bold; letter-spacing:1.4px;">
                                                VERSO &middot; INFORMATIONS DU CONCERT
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding:8px 10px 12px; background-color:#050505;">
                                                <div class="email-back-scroll" style="max-width:100%; overflow-x:auto; -webkit-overflow-scrolling:touch;">
                                                    <table class="email-back-table" role="presentation" width="680" cellspacing="0" cellpadding="0" border="0" style="width:680px; min-width:680px; table-layout:fixed;">
                                                        <tr>
                                                            <td class="email-back-column" width="25%" valign="middle" style="width:25%; padding:10px; border-right:1px solid #705a26; color:#f0d675; text-align:center;">
                                                                <strong style="font-family:Georgia, 'Times New Roman', serif; font-size:16px;">CONCERT</strong><br>
                                                                <span style="color:#eee6d3; font-family:Georgia, 'Times New Roman', serif; font-size:10px;">DU 10e ANNIVERSAIRE</span><br>
                                                                <img src="{{ $message->embed(public_path('assets/images/logo-paroisse.png')) }}" width="40" height="40" alt="Paroisse Saint Albert le Grand" style="display:inline-block; width:40px; height:40px; margin:8px 3px 0; border:1px solid #c99a2e; border-radius:50%; background:#fff; object-fit:cover;">
                                                                <img src="{{ $message->embed(public_path('assets/images/logo-chorale.png')) }}" width="40" height="40" alt="Chorale Divine M&eacute;lodie" style="display:inline-block; width:40px; height:40px; margin:8px 3px 0; border:1px solid #c99a2e; border-radius:50%; background:#fff; object-fit:cover;">
                                                                <div style="margin-top:6px; color:#f0d675; font-size:9px;">Chorale Divine M&eacute;lodie</div>
                                                            </td>
                                                            <td class="email-back-column" width="25%" valign="middle" style="width:25%; padding:10px; border-right:1px solid #705a26; color:#f4eddb; font-size:11px; line-height:1.5;">
                                                                <strong style="color:#f0d675;">DATE</strong><br>{{ $eventDate }}<br>
                                                                <strong style="color:#f0d675;">HEURE</strong><br>{{ config('ticketing.event_time') }}<br>
                                                                <strong style="color:#f0d675;">LIEU</strong><br>{{ config('ticketing.event_venue') }}
                                                            </td>
                                                            <td class="email-back-column" width="30%" valign="middle" style="width:30%; padding:10px; border-right:1px solid #705a26; color:#f4eddb; font-size:11px; line-height:1.5;">
                                                                <strong style="color:#f0d675;">CONTACTS &amp; R&Eacute;SERVATIONS</strong><br>
                                                                <a href="tel:+2250171275381" style="color:#f4eddb; text-decoration:none;">01 71 27 53 81</a><br>
                                                                <a href="tel:+2250759277753" style="color:#f4eddb; text-decoration:none;">07 59 27 77 53</a>
                                                                <div style="margin-top:8px; padding-top:6px; border-top:1px solid #705a26;">
                                                                    <strong style="color:#f0d675;">CONDITIONS D'ACC&Egrave;S</strong><br>
                                                                    Ticket obligatoire &agrave; l'entr&eacute;e. Un ticket donne droit &agrave; une seule entr&eacute;e.
                                                                </div>
                                                            </td>
                                                            <td class="email-back-column" width="20%" align="center" valign="middle" style="width:20%; padding:8px; color:#f0d675; text-align:center;">
                                                                <img
                                                                    class="email-qr"
                                                                    src="{{ $message->embedData($qrCodes[$ticket->id], 'qr-' . $ticket->ticket_number . '.png', 'image/png') }}"
                                                                    width="112"
                                                                    height="112"
                                                                    alt="QR code unique du billet {{ $ticket->ticket_number }}"
                                                                    style="display:block; width:112px; height:112px; margin:0 auto 7px; padding:4px; border:1px solid #c99a2e; background:#fff;"
                                                                >
                                                                <strong style="font-size:9px;">N&deg; {{ $ticket->ticket_number }}</strong>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center" style="padding:12px 10px; border-top:1px solid #705a26; background-color:#050505; color:#f0d675; font-family:Georgia, 'Times New Roman', serif; font-size:15px; font-style:italic;">
                                                Chanter pour Dieu, c'est notre mission
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center" style="padding:14px 16px; background-color:#050505;">
                                                <a
                                                    href="{{ route('tickets.show', ['token' => $ticket->token, 'imprimer' => 1]) }}"
                                                    style="display:inline-block; padding:11px 22px; border-radius:999px; background-color:#f0d675; color:#050505; font-size:13px; font-weight:bold; text-decoration:none;"
                                                >
                                                    Imprimer mon billet recto-verso
                                                </a>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        @endforeach

                        <tr>
                            <td style="padding:0 8px; color:#c6b993; font-size:11px; line-height:1.5;">
                                Chaque billet poss&egrave;de son propre QR code et son propre num&eacute;ro.
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>
