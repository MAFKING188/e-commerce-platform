@section('title', 'Add Product | Partner Dashboard')

@section('scripts')
@vite('resources/js/partner.js')
@endsection

<x-app-layout>
@include('partials.partner-nav')

<div class="pc-header">
    <div>
        <span class="pc-eyebrow">Inventory Expansion</span>
        <h1 class="pc-title">Add New Piece.</h1>
    </div>
</div>

<div class="form-container">
    <form action="{{ route('partner.inventory.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="pc-field">
            <label for="name" class="pc-field__label">Product Name</label>
            <input type="text" name="name" id="name" class="pc-field__input {{ $errors->has('name') ? 'pc-field__input--invalid' : '' }}" placeholder="e.g. Minimalist Oak Chair" value="{{ old('name') }}" required>
            @error('name')<p class="pc-field__error">{{ $message }}</p>@enderror
        </div>

        <div class="pc-form-grid">
            <div class="pc-field">
                <label for="price" class="pc-field__label">Price (USD)</label>
                <input type="number" name="price" id="price" class="pc-field__input {{ $errors->has('price') ? 'pc-field__input--invalid' : '' }}" step="0.01" placeholder="0.00" value="{{ old('price') }}" required>
                @error('price')<p class="pc-field__error">{{ $message }}</p>@enderror
            </div>
            <div class="pc-field">
                <label for="stock" class="pc-field__label">Initial Stock</label>
                <input type="number" name="stock" id="stock" class="pc-field__input {{ $errors->has('stock') ? 'pc-field__input--invalid' : '' }}" placeholder="0" value="{{ old('stock') }}" required>
                @error('stock')<p class="pc-field__error">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="pc-field">
            <label for="category_id" class="pc-field__label">Category</label>
            <select name="category_id" id="category_id" class="pc-field__input {{ $errors->has('category_id') ? 'pc-field__input--invalid' : '' }}" required>
                <option value="">Select a Category</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                @endforeach
            </select>
            @error('category_id')<p class="pc-field__error">{{ $message }}</p>@enderror
        </div>

        <div class="pc-field">
            <label for="description" class="pc-field__label">Detailed Narrative</label>
            <textarea name="description" id="description" class="pc-field__input {{ $errors->has('description') ? 'pc-field__input--invalid' : '' }}" placeholder="Describe the craftsmanship and soul of this piece..." required>{{ old('description') }}</textarea>
            @error('description')<p class="pc-field__error">{{ $message }}</p>@enderror
        </div>

        <div class="pc-field">
            <label for="images" class="pc-field__label">Product Narrative Visuals (JPEG/PNG)</label>
            <input type="file" name="images[]" id="images" class="pc-field__input" multiple>
            <p class="pc-field__hint">Select one or more images to build the product's visual story.</p>
        </div>

        <div class="inventory-form-actions">
            <button type="submit" class="btn btn-primary inventory-form-submit">Publish to Catalog</button>
            <a href="{{ route('partner.inventory.index') }}" class="btn btn-ghost inventory-form-cancel">Cancel</a>
        </div>
    </form>
</div>
</x-app-layout>