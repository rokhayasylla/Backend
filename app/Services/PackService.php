<?php

namespace App\Services;

use App\Http\Requests\PackFormRequest;
use App\Models\Pack;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PackService
{
    public function index()
    {
        return Pack::with('products')->get();
    }

    public function store(array $data, $imageFile = null)
    {
        $products = $data['products'];
        unset($data['products']);

        // Gérer l'upload de l'image
        if ($imageFile && $imageFile instanceof UploadedFile) {
            $data['image_path'] = $this->storeImage($imageFile);
        }

        $pack = Pack::create($data);

        foreach ($products as $product) {
            $pack->products()->attach($product['product_id'], [
                'quantity' => $product['quantity']
            ]);
        }

        return $pack->load('products');
    }

    public function show(string $id)
    {
        return Pack::with('products')->findOrFail($id);
    }

    public function update(array $data, string $id)
    {
        $pack = Pack::findOrFail($id);

        // Gérer l'upload de la nouvelle image
        if (isset($data['image_path']) && $data['image_path'] instanceof UploadedFile) {
            // Supprimer l'ancienne image si elle existe
            if ($pack->image_path) {
                $this->deleteImage($pack->image_path);
            }

            // Stocker la nouvelle image
            $data['image_path'] = $this->storeImage($data['image_path']);
            unset($data['image_path']);
        } elseif (isset($data['image_path'])) {
            // Si 'image' est présent mais pas un fichier (ex: null pour supprimer)
            if (is_null($data['image_path']) && $pack->image_path) {
                $this->deleteImage($pack->image_path);
                $data['image_path'] = null;
            }
            unset($data['image_path']);
        }

        // Gérer les produits
        if (isset($data['products'])) {
            $products = $data['products'];
            unset($data['products']);

            $pack->products()->detach();
            foreach ($products as $product) {
                $pack->products()->attach($product['product_id'], [
                    'quantity' => $product['quantity']
                ]);
            }
        }

        $pack->update($data);
        return $pack->load('products');
    }

    public function destroy(string $id)
    {
        $pack = Pack::findOrFail($id);
        $pack->products()->detach();
        $pack->delete();
    }

    public function getActivePacks()
    {
        return Pack::active()->with('products')->get();
    }

    /**
     * Stocker l'image et retourner le chemin
     */
    private function storeImage(UploadedFile $file): string
    {
        // Générer un nom unique pour l'image
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

        // Stocker l'image dans le disk 'packs'
        $file->storeAs('', $filename, 'packs');

        return $filename;
    }

    /**
     * Supprimer une image
     */
    public function deleteImage(string $imagePath): bool
    {
        return Storage::disk('packs')->delete($imagePath);
    }
}
