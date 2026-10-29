<?php // app/Http/Requests/AdminSubmissionIndexRequest.php

namespace App\Http\Requests;

use App\Enums\KpiSubmissionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminSubmissionIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id'     => ['nullable', 'integer', 'exists:users,id'],
            'role_id'     => ['nullable', 'integer', 'exists:roles,id'],
            'position_id' => ['nullable', 'integer', 'exists:positions,id'],
            'status'      => ['nullable', Rule::enum(KpiSubmissionStatus::class)],
            'date_from'   => ['nullable', 'date_format:Y-m-d'],
            'date_to'     => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'per_page'    => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}