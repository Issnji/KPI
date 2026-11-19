<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Http\FormRequest;

class DashboardDateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dijaga auth:sanctum + role middleware
    }

    public function rules(): array
    {
        return [
            'date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ];
    }

    // Bukan date(): bentrok dengan Request::date() bawaan Laravel
    public function targetDate(): CarbonInterface
    {
        return $this->filled('date') ? Carbon::parse($this->input('date')) : now();
    }
}