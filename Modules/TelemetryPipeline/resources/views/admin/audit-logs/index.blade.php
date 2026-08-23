@section('title', 'Audit Trail | LUWI Admin')

<x-app-layout>
@include('partials.admin-nav')

<div class="pc-header">
    <div>
        <span class="pc-eyebrow">Telemetry</span>
        <h1 class="pc-title">Audit Trail</h1>
    </div>
</div>

<form method="GET" action="{{ route('admin.audit-logs.index') }}" class="inline-form pc-filter-bar">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Filter by action…" class="pc-field__input">
    <select name="actor_id" class="pc-field__input">
        <option value="">All actors</option>
        @foreach($actors as $actor)
            <option value="{{ $actor->id }}" {{ request('actor_id') == $actor->id ? 'selected' : '' }}>{{ $actor->name }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-ghost pc-btn-sm">Filter</button>
</form>

@if($logs->isEmpty())
    <div class="pc-card pc-empty-state">
        <h3>No audit events yet</h3>
        <p>Administrative actions (member updates, order completion, payouts) are recorded here automatically.</p>
    </div>
@else
    <div class="pc-table-wrap">
        <table class="pc-table">
            <thead>
                <tr>
                    <th>When</th>
                    <th>Actor</th>
                    <th>Action</th>
                    <th>Details</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                    <tr>
                        <td>{{ $log->created_at?->format('M d, Y H:i') }}</td>
                        <td>{{ $log->actor?->name ?? 'System' }}</td>
                        <td><code>{{ $log->action }}</code></td>
                        <td class="is-muted">
                            @if($log->metadata)
                                {{ collect($log->metadata)->map(fn ($v, $k) => "{$k}: {$v}")->implode(', ') }}
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $log->ip ?? '—' }}</td>
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