@extends('layouts.app')

@section('title', 'Member Management | LUWI Admin')

@section('styles')
<style>
    .admin-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 4rem; }
    .admin-header h1 { font-size: 3rem; font-weight: 800; color: var(--text-900); }

    .user-table-wrap {
        background: var(--surface-100);
        border-radius: 2rem;
        border: 1px solid var(--border);
        overflow: hidden;
        box-shadow: var(--shadow-md);
    }

    .user-table { width: 100%; border-collapse: collapse; }
    .user-table th {
        text-align: left;
        padding: 1.5rem 2rem;
        background: var(--surface-300);
        font-size: 0.7rem;
        font-weight: 800;
        text-transform: uppercase;
        color: var(--text-600);
        letter-spacing: 0.1em;
    }

    .user-table td { padding: 1.5rem 2rem; border-bottom: 1px solid var(--border); vertical-align: middle; }
    
    .user-info { display: flex; align-items: center; gap: 1rem; }
    .user-avatar {
        width: 40px;
        height: 40px;
        background: var(--brand-accent-soft);
        color: var(--brand-accent);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 0.8rem;
    }

    .role-pill {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 2rem;
        font-size: 0.65rem;
        font-weight: 800;
        text-transform: uppercase;
    }

    .role-admin { background: var(--brand-primary); color: var(--surface-100); }
    .role-user { background: var(--surface-300); color: var(--text-600); }
</style>
@endsection

@section('content')
<div class="admin-header">
    <div>
        <span class="cat-badge">Member Base</span>
        <h1>Community.</h1>
    </div>
    <div style="display: flex; align-items: flex-end; gap: 2rem;">
        <form action="{{ route('users.index') }}" method="GET" style="display: flex; gap: 0.5rem;">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name or email..." 
                style="padding: 0.75rem 1.5rem; border-radius: 1rem; border: 1px solid var(--border); background: var(--surface-100); width: 250px; font-size: 0.9rem;">
            <button type="submit" class="btn btn-primary" style="padding: 0.75rem 1.5rem; border-radius: 1rem;">Search</button>
            @if(request('search'))
                <a href="{{ route('users.index') }}" class="btn btn-secondary" style="padding: 0.75rem 1.5rem; border-radius: 1rem; display: flex; align-items: center; text-decoration: none;">Clear</a>
            @endif
        </form>
        <div style="font-weight: 700; color: var(--text-400);">Total: {{ $users->total() }} Members</div>
    </div>
</div>

@if(session('success'))
    <div style="background: #10b98120; color: #10b981; padding: 1.5rem; border-radius: 1rem; margin-bottom: 2rem; font-weight: 700;">
        {{ session('success') }}
    </div>
@endif

<div class="user-table-wrap">
    <table class="user-table">
        <thead>
            <tr>
                <th>Member</th>
                <th>Role</th>
                <th>Registration Date</th>
                <th style="text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
                <tr>
                    <td>
                        <div class="user-info">
                            <div class="user-avatar">{{ substr($user->name, 0, 1) }}</div>
                            <div>
                                <div style="font-weight: 700; color: var(--text-900);">{{ $user->name }} {{ auth()->id() == $user->id ? '(You)' : '' }}</div>
                                <div style="font-size: 0.8rem; color: var(--text-400);">{{ $user->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="role-pill role-{{ strtolower($user->role) }}">
                            {{ $user->role }}
                        </span>
                    </td>
                    <td style="color: var(--text-600); font-size: 0.9rem;">
                        {{ $user->created_at->format('d M, Y') }}
                    </td>
                    <td style="text-align: right;">
                        <div style="display: flex; gap: 1rem; justify-content: flex-end; align-items: center;">
                            <a href="{{ route('users.edit', $user->id) }}" style="color: var(--brand-accent); font-weight: 800; font-size: 0.7rem; text-transform: uppercase; text-decoration: none;">
                                Manage
                            </a>
                            
                            @if(auth()->id() !== $user->id)
                                <form action="{{ route('users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Revoke member access permanently?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="color: var(--error); background: none; border: none; font-weight: 800; font-size: 0.7rem; text-transform: uppercase; cursor: pointer;">
                                        Revoke
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div style="margin-top: 3rem;">
    {{ $users->links('partials.pagination') }}
</div>
@endsection
