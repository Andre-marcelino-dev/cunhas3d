<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Histórico das notificações de novidade enviadas para todos */
class PushMessage extends Model
{
    protected $fillable = ['title', 'body', 'url', 'kind', 'sent', 'failed'];
}
