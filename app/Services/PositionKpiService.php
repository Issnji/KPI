<?php // app/Services/PositionKpiService.php

namespace App\Services;

use App\Enums\KpiSubmissionStatus;
use App\Models\KpiIndicator;
use App\Models\Position;
use App\Models\PositionKpi;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PositionKpiService
{
    public function __construct(private readonly KpiCatalogService $catalog) {}

    /**
     * Timpa seluruh daftar KPI sebuah jabatan (tambah / ubah / kurangi sekaligus).
     * Validasi total bobot = 100 sudah di FormRequest; di sini atomik via transaksi.
     *
     * @param array<int, array{kpi_indicator_id:int, target:numeric, weight:numeric, frequency:string}> $items
     */
    public function sync(Position $position, array $items): Collection
    {
        return DB::transaction(function () use ($position, $items): Collection {
            $indicators = KpiIndicator::query()
                ->whereIn('id', array_column($items, 'kpi_indicator_id'))
                ->get()
                ->keyBy('id');

            $inactive = $indicators->where('is_active', false)->pluck('name');
            if ($inactive->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Indikator nonaktif tidak bisa ditugaskan: ' . $inactive->implode(', '),
                ]);
            }

            $existing = PositionKpi::query()
                ->where('position_id', $position->id)
                ->lockForUpdate()
                ->get()
                ->keyBy('kpi_indicator_id');

            foreach ($items as $i => $item) {
                $indicator = $indicators[(int) $item['kpi_indicator_id']];
                $pk        = $existing->get($indicator->id)
                    ?? new PositionKpi(['position_id' => $position->id, 'kpi_indicator_id' => $indicator->id]);

                // Ganti frekuensi saat ada capaian berjalan akan membuat periode tumpang tindih
                if ($pk->exists && $pk->frequency !== $item['frequency'] && $this->hasOpenSubmissions($pk)) {
                    throw ValidationException::withMessages([
                        "items.$i.frequency" => "Frekuensi '{$indicator->name}' tidak bisa diubah: masih ada capaian DRAFT/SUBMITTED.",
                    ]);
                }

                $pk->fill([
                    // YES_NO selalu bertarget 1 (ya = 100%, tidak = 0%)
                    'target'           => $indicator->assessment_type === 'YES_NO' ? 1 : $item['target'],
                    'weight'           => $item['weight'],
                    'frequency'        => $item['frequency'],
                    'calculation_type' => $indicator->calculation_type,
                    'is_active'        => true,
                ])->save();
            }

            // Indikator yang dikurangi: hapus jika belum punya capaian, kalau sudah -> nonaktifkan
            $keep = collect($items)->pluck('kpi_indicator_id')->map(fn ($v) => (int) $v);

            foreach ($existing->except($keep->all()) as $pk) {
                if (! $pk->is_active) {
                    continue;
                }

                $pk->kpiSubmissions()->exists()
                    ? $pk->update(['is_active' => false])
                    : $pk->delete();
            }

            return $this->catalog->forPosition($position->id);
        });
    }

    private function hasOpenSubmissions(PositionKpi $pk): bool
    {
        return $pk->kpiSubmissions()
            ->whereIn('status', [KpiSubmissionStatus::Draft->value, KpiSubmissionStatus::Submitted->value])
            ->exists();
    }
}