<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Um celular/navegador que aceitou receber notificações do app */
class PushSubscription extends Model
{
    protected $fillable = ['user_id', 'endpoint_hash', 'endpoint', 'public_key', 'auth_token', 'user_agent', 'last_sent_at'];

    protected $hidden = ['endpoint', 'public_key', 'auth_token'];

    protected $casts = ['last_sent_at' => 'datetime'];

    public static function hashOf(string $endpoint): string
    {
        return hash('sha256', $endpoint);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
