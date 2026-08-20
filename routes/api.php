<?php

use App\Http\Controllers\Api\GastoOcrController;
use App\Http\Controllers\Api\MCPController;
use App\Http\Controllers\Api\NovaChatController;
use App\Http\Controllers\Api\NovaWhatsappWebhookController;
use App\Http\Controllers\Api\StaffAccessController;
use App\Http\Controllers\Api\WebhookSolicitudController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\Api\V1\CommunityController;
use App\Http\Controllers\Api\V1\CommunityPlanController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\IncidentController;
use App\Http\Controllers\Api\V1\WorkCatalogController;
use App\Http\Controllers\Api\V1\WorkOrderController;
use App\Http\Controllers\Api\V1\WorkOrderTaskController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ShipmentDeliveryController;
use App\Http\Controllers\Api\V1\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

// API Version 1
Route::prefix('v1')->group(function () {

    // Protected routes requiring Sanctum authentication
    Route::middleware([
        'auth:sanctum',
        'throttle:60,1',  // Rate limiting: 60 requests per minute per user
        \App\Http\Middleware\LogApiRequest::class,
    ])->group(function () {

        // Orders
        Route::get('orders', [OrderController::class, 'index'])
            ->name('api.v1.orders.index');
        Route::get('orders/{increment_id}', [OrderController::class, 'show'])
            ->name('api.v1.orders.show');

        // Invoices
        Route::get('invoices', [InvoiceController::class, 'index'])
            ->name('api.v1.invoices.index');
        Route::get('invoices/{increment_id}', [InvoiceController::class, 'show'])
            ->name('api.v1.invoices.show');

        // Webhooks
        Route::post('webhooks/shipment-status', [WebhookController::class, 'shipmentStatus'])
            ->name('api.v1.webhooks.shipment-status');

        // Shipment delivery updates
        Route::patch(
            'shipments/{magento_shipment_id}/delivery',
            [ShipmentDeliveryController::class, 'updateDelivery']
        )->name('api.v1.shipments.update-delivery');

    });

});

// Health check endpoint (no auth required, but rate-limited)
Route::middleware('throttle:300,1')->get('health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toIso8601String(),
    ]);
})->name('api.health');

// Debug authentication endpoint (NO AUTH - for debugging)
Route::get('debug/token', function (Request $request) {
    $bearerToken = $request->bearerToken();

    if (!$bearerToken) {
        return response()->json([
            'error' => 'No bearer token provided',
            'all_headers' => $request->headers->all(),
            'authorization_header' => $request->header('Authorization'),
        ]);
    }

    // Parse token manually
    $tokenParts = explode('|', $bearerToken);
    if (count($tokenParts) !== 2) {
        return response()->json(['error' => 'Invalid token format']);
    }

    [$id, $token] = $tokenParts;
    $tokenHash = hash('sha256', $token);

    // Find token in database
    $dbToken = \Laravel\Sanctum\PersonalAccessToken::find($id);

    if (!$dbToken) {
        return response()->json(['error' => 'Token not found in database', 'token_id' => $id]);
    }

    // Check hash
    $hashMatches = hash_equals($dbToken->token, $tokenHash);

    // Try to get user
    $user = $dbToken->tokenable;

    return response()->json([
        'token_found' => true,
        'token_id' => $dbToken->id,
        'hash_matches' => $hashMatches,
        'tokenable_type' => $dbToken->tokenable_type,
        'tokenable_id' => $dbToken->tokenable_id,
        'tenant_id' => $dbToken->tenant_id,
        'user_found' => $user ? true : false,
        'user_email' => $user ? $user->email : null,
        'guards_configured' => array_keys(config('auth.guards')),
        'default_guard' => config('auth.defaults.guard'),
    ]);
})->name('api.debug.token');


Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('daily-work', [DashboardController::class, 'daily'])->name('api.v1.daily-work');

    Route::apiResource('communities', CommunityController::class)->names('api.v1.communities');
    Route::get('communities/{community}/work-orders', [WorkOrderController::class, 'index'])->name('api.v1.communities.work-orders.index');
    Route::get('communities/{community}/plans', [CommunityPlanController::class, 'index'])->name('api.v1.communities.plans.index');
    Route::post('communities/{community}/plans', [CommunityPlanController::class, 'store'])->name('api.v1.communities.plans.store');

    Route::apiResource('work-catalog', WorkCatalogController::class)->names('api.v1.work-catalog');

    Route::apiResource('work-orders', WorkOrderController::class)->except(['index'])->names('api.v1.work-orders');
    Route::post('work-orders/{work_order}/start', [WorkOrderController::class, 'start'])->name('api.v1.work-orders.start');
    Route::post('work-orders/{work_order}/finish', [WorkOrderController::class, 'finish'])->name('api.v1.work-orders.finish');

    Route::get('work-orders/{work_order}/tasks', [WorkOrderTaskController::class, 'index'])->name('api.v1.work-orders.tasks.index');
    Route::post('work-orders/{work_order}/tasks', [WorkOrderTaskController::class, 'store'])->name('api.v1.work-orders.tasks.store');
    Route::post('work-orders/{work_order}/tasks/{task}/complete', [WorkOrderTaskController::class, 'complete'])->name('api.v1.work-orders.tasks.complete');

    Route::apiResource('incidents', IncidentController::class)->names('api.v1.incidents');
    Route::post('incidents/{incident}/close', [IncidentController::class, 'close'])->name('api.v1.incidents.close');
});

// Route::get('/user', function (Request $request) {
//    return $request->user();
// })->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->apiResource('tasks', TaskController::class);
Route::post('/webhook/solicitud-taxi', WebhookSolicitudController::class)
    ->name('api.webhook.solicitud-taxi');

Route::post('/nova/chat', NovaChatController::class)
    ->name('api.nova.chat');

Route::post('/nova/chat/reset', [NovaChatController::class, 'reset'])
    ->name('api.nova.chat.reset');

Route::post('/nova/bundle-order', [NovaChatController::class, 'bundleOrder'])
    ->name('api.nova.bundle-order');

Route::get('/nova/whatsapp/copilot', [NovaWhatsappWebhookController::class, 'verify'])
    ->name('api.nova.whatsapp.webhook.verify');

Route::post('/nova/whatsapp/webhook', NovaWhatsappWebhookController::class)
    ->name('api.nova.whatsapp.webhook');

Route::post('/nova/whatsapp/copilot', [NovaWhatsappWebhookController::class, 'copilot'])
    ->name('api.nova.whatsapp.copilot');

Route::get('/nova/whatsapp/webhook/test', [NovaWhatsappWebhookController::class, 'test'])->name('api.nova.whatsapp.webhook.test');

Route::post('/gastos/ocr', GastoOcrController::class)
    ->middleware('auth:sanctum')
    ->name('api.gastos.ocr');

Route::prefix('mcp')->group(function () {
    Route::get('/info', [MCPController::class, 'serverInfo'])->name('api.mcp.info');
    Route::get('/tools', [MCPController::class, 'listTools'])->name('api.mcp.tools');
    Route::get('/tools/get_hotels', [MCPController::class, 'listHotels'])->name('api.mcp.tools.get_hotels');
    Route::get('/tools/get_servicios', [MCPController::class, 'listServicios'])->name('api.mcp.tools.get_servicios');
    Route::post('/execute', [MCPController::class, 'executeTool'])->name('api.mcp.execute');
});

Route::prefix('staff')->name('api.staff.')->middleware('auth:sanctum')->group(function () {
    Route::get('/grants', [StaffAccessController::class, 'grants'])->name('grants');
    Route::get('/sessions/current', [StaffAccessController::class, 'currentSession'])->name('sessions.current');
    Route::get('/sessions/{session}', [StaffAccessController::class, 'show'])->name('sessions.show');
    Route::post('/sessions', [StaffAccessController::class, 'start'])->name('sessions.start');
    Route::post('/sessions/{session}/finish', [StaffAccessController::class, 'finish'])->name('sessions.finish');
    Route::post('/sessions/{session}/report', [StaffAccessController::class, 'submitReport'])->name('sessions.report');
    Route::post('/sessions/{session}/complete', [StaffAccessController::class, 'complete'])->name('sessions.complete');
});

Route::post('/staff/login', [StaffAccessController::class, 'login'])->name('api.staff.login');
