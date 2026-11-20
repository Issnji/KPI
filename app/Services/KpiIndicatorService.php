<?php 

namespace App\Services;

use App\Models\KpiIndicator;
use App\Models\PositionKpi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use illumintane\Support\Str;

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
        // FE mencocokkan indikator lewat code -> selalu terisi, unik
        $data['code'] ??= $this->makeCode($data['name']);

        return KpiIndicator::create($data)->load('kpiCategory');
    }

    public function update(KpiIndicator $indicator, array $data): KpiIndicator
    {
        return DB::transaction(function () use ($indicator, $data): KpiIndicator {
            // Code dipakai FE sebagai kunci pemetaan -> tidak boleh berubah
            if (isset($data['code']) && $indicator->code !== null && $data['code'] !== $indicator->code) {
                throw ValidationException::withMessages(['code' => 'Kode indikator tidak bisa diubah.']);
            }

            if (
                isset($data['assessment_type'])
                && $data['assessment_type'] !== $indicator->assessment_type
                && $this->hasSubmissions($indicator)
            ) {
                throw ValidationException::withMessages([
                    'assessment_type' => 'Tipe penilaian tidak bisa diubah karena indikator sudah memiliki capaian. Buat indikator baru.',
                ]);
            }

            if (array_key_exists('is_active', $data) && ! $data['is_active'] && $indicator->is_active) {
                $this->assertNotAssigned($indicator);
            }

            $indicator->update($data);

            if ($indicator->wasChanged('calculation_type')) {
                PositionKpi::where('kpi_indicator_id', $indicator->id)
                    ->update(['calculation_type' => $indicator->calculation_type]);
            }

            // BUG LAMA: ganti ke YES_NO membiarkan target jabatan (mis. 100) padahal YES_NO selalu bertarget 1
            if ($indicator->wasChanged('assessment_type') && $indicator->assessment_type === 'YES_NO') {
                PositionKpi::where('kpi_indicator_id', $indicator->id)->update(['target' => 1]);
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
    
    private function makeCode(string $name): string
    {
        $base = Str::limit(Str::upper(Str::slug($name, '_')), 40, '');
        $code = $base;

        for ($i = 2; KpiIndicator::where('code', $code)->exists(); $i++) {
            $code = "{$base}_{$i}";
        }

        return $code;
    }
}