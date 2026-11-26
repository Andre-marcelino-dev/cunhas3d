@extends('layouts.admin')

@section('title', $product->exists ? 'Editar produto' : 'Novo produto')

@section('actions')
    <a href="{{ route('admin.products.index') }}" class="btn-ghost !px-4 !py-2">← Voltar</a>
@endsection

@section('content')
<form method="POST" enctype="multipart/form-data"
      action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}"
      class="grid gap-6 xl:grid-cols-3">
    @csrf
    @if($product->exists) @method('PUT') @endif

    <div class="space-y-6 xl:col-span-2">
        <section class="glass space-y-5 !rounded-2xl p-6">
            <h2 class="text-lg font-semibold">Informações</h2>
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="label" for="name">Nome *</label>
                    <input id="name" name="name" value="{{ old('name', $product->name) }}" required class="input">
                    @error('name')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="category_id">Categoria *</label>
                    <select id="category_id" name="category_id" required class="input">
                        <option value="">Selecione…</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}" @selected(old('category_id', $product->category_id) == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="sku">SKU</label>
                    <input id="sku" name="sku" value="{{ old('sku', $product->sku) }}" placeholder="gerado automaticamente" class="input font-mono">
                    @error('sku')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="label" for="short_description">Resumo (aparece no topo da página)</label>
                    <input id="short_description" name="short_description" maxlength="255" value="{{ old('short_description', $product->short_description) }}" class="input">
                </div>
                <div class="sm:col-span-2">
                    <label class="label" for="description">Descrição completa</label>
                    <textarea id="description" name="description" rows="7" class="input">{{ old('description', $product->description) }}</textarea>
                </div>
            </div>
        </section>

        <section class="glass space-y-5 !rounded-2xl p-6">
            <h2 class="text-lg font-semibold">Preço e estoque</h2>
            <div class="grid gap-5 sm:grid-cols-3">
                <div>
                    <label class="label" for="price">Preço (R$) *</label>
                    <input id="price" name="price" type="number" step="0.01" min="0.01" value="{{ old('price', $product->price) }}" required class="input">
                    @error('price')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="sale_price">Preço promocional</label>
                    <input id="sale_price" name="sale_price" type="number" step="0.01" min="0" value="{{ old('sale_price', $product->sale_price) }}" class="input">
                    @error('sale_price')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="stock">Estoque *</label>
                    <input id="stock" name="stock" type="number" min="0" value="{{ old('stock', $product->stock) }}" required class="input">
                </div>
                <div>
                    <label class="label" for="weight_grams">Peso (g) *</label>
                    <input id="weight_grams" name="weight_grams" type="number" min="1" value="{{ old('weight_grams', $product->weight_grams) }}" required class="input">
                </div>
                <div>
                    <label class="label" for="production_days">Dias de produção *</label>
                    <input id="production_days" name="production_days" type="number" min="0" value="{{ old('production_days', $product->production_days) }}" required class="input">
                </div>
                <div>
                    <label class="label" for="material">Material *</label>
                    <select id="material" name="material" class="input">
                        @foreach($materials as $m)
                            <option @selected(old('material', $product->material) === $m)>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </section>

        <section class="glass space-y-5 !rounded-2xl p-6">
            <h2 class="text-lg font-semibold">Imagens</h2>
            <div>
                <label class="label" for="image">Imagem principal (usada nos cards)</label>
                @if($product->image_url)
                    <img src="{{ $product->image_url }}" alt="" class="mb-3 h-28 rounded-xl object-cover">
                @endif
                <input id="image" name="image" type="file" accept="image/*" class="input file:mr-3 file:rounded-full file:border-0 file:bg-neon file:px-3 file:py-1 file:text-xs file:font-semibold file:text-ink">
                <p class="mt-1 text-xs text-zinc-500">Sem imagem, o card mostra uma miniatura 3D gerada automaticamente.</p>
                @error('image')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="gallery">Galeria (várias imagens)</label>
                <input id="gallery" name="gallery[]" type="file" accept="image/*" multiple class="input file:mr-3 file:rounded-full file:border-0 file:bg-plasma file:px-3 file:py-1 file:text-xs file:font-semibold file:text-ink">
                @error('gallery.*')<p class="error">{{ $message }}</p>@enderror
            </div>
        </section>
    </div>

    {{-- Coluna lateral: 3D + publicação --}}
    <div class="space-y-6">
        <section class="glass space-y-4 !rounded-2xl p-6" x-data="{ color: @js(old('color', $product->color)) }">
            <h2 class="text-lg font-semibold">Visualização 3D</h2>
            @if($product->exists)
                <div class="relative aspect-square overflow-hidden rounded-xl bg-[#0a0a12]" data-viewer data-shape="{{ $product->shape }}" data-color="{{ $product->color }}" @if($product->model_url) data-model="{{ $product->model_url }}" data-format="{{ $product->model_format }}" @endif>
                    <div data-viewer-loading class="absolute inset-0 grid place-items-center text-xs text-zinc-500">Carregando…</div>
                </div>
            @endif
            <div>
                <label class="label" for="model">Modelo 3D (.stl, .3mf, .obj ou .glb) — opcional</label>
                <input id="model" name="model" type="file" accept=".stl,.3mf,.obj,.glb" class="input text-xs file:mr-3 file:rounded-full file:border-0 file:bg-flare file:px-3 file:py-1 file:text-xs file:font-semibold file:text-ink">
                @error('model')<p class="error">{{ $message }}</p>@enderror
                @if($product->model_path)
                    <label class="mt-2 flex items-center gap-2 text-xs text-zinc-400"><input type="checkbox" name="remove_model" value="1"> Remover modelo atual</label>
                @endif
                <p class="mt-1 text-xs text-zinc-500">Envie o mesmo arquivo que você usa para imprimir (STL ou 3MF do fatiador). STL e OBJ aparecem na cor principal escolhida abaixo. Máx. 50 MB.</p>
            </div>
            <div>
                <label class="label" for="shape">Forma 3D (se não houver modelo)</label>
                <select id="shape" name="shape" class="input">
                    @foreach($shapes as $key => $label)
                        <option value="{{ $key }}" @selected(old('shape', $product->shape) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label" for="color">Cor principal</label>
                <div class="flex gap-3">
                    <input id="color" name="color" type="color" x-model="color" class="h-[46px] w-16 cursor-pointer rounded-xl border border-white/10 bg-transparent">
                    <input x-model="color" class="input font-mono" readonly>
                </div>
            </div>
        </section>

        <section class="glass space-y-4 !rounded-2xl p-6">
            <h2 class="text-lg font-semibold">Publicação</h2>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="active" value="1" @checked(old('active', $product->active)) class="accent-cyan-400"> Produto ativo na loja</label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="featured" value="1" @checked(old('featured', $product->featured)) class="accent-cyan-400"> Mostrar em destaque na home</label>
            <button class="btn-primary w-full">Salvar produto</button>
        </section>
    </div>
</form>

@if($product->exists && $product->images->isNotEmpty())
    <section class="glass mt-6 !rounded-2xl p-6">
        <h2 class="text-lg font-semibold">Galeria atual</h2>
        <div class="mt-4 flex flex-wrap gap-3">
            @foreach($product->images as $img)
                <div class="group relative">
                    <img src="{{ $img->url }}" alt="" class="h-28 w-28 rounded-xl object-cover">
                    <form method="POST" action="{{ route('admin.products.image.destroy', $img) }}" class="absolute right-1 top-1">
                        @csrf @method('DELETE')
                        <button class="grid h-7 w-7 place-items-center rounded-full bg-rose-500 text-xs text-white opacity-0 transition group-hover:opacity-100" title="Remover">✕</button>
                    </form>
                </div>
            @endforeach
        </div>
    </section>
@endif
@endsection
