<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        return view('admin.categories.index', [
            'categories' => Category::with('parent')->withCount('products')->orderBy('position')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.categories.form', [
            'category' => new Category(['active' => true, 'color' => '#22d3ee']),
            'parents' => Category::whereNull('parent_id')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        Category::create($this->validated($request));

        return redirect()->route('admin.categories.index')->with('success', 'Categoria criada!');
    }

    public function edit(Category $category)
    {
        return view('admin.categories.form', [
            'category' => $category,
            'parents' => Category::whereNull('parent_id')->whereKeyNot($category->id)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $category->update($this->validated($request, $category));

        return redirect()->route('admin.categories.index')->with('success', 'Categoria atualizada!');
    }

    public function destroy(Category $category)
    {
        if ($category->products()->exists()) {
            return back()->with('error', 'Esta categoria tem produtos. Mova ou exclua os produtos antes.');
        }

        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }
        $category->delete();

        return back()->with('success', 'Categoria excluída.');
    }

    private function validated(Request $request, ?Category $category = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => ['nullable', 'string', 'max:120', Rule::unique('categories')->ignore($category?->id)],
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string|max:1000',
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'position' => 'nullable|integer|min:0',
            'image' => 'nullable|image|max:4096',
        ], [], ['name' => 'nome', 'color' => 'cor', 'image' => 'imagem']);

        $data['slug'] = Str::slug(($data['slug'] ?? null) ?: $data['name']);
        $data['active'] = $request->boolean('active');
        $data['position'] ??= 0;

        if ($request->hasFile('image')) {
            if ($category?->image) {
                Storage::disk('public')->delete($category->image);
            }
            $data['image'] = $request->file('image')->store('categories', 'public');
        } else {
            unset($data['image']);
        }

        return $data;
    }
}
