<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('category')->latest();

        if ($search = $request->query('q')) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"));
        }

        if ($categoryId = $request->query('categoria')) {
            $query->where('category_id', $categoryId);
        }

        return view('admin.products.index', [
            'products' => $query->paginate(15)->withQueryString(),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.products.form', $this->formData(new Product([
            'active' => true, 'material' => 'PLA', 'color' => '#22d3ee', 'shape' => 'torusknot',
            'stock' => 10, 'production_days' => 3, 'weight_grams' => 200,
        ])));
    }

    public function store(Request $request)
    {
        $product = Product::create($this->validated($request));
        $this->storeGallery($request, $product);

        return redirect()->route('admin.products.index')->with('success', 'Produto cadastrado!');
    }

    public function edit(Product $product)
    {
        return view('admin.products.form', $this->formData($product->load('images')));
    }

    public function update(Request $request, Product $product)
    {
        $product->update($this->validated($request, $product));
        $this->storeGallery($request, $product);

        return redirect()->route('admin.products.edit', $product)->with('success', 'Produto atualizado!');
    }

    public function destroy(Product $product)
    {
        foreach ($product->images as $image) {
            Storage::disk('public')->delete($image->path);
        }
        Storage::disk('public')->delete(array_filter([$product->image, $product->model_path]));
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Produto excluído.');
    }

    public function destroyImage(ProductImage $image)
    {
        Storage::disk('public')->delete($image->path);
        $image->delete();

        return back()->with('success', 'Imagem removida.');
    }

    private function formData(Product $product): array
    {
        return [
            'product' => $product,
            'categories' => Category::orderBy('name')->get(),
            'materials' => Product::MATERIALS,
            'shapes' => Product::SHAPES,
        ];
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:160',
            'sku' => ['nullable', 'string', 'max:40', Rule::unique('products')->ignore($product?->id)],
            'short_description' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:10000',
            'price' => 'required|numeric|min:0.01|max:999999',
            'sale_price' => 'nullable|numeric|min:0|lt:price',
            'stock' => 'required|integer|min:0',
            'material' => ['required', Rule::in(Product::MATERIALS)],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'shape' => ['required', Rule::in(array_keys(Product::SHAPES))],
            'weight_grams' => 'required|integer|min:1',
            'production_days' => 'required|integer|min:0|max:60',
            'image' => 'nullable|image|max:5120',
            // Modelos 3D são binários (o navegador envia como application/octet-stream),
            // por isso validamos pela extensão. STL/3MF/OBJ saem direto do fatiador.
            'model' => 'nullable|file|max:51200|extensions:glb,stl,3mf,obj',
            'gallery.*' => 'nullable|image|max:5120',
        ], [
            'sale_price.lt' => 'O preço promocional deve ser menor que o preço normal.',
            'model.extensions' => 'O modelo 3D deve ser um arquivo .stl, .3mf, .obj ou .glb.',
            'model.max' => 'O modelo 3D pode ter no máximo 50 MB.',
        ], [
            'category_id' => 'categoria', 'name' => 'nome', 'price' => 'preço', 'sale_price' => 'preço promocional',
            'stock' => 'estoque', 'image' => 'imagem principal', 'model' => 'modelo 3D',
        ]);

        $data['featured'] = $request->boolean('featured');
        $data['active'] = $request->boolean('active');

        if ($request->hasFile('image')) {
            if ($product?->image) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')->store('products', 'public');
        } else {
            unset($data['image']);
        }

        if ($request->hasFile('model')) {
            if ($product?->model_path) {
                Storage::disk('public')->delete($product->model_path);
            }
            $extension = strtolower($request->file('model')->getClientOriginalExtension());
            $data['model_path'] = $request->file('model')->storeAs(
                'models', uniqid('m_').'.'.$extension, 'public'
            );
        }
        unset($data['model'], $data['gallery']);

        if ($request->boolean('remove_model') && $product?->model_path) {
            Storage::disk('public')->delete($product->model_path);
            $data['model_path'] = null;
        }

        return $data;
    }

    private function storeGallery(Request $request, Product $product): void
    {
        $position = (int) $product->images()->max('position');

        foreach ($request->file('gallery', []) as $file) {
            $product->images()->create([
                'path' => $file->store('products/gallery', 'public'),
                'position' => ++$position,
            ]);
        }
    }
}
