@extends('layouts.app')

@section('title', 'Edit Member | SmartShop Admin')

@section('styles')
<style>
    .editor-container { max-width: 600px; margin: 4rem auto; }
    .editor-card {
        background: var(--surface-100);
        padding: 3rem;
        border-radius: 2rem;
        border: 1px solid var(--border);
        box-shadow: var(--shadow-lg);
    }
    .form-group { margin-bottom: 2rem; }
    .form-label {
        display: block;
        font-size: 0.7rem;
        font-weight: 800;
        text-transform: uppercase;
        color: var(--text-400);
        margin-bottom: 0.75rem;
    }
    .form-input {
        width: 100%;
        padding: 1rem 1.25rem;
        border-radius: 12px;
        border: 1px solid var(--border);
        background: var(--surface-200);
        color: var(--text-900);
    }
</style>
@endsection

@section('content')
<div class="editor-container">
    <div class="editor-card">
        <span class="cat-badge">Member Editor</span>
        <h1 style="font-size: 2rem; font-weight: 800; margin: 1rem 0 3rem;">Adjust Permissions</h1>

        <form action="{{ route('admin.users.update', $user->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-input" value="{{ $user->name }}" required>
            </div>

            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-input" value="{{ $user->email }}" required>
            </div>

            <div class="form-group">
                <label class="form-label">Access Role</label>
                <select name="role" class="form-input" required>
                    <option value="user" {{ $user->role == 'user' ? 'selected' : '' }}>Standard Member</option>
                    <option value="admin" {{ $user->role == 'admin' ? 'selected' : '' }}>Platform Admin</option>
                </select>
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 3rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1; padding: 1.25rem;">Save Changes</button>
                <a href="{{ route('admin.users.index') }}" class="btn" style="padding: 1.25rem; background: var(--surface-300); color: var(--text-600);">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
