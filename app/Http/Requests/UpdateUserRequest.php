<?php // app/Http/Requests/UpdateUserRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['sometimes', 'string', 'max:255'],
            'email'       => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'password'    => ['sometimes', 'string', 'min:8'],
            'role_id'     => ['sometimes', 'integer', 'exists:roles,id'],
            'position_id' => ['sometimes', 'nullable', 'integer', 'exists:positions,id'],
            'is_active'   => ['sometimes', 'boolean'],
        ];
    }
}