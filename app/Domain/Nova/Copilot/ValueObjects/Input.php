<?php

declare(strict_types=1);

namespace App\Domain\Nova\Copilot\ValueObjects;

use App\Domain\Nova\Copilot\Enums\InputType;

final readonly class Input
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public InputType $type,
        public string $text,
        public string $channel,
        public ?string $mediaId = null,
        public array $payload = [],
    ) {}

    public function isGreeting(): bool
    {
        $greetings = ['hola', 'buenos días', 'buenas tardes', 'buenas noches', 'hi', 'hello', 'hey'];

        return in_array(mb_strtolower(trim($this->text)), $greetings, true);
    }

    public function isPowerMenuTrigger(): bool
    {
        return in_array(mb_strtolower(trim($this->text)), ['menu', '?', 'opciones', 'debug', 'power'], true);
    }
}
