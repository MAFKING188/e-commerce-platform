@extends('layouts.app')

@section('title', $vendor->name . ' | Partner Profile')

@section('styles')
<style>
    .vendor-profile-header {
        background: var(--surface-100);
        border: 1px solid var(--border);
        border-radius: 3rem;
        padding: 4rem;
        margin-bottom: 4rem;
        display: flex;
        gap: 4rem;
        align-items: center;
    }

    .vendor-visual {
        width: 120px;
        height: 120px;
        background: var(--brand-accent);
        color: white;
        border-radius: 2rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        font-weight: 800;
        flex-shrink: 0;
    }

    .vendor-meta h1 { font-size: 3rem; font-weight: 800; color: var(--text-900); margin-bottom: 0.5rem; }
    .vendor-meta p { color: var(--text-600); max-width: 600px; margin-bottom: 2rem; }

    .inventory-section { margin-top: 6rem; }
    .inventory-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 2rem; }

    .mapping-card {
        background: var(--surface-100);
        border: 1px solid var(--border);
        padding: 2rem;
        border-radius: 2rem;
        transition: all 0.3s ease;
    }
    .mapping-card:hover { border-color: var(--brand-accent); transform: translateY(-5px); }
</style>
@endsection

@section('content')
<div style="margin-bottom: 2rem;">
    <a href="{{ route('admin.vendors.index') }}" style="color: var(--text-400); font-weight: 700;">← Back to Ecosystem</a>
</div>

<div class="vendor-profile-header">
    <div class="vendor-visual">
        {{ substr($vendor->name, 0, 1) }}
    </div>
    <div class="vendor-meta">
        <span class="cat-badge" style="margin-bottom: 1rem; display: inline-block;">Partner Artisan</span>
        <h1>{{ $vendor->name }}</h1>
        <p>{{ $vendor->description ?? 'No description provided for this luxury partner.' }}</p>
        
        <div style="display: flex; gap: 2rem; align-items: center;">
            @if($vendor->website)
                <a href="{{ $vendor->website }}" target="_blank" class="btn btn-ghost">Visit Website</a>
            @endif
            <div style="color: var(--text-400); font-size: 0.9rem;">
                <strong>Contact:</strong> {{ $vendor->contact_info }}
            </div>
        </div>
    </div>
</div>

<div class="inventory-section">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 3rem;">
        <div>
            <h2 style="font-size: 2rem; font-weight: 800;">Inventory Mapping.</h2>
            <p style="color: var(--text-600);">Pieces associated with this partner origin.</p>
        </div>
        
        <form action="{{ route('admin.vendors.add_product', $vendor->id) }}" method="POST" style="display: flex; gap: 1rem; align-items: center; background: var(--surface-100); padding: 0.5rem 1rem; border-radius: 1.5rem; border: 1px solid var(--border);">
            @csrf
            <select name="product_id" style="background: transparent; border: none; font-weight: 600; color: var(--text-900); padding: 0.5rem; outline: none;">
                <option value="">Map New Piece...</option>
                @foreach($availableProducts as $prod)
                    <option value="{{ $prod->id }}">{{ $prod->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1.5rem;">Link</button>
        </form>
    </div>

    <div class="inventory-grid">
        @foreach($vendor->products as $product)
            <div class="mapping-card">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem;">
                    <div style="font-weight: 800; font-size: 1.1rem; color: var(--text-900);">{{ $product->name }}</div>
                    <form action="{{ route('admin.vendors.remove_product', [$vendor->id, $product->id]) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" style="background: none; border: none; color: var(--error); cursor: pointer; font-weight: 800; font-size: 0.7rem; text-transform: uppercase;">Sever</button>
                    </form>
                </div>
                <div style="color: var(--text-400); font-size: 0.8rem; margin-bottom: 1.5rem;">{{ $product->category->name }}</div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-weight: 800; color: var(--brand-accent);">${{ number_format($product->price, 2) }}</span>
                    <span style="font-size: 0.8rem; font-weight: 700; color: var(--text-600);">Stock: {{ $product->stock }}</span>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
