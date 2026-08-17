<div class="pc-card pc-stat">
    <span class="pc-stat__label">{{ $label }}</span>
    <div class="pc-stat__value {{ isset($accent) && $accent ? 'is-accent' : '' }}">{{ $value }}</div>
    @if (!empty($footnote))
        <div class="pc-stat__foot">{{ $footnote }}</div>
    @endif
    @if (!empty($footnoteLink) && !empty($footnoteLinkLabel))
        <a href="{{ $footnoteLink }}" class="pc-stat__link">{{ $footnoteLinkLabel }} →</a>
    @endif
</div>