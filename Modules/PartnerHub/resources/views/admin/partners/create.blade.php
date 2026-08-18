@section('title', 'Establish Partner | Admin')

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
            <h1 class="pc-title">Establish Partner</h1>
        </div>
    </div>

    <div class="pc-card">
        <form action="{{ route('admin.partners.store') }}" method="POST">
            @csrf

            <div class="pc-form-grid">
                <div class="pc-field pc-field--full">
                    <label class="pc-field__label" for="user_id">Associate Partner Account (User)</label>
                    <select name="user_id" id="user_id" class="pc-field__input" required>
                        <option value="">Select a user...</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                        @endforeach
                    </select>
                    @error('user_id')
                        <div class="pc-field__error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="pc-field pc-field--full">
                    <label class="pc-field__label" for="name">Artisan / Partner Name</label>
                    <input id="name" type="text" name="name" class="pc-field__input" placeholder="e.g. Atelier Mafuleti" required>
                </div>

                <div class="pc-field pc-field--full">
                    <label class="pc-field__label" for="description">Philosophy / Description</label>
                    <textarea id="description" name="description" class="pc-field__input" rows="4" placeholder="Describe the artisan's philosophy and craft..."></textarea>
                </div>

                <div class="pc-field pc-field--full">
                    <label class="pc-field__label" for="contact_info">Contact Registry (Email/Phone)</label>
                    <input id="contact_info" type="text" name="contact_info" class="pc-field__input" placeholder="Direct contact details">
                </div>

                <div class="pc-field pc-field--full">
                    <label class="pc-field__label" for="website">Official Website (URL)</label>
                    <input id="website" type="url" name="website" class="pc-field__input" placeholder="https://artisan.com">
                </div>
            </div>

            <div class="pc-form-actions">
                <button type="submit" class="btn btn-primary pc-btn-sm">Initialize Relationship</button>
                <a href="{{ route('admin.partners.index') }}" class="pc-btn-sm">Cancel</a>
            </div>
        </form>
    </div>
</div>
</x-app-layout>