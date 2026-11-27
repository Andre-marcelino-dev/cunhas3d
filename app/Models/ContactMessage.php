<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Mensagem enviada pelo "Fale conosco" (site ou app), opcionalmente sobre um produto */
class ContactMessage extends Model
{
    protected $fillable = ['user_id', 'product_id', 'name', 'email', 'phone', 'subject', 'message', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }
}
