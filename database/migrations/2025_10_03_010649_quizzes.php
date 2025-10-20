<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes_information', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->integer('passing_score')->default(75);
            $table->integer('time_limit')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quizzes_information');
    }
};
