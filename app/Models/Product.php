<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class Product extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    // ❌ NE PAS utiliser $appends car l'URL est déjà dans 'image'
    // Le ProductService s'occupe d'ajouter 'imageUrl' dans formatProduct()

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

    // ❌ SUPPRIMER cet accesseur - l'URL Cloudinary est déjà complète
    // public function getImageUrlAttribute() { ... }

    protected static function boot()
    {
        parent::boot();

        // Supprimer l'image de Cloudinary lors de la suppression du produit
        static::deleting(function ($product) {
            if ($product->image && strpos($product->image, 'cloudinary.com') !== false) {
                $parts = explode('/', $product->image);
                $uploadIndex = array_search('upload', $parts);

                if ($uploadIndex !== false && isset($parts[$uploadIndex + 2])) {
                    $pathParts = array_slice($parts, $uploadIndex + 2);
                    $filename = end($pathParts);
                    $filenameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);

                    array_pop($pathParts);
                    $pathParts[] = $filenameWithoutExt;
                    $publicId = implode('/', $pathParts);

                    Cloudinary::destroy($publicId);
                }
            }
        });
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }
}
