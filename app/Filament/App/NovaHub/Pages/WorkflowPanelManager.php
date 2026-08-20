<?php

namespace App\Filament\App\NovaHub\Pages;

use Filament\Support\Icons\Heroicon;

use Filament\Pages\Page;
use Illuminate\Contracts\View\View;

class WorkflowPanelManager extends Page
{
    protected  string $view = 'filament.pages.workflow-panel-manager';


    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static string|\UnitEnum|null $navigationGroup = 'MCP';
    protected static ?string $navigationParentGroup = 'IA';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Workflow Panel Manager';

    protected static ?string $slug = 'workflow-panel-manager';

    public function mount(): void
    {
        //
    }

    public function getTitle(): string
    {
        return 'Workflow Panel Manager';
    }

    public function getHeading(): string
    {
        return 'Workflow Panel Manager';
    }

    public static function getNavigationLabel(): string
    {
        return 'Workflow Manager';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('manage_panels') ?? true;
    }

    protected function getViewData(): array
    {
        return [
            'title' => $this->getTitle(),
            'heading' => $this->getHeading(),
        ];
    }

    public function getContent(): View
    {
        return view('livewire.workflow-panel-manager')
            ->with($this->getViewData());
    }
}
