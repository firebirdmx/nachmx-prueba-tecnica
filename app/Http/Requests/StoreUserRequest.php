<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->email)) {
            $this->merge(['email' => mb_strtolower(trim($this->email))]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Escribe el nombre del usuario.',
            'email.required' => 'Escribe un correo electrónico.',
            'email.email' => 'El correo electrónico no es válido.',
            'email.unique' => 'Ya existe un usuario con este correo.',
        ];
    }
}
