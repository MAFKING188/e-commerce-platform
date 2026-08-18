@section('title', 'Refine Partner | Admin')

<x-app-layout>
@include('partials.admin-nav')
<div class="pc-wrap-narrow">
    <a href="{{ route('admin.partners.index') }}" class="pc-back-link">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
        Back to Ecosystem
    </a>

    <div class="pc-header">
        <div>
            <span class="pc-eyebrow">Supply Chain</span>
            <h1 class="pc-title">Refine Partner</h1>
        </div>
    </div>

    <div class="pc-card">
        <form action="{{ route('admin.partners.update', $partner->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="pc-form-grid">
                <div class="pc-field pc-field--full">
                    <label class="pc-field__label" for="name">Artisan / Partner Name</label>
                    <input id="name" type="text" name="name" class="pc-field__input" value="{{ $partner->name }}" required>
                </div>

                <div class="pc-field pc-field--full">
                    <label class="pc-field__label" for="description">Philosophy / Description</label>
                    <textarea id="description" name="description" class="pc-field__input" rows="4">{{ $partner->description }}</textarea>
                </div>

                <div class="pc-field pc-field--full">
                    <label class="pc-field__label" for="contact_info">Contact Registry (Email/Phone)</label>
                    <input id="contact_info" type="text" name="contact_info" class="pc-field__input" value="{{ $partner->contact_info }}">
                </div>

                <div class="pc-field pc-field--full">
                    <label class="pc-field__label" for="website">Official Website (URL)</label>
                    <input id="website" type="url" name="website" class="pc-field__input" value="{{ $partner->website }}">
                </div>
            </div>

            <div class="pc-form-actions">
                <button type="submit" class="btn btn-primary pc-btn-sm">Refine Metadata</button>
                <a href="{{ route('admin.partners.index') }}" class="pc-btn-sm">Cancel</a>
            </div>
        </form>
    </div>
</div>
</x-app-layout>