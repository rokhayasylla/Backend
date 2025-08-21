<?php

namespace App\Http\Controllers;

use App\Http\Requests\PromotionFormRequest;
use App\Services\PromotionService;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    protected $promotionService;

    public function __construct(PromotionService $promotionService)
    {
        $this->promotionService = $promotionService;
    }

    public function index()
    {
        $promotions = $this->promotionService->index();
        return response()->json($promotions, 200);
    }

    public function store(PromotionFormRequest $request)
    {
        $promotion = $this->promotionService->store($request);
        return response()->json($promotion, 201);
    }

    public function show(string $id)
    {
        $promotion = $this->promotionService->show($id);
        return response()->json($promotion, 200);
    }

    public function update(PromotionFormRequest $request, string $id)
    {
        $promotion = $this->promotionService->update($request->validated(), $id);
        return response()->json($promotion, 200);
    }

    public function destroy(string $id)
    {
        $this->promotionService->destroy($id);
        return response('', 204);
    }

    public function active()
    {
        $promotions = $this->promotionService->getActivePromotions();
        return response()->json($promotions, 200);
    }
}
