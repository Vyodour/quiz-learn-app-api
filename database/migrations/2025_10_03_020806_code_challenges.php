<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('code_challenges', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->enum('language', ['python', 'javascript', 'php', 'java']);

            $table->integer('passing_score')->default(100);
            $table->longText('instructions');
            $table->longText('starter_code')->nullable();
            $table->json('test_cases');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('code_challenges');
    }
};
