<?php

namespace App\Services;

use App\Http\Requests\ProductFormRequest;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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
            // Utiliser Storage::url() pour obtenir l'URL publique
            $productArray['imageUrl'] = Storage::disk('public')->url($product->image);
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
        // ✅ Retourner les produits formatés avec imageUrl
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
        // ✅ Retourner le produit formaté avec imageUrl
        return $this->formatProduct($product->load(['category', 'promotions']));
    }

    public function show(string $id)
    {
        $product = Product::with(['category', 'promotions', 'packs'])->findOrFail($id);
        // ✅ Retourner le produit formaté avec imageUrl
        return $this->formatProduct($product);
    }

    public function update(array $request, string $id)
    {
        $product = Product::findOrFail($id);

        // Gérer l'upload de la nouvelle image
        if (isset($request['image']) && $request['image'] instanceof UploadedFile) {
            // Supprimer l'ancienne image si elle existe
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }

            // Stocker la nouvelle image
            $newImageName = $this->storeImage($request['image']);
            $request['image'] = $newImageName; // Garder le chemin complet pour la mise à jour
        } elseif (isset($request['image'])) {
            // Si 'image' est présent mais pas un fichier (ex: null pour supprimer)
            if (is_null($request['image']) && $product->image) {
                Storage::disk('public')->delete($product->image);
                $request['image'] = null;
            } else {
                // Si on ne change pas l'image, on la retire du tableau
                unset($request['image']);
            }
        } else {
            // Pas d'image dans la requête, on ne touche pas à l'image existante
            unset($request['image']);
        }

        $product->update($request);
        $updatedProduct = $product->fresh(['category', 'promotions']);
        // ✅ Retourner le produit formaté avec imageUrl
        return $this->formatProduct($updatedProduct);
    }

    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);

        // ✅ Supprimer l'image avant de supprimer le produit
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
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
        return Storage::disk('public')->delete($imagePath);
    }

    public function getProductsByCategory(string $categoryId)
    {
        $products = Product::where('category_id', $categoryId)->get();
        // ✅ Retourner les produits formatés avec imageUrl
        return $this->formatProducts($products);
    }

    public function updateStock(string $productId, int $quantity)
    {
        $product = Product::findOrFail($productId);
        $product->decrement('stock_quantity', $quantity);
        // ✅ Retourner le produit formaté avec imageUrl
        return $this->formatProduct($product->fresh());
    }
}
