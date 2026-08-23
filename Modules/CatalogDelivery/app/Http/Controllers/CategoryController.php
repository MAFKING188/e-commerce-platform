<?php

namespace Modules\CatalogDelivery\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\CatalogDelivery\Models\Category;
use Modules\CatalogDelivery\Services\CatalogCache;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('products')->latest()->get();
        return view('catalogdelivery::admin.categories.index', compact('categories'));
    }


    public function create()
    {
        return view('catalogdelivery::admin.categories.create');
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100'
        ]);

        Category::create($validated);
        CatalogCache::flush();

        return redirect()->route('admin.categories.index')->with('success', 'Category created successfully');
    }
    
    public function edit($id)
    {
        $category = Category::findOrFail($id);
        return view('catalogdelivery::admin.categories.edit', compact('category'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100'
        ]);

        $category = Category::findOrFail($id);
        $category->update($validated);
        CatalogCache::flush();

        return redirect()->route('admin.categories.index')->with('success', 'Category updated successfully');
    }

    public function destroy($id)
    {
        try {
            $category = Category::findOrFail($id);
            $category->delete();
            CatalogCache::flush();

            return redirect()->back()->with('success', 'Category deleted');
        } catch (\Illuminate\Database\QueryException $e) {
            return redirect()->back()
                ->with('error', 'Cannot delete this category: it still contains products.');
        }
    }


}

