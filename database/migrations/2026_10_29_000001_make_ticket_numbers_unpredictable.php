<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    private const PREFIXES = [
        'standard' => 'STD',
        'vip' => 'VIP',
    ];

    private const CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    private const CODE_LENGTH = 12;

    public function up(): void
    {
        $this->updateTicketNumbers();

        Schema::dropIfExists('ticket_number_sequences');
    }

    public function down(): void
    {
        Schema::create('ticket_number_sequences', function ($table) {
            $table->string('ticket_type', 20)->primary();
            $table->unsignedInteger('next_number')->default(1);
        });

        $nextNumbers = [
            'standard' => 1,
            'vip' => 1,
        ];

        DB::table('tickets')
            ->join('orders', 'tickets.order_id', '=', 'orders.id')
            ->select(
                'tickets.id as ticket_id',
                'orders.ticket_type'
            )
            ->orderBy('tickets.id')
            ->chunkById(500, function ($tickets) use (&$nextNumbers) {
                foreach ($tickets as $ticket) {
                    $ticketType = $ticket->ticket_type;

                    if (! isset(self::PREFIXES[$ticketType])) {
                        throw new RuntimeException(
                            'Type de billet inconnu : '.$ticketType
                        );
                    }

                    $number = $nextNumbers[$ticketType]++;

                    DB::table('tickets')
                        ->where('id', $ticket->ticket_id)
                        ->update([
                            'ticket_number' => sprintf(
                                '%s-%04d',
                                self::PREFIXES[$ticketType],
                                $number
                            ),
                        ]);
                }
            }, 'tickets.id', 'ticket_id');

        foreach ($nextNumbers as $ticketType => $nextNumber) {
            DB::table('ticket_number_sequences')
                ->insert([
                    'ticket_type' => $ticketType,
                    'next_number' => $nextNumber,
                ]);
        }
    }

    private function updateTicketNumbers(): void
    {
        DB::table('tickets')
            ->join('orders', 'tickets.order_id', '=', 'orders.id')
            ->select(
                'tickets.id as ticket_id',
                'orders.ticket_type'
            )
            ->orderBy('tickets.id')
            ->chunkById(500, function ($tickets) {
                foreach ($tickets as $ticket) {
                    $prefix = self::PREFIXES[$ticket->ticket_type] ?? null;

                    if ($prefix === null) {
                        throw new RuntimeException(
                            'Type de billet inconnu : '.$ticket->ticket_type
                        );
                    }

                    $ticketNumber = $this->uniqueTicketNumber($prefix);

                    DB::table('tickets')
                        ->where('id', $ticket->ticket_id)
                        ->update(['ticket_number' => $ticketNumber]);
                }
            }, 'tickets.id', 'ticket_id');
    }

    private function uniqueTicketNumber(string $prefix): string
    {
        do {
            $ticketNumber = $prefix.'-'.$this->randomTicketCode();
        } while (
            DB::table('tickets')
                ->where('ticket_number', $ticketNumber)
                ->exists()
        );

        return $ticketNumber;
    }

    private function randomTicketCode(): string
    {
        $alphabetLength = strlen(self::CODE_ALPHABET);
        $characters = '';

        for ($index = 0; $index < self::CODE_LENGTH; $index++) {
            $characters .= self::CODE_ALPHABET[
                random_int(0, $alphabetLength - 1)
            ];
        }

        return implode('-', str_split($characters, 4));
    }
};