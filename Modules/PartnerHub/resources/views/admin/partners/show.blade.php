@section('title', $partner->name . ' | Partner Profile')

<x-app-layout>
@include('partials.admin-nav')
<div class="pc-wrap-narrow">
    <a href="{{ route('admin.partners.index') }}" class="pc-back-link">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
        Back to Ecosystem
    </a>

    <div class="pc-card">
        <div class="pc-card__head">
            <div class="partner-meta">
                <span class="pc-eyebrow">Partner Artisan</span>
                <h1 class="pc-title">{{ $partner->name }}</h1>
                <p class="pc-subtitle">{{ $partner->description ?? 'No description provided for this luxury partner.' }}</p>
            </div>
            <div class="pc-row-actions pc-row-actions--end">
                @if($partner->website)
                    <a href="{{ $partner->website }}" target="_blank" class="pc-btn-sm">Visit Website</a>
                @endif
                <span class="pc-btn-sm">Contact: {{ $partner->contact_info }}</span>
            </div>
        </div>
    </div>

    <div class="pc-header">
        <div>
            <span class="pc-eyebrow">Catalog Mapping</span>
            <h1 class="pc-title">Inventory Mapping</h1>
            <p class="pc-subtitle">Pieces associated with this partner origin.</p>
        </div>
        <form action="{{ route('admin.partners.add_product', $partner->id) }}" method="POST" class="pc-filter">
            @csrf
            <select name="product_id" class="pc-filter__select">
                <option value="">Map New Piece...</option>
                @foreach($availableProducts as $prod)
                    <option value="{{ $prod->id }}">{{ $prod->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary pc-btn-sm">Link</button>
        </form>
    </div>

    <div class="inventory-grid">
        @foreach($partner->products as $product)
            <div class="mapping-card">
                <div class="mapping-card__head">
                    <div class="is-strong">{{ $product->name }}</div>
                    <form action="{{ route('admin.partners.remove_product', [$partner->id, $product->id]) }}" method="POST" data-confirm="Sever this product from the partner's catalog?">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="pc-btn-sm pc-btn-sm--danger">Sever</button>
                    </form>
                </div>
                <div class="is-muted mapping-card__cat">{{ $product->category->name }}</div>
                <div class="mapping-card__foot">
                    <span class="is-strong">${{ number_format($product->price, 2) }}</span>
                    <span class="is-muted">Stock: {{ $product->stock }}</span>
                </div>
            </div>
        @endforeach
    </div>
</div>
</x-app-layout>