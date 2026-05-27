<div class="product-card">
    <img src="{{asset('storage/' . $product->image)}}" alt="image here boss">

    <h3>{{ $product->name }}</h3>
    <p>${{ $product->price }}</p>

    <a href="{{ route('products.show', $product->id)}}">View Details</a>
</div>