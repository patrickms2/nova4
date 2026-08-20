<?php

declare(strict_types=1);

namespace App\Domain\Nova\Copilot;

use App\Domain\Nova\Copilot\Enums\InputType;
use App\Domain\Nova\Copilot\ValueObjects\Input;
use Illuminate\Http\Request;

final readonly class InputAnalyzer
{
    public function analyze(Request $request, string $channel = 'whatsapp'): Input
    {
        $payload = $request->all();

        $type = $this->detectType($payload);
        $text = $this->extractText($payload, $type);
        $mediaId = $this->extractMediaId($payload, $type);

        return new Input(
            type: $type,
            text: $text,
            channel: $channel,
            mediaId: $mediaId,
            payload: $payload,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function analyzeArray(array $payload, string $channel = 'whatsapp'): Input
    {
        $type = $this->detectType($payload);
        $text = $this->extractText($payload, $type);
        $mediaId = $this->extractMediaId($payload, $type);

        return new Input(
            type: $type,
            text: $text,
            channel: $channel,
            mediaId: $mediaId,
            payload: $payload,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function detectType(array $payload): InputType
    {
        $message = $this->messageNode($payload);

        if ($message === null) {
            return InputType::UNKNOWN;
        }

        if (isset($message['audio'])) {
            return InputType::AUDIO;
        }

        if (isset($message['image'])) {
            return InputType::IMAGE;
        }

        if (isset($message['document'])) {
            return InputType::DOCUMENT;
        }

        if (isset($message['location'])) {
            return InputType::LOCATION;
        }

        if (isset($message['contacts'])) {
            return InputType::CONTACT;
        }

        if (isset($message['text']['body'])) {
            return InputType::TEXT;
        }

        if (isset($message['interactive'])) {
            return InputType::TEXT;
        }

        return InputType::UNKNOWN;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractText(array $payload, InputType $type): string
    {
        if ($type === InputType::TEXT) {
            $message = $this->messageNode($payload);

            if (isset($message['interactive']['button_reply']['id']) && is_string($message['interactive']['button_reply']['id'])) {
                return trim($message['interactive']['button_reply']['id']);
            }

            if (isset($message['interactive']['list_reply']['id']) && is_string($message['interactive']['list_reply']['id'])) {
                return trim($message['interactive']['list_reply']['id']);
            }

            $text = $message['text']['body'] ?? '';

            return is_string($text) ? trim($text) : '';
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractMediaId(array $payload, InputType $type): ?string
    {
        if (! in_array($type, [InputType::AUDIO, InputType::IMAGE, InputType::DOCUMENT], true)) {
            return null;
        }

        $message = $this->messageNode($payload);
        $typeKey = $type->value;

        if (! is_array($message[$typeKey] ?? null)) {
            return null;
        }

        $id = $message[$typeKey]['id'] ?? null;

        return is_string($id) && $id !== '' ? $id : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    private function messageNode(array $payload): ?array
    {
        $entry = $payload['entry'] ?? null;

        if (is_array($entry) && isset($entry[0]['changes'][0]['value']['messages'][0])) {
            $message = $entry[0]['changes'][0]['value']['messages'][0];

            return is_array($message) ? $message : null;
        }

        if (isset($payload['message']) && is_array($payload['message'])) {
            return $payload['message'];
        }

        return null;
    }
}
