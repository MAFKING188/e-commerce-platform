@section('title', 'Member Profile | LUWI')

<x-app-layout>
<x-profile-layout :user="$user" :stats="$stats" :active="'overview'" identity>

    <section class="profile-section">
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

    <section class="profile-section">
        <h2 class="pc-card__title">Quick Links</h2>
        <div class="profile-quicklinks">
            <a href="{{ route('orders.index') }}" class="profile-quicklinks__link">My Orders</a>
            <a href="{{ route('profile.wishlist') }}" class="profile-quicklinks__link">My Archive</a>
            <a href="{{ route('profile.security') }}" class="profile-quicklinks__link">Address & Security</a>
            <a href="{{ route('profile.settings') }}" class="profile-quicklinks__link">Settings</a>
        </div>
    </section>

</x-profile-layout>
</x-app-layout>