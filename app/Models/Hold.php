<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Hold extends Model
{
    use HasFactory;

    protected $fillable= [
        'product_id',
        'quantity',
        'expires_at',
        'status',
    ];


    protected $casts=[
        'expires_at'=>'datetime',
        'quantity' =>'integer',
    ];

    //one to one (holds.product_id)
    public function product():BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    // one to one (orders.hold_id)
    public function order():HasOne
    {
        return $this->hasOne(Order::class);
    }
    public function isExpired():bool
    {
        return $this->expires_at < now();
    }
    public function isActive():bool
    {
        return $this->status === 'active' && !$this->isExpired();
    }
}
