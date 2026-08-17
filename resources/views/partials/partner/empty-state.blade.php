<div class="pc-empty">
    <div class="pc-empty__icon">
        @if (($icon ?? '') === 'box')
            <svg viewBox="0 0 24 24"><path d="M21 8l-9-5-9 5v8l9 5 9-5V8z"/><path d="M3 8l9 5 9-5"/><path d="M12 13v8"/></svg>
        @elseif (($icon ?? '') === 'receipt')
            <svg viewBox="0 0 24 24"><path d="M6 2h12v20l-2-1.5L14 22l-2-1.5L10 22l-2-1.5L6 22V2z"/><path d="M9 7h6"/><path d="M9 11h6"/><path d="M9 15h4"/></svg>
        @elseif (($icon ?? '') === 'search')
            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
        @else
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
        @endif
    </div>
    <div class="pc-empty__title">{{ $title }}</div>
    <div class="pc-empty__text">{{ $text }}</div>
    @if (!empty($actionLabel) && !empty($actionUrl))
        <a href="{{ $actionUrl }}" class="btn btn-primary pc-empty__action">{{ $actionLabel }}</a>
    @endif
</div>