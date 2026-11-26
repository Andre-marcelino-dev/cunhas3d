<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex">
    <title>@yield('title', 'Painel') · Admin {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/admin.js'])
</head>
<body class="min-h-screen bg-ink font-sans" x-data="{ side: false }">
@php
    $nav = [
        ['admin.dashboard', 'Dashboard', 'M3 12l9-8 9 8M5 10v10h14V10'],
        ['admin.orders.*', 'Pedidos', 'M6 7h12l-1 13H7L6 7Zm3 0a3 3 0 0 1 6 0', 'admin.orders.index'],
        ['admin.products.*', 'Produtos', 'M12 3 3 8l9 5 9-5-9-5Zm-9 5v8l9 5 9-5V8', 'admin.products.index'],
        ['admin.categories.*', 'Categorias', 'M4 5h7v7H4zM13 5h7v7h-7zM4 14h7v6H4zM13 14h7v6h-7z', 'admin.categories.index'],
        ['admin.customers.*', 'Clientes', 'M16 19v-1a4 4 0 0 0-8 0v1M12 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z', 'admin.customers.index'],
        ['admin.profile.*', 'Meu perfil', 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 9a7 7 0 0 1 14 0', 'admin.profile.edit'],
    ];
@endphp

<aside class="fixed inset-y-0 left-0 z-40 w-64 -translate-x-full border-r border-white/5 bg-panel/95 backdrop-blur-xl transition lg:translate-x-0" :class="side && '!translate-x-0'">
    <div class="flex h-16 items-center gap-2 px-6">
        <span class="font-display text-lg font-bold text-white">Cunhas <span class="text-gradient">3D</span></span>
        <span class="badge border-neon/30 text-neon">admin</span>
    </div>
    <nav class="space-y-1 px-3 py-4">
        @foreach($nav as $item)
            <a href="{{ route($item[3] ?? $item[0]) }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition {{ request()->routeIs($item[0]) ? 'bg-gradient-to-r from-neon/20 to-plasma/10 text-white' : 'text-zinc-400 hover:bg-white/5 hover:text-white' }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $item[2] }}"/></svg>
                {{ $item[1] }}
            </a>
        @endforeach
    </nav>
    <div class="absolute inset-x-3 bottom-4 space-y-1">
        <a href="{{ route('home') }}" target="_blank" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-zinc-400 hover:bg-white/5 hover:text-white">↗ Ver loja</a>
        <form method="POST" action="{{ route('admin.logout') }}">@csrf<button class="w-full rounded-xl px-3 py-2.5 text-left text-sm text-zinc-500 hover:bg-white/5">Sair</button></form>
    </div>
</aside>

<div class="lg:pl-64">
    <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-white/5 bg-ink/80 px-5 backdrop-blur-xl sm:px-8">
        <div class="flex items-center gap-3">
            <button @click="side = !side" class="lg:hidden" aria-label="Menu">☰</button>
            <h1 class="font-display text-lg font-semibold text-white">@yield('title', 'Painel')</h1>
        </div>
        <div class="flex items-center gap-3">
            @yield('actions')
            <a href="{{ route('admin.profile.edit') }}" class="hidden text-sm text-zinc-400 hover:text-white sm:block">{{ auth()->user()->name }}</a>
        </div>
    </header>
    <main class="p-5 sm:p-8">
        @yield('content')
    </main>
</div>

@include('components.toasts')
</body>
</html>
