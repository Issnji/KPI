<?php // app/Support/KpiScore.php

namespace App\Support;

use App\Models\KpiSubmission;
use App\Models\PositionKpi;

final class KpiScore
{
    public const CAP = 120.0; // batas pencapaian maksimum (%)

    /** Persentase pencapaian 0..CAP untuk satu capaian. */
    public static function achievement(KpiSubmission $submission, PositionKpi $pk): float
    {
        if ($pk->kpiIndicator->assessment_type === 'YES_NO') {
            $raw = $submission->answer_yes_no ? 100.0 : 0.0;
        } else {
            $actual = (float) $submission->numeric_value;
            $target = (float) $pk->target;

            $raw = $pk->calculation_type === 'LOWER_IS_BETTER'
                ? self::lowerIsBetter($actual, $target)
                : self::higherIsBetter($actual, $target);
        }

        return round(min(max($raw, 0.0), self::CAP), 2);
    }

    private static function higherIsBetter(float $actual, float $target): float
    {
        return $target <= 0 ? 100.0 : ($actual / $target) * 100;
    }

    private static function lowerIsBetter(float $actual, float $target): float
    {
        if ($actual <= 0) {
            return self::CAP;   // tidak ada waste/biaya = hasil terbaik (hindari bagi nol)
        }

        return $target <= 0 ? 0.0 : ($target / $actual) * 100;
    }
}