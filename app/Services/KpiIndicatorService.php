<?php 

namespace App\Services;

use App\Models\KpiIndicator;
use App\Models\PositionKpi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KpiIndicatorService
{
    /** @param array<string, mixed> $f */
    public function paginate(array $f, int $perPage = 15): LengthAwarePaginator
    {
        return KpiIndicator::query()
            ->with('kpiCategory:id,name')
            ->withCount(['positionKpis' => fn ($q) => $q->where('is_active', true)])
            ->when($f['search'] ?? null, fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->when($f['category_id'] ?? null, fn ($q, $v) => $q->where('category_id', $v))
            ->when(isset($f['is_active']), fn ($q) => $q->where('is_active', (bool) $f['is_active']))
            ->orderBy('category_id')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function create(array $data): KpiIndicator
    {
        return KpiIndicator::create($data)->load('kpiCategory');
    }

    public function update(KpiIndicator $indicator, array $data): KpiIndicator
    {
        return DB::transaction(function () use ($indicator, $data): KpiIndicator {
            // Tipe input tidak boleh berubah jika sudah ada capaian (data lama jadi tidak valid)
            if (
                isset($data['assessment_type'])
                && $data['assessment_type'] !== $indicator->assessment_type
                && $this->hasSubmissions($indicator)
            ) {
                throw ValidationException::withMessages([
                    'assessment_type' => 'Tipe penilaian tidak bisa diubah karena indikator sudah memiliki capaian. Buat indikator baru.',
                ]);
            }

            // Menonaktifkan indikator yang masih dipakai jabatan akan merusak total bobot 100
            if (array_key_exists('is_active', $data) && ! $data['is_active'] && $indicator->is_active) {
                $this->assertNotAssigned($indicator);
            }

            $indicator->update($data);

            // position_kpis.calculation_type adalah salinan dari indikator -> jaga tetap sinkron
            if ($indicator->wasChanged('calculation_type')) {
                PositionKpi::where('kpi_indicator_id', $indicator->id)
                    ->update(['calculation_type' => $indicator->calculation_type]);
            }

            return $indicator->load('kpiCategory');
        });
    }

    /**
     * Hapus fisik bila belum pernah dipakai; jika sudah ada capaian -> nonaktifkan saja.
     *
     * @return 'deleted'|'deactivated'
     */
    public function remove(KpiIndicator $indicator): string
    {
        return DB::transaction(function () use ($indicator): string {
            $this->assertNotAssigned($indicator);

            if ($this->hasSubmissions($indicator)) {
                $indicator->update(['is_active' => false]);

                return 'deactivated';
            }

            $indicator->delete(); // position_kpis nonaktif tanpa capaian ikut terhapus (cascade)

            return 'deleted';
        });
    }

    private function hasSubmissions(KpiIndicator $indicator): bool
    {
        return $indicator->positionKpis()->whereHas('kpiSubmissions')->exists();
    }

    private function assertNotAssigned(KpiIndicator $indicator): void
    {
        $positions = $indicator->positionKpis()
            ->where('is_active', true)
            ->with('position:id,name')
            ->get()
            ->pluck('position.name');

        if ($positions->isNotEmpty()) {
            throw ValidationException::withMessages([
                'indicator' => 'Indikator masih dipakai oleh jabatan: ' . $positions->implode(', ')
                    . '. Lepas dulu dari jabatan tersebut.',
            ]);
        }
    }
}