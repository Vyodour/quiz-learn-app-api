<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('module_id')
                ->constrained('modules')
                ->onDelete('cascade');

            $table->string('title');
            $table->string('slug')->unique();
            $table->integer('order_number')->default(1);

            $table->timestamps();

            $table->unique(['module_id', 'order_number']);
            $table->index('module_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contents');
    }
};
