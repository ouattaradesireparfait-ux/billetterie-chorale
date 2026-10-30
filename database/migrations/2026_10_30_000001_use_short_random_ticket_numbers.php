<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PREFIXES = [
        'standard' => 'STD',
        'vip' => 'VIP',
    ];

    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    private const LEGACY_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function up(): void
    {
        $counts = [
            'standard' => 0,
            'vip' => 0,
        ];

        $existingCounts = DB::table('tickets')
            ->join('orders', 'tickets.order_id', '=', 'orders.id')
            ->select('orders.ticket_type', DB::raw('COUNT(tickets.id) as ticket_count'))
            ->groupBy('orders.ticket_type')
            ->get();

        foreach ($existingCounts as $row) {
            if (! array_key_exists($row->ticket_type, $counts)) {
                throw new RuntimeException('Type de billet inconnu : '.$row->ticket_type);
            }

            $counts[$row->ticket_type] = (int) $row->ticket_count;

            if ($counts[$row->ticket_type] > 999) {
                throw new RuntimeException(
                    'Impossible de numeroter plus de 999 billets '.$row->ticket_type.'.'
                );
            }
        }

        Schema::dropIfExists('ticket_number_sequences');
        Schema::create('ticket_number_sequences', function (Blueprint $table) {
            $table->string('ticket_type', 20)->primary();
            $table->unsignedInteger('next_number')->default(1);
        });

        DB::table('ticket_number_sequences')->insert([
            ['ticket_type' => 'standard', 'next_number' => 1],
            ['ticket_type' => 'vip', 'next_number' => 1],
        ]);

        DB::transaction(function () {
            DB::table('tickets')
                ->orderBy('id')
                ->chunkById(500, function ($tickets) {
                    foreach ($tickets as $ticket) {
                        DB::table('tickets')
                            ->where('id', $ticket->id)
                            ->update(['ticket_number' => '__MIGRATION__'.$ticket->id]);
                    }
                });

            $nextNumbers = [
                'standard' => 1,
                'vip' => 1,
            ];

            DB::table('tickets')
                ->join('orders', 'tickets.order_id', '=', 'orders.id')
                ->select('tickets.id as ticket_id', 'orders.ticket_type')
                ->orderBy('tickets.id')
                ->chunk(500, function ($tickets) use (&$nextNumbers) {
                    foreach ($tickets as $ticket) {
                        $ticketType = $ticket->ticket_type;
                        $number = $nextNumbers[$ticketType]++;
                        $ticketNumber = $this->uniqueTicketNumber(
                            self::PREFIXES[$ticketType],
                            $number
                        );

                        DB::table('tickets')
                            ->where('id', $ticket->ticket_id)
                            ->update(['ticket_number' => $ticketNumber]);
                    }
                });

            foreach ($nextNumbers as $ticketType => $nextNumber) {
                DB::table('ticket_number_sequences')
                    ->where('ticket_type', $ticketType)
                    ->update(['next_number' => $nextNumber]);
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            DB::table('tickets')
                ->orderBy('id')
                ->chunkById(500, function ($tickets) {
                    foreach ($tickets as $ticket) {
                        DB::table('tickets')
                            ->where('id', $ticket->id)
                            ->update(['ticket_number' => '__MIGRATION__'.$ticket->id]);
                    }
                });

            DB::table('tickets')
                ->join('orders', 'tickets.order_id', '=', 'orders.id')
                ->select('tickets.id as ticket_id', 'orders.ticket_type')
                ->orderBy('tickets.id')
                ->chunk(500, function ($tickets) {
                    foreach ($tickets as $ticket) {
                        $prefix = self::PREFIXES[$ticket->ticket_type] ?? null;

                        if ($prefix === null) {
                            throw new RuntimeException(
                                'Type de billet inconnu : '.$ticket->ticket_type
                            );
                        }

                        DB::table('tickets')
                            ->where('id', $ticket->ticket_id)
                            ->update([
                                'ticket_number' => $this->uniqueLegacyTicketNumber($prefix),
                            ]);
                    }
                });
        });

        Schema::dropIfExists('ticket_number_sequences');
    }

    private function uniqueTicketNumber(string $prefix, int $number): string
    {
        do {
            $ticketNumber = sprintf(
                '%s-%s-%03d',
                $prefix,
                $this->randomCharacters(self::ALPHABET, 4),
                $number
            );
        } while (
            DB::table('tickets')
                ->where('ticket_number', $ticketNumber)
                ->exists()
        );

        return $ticketNumber;
    }

    private function uniqueLegacyTicketNumber(string $prefix): string
    {
        do {
            $characters = $this->randomCharacters(self::LEGACY_ALPHABET, 12);
            $ticketNumber = $prefix.'-'.implode('-', str_split($characters, 4));
        } while (
            DB::table('tickets')
                ->where('ticket_number', $ticketNumber)
                ->exists()
        );

        return $ticketNumber;
    }

    private function randomCharacters(string $alphabet, int $length): string
    {
        $alphabetLength = strlen($alphabet);
        $characters = '';

        for ($index = 0; $index < $length; $index++) {
            $characters .= $alphabet[random_int(0, $alphabetLength - 1)];
        }

        return $characters;
    }
};
