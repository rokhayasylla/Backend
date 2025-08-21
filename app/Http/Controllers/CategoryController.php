<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryFormRequest;
use App\Services\CategoryService;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    protected $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
        $this->middleware('auth:api');
    }

    public function index()
    {
        $categories = $this->categoryService->index();
        return response()->json($categories, 200);
    }

    public function store(CategoryFormRequest $request)
    {
        $category = $this->categoryService->store($request);
        return response()->json($category, 201);
    }

    public function show(string $id)
    {
        $category = $this->categoryService->show($id);
        return response()->json($category, 200);
    }

    public function update(CategoryFormRequest $request, string $id)
    {
        $category = $this->categoryService->update($request->validated(), $id);
        return response()->json($category, 200);
    }

    public function destroy(string $id)
    {
        $this->categoryService->destroy($id);
        return response('', 204);
    }

}
