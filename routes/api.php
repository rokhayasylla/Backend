<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ChatMessageController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PackController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::group(['prefix' => 'auth'], function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('me', [AuthController::class, 'me']);
});


Route::middleware('auth:api')->group(function () {

    // Categories
    Route::apiResource('categories', CategoryController::class);

    // Products
    Route::apiResource('products', ProductController::class);
    Route::get('products/category/{categoryId}', [ProductController::class, 'byCategory']);

    // Promotions
    Route::apiResource('promotions', PromotionController::class);
    Route::get('promotions-active', [PromotionController::class, 'active']);

    // Orders
    Route::apiResource('orders', OrderController::class)->except(['update', 'destroy']);
    Route::patch('orders/{id}/status', [OrderController::class, 'updateStatus']);
    Route::get('my-orders', [OrderController::class, 'myOrders']);
    Route::get('orders/status/{status}', [OrderController::class, 'byStatus']);

    // Invoices
    Route::apiResource('invoices', InvoiceController::class)->only(['index', 'store', 'show']);
    Route::post('invoices/{id}/send-email', [InvoiceController::class, 'sendEmail']);
    Route::get('my-invoices', [InvoiceController::class, 'myInvoices']);

    // Chat Messages
    Route::apiResource('chat-messages', ChatMessageController::class)->only(['index', 'store']);
    Route::get('my-messages', [ChatMessageController::class, 'myMessages']);
    Route::patch('chat-messages/{id}/read', [ChatMessageController::class, 'markAsRead']);
    Route::get('unread-messages', [ChatMessageController::class, 'unread']);

    // Packs
    Route::apiResource('packs', PackController::class);
    Route::get('packs-active', [PackController::class, 'active']);

    // Users (Admin only)
    Route::apiResource('users', UserController::class);
    Route::get('employees', [UserController::class, 'employees']);
    Route::get('clients', [UserController::class, 'clients']);

    Route::group(['prefix' => 'cart'], function () {
        Route::get('/', [CartController::class, 'index']);                    // Voir le panier
        Route::get('/count', [CartController::class, 'count']);               // Nombre d'articles
        Route::post('/add-product', [CartController::class, 'addProduct']);   // Ajouter produit
        Route::post('/add-pack', [CartController::class, 'addPack']);         // Ajouter pack
        Route::put('/items/{itemId}', [CartController::class, 'updateItem']); // Modifier quantité
        Route::delete('/items/{itemId}', [CartController::class, 'removeItem']); // Supprimer item
        Route::delete('/clear', [CartController::class, 'clear']);            // Vider panier
        Route::post('/checkout', [CartController::class, 'convertToOrder']);  // Valider commande
    });
});
