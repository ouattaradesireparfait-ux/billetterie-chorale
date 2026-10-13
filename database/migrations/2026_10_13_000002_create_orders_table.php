<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 32)->unique();
            $t->string('buyer_name', 120);
            $t->string('buyer_email')->nullable();
            $t->string('buyer_phone', 40);
            $t->string('ticket_type', 20);
            $t->unsignedInteger('quantity');
            $t->unsignedInteger('unit_price');
            $t->unsignedInteger('total_amount');
            $t->string('payment_method', 30);
            $t->string('payment_reference', 100)->nullable();
            $t->string('status', 20)->default('pending')->index();
            $t->timestamp('paid_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
