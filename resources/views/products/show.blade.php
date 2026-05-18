@extends('layouts.app')

@section('content')

<h1>{{ $product->name }}</h1>

<!-- MAIN IMAGE -->
@if($product->images->first())
    <!-- MAIN IMAGE -->
@if($product->images->first())
    <div style="text-align:center;">
        <img id="mainImage"
             src="{{ asset('storage/' . $product->images->first()->url) }}"
             style="width:300px; border:1px solid #ddd; padding:5px;">
    </div>
@endif

<!-- THUMBNAILS -->
<div style="display:flex; gap:10px; margin-top:15px; justify-content:center;">
    @foreach($product->images as $image)
        <img src="{{ asset('storage/' . $image->url) }}"
             style="width:60px; cursor:pointer; border:1px solid #ccc;"
             onclick="document.getElementById('mainImage').src=this.src;">
    @endforeach
</div>
@else
    <img src="https://via.placeholder.com/300x200">
@endif

<!-- OPTIONAL: ALL IMAGES (mini gallery) -->
@if($product->images && $product->images->count() > 1)
    <div style="margin-top:10px;">
        @foreach($product->images as $image)
            <img src="{{ asset($image->url) }}" width="80" style="margin:5px;">
        @endforeach
    </div>
@endif

<p>{{ $product->description }}</p>
<p>Price: ${{ number_format($product->price, 2) }}</p>

<form action="{{ route('cart.add')}}" method="POST">
    @csrf
    <input type="hidden" name="product_id" value="{{ $product->id }}">
    <button type="submit">Add to Cart</button>
</form>

@endsection