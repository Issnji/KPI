<?php // app/Http/Requests/StoreUserRequest.php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dijaga middleware role:admin
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'email'       => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'    => ['required', 'string', 'min:8'],
            'role_id'     => ['required', 'integer', 'exists:roles,id'],
            'position_id' => [
                'nullable', 'integer', 'exists:positions,id',
                Rule::requiredIf(fn () => ! $this->isAdminRole()), // non-admin wajib punya jabatan
            ],
            'is_active'   => ['sometimes', 'boolean'],
        ];
    }

    private function isAdminRole(): bool
    {
        $name = Role::whereKey($this->input('role_id'))->value('name');

        return $name !== null && strtolower(trim($name)) === 'admin';
    }

    public function messages(): array
    {
        return [
            'email.unique'         => 'Email sudah terdaftar.',
            'position_id.required' => 'Jabatan wajib diisi untuk karyawan/manager.',
        ];
    }
}