<?php

namespace App\Enums;

enum KpiSubmissionStatus: string
{
    case Draft     = 'DRAFT';
    case Submitted = 'SUBMITTED';
    case Approved  = 'APPROVED';
    case Rejected  = 'REJECTED';

    /** Karyawan hanya boleh mengubah saat Draft atau setelah ditolak. */
    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Rejected], true);
    }
}