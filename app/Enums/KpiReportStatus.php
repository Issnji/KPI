<?php

namespace App\Enums;

enum KpiReportStatus: string
{
    case Approved = 'APPROVED';
    case Pending  = 'PENDING';
    case Revision = 'REVISION';

    public static function fromCounts(int $submitted, int $rejected): self
    {
        return match (true) {
            $rejected > 0  => self::Revision,
            $submitted > 0 => self::Pending,
            default        => self::Approved,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Approved => 'Disetujui',
            self::Pending  => 'Menunggu',
            self::Revision => 'Perlu Revisi',
        };
    }
}