@extends('layouts.app')

@section('title', 'Establish Partner | Admin')

@section('styles')
<style>
    .form-container {
        max-width: 800px;
        margin: 0 auto;
        background: var(--surface-100);
        padding: 4rem;
        border-radius: 3rem;
        border: 1px solid var(--border);
        box-shadow: var(--shadow-lg);
    }
    .form-group { margin-bottom: 2.5rem; }
    .form-group label { display: block; font-weight: 800; text-transform: uppercase; font-size: 0.75rem; color: var(--text-400); margin-bottom: 1rem; letter-spacing: 0.1em; }
    .form-control { width: 100%; padding: 1.25rem; border-radius: 1rem; border: 1px solid var(--border); background: var(--surface-200); color: var(--text-900); font-family: inherit; font-size: 1rem; }
    .form-control:focus { outline: none; border-color: var(--brand-accent); background: var(--surface-100); }
</style>
@endsection

@section('content')
<div class="form-container">
    <div style="margin-bottom: 4rem;">
        <span class="cat-badge">Supply Chain</span>
        <h1 style="font-size: 2.5rem; font-weight: 800; margin-top: 1rem;">Establish Partner.</h1>
    </div>

    <form action="{{ route('admin.vendors.store') }}" method="POST">
        @csrf
        
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
            <a href="{{ route('admin.vendors.index') }}" class="btn btn-ghost" style="flex: 1; padding: 1.25rem;">Cancel</a>
        </div>
    </form>
</div>
@endsection
