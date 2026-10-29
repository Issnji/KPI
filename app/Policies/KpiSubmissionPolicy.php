<?php // app/Policies/KpiSubmissionPolicy.php

namespace App\Policies;

use App\Models\KpiSubmission;
use App\Models\User;

class KpiSubmissionPolicy
{
    public function review(User $reviewer, KpiSubmission $submission): bool
    {
        // Tidak boleh me-review capaian sendiri
        if ($submission->user_id === $reviewer->id) {
            return false;
        }

        if ($reviewer->hasRole('admin')) {
            return true;
        }

        // Store Manager: hanya capaian karyawan
        if ($reviewer->hasRole('manager') && $reviewer->hasPosition('store manager')) {
            $submission->loadMissing('user.role');

            return (bool) $submission->user?->hasRole('karyawan');
        }

        return false;
    }
}