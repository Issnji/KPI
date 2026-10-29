<?php // app/Http/Requests/StoreKpiIndicatorRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKpiIndicatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dijaga middleware role:admin
    }

    public function rules(): array
    {
        return [
            'category_id'       => ['required', 'integer', 'exists:kpi_categories,id'],
            'name'              => ['required', 'string', 'max:255', 'unique:kpi_indicators,name'],
            'description'       => ['nullable', 'string', 'max:255'],
            'unit'              => ['nullable', 'string', 'max:50'],
            'assessment_type'   => ['required', Rule::in(['NUMERIC', 'YES_NO'])],
            'calculation_type'  => ['required', Rule::in(['HIGHER_IS_BETTER', 'LOWER_IS_BETTER'])],
            'requires_evidence' => ['sometimes', 'boolean'],
            'is_active'         => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return ['name.unique' => 'Nama indikator sudah ada.'];
    }
}