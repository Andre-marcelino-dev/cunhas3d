<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentEvent extends Model
{
    protected $fillable = ['mp_payment_id', 'status', 'payload'];

    protected $casts = ['payload' => 'array'];
}
