<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaAiKnowledgeResource\Pages;

use App\Filament\App\NovaHub\Resources\NovaAiKnowledgeResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateNovaAiKnowledge extends CreateRecord
{
    protected static string $resource = NovaAiKnowledgeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
