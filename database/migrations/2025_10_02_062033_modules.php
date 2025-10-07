<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('learning_path_id')
                  ->nullable()
                  ->constrained('learning_paths')
                  ->onDelete('set null');

            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('description')->nullable();
            $table->integer('duration');
            $table->enum('level', ['simple', 'moderate', 'advance'])
                  ->default('simple');
            $table->float('rating', 2, 1)->default(0.0);
            $table->integer('order_number')->default(1);
            $table->timestamps();
            $table->index(['learning_path_id', 'order_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
