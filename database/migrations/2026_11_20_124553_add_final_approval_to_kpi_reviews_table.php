<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_reviews', function (Blueprint $table) {
            $table->foreignId('finalized_by')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
            $table->dateTime('finalized_at')->nullable()->after('finalized_by');
            $table->string('final_notes')->nullable()->after('finalized_at');
            $table->index('finalized_at', 'kpi_reviews_finalized_idx');
        });

        // Data lama yang sudah APPROVED dianggap final agar tidak membanjiri antrean Admin
        DB::table('kpi_reviews')->where('status', 'APPROVED')->update(['finalized_at' => DB::raw('reviewed_at')]);
    }

    public function down(): void
    {
        Schema::table('kpi_reviews', function (Blueprint $table) {
            $table->dropIndex('kpi_reviews_finalized_idx');
            $table->dropConstrainedForeignId('finalized_by');
            $table->dropColumn(['finalized_at', 'final_notes']);
        });
    }
};