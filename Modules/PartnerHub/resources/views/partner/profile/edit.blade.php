@section('title', 'Edit Public Profile | Partner Dashboard')

@section('scripts')
@vite('resources/js/partner.js')
@endsection

<x-app-layout>
@include('partials.partner-nav')

<div class="pc-header">
    <div>
        <span class="pc-eyebrow">Artisan Portal</span>
        <h1 class="pc-title">Edit Public Profile.</h1>
    </div>
</div>

@if (session('status'))
    <div class="pc-flash pc-flash--success">✓ {{ session('status') }}</div>
@endif

<div class="form-container">
    <form method="POST" action="{{ route('partner.profile.update') }}">
        @csrf
        @method('PUT')

        <div class="pc-field">
            <label for="name" class="pc-field__label">Business name</label>
            <input id="name" name="name" class="pc-field__input {{ $errors->has('name') ? 'pc-field__input--invalid' : '' }}" value="{{ old('name', $partner->name) }}" required>
            @error('name')<p class="pc-field__error">{{ $message }}</p>@enderror
        </div>

        <div class="pc-field">
            <label for="description" class="pc-field__label">Bio</label>
            <textarea id="description" name="description" class="pc-field__input {{ $errors->has('description') ? 'pc-field__input--invalid' : '' }}" rows="4">{{ old('description', $partner->description) }}</textarea>
            <p class="pc-field__hint">Shown on your public storefront profile to help customers understand your craft.</p>
            @error('description')<p class="pc-field__error">{{ $message }}</p>@enderror
        </div>

        <div class="pc-form-grid">
            <div class="pc-field">
                <label for="website" class="pc-field__label">Website</label>
                <input id="website" name="website" class="pc-field__input {{ $errors->has('website') ? 'pc-field__input--invalid' : '' }}" value="{{ old('website', $partner->website) }}">
                @error('website')<p class="pc-field__error">{{ $message }}</p>@enderror
            </div>
            <div class="pc-field">
                <label for="contact_info" class="pc-field__label">Contact info</label>
                <input id="contact_info" name="contact_info" class="pc-field__input {{ $errors->has('contact_info') ? 'pc-field__input--invalid' : '' }}" value="{{ old('contact_info', $partner->contact_info) }}">
                @error('contact_info')<p class="pc-field__error">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="inventory-form-actions">
            <button type="submit" class="btn btn-primary inventory-form-submit">Save changes</button>
        </div>
    </form>
</div>
</x-app-layout>