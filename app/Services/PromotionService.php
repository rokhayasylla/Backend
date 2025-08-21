<?php

namespace App\Services;

use App\Http\Requests\PromotionFormRequest;
use App\Models\Promotion;

class PromotionService
{
    public function index()
    {
        return Promotion::with('products')->get();
    }

    public function store(PromotionFormRequest $request)
    {
        $data = $request->validated();
        $productIds = $data['product_ids'];
        unset($data['product_ids']);

        $promotion = Promotion::create($data);
        $promotion->products()->attach($productIds);

        return $promotion->load('products');
    }

    public function show(string $id)
    {
        return Promotion::with('products')->findOrFail($id);
    }

    public function update(array $request, string $id)
    {
        $promotion = Promotion::findOrFail($id);

        if (isset($request['product_ids'])) {
            $productIds = $request['product_ids'];
            unset($request['product_ids']);
            $promotion->products()->sync($productIds);
        }

        $promotion->update($request);
        return $promotion->load('products');
    }

    public function destroy(string $id)
    {
        $promotion = Promotion::findOrFail($id);
        $promotion->products()->detach();
        $promotion->delete();
    }

    public function getActivePromotions()
    {
        return Promotion::active()->with('products')->get();
    }
}
