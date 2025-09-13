<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderFormRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Services\OrderService;
use Illuminate\Http\Request;
use App\Mail\OrderConfirmationMail;
use Illuminate\Support\Facades\Mail;

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
    /**
     * Envoie un email de notification de début de livraison
     */
    public function sendDeliveryNotification(string $id)
    {
        try {
            // Récupérer la commande via le service
            $order = $this->orderService->show($id);

            // Vérifier que la commande est bien en livraison
            if ($order->status !== 'delivering') {
                return response()->json([
                    'error' => 'La commande doit être en statut "delivering" pour envoyer cette notification'
                ], 400);
            }

            // Envoyer l'email
            Mail::to($order->user->email)->send(new OrderConfirmationMail($order));

            return response()->json([
                'message' => 'Email de notification de livraison envoyé avec succès',
                'order_number' => $order->order_number,
                'recipient' => $order->user->email
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de l\'envoi de l\'email',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Envoie un email de confirmation de commande
     */
    public function sendConfirmationEmail(string $id)
    {
        try {
            // Récupérer la commande via le service
            $order = $this->orderService->show($id);

            // Envoyer l'email de confirmation
            Mail::to($order->user->email)->send(new OrderConfirmationMail($order));

            return response()->json([
                'message' => 'Email de confirmation envoyé avec succès',
                'order_number' => $order->order_number,
                'recipient' => $order->user->email
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de l\'envoi de l\'email de confirmation',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
