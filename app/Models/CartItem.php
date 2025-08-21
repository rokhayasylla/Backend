<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
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
     * Obtenir le prix unitaire avec promotion si applicable (calculé dynamiquement)
     */
    public function getUnitPriceAttribute()
    {
        if ($this->item_type === 'product' && $this->product) {
            $price = $this->product->price;

            // Vérifier s'il y a une promotion active
            $activePromotion = $this->product->promotions()
                ->where('is_active', true)
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->first();

            if ($activePromotion) {
                if ($activePromotion->discount_percentage) {
                    $price = $price * (1 - $activePromotion->discount_percentage / 100);
                } elseif ($activePromotion->discount_amount) {
                    $price = max(0, $price - $activePromotion->discount_amount);
                }
            }

            return round($price, 2);
        }

        if ($this->item_type === 'pack' && $this->pack) {
            return $this->pack->price;
        }

        return 0;
    }

    /**
     * Calculer le prix total de cet item (calculé dynamiquement)
     */
    public function getTotalPriceAttribute()
    {
        return round($this->unit_price * $this->quantity, 2);
    }

    /**
     * Vérifier si l'item est en promotion
     */
    public function getIsOnPromotionAttribute()
    {
        if ($this->item_type === 'product' && $this->product) {
            return $this->product->promotions()
                ->where('is_active', true)
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->exists();
        }

        return false; // Les packs n'ont pas de promotions
    }

    /**
     * Obtenir le nom de l'item
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
     * Scopes pour filtrer par type
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
