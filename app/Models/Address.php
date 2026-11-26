<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Endereço de entrega do cliente (usado pelo app) */
class Address extends Model
{
    protected $fillable = ['user_id', 'label', 'cep', 'address', 'number', 'complement', 'district', 'city', 'state', 'is_default'];

    protected $casts = ['is_default' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
