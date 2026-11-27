@props(['product' => null])
{{-- Formulário "Fale conosco". Com $product, a mensagem fica vinculada à peça. --}}
@php $user = auth()->user(); @endphp
<form method="POST" action="{{ route('contact.store') }}" class="space-y-5">
    @csrf
    @if($product)
        <input type="hidden" name="product_id" value="{{ $product->id }}">
    @endif
    {{-- Armadilha anti-spam: invisível para pessoas --}}
    <div class="hidden" aria-hidden="true"><input name="website" tabindex="-1" autocomplete="off"></div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label class="label" for="contact_name">Nome</label>
            <input id="contact_name" name="name" value="{{ old('name', $user?->name) }}" required maxlength="120" class="input">
            @error('name')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label" for="contact_email">E-mail</label>
            <input id="contact_email" name="email" type="email" value="{{ old('email', $user?->email) }}" required class="input">
            @error('email')<p class="error">{{ $message }}</p>@enderror
        </div>
    </div>
    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label class="label" for="contact_phone">Celular / WhatsApp (opcional)</label>
            <input id="contact_phone" name="phone" data-phone value="{{ old('phone', $user?->phone) }}" class="input">
            @error('phone')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label" for="contact_subject">Assunto</label>
            <input id="contact_subject" name="subject" maxlength="120" class="input"
                   value="{{ old('subject', $product ? 'Dúvida sobre: '.$product->name : '') }}"
                   placeholder="Ex.: prazo, cores, personalização">
            @error('subject')<p class="error">{{ $message }}</p>@enderror
        </div>
    </div>
    <div>
        <label class="label" for="contact_message">Mensagem</label>
        <textarea id="contact_message" name="message" rows="5" required minlength="10" maxlength="2000" class="input"
                  placeholder="{{ $product ? 'Quer outra cor, tamanho ou tem alguma dúvida sobre esta peça? Conte pra gente.' : 'Como podemos ajudar?' }}">{{ old('message') }}</textarea>
        @error('message')<p class="error">{{ $message }}</p>@enderror
    </div>
    <button class="btn-primary w-full !py-3.5 sm:w-auto sm:px-10">Enviar mensagem</button>
</form>
