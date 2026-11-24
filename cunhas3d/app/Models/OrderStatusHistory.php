<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderStatusHistory extends Model
{
    protected $fillable = ['order_id', 'status', 'note'];

    public function getStatusLabelAttribute(): string
    {
        return Order::STATUSES[$this->status] ?? $this->status;
    }
}
