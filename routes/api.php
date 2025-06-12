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
    MaintenanceController,
    UserTrainingVideoController
};
use Spatie\Permission\Middleware\RoleMiddleware;

// Rutas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']); // TODO: Remove this route
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::get('/test', fn() => response()->json(['message' => 'API is working']));

// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', fn(Request $request) => $request->user()->load('roles')); // TODO: Remove this route
    Route::get('/auth/user', fn(Request $request) => $request->user()->load('roles'));
    Route::put('/user', [UserController::class, 'updateProfile']); // Ruta para actualizar el perfil del usuario
    Route::delete('/auth/me', [AuthController::class, 'deleteAccount']);

    // Admin & Superadmin
    Route::middleware([RoleMiddleware::class . ':admin|superadmin'])->group(function () {
        Route::apiResource('users', UserController::class);
        Route::post('users/{user}/assign-role', [UserController::class, 'assignRole']);

        // Solo operaciones de creación, actualización y eliminación para admin/superadmin
        Route::post('supplies', [SupplyController::class, 'store']);
        Route::put('supplies/{supply}', [SupplyController::class, 'update']);
        Route::patch('supplies/{supply}', [SupplyController::class, 'update']);
        Route::delete('supplies/{supply}', [SupplyController::class, 'destroy']);

        Route::post('training-videos', [TrainingVideoController::class, 'store']);
        Route::put('training-videos/{training_video}', [TrainingVideoController::class, 'update']);
        Route::delete('training-videos/{training_video}', [TrainingVideoController::class, 'destroy']);

        // Nuevas rutas para gestionar asignaciones de videos a usuarios
        Route::get('users/{user}/training-videos', [UserTrainingVideoController::class, 'getUserVideos']);
        Route::post('users/{user}/assign-video', [UserTrainingVideoController::class, 'assignVideo']);
        Route::delete('users/videos/{userVideo}', [UserTrainingVideoController::class, 'removeUserVideo']);

        Route::get('payment-config', [PaymentController::class, 'index']);
        Route::put('payment-config', [PaymentController::class, 'updateConfiguration']);

        // Plantas: gestión completa + asignación manual
        Route::apiResource('plants', PlantController::class);
        Route::get('plants/user/{user_id}', [PlantController::class, 'UserPlants']);
        Route::post('plants/{plant}/assign', [PlantController::class, 'assignToUser']);
        Route::post('plants/{plant}/unassign', [PlantController::class, 'unassignUser']);
    });

    // Acceso a insumos de solo lectura para todos los usuarios autenticados
    Route::get('supplies', [SupplyController::class, 'index']);
    Route::get('supplies/{supply}', [SupplyController::class, 'show']);

    // Servicios para admin, superadmin, técnico
    Route::middleware([RoleMiddleware::class . ':admin|superadmin|tecnico'])->group(function () {
        Route::apiResource('services', ServiceController::class);
    });

    // Visualización de Training Videos para todos los autenticados
    Route::get('training-videos', [TrainingVideoController::class, 'index']);
    Route::get('training-videos/{training_video}', [TrainingVideoController::class, 'show']);

    // Ruta para que los usuarios vean sus propios videos asignados
    Route::get('user/training-videos', [UserTrainingVideoController::class, 'getCurrentUserVideos']);

    // Técnicos - solo admin, superadmin y técnico pueden ver/actualizar todas las solicitudes
    Route::middleware([RoleMiddleware::class . ':tecnico|admin|superadmin'])->group(function () {
        Route::get('service-requests', [ServiceRequestController::class, 'index']);
        Route::put('service-requests/{service_request}', [ServiceRequestController::class, 'update']);
        Route::delete('service-requests/{service_request}', [ServiceRequestController::class, 'destroy']);
        Route::post('service-requests/{service_request}/assign-technician', [ServiceRequestController::class, 'assignTechnician']);
        Route::apiResource('supply-purchases', SupplyPurchaseController::class);

        // Rutas para estadísticas
        Route::get('stats/monthly-sales', [SupplyPurchaseController::class, 'monthlySalesStats']);
        Route::get('stats/maintenance', [ServiceRequestController::class, 'maintenanceStats']);
        Route::get('stats/top-selling-supplies', [SupplyPurchaseController::class, 'topSellingSupplies']);
        Route::get('stats/product-demand-trends', [SupplyPurchaseController::class, 'productDemandTrends']);
    });

    // Rutas para solicitudes de servicio (acceso para clientes para crear/ver sus propias solicitudes)
    Route::post('service-requests', [ServiceRequestController::class, 'store']); // Crear solicitudes (clientes)
    Route::get('service-requests/{service_request}', [ServiceRequestController::class, 'show']); // Ver detalle (todos con autenticación)

    // Clientes (solo ver sus plantas)
    Route::middleware([RoleMiddleware::class . ':cliente'])->group(function () {
        Route::get('my-plants', [PlantController::class, 'myPlants']);
        Route::get('my-service-requests', [ServiceRequestController::class, 'myRequests']);
        // Añadir ruta para pedidos del cliente
        Route::get('user/orders', [SupplyPurchaseController::class, 'userOrders']);
    });

    // Carrito (cliente, admin, superadmin)
    Route::middleware([RoleMiddleware::class . ':cliente|admin|superadmin'])->group(function () {
        Route::get('cart', [CartController::class, 'index']);
        Route::post('cart', [CartController::class, 'store']);
        Route::put('cart/{item}', [CartController::class, 'update']);
        Route::delete('cart/{item}', [CartController::class, 'destroy']);
        Route::post('cart/checkout', [CartController::class, 'checkout']);
    });

    // Rutas para direcciones (para todos los usuarios autenticados)
    Route::get('user/addresses', [App\Http\Controllers\AddressController::class, 'index']);
    Route::get('user/address', [App\Http\Controllers\AddressController::class, 'getDefault']);
    Route::post('user/address', [App\Http\Controllers\AddressController::class, 'store']);
    Route::get('user/address/{id}', [App\Http\Controllers\AddressController::class, 'show']);
    Route::put('user/address/{id}', [App\Http\Controllers\AddressController::class, 'update']);
    Route::delete('user/address/{id}', [App\Http\Controllers\AddressController::class, 'destroy']);

    // Común a todos los usuarios autenticados
    Route::apiResource('alerts', AlertController::class);
    Route::apiResource('notifications', NotificationController::class);
    Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
});
