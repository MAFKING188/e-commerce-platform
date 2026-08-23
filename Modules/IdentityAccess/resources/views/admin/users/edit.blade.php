@section('title', 'Edit Member | LUWI Admin')

<x-app-layout>
@include('partials.admin-nav')

<div class="pc-wrap-narrow">
    <a href="{{ route('admin.users.index') }}" class="pc-back-link">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
        Back to Members
    </a>

    <div class="pc-header">
        <div>
            <span class="pc-eyebrow">Member Registry</span>
            <h1 class="pc-title">{{ $user->name }}</h1>
        </div>
    </div>

    <div class="pc-card">
        <form action="{{ route('admin.users.update', $user->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="pc-form-grid">
                <div class="pc-field pc-field--full">
                    <label class="pc-field__label">Email</label>
                    <p class="pc-field__static">{{ $user->email }}</p>
                </div>
            </div>

            <div class="pc-form-grid">
                <div class="pc-field">
                    <label class="pc-field__label" for="role">Role</label>
                    <select id="role" name="role" class="pc-field__input">
                        @foreach(['user' => 'Member', 'partner' => 'Artisan Partner', 'admin' => 'Administrator'] as $value => $label)
                            <option value="{{ $value }}" {{ $user->role === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('role')<span class="pc-field__error">{{ $message }}</span>@enderror
                </div>

                <div class="pc-field">
                    <label class="pc-field__label" for="status">Status</label>
                    <select id="status" name="status" class="pc-field__input">
                        @foreach(['active' => 'Active', 'pending' => 'Pending Confirmation', 'suspended' => 'Suspended'] as $value => $label)
                            <option value="{{ $value }}" {{ $user->status === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status')<span class="pc-field__error">{{ $message }}</span>@enderror
                </div>
            </div>

            <p class="pc-field__hint">Changing status notifies the member by email. Promoting to Artisan creates their portal registry entry automatically.</p>

            <div class="pc-form-actions">
                <button type="submit" class="btn btn-primary pc-btn-sm">Save Changes</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-ghost pc-btn-sm">Cancel</a>
            </div>
        </form>
    </div>
</div>

</x-app-layout>