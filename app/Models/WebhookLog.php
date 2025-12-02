<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class WebhookLog extends Model
{
    //
    use HasFactory;

    protected $fillable=[
        'idempotency_key',
        'order_id',
        'status',
        'payload',
        'processed_at',
    ];

    protected $casts=[
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];


    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
