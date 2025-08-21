<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function pack()
    {
        return $this->belongsTo(Pack::class);
    }

    /**
     * Accesseur pour obtenir le nom de l'item (produit ou pack)
     */
    public function getItemNameAttribute()
    {
        if ($this->item_type === 'product' && $this->product) {
            return $this->product->name;
        } elseif ($this->item_type === 'pack' && $this->pack) {
            return $this->pack->name;
        }

        return 'Item supprimé';
    }

    /**
     * Accesseur pour obtenir la description de l'item
     */
    public function getItemDescriptionAttribute()
    {
        if ($this->item_type === 'product' && $this->product) {
            return $this->product->description;
        } elseif ($this->item_type === 'pack' && $this->pack) {
            return $this->pack->description;
        }

        return null;
    }

    /**
     * Accesseur pour obtenir l'image de l'item
     */
    public function getItemImageUrlAttribute()
    {
        if ($this->item_type === 'product' && $this->product) {
            return $this->product->image_url;
        } elseif ($this->item_type === 'pack' && $this->pack) {
            return $this->pack->image_url;
        }

        return null;
    }

    /**
     * Scope pour filtrer par type d'item
     */
    public function scopeProducts($query)
    {
        return $query->where('item_type', 'product');
    }

    public function scopePacks($query)
    {
        return $query->where('item_type', 'pack');
    }
}
