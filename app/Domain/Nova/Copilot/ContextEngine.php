<?php

declare(strict_types=1);

namespace App\Domain\Nova\Copilot;

use App\Domain\Nova\Copilot\ValueObjects\ConversationContext;
use Illuminate\Support\Facades\Cache;

final readonly class ContextEngine
{
    private const CACHE_PREFIX = 'nova_copilot_context_';

    private const TTL_SECONDS = 86400;

    public function load(string $phone): ConversationContext
    {
        $data = Cache::get(self::CACHE_PREFIX.$phone, []);

        if (! is_array($data)) {
            $data = [];
        }

        return ConversationContext::fromArray($phone, $data);
    }

    public function save(ConversationContext $context): void
    {
        Cache::put(
            self::CACHE_PREFIX.$context->phone,
            $context->toArray(),
            self::TTL_SECONDS,
        );
    }

    public function clear(string $phone): void
    {
        Cache::forget(self::CACHE_PREFIX.$phone);
    }

    /**
     * @param  array<string, mixed>  $workspace
     */
    public function attachWorkspace(string $phone, array $workspace): ConversationContext
    {
        $context = $this->load($phone)->withWorkspace($workspace);
        $this->save($context);

        return $context;
    }

    public function recordUserMessage(string $phone, string $text): ConversationContext
    {
        $context = $this->load($phone)->recordMessage('user', $text);
        $this->save($context);

        return $context;
    }

    public function recordAssistantMessage(string $phone, string $text): ConversationContext
    {
        $context = $this->load($phone)->recordMessage('assistant', $text);
        $this->save($context);

        return $context;
    }
}
