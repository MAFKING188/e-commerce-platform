@section('title', 'Edit Public Profile | Partner Dashboard')

<x-app-layout>
@include('partials.partner-nav')

<div class="inventory-form-head">
    <span class="cat-badge">Artisan Portal</span>
    <h1 class="inventory-title">Edit Public Profile.</h1>
</div>

@if (session('status'))
    <div class="form-feedback">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="form-feedback form-feedback-error">{{ $errors->first() }}</div>
@endif

<div class="form-container">
    <form method="POST" action="{{ route('partner.profile.update') }}">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="name">Business name</label>
            <input id="name" name="name" class="form-control" value="{{ old('name', $partner->name) }}" required>
        </div>

        <div class="form-group">
            <label for="description">Bio</label>
            <textarea id="description" name="description" class="form-control" rows="4">{{ old('description', $partner->description) }}</textarea>
        </div>

        <div class="form-group">
            <label for="website">Website</label>
            <input id="website" name="website" class="form-control" value="{{ old('website', $partner->website) }}">
        </div>

        <div class="form-group">
            <label for="contact_info">Contact info</label>
            <input id="contact_info" name="contact_info" class="form-control" value="{{ old('contact_info', $partner->contact_info) }}">
        </div>

        <div class="inventory-form-actions">
            <button type="submit" class="btn btn-primary inventory-form-submit">Save changes</button>
        </div>
    </form>
</div>
</x-app-layout>