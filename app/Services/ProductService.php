<?php

namespace App\Services;

use App\Http\Requests\ProductFormRequest;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class ProductService
{
    /**
     * Formater un produit - L'URL Cloudinary est déjà complète dans la BDD
     */
    private function formatProduct($product)
    {
        $productArray = $product->toArray();

        // L'URL Cloudinary est déjà complète, on l'assigne à imageUrl
        $productArray['imageUrl'] = $product->image;

        return $productArray;
    }

    /**
     * Formater une collection de produits
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

        // Gérer l'upload de l'image vers Cloudinary
        if ($request->hasFile('image')) {
            $data['image'] = $this->uploadToCloudinary($request->file('image'));
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
            // Supprimer l'ancienne image de Cloudinary
            if ($product->image) {
                $this->deleteFromCloudinary($product->image);
            }

            // Uploader la nouvelle image
            $request['image'] = $this->uploadToCloudinary($request['image']);
        } elseif (isset($request['image'])) {
            if (is_null($request['image']) && $product->image) {
                $this->deleteFromCloudinary($product->image);
                $request['image'] = null;
            } else {
                unset($request['image']);
            }
        } else {
            unset($request['image']);
        }

        $product->update($request);
        $updatedProduct = $product->fresh(['category', 'promotions']);
        return $this->formatProduct($updatedProduct);
    }

    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);

        // Supprimer l'image de Cloudinary
        if ($product->image) {
            $this->deleteFromCloudinary($product->image);
        }

        $product->delete();
    }

    /**
     * Upload une image vers Cloudinary et retourner l'URL complète
     */
    private function uploadToCloudinary(UploadedFile $file): string
    {
        $uploadedFileUrl = Cloudinary::upload($file->getRealPath(), [
            'folder' => 'products',
            'resource_type' => 'image'
        ])->getSecurePath();

        return $uploadedFileUrl;
    }

    /**
     * Supprimer une image de Cloudinary
     */
    private function deleteFromCloudinary(string $imageUrl): void
    {
        if (strpos($imageUrl, 'cloudinary.com') !== false) {
            $parts = explode('/', $imageUrl);
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
