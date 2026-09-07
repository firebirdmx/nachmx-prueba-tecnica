<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Escribe un título para la tarea.',
            'title.max' => 'El título admite hasta 255 caracteres.',
            'description.required' => 'Describe la tarea.',
            'description.max' => 'La descripción admite hasta 5000 caracteres.',
        ];
    }
}
