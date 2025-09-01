<?php

namespace App\Services;

use App\Http\Requests\OrderFormRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use App\Models\Pack;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class OrderService
{
    protected $notificationService;
    protected $invoiceService;

    public function __construct(NotificationService $notificationService, InvoiceService $invoiceService)
    {
        $this->notificationService = $notificationService;
        $this->invoiceService = $invoiceService;
    }
    public function index()
    {
        return Order::with(['user', 'orderItems.product', 'orderItems.pack'])->latest()->get();
    }

    public function store($request)
    {
        return DB::transaction(function () use ($request) {
            $data = is_array($request) ? $request : $request->validated();
            $items = $data['items'];
            unset($data['items']);

            $totalAmount = 0;
            $orderItems = [];

            // Calculate total and prepare order items
            foreach ($items as $item) {
                if (isset($item['product_id']) && $item['product_id']) {
                    // Produit individuel
                    $product = Product::findOrFail($item['product_id']);

                    if ($product->stock_quantity < $item['quantity']) {
                        throw new \Exception("Stock insuffisant pour le produit: {$product->name}");
                    }

                    $unitPrice = $item['unit_price'] ?? $product->price;
                    $totalPrice = $unitPrice * $item['quantity'];
                    $totalAmount += $totalPrice;

                    $orderItems[] = [
                        'product_id' => $product->id,
                        'pack_id' => null,
                        'item_type' => 'product',
                        'quantity' => $item['quantity'],
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice
                    ];
                } elseif (isset($item['pack_id']) && $item['pack_id']) {
                    // Pack
                    $pack = Pack::with('products')->findOrFail($item['pack_id']);

                    // Vérifier le stock des produits du pack
                    foreach ($pack->products as $product) {
                        $requiredQuantity = $product->pivot->quantity * $item['quantity'];
                        if ($product->stock_quantity < $requiredQuantity) {
                            throw new \Exception("Stock insuffisant pour le produit '{$product->name}' dans le pack '{$pack->name}'");
                        }
                    }

                    $unitPrice = $item['unit_price'] ?? $pack->price;
                    $totalPrice = $unitPrice * $item['quantity'];
                    $totalAmount += $totalPrice;

                    $orderItems[] = [
                        'product_id' => null,
                        'pack_id' => $pack->id,
                        'item_type' => 'pack',
                        'quantity' => $item['quantity'],
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice
                    ];
                }
            }

            // Create order
            $order = Order::create([
                'user_id' => auth()->id(),
                'total_amount' => $totalAmount,
                'delivery_address' => $data['delivery_address'],
                'payment_method' => $data['payment_method'],
                'notes' => $data['notes'] ?? null
            ]);

            // Create order items and update stock
            foreach ($orderItems as $orderItem) {
                $order->orderItems()->create($orderItem);

                // Update stock based on item type
                if ($orderItem['item_type'] === 'product') {
                    // Produit individuel - décrémenter le stock directement
                    Product::where('id', $orderItem['product_id'])
                        ->decrement('stock_quantity', $orderItem['quantity']);
                } else {
                    // Pack - décrémenter le stock de chaque produit du pack
                    $pack = Pack::with('products')->find($orderItem['pack_id']);
                    foreach ($pack->products as $product) {
                        $quantityToDeduct = $product->pivot->quantity * $orderItem['quantity'];
                        Product::where('id', $product->id)
                            ->decrement('stock_quantity', $quantityToDeduct);
                    }
                }
            }

            $order->load(['orderItems.product', 'orderItems.pack', 'user']);
            $this->notificationService->sendOrderConfirmation($order);

            return $order;
        });
    }

    public function show(string $id)
    {
        return Order::with(['user', 'orderItems.product', 'orderItems.pack', 'invoice'])->findOrFail($id);
    }

//    public function updateStatus(UpdateOrderStatusRequest $request, string $id)
//    {
//        $order = Order::findOrFail($id);
//        $order->update($request->validated());
//
//        if ($request->status === 'delivered') {
//            $order->update(['delivered_at' => now()]);
//            // Créer et envoyer la facture automatiquement
//            $this->createAndSendInvoice($order);
//        }
//
//        return $order->load(['user', 'orderItems.product', 'orderItems.pack']);
//    }

    public function updateStatus(UpdateOrderStatusRequest $request, string $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $order = Order::with(['user', 'orderItems.product', 'orderItems.pack'])->findOrFail($id);
            $oldStatus = $order->status;
            $newStatus = $request->status;

            $order->update($request->validated());

            if ($newStatus === 'delivered') {
                $order->update(['delivered_at' => now()]);

                // Créer et envoyer la facture automatiquement
                try {
                    $invoice = $this->invoiceService->createInvoiceForOrder($order);
                    $invoiceSent = $this->notificationService->sendInvoice($invoice);

                    if ($invoiceSent) {
                        \Log::info('✅ Facture envoyée avec succès pour commande #' . $order->order_number);
                    } else {
                        \Log::warning('⚠️ Échec envoi facture pour commande #' . $order->order_number);
                    }
                } catch (\Exception $e) {
                    \Log::error('❌ Erreur lors de la création/envoi de facture: ' . $e->getMessage());
                }
            }

            // Envoyer notification de changement de statut si nécessaire
            $this->notificationService->sendOrderStatusUpdate($order, $oldStatus, $newStatus);

            return $order->load(['user', 'orderItems.product', 'orderItems.pack']);
        });
    }

    public function getUserOrders(string $userId)
    {
        return Order::where('user_id', $userId)
            ->with(['orderItems.product', 'orderItems.pack'])
            ->latest()
            ->get();
    }

    public function getOrdersByStatus(string $status)
    {
        return Order::where('status', $status)
            ->with(['user', 'orderItems.product', 'orderItems.pack'])
            ->latest()
            ->get();
    }

    /**
     * Créer une commande depuis le panier
     */
    public function createFromCart(array $orderData, array $orderItems)
    {
        return DB::transaction(function () use ($orderData, $orderItems) {
            $totalAmount = 0;

            // Calculate total amount
            foreach ($orderItems as $item) {
                $totalAmount += $item['total_price'];
            }

            // Create order
            $order = Order::create([
                'user_id' => auth()->id(),
                'total_amount' => $totalAmount,
                'delivery_address' => $orderData['delivery_address'],
                'payment_method' => $orderData['payment_method'],
                'notes' => $orderData['notes'] ?? null
            ]);

            // Create order items and update stock
            foreach ($orderItems as $orderItem) {
                $order->orderItems()->create($orderItem);

                // Update stock based on item type
                if ($orderItem['item_type'] === 'product') {
                    // Produit individuel - décrémenter le stock directement
                    Product::where('id', $orderItem['product_id'])
                        ->decrement('stock_quantity', $orderItem['quantity']);
                } else {
                    // Pack - décrémenter le stock de chaque produit du pack
                    $pack = Pack::with('products')->find($orderItem['pack_id']);
                    foreach ($pack->products as $product) {
                        $quantityToDeduct = $product->pivot->quantity * $orderItem['quantity'];
                        Product::where('id', $product->id)
                            ->decrement('stock_quantity', $quantityToDeduct);
                    }
                }
            }

            $order->load(['orderItems.product', 'orderItems.pack', 'user']);
            // Envoyer l'email de confirmation de commande
            $this->notificationService->sendOrderConfirmation($order);

            return $order;
        });
    }

    /**
     * Créer une facture et l'envoyer par email
     */
    private function createAndSendInvoice(Order $order)
    {
        try {
            // Utiliser le service Invoice pour créer la facture
            $invoiceService = new InvoiceService();
            $invoice = $invoiceService->createInvoiceForOrder($order);

            // Envoyer la facture par email
            $invoiceService->sendInvoiceByEmail($invoice);

        } catch (\Exception $e) {
            // Log l'erreur mais ne pas faire échouer la mise à jour du statut
            \Log::error('Erreur création/envoi facture pour commande ' . $order->id . ': ' . $e->getMessage());
        }
    }
}
