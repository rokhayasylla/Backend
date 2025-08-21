<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $guarded = ['order_number'];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'delivered_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            // Générer un numéro unique avec format : ORD-YYYYMMDD-XXXXX
            $date = now()->format('Ymd');
            $lastOrder = static::whereDate('created_at', today())
                ->orderBy('id', 'desc')
                ->first();

            $sequence = $lastOrder ?
                intval(substr($lastOrder->order_number, -5)) + 1 : 1;

            $order->order_number = 'CMD-' . $date . '-' . str_pad($sequence, 5, '0', STR_PAD_LEFT);
        });
    }
}
