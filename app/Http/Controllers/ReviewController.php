<?php

namespace App\Http\Controllers;

use Modules\CatalogDelivery\Models\Review;
use Modules\CatalogDelivery\Models\Product;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index()
    {
        $reviews = Review::with(['user', 'product'])
                         ->latest()
                         ->paginate(10);

        return view('admin.reviews.index', compact('reviews'));
    }

    public function create($productId)
    {
        $product = Product::findOrFail($productId);

        return view('reviews.create', compact('product'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|min:3|max:1000'
        ]);

        Review::create([
            'user_id' => auth()->id(),
            'product_id' => $request->product_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'status' => 'pending',
        ]);

        return redirect()->back()
            ->with('success', 'Review submitted successfully');
    }

    public function edit($id)
    {
        $review = Review::findOrFail($id);

        return view('reviews.edit', compact('review'));
    }

    public function update(Request $request, $id)
    {
        $review = Review::findOrFail($id);

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string'
        ]);

        $review->update($request->only(['rating', 'comment']));

        return redirect()->back()
            ->with('success', 'Review updated successfully');
    }

    public function destroy($id)
    {
        $review = Review::findOrFail($id);

        if (auth()->user()->role !== 'admin' && auth()->id() !== $review->user_id) {
            abort(403);
        }

        $review->delete();

        return redirect()->back()->with('success', 'Review removed successfully');
    }

    /**
     * Moderate: Approve a community piece.
     */
    public function approve($id)
    {
        $review = Review::findOrFail($id);
        $review->update(['status' => 'approved']);

        return redirect()->back()->with('success', 'Review curated to public view.');
    }

    /**
     * Moderate: Reject/Hide a community piece.
     */
    public function reject($id)
    {
        $review = Review::findOrFail($id);
        $review->update(['status' => 'rejected']);

        return redirect()->back()->with('success', 'Review hidden from public catalog.');
    }
}