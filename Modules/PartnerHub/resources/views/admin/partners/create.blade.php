@section('title', 'Establish Partner | Admin')

<x-app-layout>
<div class="form-container">
    <div style="margin-bottom: 4rem;">
        <span class="cat-badge">Supply Chain</span>
        <h1 style="font-size: 2.5rem; font-weight: 800; margin-top: 1rem;">Establish Partner.</h1>
    </div>

    <form action="{{ route('admin.partners.store') }}" method="POST">
        @csrf
        
        <div class="form-group">
            <label>Associate Partner Account (User)</label>
            <select name="user_id" class="form-control" required>
                <option value="">Select a user...</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                @endforeach
            </select>
            @error('user_id')
                <div style="color: #ef4444; margin-top: 0.5rem; font-size: 0.8rem;">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label>Artisan / Partner Name</label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Atelier Mafuleti" required>
        </div>

        <div class="form-group">
            <label>Philosophy / Description</label>
            <textarea name="description" class="form-control" rows="4" placeholder="Describe the artisan's philosophy and craft..."></textarea>
        </div>

        <div class="form-group">
            <label>Contact Registry (Email/Phone)</label>
            <input type="text" name="contact_info" class="form-control" placeholder="Direct contact details">
        </div>

        <div class="form-group">
            <label>Official Website (URL)</label>
            <input type="url" name="website" class="form-control" placeholder="https://artisan.com">
        </div>

        <div style="display: flex; gap: 2rem; margin-top: 4rem;">
            <button type="submit" class="btn btn-primary" style="flex: 1; padding: 1.25rem;">Initialize Relationship</button>
            <a href="{{ route('admin.partners.index') }}" class="btn btn-ghost" style="flex: 1; padding: 1.25rem;">Cancel</a>
        </div>
    </form>
</div>
</x-app-layout>
