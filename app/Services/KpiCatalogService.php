<?php

namespace App\Services;

use App\Models\KpiCategory;
use App\Models\KpiSubmission;
use App\Models\PositionKpi;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

class KpiCatalogService
{
    public function categoriesWithIndicators(): Collection
    {
        return KpiCategory::query()
            ->with(['kpiIndicators' => fn ($q) => $q->where('is_active', true)->orderBy('name')])
            ->orderBy('name')
            ->get();
    }

    public function forPosition(int $positionId): Collection
    {
        return PositionKpi::query()
            ->with('kpiIndicator.kpiCategory')   // eager load: cegah N+1
            ->where('position_id', $positionId)
            ->where('is_active', true)
            ->orderByDesc('weight')
            ->get();
    }

    /**
     * KPI milik user + periode berjalan + submission di periode itu (jika ada).
     */
    public function forUser(User $user, ?CarbonInterface $date = null): Collection
    {
        abort_if(! $user->position_id, 422, 'Akun belum memiliki posisi/jabatan.');

        $date ??= now();
        $items   = $this->forPosition($user->position_id);

        if ($items->isEmpty()) {
            return $items;
        }

        $windows = $items->mapWithKeys(
            fn (PositionKpi $pk) => [$pk->id => $this->period($pk->frequency, $date)]
        );

        // 1 query untuk semua submission di rentang periode (bukan 1 query per KPI)
        $submissions = KpiSubmission::query()
            ->where('user_id', $user->id)
            ->whereIn('position_kpi_id', $items->pluck('id'))
            ->whereDate('period_start', '>=', $windows->min('start'))
            ->whereDate('period_end', '<=', $windows->max('end'))
            ->get()
            ->groupBy('position_kpi_id');

        return $items->each(function (PositionKpi $pk) use ($windows, $submissions): void {
            $window = $windows[$pk->id];

            $pk->setAttribute('current_period', $window);
            $pk->setRelation(
                'currentSubmission',
                ($submissions[$pk->id] ?? collect())
                    ->first(fn (KpiSubmission $s) => $s->period_start->toDateString() === $window['start'])
            );
        });
    }

    /** @return array{start: string, end: string} */
    private function period(string $frequency, CarbonInterface $date): array
    {
        [$start, $end] = match ($frequency) {
            'WEEKLY'  => [$date->copy()->startOfWeek(), $date->copy()->endOfWeek()],
            'MONTHLY' => [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()],
            default   => [$date->copy()->startOfDay(), $date->copy()->endOfDay()],
        };

        return ['start' => $start->toDateString(), 'end' => $end->toDateString()];
    }
}