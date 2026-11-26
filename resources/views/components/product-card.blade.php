@props(['product'])

<article class="tilt group relative">
    <a href="{{ route('products.show', $product) }}" class="block overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-b from-white/[0.06] to-white/[0.01] transition-colors duration-500 hover:border-white/25">
        <div class="relative aspect-square overflow-hidden" style="background: radial-gradient(circle at 50% 60%, {{ $product->color }}33, transparent 65%)">
            <div class="grid-bg absolute inset-0 opacity-60"></div>
            @if($product->image_url)
                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy" class="relative h-full w-full object-cover transition duration-700 group-hover:scale-110">
            @else
                <img data-thumb data-shape="{{ $product->shape }}" data-color="{{ $product->color }}" alt="{{ $product->name }}"
                     class="relative h-full w-full scale-90 object-contain opacity-0 transition duration-700 group-hover:rotate-6 group-hover:scale-100">
            @endif

            <div class="absolute left-3 top-3 flex gap-1.5">
                <span class="badge border-white/15 bg-ink/60 text-zinc-200 backdrop-blur">{{ $product->material }}</span>
                @if($product->discount_percent)
                    <span class="badge border-flare/40 bg-flare/15 text-flare backdrop-blur">-{{ $product->discount_percent }}%</span>
                @endif
            </div>
            @unless($product->inStock())
                <span class="badge absolute right-3 top-3 border-white/15 bg-ink/80 text-zinc-400">Esgotado</span>
            @endunless
            <div class="tilt-glare pointer-events-none absolute inset-0"></div>
        </div>

        <div class="p-5">
            <p class="text-xs uppercase tracking-wider text-zinc-500">{{ $product->category?->name }}</p>
            <h3 class="mt-1 line-clamp-1 text-base font-semibold text-white">{{ $product->name }}</h3>
            <div class="mt-3 flex items-end justify-between">
                <div>
                    @if($product->relationLoaded('variants') && $product->hasPriceRange())
                        <p class="text-xs text-zinc-500">a partir de</p>
                    @elseif($product->discount_percent)
                        <p class="text-xs text-zinc-500 line-through">{{ money($product->price) }}</p>
                    @endif
                    <p class="font-display text-xl font-bold text-white">{{ money($product->final_price) }}</p>
                </div>
                <span class="grid h-10 w-10 place-items-center rounded-full border border-white/10 transition duration-300 group-hover:border-neon group-hover:bg-neon group-hover:text-ink">
                    <svg class="h-4 w-4 -rotate-45 transition group-hover:rotate-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                </span>
            </div>
        </div>
    </a>
</article>
