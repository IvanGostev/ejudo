<x-mail::message>
    # Новая регистрация

    Зарегистрирован новый пользователь.

    **Email:** {{ $user->email }}
    **Дата:** {{ $user->created_at->format('d.m.Y H:i:s') }}

    <x-mail::button :url="config('app.url') . '/admin/users/' . $user->id . '/edit'">
        Посмотреть в админке
    </x-mail::button>

    Спасибо,<br>
    {{ config('app.name') }}
</x-mail::message>