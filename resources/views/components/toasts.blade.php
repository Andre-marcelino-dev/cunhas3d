{{-- Notificações flutuantes. Use window.toast('mensagem') no JS ou session('success'/'error') no PHP. --}}
<div x-data="{
        items: [],
        push(d) { const id = Date.now() + Math.random(); this.items.push({ id, ...d }); setTimeout(() => this.items = this.items.filter(i => i.id !== id), 4200); }
     }"
     x-init="
        @if(session('success')) push({ message: @js(session('success')), type: 'success' }); @endif
        @if(session('error')) push({ message: @js(session('error')), type: 'error' }); @endif
        @if($errors->has('cart')) push({ message: @js($errors->first('cart')), type: 'error' }); @endif
     "
     @toast.window="push($event.detail)"
     class="pointer-events-none fixed bottom-6 right-6 z-[70] flex w-[min(92vw,22rem)] flex-col gap-3">
    <template x-for="t in items" :key="t.id">
        <div x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-y-4 opacity-0"
             class="pointer-events-auto flex items-start gap-3 rounded-2xl border bg-panel/95 p-4 text-sm shadow-2xl backdrop-blur-xl"
             :class="t.type === 'error' ? 'border-rose-500/40' : 'border-neon/40'">
            <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full text-[11px] font-bold text-ink"
                  :class="t.type === 'error' ? 'bg-rose-400' : 'bg-neon'" x-text="t.type === 'error' ? '!' : '✓'"></span>
            <p class="text-zinc-200" x-text="t.message"></p>
        </div>
    </template>
</div>
