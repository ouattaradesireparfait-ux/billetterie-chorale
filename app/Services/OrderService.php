<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

class OrderService
{
    private const TICKET_PREFIXES = [
        'standard' => 'STD',
        'vip' => 'VIP',
    ];

    private const TICKET_CODE_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    private const TICKET_CODE_LENGTH = 4;

    public function create(array $data, bool $paid = false): Order
    {
        return DB::transaction(function () use ($data, $paid) {
            $ticketType = $data['ticket_type'];
            $unitPrice = config("ticketing.ticket_types.{$ticketType}.price");
            $quantity = (int) $data['quantity'];

            $order = Order::create([
                'reference' => 'CMD-'.now()->format('ymd').'-'.Str::upper(Str::random(16)),
                'buyer_name' => $data['buyer_name'],
                'buyer_email' => $data['buyer_email'] ?? null,
                'buyer_phone' => $data['buyer_phone'],
                'ticket_type' => $ticketType,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_amount' => $unitPrice * $quantity,
                'payment_method' => $data['payment_method'],
                'payment_reference' => $data['payment_reference'] ?? null,
                'payment_proof_path' => $data['payment_proof_path'] ?? null,
                'payment_proof_uploaded_at' => $data['payment_proof_uploaded_at'] ?? null,
                'status' => $paid ? 'paid' : 'pending',
                'paid_at' => $paid ? now() : null,
            ]);

            for ($index = 1; $index <= $order->quantity; $index++) {
                $token = Str::random(48);

                $order->tickets()->create([
                    'ticket_number' => $this->nextTicketNumber($ticketType),
                    'token_hash' => hash('sha256', $token),
                    'token' => $token,
                    'status' => 'valid',
                ]);
            }

            return $order->load('tickets');
        });
    }

    private function nextTicketNumber(string $ticketType): string
    {
        $prefix = self::TICKET_PREFIXES[$ticketType] ?? null;

        if ($prefix === null) {
            throw new InvalidArgumentException('Type de billet inconnu : '.$ticketType);
        }

        $sequence = DB::table('ticket_number_sequences')
            ->where('ticket_type', $ticketType)
            ->lockForUpdate()
            ->first();

        if ($sequence === null) {
            throw new RuntimeException(
                "La numerotation des billets n'est pas initialisee. Executez les migrations."
            );
        }

        $number = (int) $sequence->next_number;

        if ($number > 999) {
            throw ValidationException::withMessages([
                'quantity' => 'La limite de 999 billets '.$prefix.' est atteinte.',
            ]);
        }

        do {
            $ticketNumber = sprintf(
                '%s-%s-%03d',
                $prefix,
                $this->randomTicketCode(),
                $number
            );
        } while (
            DB::table('tickets')
                ->where('ticket_number', $ticketNumber)
                ->exists()
        );

        DB::table('ticket_number_sequences')
            ->where('ticket_type', $ticketType)
            ->update(['next_number' => $number + 1]);

        return $ticketNumber;
    }

    private function randomTicketCode(): string
    {
        $alphabetLength = strlen(self::TICKET_CODE_ALPHABET);
        $characters = '';

        for ($index = 0; $index < self::TICKET_CODE_LENGTH; $index++) {
            $characters .= self::TICKET_CODE_ALPHABET[
                random_int(0, $alphabetLength - 1)
            ];
        }

        return implode('-', str_split($characters, 4));
    }
}
