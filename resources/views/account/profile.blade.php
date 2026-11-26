@extends('account.layout')

@section('title', 'Meus dados')

@section('account')
<div class="space-y-6">
    <form method="POST" action="{{ route('account.profile.update') }}" class="glass p-6 sm:p-8">
        @csrf @method('PUT')
        <h2 class="text-xl font-semibold">Dados pessoais e endereço</h2>
        <div class="mt-6 grid gap-4 sm:grid-cols-6">
            @foreach([
                ['name', 'Nome completo', 6], ['email', 'E-mail', 3], ['phone', 'Celular', 3], ['cpf', 'CPF', 2],
                ['cep', 'CEP', 2], ['address', 'Rua', 2], ['number', 'Número', 2], ['complement', 'Complemento', 4],
                ['district', 'Bairro', 2], ['city', 'Cidade', 3], ['state', 'UF', 1],
            ] as [$field, $label, $span])
                <div class="sm:col-span-{{ $span }}">
                    <label class="label" for="{{ $field }}">{{ $label }}</label>
                    <input id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $user->$field) }}" class="input"
                           @if($field === 'cep') data-cep @endif @if($field === 'phone') data-phone @endif @if($field === 'state') maxlength="2" @endif>
                    @error($field)<p class="error">{{ $message }}</p>@enderror
                </div>
            @endforeach
        </div>
        <button class="btn-primary mt-6">Salvar alterações</button>
    </form>

    <form method="POST" action="{{ route('account.password.update') }}" class="glass p-6 sm:p-8">
        @csrf @method('PUT')
        <h2 class="text-xl font-semibold">Alterar senha</h2>
        <div class="mt-6 grid gap-4 sm:grid-cols-3">
            <div>
                <label class="label" for="current_password">Senha atual</label>
                <input id="current_password" name="current_password" type="password" class="input">
                @error('current_password')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="password">Nova senha</label>
                <input id="password" name="password" type="password" class="input">
                @error('password')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="password_confirmation">Confirmar</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="input">
            </div>
        </div>
        <button class="btn-ghost mt-6">Alterar senha</button>
    </form>
</div>
@endsection
