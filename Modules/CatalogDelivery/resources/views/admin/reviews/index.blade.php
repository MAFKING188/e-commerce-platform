@section('title', 'Community Moderation | Admin')

<x-app-layout>
@include('partials.admin-nav')

<div class="admin-header">
    <div>
        <span class="cat-badge">Community Sentiment</span>
        <h1>Moderation.</h1>
    </div>
</div>

<div class="reviews-list">
    @forelse($reviews as $review)
        <div class="review-card">
            <div class="review-meta">
                <div class="user-info">
                    <div class="user-avatar">
                        @if ($review->user->avatarUrl())
                            <img src="{{ $review->user->avatarUrl() }}" alt="{{ $review->user->name }}" class="user-avatar__img">
                        @else
                            {{ substr($review->user->name, 0, 1) }}
                        @endif
                    </div>
                    <div>
                        <div class="review-user-name">{{ $review->user->name }}</div>
                        <div class="review-date">{{ $review->created_at->format('M d, Y') }}</div>
                    </div>
                </div>
                <div class="align-right">
                    <div class="review-stars-row">
                        @for($i = 1; $i <= 5; $i++)
                            <span class="review-star {{ $i <= $review->rating ? 'is-filled' : '' }}">★</span>
                        @endfor
                    </div>
                    <span class="status-badge status-{{ $review->status ?? 'pending' }}">
                        {{ $review->status ?? 'pending' }}
                    </span>
                </div>
            </div>

            <div class="review-content">
                "{{ $review->comment }}"
            </div>

            <div class="review-footer">
                <div class="review-piece">
                    Regarding: <a href="{{ route('product.show', $review->product_id) }}" class="piece-link">{{ $review->product->name }}</a>
                </div>
                <div class="review-actions">
                    @if($review->status !== 'approved')
                        <form action="{{ route('admin.reviews.approve', $review->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-ghost review-curate">Curate</button>
                        </form>
                    @endif
                    @if($review->status !== 'rejected')
                        <form action="{{ route('admin.reviews.reject', $review->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-ghost review-hide">Hide</button>
                        </form>
                    @endif
                    <form action="{{ route('admin.reviews.destroy', $review->id) }}" method="POST" data-confirm="Permanently purge this review?">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-primary review-purge">Purge</button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="review-empty">
            <svg class="review-empty-icon" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            <p>No community feedback recorded yet.</p>
        </div>
    @endforelse

    <div class="review-pagination">
        {{ $reviews->links() }}
    </div>
</div>
</x-app-layout>
