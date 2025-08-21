<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Pack extends Model
{
    use HasFactory;
    protected $guarded = [];
    public function products()
    {
        return $this->belongsToMany(Product::class, 'pack_products')->withPivot('quantity');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Accesseur pour l'URL de l'image
    public function getImageUrlAttribute()
    {
        if ($this->image_path && Storage::disk('packs')->exists($this->image_path)) {
            return Storage::disk('packs')->url($this->image_path);
        }
        return asset('images/default-pack.png'); // Image par défaut
    }

    // Event listeners pour gérer les images
    protected static function boot()
    {
        parent::boot();

        // Supprimer l'image lors de la suppression du pack
        static::deleting(function ($pack) {
            if ($pack->image_path) {
                Storage::disk('packs')->delete($pack->image_path);
            }
        });
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }
}
