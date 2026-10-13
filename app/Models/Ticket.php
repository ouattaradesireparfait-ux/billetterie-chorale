<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    protected $fillable = [
        'order_id',
        'ticket_number',
        'token_hash',
        'token',
        'status',
        'used_at',
        'scanned_by',
    ];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return [
            'token' => 'encrypted',
            'used_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
