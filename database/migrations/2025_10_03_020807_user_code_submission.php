<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_code_submissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            $table->foreignId('code_challenge_id')
                  ->constrained('code_challenges')
                  ->onDelete('cascade');

            $table->boolean('is_passed')->default(false);
            $table->integer('score')->default(0);
            $table->longText('submitted_code');
            $table->json('grading_log')->nullable()->comment('Log eksekusi, error, dan hasil test case.');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_code_submissions');
    }
};
