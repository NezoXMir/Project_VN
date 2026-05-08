<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = Auth::id();

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'string',
                'email:filter',
                'max:160',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'bio' => ['nullable', 'string', 'max:500'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Укажите имя.',
            'name.max' => 'Имя не длиннее 120 символов.',
            'email.required' => 'Укажите email.',
            'email.email' => 'Email указан некорректно.',
            'email.unique' => 'Этот email уже используется другим пользователем.',
            'bio.max' => 'О себе — не длиннее 500 символов.',
            'avatar.image' => 'Аватар должен быть изображением.',
            'avatar.mimes' => 'Аватар: jpeg, jpg, png или webp.',
            'avatar.max' => 'Аватар не больше 2 МБ.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'имя',
            'email' => 'email',
            'bio' => 'о себе',
            'avatar' => 'аватар',
        ];
    }
}
