<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMyKpiSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dijaga auth:sanctum + role middleware
    }

    public function rules(): array
    {
        return [
            'date'                    => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'submit'                  => ['sometimes', 'boolean'],
            'items'                   => ['required', 'array', 'min:1'],
            'items.*.position_kpi_id' => ['required', 'integer', 'distinct'],
            'items.*.answer_yes_no'   => ['nullable', 'boolean'],
            'items.*.numeric_value'   => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'                   => 'Minimal satu indikator harus diisi.',
            'items.*.position_kpi_id.distinct' => 'Indikator tidak boleh duplikat dalam satu pengiriman.',
            'items.*.numeric_value.min'        => 'Nilai tidak boleh negatif.',
            'date.before_or_equal'             => 'Tanggal tidak boleh di masa depan.',
        ];
    }
}