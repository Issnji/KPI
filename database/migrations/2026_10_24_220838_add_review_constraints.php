<?php // database/migrations/2026_10_25_000000_add_review_support.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Penanda "satu laporan" = user_id + report_date
        Schema::table('kpi_submissions', function (Blueprint $table) {
            $table->date('report_date')->nullable()->after('period_end');
        });

        // Backfill data lama
        DB::statement('UPDATE kpi_submissions SET report_date = COALESCE(DATE(submitted_at), period_start)');

        Schema::table('kpi_submissions', function (Blueprint $table) {
            $table->index(['user_id', 'report_date'], 'kpi_sub_user_report_idx');
            $table->index(['status', 'report_date'], 'kpi_sub_status_report_idx');
        });

        // 1 submission = 1 review aktif (dipakai updateOrCreate)
        Schema::table('kpi_reviews', function (Blueprint $table) {
            $table->unique('submission_id');
        });
    }

    public function down(): void
    {
        Schema::table('kpi_reviews', function (Blueprint $table) {
            $table->dropUnique(['submission_id']);
        });

        Schema::table('kpi_submissions', function (Blueprint $table) {
            $table->dropIndex('kpi_sub_user_report_idx');
            $table->dropIndex('kpi_sub_status_report_idx');
            $table->dropColumn('report_date');
        });
    }
};