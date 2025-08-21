<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductFormRequest;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index()
    {
        $products = $this->productService->index();
        return response()->json($products, 200);
    }

    public function store(ProductFormRequest $request)
    {
        try {
            $product = $this->productService->store($request);
            return response()->json($product, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors de la création du produit: ' . $e->getMessage()], 400);
        }
    }

    public function show(string $id)
    {
        $product = $this->productService->show($id);
        return response()->json($product, 200);
    }

    public function update(ProductFormRequest $request, string $id)
    {
        try {
            // Préparer les données incluant le fichier image si présent
            $data = $request->validated();
            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image');
            }

            $product = $this->productService->update($data, $id);
            return response()->json($product, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors de la mise à jour: ' . $e->getMessage()], 400);
        }
    }

    public function destroy(string $id)
    {
        try {
            $this->productService->destroy($id);
            return response('', 204);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors de la suppression: ' . $e->getMessage()], 400);
        }
    }

    public function byCategory(string $categoryId)
    {
        $products = $this->productService->getProductsByCategory($categoryId);
        return response()->json($products, 200);
    }
}
