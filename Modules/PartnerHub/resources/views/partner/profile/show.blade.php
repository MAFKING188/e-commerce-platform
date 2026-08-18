@section('title', 'My Profile | Partner Dashboard')

@section('scripts')
@vite('resources/js/partner.js')
@endsection

<x-app-layout>
@include('partials.partner-nav')

<x-profile-layout :user="$user" :stats="$stats" :active="'overview'" identity>

    <section class="profile-section">
        <h2 class="pc-card__title">Atelier</h2>
        <div class="pc-stack">
            <div class="pc-card">
                <div class="pc-card__head">
                    <div>
                        <h2 class="pc-card__title">{{ $partner->name }}</h2>
                        <p class="pc-subtitle">{{ $partner->description }}</p>
                    </div>
                    <a href="{{ route('partner.profile', $partner->id) }}" class="pc-btn-sm">View Public Profile</a>
                </div>
            </div>
            @if ($partner->website || $partner->contact_info)
                <div class="profile-card">
                    @if ($partner->website)
                        <div class="profile-identity__row">
                            <span class="profile-identity__label">Website</span>
                            <span class="profile-identity__value"><a href="{{ $partner->website }}" target="_blank" rel="noopener">{{ $partner->website }}</a></span>
                        </div>
                    @endif
                    @if ($partner->contact_info)
                        <div class="profile-identity__row">
                            <span class="profile-identity__label">Contact</span>
                            <span class="profile-identity__value">{{ $partner->contact_info }}</span>
                        </div>
                    @endif
                </div>
            @endif
            <h2 class="pc-card__title">Recent Activity</h2>
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
        </div>
    </section>

</x-profile-layout>
</x-app-layout>