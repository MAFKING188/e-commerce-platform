@extends('layouts.app')

@section('title', 'Your Archive | SmartShop')

@section('content')
<div style="margin-bottom: 4rem;">
    <h1 style="font-size: 3rem; font-weight: 800;">Your Archive.</h1>
    <p style="color: var(--text-600);">Personal curation of exceptional pieces.</p>
</div>

@if($items->isEmpty())
    <div style="text-align: center; padding: 10rem 0; border: 2px dashed var(--border); border-radius: 2rem;">
        <h2 style="font-weight: 700; color: var(--text-800);">Your archive is empty.</h2>
        <p style="color: var(--text-400); margin-bottom: 2rem;">Start curating your personal collection of luxury pieces.</p>
        <a href="{{ route('shop') }}" class="btn btn-primary">Discover Pieces</a>
    </div>
@else
    <div class="catalog-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 3rem;">
        @foreach($items as $item)
            @include('components.product-card', ['product' => $item->product])
        @endforeach
    </div>
@endif

@endsection
