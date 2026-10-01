<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('position_kpi_id')->constrained('position_kpis')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->boolean('answer_yes_no')->nullable();
            $table->decimal('numeric_value', 10, 2)->nullable();
            $table->string('status');
            $table->dateTime('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_submissions');
    }
};