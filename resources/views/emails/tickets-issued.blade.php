<!doctype html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width">
        <title>{{ config('ticketing.event_name') }} — vos billets</title>
        <style type="text/css">
            @@media only screen and (max-width: 480px) {
                .email-shell { padding: 14px 8px !important; }
                .email-card-cell { padding-right: 2px !important; padding-left: 2px !important; }
                .email-ticket-header { padding: 12px 10px !important; }
                .email-ticket-content { padding-right: 14px !important; padding-left: 14px !important; }
                .email-ticket-title { font-size: 21px !important; }
                .email-qr-cell { padding-right: 10px !important; padding-left: 10px !important; }
                .email-qr { width: 148px !important; height: 148px !important; }
                .email-details td { font-size: 9px !important; word-break: break-word !important; }
            }
        </style>
    </head>
    <body style="margin:0; padding:0; background-color:#eeeae0; color:#171717; font-family:Arial, Helvetica, sans-serif;">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#eeeae0;">
            <tr>
                <td class="email-shell" align="center" style="padding:24px 12px;">
                    <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="width:100%; max-width:600px;">
                        <tr>
                            <td style="padding:0 8px 18px; color:#38352e; font-size:15px; line-height:1.5;">
                                Bonjour {{ $order->buyer_name }}, voici {{ $order->quantity > 1 ? 'vos billets' : 'votre billet' }}.
                            </td>
                        </tr>

                        @foreach ($order->tickets as $ticket)
                            @php
                                $ticketType = config('ticketing.ticket_types.' . $order->ticket_type);
                            @endphp
                            <tr>
                                <td class="email-card-cell" style="padding:0 4px 22px;">
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" class="email-ticket" style="overflow:hidden; border:2px solid #c9a54c; border-radius:18px; background-color:#ffffff;">
                                        <tr>
                                            <td class="email-ticket-header" style="padding:14px 18px; border-radius:15px 15px 0 0; background-color:#111111;">
                                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                                    <tr>
                                                        <td width="56" align="left" valign="middle">
                                                            <img
                                                                src="{{ $message->embed(public_path('assets/images/logo-chorale.png')) }}"
                                                                width="48"
                                                                height="48"
                                                                alt="Chorale Divine Mélodie"
                                                                style="display:block; width:48px; height:48px; border-radius:50%; background-color:#ffffff; object-fit:contain;"
                                                            >
                                                        </td>
                                                        <td align="center" valign="middle" style="padding:0 8px; color:#d8b65e; font-size:11px; font-weight:bold; letter-spacing:1.5px;">
                                                            BILLET OFFICIEL<br>
                                                            <span style="color:#ffffff; font-size:13px; letter-spacing:0;">{{ $ticketType['label'] }}</span>
                                                        </td>
                                                        <td width="56" align="right" valign="middle">
                                                            <img
                                                                src="{{ $message->embed(public_path('assets/images/logo-paroisse.png')) }}"
                                                                width="48"
                                                                height="48"
                                                                alt="Paroisse Saint Albert le Grand de Cocody"
                                                                style="display:block; width:48px; height:48px; border-radius:50%; background-color:#ffffff; object-fit:contain;"
                                                            >
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="email-ticket-content" align="center" style="padding:22px 24px 10px;">
                                                <p style="margin:0 0 7px; color:#8a6a20; font-size:10px; font-weight:bold; letter-spacing:1.3px; text-transform:uppercase;">
                                                    Chorale Divine Mélodie présente
                                                </p>
                                                <h1 class="email-ticket-title" style="margin:0; color:#111111; font-family:Georgia, 'Times New Roman', serif; font-size:25px; font-weight:normal; line-height:1.2;">
                                                    {{ config('ticketing.event_name') }}
                                                </h1>
                                                <p style="margin:10px 0 3px; color:#765916; font-size:14px; font-weight:bold;">
                                                    {{ $eventDate }} · {{ config('ticketing.event_time') }}
                                                </p>
                                                <p style="margin:0; color:#4b4943; font-size:13px; line-height:1.5;">
                                                    {{ config('ticketing.event_venue') }}
                                                </p>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td style="padding:8px 24px 0;">
                                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                                    <tr>
                                                        <td style="border-top:1px dashed #c9a54c; font-size:1px; line-height:1px;">&nbsp;</td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="email-details" style="padding:14px 24px 3px;">
                                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                                    <tr>
                                                        <td align="left" valign="top" style="color:#6b665b; font-size:10px; line-height:1.5;">
                                                            AU NOM DE<br>
                                                            <strong style="color:#171717; font-size:13px;">{{ $order->buyer_name }}</strong>
                                                        </td>
                                                        <td align="right" valign="top" style="color:#6b665b; font-size:10px; line-height:1.5;">
                                                            NUMÉRO DU BILLET<br>
                                                            <strong style="color:#171717; font-size:13px;">{{ $ticket->ticket_number }}</strong>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="2" align="center" style="padding-top:8px; color:#6b665b; font-size:11px;">
                                                            {{ number_format((int) $order->unit_price, 0, ',', ' ') }} FCFA
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="email-qr-cell" align="center" style="padding:10px 20px 6px;">
                                                <img
                                                    class="email-qr"
                                                    src="{{ $message->embedData($qrCodes[$ticket->id], 'qr-' . $ticket->ticket_number . '.png', 'image/png') }}"
                                                    width="168"
                                                    height="168"
                                                    alt="QR code du billet {{ $ticket->ticket_number }}"
                                                    style="display:block; width:168px; height:168px; padding:6px; border:1px solid #e2dccd; background-color:#ffffff;"
                                                >
                                                <p style="margin:7px 0 0; color:#6b665b; font-size:10px;">
                                                    Présentez ce QR code à l’entrée
                                                </p>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td align="center" style="padding:12px 20px 22px;">
                                                <a
                                                    href="{{ route('tickets.show', ['token' => $ticket->token, 'imprimer' => 1]) }}"
                                                    style="display:inline-block; padding:11px 22px; border-radius:999px; background-color:#d7b670; color:#171717; font-size:13px; font-weight:bold; text-decoration:none;"
                                                >
                                                    Imprimer mon billet
                                                </a>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td align="center" style="padding:10px 16px; border-radius:0 0 15px 15px; background-color:#111111; color:#d8b65e; font-size:10px; letter-spacing:.5px;">
                                                {{ $ticket->ticket_number }} · Une entrée
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>