@section('title', 'Admin Profile | Command Center')

<x-app-layout>
@include('partials.admin-nav')

<x-profile-layout :user="$user" :stats="$stats" :subnav="[
    ['id' => 'overview', 'label' => 'Overview'],
    ['id' => 'activity', 'label' => 'Recent Activity'],
    ['id' => 'security', 'label' => 'Address & Security'],
    ['id' => 'settings', 'label' => 'Settings'],
]">

    <section id="overview" class="profile-section">
        <h2 class="pc-card__title">Platform Pulse</h2>
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
    </section>

    <section id="activity" class="profile-section">
        <h2 class="pc-card__title">Recent Acquisitions</h2>
        <div class="pc-table-wrap">
            <table class="pc-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Member</th>
                        <th>Value</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentOrders as $order)
                        <tr>
                            <td class="is-numeric">#{{ $order->id }}</td>
                            <td class="is-muted">{{ $order->user->name }}</td>
                            <td class="is-strong">${{ number_format($order->total_price, 2) }}</td>
                            <td>@include('partials.partner.status-badge', ['status' => $order->status])</td>
                            <td class="is-muted">{{ $order->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="is-muted">No acquisitions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section id="security" class="profile-section">
        <h2 class="pc-card__title">Address & Password</h2>
        <div class="profile-card">
            <p class="pc-subtitle">Use the same forms as the member profile — address fields and password change are shared across roles.</p>
            <a href="{{ route('profile') }}" class="btn btn-ghost">Open Member Profile Settings</a>
        </div>
    </section>

    <section id="settings" class="profile-section">
        <h2 class="pc-card__title">Settings</h2>
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
                <button type="submit" class="btn btn-primary">Save Details</button>
            </form>
        </div>
    </section>

</x-profile-layout>
</x-app-layout>