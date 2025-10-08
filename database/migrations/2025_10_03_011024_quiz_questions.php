<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('quiz_information_id')
                  ->constrained('quizzes_information')
                  ->onDelete('cascade');

            $table->longText('question_text');
            $table->json('options');
            $table->unsignedTinyInteger('correct_option_index');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_questions');
    }
};
