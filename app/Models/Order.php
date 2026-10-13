<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'reference',
        'buyer_name',
        'buyer_email',
        'buyer_phone',
        'ticket_type',
        'quantity',
        'unit_price',
        'total_amount',
        'payment_method',
        'payment_reference',
        'payment_proof_path',
        'payment_proof_uploaded_at',
        'status',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'payment_proof_uploaded_at' => 'datetime',
        ];
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function paymentMethodLabel(): string
    {
        return match ($this->payment_method) {
            'orange_money' => 'Orange Money',
            'wave' => 'Wave',
            'momo' => 'Momo',
            'moov_money' => 'Moov Money',
            default => ucfirst(str_replace('_', ' ', $this->payment_method)),
        };
    }

    public function formattedTotal(): string
    {
        return number_format($this->total_amount, 0, ',', ' ').' FCFA';
    }
}