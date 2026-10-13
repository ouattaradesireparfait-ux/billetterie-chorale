<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    public function create(array $d, bool $paid = false): Order
    {
        return DB::transaction(function () use ($d, $paid) {
            $type = $d['ticket_type'];
            $price = config("ticketing.ticket_types.$type.price");
            $o = Order::create(['reference' => 'CMD-'.now()->format('ymd').'-'.Str::upper(Str::random(16)), 'buyer_name' => $d['buyer_name'], 'buyer_email' => $d['buyer_email'] ?? null, 'buyer_phone' => $d['buyer_phone'], 'ticket_type' => $type, 'quantity' => (int) $d['quantity'], 'unit_price' => $price, 'total_amount' => $price * (int) $d['quantity'], 'payment_method' => $d['payment_method'], 'payment_reference' => $d['payment_reference'] ?? null, 'status' => $paid ? 'paid' : 'pending', 'paid_at' => $paid ? now() : null]);
            for ($i = 1; $i <= $o->quantity; $i++) {
                $token = Str::random(48);
                $o->tickets()->create(['ticket_number' => sprintf('CH-%06d-%02d', $o->id, $i), 'token_hash' => hash('sha256', $token), 'token' => $token, 'status' => 'valid']);
            }

return $o->load('tickets');
        });
    }
}
