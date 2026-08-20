<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaAiKnowledgeResource\Pages;

use App\Filament\App\NovaHub\Resources\NovaAiKnowledgeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditNovaAiKnowledge extends EditRecord
{
    protected static string $resource = NovaAiKnowledgeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
