@extends('layouts.app')

@section('title', 'Criar conta')

@section('content')
<section class="container-x grid min-h-[85vh] place-items-center pt-32">
    <div data-reveal class="glass w-full max-w-md p-8 sm:p-10">
        <h1 class="text-3xl font-bold">Crie sua conta</h1>
        <p class="mt-2 text-sm text-zinc-400">Leva menos de um minuto.</p>

        <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-5">
            @csrf
            <div>
                <label class="label" for="name">Nome completo</label>
                <input id="name" name="name" value="{{ old('name') }}" required autofocus class="input">
                @error('name')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="email">E-mail</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required class="input">
                @error('email')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="phone">Celular (opcional)</label>
                <input id="phone" name="phone" data-phone value="{{ old('phone') }}" class="input">
            </div>
            <div>
                <label class="label" for="password">Senha</label>
                <input id="password" name="password" type="password" required autocomplete="new-password" class="input">
                <p class="mt-1 text-xs text-zinc-500">Mínimo de 8 caracteres, com letras e números.</p>
                @error('password')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="password_confirmation">Confirme a senha</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required class="input">
            </div>
            <button class="btn-primary w-full !py-3.5">Criar conta</button>
        </form>

        <p class="mt-8 text-center text-sm text-zinc-400">Já tem conta? <a href="{{ route('login') }}" class="font-semibold text-neon hover:underline">Entrar</a></p>
    </div>
</section>
@endsection
