@extends('layouts.app')

@section('title', 'Редактирование категории — ' . config('app.name'))

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Редактирование категории</h1>
    </div>

    <x-card padding="p-6" class="max-w-xl">
        @include('categories._form', [
            'action' => route('categories.update', $category->id),
            'method' => 'PATCH',
            'palette' => $palette,
            'userPalette' => $userPalette,
            'category' => $category,
            'submitLabel' => 'Сохранить',
        ])
    </x-card>
@endsection
