@extends('layouts.admin')

@section('title', 'Pedidos')

@section('content')
<form class="mb-6 flex flex-wrap gap-3">
    <input name="q" value="{{ request('q') }}" placeholder="Código, nome ou e-mail" class="input max-w-xs">
    <select name="status" class="input max-w-xs" onchange="this.form.submit()">
        <option value="">Todos os status</option>
        @foreach($statuses as $key => $label)
            <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
        @endforeach
    </select>
    <button class="btn-ghost">Filtrar</button>
</form>

<div class="glass overflow-hidden !rounded-2xl">
    @include('admin.orders._table')
</div>
{{ $orders->links() }}
@endsection
