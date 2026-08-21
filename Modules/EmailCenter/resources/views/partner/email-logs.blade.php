@section('title', 'My Send History | LUWI Partner')

<x-app-layout>
@include('partials.partner-nav')

<div class="pc-header">
    <div>
        <span class="pc-eyebrow">Artisan Messaging</span>
        <h1 class="pc-title">My Send History</h1>
    </div>
    <a href="{{ route('partner.email.compose') }}" class="btn btn-primary pc-btn-sm">Email Your Buyers</a>
</div>

@if($logs->isEmpty())
    <div class="pc-card pc-empty-state">
        <h3>No emails sent yet</h3>
        <p>Emails you send to your buyers will be listed here.</p>
    </div>
@else
    <div class="pc-table-wrap">
        <table class="pc-table">
            <thead>
                <tr>
                    <th>Sent At</th>
                    <th>Recipient</th>
                    <th>Subject</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                    <tr>
                        <td>{{ $log->created_at?->format('M d, Y H:i') }}</td>
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