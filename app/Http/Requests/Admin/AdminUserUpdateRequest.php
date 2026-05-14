<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminUserUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        $id = (int) $this->route('id');

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', Rule::unique('users', 'email')->ignore($id)],
            'role' => ['required', Rule::in([User::ROLE_USER, User::ROLE_MANAGER, User::ROLE_ADMIN])],
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
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'имя',
            'email' => 'email',
            'role' => 'роль',
        ];
    }
}
