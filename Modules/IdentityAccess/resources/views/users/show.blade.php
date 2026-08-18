@section('title', 'Member Profile | LUWI')

<x-app-layout>
<div class="profile-grid">
    <!-- Left: Settings -->
    <div class="settings-card">
        <div class="avatar-circle">
            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
        </div>
        <h1 class="profile-name">{{ auth()->user()->name }}</h1>
        <p class="profile-since">Premium Member since {{ auth()->user()->created_at->format('M Y') }}</p>

        @if(session('success'))
            <div class="profile-flash profile-flash--ok">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="profile-flash profile-flash--err">
                <ul class="profile-flash__list">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('profile.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="profile-section">
                <span class="cat-badge">Account Details</span>
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-input" value="{{ auth()->user()->name }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-input" value="{{ auth()->user()->email }}">
                </div>
            </div>

            <div class="profile-section">
                <span class="cat-badge">Primary Residence</span>

                <div class="form-group">
                    <label class="form-label">Street Address</label>
                    <input type="text" name="line1" class="form-input" value="{{ $address->line1 ?? '' }}" placeholder="Luxury Street, 12">
                </div>

                <div class="form-group">
                    <label class="form-label">Apartment / Suite (Optional)</label>
                    <input type="text" name="line2" class="form-input" value="{{ $address->line2 ?? '' }}" placeholder="Apt, floor, building...">
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">City</label>
                        <input type="text" name="city" class="form-input" value="{{ $address->city ?? '' }}" placeholder="Milan">
                    </div>
                    <div class="form-group">
                        <label class="form-label">State / Region</label>
                        <input type="text" name="state" class="form-input" value="{{ $address->state ?? '' }}" placeholder="Lombardy">
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Postal Code</label>
                        <input type="text" name="zip" class="form-input" value="{{ $address->zip ?? '' }}" placeholder="20121">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Country</label>
                        <input type="text" name="country" class="form-input" value="{{ $address->country ?? '' }}" placeholder="Italy">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary profile-submit">Update Member Identity</button>
        </form>
    </div>

    <!-- Right: History -->
    <div class="history-section">
        <h2>Collection History</h2>

        @forelse(auth()->user()->orders as $order)
            <div class="order-item-lite">
                <div>
                    <span class="order-lite-id">#{{ $order->id }}</span>
                    <h3 class="order-lite-title">Placed on {{ $order->created_at->format('d M, Y') }}</h3>
                </div>
                <div class="order-lite-total">
                    <div class="order-lite-price">${{ number_format($order->total_price, 0) }}</div>
                    <span class="order-lite-status">{{ $order->status }}</span>
                </div>
            </div>
        @empty
            <div class="order-lite-empty">
                <p class="order-lite-empty__text">No acquisitions recorded yet.</p>
                <a href="{{ route('shop') }}" class="order-lite-empty__link">Begin Collection</a>
            </div>
        @endforelse
    </div>
</div>
</x-app-layout>