@section('title', 'Add Product | Partner Dashboard')

<x-app-layout>
@include('partials.partner-nav')

<div class="inventory-form-head">
    <span class="cat-badge">Inventory Expansion</span>
    <h1 class="inventory-title">Add New Piece.</h1>
</div>

<div class="form-container">
    <form action="{{ route('partner.inventory.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        
        <div class="form-group">
            <label for="name">Product Name</label>
            <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Minimalist Oak Chair" required>
        </div>

        <div class="inventory-form-grid">
            <div class="form-group">
                <label for="price">Price (USD)</label>
                <input type="number" name="price" id="price" class="form-control" step="0.01" placeholder="0.00" required>
            </div>
            <div class="form-group">
                <label for="stock">Initial Stock</label>
                <input type="number" name="stock" id="stock" class="form-control" placeholder="0" required>
            </div>
        </div>

        <div class="form-group">
            <label for="category_id">Category</label>
            <select name="category_id" id="category_id" class="form-control" required>
                <option value="">Select a Category</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="description">Detailed Narrative</label>
            <textarea name="description" id="description" class="form-control" placeholder="Describe the craftsmanship and soul of this piece..." required></textarea>
        </div>

        <div class="form-group">
            <label for="images">Product Narrative Visuals (JPEG/PNG)</label>
            <input type="file" name="images[]" id="images" class="form-control" multiple>
            <p class="form-hint">Select one or more images to build the product's visual story.</p>
        </div>

        <div class="inventory-form-actions">
            <button type="submit" class="btn btn-primary inventory-form-submit">Publish to Catalog</button>
            <a href="{{ route('partner.inventory.index') }}" class="btn btn-ghost inventory-form-cancel">Cancel</a>
        </div>
    </form>
</div>
</x-app-layout>
