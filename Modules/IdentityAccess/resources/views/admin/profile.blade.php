@section('title', 'Admin Profile | Command Center')

<x-app-layout>
@include('partials.admin-nav')

<x-profile-layout :user="$user" :stats="$stats" :active="'overview'" identity>

    <section class="profile-section">
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

    <section class="profile-section">
        <h2 class="pc-card__title">Command Center</h2>
        <div class="profile-card">
            <p class="pc-subtitle">Orders, payouts, reviews and members live in the Command Center.</p>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-ghost">Open Command Center</a>
        </div>
    </section>

</x-profile-layout>
</x-app-layout>