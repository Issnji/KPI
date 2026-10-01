<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_indicators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('kpi_categories')->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('unit')->nullable();
            $table->string('assessment_type');
            $table->string('calculation_type');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_indicators');
    }
};