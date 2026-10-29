<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class OrderService
{
    private const TICKET_PREFIXES = [
        'standard' => 'STD',
        'vip' => 'VIP',
    ];

    private const TICKET_CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    private const TICKET_CODE_LENGTH = 12;

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
