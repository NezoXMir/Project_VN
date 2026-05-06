<?php

namespace App\Http\Requests;

use App\Models\Goal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category' => ['required', Rule::in(array_keys(Goal::CATEGORIES))],
            'deadline' => ['nullable', 'date', 'after:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Укажите название цели.',
            'title.max' => 'Название не длиннее 200 символов.',
            'category.required' => 'Выберите категорию.',
            'category.in' => 'Категория указана некорректно.',
            'deadline.date' => 'Дедлайн должен быть датой.',
            'deadline.after' => 'Дедлайн должен быть в будущем.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'название',
            'description' => 'описание',
            'category' => 'категория',
            'deadline' => 'дедлайн',
        ];
    }
}
