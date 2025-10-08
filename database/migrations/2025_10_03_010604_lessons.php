<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->longText('body')->nullable(); // Materi teks/HTML/Markdown
            $table->string('video_url')->nullable(); // Link ke video
            $table->string('attachment_url')->nullable(); // Link ke file pendukung (PDF, PPT, dll.)

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
