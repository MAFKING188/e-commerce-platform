@section('title', 'Member Profile | LUWI')

<x-app-layout>
<x-profile-layout :user="$user" :stats="$stats" :subnav="[
    ['id' => 'overview', 'label' => 'Overview'],
    ['id' => 'activity', 'label' => 'Orders / Activity'],
    ['id' => 'security', 'label' => 'Address & Security'],
    ['id' => 'settings', 'label' => 'Settings'],
]">

    <section id="overview" class="profile-section">
        <h2 class="pc-card__title">Activity</h2>
        @if ($timeline->isEmpty())
            <div class="profile-card">
                <p class="profile-empty">No activity yet — your journey begins with the first piece you collect.</p>
            </div>
        @else
            <div class="profile-card">
                <ul class="profile-timeline">
                    @foreach ($timeline as $event)
                        <li class="profile-timeline__item">
                            <span class="profile-timeline__dot"></span>
                            <div>
                                <div class="profile-timeline__title">{{ $event['title'] }}</div>
                                @if ($event['detail'])
                                    <div class="profile-timeline__detail">{{ $event['detail'] }}</div>
                                @endif
                            </div>
                            <span class="profile-timeline__time">{{ $event['at']->diffForHumans() }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </section>

    <section id="activity" class="profile-section">
        <h2 class="pc-card__title">Order History</h2>
        @forelse ($recentOrders as $order)
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
        @if ($recentOrders->isNotEmpty())
            <a href="{{ route('orders.index') }}" class="btn btn-ghost">View Full History</a>
        @endif
    </section>

    <section id="security" class="profile-section">
        <h2 class="pc-card__title">Address</h2>
        <div class="profile-card">
            <form action="{{ route('profile.update') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Street Address</label>
                        <input type="text" name="line1" class="form-input" value="{{ $address->line1 ?? '' }}" placeholder="Luxury Street, 12">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Apartment / Suite (Optional)</label>
                        <input type="text" name="line2" class="form-input" value="{{ $address->line2 ?? '' }}" placeholder="Apt, floor, building...">
                    </div>
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
                <button type="submit" class="btn btn-primary">Save Address</button>
            </form>
        </div>

        <h2 class="pc-card__title">Password</h2>
        <div class="profile-card">
            <form action="{{ route('profile.password') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-input" required>
                </div>
                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <input type="password" name="password" class="form-input" minlength="8" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="password_confirmation" class="form-input" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Update Password</button>
            </form>
        </div>
    </section>

    <section id="settings" class="profile-section">
        <h2 class="pc-card__title">Account Details</h2>
        <div class="profile-card">
            <form action="{{ route('profile.update') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-input" value="{{ $user->name }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-input" value="{{ $user->email }}">
                </div>
                <button type="submit" class="btn btn-primary">Save Account Details</button>
            </form>
        </div>
    </section>

</x-profile-layout>
</x-app-layout>