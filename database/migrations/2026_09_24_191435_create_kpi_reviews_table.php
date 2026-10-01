<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('kpi_submissions')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->string('review_type');
            $table->string('status');
            $table->decimal('score', 5, 2)->nullable();
            $table->string('notes')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_reviews');
    }
};