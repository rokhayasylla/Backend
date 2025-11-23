<?php

namespace App\Services;

use App\Http\Requests\ProductFormRequest;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductService
{
    /**
     * ✅ Méthode helper pour formater un produit avec l'URL complète de l'image
     */
    private function formatProduct($product)
    {
        $productArray = $product->toArray();

        // Ajouter l'URL complète de l'image
        if ($product->image) {
            // Si l'image ne commence pas par "images/", l'ajouter (pour les anciennes images)
            $imagePath = $product->image;
            if (!Str::startsWith($imagePath, 'images/')) {
                $imagePath = 'images/' . $imagePath;
            }

            // Construire l'URL complète
            $productArray['imageUrl'] = url('storage/' . $imagePath);
        } else {
            $productArray['imageUrl'] = null;
        }

        return $productArray;
    }

    /**
     * ✅ Formater une collection de produits
     */
    private function formatProducts($products)
    {
        return $products->map(function($product) {
            return $this->formatProduct($product);
        });
    }

    public function index()
    {
        $products = Product::with(['category', 'promotions'])->get();
        return $this->formatProducts($products);
    }

    public function store(ProductFormRequest $request)
    {
        $data = $request->validated();

        // Gérer l'upload de l'image
        if ($request->hasFile('image')) {
            $data['image'] = $this->storeImage($request->file('image'));
        }

        $product = Product::create($data);
        return $this->formatProduct($product->load(['category', 'promotions']));
    }

    public function show(string $id)
    {
        $product = Product::with(['category', 'promotions', 'packs'])->findOrFail($id);
        return $this->formatProduct($product);
    }

    public function update(array $request, string $id)
    {
        $product = Product::findOrFail($id);

        // Gérer l'upload de la nouvelle image
        if (isset($request['image']) && $request['image'] instanceof UploadedFile) {
            // Supprimer l'ancienne image si elle existe
            if ($product->image) {
                // Gérer le cas où l'ancienne image n'a pas le préfixe "images/"
                $oldImagePath = $product->image;
                if (!Str::startsWith($oldImagePath, 'images/')) {
                    $oldImagePath = 'images/' . $oldImagePath;
                }
                Storage::disk('public')->delete($oldImagePath);
            }

            // Stocker la nouvelle image
            $request['image'] = $this->storeImage($request['image']);
        } elseif (isset($request['image'])) {
            // Si 'image' est présent mais pas un fichier
            if (is_null($request['image']) && $product->image) {
                $imagePath = $product->image;
                if (!Str::startsWith($imagePath, 'images/')) {
                    $imagePath = 'images/' . $imagePath;
                }
                Storage::disk('public')->delete($imagePath);
                $request['image'] = null;
            } else {
                // Si on ne change pas l'image, on la retire du tableau
                unset($request['image']);
            }
        } else {
            // Pas d'image dans la requête
            unset($request['image']);
        }

        $product->update($request);
        $updatedProduct = $product->fresh(['category', 'promotions']);
        return $this->formatProduct($updatedProduct);
    }

    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);

        // Supprimer l'image avant de supprimer le produit
        if ($product->image) {
            $imagePath = $product->image;
            if (!Str::startsWith($imagePath, 'images/')) {
                $imagePath = 'images/' . $imagePath;
            }
            Storage::disk('public')->delete($imagePath);
        }

        $product->delete();
    }

    /**
     * Stocker l'image et retourner le chemin relatif (images/filename.jpg)
     */
    private function storeImage(UploadedFile $file): string
    {
        // Générer un nom unique pour l'image
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

        // Stocker l'image dans storage/app/public/images/
        $path = $file->storeAs('images', $filename, 'public');

        // Retourner le chemin relatif : images/filename.jpg
        return $path;
    }

    /**
     * Supprimer une image
     */
    public function deleteImage(string $imagePath): bool
    {
        if (!Str::startsWith($imagePath, 'images/')) {
            $imagePath = 'images/' . $imagePath;
        }
        return Storage::disk('public')->delete($imagePath);
    }

    public function getProductsByCategory(string $categoryId)
    {
        $products = Product::where('category_id', $categoryId)->get();
        return $this->formatProducts($products);
    }

    public function updateStock(string $productId, int $quantity)
    {
        $product = Product::findOrFail($productId);
        $product->decrement('stock_quantity', $quantity);
        return $this->formatProduct($product->fresh());
    }
}
