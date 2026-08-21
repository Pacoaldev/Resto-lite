<?php

namespace App\Orders\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class OrderModel extends Model
{
    protected $table = 'orders';

    protected $fillable = ['tableId', 'status', 'items'];

    protected $casts = [
        'items' => 'array',
    ];
}
