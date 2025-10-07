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

            $table->string('name')->comment('Nama atau deskripsi singkat Quiz.');
            $table->integer('passing_score')->default(75)->comment('Nilai minimum kelulusan (0-100).');
            $table->integer('time_limit')->nullable()->comment('Batas waktu pengerjaan dalam menit.');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quizzes_information');
    }
};
