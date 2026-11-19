<?php

namespace App\Http\Requests;

use App\Enums\TeamReportStatus;
use Illuminate\Validation\Rule;

class TeamDashboardRequest extends DashboardDateRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->canReviewTeam();
    }

    public function rules(): array
    {
        return parent::rules() + [
            'status' => ['nullable', Rule::enum(TeamReportStatus::class)],
            'sort'   => ['nullable', Rule::in(['priority', 'name'])],
        ];
    }
}