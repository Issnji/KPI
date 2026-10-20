<?php

namespace App\Support;

use Carbon\CarbonInterface;

final class KpiPeriod
{
    /** @return array{start: string, end: string} */
    public static function for(string $frequency, CarbonInterface $date): array
    {
        [$start, $end] = match ($frequency) {
            'WEEKLY'  => [$date->copy()->startOfWeek(), $date->copy()->endOfWeek()],
            'MONTHLY' => [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()],
            default   => [$date->copy()->startOfDay(), $date->copy()->endOfDay()],
        };

        return ['start' => $start->toDateString(), 'end' => $end->toDateString()];
    }
}