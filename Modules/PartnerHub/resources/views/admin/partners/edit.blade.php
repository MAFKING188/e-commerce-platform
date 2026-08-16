@section('title', 'Refine Partner | Admin')

<x-app-layout>
<div class="form-container">
    <div style="margin-bottom: 4rem;">
        <span class="cat-badge">Supply Chain</span>
        <h1 style="font-size: 2.5rem; font-weight: 800; margin-top: 1rem;">Refine Partner.</h1>
    </div>

    <form action="{{ route('admin.partners.update', $partner->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="form-group">
            <label>Artisan / Partner Name</label>
            <input type="text" name="name" class="form-control" value="{{ $partner->name }}" required>
        </div>

        <div class="form-group">
            <label>Philosophy / Description</label>
            <textarea name="description" class="form-control" rows="4">{{ $partner->description }}</textarea>
        </div>

        <div class="form-group">
            <label>Contact Registry (Email/Phone)</label>
            <input type="text" name="contact_info" class="form-control" value="{{ $partner->contact_info }}">
        </div>

        <div class="form-group">
            <label>Official Website (URL)</label>
            <input type="url" name="website" class="form-control" value="{{ $partner->website }}">
        </div>

        <div style="display: flex; gap: 2rem; margin-top: 4rem;">
            <button type="submit" class="btn btn-primary" style="flex: 1; padding: 1.25rem;">Refine Metadata</button>
            <a href="{{ route('admin.partners.index') }}" class="btn btn-ghost" style="flex: 1; padding: 1.25rem;">Cancel</a>
        </div>
    </form>
</div>
</x-app-layout>
