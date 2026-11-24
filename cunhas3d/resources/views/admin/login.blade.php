<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex">
    <title>Entrar · Admin {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/admin.js'])
</head>
<body class="grid min-h-screen place-items-center bg-ink px-4 font-sans">
    <div class="glass w-full max-w-md p-8 sm:p-10">
        <div class="flex items-center gap-2">
            <span class="font-display text-lg font-bold text-white">Cunhas <span class="text-gradient">3D</span></span>
            <span class="badge border-neon/30 text-neon">admin</span>
        </div>
        <h1 class="mt-6 text-3xl font-bold">Painel administrativo</h1>
        <p class="mt-2 text-sm text-zinc-400">Acesso restrito à equipe da loja.</p>

        <form method="POST" action="{{ route('admin.login') }}" class="mt-8 space-y-5">
            @csrf
            <div>
                <label class="label" for="email">E-mail</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="input">
                @error('email')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="password">Senha</label>
                <input id="password" name="password" type="password" required autocomplete="current-password" class="input">
            </div>
            <label class="flex items-center gap-2 text-sm text-zinc-400">
                <input type="checkbox" name="remember" class="accent-cyan-400"> Manter conectado
            </label>
            <button class="btn-primary w-full !py-3.5">Entrar no painel</button>
        </form>

        <p class="mt-8 text-center text-sm text-zinc-500"><a href="{{ route('home') }}" class="hover:text-white">← Voltar para a loja</a></p>
    </div>
</body>
</html>
