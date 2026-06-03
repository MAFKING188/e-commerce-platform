@extends('layouts.app')

@section('title', 'Member Profile | LUWI')

@section('styles')
<style>
    .profile-grid {
        display: grid;
        grid-template-columns: 350px 1fr;
        gap: 5rem;
        align-items: start;
    }

    .settings-card {
        background: var(--surface-100);
        padding: 3rem;
        border-radius: 2rem;
        border: 1px solid var(--border);
        box-shadow: var(--shadow-md);
        position: sticky;
        top: 8rem;
    }

    .avatar-circle {
        width: 100px;
        height: 100px;
        background: var(--brand-primary);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        font-weight: 800;
        margin-bottom: 2rem;
    }

    .form-group { margin-bottom: 2rem; }
    .form-label {
        display: block;
        font-size: 0.7rem;
        font-weight: 800;
        text-transform: uppercase;
        color: var(--text-400);
        margin-bottom: 0.75rem;
        letter-spacing: 0.1em;
    }

    .form-input {
        width: 100%;
        padding: 1rem 1.25rem;
        border-radius: 12px;
        border: 1px solid var(--border);
        background: var(--surface-200);
        color: var(--text-900);
        font-family: inherit;
        transition: all 0.3s ease;
    }

    .form-input:focus { border-color: var(--brand-accent); outline: none; background: var(--surface-100); }

    .history-section h2 {
        font-size: 2.5rem;
        font-weight: 800;
        color: var(--text-900);
        margin-bottom: 3rem;
    }

    .order-item-lite {
        background: var(--surface-100);
        padding: 2rem;
        border-radius: 1.5rem;
        border: 1px solid var(--border);
        margin-bottom: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: transform 0.3s ease;
    }

    .order-item-lite:hover { transform: translateX(10px); border-color: var(--brand-accent); }

    @media (max-width: 1024px) {
        .profile-grid { grid-template-columns: 1fr; gap: 4rem; }
    }
</style>
@endsection

@section('content')
<div class="profile-grid">
    <!-- Left: Settings -->
    <div class="settings-card">
        <div class="avatar-circle">
            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
        </div>
        <h1 style="font-size: 1.75rem; font-weight: 800; margin-bottom: 0.5rem;">{{ auth()->user()->name }}</h1>
        <p style="color: var(--text-400); margin-bottom: 3rem;">Premium Member since {{ auth()->user()->created_at->format('M Y') }}</p>

        @if(session('success'))
            <div style="background: #10b98120; color: #10b981; padding: 1rem; border-radius: 12px; margin-bottom: 2rem; font-weight: 700;">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div style="background: #ef444420; color: #ef4444; padding: 1rem; border-radius: 12px; margin-bottom: 2rem; font-size: 0.85rem;">
                <ul style="list-style: none; margin: 0; padding: 0;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('profile.update') }}" method="POST">
            @csrf
            @method('PUT')
            
            <div style="margin-bottom: 3rem;">
                <span class="cat-badge">Account Details</span>
                <div class="form-group" style="margin-top: 1rem;">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-input" value="{{ auth()->user()->name }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-input" value="{{ auth()->user()->email }}">
                </div>
            </div>

            <div style="margin-bottom: 3rem;">
                <span class="cat-badge">Primary Residence</span>
                @php $address = auth()->user()->addresses->first(); @endphp
                
                <div class="form-group" style="margin-top: 1rem;">
                    <label class="form-label">Street Address</label>
                    <input type="text" name="line1" class="form-input" value="{{ $address->line1 ?? '' }}" placeholder="Luxury Street, 12">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">City</label>
                        <input type="text" name="city" class="form-input" value="{{ $address->city ?? '' }}" placeholder="Milan">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Country</label>
                        <input type="text" name="country" class="form-input" value="{{ $address->country ?? '' }}" placeholder="Italy">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1.25rem;">Update Member Identity</button>
        </form>
    </div>

    <!-- Right: History -->
    <div class="history-section">
        <h2>Collection History</h2>
        
        @forelse(auth()->user()->orders as $order)
            <div class="order-item-lite">
                <div>
                    <span style="font-size: 0.75rem; font-weight: 800; color: var(--brand-accent);">#{{ $order->id }}</span>
                    <h3 style="font-size: 1.1rem; font-weight: 700; margin-top: 0.25rem;">Placed on {{ $order->created_at->format('d M, Y') }}</h3>
                </div>
                <div style="text-align: right;">
                    <div style="font-weight: 800; font-size: 1.25rem;">${{ number_format($order->total_price, 0) }}</div>
                    <span style="font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: #10b981;">{{ $order->status }}</span>
                </div>
            </div>
        @empty
            <div style="padding: 5rem; text-align: center; background: var(--surface-100); border-radius: 2rem; border: 1px dashed var(--border);">
                <p style="color: var(--text-400); font-weight: 700;">No acquisitions recorded yet.</p>
                <a href="{{ route('shop') }}" style="color: var(--brand-accent); font-weight: 800; margin-top: 1rem; display: block;">Begin Collection</a>
            </div>
        @endforelse
    </div>
</div>
@endsection
