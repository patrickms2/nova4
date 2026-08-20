<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Nova\NovaConversationKernel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class NovaDebugChatController extends Controller
{
    public function index(): View
    {
        return view('livewire.nova.debug-chat');
    }

    public function send(Request $request, NovaConversationKernel $kernel): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:10000'],
            'channel' => ['nullable', 'string', 'max:50'],
            'user' => ['nullable', 'string', 'max:120'],
            'debug' => ['nullable', 'boolean'],
        ]);

        $channel = (string) ($validated['channel'] ?? 'debug');
        $user = (string) ($validated['user'] ?? 'debug-user');

        $result = $kernel->process(
            message: $validated['message'],
            channel: $channel,
            user: $user,
            debug: (bool) ($validated['debug'] ?? true),
        );

        $data = $result->toArray();
        $data['choices'] = $data['response']['menu'] ?? $data['response']['actions'] ?? [];
        $data['input_type'] = $this->detectInputType($data);

        return response()->json($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function detectInputType(array $data): ?string
    {
        $step = (string) ($data['conversation']['current_step'] ?? '');
        $operation = (string) ($data['conversation']['operation'] ?? '');
        $text = mb_strtolower((string) ($data['reply'] ?? ''));

        if (str_contains($step, 'date') || str_contains($text, 'fecha') || str_contains($text, 'día') || str_contains($text, 'cuándo')) {
            return 'date';
        }

        if (str_contains($step, 'participant') || str_contains($text, 'participantes') || str_contains($text, 'personas') || str_contains($text, 'adultos')) {
            return 'participants';
        }

        if (str_contains($step, 'service') || str_contains($operation, 'service') || str_contains($text, 'servicio') || str_contains($text, 'selecciona')) {
            return 'service';
        }

        return null;
    }

    public function export(Request $request, NovaConversationKernel $kernel): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:10000'],
            'channel' => ['nullable', 'string', 'max:50'],
            'user' => ['nullable', 'string', 'max:120'],
        ]);

        $result = $kernel->process(
            message: $validated['message'],
            channel: (string) ($validated['channel'] ?? 'debug'),
            user: (string) ($validated['user'] ?? 'debug-user'),
        );

        $data = $result->toArray();
        $data['choices'] = $data['response']['menu'] ?? $data['response']['actions'] ?? [];
        $data['input_type'] = $this->detectInputType($data);

        return response()->json($data)->withHeaders([
            'Content-Disposition' => 'attachment; filename="conversation.json"',
        ]);
    }
}
