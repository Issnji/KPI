<?php

namespace App\Http\Requests;

use App\Enums\KpiReportStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MyKpiHistoryIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dijaga auth:sanctum + role middleware; data selalu milik user login
    }

    public function rules(): array
    {
        return [
            'status'    => ['nullable', Rule::enum(KpiReportStatus::class)],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to'   => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'per_page'  => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}