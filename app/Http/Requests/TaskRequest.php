<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Укажите название задачи.',
            'title.max' => 'Название не длиннее 200 символов.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'название',
        ];
    }
}
