<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddPackToCartRequest;
use App\Http\Requests\AddProductToCartRequest;
use App\Http\Requests\CartToOrderRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
        $this->middleware('auth:api');
    }

    /**
     * Afficher le panier de l'utilisateur connecté
     */
    public function index()
    {
        try {
            $cart = $this->cartService->getUserCart();
            return response()->json($cart, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Ajouter un produit au panier
     */
    public function addProduct(AddProductToCartRequest $request)
    {
        try {
            $cart = $this->cartService->addProduct($request->validated());
            return response()->json([
                'message' => 'Produit ajouté au panier avec succès',
                'cart' => $cart
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Ajouter un pack au panier
     */
    public function addPack(AddPackToCartRequest $request)
    {
        try {
            $cart = $this->cartService->addPack($request->validated());
            return response()->json([
                'message' => 'Pack ajouté au panier avec succès',
                'cart' => $cart
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Mettre à jour la quantité d'un item du panier
     */
    public function updateItem(UpdateCartItemRequest $request, string $itemId)
    {
        try {
            $cart = $this->cartService->updateItemQuantity($itemId, $request->quantity);
            return response()->json([
                'message' => 'Quantité mise à jour avec succès',
                'cart' => $cart
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Supprimer un item du panier
     */
    public function removeItem(string $itemId)
    {
        try {
            $cart = $this->cartService->removeItem($itemId);
            return response()->json([
                'message' => 'Article supprimé du panier avec succès',
                'cart' => $cart
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Vider complètement le panier
     */
    public function clear()
    {
        try {
            $cart = $this->cartService->clearCart();
            return response()->json([
                'message' => 'Panier vidé avec succès',
                'cart' => $cart
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Convertir le panier en commande
     */
    public function convertToOrder(CartToOrderRequest $request)
    {
        try {
            $order = $this->cartService->convertToOrder($request->validated());
            return response()->json([
                'message' => 'Commande créée avec succès',
                'order' => $order
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Obtenir le nombre d'articles dans le panier (pour badge)
     */
    public function count()
    {
        try {
            $cart = $this->cartService->getUserCart();
            return response()->json([
                'count' => $cart['total_items']
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
