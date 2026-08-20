<?php

declare(strict_types=1);

namespace App\Domain\Nova\Copilot;

use App\Domain\Nova\Copilot\Conversations\CapabilityConversation;
use App\Domain\Nova\Copilot\Conversations\ConversationRegistry;
use App\Domain\Nova\Copilot\Conversations\OperationExecutor;
use App\Domain\Nova\Copilot\Enums\ConversationStatus;
use App\Domain\Nova\Copilot\Enums\InputType;
use App\Domain\Nova\Copilot\ValueObjects\Conversation;
use App\Domain\Nova\Copilot\ValueObjects\ConversationResult;
use App\Domain\Nova\Copilot\ValueObjects\Input;
use App\Domain\Nova\Copilot\ValueObjects\Step;

final readonly class ConversationEngine
{
    /**
     * @param  array<int, OperationExecutor>  $executors
     */
    public function __construct(
        private ConversationRegistry $registry,
        private array $executors = [],
        private ?PipelineTracer $tracer = null,
    ) {}

    public function start(string $capability, string $operation): ConversationResult
    {
        $definition = $this->registry->for($capability);
        $conversation = new Conversation(
            capability: $capability,
            operation: $operation,
            currentStep: $definition->startStep($operation) ?? '',
            startedAt: now()->toIso8601String(),
        );

        if ($conversation->currentStep === '') {
            return $this->finish(
                $conversation->withStatus(ConversationStatus::CANCELLED),
                "No hay un flujo definido para {$operation} en {$capability}."
            );
        }

        $step = $this->findStep($definition, $conversation->operation, $conversation->currentStep);

        if ($step !== null && $step->isFinal) {
            return $this->execute($conversation, $definition);
        }

        return $this->prompt($conversation, $definition);
    }

    public function proceed(Conversation $conversation, Input $input): ConversationResult
    {
        $definition = $this->registry->for($conversation->capability);
        $step = $this->findStep($definition, $conversation->operation, $conversation->currentStep);

        if ($step === null) {
            return $this->finish(
                $conversation->withStatus(ConversationStatus::CANCELLED),
                'Paso no encontrado. La conversación se ha cerrado.'
            );
        }

        $canonical = $this->canonicalInput($input, $step);

        if ($canonical === null) {
            return new ConversationResult(
                text: $step->fallbackPrompt ?? 'No entendí la respuesta. Intenta de nuevo.',
                conversation: $conversation->touch(),
            );
        }

        if ($this->isCancel($canonical, $step)) {
            return $this->finish(
                $conversation->withCurrentStep($this->cancelStep($definition, $conversation->operation) ?? '')->withStatus(ConversationStatus::CANCELLED),
                $this->cancelMessage($definition, $conversation->operation)
            );
        }

        $data = $conversation->data;
        $data[$step->key] = $input->text;
        $data = $this->extractEntities($input, $data);

        $nextStepKey = $this->resolveNextStep($canonical, $step, $definition);

        if ($nextStepKey === null) {
            return new ConversationResult(
                text: $step->fallbackPrompt ?? 'No entendí la respuesta. Intenta de nuevo.',
                conversation: $conversation->touch(),
            );
        }

        $conversation = $conversation->withData($data)->withCurrentStep($nextStepKey)->touch();

        if ($this->isCancelStep($definition, $conversation->operation, $nextStepKey)) {
            return $this->finish(
                $conversation->withStatus(ConversationStatus::CANCELLED),
                $this->stepPrompt($definition, $conversation->operation, $nextStepKey) ?? 'Operación cancelada.'
            );
        }

        $nextStep = $this->findStep($definition, $conversation->operation, $nextStepKey);

        if ($nextStep !== null && $nextStep->isFinal) {
            return $this->execute($conversation, $definition);
        }

        return $this->prompt($conversation, $definition);
    }

    private function prompt(Conversation $conversation, CapabilityConversation $definition): ConversationResult
    {
        $step = $this->findStep($definition, $conversation->operation, $conversation->currentStep);
        $text = $step?->prompt ?? '¿Cómo puedo ayudarte?';

        return new ConversationResult(
            text: $text,
            conversation: $conversation,
            menu: $this->menuFor($step),
        );
    }

    private function execute(Conversation $conversation, CapabilityConversation $definition): ConversationResult
    {
        $this->tracer?->start('handler');

        foreach ($this->executors as $executor) {
            if ($executor->supports($conversation->capability, $conversation->operation)) {
                $result = $executor->execute(
                    $conversation->capability,
                    $conversation->operation,
                    $conversation->data,
                    ''
                );

                $this->tracer?->finish('handler', [
                    'capability' => $conversation->capability,
                    'operation' => $conversation->operation,
                    'handler' => get_class($executor),
                ]);

                return $this->finish(
                    $conversation->withStatus(ConversationStatus::COMPLETED),
                    $result['reply'] ?? 'Operación completada.'
                );
            }
        }

        $this->tracer?->finish('handler', [
            'capability' => $conversation->capability,
            'operation' => $conversation->operation,
            'handler' => null,
        ]);

        return $this->finish(
            $conversation->withStatus(ConversationStatus::COMPLETED),
            'Operación reconocida, pero no hay un ejecutor configurado para completarla.'
        );
    }

    private function finish(Conversation $conversation, string $text): ConversationResult
    {
        return new ConversationResult(
            text: $text,
            conversation: $conversation,
        );
    }

    private function findStep(CapabilityConversation $definition, string $operation, string $key): ?Step
    {
        foreach ($definition->definition()[$operation] ?? [] as $step) {
            if ($step->key === $key) {
                return $step;
            }
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function menuFor(?Step $step): array
    {
        if ($step === null) {
            return [];
        }

        $items = [];

        foreach ($step->acceptedInputs as $input) {
            $items[] = ['id' => $input, 'label' => $input];
        }

        foreach ($step->acceptedNumbers as $number) {
            $items[] = ['id' => (string) $number, 'label' => (string) $number];
        }

        return $items;
    }

    private function canonicalInput(Input $input, Step $step): ?string
    {
        if ($input->type === InputType::IMAGE && $step->acceptsImage) {
            return 'image';
        }

        if ($input->type === InputType::AUDIO && $step->acceptsAudio) {
            return 'audio';
        }

        if ($input->type === InputType::DOCUMENT && $step->acceptsDocument) {
            return 'document';
        }

        $normalized = mb_strtolower(trim($input->text));

        if (array_key_exists($normalized, $step->acceptedSynonyms)) {
            return $step->acceptedSynonyms[$normalized];
        }

        if (in_array($normalized, array_map('mb_strtolower', $step->acceptedInputs), true)) {
            return $normalized;
        }

        if (is_numeric($normalized)) {
            $number = (int) $normalized;

            if (in_array($number, $step->acceptedNumbers, true)) {
                return (string) $number;
            }
        }

        if ($step->acceptedInputs === [] && $step->acceptedNumbers === []) {
            return $normalized;
        }

        return null;
    }

    private function isCancel(string $canonical, Step $step): bool
    {
        return in_array($canonical, ['cancel', 'cancelar', 'no'], true)
            || $step->isCancel;
    }

    private function resolveNextStep(string $canonical, Step $step, CapabilityConversation $definition): ?string
    {
        if (array_key_exists($canonical, $step->branches)) {
            $target = $step->branches[$canonical];

            return $target === null ? null : $target;
        }

        if (array_key_exists('_default', $step->branches)) {
            return $step->branches['_default'];
        }

        return $step->nextStep;
    }

    private function isCancelStep(CapabilityConversation $definition, string $operation, string $key): bool
    {
        $step = $this->findStep($definition, $operation, $key);

        return $step !== null && $step->isCancel;
    }

    private function cancelStep(CapabilityConversation $definition, string $operation): ?string
    {
        foreach ($definition->definition()[$operation] ?? [] as $step) {
            if ($step->isCancel) {
                return $step->key;
            }
        }

        return null;
    }

    private function cancelMessage(CapabilityConversation $definition, string $operation): string
    {
        foreach ($definition->definition()[$operation] ?? [] as $step) {
            if ($step->isCancel) {
                return $step->prompt;
            }
        }

        return 'Operación cancelada.';
    }

    private function stepPrompt(CapabilityConversation $definition, string $operation, string $key): ?string
    {
        foreach ($definition->definition()[$operation] ?? [] as $step) {
            if ($step->key === $key) {
                return $step->prompt;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function extractEntities(Input $input, array $data): array
    {
        if ($input->type !== InputType::TEXT) {
            return $data;
        }

        $text = $input->text;

        if (preg_match('/(\d+(?:[.,]\d+)?)\s*(?:€|eur|euros)/i', $text, $matches)) {
            $data['extracted_amount'] = (float) str_replace(',', '.', $matches[1]);
        }

        if (preg_match('/(\d{1,2})[\/-](\d{1,2})[\/-](\d{2,4})/', $text, $matches)) {
            $year = strlen($matches[3]) === 2 ? '20' . $matches[3] : $matches[3];
            $data['extracted_date'] = "{$year}-{$matches[2]}-{$matches[1]}";
        }

        if (! isset($data['extracted_description'])) {
            $parts = preg_split('/[,\.]/', $text, 2);
            if (! empty($parts[0])) {
                $data['extracted_description'] = trim($parts[0]);
            }
        }

        return $data;
    }
}
