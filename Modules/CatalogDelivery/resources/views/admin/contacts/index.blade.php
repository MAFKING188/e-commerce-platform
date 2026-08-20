@section('title', 'Contact Inquiries | Admin')

<x-app-layout>
@include('partials.admin-nav')

<div class="admin-header">
    <div>
        <span class="cat-badge">Inbound Inquiries</span>
        <h1>Contact Messages.</h1>
    </div>
</div>

<div class="reviews-list">
    @forelse($messages as $message)
        <div class="review-card">
            <div class="review-meta">
                <div class="user-info">
                    <div class="user-avatar">{{ substr($message->first_name, 0, 1) }}</div>
                    <div>
                        <div class="review-user-name">{{ $message->full_name }}</div>
                        <div class="review-date">{{ $message->created_at->format('M d, Y H:i') }} · {{ $message->ip_address ?? 'unknown IP' }}</div>
                    </div>
                </div>
                <div class="align-right">
                    <a href="mailto:{{ $message->email }}" class="piece-link">{{ $message->email }}</a>
                </div>
            </div>

            <div class="review-content">
                "{{ $message->message }}"
            </div>

            <div class="review-footer">
                <div class="review-piece">
                    <span class="review-date">User agent: {{ Str::limit($message->user_agent ?? 'unknown', 60) }}</span>
                </div>
                <div class="review-actions">
                    <form action="{{ route('admin.contacts.destroy', $message->id) }}" method="POST" data-confirm="Delete this inquiry?">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-primary review-purge">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="review-empty">
            <svg class="review-empty-icon" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            <p>No contact inquiries received yet.</p>
        </div>
    @endforelse

    <div class="review-pagination">
        {{ $messages->links() }}
    </div>
</div>
</x-app-layout>