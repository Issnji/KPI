<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_submissions', function (Blueprint $table) {
            $table->unique(['user_id', 'position_kpi_id', 'period_start'], 'kpi_sub_user_kpi_period_unique');
        });
    }

    public function down(): void
    {
        Schema::table('kpi_submissions', function (Blueprint $table) {
            $table->dropUnique('kpi_sub_user_kpi_period_unique');
        });
    }
};