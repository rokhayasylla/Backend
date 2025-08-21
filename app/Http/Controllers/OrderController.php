<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderFormRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function index()
    {
        $orders = $this->orderService->index();
        return response()->json($orders, 200);
    }

    public function store(OrderFormRequest $request)
    {
        try {
            $order = $this->orderService->store($request);
            return response()->json($order, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function show(string $id)
    {
        $order = $this->orderService->show($id);
        return response()->json($order, 200);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, string $id)
    {
        $order = $this->orderService->updateStatus($request, $id);
        return response()->json($order, 200);
    }

    public function myOrders()
    {
        $orders = $this->orderService->getUserOrders(auth()->id());
        return response()->json($orders, 200);
    }

    public function byStatus(string $status)
    {
        $orders = $this->orderService->getOrdersByStatus($status);
        return response()->json($orders, 200);
    }
}
