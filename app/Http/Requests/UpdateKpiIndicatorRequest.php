<?php // app/Http/Requests/UpdateKpiIndicatorRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateKpiIndicatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id'       => ['sometimes', 'integer', 'exists:kpi_categories,id'],
            'name'              => ['sometimes', 'string', 'max:255',
                Rule::unique('kpi_indicators', 'name')->ignore($this->route('kpi_indicator'))],
            'description'       => ['sometimes', 'nullable', 'string', 'max:255'],
            'unit'              => ['sometimes', 'nullable', 'string', 'max:50'],
            'assessment_type'   => ['sometimes', Rule::in(['NUMERIC', 'YES_NO'])],
            'calculation_type'  => ['sometimes', Rule::in(['HIGHER_IS_BETTER', 'LOWER_IS_BETTER'])],
            'requires_evidence' => ['sometimes', 'boolean'],
            'is_active'         => ['sometimes', 'boolean'],
        ];
    }
}