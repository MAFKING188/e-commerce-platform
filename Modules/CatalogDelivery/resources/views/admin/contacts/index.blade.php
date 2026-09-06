@section('title', 'Contact Inquiries | Admin')

<x-app-layout>
@include('partials.admin-nav')

<div class="admin-header">
    <div>
        <span class="cat-badge">Inbound Inquiries</span>
        <h1>Contact Messages.</h1>
    </div>
</div>

@if(session('status'))
    <div class="pc-alert pc-alert--success" style="margin-bottom: 1.5rem;">{{ session('status') }}</div>
@endif

@if($errors->any())
    <div class="pc-alert pc-alert--error" style="margin-bottom: 1.5rem;">
        @foreach($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<div class="reviews-list">
    @forelse($messages as $message)
        <div class="review-card" style="border-left: 3px solid {{ $message->status === 'replied' ? '#16a34a' : ($message->status === 'archived' ? '#94a3b8' : '#f59e0b') }};">
            <div class="review-meta">
                <div class="user-info">
                    <div class="user-avatar">{{ substr($message->first_name, 0, 1) }}</div>
                    <div>
                        <div class="review-user-name">{{ $message->full_name }}</div>
                        <div class="review-date">{{ $message->created_at->format('M d, Y H:i') }} · {{ $message->ip_address ?? 'unknown IP' }}</div>
                    </div>
                </div>
                <div class="align-right" style="display: flex; align-items: center; gap: 0.75rem;">
                    <span style="display: inline-block; padding: 0.2rem 0.6rem; border-radius: 9999px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; background: {{ $message->status === 'replied' ? '#dcfce7' : ($message->status === 'archived' ? '#f1f5f9' : '#fef3c7') }}; color: {{ $message->status === 'replied' ? '#166534' : ($message->status === 'archived' ? '#64748b' : '#92400e') }};">
                        {{ ucfirst($message->status) }}
                    </span>
                    <a href="mailto:{{ $message->email }}" class="piece-link">{{ $message->email }}</a>
                </div>
            </div>

            <div class="review-content">
                "{{ $message->message }}"
            </div>

            @if($message->replies->count())
                <div style="margin: 1rem 0; padding: 1rem; background: #f0fdf4; border-radius: 8px; border: 1px solid #bbf7d0;">
                    <strong style="font-size: 0.8rem; color: #166534;">Previous Replies:</strong>
                    @foreach($message->replies as $reply)
                        <div style="margin-top: 0.75rem; padding-top: 0.75rem; {{ !$loop->last ? 'border-bottom: 1px solid #bbf7d0;' : '' }}">
                            <div style="font-size: 0.75rem; color: #64748b; margin-bottom: 0.25rem;">
                                {{ $reply->admin_name }} · {{ $reply->created_at->format('M d, Y H:i') }}
                            </div>
                            <p style="font-size: 0.875rem; margin: 0;">{{ $reply->body }}</p>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="review-footer">
                <div class="review-piece">
                    <span class="review-date">User agent: {{ Str::limit($message->user_agent ?? 'unknown', 60) }}</span>
                </div>
                <div class="review-actions" style="display: flex; gap: 0.5rem; align-items: center;">
                    <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('reply-form-{{ $message->id }}').style.display = document.getElementById('reply-form-{{ $message->id }}').style.display === 'none' ? 'block' : 'none';">
                        Reply
                    </button>
                    <form action="{{ route('admin.contacts.destroy', $message->id) }}" method="POST" data-confirm="Delete this inquiry?">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-primary review-purge">Delete</button>
                    </form>
                </div>
            </div>

            <div id="reply-form-{{ $message->id }}" style="display: none; margin-top: 1rem; padding: 1rem; background: var(--surface-200); border-radius: 8px;">
                <form action="{{ route('admin.contacts.reply', $message->id) }}" method="POST">
                    @csrf
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.5rem;">
                        Reply to {{ $message->full_name }} ({{ $message->email }})
                    </label>
                    <textarea name="body" rows="4" class="form-input" placeholder="Type your reply here..." required style="width: 100%; margin-bottom: 0.75rem;"></textarea>
                    <div style="display: flex; gap: 0.5rem;">
                        <button type="submit" class="btn btn-primary btn-sm">Send Reply via SmartShop</button>
                        <button type="button" class="btn btn-sm" onclick="document.getElementById('reply-form-{{ $message->id }}').style.display='none';" style="background: var(--surface-300);">Cancel</button>
                    </div>
                </form>
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
