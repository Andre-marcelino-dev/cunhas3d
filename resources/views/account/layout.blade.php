@extends('layouts.app')

@section('content')
<section class="container-x pt-36">
    <div data-reveal>
        <p class="label !text-neon">Minha conta</p>
        <h1 class="text-4xl font-bold sm:text-5xl">Olá, {{ str(auth()->user()->name)->before(' ') }} 👋</h1>
    </div>
    <div class="mt-10 grid gap-8 lg:grid-cols-[220px_1fr]">
        <nav class="flex gap-2 lg:flex-col">
            @foreach([['account.orders', 'Meus pedidos'], ['account.profile', 'Meus dados']] as [$r, $label])
                <a href="{{ route($r) }}" class="chip {{ request()->routeIs($r) || ($r === 'account.orders' && request()->routeIs('account.order')) ? 'chip-active' : '' }}">{{ $label }}</a>
            @endforeach
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="chip w-full text-left text-zinc-500">Sair</button></form>
        </nav>
        <div data-reveal>@yield('account')</div>
    </div>
</section>
@endsection
