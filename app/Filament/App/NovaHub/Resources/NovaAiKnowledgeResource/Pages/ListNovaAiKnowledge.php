<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaAiKnowledgeResource\Pages;

use App\Filament\App\NovaHub\Resources\NovaAiKnowledgeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListNovaAiKnowledge extends ListRecords
{
    protected static string $resource = NovaAiKnowledgeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo fragmento'),
        ];
    }
}
