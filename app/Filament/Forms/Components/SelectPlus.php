<?php

namespace App\Filament\Forms\Components;

use Closure;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select as BaseSelect;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SelectPlus extends BaseSelect
{
    protected ?string $modelClass = null;
    protected ?string $relationshipName = null;

    protected string $labelColumn = 'nombre';
    protected string $valueColumn = 'id';

    protected mixed $createForm = null;
    protected mixed $editForm = null;

    protected ?Closure $mutateCreateData = null;
    protected ?Closure $mutateEditData = null;

    protected ?string $groupByColumn = null;
    protected ?string $groupLabelColumn = null;

    protected ?Model $currentOptionRecord = null;

    public function forRelationship(string $relationshipName, string $labelColumn = 'nombre', string $valueColumn = 'id'): static
    {
        $this->relationshipName = $relationshipName;
        $this->labelColumn = $labelColumn;
        $this->valueColumn = $valueColumn;

        parent::relationship($relationshipName, $labelColumn);

        return $this->searchable()->native(false);
    }

    public function forModel(string $modelClass, string $labelColumn = 'nombre', string $valueColumn = 'id'): static
    {
        $this->modelClass = $modelClass;
        $this->labelColumn = $labelColumn;
        $this->valueColumn = $valueColumn;

        $this->refreshOptions();

        return $this->searchable()->native(false);
    }

    public function groupBy(string $groupByColumn, ?string $groupLabelColumn = null): static
    {
        $this->groupByColumn = $groupByColumn;
        $this->groupLabelColumn = $groupLabelColumn;

        $this->refreshOptions();

        return $this;
    }

    public function createForm(array|Closure $schema): static
    {
        $this->createForm = $schema;

        return $this;
    }

    public function editForm(array|Closure $schema): static
    {
        $this->editForm = $schema;

        return $this;
    }

    public function mutateCreateData(Closure $callback): static
    {
        $this->mutateCreateData = $callback;

        return $this;
    }

    public function mutateEditData(Closure $callback): static
    {
        $this->mutateEditData = $callback;

        return $this;
    }

    public function smartPreload(int $threshold = 250): static
    {
        try {
            $count = null;

            if ($this->modelClass) {
                $class = $this->modelClass;
                $count = $class::query()->count();
            } elseif ($this->relationshipName && method_exists($this, 'getContainer')) {
                $container = $this->getContainer();
                $parent = $container?->getRecord();
                if ($parent && method_exists($parent, $this->relationshipName)) {
                    $related = $parent->{$this->relationshipName}()->getRelated();
                    $count = $related::query()->count();
                }
            }

            if ($count !== null && $count <= $threshold) {
                $this->preload();
            }
        } catch (\Throwable) {
            // No hacemos preload si falla el conteo
        }

        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Crear en dropdown
        $this->createOptionForm(function () {
            if ($this->createForm instanceof Closure) {
                return ($this->createForm)($this) ?? [];
            }
            if (is_array($this->createForm)) {
                return $this->createForm;
            }

            return [
                TextInput::make($this->labelColumn)
                    ->label(ucfirst(str_replace('_', ' ', $this->labelColumn)))
                    ->required()
                    ->maxLength(255),
            ];
        });

        $this->createOptionUsing(function (array $data) {
            $data = $this->mutateCreateData ? ($this->mutateCreateData)($data, $this) ?? $data : $data;

            $record = $this->newRecordInstance();
            $record->fill($data)->save();

            $this->refreshOptions();

            return $record->{$this->valueColumn};
        });

        $this->createOptionAction(function (Action $action) {
            return $action
                ->label('Crear')
                ->modalHeading('Crear ' . ($this->getLabel() ?? 'registro'))
                ->modalWidth('md');
        });

        // Editar en dropdown
        $this->editOptionForm(function (?Model $record) {
            // Aceptamos solo el modelo de la opción; si llega el padre, lo descartamos
            $record = $this->ensureOptionRecord($record) ?: $this->resolveOptionRecordByState();
            $this->currentOptionRecord = $record;

            if ($this->editForm instanceof Closure) {
                return ($this->editForm)($record, $this) ?? [];
            }
            if (is_array($this->editForm)) {
                return $this->editForm;
            }

            // No poner default() aquí: Filament hidrata desde $record
            return [
                TextInput::make($this->labelColumn)
                    ->label(ucfirst(str_replace('_', ' ', $this->labelColumn)))
                    ->required()
                    ->maxLength(255),
            ];
        });

        $this->editOptionAction(function (Action $action) {
            return $action
                ->label('Editar')
                ->modalHeading('Editar ' . ($this->getLabel() ?? 'registro'))
                ->modalWidth('md')
                ->action(function (?Model $record, array $data) {
                    // Ignora el padre; usa solo el modelo de la opción
                    $target = $this->ensureOptionRecord($record)
                        ?: $this->currentOptionRecord
                            ?: $this->resolveOptionRecordByState();

                    if (! $target) {
                        return;
                    }

                    $data = $this->mutateEditData ? ($this->mutateEditData)($data, $target, $this) ?? $data : $data;

                    $target->fill($data)->save();
                    $this->refreshOptions();

                    return $target->{$this->valueColumn};
                })
                ->extraModalFooterActions(function () {
                    return [
                        DeleteAction::make('deleteOption')
                            ->record(fn () => $this->resolveOptionRecordByState())
                            ->label('Eliminar')
                            ->color('danger')
                            ->requiresConfirmation()
                            ->action(function (?Model $recordToDelete) {
                                if (! $recordToDelete) {
                                    return;
                                }
                                $recordToDelete->delete();
                                $this->currentOptionRecord = null;
                                $this->state(null);
                                $this->refreshOptions();
                            }),
                    ];
                });
        });
    }

    protected function refreshOptions(): void
    {
        if ($this->relationshipName && ! $this->modelClass) {
            // Deja que Filament gestione las options por relación
            return;
        }

        if (! $this->modelClass) {
            return;
        }

        $class = $this->modelClass;
        $labelCol = $this->labelColumn;
        $valueCol = $this->valueColumn;

        if ($this->groupByColumn) {
            $groupBy = $this->groupByColumn;
            $groupLabel = $this->groupLabelColumn ?? $this->groupByColumn;

            $this->options(function () use ($class, $labelCol, $valueCol, $groupBy, $groupLabel) {
                return $class::query()
                    ->select([$valueCol, $labelCol, $groupBy])
                    ->orderBy($groupBy)
                    ->orderBy($labelCol)
                    ->get()
                    ->groupBy($groupBy)
                    ->mapWithKeys(function ($rows, $groupValue) use ($labelCol, $valueCol, $groupLabel) {
                        $title = is_string($groupValue) ? $groupValue : (string) $groupValue;
                        $title = Str::of($title)->headline()->toString();

                        return [
                            $title => $rows->pluck($labelCol, $valueCol)->toArray(),
                        ];
                    })
                    ->toArray();
            });

            return;
        }

        $this->options(fn () => $class::query()->orderBy($labelCol)->pluck($labelCol, $valueCol)->toArray());
    }

    protected function newRecordInstance(): ?Model
    {
        if ($this->relationshipName && method_exists($this, 'getContainer')) {
            $container = $this->getContainer();
            $parent = $container?->getRecord();

            if ($parent && method_exists($parent, $this->relationshipName)) {
                return $parent->{$this->relationshipName}()->getRelated()->newInstance();
            }
        }

        if ($this->modelClass) {
            $class = $this->modelClass;
            return new $class();
        }

        return null;
    }

    protected function resolveOptionRecordByState(): ?Model
    {
        $key = $this->getState();
        if (! $key) {
            return null;
        }

        if ($class = $this->getOptionModelClass()) {
            return $class::query()->find($key);
        }

        return null;
    }

    protected function getOptionModelClass(): ?string
    {
        if ($this->modelClass) {
            return $this->modelClass;
        }

        if ($this->relationshipName && method_exists($this, 'getContainer')) {
            $container = $this->getContainer();
            $parent = $container?->getRecord();
            if ($parent && method_exists($parent, $this->relationshipName)) {
                return get_class($parent->{$this->relationshipName}()->getRelated());
            }
        }

        return null;
    }

    protected function ensureOptionRecord(?Model $record): ?Model
    {
        $class = $this->getOptionModelClass();
        if ($record && $class && $record instanceof $class) {
            return $record;
        }

        return null;
    }
}
