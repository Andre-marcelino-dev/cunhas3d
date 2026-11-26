@extends('layouts.admin')

@section('title', $product->exists ? 'Editar produto' : 'Novo produto')

@section('actions')
    <a href="{{ route('admin.products.index') }}" class="btn-ghost !px-4 !py-2">← Voltar</a>
@endsection

@section('content')
@php
    // Linhas de variação: após erro de validação, volta o que foi digitado; senão, o que está salvo
    $savedVariants = $product->relationLoaded('variants') ? $product->variants->keyBy('id') : collect();
    $variantRows = collect(old('variants', $savedVariants->map(fn ($v) => $v->only('id', 'name', 'price', 'sale_price', 'stock'))->values()->all()))
        ->values()
        ->map(fn ($row, $i) => [
            'uid' => $i + 1,
            'id' => $row['id'] ?? null,
            'name' => $row['name'] ?? '',
            'price' => $row['price'] ?? '',
            'sale_price' => $row['sale_price'] ?? '',
            'stock' => $row['stock'] ?? 0,
            'image_url' => isset($row['id'], $savedVariants[$row['id']]) && $savedVariants[$row['id']]->image
                ? $savedVariants[$row['id']]->image_url : null,
        ]);
@endphp
<form method="POST" enctype="multipart/form-data"
      action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}"
      class="grid gap-6 xl:grid-cols-3"
      x-data="{
          rows: @js($variantRows),
          next: {{ $variantRows->count() + 1 }},
          add() { this.rows.push({ uid: this.next++, id: null, name: '', price: '', sale_price: '', stock: 0, image_url: null }) },
          remove(i) { this.rows.splice(i, 1) },
      }">
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
            <p x-show="rows.length" x-cloak class="rounded-xl border border-neon/30 bg-neon/10 p-3 text-xs text-zinc-300">
                Este produto tem variações: o preço e o estoque são definidos em cada variação, logo abaixo.
                A loja mostra "a partir de" com o preço da variação mais barata.
            </p>
            <div class="grid gap-5 sm:grid-cols-3">
                <div x-show="!rows.length">
                    <label class="label" for="price">Preço (R$) *</label>
                    <input id="price" name="price" type="number" step="0.01" min="0.01" value="{{ old('price', $product->price) }}" :required="!rows.length" :disabled="rows.length > 0" class="input">
                    @error('price')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div x-show="!rows.length">
                    <label class="label" for="sale_price">Preço promocional</label>
                    <input id="sale_price" name="sale_price" type="number" step="0.01" min="0" value="{{ old('sale_price', $product->sale_price) }}" :disabled="rows.length > 0" class="input">
                    @error('sale_price')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div x-show="!rows.length">
                    <label class="label" for="stock">Estoque *</label>
                    <input id="stock" name="stock" type="number" min="0" value="{{ old('stock', $product->stock) }}" :required="!rows.length" :disabled="rows.length > 0" class="input">
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
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold">Variações</h2>
                    <p class="text-xs text-zinc-500">Opcional. Use quando a mesma peça é vendida de formas diferentes, ex.: "Com vela LED" e "Sem vela".</p>
                </div>
                <button type="button" @click="add()" class="btn-ghost !px-4 !py-2 text-sm">+ Adicionar variação</button>
            </div>

            @if($errors->has('variants') || $errors->has('variants.*'))
                <div class="rounded-xl border border-rose-400/40 bg-rose-400/10 p-3 text-sm text-rose-200">
                    @foreach(collect($errors->get('variants'))->merge(collect($errors->get('variants.*'))->flatten())->unique() as $message)
                        <p>{{ $message }}</p>
                    @endforeach
                </div>
            @endif

            <template x-for="(row, i) in rows" :key="row.uid">
                <div class="grid gap-4 rounded-2xl border border-white/10 bg-white/[0.02] p-4 sm:grid-cols-[96px_1fr]">
                    <input type="hidden" :name="`variants[${i}][id]`" :value="row.id">
                    <div>
                        <div class="grid h-24 w-24 place-items-center overflow-hidden rounded-xl border border-white/10 bg-white/5 text-[11px] text-zinc-500">
                            <template x-if="row.image_url"><img :src="row.image_url" alt="" class="h-full w-full object-cover"></template>
                            <template x-if="!row.image_url"><span class="px-2 text-center">usa a imagem principal</span></template>
                        </div>
                        <template x-if="row.image_url && row.id">
                            <label class="mt-2 flex items-center gap-1.5 text-[11px] text-zinc-400"><input type="checkbox" :name="`variants[${i}][remove_image]`" value="1"> remover foto</label>
                        </template>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-4">
                        <div class="sm:col-span-4">
                            <label class="label">Nome da variação *</label>
                            <input :name="`variants[${i}][name]`" x-model="row.name" maxlength="80" required placeholder="Ex.: Com vela LED" class="input">
                        </div>
                        <div>
                            <label class="label">Preço (R$) *</label>
                            <input :name="`variants[${i}][price]`" x-model="row.price" type="number" step="0.01" min="0.01" required class="input">
                        </div>
                        <div>
                            <label class="label">Promocional</label>
                            <input :name="`variants[${i}][sale_price]`" x-model="row.sale_price" type="number" step="0.01" min="0" class="input">
                        </div>
                        <div>
                            <label class="label">Estoque *</label>
                            <input :name="`variants[${i}][stock]`" x-model="row.stock" type="number" min="0" required class="input">
                        </div>
                        <div class="flex items-end">
                            <button type="button" @click="remove(i)" class="w-full rounded-xl border border-rose-400/30 px-3 py-2.5 text-sm text-rose-300 hover:bg-rose-500/10">Remover</button>
                        </div>
                        <div class="sm:col-span-4">
                            <label class="label">Foto desta variação</label>
                            <input :name="`variants[${i}][image]`" type="file" accept="image/*" class="input text-xs file:mr-3 file:rounded-full file:border-0 file:bg-neon file:px-3 file:py-1 file:text-xs file:font-semibold file:text-ink">
                        </div>
                    </div>
                </div>
            </template>

            <p x-show="!rows.length" class="text-sm text-zinc-500">Nenhuma variação: o produto é vendido com o preço e o estoque acima.</p>
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
