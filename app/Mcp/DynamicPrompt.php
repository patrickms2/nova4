<?php

namespace App\Mcp;

use App\Models\Prompt as PromptModel;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

class DynamicPrompt extends Prompt
{
    protected PromptModel $promptModel;

    public function __construct(PromptModel $promptModel)
    {
        $this->promptModel = $promptModel;
        $this->name = $promptModel->name;
        $this->title = $promptModel->title;
        $this->description = $promptModel->description;
    }

    public function arguments(): array
    {
        return collect($this->promptModel->arguments ?? [])
            ->map(fn ($arg) => new Argument(
                name: $arg['name'],
                description: $arg['description'] ?? '',
                required: $arg['required'] ?? false,
            ))
            ->all();
    }

    public function handle(Request $request): array
    {
        $messages = $this->promptModel->messages ?? [];
        $params = $request->all();

        return collect($messages)->map(function ($msg) use ($params) {
            $content = $msg['content'];

            // Replace placeholders like {tone} with actual values
            foreach ($params as $key => $value) {
                $content = str_replace("{{$key}}", $value, $content);
            }

            $response = Response::text($content);

            if (($msg['role'] ?? 'user') === 'assistant') {
                $response->asAssistant();
            }

            return $response;
        })->all();
    }
}
