<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
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
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(function ($q) {
                    $q->where(function ($q2) {
                        $q2->where('is_system', true)
                            ->orWhere('user_id', Auth::id());
                    });
                }),
            ],
            'deadline' => ['nullable', 'date', 'after:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Укажите название цели.',
            'title.max' => 'Название не длиннее 200 символов.',
            'category_id.required' => 'Выберите категорию.',
            'category_id.exists' => 'Категория недоступна.',
            'deadline.date' => 'Дедлайн должен быть датой.',
            'deadline.after' => 'Дедлайн должен быть в будущем.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'название',
            'description' => 'описание',
            'category_id' => 'категория',
            'deadline' => 'дедлайн',
        ];
    }
}
