<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\CreateGastoFromReceiptAction;
use App\Services\Nova\NovaConversationKernel;
use App\Http\Controllers\Controller;
use App\Models\NovaWhatsappMessage;
use App\Services\Nova\NovaOrchestratorService;
use App\Services\Nova\NovaWhatsappAudioTranscriptionService;
use App\Services\Nova\NovaWhatsAppCloudService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

final class NovaWhatsappWebhookController extends Controller
{
    public function verify(Request $request): Response|JsonResponse
    {
        $mode = (string) $request->query('hub_mode', $request->query('hub.mode', ''));
        $token = (string) $request->query('hub_verify_token', $request->query('hub.verify_token', ''));
        $challenge = (string) $request->query('hub_challenge', $request->query('hub.challenge', ''));
        $configuredToken = (string) config('services.nova.whatsapp_verify_token');

        if ($mode === 'subscribe' && $configuredToken !== '' && hash_equals($configuredToken, $token)) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response()->json([
            'success' => false,
            'error' => 'Webhook verification failed',
        ], 403);
    }

    public function copilot(Request $request, NovaConversationKernel $kernel, NovaWhatsAppCloudService $whatsApp): JsonResponse
    {
        $phone = $this->extractPhone($request);

        \Log::info('Nova Copilot v2: received request', [
            'phone' => $phone,
            'is_meta_payload' => $this->isMetaPayload($request),
        ]);

        $message = $this->extractMessage($request);
        $result = $kernel->process(
            message: $message,
            channel: 'whatsapp',
            user: $phone,
            request: $request,
        );

        \Log::info('Nova Copilot v2: kernel result', [
            'phone' => $phone,
            'reply' => $result->reply,
        ]);

        if ($request->boolean('auto_reply', true)) {
            $sendResult = $whatsApp->sendText($phone, $result->reply);
            $result = $result->toArray();
            $result['send_result'] = $sendResult;
        } else {
            $result = $result->toArray();
        }

        return response()->json($result);
    }

    public function __invoke(Request $request, NovaOrchestratorService $orchestrator, NovaWhatsAppCloudService $whatsApp, NovaWhatsappAudioTranscriptionService $audioTranscription): JsonResponse
    {
        $isMetaPayload = $this->isMetaPayload($request);

        \Log::info('Nova webhook: received request', [
            'has_entry' => $request->has('entry'),
            'is_meta_payload' => $isMetaPayload,
            'has_message' => $request->has('message'),
            'phone' => $this->extractPhone($request),
            'request_keys' => array_keys($request->all()),
            'entry_structure' => $request->has('entry') ? array_keys($request->input('entry.0') ?? []) : null,
        ]);

        if (!$isMetaPayload && $request->has('entry')) {
            \Log::warning('Nova webhook: has entry but not detected as Meta payload', [
                'entry_keys' => array_keys($request->input('entry.0') ?? []),
                'changes_keys' => $request->has('entry.0.changes') ? array_keys($request->input('entry.0.changes.0') ?? []) : null,
            ]);

            // If this is a Meta event without messages, ignore it
            if ($request->has('entry.0.changes.0.value') && !$request->has('entry.0.changes.0.value.messages')) {
                \Log::info('Nova webhook: ignoring Meta event without messages', [
                    'field' => $request->input('entry.0.changes.0.field'),
                ]);
                return response()->json([
                    'success' => true,
                    'ignored' => true,
                    'message' => 'Meta event without messages ignored',
                ]);
            }
        }

        if (! $this->hasValidMetaSignature($request)) {
            \Log::warning('Nova webhook: invalid signature');
            return response()->json([
                'success' => false,
                'error' => 'Invalid signature',
            ], 401);
        }

        $configuredToken = config('services.nova.webhook_token');
        $receivedToken = $request->header('X-Nova-Webhook-Token');

        if ($configuredToken && $receivedToken !== $configuredToken) {
            \Log::warning('Nova webhook: unauthorized', [
                'configured' => $configuredToken ? 'set' : 'not set',
                'received' => $receivedToken ? 'set' : 'not set',
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized',
            ], 401);
        }

        $message = $this->extractMessage($request);
        $phone = $this->extractPhone($request);
        $messageId = $this->extractMessageId($request);
        $phoneNumberId = $this->extractPhoneNumberId($request);
        $messageType = $this->extractMessageType($request);
        $audioTranscriptionResult = null;

        // Handle interactive button/list responses
        $interactiveResponse = $this->extractInteractiveResponse($request);
        if ($interactiveResponse !== null) {
            $message = $interactiveResponse;
        }

        \Log::info('Nova webhook: extracted data', [
            'message' => $message,
            'phone' => $phone,
            'messageId' => $messageId,
            'phoneNumberId' => $phoneNumberId,
            'messageType' => $messageType,
            'interactive_response' => $interactiveResponse,
        ]);

        if ($message === '' && $messageType === 'audio') {
            $audioTranscriptionResult = $audioTranscription->transcribe($this->extractAudioMediaId($request));
            $message = trim((string) ($audioTranscriptionResult['text'] ?? ''));
        }

        // Handle image messages for OCR
        if ($message === '' && $messageType === 'image') {
            $ocrResult = $this->handleImageMessage($request, $phone);
            if ($ocrResult['success']) {
                $message = $ocrResult['ocr_text'] ?? '';
            }
        }

        if ($message === '') {
            \Log::warning('Nova webhook: message is empty', [
                'messageType' => $messageType,
                'audioTranscriptionResult' => $audioTranscriptionResult,
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Message text is required',
                'audio_transcription' => $audioTranscriptionResult,
            ], 422);
        }

        $whatsappMessage = null;

        \Log::info('Nova webhook: before message creation', [
            'messageId' => $messageId,
            'phone' => $phone,
        ]);

        if ($messageId !== null) {
            $whatsappMessage = NovaWhatsappMessage::query()->firstOrCreate(
                ['message_id' => $messageId],
                [
                    'phone_number_id' => $phoneNumberId,
                    'from_phone' => $phone,
                    'message_type' => $messageType,
                    'message_text' => $message,
                    'status' => 'received',
                    'payload' => $request->all(),
                ],
            );

            \Log::info('Nova webhook: message created/found', [
                'whatsapp_message_id' => $whatsappMessage->id,
                'was_recently_created' => $whatsappMessage->wasRecentlyCreated,
            ]);

            // Only skip if message is already processed
            if (! $whatsappMessage->wasRecentlyCreated && $whatsappMessage->status === 'processed') {
                \Log::info('Nova webhook: duplicate message, returning');
                return response()->json([
                    'success' => true,
                    'duplicate' => true,
                    'message' => 'Message already processed',
                ]);
            }

            // Update message status to processing
            $whatsappMessage->update(['status' => 'processing']);

            $whatsApp->sendReaction($phone, $messageId, '✅', $phoneNumberId);
        }

        \Log::info('Nova webhook: before message cluster check', [
            'whatsapp_message_id' => $whatsappMessage?->id,
            'is_meta_payload' => $this->isMetaPayload($request),
        ]);

        // Skip message collection entirely for NovaFactu requests
        if ($whatsappMessage && $this->isMetaPayload($request) && !$this->isNovaFactuRequest($message)) {
            \Log::info('Nova webhook: calling waitForMessageCluster');
            $yieldResponse = $this->waitForMessageCluster($whatsappMessage, $phone);

            if ($yieldResponse !== null) {
                \Log::info('Nova webhook: yieldResponse returned, exiting');
                return $yieldResponse;
            }

            \Log::info('Nova webhook: calling collectPendingMessages');
            $message = $this->collectPendingMessages($phone);

            if ($message === '') {
                \Log::info('Nova webhook: empty message after collection, returning');
                return response()->json([
                    'success' => true,
                    'deferred' => true,
                    'message' => 'Message cluster already collected',
                ]);
            }
        } else {
            \Log::info('Nova webhook: skipping message collection (NovaFactu request or non-Meta)', [
                'is_novafactu' => $this->isNovaFactuRequest($message),
                'is_meta' => $this->isMetaPayload($request),
            ]);
        }

        \Log::info('Nova webhook: calling orchestrator', [
            'message' => $message,
            'phone' => $phone,
        ]);

        // Detect if this is a NovaFactu-related request
        $isNovaFactuRequest = $this->isNovaFactuRequest($message);

        if ($isNovaFactuRequest) {
            $result = $orchestrator->runNovaFactuScenario(
                message: $message,
                userPhone: $phone,
                channel: 'whatsapp',
                conversationId: $messageId ?? $phone,
                channelContext: [
                    'message_id' => $messageId,
                    'phone_number_id' => $phoneNumberId,
                    'message_type' => $messageType,
                    'is_meta_payload' => $this->isMetaPayload($request),
                ],
            );
        } else {
            $result = $orchestrator->runLocalTourismScenario(
                message: $message,
                touristPhone: $phone,
                channel: 'whatsapp',
                conversationId: $messageId ?? $phone,
                channelContext: [
                    'message_id' => $messageId,
                    'phone_number_id' => $phoneNumberId,
                    'message_type' => $messageType,
                    'is_meta_payload' => $this->isMetaPayload($request),
                ],
            );
        }
        $sendResult = null;

        \Log::info('Nova webhook: orchestrator result', [
            'phone' => $phone,
            'reply' => $result['reply'] ?? null,
            'nova_request_id' => $result['nova_request_id'] ?? null,
        ]);

        if ($request->boolean('auto_reply', true)) {
            if ($this->isMetaPayload($request)) {
                if ($messageId !== null) {
                    $whatsApp->markAsRead($messageId, $phoneNumberId);
                }
            }

            \Log::info('Nova webhook: sending WhatsApp reply', [
                'phone' => $phone,
                'reply_length' => strlen($result['reply'] ?? ''),
            ]);

            $sendResult = $whatsApp->sendText($phone, $result['reply']);

            \Log::info('Nova webhook: WhatsApp reply sent', [
                'phone' => $phone,
                'send_result' => $sendResult,
            ]);
        }

        \Log::info('Nova webhook: updating status to processed', [
            'phone' => $phone,
            'whatsapp_message_id' => $whatsappMessage?->id,
        ]);

        if ($whatsappMessage) {
            NovaWhatsappMessage::query()
                ->where('from_phone', $phone)
                ->where('status', 'processing')
                ->update([
                    'status' => 'processed',
                    'nova_request_id' => $result['nova_request_id'] ?? null,
                    'processed_at' => now(),
                    'updated_at' => now(),
                ]);
        } else {
            // Update processing messages even without messageId (local demo or non-Meta payload)
            NovaWhatsappMessage::query()
                ->where('from_phone', $phone)
                ->where('status', 'processing')
                ->update([
                    'status' => 'processed',
                    'nova_request_id' => $result['nova_request_id'] ?? null,
                    'processed_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        return response()->json([
            'success' => true,
            'source' => 'nova_whatsapp_webhook',
            'phone' => $phone,
            'message' => $message,
            'reply' => $result['reply'],
            'send_result' => $sendResult,
            'audio_transcription' => $audioTranscriptionResult,
            'nova_request_id' => $result['nova_request_id'],
            'checks' => $result['checks'],
        ]);
    }

    private function extractMessage(Request $request): string
    {
        return trim((string) (
            $request->input('message')
            ?? $request->input('text')
            ?? $request->input('data.message.conversation')
            ?? $request->input('data.message.extendedTextMessage.text')
            ?? $request->input('entry.0.changes.0.value.messages.0.text.body')
            ?? ''
        ));
    }

    private function extractPhone(Request $request): string
    {
        return trim((string) (
            $request->input('phone')
            ?? $request->input('from')
            ?? $request->input('data.key.remoteJid')
            ?? $request->input('entry.0.changes.0.value.messages.0.from')
            ?? 'unknown'
        ));
    }

    private function extractMessageId(Request $request): ?string
    {
        $messageId = $request->input('entry.0.changes.0.value.messages.0.id');

        return is_string($messageId) && $messageId !== '' ? $messageId : null;
    }

    private function extractPhoneNumberId(Request $request): ?string
    {
        $phoneNumberId = $request->input('entry.0.changes.0.value.metadata.phone_number_id');

        return is_string($phoneNumberId) && $phoneNumberId !== '' ? $phoneNumberId : null;
    }

    private function extractMessageType(Request $request): string
    {
        return (string) $request->input('entry.0.changes.0.value.messages.0.type', 'text');
    }

    private function extractAudioMediaId(Request $request): string
    {
        return trim((string) $request->input('entry.0.changes.0.value.messages.0.audio.id', ''));
    }

    private function extractImageMediaId(Request $request): string
    {
        return trim((string) $request->input('entry.0.changes.0.value.messages.0.image.id', ''));
    }

    /**
     * Extract interactive response (button/list selection) from request.
     */
    private function extractInteractiveResponse(Request $request): ?string
    {
        // Check for button response
        $buttonResponse = $request->input('entry.0.changes.0.value.messages.0.interactive.button_reply.id');
        if ($buttonResponse !== null) {
            return $buttonResponse;
        }

        // Check for list response
        $listResponse = $request->input('entry.0.changes.0.value.messages.0.interactive.list_reply.id');
        if ($listResponse !== null) {
            return $listResponse;
        }

        return null;
    }

    /**
     * Handle image message for OCR processing.
     */
    private function handleImageMessage(Request $request, string $phone): array
    {
        $mediaId = $this->extractImageMediaId($request);

        if (empty($mediaId)) {
            return [
                'success' => false,
                'error' => 'No image media ID found',
            ];
        }

        try {
            // Download image from WhatsApp
            $phoneNumberId = $this->extractPhoneNumberId($request);
            $accessToken = (string) config('services.nova.whatsapp_access_token');

            $response = Http::withToken($accessToken)
                ->get("https://graph.facebook.com/v19.0/{$mediaId}");

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => 'Failed to download image from WhatsApp',
                ];
            }

            $imageUrl = $response->json()['url'] ?? null;
            if (!$imageUrl) {
                return [
                    'success' => false,
                    'error' => 'No image URL in response',
                ];
            }

            // Download the actual image
            $imageResponse = Http::withToken($accessToken)->get($imageUrl);
            if (!$imageResponse->successful()) {
                return [
                    'success' => false,
                    'error' => 'Failed to download image content',
                ];
            }

            // Store image temporarily
            $imageContent = $imageResponse->body();
            $fileName = "whatsapp_{$mediaId}_" . time() . '.jpg';
            $storedPath = "temp/{$fileName}";
            Storage::disk('local')->put($storedPath, $imageContent);
            $fullPath = Storage::disk('local')->path($storedPath);

            // Process with OCR
            $ocrAction = new CreateGastoFromReceiptAction();
            $result = $ocrAction->handle($fullPath, 'image/jpeg', $storedPath);

            // Clean up temp file
            Storage::disk('local')->delete($storedPath);

            return [
                'success' => true,
                'imagen' => true,
                'ocr_text' => $result['gasto']->descripcion,
            ];
        } catch (\Throwable $e) {
            \Log::error('OCR processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'OCR processing failed: ' . $e->getMessage(),
            ];
        }
    }

    private function hasValidMetaSignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');
        $appSecret = (string) env('NOVA_META_APP_SECRET');

        if (! $this->isMetaPayload($request) || $signature === null || $appSecret === '') {
            return true;
        }

        $expectedSignature = 'sha256='.hash_hmac('sha256', $request->getContent(), $appSecret);

        return hash_equals($expectedSignature, $signature);
    }

    private function waitForMessageCluster(NovaWhatsappMessage $message, string $phone): ?JsonResponse
    {
        $debounceMs = (int) config('services.nova.whatsapp_debounce_ms', 1800);

        if ($debounceMs > 0) {
            usleep($debounceMs * 1000);
        }

        $hasNewerPendingMessage = NovaWhatsappMessage::query()
            ->where('from_phone', $phone)
            ->where('status', 'received')
            ->where('id', '>', $message->id)
            ->exists();

        if (! $hasNewerPendingMessage) {
            return null;
        }

        return response()->json([
            'success' => true,
            'deferred' => true,
            'message' => 'A newer message will process the cluster',
        ]);
    }

    public function test(Request $request)
    {
dd($request->all());
        $requestData = $request->all();
        \Log::info('Test endpoint called', ['request' => $requestData]);
        return response()->json([
            'success' => true,
            'message' => 'Test endpoint working',
            'data' => $requestData,
        ]);
    }


    private function collectPendingMessages(string $phone): string
    {
        $pendingMessages = NovaWhatsappMessage::query()
            ->where('from_phone', $phone)
            ->where('status', 'received')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        if ($pendingMessages->isEmpty()) {
            return '';
        }

        NovaWhatsappMessage::query()
            ->whereIn('id', $pendingMessages->pluck('id'))
            ->update([
                'status' => 'processing',
                'updated_at' => now(),
            ]);

        return $pendingMessages
            ->pluck('message_text')
            ->filter()
            ->implode('. ');
    }

    private function isMetaPayload(Request $request): bool
    {
        return $request->has('entry.0.changes.0.value.messages.0');
    }

    /**
     * Detect if the message is related to NovaFactu operations.
     */
    private function isNovaFactuRequest(string $message): bool
    {
        $normalized = mb_strtolower($message);

        // Check for numbered responses (any number) - could be client number or menu option
        if (preg_match('/^\s*\d+\s*$/', $normalized)) {
            return true;
        }

        // Check for number with date format (client number, date)
        if (preg_match('/^\s*\d+,\s*\d{1,2}\/\d{1,2}\/\d{2,4}\s*$/', $normalized)) {
            return true;
        }

        // Greetings also trigger NovaFactu menu
        if (in_array($normalized, ['hola', 'buenos días', 'buenas tardes', 'buenas noches', 'hi', 'hello', 'hey'])) {
            return true;
        }

        // Check for amount (€) - likely creating expense or invoice
        if (preg_match('/\d+(?:[.,]\d+)?\s*(?:€|eur|euros)/i', $message)) {
            return true;
        }

        // Check for date format (DD/MM/YYYY) - likely invoice/expense creation
        if (preg_match('/\d{1,2}\/\d{1,2}\/\d{2,4}/', $message)) {
            return true;
        }

        // Check for comma-separated format (name, date, description amount)
        if (preg_match('/^[^,]+,\s*\d{1,2}\/\d{1,2}\/\d{2,4},/', $message)) {
            return true;
        }

        // Check for single word that might be a client name (likely invoice creation)
        $trimmed = trim($message);
        if (preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $trimmed) && strlen($trimmed) > 2) {
            return true;
        }

        // Check for NovaFactu-related keywords
        $novaFactuKeywords = [
            'factura', 'facturas', 'cliente', 'clientes',
            'gasto', 'gastos', 'empresa', 'empresas',
            'cobro', 'pagos', 'facturación', 'billing',
            'mantenimiento', 'servicio', 'alquiler', 'venta'
        ];

        foreach ($novaFactuKeywords as $keyword) {
            if (str_contains($normalized, $keyword)) {
                return true;
            }
        }

        return false;
    }
}
