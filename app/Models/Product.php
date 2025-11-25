<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $casts = [
        'price' => 'decimal:2',
    ];
    protected $appends = ['imageUrl'];
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function promotions()
    {
        return $this->belongsToMany(Promotion::class, 'promotion_products');
    }

    public function packs()
    {
        return $this->belongsToMany(Pack::class, 'pack_products')->withPivot('quantity');
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    // Accesseurs
    public function getImageUrlAttribute()
    {
        if ($this->image && Storage::disk('public')->exists($this->image)) {
            return Storage::disk('public')->url($this->image);
        }
        return null;
    }

    // Event listeners pour gérer les images
    protected static function boot()
    {
        parent::boot();

        // Supprimer l'image lors de la suppression du produit
        static::deleting(function ($product) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
        });
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }
}
