@props(['order'])
@php
    $map = [
        'amber' => 'border-amber-400/40 bg-amber-400/10 text-amber-300',
        'emerald' => 'border-emerald-400/40 bg-emerald-400/10 text-emerald-300',
        'violet' => 'border-violet-400/40 bg-violet-400/10 text-violet-300',
        'sky' => 'border-sky-400/40 bg-sky-400/10 text-sky-300',
        'green' => 'border-green-400/40 bg-green-400/10 text-green-300',
        'rose' => 'border-rose-400/40 bg-rose-400/10 text-rose-300',
        'zinc' => 'border-zinc-400/40 bg-zinc-400/10 text-zinc-300',
    ];
@endphp
<span {{ $attributes->merge(['class' => 'badge '.$map[$order->status_color]]) }}>
    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>{{ $order->status_label }}
</span>
