<?php

namespace App\Filament\Resources\AgentWorkflows\Pages;

use Filament\Support\Icons\Heroicon;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Heiner\FilamentAgenticChatbot\Exceptions\WorkflowActivationException;
use App\Filament\Resources\AgentWorkflows\AgentWorkflowResource;
use Heiner\FilamentAgenticChatbot\Models\AgentWorkflow;
use Heiner\FilamentAgenticChatbot\Models\RagBot;
use Heiner\FilamentAgenticChatbot\Support\UiText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ListAgentWorkflows extends ListRecords
{
    protected static string $resource = AgentWorkflowResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    /**
     * @var array<int, AgentWorkflow|null>
     */
    protected array $runtimeLiveWorkflowCache = [];

    public function getSubheading(): string
    {
        $totalCount = AgentWorkflow::query()->count();
        $liveCount = AgentWorkflow::query()->where('is_active', true)->count();
        $conflictCount = $this->routingConflictCount();
        $summary = $this->formatHeaderCount($totalCount, 'workflow').' · '.$this->formatHeaderCount($liveCount, 'live workflow');

        return $conflictCount > 0
            ? $summary.'. '.$this->formatHeaderCount($conflictCount, 'routing conflict').($conflictCount === 1 ? ' needs' : ' need').' attention.'
            : $summary.'. Draft, publish, and assign chat workflows.';
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                $this->normalizeLiveRoutingAction(),
            ])
                ->label(UiText::raw('Actions'))
                ->icon(Heroicon::EllipsisHorizontal)
                ->color('gray')
                ->visible(fn (): bool => $this->routingConflictCount() > 0)
                ->tooltip(UiText::raw('Workflow maintenance actions.'))
                ->dropdownPlacement('bottom-end')
                ->dropdownWidth(Width::Small)
                ->labeledFrom('md')
                ->button(),
            CreateAction::make()
                ->label(UiText::raw('New Workflow'))
                ->icon(Heroicon::OutlinedPlusCircle)
                ->color('primary')
                ->tooltip(UiText::raw('Start a new workflow draft. It stays a draft until you publish it and optionally assign it to a bot.')),
        ];
    }

    protected function normalizeLiveRoutingAction(): Action
    {
        return Action::make('normalizeLiveRouting')
            ->label(UiText::raw('Fix Routing Conflicts'))
            ->icon(Heroicon::OutlinedWrenchScrewdriver)
            ->color('warning')
            ->tooltip(UiText::raw('Keeps one live workflow per bot by disabling extra enabled workflows that chat would ignore.'))
            ->requiresConfirmation()
            ->modalHeading(UiText::raw('Fix routing conflicts?'))
            ->modalDescription(UiText::raw('If a bot has multiple enabled workflows, keep only the single workflow chat would actually use and disable the rest.'))
            ->modalSubmitActionLabel(UiText::raw('Fix conflicts'))
            ->action(function (): void {
                $deactivatedCount = 0;

                $botIds = AgentWorkflow::query()
                    ->whereNotNull('rag_bot_id')
                    ->where('is_active', true)
                    ->groupBy('rag_bot_id')
                    ->havingRaw('COUNT(*) > 1')
                    ->pluck('rag_bot_id');

                foreach ($botIds as $botId) {
                    $winner = AgentWorkflow::query()
                        ->where('rag_bot_id', $botId)
                        ->where('is_active', true)
                        ->orderByDesc('updated_at')
                        ->orderByDesc('id')
                        ->first();

                    if (! $winner instanceof AgentWorkflow) {
                        continue;
                    }

                    $deactivatedCount += AgentWorkflow::query()
                        ->where('rag_bot_id', $botId)
                        ->where('is_active', true)
                        ->whereKeyNot($winner->getKey())
                        ->update(['is_active' => false]);
                }

                $this->runtimeLiveWorkflowCache = [];

                Notification::make()
                    ->title($deactivatedCount > 0
                        ? "Normalized {$deactivatedCount} overridden workflow(s)."
                        : 'No live-routing conflicts found.')
                    ->success()
                    ->send();
            });
    }

    protected function routingConflictCount(): int
    {
        return AgentWorkflow::query()
            ->select('rag_bot_id')
            ->whereNotNull('rag_bot_id')
            ->where('is_active', true)
            ->groupBy('rag_bot_id')
            ->havingRaw('COUNT(*) > 1')
            ->toBase()
            ->getCountForPagination();
    }

    protected function formatHeaderCount(int $count, string $singular, ?string $plural = null): string
    {
        return number_format($count).' '.($count === 1 ? $singular : ($plural ?? $singular.'s'));
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(UiText::raw('Workflow'))
                    ->searchable()
                    ->sortable()
                    ->limit(36)
                    ->wrap(false)
                    ->tooltip(fn (AgentWorkflow $record): string => $this->workflowTooltip($record)),

                TextColumn::make('workflow_scope')
                    ->label(UiText::raw('Scope'))
                    ->width('9rem')
                    ->grow(false)
                    ->badge()
                    ->state(fn (AgentWorkflow $record): string => $this->workflowScopeLabel($record))
                    ->color(fn (AgentWorkflow $record): string => $this->workflowScopeColor($record)),

                TextColumn::make('description')
                    ->label(UiText::raw('Description'))
                    ->limit(50)
                    ->wrap(false)
                    ->tooltip(fn (?string $state): ?string => $state)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('ragBot.name')
                    ->label(UiText::raw('Bot'))
                    ->placeholder('—')
                    ->width('18rem')
                    ->grow(false)
                    ->limit(32)
                    ->wrap(false)
                    ->tooltip(fn (?string $state): ?string => $state),

                TextColumn::make('chat_routing_status')
                    ->label(UiText::raw('Routing'))
                    ->width('10rem')
                    ->grow(false)
                    ->badge()
                    ->state(fn (AgentWorkflow $record): string => $this->chatRoutingStatusLabel($record))
                    ->color(fn (AgentWorkflow $record): string => $this->chatRoutingStatusColor($record))
                    ->tooltip(fn (AgentWorkflow $record): ?string => $this->chatRoutingStatusDescription($record)),

                IconColumn::make('is_active')
                    ->label(UiText::raw('Enabled'))
                    ->width('6rem')
                    ->grow(false)
                    ->alignCenter()
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label(UiText::raw('Created'))
                    ->since()
                    ->sortable()
                    ->width('8rem')
                    ->grow(false)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label(UiText::raw('Updated'))
                    ->since()
                    ->sortable()
                    ->width('8rem')
                    ->grow(false),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                SelectFilter::make('scope')
                    ->label(UiText::raw('Scope'))
                    ->options([
                        'public' => UiText::raw('Public Demo'),
                        'internal' => UiText::raw('Internal Ops'),
                        'archived' => UiText::raw('Archived / Reference'),
                        'unassigned' => UiText::raw('Unassigned'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $this->applyScopeFilter($query, $data['value'] ?? null)),
                TrashedFilter::make(),
            ])
            ->actions([
                
            Action::make('testWorkflow')
            ->label('Test Workflow')
            ->icon(Heroicon::OutlinedPlay)
            ->color('primary')
            ->url(fn (AgentWorkflow $record) => route('filament.admin.pages.workflow-chat', [
                'workflow' => $record->id,
                'bot' => $record->rag_bot_id,
            ]))
            ->openUrlInNewTab(),

                ActionGroup::make([
                    Action::make('changeBot')
                        ->label(fn (AgentWorkflow $record) => $record->ragBot ? 'Change Assignment' : 'Assign Bot')
                        ->icon(Heroicon::OutlinedArrowPath)
                        ->color(fn (AgentWorkflow $record) => $record->ragBot ? 'gray' : 'warning')
                        ->modalHeading(UiText::raw('Assign Bot'))
                        ->modalDescription(UiText::raw('Assign which bot should use this workflow\'s AI settings and knowledge base. Each bot can route chat to only one enabled workflow at a time.'))
                        ->modalSubmitActionLabel(UiText::raw('Save Assignment'))
                        ->fillForm(fn (AgentWorkflow $record): array => [
                            'rag_bot_id' => $record->rag_bot_id,
                        ])
                        ->schema([
                            Select::make('rag_bot_id')
                                ->label(UiText::raw('Assigned Bot'))
                                ->options(config('filament-agentic-chatbot.eloquent_models.bot', RagBot::class)::pluck('name', 'id'))
                                ->searchable()
                                ->nullable()
                                ->placeholder(UiText::raw('No bot assigned'))
                                ->helperText(UiText::raw('If this workflow is already enabled, assigning a bot makes it that bot\'s single live chat workflow.')),
                        ])
                        ->action(function (AgentWorkflow $record, array $data): void {
                            $record->update([
                                'rag_bot_id' => $data['rag_bot_id'],
                            ]);

                            $record->refresh();

                            Notification::make()
                                ->title($this->botAssignmentNotificationTitle($record))
                                ->success()
                                ->send();
                        }),
                    Action::make('toggleActive')
                        ->label(fn (AgentWorkflow $record) => $record->is_active ? 'Disable' : 'Enable')
                        ->icon(fn (AgentWorkflow $record) => $record->is_active ? Heroicon::OutlinedPauseCircle : Heroicon::OutlinedPlayCircle)
                        ->color(fn (AgentWorkflow $record) => $record->is_active ? 'gray' : 'success')
                        ->requiresConfirmation()
                        ->modalHeading(fn (AgentWorkflow $record) => $record->is_active
                            ? 'Disable workflow?'
                            : ($record->canBeActivated() ? 'Enable workflow?' : 'Publish before going live'))
                        ->modalDescription(fn (AgentWorkflow $record) => $this->workflowToggleDescription($record))
                        ->modalSubmitActionLabel(fn (AgentWorkflow $record) => $record->is_active
                            ? UiText::raw('Disable')
                            : ($record->canBeActivated() ? UiText::raw('Enable') : UiText::raw('Understood')))
                        ->action(function (AgentWorkflow $record): void {
                            try {
                                $record->update(['is_active' => ! $record->is_active]);
                                $record->refresh();

                                Notification::make()
                                    ->title($record->is_active ? UiText::raw('Workflow enabled') : UiText::raw('Workflow disabled'))
                                    ->success()
                                    ->send();
                            } catch (WorkflowActivationException $e) {
                                $record->refresh();

                                Notification::make()
                                    ->title(UiText::raw('Workflow not activated'))
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    EditAction::make()
                        ->hidden(fn (AgentWorkflow $record): bool => $record->trashed()),
                    DeleteAction::make()
                        ->hidden(fn (AgentWorkflow $record): bool => $record->trashed()),
                    RestoreAction::make(),
                    ForceDeleteAction::make(),
                ])
                    ->label(UiText::raw('Actions'))
                    ->icon(Heroicon::EllipsisVertical)
                    ->color('gray')
                    ->tooltip(UiText::raw('Workflow actions')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->deselectRecordsAfterCompletion(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make()->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    protected function chatRoutingStatusLabel(AgentWorkflow $record): string
    {
        if (! $record->hasPublishedWorkflow()) {
            return 'Draft';
        }

        if (! $record->rag_bot_id) {
            return 'No bot';
        }

        if (! $record->is_active) {
            return 'Not live';
        }

        return $this->isRuntimeLiveWorkflow($record) ? 'Live' : 'Not live';
    }

    protected function workflowTooltip(AgentWorkflow $record): string
    {
        $parts = [$record->name];

        $description = trim((string) $record->description);
        if ($description !== '') {
            $parts[] = $description;
        }

        return implode("\n", $parts);
    }

    protected function chatRoutingStatusColor(AgentWorkflow $record): string
    {
        if (! $record->hasPublishedWorkflow()) {
            return 'warning';
        }

        if (! $record->rag_bot_id) {
            return 'gray';
        }

        if (! $record->is_active) {
            return 'gray';
        }

        return $this->isRuntimeLiveWorkflow($record) ? 'success' : 'danger';
    }

    protected function chatRoutingStatusDescription(AgentWorkflow $record): ?string
    {
        if (! $record->hasPublishedWorkflow() || ! $record->rag_bot_id || ! $record->is_active || $this->isRuntimeLiveWorkflow($record)) {
            return null;
        }

        $winner = $this->runtimeLiveWorkflowForBot($record->rag_bot_id);

        if (! $winner instanceof AgentWorkflow) {
            return null;
        }

        return "Runtime currently routes to #{$winner->getKey()} \"{$winner->name}\".";
    }

    protected function botAssignmentNotificationTitle(AgentWorkflow $record): string
    {
        $botName = $record->ragBot?->name;

        if ($botName === null) {
            return 'Bot assignment cleared';
        }

        if ($record->is_active && $record->canBeActivated()) {
            return "Bot assigned: {$botName}. This workflow is now live in chat.";
        }

        if ($record->is_active) {
            return "Bot assigned: {$botName}. Publish the workflow before it can go live.";
        }

        return UiText::raw('Bot assigned: :bot', ['bot' => $botName]);
    }

    protected function workflowToggleDescription(AgentWorkflow $record): string
    {
        $bot = $record->ragBot;

        if ($record->is_active) {
            if ($bot instanceof RagBot) {
                return UiText::raw('This removes the workflow from live chat for :bot. The bot will not route chat here again until this workflow is re-enabled.', [
                    'bot' => $bot->name,
                ]);
            }

            return UiText::raw('This disables the workflow. No bot will route chat here until it is enabled again.');
        }

        if (! $record->canBeActivated()) {
            return $record->activationGuardMessage();
        }

        if (! $bot instanceof RagBot) {
            return UiText::raw('This enables the workflow, but no chat widget will use it until a bot is assigned.');
        }

        $currentLiveWorkflow = $bot->activeWorkflow();

        if ($currentLiveWorkflow instanceof AgentWorkflow && $currentLiveWorkflow->getKey() !== $record->getKey()) {
            return UiText::raw('This makes the workflow live for :bot and replaces workflow #:workflow_id ":workflow".', [
                'bot' => $bot->name,
                'workflow_id' => $currentLiveWorkflow->getKey(),
                'workflow' => $currentLiveWorkflow->name,
            ]);
        }

        return UiText::raw('This makes the workflow the single live chat workflow for :bot.', ['bot' => $bot->name]);
    }

    protected function workflowScopeLabel(AgentWorkflow $record): string
    {
        return match ($this->workflowScopeKey($record)) {
            'public' => UiText::raw('Public Demo'),
            'internal' => UiText::raw('Internal Ops'),
            'archived' => UiText::raw('Archived'),
            'unassigned' => UiText::raw('Unassigned'),
            default => UiText::raw('Unknown'),
        };
    }

    protected function workflowScopeColor(AgentWorkflow $record): string
    {
        return match ($this->workflowScopeKey($record)) {
            'public' => 'success',
            'internal' => 'warning',
            'archived' => 'gray',
            'unassigned' => 'danger',
            default => 'gray',
        };
    }

    protected function workflowScopeKey(AgentWorkflow $record): string
    {
        if (! $record->rag_bot_id) {
            return 'unassigned';
        }

        if ($this->isPublicDemoWorkflow($record)) {
            return 'public';
        }

        if ($this->isInternalWorkflow($record)) {
            return 'internal';
        }

        return 'archived';
    }

    /**
     * @param  Builder<AgentWorkflow>  $query
     * @return Builder<AgentWorkflow>
     */
    protected function applyScopeFilter(Builder $query, ?string $scope): Builder
    {
        if (! in_array($scope, ['public', 'internal', 'archived', 'unassigned'], true)) {
            return $query;
        }

        $matchingWorkflowIds = AgentWorkflow::query()
            ->with('ragBot')
            ->get()
            ->filter(fn (AgentWorkflow $record): bool => $this->workflowScopeKey($record) === $scope)
            ->pluck('id')
            ->all();

        if ($matchingWorkflowIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereKey($matchingWorkflowIds);
    }

    protected function isPublicDemoWorkflow(AgentWorkflow $record): bool
    {
        $bot = $record->ragBot;

        if (! $bot instanceof RagBot || ! $this->isPublicDemoBot($bot)) {
            return false;
        }

        return $this->isRuntimeLiveWorkflow($record);
    }

    protected function isInternalWorkflow(AgentWorkflow $record): bool
    {
        $name = Str::lower($record->name);
        $description = Str::lower((string) $record->description);
        $botName = Str::lower((string) $record->ragBot?->name);

        return Str::contains($botName, ['internal', 'ops'])
            || Str::contains($name, ['internal', 'ops'])
            || Str::contains($description, ['internal admins', 'internal admin', 'internal ops']);
    }

    protected function isPublicDemoBot(RagBot $bot): bool
    {
        return data_get($bot->rag_config, 'demo.is_public_demo', false) === true;
    }

    protected function isRuntimeLiveWorkflow(AgentWorkflow $record): bool
    {
        if (! $record->rag_bot_id || ! $record->is_active) {
            return false;
        }

        return $this->runtimeLiveWorkflowForBot($record->rag_bot_id)?->getKey() === $record->getKey();
    }

    protected function runtimeLiveWorkflowForBot(int $botId): ?AgentWorkflow
    {
        if (array_key_exists($botId, $this->runtimeLiveWorkflowCache)) {
            return $this->runtimeLiveWorkflowCache[$botId];
        }

        return $this->runtimeLiveWorkflowCache[$botId] = AgentWorkflow::query()
            ->where('rag_bot_id', $botId)
            ->where('is_active', true)
            ->whereNotNull('workflow_data')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();
    }
}
