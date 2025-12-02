<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Order extends Model
{
    use HasFactory;

    protected $fillable=[
        'hold_id',
        'product_id',
        'quantity',
        'total_price',
        'status',
    ];

    protected $casts=[
        'quantity'=>'integer',
        'total_price'=>'decimal:2',
    ];

    public function hold():BelongTo{
        return $this->belongTo(Hold::Class);
    }

    public function product():BelongTo {
        return $this->belongTo(Product::class);
    }
}
