@if ($paginator->hasPages())
    <nav class="mt-12 flex items-center justify-center gap-1.5" aria-label="Paginação">
        @if ($paginator->onFirstPage())
            <span class="chip opacity-40">‹</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="chip" rel="prev">‹</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="px-2 text-zinc-500">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    <a href="{{ $url }}" class="chip {{ $page == $paginator->currentPage() ? 'chip-active' : '' }}">{{ $page }}</a>
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="chip" rel="next">›</a>
        @else
            <span class="chip opacity-40">›</span>
        @endif
    </nav>
@endif
