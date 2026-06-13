@extends('layouts.app')

@section('title', 'Join the Collection | LUWI')

@section('styles')
<style>
    .auth-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 60vh;
        padding: 2rem 0;
    }

    .auth-card {
        background: var(--surface-100);
        padding: 2.5rem;
        border-radius: 12px;
        box-shadow: var(--shadow-md);
        width: 100%;
        max-width: 440px;
        border: 1px solid var(--border);
    }

    .auth-title {
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: var(--text-900);
        text-align: center;
    }

    .auth-subtitle {
        text-align: center;
        color: var(--text-600);
        font-size: 0.875rem;
        margin-bottom: 2rem;
    }

    .form-group {
        margin-bottom: 1.25rem;
    }

    .form-label {
        display: block;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--text-400);
        margin-bottom: 0.5rem;
    }

    .form-input {
        width: 100%;
        padding: 0.75rem 1rem;
        border-radius: 8px;
        border: 1px solid var(--border);
        background: var(--surface-200);
        color: var(--text-900);
        font-family: inherit;
        font-size: 0.95rem;
        transition: all 0.2s;
    }

    .form-input:focus {
        outline: none;
        border-color: var(--brand-accent);
        background: var(--surface-100);
        box-shadow: 0 0 0 4px var(--brand-accent-soft);
    }

    .auth-button {
        width: 100%;
        padding: 0.875rem;
        font-size: 1rem;
        font-weight: 600;
        background: var(--brand-primary);
        color: var(--surface-100);
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .auth-button:hover {
        background: var(--text-600);
    }

    .switch-auth {
        margin-top: 2rem;
        text-align: center;
        font-size: 0.875rem;
        color: var(--text-600);
    }

    .switch-auth a {
        color: var(--brand-accent);
        text-decoration: none;
        font-weight: 600;
    }
</style>
@endsection

@section('content')
<div class="auth-wrapper">
    <div class="auth-card">
        <h1 class="auth-title">Join the Collection</h1>
        <p class="auth-subtitle">Create your account to start shopping.</p>

        <form action="{{ url('/createaccount') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" value="{{ old('name') }}" required autofocus class="form-input">
            </div>

            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" value="{{ old('email') }}" required class="form-input">
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" required placeholder="Min. 8 characters" class="form-input">
            </div>

            <div class="form-group">
                <label class="form-label">Account Type</label>
                <select name="role" class="form-input" required>
                    <option value="user">Private Member (Instant Access)</option>
                    <option value="vendor">Partner Artisan (Requires Confirmation)</option>
                    <option value="admin">System Administrator (Requires Confirmation)</option>
                </select>
            </div>

            <button type="submit" class="auth-button">Create Account</button>
        </form>

        <p class="switch-auth">
            Already a member? <a href="{{ url('/login') }}">Sign in here</a>
        </p>
    </div>
</div>
@endsection
