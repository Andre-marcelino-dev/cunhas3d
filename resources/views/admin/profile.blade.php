@extends('layouts.admin')

@section('title', 'Meu perfil')

@section('content')
<div class="grid max-w-5xl gap-6 lg:grid-cols-2">
    {{-- Dados do administrador --}}
    <form method="POST" action="{{ route('admin.profile.update') }}" class="glass space-y-5 !rounded-2xl p-6 sm:p-8">
        @csrf
        @method('PUT')
        <h2 class="font-display text-base font-semibold text-white">Dados da conta</h2>

        <div>
            <label class="label" for="name">Nome *</label>
            <input id="name" name="name" value="{{ old('name', $admin->name) }}" required class="input">
            @error('name')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label" for="email">E-mail *</label>
            <input id="email" name="email" type="email" value="{{ old('email', $admin->email) }}" required class="input">
            @error('email')<p class="error">{{ $message }}</p>@enderror
        </div>

        <button class="btn-primary">Salvar dados</button>
    </form>

    {{-- Troca de senha --}}
    <form method="POST" action="{{ route('admin.profile.password') }}" class="glass space-y-5 !rounded-2xl p-6 sm:p-8">
        @csrf
        @method('PUT')
        <h2 class="font-display text-base font-semibold text-white">Trocar senha</h2>

        <div>
            <label class="label" for="current_password">Senha atual *</label>
            <input id="current_password" name="current_password" type="password" required autocomplete="current-password" class="input">
            @error('current_password')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label" for="password">Nova senha *</label>
            <input id="password" name="password" type="password" required autocomplete="new-password" class="input">
            <p class="mt-1 text-xs text-zinc-500">Mínimo de 8 caracteres, com letras e números.</p>
            @error('password')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label" for="password_confirmation">Confirmar nova senha *</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="input">
        </div>

        <button class="btn-primary">Alterar senha</button>
    </form>
</div>
@endsection
