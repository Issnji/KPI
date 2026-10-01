<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('position_kpis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('position_id')->constrained('positions')->cascadeOnDelete();
            $table->foreignId('kpi_indicator_id')->constrained('kpi_indicators')->cascadeOnDelete();
            $table->decimal('target', 10, 2);
            $table->decimal('weight', 5, 2);
            $table->string('frequency');
            $table->string('calculation_type');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('position_kpis');
    }
};