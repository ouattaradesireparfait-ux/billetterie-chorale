<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_number_sequences', function (Blueprint $table) {
            $table->string('ticket_type', 20)->primary();
            $table->unsignedInteger('next_number')->default(1);
        });

        DB::table('ticket_number_sequences')->insert([
            ['ticket_type' => 'standard', 'next_number' => 1],
            ['ticket_type' => 'vip', 'next_number' => 1],
        ]);

        $prefixes = [
            'standard' => 'STD',
            'vip' => 'VIP',
        ];
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
            ->chunk(500, function ($tickets) use (&$nextNumbers, $prefixes) {
                foreach ($tickets as $ticket) {
                    $ticketType = $ticket->ticket_type;

                    if (! isset($prefixes[$ticketType])) {
                        continue;
                    }

                    $number = $nextNumbers[$ticketType]++;

                    DB::table('tickets')
                        ->where('id', $ticket->ticket_id)
                        ->update([
                            'ticket_number' => sprintf('%s-%04d', $prefixes[$ticketType], $number),
                        ]);
                }
            });

        foreach ($nextNumbers as $ticketType => $nextNumber) {
            DB::table('ticket_number_sequences')
                ->where('ticket_type', $ticketType)
                ->update(['next_number' => $nextNumber]);
        }
    }

    public function down(): void
    {
        $currentOrderId = null;
        $ticketIndexInOrder = 0;

        DB::table('tickets')
            ->orderBy('order_id')
            ->orderBy('id')
            ->chunk(500, function ($tickets) use (&$currentOrderId, &$ticketIndexInOrder) {
                foreach ($tickets as $ticket) {
                    if ($currentOrderId !== $ticket->order_id) {
                        $currentOrderId = $ticket->order_id;
                        $ticketIndexInOrder = 0;
                    }

                    $ticketIndexInOrder++;

                    DB::table('tickets')
                        ->where('id', $ticket->id)
                        ->update([
                            'ticket_number' => sprintf(
                                'CH-%06d-%02d',
                                $ticket->order_id,
                                $ticketIndexInOrder
                            ),
                        ]);
                }
            });

        Schema::dropIfExists('ticket_number_sequences');
    }
};