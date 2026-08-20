<?php

use App\Http\Controllers\addExpenseController;
use App\Http\Controllers\Api\NovaChatController;
use App\Http\Controllers\Api\NovaWhatsappWebhookController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CalendarFeedController;
use App\Http\Controllers\CommunityAttendanceAudioController;
use App\Http\Controllers\editExpenseController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\MCPController;
use App\Http\Controllers\Nova\CapabilityGraphOptionsController;
use App\Http\Controllers\NovaDebugChatController;
use App\Http\Controllers\PanelBuilderController;
use App\Http\Controllers\PublicBundleController;
use App\Http\Controllers\PublicBundleRedsysController;
use App\Http\Controllers\PublicExploreController;
use App\Http\Controllers\PublicRedsysPaymentController;
use App\Http\Controllers\PublicTaxiRouteCheckoutController;
use App\Http\Controllers\RedsysController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\VoiceController;
use App\Livewire\BuilderGenerator;
use App\Livewire\CommunityPortal;
use App\Livewire\Comunigest\CommunityCrud;
use App\Livewire\Comunigest\IncidentCrud;
use App\Livewire\Comunigest\NewIncident;
use App\Livewire\Comunigest\OrderTypeCrud;
use App\Livewire\Comunigest\Settings;
use App\Livewire\Comunigest\TaskTypeCrud;
use App\Livewire\Comunigest\UserCrud;
use App\Livewire\Comunigest\WorkOrderCrud;
use App\Livewire\Comunigest\WorkOrderDetail;
use App\Livewire\Comunigest\WorkOrderTaskCrud;
use App\Livewire\ComunigestCommunityShow;
use App\Livewire\ComunigestDashboard;
use App\Livewire\Facturas\Ajustes;
use App\Livewire\Facturas\Clientes;
use App\Livewire\Facturas\Dashboard;
use App\Livewire\Facturas\Empresas;
use App\Livewire\Facturas\FacturaForm;
use App\Livewire\Facturas\Facturas;
use App\Livewire\Facturas\Remesas;
use App\Livewire\IntegratedPanelManager;
use App\Livewire\Notes\Notes;
use App\Livewire\Nova\NovaGraph;
use App\Livewire\Nova\NovaStudio;
use App\Livewire\Nova\NovaWorkspace;
use App\Livewire\PanelBuilder;
use App\Livewire\Projects\Projects;
use App\Livewire\Providers\ServiceSubmissionWizard;
use App\Livewire\ReactFlowBuilder;
use App\Livewire\ReactFlowEditor;
use App\Livewire\Tasks\Tasks;
use App\Livewire\Tourist\BookingPage;
use App\Livewire\Tourist\BookingSuccessPage;
use App\Livewire\Tourist\BusinessOnboarding;
use App\Livewire\Tourist\HomePage;
use App\Livewire\Tourist\OfferDetailPage;
use App\Livewire\Tourist\RideConfirmedPage;
use App\Livewire\VisualPanelBuilder;
use App\Livewire\WorkflowPanelManager;
use App\Models\Factura;
use App\Models\Server;
use App\Models\Transaction;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use LivewireFilemanager\Filemanager\Http\Controllers\Files\FileController;
use App\Livewire\Nova\CapabilityComposer;


use App\Models\MagentoOrderSync;
// JSON Viewer for MagentoOrderSync raw data
Route::get('/magento-order-sync/{id}/json', function ($id) {
    $sync = MagentoOrderSync::findOrFail($id);

    return view('magento-order-sync-json', [
        'sync' => $sync,
        'json' => json_encode($sync->raw_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    ]);
})->name('magento-order-sync.json');


Route::get('/studio/capabilities', CapabilityComposer::class)->name('nova.studio.capabilities');
Route::view('/comunigest-front', 'comunigest.front')->name('comunigest.front');

Route::view('/comunigest/login', 'comunigest.login')->name('comunigest.login');
Route::post('/comunigest/login', [AuthController::class, 'login'])->name('comunigest.login');

Route::get('/comunigest/inicio', CommunityPortal::class)->middleware('auth')->name('comunigest.inicio');
Route::get('/comunigest/app', CommunityPortal::class)->middleware('auth')->name('comunigest.app');
Route::get('/comunigest/dashboard', CommunityPortal::class)->middleware('auth')->name('comunigest.dashboard');

Route::get('/comunigest/asistencias/{attendance}/audio', CommunityAttendanceAudioController::class)
    ->middleware('auth')
    ->name('comunigest.attendances.audio');
Route::get('/comunigest', ComunigestDashboard::class)->name('comunigest.dashboard');
Route::get('/comunigest/communities/{community}', ComunigestCommunityShow::class)->name('comunigest.community.show');

Route::get('/comunigest/admin/users', UserCrud::class)->name('comunigest.admin.users');
Route::get('/comunigest/admin/communities', CommunityCrud::class)->name('comunigest.admin.communities');
Route::get('/comunigest/admin/work-orders', WorkOrderCrud::class)->name('comunigest.admin.work-orders');
Route::get('/comunigest/admin/work-orders/{orderId}/tasks', WorkOrderTaskCrud::class)->name('comunigest.admin.work-order-tasks');
Route::get('/comunigest/admin/incidents', IncidentCrud::class)->name('comunigest.admin.incidents');
Route::get('/comunigest/admin/settings', Settings::class)->name('comunigest.admin.settings');
Route::get('/comunigest/admin/order-types', OrderTypeCrud::class)->name('comunigest.admin.order-types');
Route::get('/comunigest/admin/task-types', TaskTypeCrud::class)->name('comunigest.admin.task-types');
Route::get('/comunigest/trabajo/{workOrder}', WorkOrderDetail::class)->middleware('auth')->name('comunigest.work-order');
Route::get('/comunigest/incidencia/nueva/{workOrder?}', NewIncident::class)->middleware('auth')->name('comunigest.new-incident');

Route::get('/status', [StatusController::class, 'index']);
Volt::route('/nova/reservations', 'nova.reservations');
Volt::route('/nova/customers', 'nova.customers');
Volt::route('/nova', 'pages.nova.overview')
    ->name('nova.overview');

Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});
// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/profile/update', [AuthController::class, 'updateProfile'])->name('profile.update');

    Route::get('/nova/graph/capability-options', CapabilityGraphOptionsController::class)
        ->name('nova.graph.capability-options');

    Route::get('/add-expense', function () {
        $data = ['page' => 'Add Expense'];

        if (session('username') == null) {
            return redirect('/login');
        }

        return view('add-expense', $data);
    });

    Route::get('expense/{id}', function ($id) {
        $data = ['page' => 'Edit Expense', 'transactions' => Transaction::find($id)];

        if (session('username') == null) {
            return redirect('/login');
        }

        return view('expense', $data);
    });

    Route::post('/edit-expense/{id}', [editExpenseController::class, 'editExpense'])->name('transactions.editExpense');

    Route::get('delete-expense/{id}', function ($id) {
        Transaction::find($id)->delete();

        return redirect('/')->with('success', 'Expense deleted successfully!');
    });

    Route::livewire('/nova/chat', 'pages::chat');

    Route::post('/transactions', [addExpenseController::class, 'store'])->name('transactions.store');
    Route::get('/nova', NovaWorkspace::class)->name('nova.nova-workspace');
    Route::get('/nova/graph', NovaGraph::class)->name('nova.graph');
    Route::get('/nova/debug/chat', [NovaDebugChatController::class, 'index'])->name('nova.debug.chat');
    Route::post('/nova/debug/chat/send', [NovaDebugChatController::class, 'send'])->name('nova.debug.chat.send');
    Route::post('/nova/debug/chat/export', [NovaDebugChatController::class, 'export'])->name('nova.debug.chat.export');
    Route::get('/studio', NovaStudio::class)->name('nova.studio');
    Volt::route('/nova2', 'nova/nova-workspace')->name('nova.nova-workspace2');

    Volt::route('/facturacion/bundles', 'facturacion.bundleorders')->name('facturacion.bundles');
    Volt::route('/facturacion/bundle-products', 'facturacion.bundle-products')->name('facturacion.bundle-products');
    Volt::route('/facturacion/imported-products', 'facturacion.imported-products')->name('facturacion.imported-products');
    Volt::route('/facturacion/nueva', 'facturacion.nuevafactura')->name('facturacion.nuevafactura');
    Volt::route('/facturacion/facturas2', 'facturacion.facturas-dashboard')->name('facturacion.facturas');
    Route::get('/facturacion/dashboard', Dashboard::class)->name('facturacion.dashboard');
    Route::get('/filemanager/{path}', [FileController::class, 'show'])->where('path', '.*')->name('assets.show');
    Route::get('/facturacion/tasks', Tasks::class)->name('facturacion.tasks.index');
    Route::get('/facturacion/projects', Projects::class)->name('facturacion.projects.index');
    Route::get('/facturacion/notes', Notes::class)->name('facturacion.notes.index');

    Route::get('/facturacion/facturas', Facturas::class)->name('facturacion.facturas2');
    Route::get('/facturacion/empresas', Empresas::class)->name('facturacion.empresas');
    Route::get('/facturacion/clientes', Clientes::class)->name('facturacion.clientes');
    Route::get('/facturacion/remesas', Remesas::class)->name('facturacion.remesas');
    Route::get('/facturacion/ajustes', Ajustes::class)->name('facturacion.ajustes');

    Route::get('/novafactu', function () {
        return view('livewire.facturacion.dashboard');
    })->name('novafactu.dashboard');

    Volt::route('/transactions', 'transactions.manage-transactions')->name('facturacion.transactions');
    Volt::route('/facturacion/expenses', 'expenses.expenses')->name('facturacion.expenses');
    Route::livewire('/facturacion/manage-categories', 'manage-categories')->name('facturacion.categories');
    Route::livewire('/facturacion/budget', 'budget')->name('facturacion.budget');
    Route::livewire('/facturacion/recurring', 'recurring-transactions')->name('facturacion.recurring');

    Route::get('/facturacion/factura', FacturaForm::class)->name('facturacion.factura');
    Route::get('/facturacion/pdf/{codfactura?}', function (?string $codfactura = null) {
        $code = $codfactura ?? request()->query('codfactura') ?? request()->query('factura');

        $factura = null;
        if ($code) {
            $factura = Factura::where('codfactura', $code)->first();
            if (! $factura && is_numeric($code)) {
                $factura = Factura::find($code);
            }
        }

        if (! $factura) {
            $id = request()->query('id') ?? request()->query('record');
            if ($id && is_numeric($id)) {
                $factura = Factura::find($id);
            }
        }

        if (! $factura) {
            abort(404, 'Factura no encontrada');
        }

        $registros = $factura->registros;

        return view('pdf.factura', compact('factura', 'registros'));
    })->name('factura.pdf')->where('codfactura', '.*');

    Volt::route('/facturacion/zip', 'facturacion.zip')->name('factura.zip');
});
// Panel Manager Principal - Centro de Control Integrado
Route::get('/panel-manager', IntegratedPanelManager::class);

// Workflow Panel Manager - Nuevo Sistema de Edición por Workflow
Route::get('/pam', WorkflowPanelManager::class)->name('workflow-panel-manager');

// Editor Visual React Flow
Route::get('/react-flow-editor/{panelId?}', ReactFlowEditor::class);

// Rutas Legacy - Mantener para compatibilidad
Route::get('/builder', BuilderGenerator::class);
Route::get('/visual-builder', VisualPanelBuilder::class);
Route::get('/panel-builder', PanelBuilder::class);
Route::get('/react-flow-builder', ReactFlowBuilder::class);

// Panel Builder API Routes
Route::prefix('api/panel-builder')->group(function () {
    Route::get('/', [PanelBuilderController::class, 'index']);
    Route::get('/visual', [PanelBuilderController::class, 'visual']);
    Route::get('/field-configurator', [PanelBuilderController::class, 'fieldConfigurator']);
    Route::get('/panels/{panel}', [PanelBuilderController::class, 'getPanel']);
    Route::get('/field-types', [PanelBuilderController::class, 'getFieldTypes']);

    Route::post('/panels', [PanelBuilderController::class, 'store']);
    Route::put('/panels/{panel}', [PanelBuilderController::class, 'update']);
    Route::delete('/panels/{panel}', [PanelBuilderController::class, 'destroy']);

    Route::post('/panels/{panel}/fields', [PanelBuilderController::class, 'addField']);
    Route::put('/fields/{field}', [PanelBuilderController::class, 'updateField']);
    Route::delete('/fields/{field}', [PanelBuilderController::class, 'destroyField']);

    Route::post('/panels/{panel}/relations', [PanelBuilderController::class, 'addRelation']);
    Route::put('/relations/{relation}', [PanelBuilderController::class, 'updateRelation']);
    Route::delete('/relations/{relation}', [PanelBuilderController::class, 'destroyRelation']);

    Route::post('/panels/{panel}/tables', [PanelBuilderController::class, 'addTable']);
    Route::put('/tables/{table}', [PanelBuilderController::class, 'updateTable']);
    Route::delete('/tables/{table}', [PanelBuilderController::class, 'destroyTable']);

    Route::post('/panels/{panel}/generate-code', [PanelBuilderController::class, 'generateCode']);
    Route::get('/panels/{panel}/preview-code', [PanelBuilderController::class, 'previewCode']);
    Route::get('/panels/{panel}/export', [PanelBuilderController::class, 'export']);
    Route::post('/import', [PanelBuilderController::class, 'import']);
});

// MCP Server Routes
Route::prefix('mcp/nova')->group(function () {
    Route::get('/info', [MCPController::class, 'serverInfo'])->name('mcp.info');
    Route::get('/tools', [MCPController::class, 'listTools'])->name('mcp.tools');
    Route::post('/execute', [MCPController::class, 'executeTool'])->name('mcp.execute');
});

Volt::route('/test', 'facturacion.facturas')->name('facturacion.test');
Route::get('/nova/whatsapp/webhook/test', [NovaWhatsappWebhookController::class, 'test'])->name('nova.whatsapp.webhook.test');

Route::get('/home', function () {
    return view('welcome');
})->name('home');
Route::get('/explore', [PublicExploreController::class, 'index'])->name('public.explore');
Route::get('/explore/places', [PublicExploreController::class, 'places'])->name('public.explore.places');
Route::get('/explore/availability', [PublicExploreController::class, 'availability'])->name('public.explore.availability');
Route::get('/explore/transfer-estimate', [PublicExploreController::class, 'transferEstimate'])->name('public.explore.transfer-estimate');
Route::post('/explore/requests', [PublicExploreController::class, 'storeBookingRequest'])->name('public.explore.requests.store');
Route::post('/explore/packages', [PublicExploreController::class, 'storePackageBookingRequest'])->name('public.explore.packages.store');

// Public bundle landing page
Route::get('/bundle', [PublicBundleController::class, 'index'])->name('public.bundle');
Route::post('/bundle', [PublicBundleController::class, 'store'])->name('public.bundle.store');

// Map routes for explore integration
Route::get('/maps/select-address', [MapController::class, 'selectAddress'])->name('maps.select-address');
Route::get('/maps/taxi-route', [MapController::class, 'taxiRoute'])->name('maps.taxi-route');
Route::post('/maps/places', [MapController::class, 'searchPlaces'])->name('maps.places');
Route::post('/maps/route', [MapController::class, 'getRoute'])->name('maps.route');
Route::post('/maps/geocode', [MapController::class, 'geocodeAddress'])->name('maps.geocode');
Route::post('/maps/reverse-geocode', [MapController::class, 'reverseGeocode'])->name('maps.reverse-geocode');
Route::post('/maps/transfer-route', [MapController::class, 'transferRoute'])->name('maps.transfer-route');

Route::get('/explore/pay/{request}', [PublicRedsysPaymentController::class, 'start'])->name('public.redsys.start');
Route::post('/payments/redsys/notify', [PublicRedsysPaymentController::class, 'notify'])->name('public.redsys.notify');
Route::get('/payments/redsys/ok/{request}', [PublicRedsysPaymentController::class, 'ok'])->name('public.redsys.ok');
Route::get('/payments/redsys/ko/{request}', [PublicRedsysPaymentController::class, 'ko'])->name('public.redsys.ko');

Route::get('/payments/redsys/notify', [PublicRedsysPaymentController::class, 'notify'])->name('public.redsys.notify');
Route::get('/redsys/pay/{pago}', [RedsysController::class, 'payFromPago'])->name('redsys.pay.fromPago');
// Route::get('/redsys/pay/{pago}', [ServicioController::class, 'formPago'])->name('redsys.pay.pago');

Route::get('/bundle/{bundle}/pay', [PublicBundleRedsysController::class, 'start'])->name('bundle.redsys.start');
Route::post('/bundle/redsys/notify', [PublicBundleRedsysController::class, 'notify'])->name('bundle.redsys.notify');
Route::get('/bundle/redsys/ok/{bundle}', [PublicBundleRedsysController::class, 'ok'])->name('bundle.redsys.ok');
Route::get('/bundle/redsys/ko/{bundle}', [PublicBundleRedsysController::class, 'ko'])->name('bundle.redsys.ko');

// Notificación servidor a servidor de Redsys
Route::post('/redsys/callback', [RedsysController::class, 'callback'])->name('redsys.callback');

Route::get('/redsys/ok', [RedsysController::class, 'ok'])->name('redsys.ok');
Route::get('/redsys/ko', [RedsysController::class, 'ko'])->name('redsys.ko');
Route::post('/redsys/pay', [RedsysController::class, 'pay'])->name('redsys.pay');
Route::post('/redsys/callback', [RedsysController::class, 'callback'])->name('redsys.callback');
Route::get('pdf/order/{id}', [RedsysController::class, 'pdf'])->name('order.pdf');

Route::get('/ai/test', function () {

    $response = Http::post(
        'http://192.168.1.130:11434/api/generate',
        [
            'model' => 'qwen3:4b',
            'prompt' => 'Extrae intención y devuelve JSON...',
            'stream' => false,
            'think' => false,
        ]
    );

    return $response->json();
});

Route::get('/ai-bot', function () {
    $novaServer = Server::query()
        ->with(['prompts' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])
        ->where('slug', 'nova')
        ->first();

    $activePrompt = $novaServer?->prompts->first();
    $allPrompts = $novaServer?->prompts ?? collect();

    return view('ai-bot', compact('novaServer', 'activePrompt', 'allPrompts'));
})->name('ai-bot.view');

Route::post('/ai-bot', NovaChatController::class)->name('ai-bot.chat');

Route::get('/taxi-routes/checkout/{token}', [PublicTaxiRouteCheckoutController::class, 'show'])
    ->name('taxi-routes.checkout');

Route::get('/chat', function () {
    return view('chat');
})->name('chat.view');
Route::get('/welcome', function () {
    return view('welcome');
})->name('welcome.view');
Route::get('/ride', HomePage::class)->name('home');
Route::get('/rides/{ride}/confirmed', RideConfirmedPage::class)->name('rides.confirmed');
Route::get('/offers/{offer:slug}', OfferDetailPage::class)->name('offers.show');
Route::get('/offers/{offer:slug}/booking', BookingPage::class)->name('bookings.create');
Route::get('/bookings/{booking}/success', BookingSuccessPage::class)->name('bookings.success');

Route::get('/business/onboarding', BusinessOnboarding::class)->name('business.onboarding');
Route::get('/submit-service', ServiceSubmissionWizard::class)->name('service.submission');
Route::view('/healthz', 'healthz')->name('healthz');

Route::post('/transcribe', [VoiceController::class, 'transcribe'])->name('transcribe');
Route::get('/calendar/feed', CalendarFeedController::class)->name('calendar.feed');
