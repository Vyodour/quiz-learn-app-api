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

            $table->longText('instruction_body');
            $table->longText('initial_code')->nullable();

            $table->enum('language', ['python', 'javascript', 'php', 'java'])->default('python');
            $table->json('test_cases');
            $table->integer('passing_score')->default(100);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('code_challenges');
    }
};
