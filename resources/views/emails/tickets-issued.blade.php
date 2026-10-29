<!doctype html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width">
        <title>{{ config('ticketing.event_name') }} — vos billets</title>
        <style type="text/css">
            @@media only screen and (max-width: 520px) {
                .email-shell { padding: 14px 8px !important; }
                .email-front-main,
                .email-front-stub,
                .email-back-column { display:block !important; width:100% !important; }
                .email-front-stub { border-left:0 !important; border-top:2px dashed #c99a2e !important; }
                .email-ticket-title { font-size:22px !important; }
                .email-qr { width:144px !important; height:144px !important; }
                .email-logo { width:44px !important; height:44px !important; }
            }
        </style>
    </head>
    <body style="margin:0; padding:0; background-color:#0b0b0b; color:#eee6d3; font-family:Arial, Helvetica, sans-serif;">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#0b0b0b;">
            <tr>
                <td class="email-shell" align="center" style="padding:24px 12px;">
                    <table role="presentation" width="640" cellspacing="0" cellpadding="0" border="0" style="width:100%; max-width:640px;">
                        <tr>
                            <td style="padding:0 8px 18px; color:#ded4bb; font-size:15px; line-height:1.5;">
                                Bonjour {{ $order->buyer_name }}, voici {{ $order->quantity > 1 ? 'vos billets' : 'votre billet' }} recto-verso.
                            </td>
                        </tr>

                        @foreach ($order->tickets as $ticket)
                            @php
                                $ticketType = config('ticketing.ticket_types.' . $order->ticket_type);
                                $ticketPrice = number_format((int) $order->unit_price, 0, ',', ' ');
                            @endphp

                            <tr>
                                <td style="padding:0 4px 24px;">
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="overflow:hidden; border:2px solid #c99a2e; border-radius:18px; background-color:#171717;">
                                        <tr>
                                            <td style="padding:9px 16px; background-color:#050505; color:#f0d675; font-size:10px; font-weight:bold; letter-spacing:1.5px;">
                                                RECTO · BILLET OFFICIEL
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding:0;">
                                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                                    <tr>
                                                        <td class="email-front-main" width="68%" valign="middle" style="width:68%; padding:22px 20px; background-color:#111111;">
                                                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                                                <tr>
                                                                    <td width="54" valign="middle">
                                                                        <img
                                                                            class="email-logo"
                                                                            src="{{ $message->embed(public_path('assets/images/logo-chorale.png')) }}"
                                                                            width="50"
                                                                            height="50"
                                                                            alt="Chorale Divine Mélodie"
                                                                            style="display:block; width:50px; height:50px; border-radius:50%; background-color:#fffdf7; object-fit:contain;"
                                                                        >
                                                                    </td>
                                                                    <td align="center" valign="middle" style="padding:0 6px; color:#f0d675; font-family:Georgia, 'Times New Roman', serif; font-size:12px; line-height:1.2;">
                                                                        LA CHORALE<br>
                                                                        <strong style="font-size:15px;">DIVINE MÉLODIE</strong>
                                                                    </td>
                                                                    <td width="54" align="right" valign="middle">
                                                                        <img
                                                                            class="email-logo"
                                                                            src="{{ $message->embed(public_path('assets/images/logo-paroisse.png')) }}"
                                                                            width="50"
                                                                            height="50"
                                                                            alt="Paroisse Saint Albert le Grand de Cocody"
                                                                            style="display:block; width:50px; height:50px; border-radius:50%; background-color:#fffdf7; object-fit:contain;"
                                                                        >
                                                                    </td>
                                                                </tr>
                                                            </table>

                                                            <p style="display:inline-block; margin:20px 0 9px; padding:7px 12px; border-radius:999px; background-color:#f0d675; color:#171717; font-size:12px; font-weight:bold; letter-spacing:.4px;">
                                                                ACCÈS {{ $ticketPrice }} FCFA · {{ $ticketType['label'] }}
                                                            </p>

                                                            <h1 class="email-ticket-title" style="margin:0; color:#f0d675; font-family:Georgia, 'Times New Roman', serif; font-size:27px; font-weight:normal; line-height:1.18;">
                                                                {{ config('ticketing.event_name') }}
                                                            </h1>

                                                            <p style="margin:10px 0 0; color:#c6b993; font-size:14px; font-style:italic; line-height:1.5;">
                                                                {{ config('ticketing.event_subtitle') }}
                                                            </p>

                                                            <p style="margin:12px 0 0; color:#f0d675; font-size:12px; font-weight:bold;">
                                                                Un concert de la chorale Divine Mélodie
                                                            </p>
                                                        </td>

                                                        <td class="email-front-stub" width="32%" align="center" valign="middle" style="width:32%; padding:18px 12px; border-left:2px dashed #c99a2e; background-color:#211c11;">
                                                            <span style="color:#c6b993; font-size:9px; font-weight:bold; letter-spacing:.8px;">
                                                                NUMÉRO DU BILLET
                                                            </span>
                                                            <strong style="display:block; margin:4px 0 10px; color:#f0d675; font-size:14px; word-break:break-word;">
                                                                {{ $ticket->ticket_number }}
                                                            </strong>

                                                            <img
                                                                class="email-qr"
                                                                src="{{ $message->embedData($qrCodes[$ticket->id], 'qr-' . $ticket->ticket_number . '.png', 'image/png') }}"
                                                                width="160"
                                                                height="160"
                                                                alt="QR code unique du billet {{ $ticket->ticket_number }}"
                                                                style="display:block; width:160px; height:160px; padding:6px; border:1px solid #dfc98e; background-color:#ffffff;"
                                                            >

                                                            <span style="display:block; margin-top:8px; color:#c6b993; font-size:9px; line-height:1.4;">
                                                                QR code unique<br>à présenter à l’entrée
                                                            </span>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td style="padding:9px 16px; border-top:1px solid #c99a2e; background-color:#171717; color:#c6b993; font-size:10px;">
                                                VERSO · DÉTAILS ET INFORMATIONS DU CONCERT
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding:20px 22px; background-color:#171717;">
                                                <h2 style="margin:0 0 16px; color:#f0d675; font-family:Georgia, 'Times New Roman', serif; font-size:21px; font-weight:normal;">
                                                    {{ config('ticketing.event_name') }}
                                                </h2>

                                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                                    <tr>
                                                        <td class="email-back-column" width="32%" valign="top" style="width:32%; padding:11px 10px; border:1px solid #dfc98e; background-color:#211c11;">
                                                            <span style="color:#c6b993; font-size:9px; font-weight:bold; letter-spacing:.7px;">DATE</span><br>
                                                            <strong style="color:#f0d675; font-size:12px;">{{ $eventDate }}</strong>
                                                        </td>
                                                        <td width="2%">&nbsp;</td>
                                                        <td class="email-back-column" width="20%" valign="top" style="width:20%; padding:11px 10px; border:1px solid #dfc98e; background-color:#211c11;">
                                                            <span style="color:#c6b993; font-size:9px; font-weight:bold; letter-spacing:.7px;">HEURE</span><br>
                                                            <strong style="color:#f0d675; font-size:12px;">{{ config('ticketing.event_time') }}</strong>
                                                        </td>
                                                        <td width="2%">&nbsp;</td>
                                                        <td class="email-back-column" width="44%" valign="top" style="width:44%; padding:11px 10px; border:1px solid #dfc98e; background-color:#211c11;">
                                                            <span style="color:#c6b993; font-size:9px; font-weight:bold; letter-spacing:.7px;">LIEU</span><br>
                                                            <strong style="color:#f0d675; font-size:12px;">{{ config('ticketing.event_venue') }}</strong>
                                                        </td>
                                                    </tr>
                                                </table>

                                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:12px;">
                                                    <tr>
                                                        <td width="50%" valign="top" style="padding:8px 4px; color:#c6b993; font-size:9px;">
                                                            TITULAIRE<br>
                                                            <strong style="color:#eee6d3; font-size:12px;">{{ $order->buyer_name }}</strong>
                                                        </td>
                                                        <td width="25%" valign="top" style="padding:8px 4px; color:#c6b993; font-size:9px;">
                                                            CATÉGORIE<br>
                                                            <strong style="color:#eee6d3; font-size:12px;">{{ $ticketType['label'] }} · {{ $ticketPrice }} FCFA</strong>
                                                        </td>
                                                        <td width="25%" valign="top" style="padding:8px 4px; color:#c6b993; font-size:9px;">
                                                            NUMÉRO<br>
                                                            <strong style="color:#eee6d3; font-size:12px;">{{ $ticket->ticket_number }}</strong>
                                                        </td>
                                                    </tr>
                                                </table>

                                                <p style="margin:14px 0 0; padding:11px 13px; border-left:3px solid #c99a2e; background-color:#241f14; color:#ded4bb; font-size:11px; line-height:1.5;">
                                                    Présentez votre QR code à l’entrée. Ce billet donne droit à une seule entrée.
                                                    Gardez-le avec vous jusqu’au contrôle.
                                                </p>
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
                                Chaque billet possède son propre QR code et son propre numéro.
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>