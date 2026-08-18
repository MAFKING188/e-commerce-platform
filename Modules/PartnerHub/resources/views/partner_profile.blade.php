@section('title', $partner->name . ' | Partner Profile')

<x-app-layout>
<div class="partner-public-head">
    <h1>{{ $partner->name }}</h1>
    <p>{{ $partner->description }}</p>
    @if($partner->website)
        <a href="{{ $partner->website }}" target="_blank" class="btn btn-primary">Visit Website</a>
    @endif
</div>

<div class="container">
    <h2 class="partner-public-collection">Collection</h2>
    <div class="catalog-grid">
        @foreach($partner->products as $product)
            @include('catalogdelivery::components.product-card', ['product' => $product])
        @endforeach
    </div>
</div>
</x-app-layout>