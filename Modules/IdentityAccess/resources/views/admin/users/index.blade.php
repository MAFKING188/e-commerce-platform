@section('title', 'Member Registry | Admin')

<x-app-layout>
@include('partials.admin-nav')

<div class="pc-header">
    <div>
        <span class="pc-eyebrow">Member Base</span>
        <h1 class="pc-title">Registry</h1>
    </div>
    <form action="{{ route('admin.users.index') }}" method="GET" class="pc-filter">
        <select name="status" class="pc-filter__select">
            <option value="">All Status</option>
            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
        </select>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search members..." class="pc-filter__input">
        <button type="submit" class="btn btn-primary pc-btn-sm">Filter</button>
        @if (request()->hasAny(['status', 'search']))
            <a href="{{ route('admin.users.index') }}" class="pc-filter__reset">Clear filters</a>
        @endif
    </form>
</div>

<div class="pc-table-wrap">
    <table class="pc-table">
        <thead>
            <tr>
                <th>Member</th>
                <th>Access Tier</th>
                <th>Status</th>
                <th class="is-right">Operations</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
                <tr>
                    <td>
                        <div class="is-strong">{{ $user->name }}</div>
                        <div class="is-muted">{{ $user->email }}</div>
                    </td>
                    <td>
                        <form action="{{ route('admin.users.update', $user->id) }}" method="POST" class="pc-row-actions">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="{{ $user->status }}">
                            <select name="role" onchange="this.form.submit()" class="pc-role-select">
                                <option value="user" {{ $user->role == 'user' ? 'selected' : '' }}>USER</option>
                                <option value="partner" {{ $user->role == 'partner' ? 'selected' : '' }}>PARTNER</option>
                                <option value="admin" {{ $user->role == 'admin' ? 'selected' : '' }}>ADMIN</option>
                            </select>
                        </form>
                    </td>
                    <td>
                        @include('partials.partner.status-badge', ['status' => $user->status])
                    </td>
                    <td class="is-right">
                        <div class="pc-row-actions pc-row-actions--end">
                            <a href="{{ route('admin.users.edit', $user->id) }}" class="pc-btn-sm">Edit</a>
                            @if($user->status == 'pending')
                                <form action="{{ route('admin.users.approve', $user->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="pc-btn-sm pc-btn-sm--ok">Approve</button>
                                </form>
                            @endif
                            <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" data-confirm="Purge this member? This cannot be undone.">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="pc-btn-sm pc-btn-sm--danger">Purge</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="pc-pagination">
    {{ $users->links() }}
</div>
</x-app-layout>