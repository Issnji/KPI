<?php 

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('position_kpis', function (Blueprint $table) {
            $table->unique(['position_id', 'kpi_indicator_id'], 'position_kpi_unique');
        });
    }

    public function down(): void
    {
        Schema::table('position_kpis', function (Blueprint $table) {
            $table->dropUnique('position_kpi_unique');
        });
    }
};