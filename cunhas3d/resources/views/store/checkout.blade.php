@extends('layouts.app')

@section('title', 'Finalizar compra')

@section('content')
<section class="container-x pt-36">
    <div data-reveal>
        <ol class="flex flex-wrap items-center gap-3 text-sm">
            <li class="text-zinc-500">1. Carrinho</li><li class="text-zinc-600">→</li>
            <li class="font-semibold text-neon">2. Entrega</li><li class="text-zinc-600">→</li>
            <li class="text-zinc-500">3. Pagamento</li>
        </ol>
        <h1 class="mt-4 text-5xl font-bold">Finalizar compra</h1>
    </div>

    <form method="POST" action="{{ route('checkout.store') }}" class="mt-10 grid gap-8 lg:grid-cols-[1fr_380px]" x-data="{ sending: false }" @submit="sending = true">
        @csrf
        <div data-reveal class="glass space-y-8 p-6 sm:p-8">
            <fieldset>
                <legend class="text-lg font-semibold text-white">Seus dados</legend>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="label" for="name">Nome completo</label>
                        <input id="name" name="name" value="{{ old('name', $user->name) }}" required class="input">
                        @error('name')<p class="error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label" for="email">E-mail</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required class="input">
                        @error('email')<p class="error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label" for="phone">Celular / WhatsApp</label>
                        <input id="phone" name="phone" data-phone value="{{ old('phone', $user->phone) }}" required placeholder="(11) 99999-9999" class="input">
                        @error('phone')<p class="error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label" for="cpf">CPF (opcional)</label>
                        <input id="cpf" name="cpf" value="{{ old('cpf', $user->cpf) }}" placeholder="000.000.000-00" class="input">
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend class="text-lg font-semibold text-white">Endereço de entrega</legend>
                <div class="mt-5 grid gap-4 sm:grid-cols-6">
                    <div class="sm:col-span-2">
                        <label class="label" for="cep">CEP</label>
                        <input id="cep" name="cep" data-cep value="{{ old('cep', $user->cep) }}" required placeholder="00000-000" class="input">
                        @error('cep')<p class="error">Informe um CEP válido.</p>@enderror
                    </div>
                    <div class="sm:col-span-4">
                        <label class="label" for="address">Rua</label>
                        <input id="address" name="address" value="{{ old('address', $user->address) }}" required class="input">
                        @error('address')<p class="error">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label" for="number">Número</label>
                        <input id="number" name="number" value="{{ old('number', $user->number) }}" required class="input">
                        @error('number')<p class="error">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-4">
                        <label class="label" for="complement">Complemento</label>
                        <input id="complement" name="complement" value="{{ old('complement', $user->complement) }}" placeholder="Apto, bloco..." class="input">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label" for="district">Bairro</label>
                        <input id="district" name="district" value="{{ old('district', $user->district) }}" required class="input">
                        @error('district')<p class="error">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-3">
                        <label class="label" for="city">Cidade</label>
                        <input id="city" name="city" value="{{ old('city', $user->city) }}" required class="input">
                        @error('city')<p class="error">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-1">
                        <label class="label" for="state">UF</label>
                        <input id="state" name="state" maxlength="2" value="{{ old('state', $user->state) }}" required class="input uppercase">
                        @error('state')<p class="error">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-6">
                        <label class="label" for="notes">Observações (cor desejada, personalização...)</label>
                        <textarea id="notes" name="notes" rows="3" class="input">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </fieldset>
        </div>

        <aside data-reveal class="glass h-fit p-6 lg:sticky lg:top-28">
            <h2 class="text-xl font-semibold">Seu pedido</h2>
            <ul class="mt-5 space-y-3 text-sm">
                @foreach($items as $item)
                    <li class="flex justify-between gap-3"><span class="text-zinc-300">{{ $item->quantity }}× {{ $item->product->name }}</span><span class="shrink-0 text-white">{{ money($item->total) }}</span></li>
                @endforeach
            </ul>
            <div class="my-5 h-px bg-white/10"></div>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-zinc-400">Subtotal</dt><dd>{{ money($subtotal) }}</dd></div>
                <div class="flex justify-between"><dt class="text-zinc-400">Frete</dt><dd class="{{ $shipping == 0 ? 'text-emerald-300' : '' }}">{{ $shipping == 0 ? 'Grátis' : money($shipping) }}</dd></div>
            </dl>
            <div class="mt-5 flex items-end justify-between">
                <span class="text-zinc-400">Total</span>
                <span class="font-display text-3xl font-bold text-white">{{ money($total) }}</span>
            </div>
            <button type="submit" :disabled="sending" class="btn-primary mt-6 w-full !py-4">
                <span x-show="!sending">Ir para o pagamento</span>
                <span x-cloak x-show="sending">Gerando pagamento…</span>
            </button>
            <div class="mt-5 rounded-xl bg-white/[0.03] p-4 text-xs leading-relaxed text-zinc-400">
                Você será levado ao ambiente seguro do <strong class="text-white">Mercado Pago</strong> para pagar com Pix, cartão de crédito em até 12x, cartão de débito ou boleto.
            </div>
        </aside>
    </form>
</section>
@endsection
