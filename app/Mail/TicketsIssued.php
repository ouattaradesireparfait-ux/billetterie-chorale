<?php

namespace App\Mail;

use App\Models\Order;
use App\Services\TicketQrCode;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Carbon;

class TicketsIssued extends Mailable
{
    public function __construct(public Order $order) {}

    public function build(): static
    {
        $this->order->loadMissing('tickets');

        $qrCodes = [];

        foreach ($this->order->tickets as $ticket) {
            $ticketUrl = route('tickets.short', ['token' => $ticket->token]);
            $qrCodes[$ticket->id] = app(TicketQrCode::class)->png($ticketUrl);
        }

        $eventDate = config('ticketing.event_date');

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $eventDate)) {
            $eventDate = Carbon::parse($eventDate)
                ->locale('fr')
                ->translatedFormat('l d F Y');
        }

        return $this
            ->subject('Vos billets - '.config('ticketing.event_name'))
            ->view('emails.tickets-issued')
            ->with([
                'eventDate' => $eventDate,
                'qrCodes' => $qrCodes,
            ]);
    }
}