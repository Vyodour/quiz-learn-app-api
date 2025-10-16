<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->onDelete('set null');

            $table->foreignId('plan_id')
                ->nullable()
                ->constrained('plans')
                ->onDelete('set null');

            $table->string('transaction_code')->unique();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('IDR');

            $table->enum('status', ['pending', 'paid', 'failed', 'expired', 'denied'])->default('pending');
            $table->string('payment_gateway');

            $table->string('payment_gateway_id')
                ->nullable();

            $table->timestamp('paid_at')->nullable();

            $table->json('gateway_response')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
