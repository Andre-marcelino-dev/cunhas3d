<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/** Uma opção de compra de um produto, ex.: "Com vela LED" (preço, foto e estoque próprios) */
class ProductVariant extends Model
{
    protected $fillable = ['product_id', 'name', 'price', 'sale_price', 'stock', 'image', 'position'];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
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

    /** Foto da variação; sem foto própria, usa a imagem principal do produto */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? Storage::disk('public')->url($this->image) : $this->product?->image_url;
    }

    public function inStock(): bool
    {
        return $this->stock > 0;
    }
}
