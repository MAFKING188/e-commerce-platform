@section('title', 'Email Send History | LUWI Admin')

<x-app-layout>
@include('partials.admin-nav')

<div class="pc-header">
    <div>
        <span class="pc-eyebrow">Messaging</span>
        <h1 class="pc-title">Send History</h1>
    </div>
    <a href="{{ route('admin.email.compose') }}" class="btn btn-primary pc-btn-sm">Compose &amp; Send</a>
</div>

<form method="GET" action="{{ route('admin.email.logs') }}" class="inline-form pc-filter-bar">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Search recipient or subject…" class="pc-field__input">
    <select name="status" class="pc-field__input">
        <option value="">All statuses</option>
        @foreach(['sent' => 'Sent', 'pending' => 'Pending', 'failed' => 'Failed'] as $value => $label)
            <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-ghost pc-btn-sm">Filter</button>
</form>

@if($logs->isEmpty())
    <div class="pc-card pc-empty-state">
        <h3>No emails sent yet</h3>
        <p>Every email sent through the Email Center will be logged here.</p>
    </div>
@else
    <div class="pc-table-wrap">
        <table class="pc-table">
            <thead>
                <tr>
                    <th>Sent At</th>
                    <th>Sender</th>
                    <th>Recipient</th>
                    <th>Subject</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                    <tr>
                        <td>{{ $log->created_at?->format('M d, Y H:i') }}</td>
                        <td>{{ $log->sender?->name ?? '—' }} ({{ $log->sender_role }})</td>
                        <td>{{ $log->recipient_email }}</td>
                        <td>{{ $log->subject }}</td>
                        <td>
                            @if($log->status === 'sent')
                                <span class="profile-badge profile-badge--status-ok">Sent</span>
                            @elseif($log->status === 'failed')
                                <span class="profile-badge profile-badge--status-danger" title="{{ $log->error }}">Failed</span>
                            @else
                                <span class="profile-badge profile-badge--status-warn">Pending</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="pc-pagination">
        {{ $logs->links() }}
    </div>
@endif

</x-app-layout>