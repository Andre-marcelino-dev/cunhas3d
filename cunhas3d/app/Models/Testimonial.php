<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Depoimento deixado pelo cliente no app */
class Testimonial extends Model
{
    protected $fillable = ['user_id', 'rating', 'message', 'approved'];

    protected $casts = ['approved' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
