<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Mensagem enviada pelo "Fale conosco" do app */
class ContactMessage extends Model
{
    protected $fillable = ['user_id', 'name', 'email', 'subject', 'message', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];
}
