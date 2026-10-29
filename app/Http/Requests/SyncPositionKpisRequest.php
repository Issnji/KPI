<?php // app/Http/Requests/SyncPositionKpisRequest.php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncPositionKpisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items'                    => ['required', 'array', 'min:1'],
            'items.*.kpi_indicator_id' => ['required', 'integer', 'distinct', 'exists:kpi_indicators,id'],
            'items.*.target'           => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'items.*.weight'           => ['required', 'numeric', 'min:0.01', 'max:100'],
            'items.*.frequency'        => ['required', Rule::in(['DAILY', 'WEEKLY', 'MONTHLY'])],
        ];
    }

    /** Total bobot per jabatan wajib tepat 100. */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $total = round(array_sum(array_column($this->input('items', []), 'weight')), 2);

                if ($total !== 100.0) {
                    $validator->errors()->add('items', "Total bobot harus 100 (saat ini {$total}).");
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'                    => 'Minimal satu indikator harus dipilih.',
            'items.*.kpi_indicator_id.distinct' => 'Indikator tidak boleh duplikat.',
        ];
    }
}