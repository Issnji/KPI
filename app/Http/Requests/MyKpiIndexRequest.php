<?php // app/Http/Requests/MyKpiIndexRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MyKpiIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // akses sudah dijaga middleware auth:sanctum
    }

    public function rules(): array
    {
        return [
            'date' => ['nullable', 'date_format:Y-m-d'],
        ];
    }
}