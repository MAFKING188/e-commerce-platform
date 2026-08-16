<?php

namespace Modules\CatalogDelivery\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\CatalogDelivery\Models\Category;
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
        return redirect()->route('admin.categories.index')->with('success', 'Category updated successfully');
    }
    
    public function destroy($id)
    {
        Category::destroy($id);
        return redirect()->back()->with('success', 'Category deleted');
    }


}

