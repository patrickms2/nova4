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
use Illuminate\Support\Facades\Route;

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
