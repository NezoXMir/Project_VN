<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PasswordChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Укажите текущий пароль.',
            'password.required' => 'Укажите новый пароль.',
            'password.min' => 'Новый пароль должен быть не короче 8 символов.',
            'password.confirmed' => 'Подтверждение нового пароля не совпадает.',
        ];
    }
}
