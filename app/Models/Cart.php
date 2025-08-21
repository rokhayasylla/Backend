<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Calculer le total du panier avec les promotions appliquées
     */
    public function getTotalAttribute()
    {
        $total = 0;

        foreach ($this->cartItems as $item) {
            $total += $item->total_price;
        }

        return round($total, 2);
    }

    /**
     * Obtenir le nombre total d'articles dans le panier
     */
    public function getTotalItemsAttribute()
    {
        return $this->cartItems->sum('quantity');
    }

    /**
     * Obtenir les produits individuels du panier
     */
    public function getProductItemsAttribute()
    {
        return $this->cartItems->where('item_type', 'product');
    }

    /**
     * Obtenir les packs du panier
     */
    public function getPackItemsAttribute()
    {
        return $this->cartItems->where('item_type', 'pack');
    }
}
