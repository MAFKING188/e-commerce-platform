<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Product;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index()
    {
        $reviews = Review::with(['user', 'product'])
                         ->latest()
                         ->paginate(10);

        return view('reviews.index', compact('reviews'));
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
            'comment' => 'nullable|string'
        ]);

        Review::create([
            'user_id' => auth()->id(),
            'product_id' => $request->product_id,
            'rating' => $request->rating,
            'comment' => $request->comment
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

        $this->authorize('delete', $review);

        $review->delete();

        return redirect()->back()->with('success', 'Review deleted successfully');
    }
}