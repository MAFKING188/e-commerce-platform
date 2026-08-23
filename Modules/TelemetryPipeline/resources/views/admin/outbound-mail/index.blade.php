@section('title', 'Outbound Mail | LUWI Admin')

<x-app-layout>
@include('partials.admin-nav')

<div class="pc-header">
    <div>
        <span class="pc-eyebrow">Telemetry</span>
        <h1 class="pc-title">Outbound Mail</h1>
    </div>
</div>

<p class="pc-field__hint">System-generated email (verification codes, order updates, alerts). Emails composed in the Email Center are logged separately under Messaging.</p>

<form method="GET" action="{{ route('admin.outbound-mail.index') }}" class="inline-form pc-filter-bar">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Search recipient or subject…" class="pc-field__input">
    <select name="status" class="pc-field__input">
        <option value="">All statuses</option>
        @foreach(['sent' => 'Sent', 'failed' => 'Failed'] as $value => $label)
            <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-ghost pc-btn-sm">Filter</button>
</form>

@if($logs->isEmpty())
    <div class="pc-card pc-empty-state">
        <h3>No outbound mail recorded yet</h3>
        <p>Every transactional email the platform sends is captured here automatically.</p>
    </div>
@else
    <div class="pc-table-wrap">
        <table class="pc-table">
            <thead>
                <tr>
                    <th>When</th>
                    <th>Recipient</th>
                    <th>Subject</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                    <tr>
                        <td>{{ $log->created_at?->format('M d, Y H:i') }}</td>
                        <td>{{ $log->recipient }}</td>
                        <td>{{ $log->subject ?? '—' }}</td>
                        <td>
                            @if($log->status === 'sent')
                                <span class="profile-badge profile-badge--status-ok">Sent</span>
                            @else
                                <span class="profile-badge profile-badge--status-danger">Failed</span>
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