<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:60'],
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'label.required' => 'Укажите название категории.',
            'label.max' => 'Название не длиннее 60 символов.',
            'color.required' => 'Выберите цвет.',
            'color.regex' => 'Цвет должен быть в формате #RRGGBB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'label' => 'название',
            'color' => 'цвет',
        ];
    }
}
