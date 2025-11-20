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
*/

// Gérer toutes les requêtes OPTIONS pour CORS
Route::options('{any}', function (Request $request) {
    return response('', 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept');
})->where('any', '.*');

// Routes d'authentification (sans middleware auth)
Route::group(['prefix' => 'auth'], function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('me', [AuthController::class, 'me']);
});

// Routes protégées
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
    Route::post('orders/{id}/send-delivery-notification', [OrderController::class, 'sendDeliveryNotification']);
    Route::post('orders/{id}/send-confirmation-email', [OrderController::class, 'sendConfirmationEmail']);


    // ✅ Routes pour l'assignation de livreurs
    Route::post('orders/{id}/assign-livreur', [OrderController::class, 'assignLivreur']);
    Route::get('orders/livreur/{livreurId}', [OrderController::class, 'getLivreurOrders']);

    // Invoices
    Route::apiResource('invoices', InvoiceController::class)->only(['index', 'store', 'show']);
    Route::post('invoices/{id}/send-email', [InvoiceController::class, 'sendEmail']);
    Route::get('my-invoices', [InvoiceController::class, 'myInvoices']);

    Route::get('/invoices/{id}/download', [InvoiceController::class, 'download']);
    Route::get('/invoices/{id}/preview', [InvoiceController::class, 'preview']);

    // Route admin pour générer manuellement le PDF
    Route::post('/invoices/{id}/generate-pdf', [InvoiceController::class, 'generatePDF']);

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
    Route::get('livreurs', [UserController::class, 'livreurs']);

    Route::group(['prefix' => 'cart'], function () {
        Route::get('/', [CartController::class, 'index']);
        Route::get('/count', [CartController::class, 'count']);
        Route::post('/add-product', [CartController::class, 'addProduct']);
        Route::post('/add-pack', [CartController::class, 'addPack']);
        Route::put('/items/{itemId}', [CartController::class, 'updateItem']);
        Route::delete('/items/{itemId}', [CartController::class, 'removeItem']);
        Route::delete('/clear', [CartController::class, 'clear']);
        Route::post('/checkout', [CartController::class, 'convertToOrder']);
    });

    // Routes principales
    Route::apiResource('chat-messages', ChatMessageController::class)->only(['index', 'store']);
    Route::patch('chat-messages/{id}/read', [ChatMessageController::class, 'markAsRead']);

    // Routes pour les clients
    Route::get('my-messages', [ChatMessageController::class, 'myMessages']);
    Route::get('unread-messages', [ChatMessageController::class, 'unread']);

    // Routes pour le support
    Route::get('chat-conversations', [ChatMessageController::class, 'getAllConversations']);
    Route::get('chat-user-messages/{userId}', [ChatMessageController::class, 'getUserMessages']);
    Route::patch('chat-messages/read-user/{userId}', [ChatMessageController::class, 'markUserMessagesAsRead']);
    Route::patch('chat-messages/mark-all-read', [ChatMessageController::class, 'markAllMyMessagesAsRead']);



    Route::get('chat-conversations', [ChatMessageController::class, 'getAllConversations']);
    Route::get('chat-user-messages/{userId}', [ChatMessageController::class, 'getUserMessages']);

    Route::post('orders/{id}/mark-payment-received', [OrderController::class, 'markPaymentReceived']);

});
