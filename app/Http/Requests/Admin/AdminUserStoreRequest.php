<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminUserStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', 'unique:users,email'],
            'role' => ['required', Rule::in([User::ROLE_USER, User::ROLE_MANAGER, User::ROLE_ADMIN])],
            // Пароль приходит из формы — кнопка-генератор подставит
            // 8 символов, либо админ может ввести вручную. Минимум — 8.
            'password' => ['required', 'string', 'min:8', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Укажите имя пользователя.',
            'email.required' => 'Укажите email.',
            'email.email' => 'Email указан некорректно.',
            'email.unique' => 'Пользователь с таким email уже существует.',
            'role.required' => 'Укажите роль.',
            'role.in' => 'Недопустимая роль.',
            'password.required' => 'Сгенерируйте или укажите пароль.',
            'password.min' => 'Пароль должен быть не короче 8 символов.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'имя',
            'email' => 'email',
            'role' => 'роль',
            'password' => 'пароль',
        ];
    }
}
