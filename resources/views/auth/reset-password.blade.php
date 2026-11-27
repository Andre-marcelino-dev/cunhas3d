@extends('layouts.app')

@section('title', 'Criar nova senha')

@section('content')
<section class="container-x grid min-h-[85vh] place-items-center pt-32">
    <div data-reveal class="glass w-full max-w-md p-8 sm:p-10">
        <h1 class="text-3xl font-bold">Criar nova senha</h1>
        <p class="mt-2 text-sm text-zinc-400">
            Enviamos um código para <strong class="text-white">{{ old('email', $email) ?: 'o seu e-mail' }}</strong>.
            Ele vale por 30 minutos. Não chegou? Olhe a caixa de spam.
        </p>

        <form method="POST" action="{{ route('password.update') }}" class="mt-8 space-y-5">
            @csrf
            <div>
                <label class="label" for="email">E-mail</label>
                <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required autocomplete="email" class="input">
                @error('email')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="code">Código de 6 números</label>
                <input id="code" name="code" value="{{ old('code') }}" required inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                       autocomplete="one-time-code" @if(old('email', $email)) autofocus @endif
                       class="input text-center font-display text-2xl tracking-[0.5em]">
                @error('code')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="password">Nova senha</label>
                <input id="password" name="password" type="password" required autocomplete="new-password" class="input">
                <p class="mt-1 text-xs text-zinc-500">Mínimo de 8 caracteres, com letras e números.</p>
                @error('password')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="password_confirmation">Confirme a nova senha</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="input">
            </div>
            <button class="btn-primary w-full !py-3.5">Salvar nova senha</button>
        </form>

        <p class="mt-8 text-center text-sm text-zinc-400"><a href="{{ route('password.request') }}" class="font-semibold text-neon hover:underline">Pedir um novo código</a></p>
    </div>
</section>
@endsection
