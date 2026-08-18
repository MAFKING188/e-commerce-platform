@section('title', 'Settings | LUWI')

<x-app-layout>
<x-profile-layout :user="$user" :active="'settings'">

    <section class="profile-section">
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
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-input" value="{{ $user->phone }}" placeholder="+33 6 12 34 56 78">
                </div>
                <button type="submit" class="btn btn-primary">Save Account Details</button>
            </form>
        </div>
    </section>

</x-profile-layout>
</x-app-layout>