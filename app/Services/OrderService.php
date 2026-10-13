<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
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
                    'ticket_number' => sprintf('CH-%06d-%02d', $order->id, $index),
                    'token_hash' => hash('sha256', $token),
                    'token' => $token,
                    'status' => 'valid',
                ]);
            }

            return $order->load('tickets');
        });
    }
}
