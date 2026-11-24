@extends('layouts.admin')

@section('title', $category->exists ? 'Editar categoria' : 'Nova categoria')

@section('actions')
    <a href="{{ route('admin.categories.index') }}" class="btn-ghost !px-4 !py-2">← Voltar</a>
@endsection

@section('content')
<form method="POST" enctype="multipart/form-data"
      action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}"
      class="glass max-w-3xl space-y-5 !rounded-2xl p-6 sm:p-8">
    @csrf
    @if($category->exists) @method('PUT') @endif

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label class="label" for="name">Nome *</label>
            <input id="name" name="name" value="{{ old('name', $category->name) }}" required class="input">
            @error('name')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label" for="slug">URL amigável</label>
            <input id="slug" name="slug" value="{{ old('slug', $category->slug) }}" placeholder="gerada automaticamente" class="input">
            @error('slug')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label" for="parent_id">Categoria pai</label>
            <select id="parent_id" name="parent_id" class="input">
                <option value="">— Nenhuma (categoria principal) —</option>
                @foreach($parents as $p)
                    <option value="{{ $p->id }}" @selected(old('parent_id', $category->parent_id) == $p->id)>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="label" for="color">Cor</label>
                <input id="color" name="color" type="color" value="{{ old('color', $category->color) }}" class="h-[46px] w-full cursor-pointer rounded-xl border border-white/10 bg-transparent">
            </div>
            <div>
                <label class="label" for="position">Ordem</label>
                <input id="position" name="position" type="number" min="0" value="{{ old('position', $category->position ?? 0) }}" class="input">
            </div>
        </div>
    </div>

    <div>
        <label class="label" for="description">Descrição</label>
        <textarea id="description" name="description" rows="3" class="input">{{ old('description', $category->description) }}</textarea>
    </div>

    <div>
        <label class="label" for="image">Imagem (opcional)</label>
        @if($category->image_url)
            <img src="{{ $category->image_url }}" alt="" class="mb-3 h-24 rounded-xl object-cover">
        @endif
        <input id="image" name="image" type="file" accept="image/*" class="input file:mr-3 file:rounded-full file:border-0 file:bg-neon file:px-3 file:py-1 file:text-xs file:font-semibold file:text-ink">
        @error('image')<p class="error">{{ $message }}</p>@enderror
    </div>

    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="active" value="1" @checked(old('active', $category->active)) class="accent-cyan-400"> Categoria ativa (visível na loja)
    </label>

    <button class="btn-primary">Salvar categoria</button>
</form>
@endsection
