<?php

declare(strict_types=1);

namespace App\Domain\Nova\Copilot\Enums;

enum Confidence: string
{
    case HIGH = 'high';
    case MEDIUM = 'medium';
    case LOW = 'low';

    /**
     * @return array{high: int, medium: int, low: int}
     */
    public static function thresholds(): array
    {
        return [
            'high' => 80,
            'medium' => 50,
            'low' => 0,
        ];
    }

    public static function fromScore(int $score): self
    {
        $thresholds = self::thresholds();

        if ($score >= $thresholds['high']) {
            return self::HIGH;
        }

        if ($score >= $thresholds['medium']) {
            return self::MEDIUM;
        }

        return self::LOW;
    }
}
