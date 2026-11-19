<?php // app/Enums/TeamReportStatus.php

namespace App\Enums;

enum TeamReportStatus: string
{
    case Pending     = 'PENDING';       // badge oranye "Approval"
    case Approved    = 'APPROVED';      // badge hijau "Disetujui"
    case Rejected    = 'REJECTED';
    case NotReported = 'NOT_REPORTED';  // badge abu "Belum Lapor"

    /** Urutan default daftar: yang perlu tindakan di atas. */
    public function priority(): int
    {
        return match ($this) {
            self::Pending     => 0,
            self::Rejected    => 1,
            self::NotReported => 2,
            self::Approved    => 3,
        };
    }
}