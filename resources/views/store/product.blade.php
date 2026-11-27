@extends('layouts.app')

@section('title', $product->name)
@section('description', $product->short_description)

@push('head')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $product->name,
    'sku' => $product->sku,
    'description' => $product->short_description,
    'image' => $product->image_url,
    'offers' => [
        '@type' => 'Offer',
        'priceCurrency' => 'BRL',
        'price' => number_format($product->final_price, 2, '.', ''),
        'availability' => $product->inStock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}
</script>
@endpush

@section('content')
<section class="container-x pt-32">
    <nav class="text-xs text-zinc-500">
        <a href="{{ route('home') }}" class="hover:text-white">Início</a> /
        <a href="{{ route('categories.show', $product->category) }}" class="hover:text-white">{{ $product->category->name }}</a> /
        <span class="text-zinc-300">{{ $product->name }}</span>
    </nav>

    {{-- Visualizador 3D / fotos.
         Sem arquivo .glb e com foto cadastrada: mostra só as fotos (a forma genérica não representa a peça real).
         Variações com foto própria entram na galeria; ao escolher a variação, a foto dela aparece. --}}
    @php
        $photos = collect([$product->image_url])->filter()
            ->merge($product->images->pluck('url'))
            ->merge($product->variants->map(fn ($v) => $v->image ? $v->image_url : null)->filter())
            ->unique()->values();
        $show3d = $product->model_url || $photos->isEmpty();

        $variants = $product->variants->map(fn ($v) => [
            'id' => $v->id,
            'name' => $v->name,
            'price' => (float) $v->price,
            'final' => $v->final_price,
            'discount' => $v->discount_percent,
            'stock' => (int) $v->stock,
            'photo' => ($i = $photos->search($v->image_url)) !== false ? $i : null,
        ])->values();
        $firstVariant = $variants->firstWhere('stock', '>', 0) ?? $variants->first();
    @endphp
    <div class="mt-8 grid gap-10 lg:grid-cols-2 lg:gap-16"
         x-data="{
            tab: '{{ $show3d ? '3d' : 'img0' }}',
            variants: @js($variants),
            sel: @js($firstVariant['id'] ?? null),
            q: 1,
            get v() { return this.variants.find(x => x.id === this.sel) },
            brl(n) { return n.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) },
            choose(id) {
                this.sel = id;
                this.q = Math.min(this.q, Math.max(1, this.v.stock));
                if (this.v.photo !== null) this.tab = 'img' + this.v.photo;
            },
         }"
         x-init="if (v && v.photo !== null && !{{ $show3d ? 'true' : 'false' }}) tab = 'img' + v.photo">
        <div class="lg:sticky lg:top-28 lg:self-start">
            <div class="relative aspect-square overflow-hidden rounded-[2rem] border border-white/10"
                 style="background: radial-gradient(circle at 50% 55%, {{ $product->color }}30, transparent 65%), #0a0a12">
                <div class="grid-bg absolute inset-0"></div>

                @if($show3d)
                <div x-show="tab === '3d'" class="absolute inset-0" data-viewer data-shape="{{ $product->shape }}" data-color="{{ $product->color }}" @if($product->model_url) data-model="{{ $product->model_url }}" data-format="{{ $product->model_format }}" @endif>
                    <div data-viewer-loading class="absolute inset-0 grid place-items-center text-sm text-zinc-500">Carregando modelo 3D…</div>
                </div>
                @endif

                @foreach($photos as $i => $url)
                    <img x-cloak x-show="tab === 'img{{ $i }}'" src="{{ $url }}" alt="{{ $product->name }}" class="absolute inset-0 h-full w-full object-cover">
                @endforeach

                <span x-show="tab === '3d'" class="badge absolute left-4 top-4 border-white/15 bg-ink/70 text-zinc-300 backdrop-blur">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 3 3 8l9 5 9-5-9-5Zm-9 5v8l9 5m0-8v8m9-13v8l-9 5"/></svg>
                    Arraste para girar · role para zoom
                </span>
            </div>

            <div class="mt-4 flex gap-3">
                @if($show3d && $photos->isNotEmpty())
                    <button @click="tab = '3d'" :class="tab === '3d' && 'border-neon'" class="grid h-20 w-20 place-items-center rounded-2xl border border-white/10 bg-white/5 font-display text-sm font-bold">3D</button>
                @endif
                @foreach($photos->count() > 1 || $show3d ? $photos : [] as $i => $url)
                    <button @click="tab = 'img{{ $i }}'" :class="tab === 'img{{ $i }}' && 'border-neon'" class="h-20 w-20 overflow-hidden rounded-2xl border border-white/10">
                        <img src="{{ $url }}" alt="" class="h-full w-full object-cover">
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Informações --}}
        <div data-reveal>
            <div class="flex flex-wrap gap-2">
                <span class="badge border-white/15 text-zinc-300">{{ $product->material }}</span>
                <span class="badge border-white/15 text-zinc-300">Produção em {{ $product->production_days }} {{ $product->production_days == 1 ? 'dia útil' : 'dias úteis' }}</span>
                @if($product->hasVariants())
                    <span x-show="v && v.stock > 0" class="badge border-emerald-400/40 bg-emerald-400/10 text-emerald-300">Em estoque</span>
                    <span x-cloak x-show="!v || v.stock <= 0" class="badge border-rose-400/40 bg-rose-400/10 text-rose-300">Esgotado</span>
                @elseif($product->inStock())
                    <span class="badge border-emerald-400/40 bg-emerald-400/10 text-emerald-300">Em estoque</span>
                @else
                    <span class="badge border-rose-400/40 bg-rose-400/10 text-rose-300">Esgotado</span>
                @endif
            </div>

            <h1 class="mt-5 text-4xl font-bold sm:text-5xl">{{ $product->name }}</h1>
            <p class="mt-4 text-lg text-zinc-400">{{ $product->short_description }}</p>

            @if($product->hasVariants())
                {{-- Preço muda conforme a variação escolhida --}}
                <div class="mt-8 flex items-end gap-4">
                    <p class="font-display text-5xl font-bold text-white" x-text="brl(v.final)">{{ money($firstVariant['final']) }}</p>
                    <div class="pb-1.5" x-show="v.discount" x-cloak>
                        <p class="text-sm text-zinc-500 line-through" x-text="brl(v.price)"></p>
                        <p class="text-sm font-semibold text-flare"><span x-text="v.discount"></span>% OFF</p>
                    </div>
                </div>
                <p class="mt-2 text-sm text-zinc-400">ou até 12x de <span x-text="brl(v.final / 12)">{{ money($firstVariant['final'] / 12) }}</span> no cartão · <span class="text-neon">Pix</span> com aprovação imediata</p>

                <div class="mt-8">
                    <p class="label">Escolha a opção</p>
                    <div class="grid gap-3 sm:grid-cols-2" role="radiogroup">
                        @foreach($variants as $opt)
                            <button type="button" role="radio" @click="choose({{ $opt['id'] }})"
                                    :aria-checked="sel === {{ $opt['id'] }}"
                                    :class="sel === {{ $opt['id'] }} ? 'border-neon bg-neon/10' : 'border-white/15 bg-white/5 hover:border-white/30'"
                                    class="flex items-center justify-between gap-3 rounded-2xl border px-4 py-3 text-left transition {{ $opt['stock'] <= 0 ? 'opacity-50' : '' }}">
                                <span class="flex items-center gap-3">
                                    <span class="grid h-5 w-5 shrink-0 place-items-center rounded-full border border-white/30">
                                        <span x-show="sel === {{ $opt['id'] }}" class="h-2.5 w-2.5 rounded-full bg-neon"></span>
                                    </span>
                                    <span>
                                        <span class="block font-semibold text-white">{{ $opt['name'] }}</span>
                                        @if($opt['stock'] <= 0)<span class="block text-xs text-rose-300">Esgotado</span>@endif
                                    </span>
                                </span>
                                <span class="shrink-0 font-display font-bold text-white">{{ money($opt['final']) }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="mt-8 flex items-end gap-4">
                    <p class="font-display text-5xl font-bold text-white">{{ money($product->final_price) }}</p>
                    @if($product->discount_percent)
                        <div class="pb-1.5">
                            <p class="text-sm text-zinc-500 line-through">{{ money($product->price) }}</p>
                            <p class="text-sm font-semibold text-flare">{{ $product->discount_percent }}% OFF</p>
                        </div>
                    @endif
                </div>
                <p class="mt-2 text-sm text-zinc-400">ou até 12x de {{ money($product->final_price / 12) }} no cartão · <span class="text-neon">Pix</span> com aprovação imediata</p>
            @endif

            {{-- Pré-visualizar cores (só na forma 3D gerada automaticamente) --}}
            @if($show3d && $product->model_recolorable)
            <div class="mt-8">
                <p class="label">Pré-visualize cores no modelo 3D</p>
                <div class="flex flex-wrap gap-2.5">
                    @foreach(array_unique([$product->color, '#22d3ee', '#a78bfa', '#f472b6', '#fbbf24', '#4ade80', '#f8fafc', '#18181b']) as $c)
                        <button type="button" data-viewer-color="{{ $c }}" class="h-9 w-9 rounded-full border border-white/20 ring-neon ring-offset-2 ring-offset-ink transition hover:scale-110 {{ $loop->first ? 'ring-2' : '' }}" style="background: {{ $c }}" aria-label="Cor {{ $c }}"></button>
                    @endforeach
                </div>
                <p class="mt-2 text-xs text-zinc-500">Quer outra cor? Escreva nas observações do pedido.</p>
            </div>
            @endif

            @if($product->hasVariants())
                {{-- Estoque e limite de quantidade acompanham a variação escolhida --}}
                <form x-show="v.stock > 0" method="POST" action="{{ route('cart.add', $product) }}" data-add-to-cart class="mt-8 flex flex-wrap gap-3">
                    @csrf
                    <input type="hidden" name="variant_id" :value="sel">
                    <div class="flex items-center rounded-full border border-white/15 bg-white/5">
                        <button type="button" @click="q = Math.max(1, q - 1)" class="h-12 w-12 text-xl text-zinc-400 hover:text-white">−</button>
                        <input name="quantity" x-model.number="q" type="number" min="1" :max="v.stock" class="w-10 bg-transparent text-center text-white outline-none [appearance:textfield]">
                        <button type="button" @click="q = Math.min(v.stock, q + 1)" class="h-12 w-12 text-xl text-zinc-400 hover:text-white">+</button>
                    </div>
                    <button type="submit" class="btn-ghost min-w-[9rem] flex-1 whitespace-nowrap !py-3.5"><span class="sm:hidden">Adicionar</span><span class="hidden sm:inline">Adicionar ao carrinho</span></button>
                    <button type="submit" name="buy_now" value="1" data-magnetic class="btn-primary w-full whitespace-nowrap !py-3.5 sm:w-auto sm:flex-1">Comprar agora</button>
                </form>
                <div x-cloak x-show="v.stock <= 0" class="glass mt-8 !rounded-2xl p-5 text-sm text-zinc-400">Esta opção está esgotada no momento. Escolha outra ou volte em breve!</div>
            @elseif($product->inStock())
                <form method="POST" action="{{ route('cart.add', $product) }}" data-add-to-cart class="mt-8 flex flex-wrap gap-3">
                    @csrf
                    <div class="flex items-center rounded-full border border-white/15 bg-white/5">
                        <button type="button" @click="q = Math.max(1, q - 1)" class="h-12 w-12 text-xl text-zinc-400 hover:text-white">−</button>
                        <input name="quantity" x-model.number="q" type="number" min="1" max="{{ $product->stock }}" class="w-10 bg-transparent text-center text-white outline-none [appearance:textfield]">
                        <button type="button" @click="q = Math.min({{ $product->stock }}, q + 1)" class="h-12 w-12 text-xl text-zinc-400 hover:text-white">+</button>
                    </div>
                    <button type="submit" class="btn-ghost min-w-[9rem] flex-1 whitespace-nowrap !py-3.5"><span class="sm:hidden">Adicionar</span><span class="hidden sm:inline">Adicionar ao carrinho</span></button>
                    <button type="submit" name="buy_now" value="1" data-magnetic class="btn-primary w-full whitespace-nowrap !py-3.5 sm:w-auto sm:flex-1">Comprar agora</button>
                </form>
            @else
                <div class="glass mt-8 !rounded-2xl p-5 text-sm text-zinc-400">Este produto está esgotado no momento. Volte em breve!</div>
            @endif

            <div class="mt-10 grid grid-cols-3 gap-3 text-center text-xs text-zinc-400">
                <div class="glass !rounded-2xl p-4"><p class="text-lg">🔒</p>Pagamento seguro</div>
                <div class="glass !rounded-2xl p-4"><p class="text-lg">📦</p>Envio para todo o Brasil</div>
                <div class="glass !rounded-2xl p-4"><p class="text-lg">✨</p>Acabamento revisado</div>
            </div>

            <div class="mt-10 border-t border-white/10 pt-8">
                <h2 class="text-xl font-semibold">Detalhes</h2>
                <div class="mt-4 whitespace-pre-line leading-relaxed text-zinc-400">{{ $product->description }}</div>
                <dl class="mt-6 grid grid-cols-2 gap-3 text-sm">
                    <div class="rounded-xl bg-white/[0.03] p-3"><dt class="text-zinc-500">SKU</dt><dd class="text-white">{{ $product->sku }}</dd></div>
                    <div class="rounded-xl bg-white/[0.03] p-3"><dt class="text-zinc-500">Peso aprox.</dt><dd class="text-white">{{ $product->weight_grams }} g</dd></div>
                </dl>
            </div>

            {{-- Dúvidas sobre o produto: abre sozinho (e rola até aqui) se o envio voltou com erro --}}
            @php $contactErrors = $errors->hasAny(['name', 'email', 'phone', 'subject', 'message']); @endphp
            <div id="duvidas" class="mt-10 border-t border-white/10 pt-8"
                 x-data="{ open: @js($contactErrors) }"
                 x-init="if (open) $nextTick(() => $el.scrollIntoView({ behavior: 'smooth', block: 'start' }))">
                <button type="button" @click="open = !open" :aria-expanded="open" class="flex w-full items-center justify-between gap-4 text-left">
                    <span>
                        <span class="block text-xl font-semibold">Ficou com alguma dúvida?</span>
                        <span class="mt-1 block text-sm text-zinc-400">Pergunte sobre cores, tamanho, prazo ou personalização desta peça.</span>
                    </span>
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full border border-white/15 text-xl text-zinc-300 transition" :class="open && 'rotate-45'">+</span>
                </button>
                <div x-cloak x-show="open" x-transition class="mt-6">
                    <x-contact-form :product="$product" />
                </div>
            </div>
        </div>
    </div>
</section>

@if($related->isNotEmpty())
<section class="container-x mt-32">
    <h2 data-reveal class="text-3xl font-bold">Você também pode gostar</h2>
    <div data-reveal data-stagger class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($related as $p)
            <x-product-card :product="$p" />
        @endforeach
    </div>
</section>
@endif
@endsection
