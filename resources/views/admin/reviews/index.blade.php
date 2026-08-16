@section('title', 'Community Moderation | Admin')

@section('styles')
<style>
    .admin-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 4rem; }
    .admin-header h1 { font-size: 3rem; font-weight: 800; color: var(--text-900); }

    .review-card {
        background: var(--surface-100);
        border: 1px solid var(--border);
        border-radius: 2rem;
        padding: 2rem;
        margin-bottom: 2rem;
        transition: all 0.3s ease;
    }

    .review-card:hover { border-color: var(--brand-accent); box-shadow: var(--shadow-md); }

    .review-meta { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; }
    .user-info { display: flex; align-items: center; gap: 1rem; }
    .user-avatar { width: 40px; height: 40px; background: var(--surface-300); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; }

    .status-badge {
        font-size: 0.7rem;
        font-weight: 800;
        text-transform: uppercase;
        padding: 0.25rem 0.75rem;
        border-radius: 2rem;
    }
    .status-pending { background: #fef3c7; color: #92400e; }
    .status-approved { background: #dcfce7; color: #166534; }
    .status-rejected { background: #fee2e2; color: #991b1b; }

    .review-content { font-size: 1.1rem; color: var(--text-600); margin-bottom: 2rem; font-style: italic; }
    .piece-link { font-weight: 700; color: var(--brand-accent); text-decoration: none; }
</style>
@endsection

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
                        <div style="font-weight: 700;">{{ $review->user->name }}</div>
                        <div style="font-size: 0.8rem; color: var(--text-400);">{{ $review->created_at->format('M d, Y') }}</div>
                    </div>
                </div>
                <div style="text-align: right;">
                    <div style="margin-bottom: 0.5rem;">
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

            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div style="font-size: 0.9rem;">
                    Regarding: <a href="{{ route('product.show', $review->product_id) }}" class="piece-link">{{ $review->product->name }}</a>
                </div>
                <div style="display: flex; gap: 1rem;">
                    @if($review->status !== 'approved')
                        <form action="{{ route('admin.reviews.approve', $review->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-ghost" style="color: var(--success); border-color: var(--success);">Curate</button>
                        </form>
                    @endif
                    @if($review->status !== 'rejected')
                        <form action="{{ route('admin.reviews.reject', $review->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-ghost" style="color: var(--error); border-color: var(--error);">Hide</button>
                        </form>
                    @endif
                    <form action="{{ route('admin.reviews.destroy', $review->id) }}" method="POST" onsubmit="return confirm('Permanently purge this review?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-primary" style="background: #000;">Purge</button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div style="text-align: center; padding: 10rem 0; color: var(--text-400);">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🍃</div>
            <p>No community feedback recorded yet.</p>
        </div>
    @endforelse

    <div style="margin-top: 4rem;">
        {{ $reviews->links() }}
    </div>
</div>
</x-app-layout>
