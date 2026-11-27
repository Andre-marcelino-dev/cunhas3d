@extends('layouts.app')

@section('title', 'Fale conosco')
@section('description', 'Tire suas dúvidas sobre nossas peças impressas em 3D.')

@section('content')
<section class="container-x pt-32">
    <div data-reveal class="mx-auto max-w-2xl">
        <h1 class="text-4xl font-bold sm:text-5xl">Fale conosco</h1>
        <p class="mt-4 text-lg text-zinc-400">Dúvidas sobre uma peça, pedido personalizado ou orçamento? Mande sua mensagem e respondemos no seu e-mail.</p>

        @if($product)
            <a href="{{ route('products.show', $product) }}" class="glass mt-8 flex items-center gap-4 !rounded-2xl p-4 hover:border-neon/40">
                @if($product->image_url)<img src="{{ $product->image_url }}" alt="" class="h-14 w-14 rounded-xl object-cover">@endif
                <span><span class="block text-xs text-zinc-500">Sobre o produto</span><span class="font-semibold text-white">{{ $product->name }}</span></span>
            </a>
        @endif

        <div class="glass mt-8 p-6 sm:p-8">
            <x-contact-form :product="$product" />
        </div>
    </div>
</section>
@endsection
