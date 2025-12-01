<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Product extends Model
{
    use HasFactory;

    //mass-assignment
    protected $fillable=['name','price','stock','version'];

    protected $casts=[
        'price' => 'decimal:2',
        'stock' => 'integer',
        'version' => 'integer',
    ];

    public function holds():HasMany
    {
        return $this->hasMany(Hold::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    //accessor or getter 

    public function getAvailableStockAttribute(): int
    {
        return $this->stock;
    }


}
