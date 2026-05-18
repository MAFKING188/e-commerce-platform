@if ($paginator->hasPages())
    <nav class="luxury-pagination">
        <div class="pagination-inner">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span class="page-btn disabled">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="page-btn">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
            @endif

            {{-- Pagination Elements --}}
            <div class="page-numbers">
                @foreach ($elements as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <span class="page-dots">{{ $element }}</span>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="page-num active">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="page-num">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="page-btn">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            @else
                <span class="page-btn disabled">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </span>
            @endif
        </div>
    </nav>
@endif

<style>
    .luxury-pagination {
        display: flex;
        justify-content: center;
        margin-top: 4rem;
    }

    .pagination-inner {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        padding: 0.5rem 1rem;
        background: var(--surface-100);
        border: 1px solid var(--border);
        border-radius: 99px;
        box-shadow: var(--shadow-sm);
    }

    .page-numbers {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .page-num, .page-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        text-decoration: none;
        color: var(--text-600);
        font-weight: 700;
        font-size: 0.9rem;
        transition: all 0.3s ease;
    }

    .page-num:hover:not(.active) {
        background: var(--surface-300);
        color: var(--text-900);
    }

    .page-num.active {
        background: var(--brand-primary);
        color: var(--surface-100);
    }

    .page-btn {
        color: var(--text-900);
        border: 1px solid var(--border);
    }

    .page-btn.disabled {
        opacity: 0.3;
        cursor: not-allowed;
    }

    .page-btn:not(.disabled):hover {
        background: var(--brand-accent);
        color: white;
        border-color: var(--brand-accent);
    }

    .page-dots { color: var(--text-400); }
</style>
