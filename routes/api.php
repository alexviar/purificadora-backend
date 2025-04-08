<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController,
    UserController,
    PlantController,
    AlertController,
    NotificationController,
    ServiceRequestController,
    SupplyController,
    SupplyPurchaseController,
    TrainingVideoController,
    PaymentController,
    CartController,
    ServiceController,
    MaintenanceController
};
use Spatie\Permission\Middleware\RoleMiddleware;

// Rutas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::get('/test', fn() => response()->json(['message' => 'API is working']));

// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', fn(Request $request) => $request->user()->load('roles'));

    // Admin & Superadmin
    Route::middleware([RoleMiddleware::class . ':admin|superadmin'])->group(function () {
        Route::apiResource('users', UserController::class);
        Route::post('users/{user}/assign-role', [UserController::class, 'assignRole']);
        Route::apiResource('supplies', SupplyController::class);

        Route::post('training-videos', [TrainingVideoController::class, 'store']);
        Route::put('training-videos/{training_video}', [TrainingVideoController::class, 'update']);
        Route::delete('training-videos/{training_video}', [TrainingVideoController::class, 'destroy']);

        Route::get('payment-config', [PaymentController::class, 'index']);
        Route::put('payment-config', [PaymentController::class, 'updateConfiguration']);

        // Plantas: gestión completa + asignación manual
        Route::apiResource('plants', PlantController::class);
        Route::post('plants/{plant}/assign', [PlantController::class, 'assignToUser']);
        Route::post('plants/{plant}/unassign', [PlantController::class, 'unassignUser']);
    });

    // Servicios para admin, superadmin, técnico
    Route::middleware([RoleMiddleware::class . ':admin|superadmin|tecnico'])->group(function () {
        Route::apiResource('services', ServiceController::class);
    });

    // Visualización de Training Videos para todos los autenticados
    Route::get('training-videos', [TrainingVideoController::class, 'index']);
    Route::get('training-videos/{training_video}', [TrainingVideoController::class, 'show']);

    // Técnicos
    Route::middleware([RoleMiddleware::class . ':tecnico|admin|superadmin'])->group(function () {
        Route::apiResource('service-requests', ServiceRequestController::class);
        Route::apiResource('supply-purchases', SupplyPurchaseController::class);
    });

    // Clientes (solo ver sus plantas)
    Route::middleware([RoleMiddleware::class . ':cliente'])->group(function () {
        Route::get('my-plants', [PlantController::class, 'myPlants']);
    });

    // Carrito (cliente, admin, superadmin)
    Route::middleware([RoleMiddleware::class . ':cliente|admin|superadmin'])->group(function () {
        Route::get('cart', [CartController::class, 'index']);
        Route::post('cart', [CartController::class, 'store']);
        Route::put('cart/{item}', [CartController::class, 'update']);
        Route::delete('cart/{item}', [CartController::class, 'destroy']);
        Route::post('cart/checkout', [CartController::class, 'checkout']);
    });

    // Común a todos los usuarios autenticados
    Route::apiResource('alerts', AlertController::class);
    Route::apiResource('notifications', NotificationController::class);
    Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
});
