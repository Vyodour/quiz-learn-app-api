<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_unit_order', function (Blueprint $table) {
            $table->id();

            $table->foreignId('content_id')
                ->constrained('contents')
                ->onDelete('cascade');

            $table->string('title')->nullable();
            $table->integer('order_number')->default(1);

            $table->morphs('ordered_unit');

            $table->timestamps();

            $table->unique(['content_id', 'order_number']);

            $table->boolean('is_premium')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_unit_order');
    }
};
