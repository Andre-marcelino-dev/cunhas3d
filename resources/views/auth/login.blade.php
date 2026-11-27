@extends('layouts.app')

@section('title', 'Entrar')

@section('content')
<section class="container-x grid min-h-[85vh] place-items-center pt-32">
    <div data-reveal class="glass w-full max-w-md p-8 sm:p-10">
        <h1 class="text-3xl font-bold">Bem-vindo de volta</h1>
        <p class="mt-2 text-sm text-zinc-400">Entre para acompanhar seus pedidos e finalizar compras.</p>

        <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
            @csrf
            <div>
                <label class="label" for="email">E-mail</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="input">
                @error('email')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <div class="flex items-baseline justify-between gap-3">
                    <label class="label" for="password">Senha</label>
                    <a href="{{ route('password.request') }}" class="text-xs text-neon hover:underline">Esqueci minha senha</a>
                </div>
                <input id="password" name="password" type="password" required autocomplete="current-password" class="input">
            </div>
            <label class="flex items-center gap-2 text-sm text-zinc-400">
                <input type="checkbox" name="remember" class="accent-cyan-400"> Manter conectado
            </label>
            <button class="btn-primary w-full !py-3.5">Entrar</button>
        </form>

        <p class="mt-8 text-center text-sm text-zinc-400">Ainda não tem conta? <a href="{{ route('register') }}" class="font-semibold text-neon hover:underline">Cadastre-se</a></p>
    </div>
</section>
@endsection
