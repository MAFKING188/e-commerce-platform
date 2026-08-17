@section('title', 'Member Registry | Admin')

<x-app-layout>
@include('partials.admin-nav')

<div class="admin-header">
    <div>
        <span class="cat-badge">Member Base</span>
        <h1>Registry.</h1>
    </div>
    <form action="{{ route('admin.users.index') }}" method="GET" style="display: flex; gap: 1rem;">
        <select name="status" class="form-input" style="width: 150px; padding: 0.75rem;">
            <option value="">All Status</option>
            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
        </select>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search members..." class="form-input" style="width: 250px;">
        <button type="submit" class="btn btn-primary">Filter</button>
    </form>
</div>

<div class="user-table-wrap user-table-wrap--scroll">
    <table class="user-table">
        <thead>
            <tr>
                <th>Member</th>
                <th>Access Tier</th>
                <th>Status</th>
                <th style="text-align: right;">Operations</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
                <tr>
                    <td>
                        <div style="font-weight: 800; color: var(--text-900);">{{ $user->name }}</div>
                        <div style="font-size: 0.8rem; color: var(--text-400);">{{ $user->email }}</div>
                    </td>
                    <td>
                        <form action="{{ route('admin.users.update', $user->id) }}" method="POST" style="display: flex; gap: 0.5rem; align-items: center;">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="{{ $user->status }}">
                            <select name="role" onchange="this.form.submit()" style="background: transparent; border: none; font-weight: 700; color: var(--brand-accent); outline: none; cursor: pointer;">
                                <option value="user" {{ $user->role == 'user' ? 'selected' : '' }}>USER</option>
                                <option value="partner" {{ $user->role == 'partner' ? 'selected' : '' }}>PARTNER</option>
                                <option value="admin" {{ $user->role == 'admin' ? 'selected' : '' }}>ADMIN</option>
                            </select>
                        </form>
                    </td>
                    <td>
                        <span class="status-pill status-{{ $user->status }}">
                            {{ $user->status }}
                        </span>
                    </td>
                    <td style="text-align: right;">
                        <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                            @if($user->status == 'pending')
                                <form action="{{ route('admin.users.approve', $user->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-accent" style="padding: 0.5rem 1rem; font-size: 0.7rem;">Approve</button>
                                </form>
                            @endif
                            <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Purge this member?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="color: var(--error); background: none; border: none; font-weight: 800; font-size: 0.7rem; text-transform: uppercase; cursor: pointer;">Purge</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div style="margin-top: 3rem;">
    {{ $users->links() }}
</div>
</x-app-layout>
