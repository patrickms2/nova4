<?php

declare(strict_types=1);

namespace Tests\Unit\Nova\Copilot;

use App\Domain\Nova\Copilot\ConversationEngine;
use App\Domain\Nova\Copilot\Conversations\ConversationRegistry;
use App\Domain\Nova\Copilot\Conversations\OperationExecutor;
use App\Domain\Nova\Copilot\Enums\ConversationStatus;
use App\Domain\Nova\Copilot\Enums\InputType;
use App\Domain\Nova\Copilot\ValueObjects\Input;
use Tests\TestCase;

final class ConversationEngineTest extends TestCase
{
    public function test_starts_create_expense_conversation(): void
    {
        $engine = $this->engine();

        $result = $engine->start('expenses', 'create');

        $this->assertSame('expenses', $result->conversation->capability);
        $this->assertSame('create', $result->conversation->operation);
        $this->assertSame('details', $result->conversation->currentStep);
        $this->assertSame(ConversationStatus::ACTIVE, $result->conversation->status);
        $this->assertStringContainsString('registrar', $result->text);
    }

    public function test_starts_list_invoices_conversation_and_executes_immediately(): void
    {
        $executor = new class implements OperationExecutor
        {
            public function execute(string $capability, string $operation, array $data, string $phone): array
            {
                return ['reply' => "Listado de {$capability}"];
            }

            public function supports(string $capability, string $operation): bool
            {
                return $capability === 'invoices' && $operation === 'consult';
            }
        };

        $engine = new ConversationEngine(new ConversationRegistry(), [$executor]);

        $result = $engine->start('invoices', 'consult');

        $this->assertSame(ConversationStatus::COMPLETED, $result->conversation->status);
        $this->assertSame('Listado de invoices', $result->text);
    }

    public function test_proceeds_create_conversation_to_confirmation(): void
    {
        $engine = $this->engine();

        $start = $engine->start('expenses', 'create');
        $result = $engine->proceed(
            $start->conversation,
            new Input(InputType::TEXT, 'gasolina 50€', 'whatsapp')
        );

        $this->assertSame('confirm', $result->conversation->currentStep);
        $this->assertSame(ConversationStatus::ACTIVE, $result->conversation->status);
        $this->assertSame(50.0, $result->conversation->data['extracted_amount']);
        $this->assertStringContainsString('Confirmas', $result->text);
    }

    public function test_cancels_conversation(): void
    {
        $engine = $this->engine();

        $start = $engine->start('expenses', 'create');
        $afterDetail = $engine->proceed(
            $start->conversation,
            new Input(InputType::TEXT, 'gasolina 50€', 'whatsapp')
        );
        $result = $engine->proceed(
            $afterDetail->conversation,
            new Input(InputType::TEXT, 'cancelar', 'whatsapp')
        );

        $this->assertSame(ConversationStatus::CANCELLED, $result->conversation->status);
        $this->assertStringContainsString('cancelada', mb_strtolower($result->text));
    }

    public function test_unknown_capability_uses_generic_crud_conversation(): void
    {
        $engine = $this->engine();

        $result = $engine->start('tours', 'consult');

        $this->assertSame('tours', $result->conversation->capability);
        $this->assertSame('list', $result->conversation->currentStep);
        $this->assertSame(ConversationStatus::COMPLETED, $result->conversation->status);
        $this->assertStringContainsString('no hay un ejecutor', mb_strtolower($result->text));
    }

    private function engine(): ConversationEngine
    {
        return new ConversationEngine(new ConversationRegistry(), []);
    }
}
