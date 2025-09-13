<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Pack;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class CartService
{
    protected $orderService;
    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }
    /**
     * Obtenir ou créer le panier de l'utilisateur connecté
     */
    public function getUserCart()
    {
        $cart = Cart::with([
            'cartItems.product' => function($query) {
                $query->select('id', 'name', 'description', 'price', 'stock_quantity', 'image', 'allergens', 'category_id');
            },
            'cartItems.product.category',
            'cartItems.product.promotions' => function($query) {
                $query->where('is_active', true)
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now());
            },
            'cartItems.pack' => function($query) {
                $query->select('id', 'name', 'description', 'price', 'image_path', 'is_active');
            },
            'cartItems.pack.products' => function($query) {
                $query->select('products.id', 'products.name', 'products.image', 'products.price');
            }
        ])
            ->where('user_id', auth()->id())
            ->first();

        if (!$cart) {
            $cart = Cart::create(['user_id' => auth()->id()]);
            // Recharger avec les relations
            $cart = Cart::with([
                'cartItems.product' => function($query) {
                    $query->select('id', 'name', 'description', 'price', 'stock_quantity', 'image', 'allergens', 'category_id');
                },
                'cartItems.product.category',
                'cartItems.product.promotions' => function($query) {
                    $query->where('is_active', true)
                        ->where('start_date', '<=', now())
                        ->where('end_date', '>=', now());
                },
                'cartItems.pack' => function($query) {
                    $query->select('id', 'name', 'description', 'price', 'image_path', 'is_active');
                },
                'cartItems.pack.products' => function($query) {
                    $query->select('products.id', 'products.name', 'products.image', 'products.price');
                }
            ])->find($cart->id);
        }

        return $this->formatCartResponse($cart);
    }

    /**
     * Ajouter un produit au panier
     */
    public function addProduct(array $data)
    {
        $productId = $data['product_id'];
        $quantity = $data['quantity'];

        // Vérifier si le produit existe et est en stock
        $product = Product::findOrFail($productId);

        if ($product->stock_quantity < $quantity) {
            throw new \Exception("Stock insuffisant pour le produit: {$product->name}. Stock disponible: {$product->stock_quantity}");
        }

        // Obtenir ou créer le panier
        $cart = Cart::firstOrCreate(['user_id' => auth()->id()]);

        // Vérifier si le produit est déjà dans le panier
        $existingItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $productId)
            ->where('item_type', 'product')
            ->first();

        if ($existingItem) {
            // Vérifier le stock total nécessaire
            $totalQuantity = $existingItem->quantity + $quantity;
            if ($product->stock_quantity < $totalQuantity) {
                throw new \Exception("Stock insuffisant. Quantité déjà dans le panier: {$existingItem->quantity}, Stock disponible: {$product->stock_quantity}");
            }

            $existingItem->increment('quantity', $quantity);
        } else {
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $productId,
                'pack_id' => null,
                'item_type' => 'product',
                'quantity' => $quantity
            ]);
        }

        return $this->getUserCart();
    }

    /**
     * Ajouter un pack au panier
     */
    /**
     * Ajouter un pack au panier
     */
    public function addPack(array $data)
    {
        // Validation manuelle de sécurité (au cas où le contrôleur ne le fait pas)
        if (!isset($data['pack_id'], $data['quantity']) || $data['quantity'] < 1) {
            throw new \InvalidArgumentException("Paramètres invalides : pack_id et quantity sont requis.");
        }

        $packId = $data['pack_id'];
        $quantity = (int) $data['quantity'];

        // Vérifier si le pack existe et est actif
        $pack = Pack::where('is_active', true)
            ->with(['products' => function ($query) {
                $query->select('products.id', 'products.name', 'products.stock_quantity', 'products.price')
                    ->withPivot('quantity');
            }])
            ->findOrFail($packId);

        // Vérifier le stock des produits du pack
        foreach ($pack->products as $product) {
            $requiredQuantity = $product->pivot->quantity * $quantity;
            if ($product->stock_quantity < $requiredQuantity) {
                throw new \RuntimeException(
                    "Stock insuffisant pour le produit '{$product->name}' dans le pack '{$pack->name}'. " .
                    "Requis: {$requiredQuantity}, Disponible: {$product->stock_quantity}"
                );
            }
        }

        // Obtenir ou créer le panier
        $cart = Cart::firstOrCreate(['user_id' => auth()->id()]);

        // Vérifier si le pack est déjà dans le panier
        $existingItem = CartItem::where('cart_id', $cart->id)
            ->where('pack_id', $packId)
            ->where('item_type', 'pack')
            ->first();

        if ($existingItem) {
            // Vérifier à nouveau le stock avec la quantité totale
            $totalQuantity = $existingItem->quantity + $quantity;
            foreach ($pack->products as $product) {
                $requiredQuantity = $product->pivot->quantity * $totalQuantity;
                if ($product->stock_quantity < $requiredQuantity) {
                    throw new \RuntimeException(
                        "Impossible d'ajouter plus de packs. " .
                        "Stock insuffisant pour '{$product->name}' (déjà {$existingItem->quantity} packs dans le panier)."
                    );
                }
            }

            $existingItem->increment('quantity', $quantity);
        } else {
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => null,
                'pack_id' => $packId,
                'item_type' => 'pack',
                'quantity' => $quantity
            ]);
        }

        return $this->getUserCart();
    }


    /**
     * Mettre à jour la quantité d'un item du panier
     */
    public function updateItemQuantity(string $itemId, int $quantity)
    {
        if ($quantity <= 0) {
            return $this->removeItem($itemId);
        }

        $cartItem = CartItem::whereHas('cart', function ($query) {
            $query->where('user_id', auth()->id());
        })->findOrFail($itemId);

        // Vérifier le stock selon le type d'item
        if ($cartItem->item_type === 'product') {
            $product = $cartItem->product;
            if ($product->stock_quantity < $quantity) {
                throw new \Exception("Stock insuffisant pour le produit '{$product->name}'. Stock disponible: {$product->stock_quantity}");
            }
        } else {
            // Pour les packs, vérifier le stock de chaque produit
            $pack = $cartItem->pack;
            foreach ($pack->products as $product) {
                $requiredQuantity = $product->pivot->quantity * $quantity;
                if ($product->stock_quantity < $requiredQuantity) {
                    throw new \Exception("Stock insuffisant pour le produit '{$product->name}' dans le pack '{$pack->name}'. Stock disponible: {$product->stock_quantity}, Requis: {$requiredQuantity}");
                }
            }
        }

        $cartItem->update(['quantity' => $quantity]);

        return $this->getUserCart();
    }

    /**
     * Supprimer un item du panier
     */
    public function removeItem(string $itemId)
    {
        $cartItem = CartItem::whereHas('cart', function ($query) {
            $query->where('user_id', auth()->id());
        })->findOrFail($itemId);

        $cartItem->delete();

        return $this->getUserCart();
    }

    /**
     * Vider complètement le panier
     */
    public function clearCart()
    {
        $cart = Cart::where('user_id', auth()->id())->first();

        if ($cart) {
            $cart->cartItems()->delete();
        }

        return $this->getUserCart();
    }

    /**
     * Convertir le panier en commande
     */
    public function convertToOrder(array $orderData)
    {
        return DB::transaction(function () use ($orderData) {
            $cart = Cart::with(['cartItems.product.promotions', 'cartItems.pack.products'])
                ->where('user_id', auth()->id())
                ->first();

            if (!$cart || $cart->cartItems->isEmpty()) {
                throw new \Exception("Le panier est vide");
            }

            // Préparer les items pour la commande
            $orderItems = [];

            foreach ($cart->cartItems as $cartItem) {
                if ($cartItem->item_type === 'product') {
                    // Produit individuel
                    $product = $cartItem->product;

                    // Vérifier le stock une dernière fois
                    if ($product->stock_quantity < $cartItem->quantity) {
                        throw new \Exception("Stock insuffisant pour le produit: {$product->name}");
                    }

                    $unitPrice = $cartItem->unit_price; // Prix avec promotion appliquée
                    $totalPrice = $unitPrice * $cartItem->quantity;

                    $orderItems[] = [
                        'product_id' => $product->id,
                        'pack_id' => null,
                        'item_type' => 'product',
                        'quantity' => $cartItem->quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice
                    ];
                } else {
                    // Pack complet
                    $pack = $cartItem->pack;

                    // Vérifier le stock des produits du pack
                    foreach ($pack->products as $product) {
                        $requiredQuantity = $product->pivot->quantity * $cartItem->quantity;
                        if ($product->stock_quantity < $requiredQuantity) {
                            throw new \Exception("Stock insuffisant pour le produit '{$product->name}' dans le pack '{$pack->name}'. Stock disponible: {$product->stock_quantity}, Requis: {$requiredQuantity}");
                        }
                    }

                    // Créer un order item pour le pack lui-même
                    $orderItems[] = [
                        'product_id' => null,
                        'pack_id' => $pack->id,
                        'item_type' => 'pack',
                        'quantity' => $cartItem->quantity,
                        'unit_price' => $pack->price,
                        'total_price' => $pack->price * $cartItem->quantity
                    ];
                }
            }

            // Utiliser OrderService pour créer la commande
            $order = $this->orderService->createFromCart($orderData, $orderItems);

            // Vider le panier après création de la commande réussie
            $cart->cartItems()->delete();

            return $order;
        });
    }

    /**
     * Formater la réponse du panier
     */
    private function formatCartResponse(Cart $cart)
    {
        $formattedItems = $cart->cartItems->map(function ($item) {
            $baseData = [
                'id' => $item->id,
                'cart_id' => $item->cart_id,
                'product_id' => $item->product_id,
                'pack_id' => $item->pack_id,
                'item_type' => $item->item_type,
                'quantity' => $item->quantity,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
            ];

            if ($item->item_type === 'product' && $item->product) {
                $baseData['product'] = [
                    'id' => $item->product->id,
                    'name' => $item->product->name,
                    'description' => $item->product->description,
                    'price' => $item->product->price,
                    'stock_quantity' => $item->product->stock_quantity,
                    'image' => $item->product->image, // Utiliser 'image' au lieu de 'image_url'
                    'allergens' => $item->product->allergens,
                    'category_id' => $item->product->category_id,
                ];

                // Ajouter la catégorie si chargée
                if ($item->product->category) {
                    $baseData['product']['category'] = $item->product->category;
                }

                // Calculer les prix
                $unitPrice = $item->product->price;

                // Vérifier les promotions si elles existent
                if ($item->product->promotions && $item->product->promotions->isNotEmpty()) {
                    $activePromotion = $item->product->promotions
                        ->where('is_active', true)
                        ->where('start_date', '<=', now())
                        ->where('end_date', '>=', now())
                        ->first();

                    if ($activePromotion) {
                        if ($activePromotion->discount_percentage) {
                            $unitPrice = $item->product->price * (1 - $activePromotion->discount_percentage / 100);
                        } elseif ($activePromotion->discount_amount) {
                            $unitPrice = max(0, $item->product->price - $activePromotion->discount_amount);
                        }

                        $baseData['promotion'] = [
                            'id' => $activePromotion->id,
                            'name' => $activePromotion->name,
                            'discount_percentage' => $activePromotion->discount_percentage,
                            'discount_amount' => $activePromotion->discount_amount,
                            'original_price' => $item->product->price
                        ];
                        $baseData['is_on_promotion'] = true;
                    }
                }

                $baseData['unit_price'] = $unitPrice;
                $baseData['total_price'] = $unitPrice * $item->quantity;

            } elseif ($item->item_type === 'pack' && $item->pack) {
                $baseData['pack'] = [
                    'id' => $item->pack->id,
                    'name' => $item->pack->name,
                    'description' => $item->pack->description,
                    'price' => $item->pack->price,
                    'image_path' => $item->pack->image_path, // Utiliser 'image_path' au lieu de 'image_url'
                    'is_active' => $item->pack->is_active,
                ];

                // Ajouter les produits du pack si chargés
                if ($item->pack->products && $item->pack->products->isNotEmpty()) {
                    $baseData['pack']['products'] = $item->pack->products->map(function ($product) {
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'image' => $product->image, // Utiliser 'image' au lieu de 'image_url'
                            'price' => $product->price,
                            'quantity_in_pack' => $product->pivot->quantity,
                        ];
                    });
                }

                // Calculer les prix pour le pack
                $baseData['unit_price'] = $item->pack->price;
                $baseData['total_price'] = $item->pack->price * $item->quantity;
                $baseData['is_on_promotion'] = false; // Les packs n'ont généralement pas de promotion
            }

            return $baseData;
        });

        // Calculer les totaux
        $totalItems = $formattedItems->sum('quantity');
        $totalAmount = $formattedItems->sum('total_price');

        return [
            'id' => $cart->id,
            'user_id' => $cart->user_id,
            'items' => $formattedItems->values()->all(),
            'total_items' => $totalItems,
            'total_amount' => $totalAmount,
            'created_at' => $cart->created_at,
            'updated_at' => $cart->updated_at
        ];
    }



}
