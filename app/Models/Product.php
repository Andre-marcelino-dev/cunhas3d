<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Product extends Model
{
    public const MATERIALS = ['PLA', 'PETG', 'ABS', 'TPU', 'Resina', 'Nylon'];

    // Formas geradas pelo visualizador 3D quando o produto não tem arquivo .glb
    public const SHAPES = [
        'torusknot' => 'Nó toroidal',
        'vase' => 'Vaso',
        'gear' => 'Engrenagem',
        'icosahedron' => 'Poliedro',
        'figure' => 'Figura',
        'lamp' => 'Luminária',
    ];

    protected $fillable = [
        'category_id', 'name', 'slug', 'sku', 'short_description', 'description',
        'price', 'sale_price', 'stock', 'material', 'color', 'shape',
        'weight_grams', 'production_days', 'image', 'model_path', 'featured', 'active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'featured' => 'boolean',
        'active' => 'boolean',
        'push_announced_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            if (blank($product->slug)) {
                $product->slug = Str::slug($product->name).'-'.Str::lower(Str::random(4));
            }
            if (blank($product->sku)) {
                $product->sku = 'C3D-'.Str::upper(Str::random(6));
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('position')->orderBy('id');
    }

    public function hasVariants(): bool
    {
        return $this->variants->isNotEmpty();
    }

    /** Variações com preços diferentes: a vitrine mostra "a partir de" */
    public function hasPriceRange(): bool
    {
        return $this->variants->map(fn (ProductVariant $v) => $v->final_price)->unique()->count() > 1;
    }

    /**
     * Com variações, o preço do produto vira o da variação mais barata e o estoque a soma de todas.
     * Assim filtros, ordenação por preço, cards e o app continuam funcionando sem mudança.
     */
    public function syncFromVariants(): void
    {
        $variants = $this->variants()->get();

        if ($variants->isEmpty()) {
            return;
        }

        $cheapest = $variants->sortBy(fn (ProductVariant $v) => $v->final_price)->first();

        $this->update([
            'price' => $cheapest->price,
            'sale_price' => $cheapest->sale_price,
            'stock' => $variants->sum('stock'),
        ]);
    }

    /** Preço que o cliente paga (promocional, se houver) */
    public function getFinalPriceAttribute(): float
    {
        return (float) ($this->sale_price && $this->sale_price < $this->price ? $this->sale_price : $this->price);
    }

    public function getDiscountPercentAttribute(): ?int
    {
        if (! $this->sale_price || $this->sale_price >= $this->price) {
            return null;
        }

        return (int) round(100 - ($this->sale_price / $this->price * 100));
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }

    public function getModelUrlAttribute(): ?string
    {
        return $this->model_path ? Storage::disk('public')->url($this->model_path) : null;
    }

    /** Extensão do arquivo 3D (glb, stl, 3mf ou obj) */
    public function getModelFormatAttribute(): ?string
    {
        return $this->model_path ? strtolower(pathinfo($this->model_path, PATHINFO_EXTENSION)) : null;
    }

    /** STL e OBJ não têm cor: o site pinta com a cor escolhida (e o cliente pode testar outras) */
    public function getModelRecolorableAttribute(): bool
    {
        return ! $this->model_path || in_array($this->model_format, ['stl', 'obj'], true);
    }

    public function inStock(): bool
    {
        return $this->stock > 0;
    }
}
