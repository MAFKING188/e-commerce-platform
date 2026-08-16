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
                    <div class="user-avatar">{{ substr($review->user->name, 0, 1) }}</div>
                    <div>
                        <div class="review-user-name">{{ $review->user->name }}</div>
                        <div class="review-date">{{ $review->created_at->format('M d, Y') }}</div>
                    </div>
                </div>
                <div class="align-right">
                    <div class="review-stars-row">
                        @for($i = 1; $i <= 5; $i++)
                            <span style="color: {{ $i <= $review->rating ? '#f59e0b' : 'var(--border)' }};">★</span>
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
                    <form action="{{ route('admin.reviews.destroy', $review->id) }}" method="POST" onsubmit="return confirm('Permanently purge this review?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-primary review-purge">Purge</button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="review-empty">
            <div class="review-empty-icon">🍃</div>
            <p>No community feedback recorded yet.</p>
        </div>
    @endforelse

    <div class="review-pagination">
        {{ $reviews->links() }}
    </div>
</div>
</x-app-layout>
