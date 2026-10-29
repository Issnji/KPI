<?php // app/Http/Requests/ReviewDecisionRequest.php  (per indikator / bulk, opsional)

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isBulk = ! $this->route('kpiSubmission');

        return [
            'decision'         => ['required', Rule::in(['APPROVED', 'REJECTED'])],
            'notes'            => [
                'nullable', 'string', 'max:255',
                Rule::requiredIf(fn () => $this->input('decision') === 'REJECTED'),
            ],
            'submission_ids'   => [Rule::requiredIf($isBulk), 'array', 'min:1', 'max:100'],
            'submission_ids.*' => ['integer', 'distinct', 'exists:kpi_submissions,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'notes.required'          => 'Alasan penolakan wajib diisi.',
            'submission_ids.required' => 'Pilih minimal satu capaian.',
        ];
    }
}