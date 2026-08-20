<?php

namespace App\Filament\Resources\PromptResource\Pages;

use Filament\Support\Icons\Heroicon;

use App\Filament\Resources\PromptResource;
use App\Services\NovaPromptLoader;
use App\Services\NovaServicesPromptCatalog;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListPrompts extends ListRecords
{
    protected static string $resource = PromptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('install_nova_prompts')
                ->label('Install Nova Prompts')
                ->icon(Heroicon::ArrowDownTray)
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Install Nova Service Prompts')
                ->modalDescription('This will install the editable MCP prompts used by App\\Services\\Nova classes (intent detection, booking extraction, response generation, Ollama, cross-selling, orchestrator) under a new "Nova Services" server. Already installed prompts will be skipped.')
                ->modalSubmitActionLabel('Install')
                ->action(function (): void {
                    $result = app(NovaServicesPromptCatalog::class)->install();

                    NovaPromptLoader::clearCache();

                    Notification::make()
                        ->title('Nova prompts installed')
                        ->body("Installed: {$result['installed']} · Skipped: {$result['skipped']} · Server: {$result['server']->name}")
                        ->success()
                        ->send();
                }),

            Actions\Action::make('reinstall_nova_prompts')
                ->label('Reinstall Nova Prompts')
                ->icon(Heroicon::ArrowPath)
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Reinstall Nova Service Prompts')
                ->modalDescription('This will DELETE and recreate all Nova service prompts, resetting them to their default content. Any manual edits will be lost.')
                ->modalSubmitActionLabel('Reinstall')
                ->action(function (): void {
                    $result = app(NovaServicesPromptCatalog::class)->reinstall();

                    NovaPromptLoader::clearCache();

                    Notification::make()
                        ->title('Nova prompts reinstalled')
                        ->body("Updated: {$result['updated']} prompts · Server: {$result['server']->name}")
                        ->warning()
                        ->send();
                }),

            Actions\CreateAction::make(),
        ];
    }
}

