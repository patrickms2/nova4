<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NovaRequest;
use App\Services\Nova\NovaBundleOrderService;
use App\Services\Nova\NovaOrchestratorService;
use Heiner\FilamentAgenticChatbot\Models\RagBot;
use Heiner\FilamentAgenticChatbot\Models\RagConversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NovaChatController extends Controller
{
    public function __invoke(Request $request, NovaOrchestratorService $orchestrator): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:10000'],
            'channel' => ['nullable', 'string', 'max:50'],
            'conversation_id' => ['nullable', 'string', 'max:120'],
            'user.phone' => ['nullable', 'string', 'max:50'],
            'user.name' => ['nullable', 'string', 'max:120'],
            'user.locale' => ['nullable', 'string', 'max:10'],
            'context' => ['nullable', 'array'],
            'debug' => ['nullable', 'boolean'],
        ]);

        $phone = (string) data_get($validated, 'user.phone', '+34646426442');
        $debug = (bool) data_get($validated, 'debug', false);
        $channel = 'ai_bot';
        $conversation_id = 'ai-bot-demo';
          
        try {
            $result = $orchestrator->runLocalTourismScenario(
                message: $validated['message'],
                touristPhone: $phone,
                debug: $debug,
                channel: $channel,
                conversationId: $conversation_id,
                channelContext: (array) ($validated['context'] ?? []),
            );
            $conversation = data_get($result, 'context.conversation')
                ?? data_get($result, 'checks.conversation');

            return response()->json([
                'success' => true,
                'source' => 'nova_chat_gateway',
                'channel' => $validated['channel'] ?? 'ai-bot',
                'conversation_id' => $validated['conversation_id'] ?? $phone,
                'phone' => $phone,
                'message' => $result['message'] ?? $validated['message'],
                'reply' => $result['reply'] ?? '',
                'choices' => $result['choices'] ?? null,
                'input_type' => $result['input_type'] ?? null,
                'nova_request_id' => $result['nova_request_id'] ?? null,
                'intent' => data_get($conversation, 'intent'),
                'status' => data_get($conversation, 'stage'),
                'conversation' => $conversation,
                'knowledge' => data_get($result, 'checks.knowledge'),
                'reservation_check' => data_get($result, 'checks.reservation_check'),
                'checks' => $result['checks'] ?? [],
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'source' => 'nova_chat_gateway',
                'error' => 'Error interno al procesar el mensaje.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function reset(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'conversation_id' => ['nullable', 'string', 'max:120'],
            'user.phone' => ['nullable', 'string', 'max:50'],
        ]);

        $phone = (string) data_get($validated, 'user.phone', '+34646426442');
        $conversationId = (string) data_get($validated, 'conversation_id', $phone);

        $deleted = NovaRequest::query()
            ->where('type', 'tourism_orchestration')
            ->where('context->tourist_phone', $phone)
            ->where('context->conversation_id', $conversationId)
            ->delete();

        if ($deleted === 0) {
            $deleted = NovaRequest::query()
                ->where('type', 'tourism_orchestration')
                ->where('context->tourist_phone', $phone)
                ->delete();
        }

        // Delete halted workflow runs so the next message starts a fresh workflow
        $bot = RagBot::where('name', 'Nova MCP Operator')->first();
        if ($bot) {
            $conversation = RagConversation::where('rag_bot_id', $bot->id)
                ->where('session_id', $conversationId)
                ->first();

            $conversation?->workflowRuns()->delete();
        }

        Cache::forget("nova_context_{$phone}");

        return response()->json([
            'success' => true,
            'deleted' => $deleted,
            'conversation_id' => $conversationId,
            'phone' => $phone,
        ]);
    }

    public function bundleOrder(Request $request, NovaBundleOrderService $bundleService): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120'],
            'phone' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'postcode' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:2'],
            'region_id' => ['nullable', 'integer'],
            'region_code' => ['nullable', 'string', 'max:50'],
            'region' => ['nullable', 'string', 'max:120'],
            'street' => ['nullable', 'array'],
            'company' => ['nullable', 'string', 'max:120'],
            'la_geria_product_id' => ['nullable', 'integer'],
            'la_geria_quantity' => ['nullable', 'integer', 'min:1'],
            'lanzaloe_sku' => ['nullable', 'string', 'max:120'],
            'lanzaloe_quantity' => ['nullable', 'integer', 'min:1'],
            'lanzaloe_shipping_method' => ['nullable', 'string', 'max:120'],
            'lanzaloe_shipping_carrier' => ['nullable', 'string', 'max:120'],
            'lanzaloe_payment_method' => ['nullable', 'string', 'max:120'],
            'lanzaloe_agreement_ids' => ['nullable', 'array'],
            'cancel_after' => ['nullable', 'boolean'],
        ]);

        try {
            $result = $bundleService->createBundle($validated);

            if ($validated['cancel_after'] ?? false) {
                if (isset($result['la_geria']['order_id'])) {
                    $result['la_geria']['cancel_result'] = $bundleService->cancelLaGeriaOrder((int) $result['la_geria']['order_id']);
                }
                if (isset($result['lanzaloe']['order_id'])) {
                    $result['lanzaloe']['cancel_result'] = $bundleService->cancelLanzaloeOrder((int) $result['lanzaloe']['order_id']);
                }
            }

            return response()->json([
                'success' => $result['success'],
                'source' => 'nova_bundle_order_gateway',
                'bundle_reference' => $result['bundle_reference'],
                'la_geria' => $result['la_geria'],
                'lanzaloe' => $result['lanzaloe'],
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'source' => 'nova_bundle_order_gateway',
                'error' => 'Error interno al crear el pedido cruzado.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }
}
