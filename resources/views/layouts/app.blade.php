<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Impressão 3D sob medida') · {{ config('app.name') }}</title>
    <meta name="description" content="@yield('description', 'Peças, decoração, miniaturas e protótipos em impressão 3D com acabamento profissional. Enviamos para todo o Brasil.')">
    <meta property="og:title" content="@yield('title', 'Impressão 3D sob medida') · {{ config('app.name') }}">
    <meta property="og:type" content="website">
    <meta name="theme-color" content="#06060b">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><defs><linearGradient id='g'><stop stop-color='%2322d3ee'/><stop offset='1' stop-color='%23a78bfa'/></linearGradient></defs><path fill='url(%23g)' d='M16 2 29 9v14L16 30 3 23V9z'/></svg>">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="noise min-h-screen overflow-x-hidden font-sans">

{{-- Brilhos de fundo --}}
<div aria-hidden="true" class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
    <div class="absolute -left-40 -top-40 h-[32rem] w-[32rem] rounded-full bg-neon/10 blur-[120px]"></div>
    <div class="absolute -right-40 top-1/3 h-[30rem] w-[30rem] rounded-full bg-plasma/10 blur-[120px]"></div>
</div>

{{-- ===================== Cabeçalho ===================== --}}
<header x-data="{ open: false, scrolled: false }" @scroll.window="scrolled = window.scrollY > 20"
        class="fixed inset-x-0 top-0 z-50 transition-all duration-500"
        :class="scrolled ? 'py-2' : 'py-4'">
    <div class="container-x">
        <nav class="flex items-center justify-between gap-4 rounded-full border px-4 py-2.5 transition-all duration-500 sm:px-6"
             :class="scrolled ? 'border-white/10 bg-ink/70 backdrop-blur-xl shadow-2xl shadow-black/40' : 'border-transparent'">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                <svg viewBox="0 0 32 32" class="h-8 w-8"><defs><linearGradient id="lg" x1="0" x2="1"><stop stop-color="#22d3ee"/><stop offset="1" stop-color="#a78bfa"/></linearGradient></defs><path fill="url(#lg)" d="M16 2 29 9v14L16 30 3 23V9z"/><path fill="#06060b" d="M16 9.5 23 13.4v6.7l-7 4-7-4v-6.7z" opacity=".85"/><path fill="url(#lg)" d="M9 16.5h14v1.2H9zm0 2.6h14v1.2H9z" opacity=".9"/></svg>
                <span class="font-display text-lg font-bold tracking-tight text-white">Cunhas <span class="text-gradient">3D</span></span>
            </a>

            <div class="hidden items-center gap-1 lg:flex">
                <a href="{{ route('products.index') }}" class="whitespace-nowrap rounded-full px-4 py-2 text-sm text-zinc-300 transition hover:bg-white/5 hover:text-white">Loja</a>
                @foreach($menuCategories->take(3) as $cat)
                    <a href="{{ route('categories.show', $cat) }}" class="whitespace-nowrap rounded-full px-4 py-2 text-sm text-zinc-300 transition hover:bg-white/5 hover:text-white">{{ $cat->name }}</a>
                @endforeach
            </div>

            <div class="flex items-center gap-1.5">
                <form action="{{ route('products.index') }}" class="relative hidden md:block">
                    <input name="q" value="{{ request('q') }}" placeholder="Buscar peças..." class="w-44 rounded-full border border-white/10 bg-white/5 py-2 pl-9 pr-3 text-sm text-white placeholder-zinc-500 outline-none transition focus:w-60 focus:border-neon/60">
                    <svg class="absolute left-3 top-2.5 h-4 w-4 text-zinc-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                </form>

                <a href="{{ route('cart.index') }}" class="relative grid h-10 w-10 place-items-center rounded-full transition hover:bg-white/10" aria-label="Carrinho">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M3 4h2l2.4 11.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.5L21 8H6"/><circle cx="10" cy="20.5" r="1"/><circle cx="17" cy="20.5" r="1"/></svg>
                    <span data-cart-count class="{{ $cartCount ? '' : 'hidden' }} absolute -right-0.5 -top-0.5 grid h-5 min-w-5 place-items-center rounded-full bg-gradient-to-r from-neon to-plasma px-1 text-[10px] font-bold text-ink">{{ $cartCount }}</span>
                </a>

                @auth
                    <div x-data="{ m: false }" class="relative">
                        <button @click="m = !m" @click.outside="m = false" class="flex h-10 items-center gap-2 rounded-full pl-1 pr-3 transition hover:bg-white/10">
                            <span class="grid h-8 w-8 place-items-center rounded-full bg-gradient-to-br from-neon to-plasma text-xs font-bold text-ink">{{ str(auth()->user()->name)->substr(0, 1)->upper() }}</span>
                            <span class="hidden text-sm sm:block">{{ str(auth()->user()->name)->before(' ') }}</span>
                        </button>
                        <div x-cloak x-show="m" x-transition.origin.top.right class="glass absolute right-0 mt-2 w-52 overflow-hidden !rounded-2xl !bg-panel/95 p-1.5 text-sm shadow-2xl">
                            <a href="{{ route('account.orders') }}" class="block rounded-xl px-3 py-2 hover:bg-white/5">Meus pedidos</a>
                            <a href="{{ route('account.profile') }}" class="block rounded-xl px-3 py-2 hover:bg-white/5">Meus dados</a>
                            <form method="POST" action="{{ route('logout') }}">@csrf
                                <button class="w-full rounded-xl px-3 py-2 text-left text-zinc-400 hover:bg-white/5">Sair</button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="btn-ghost !px-4 !py-2">Entrar</a>
                @endauth

                <button @click="open = !open" class="grid h-10 w-10 place-items-center rounded-full hover:bg-white/10 lg:hidden" aria-label="Menu">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path :d="open ? 'M6 6l12 12M6 18 18 6' : 'M4 7h16M4 12h16M4 17h16'"/></svg>
                </button>
            </div>
        </nav>

        {{-- Menu mobile --}}
        <div x-cloak x-show="open" x-transition class="glass mt-2 !rounded-3xl !bg-panel/95 p-4 lg:hidden">
            <form action="{{ route('products.index') }}" class="mb-3"><input name="q" placeholder="Buscar peças..." class="input"></form>
            <a href="{{ route('products.index') }}" class="block rounded-xl px-3 py-2.5 hover:bg-white/5">Todos os produtos</a>
            @foreach($menuCategories as $cat)
                <a href="{{ route('categories.show', $cat) }}" class="block rounded-xl px-3 py-2.5 hover:bg-white/5">{{ $cat->name }}</a>
            @endforeach
        </div>
    </div>
</header>

<main>
    @yield('content')
</main>

{{-- ===================== Rodapé ===================== --}}
<footer class="mt-32 border-t border-white/5">
    <div class="container-x grid gap-10 py-16 md:grid-cols-4">
        <div class="md:col-span-2">
            <p class="font-display text-2xl font-bold text-white">Cunhas <span class="text-gradient">3D</span></p>
            <p class="mt-3 max-w-sm text-sm leading-relaxed text-zinc-400">Transformamos ideias em objetos reais, camada por camada. Impressão FDM e resina com acabamento profissional e envio para todo o Brasil.</p>
            <div class="mt-6 flex gap-2 text-xs text-zinc-500">
                <span class="badge border-white/10">Pix</span><span class="badge border-white/10">Cartão em até 12x</span><span class="badge border-white/10">Boleto</span><span class="badge border-white/10">Mercado Pago</span>
            </div>
        </div>
        <div>
            <p class="label">Loja</p>
            <ul class="space-y-2 text-sm text-zinc-400">
                @foreach($menuCategories as $cat)
                    <li><a href="{{ route('categories.show', $cat) }}" class="hover:text-white">{{ $cat->name }}</a></li>
                @endforeach
            </ul>
        </div>
        <div>
            <p class="label">Conta</p>
            <ul class="space-y-2 text-sm text-zinc-400">
                <li><a href="{{ route('account.orders') }}" class="hover:text-white">Meus pedidos</a></li>
                <li><a href="{{ route('cart.index') }}" class="hover:text-white">Carrinho</a></li>
                <li><a href="{{ route('register') }}" class="hover:text-white">Criar conta</a></li>
            </ul>
        </div>
    </div>
    <div class="border-t border-white/5 py-6 text-center text-xs text-zinc-600">© {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.</div>
</footer>

@include('components.toasts')
@stack('scripts')
</body>
</html>
