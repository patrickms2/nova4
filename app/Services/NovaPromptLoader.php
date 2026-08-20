<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Prompt;

/**
 * Loads editable MCP Prompt records for use inside App\Services\Nova classes.
 *
 * Usage:
 *   $text = NovaPromptLoader::system('nova-intent-detection', $this->defaultPrompt());
 *
 * If the named prompt exists and is active, its first `system` message content
 * is returned. Otherwise the $fallback string is returned, allowing services to
 * keep working even before prompts are installed via Filament.
 */
final class NovaPromptLoader
{
    /** @var array<string, string> */
    private static array $cache = [];

    /**
     * Return the content of the first `system` message of the named prompt.
     * Falls back to $fallback when the prompt is not installed or inactive.
     */
    public static function system(string $name, string $fallback = ''): string
    {
        if (array_key_exists($name, self::$cache)) {
            return self::$cache[$name];
        }

        $prompt = Prompt::query()
            ->where('name', $name)
            ->where('is_active', true)
            ->first(['messages']);

        if ($prompt === null) {
            return $fallback;
        }

        $content = collect($prompt->messages ?? [])
            ->firstWhere('role', 'system');

        $text = is_array($content) ? ($content['content'] ?? '') : '';

        self::$cache[$name] = $text !== '' ? $text : $fallback;

        return self::$cache[$name];
    }

    /**
     * Return the full messages array of the named prompt.
     * Falls back to an empty array when the prompt is not installed.
     *
     * @return array<int, array{role: string, content: string}>
     */
    public static function messages(string $name): array
    {
        $prompt = Prompt::query()
            ->where('name', $name)
            ->where('is_active', true)
            ->first(['messages']);

        return $prompt?->messages ?? [];
    }

    /**
     * Clear the in-memory cache (useful in tests or after reinstalling prompts).
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }
}
