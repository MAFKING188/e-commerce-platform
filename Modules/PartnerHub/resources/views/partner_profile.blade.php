@section('title', $partner->name . ' | Partner Profile')

<x-app-layout>
<div style="text-align: center; margin-bottom: 4rem;">
    <h1 style="font-size: 3rem; font-weight: 800;">{{ $partner->name }}</h1>
    <p style="color: var(--text-600); max-width: 600px; margin: 1rem auto;">{{ $partner->description }}</p>
    @if($partner->website)
        <a href="{{ $partner->website }}" target="_blank" class="btn btn-primary">Visit Website</a>
    @endif
</div>

<div class="container">
    <h2 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 2rem;">Collection</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 2rem;">
        @foreach($partner->products as $product)
            @include('catalogdelivery::components.product-card', ['product' => $product])
        @endforeach
    </div>
</div>
</x-app-layout>
