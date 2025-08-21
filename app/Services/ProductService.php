<?php

namespace App\Services;

use App\Http\Requests\ProductFormRequest;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    public function index()
    {
        return Product::with(['category', 'promotions'])->get();
    }

    public function store(ProductFormRequest $request)
    {
        $data = $request->validated();

        // Gérer l'upload de l'image
        if ($request->hasFile('image')) {
            $data['image'] = $this->storeImage($request->file('image'));
        }

        return Product::create($data);
    }

    public function show(string $id)
    {
        return Product::with(['category', 'promotions', 'packs'])->findOrFail($id);
    }

    public function update(array $request, string $id)
    {
        $product = Product::findOrFail($id);

        // Gérer l'upload de la nouvelle image
        if (isset($request['image']) && $request['image'] instanceof UploadedFile) {
            // Supprimer l'ancienne image si elle existe
            if ($product->image) {
                Storage::disk('images')->delete($product->image);
            }

            // Stocker la nouvelle image
            $newImageName = $this->storeImage($request['image']);
            $request['image'] = $newImageName; // Garder le nom pour la mise à jour
        } elseif (isset($request['image'])) {
            // Si 'image' est présent mais pas un fichier (ex: null pour supprimer)
            if (is_null($request['image']) && $product->image) {
                Storage::disk('images')->delete($product->image);
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
        return $product->fresh(['category', 'promotions']);
    }

    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);
        $product->delete();
    }

    /**
     * Stocker l'image et retourner le chemin
     */
    private function storeImage(UploadedFile $file): string
    {
        // Générer un nom unique pour l'image
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

        // Stocker l'image dans le disk 'products'
        $file->storeAs('', $filename, 'images');

        return $filename;
    }

    /**
     * Supprimer une image
     */
    public function deleteImage(string $imagePath): bool
    {
        return Storage::disk('images')->delete($imagePath);
    }

    public function getProductsByCategory(string $categoryId)
    {
        return Product::where('category_id', $categoryId)->get();
    }

    public function updateStock(string $productId, int $quantity)
    {
        $product = Product::findOrFail($productId);
        $product->decrement('stock_quantity', $quantity);
        return $product;
    }
}
