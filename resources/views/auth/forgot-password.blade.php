@extends('layouts.app')

@section('title', 'Esqueci minha senha')

@section('content')
<section class="container-x grid min-h-[85vh] place-items-center pt-32">
    <div data-reveal class="glass w-full max-w-md p-8 sm:p-10">
        <h1 class="text-3xl font-bold">Esqueceu a senha?</h1>
        <p class="mt-2 text-sm text-zinc-400">Digite o e-mail da sua conta. Vamos mandar um código de 6 números para você criar uma senha nova.</p>

        <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-5">
            @csrf
            <div>
                <label class="label" for="email">E-mail</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="input">
                @error('email')<p class="error">{{ $message }}</p>@enderror
            </div>
            <button class="btn-primary w-full !py-3.5">Enviar código</button>
        </form>

        <p class="mt-8 text-center text-sm text-zinc-400">Lembrou? <a href="{{ route('login') }}" class="font-semibold text-neon hover:underline">Voltar para o login</a></p>
    </div>
</section>
@endsection
