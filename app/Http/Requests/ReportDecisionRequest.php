<?php // app/Http/Requests/ReportDecisionRequest.php  (approve/reject per laporan)

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dijaga role middleware + KpiSubmissionPolicy
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['APPROVED', 'REJECTED'])],
            'notes'    => [
                'nullable', 'string', 'max:255',
                Rule::requiredIf(fn () => $this->input('decision') === 'REJECTED'),
            ],
        ];
    }

    public function messages(): array
    {
        return ['notes.required' => 'Alasan penolakan wajib diisi.'];
    }
}