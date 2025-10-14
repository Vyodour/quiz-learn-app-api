<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom yang diperlukan untuk sistem langganan skala bisnis.
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('plan_id')
                ->nullable()
                ->after('user_id')
                ->constrained('plans')
                ->onDelete('set null');

            $table->string('gateway_subscription_id')->nullable()->after('ends_at');
            $table->string('payment_method')->nullable()->after('gateway_subscription_id');

            $table->timestamp('ends_at')->nullable()->change();

            if (Schema::hasColumn('subscriptions', 'is_active')) {
                $table->dropColumn('is_active');
            }
            $table->enum('status', ['pending', 'active', 'cancelled', 'expired'])->default('pending')->after('ends_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn(['plan_id', 'gateway_subscription_id', 'payment_method', 'status']);
            $table->boolean('is_active')->default(false)->after('user_id');
        });
    }
};
