<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderFormRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Services\OrderService;
use Illuminate\Http\Request;
use App\Mail\OrderConfirmationMail;
use Illuminate\Support\Facades\Mail;
use App\Models\Order;
use App\Models\User;

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

    // Assigner un livreur à une commande
    public function assignLivreur(Request $request, string $id)
    {
        $request->validate([
            'livreur_id' => 'required|exists:users,id'
        ]);

        $order = Order::findOrFail($id);

        // Vérifier que l'utilisateur est bien un livreur
        $livreur = User::findOrFail($request->livreur_id);
        if ($livreur->role !== 'livreur') {
            return response()->json(['error' => 'Cet utilisateur n\'est pas un livreur'], 400);
        }

        $order->livreur_id = $request->livreur_id;
        $order->save();

        // Charger la relation livreur
        $order->load('livreur', 'user', 'orderItems.product');

        return response()->json($order, 200);
    }

    // Récupérer les commandes d'un livreur
    public function getLivreurOrders(string $livreurId)
    {
        $orders = Order::with(['user', 'orderItems.product', 'livreur'])
            ->where('livreur_id', $livreurId)
            ->whereIn('status', ['ready', 'delivering', 'delivered'])
            ->latest()
            ->get();

        return response()->json($orders, 200);
    }
    /**
     * Marque le paiement comme reçu (pour les livreurs)
     */
    public function markPaymentReceived(string $id)
    {
        $order = Order::findOrFail($id);

        // ✅ Vérifier que c'est bien un livreur
        $user = auth()->user();
        if (!$user || !$user->isLivreur()) {
            return response()->json(['error' => 'Seuls les livreurs peuvent marquer le paiement comme reçu'], 403);
        }

        // ✅ Vérifier que la commande est en livraison
        if ($order->status !== 'delivering') {
            return response()->json(['error' => 'La commande doit être en cours de livraison'], 400);
        }

        // ✅ Marquer uniquement le paiement comme reçu (PAS le statut de la commande)
        $order->update([
            'payment_status' => 'paid'
        ]);

        // ✅ Recharger avec les relations
        $order->load(['user', 'livreur', 'orderItems.product', 'orderItems.pack']);

        return response()->json($order, 200);
    }

}
