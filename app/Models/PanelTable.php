<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PanelTable extends Model
{
    protected $fillable = [
        'panel_id',
        'name',
        'title',
        'description',
        'columns',
        'filters',
        'actions',
        'bulk_actions',
        'table_config',
        'is_default',
    ];

    protected $casts = [
        'columns' => 'array',
        'filters' => 'array',
        'actions' => 'array',
        'bulk_actions' => 'array',
        'table_config' => 'array',
        'is_default' => 'boolean',
    ];

    public function panel(): BelongsTo
    {
        return $this->belongsTo(Panel::class);
    }

    public function generateTableCode(): string
    {
        $panel = $this->panel;
        $schema = $panel->model_schema;
        $className = $schema['model_name'] ?? str_replace(' ', '', $panel->name);

        $code = "    public static function table(Table \$table): Table\n";
        $code .= "    {\n";
        $code .= "        return \$table\n";
        $code .= "            ->columns([\n";

        // Generate columns
        if (!empty($this->columns)) {
            foreach ($this->columns as $column) {
                $code .= $this->generateColumnCode($column);
            }
        } else {
            // Default columns from panel fields
            foreach ($panel->fields as $field) {
                $code .= "                Tables\\Columns\\{$field->column_type}::make('{$field->name}')\n";
                $code .= "                    ->label('{$field->label}')\n";
                $code .= "                    ->searchable(),\n";
            }
        }

        $code .= "            ])\n";

        // Generate filters
        $code .= "            ->filters([\n";
        if (!empty($this->filters)) {
            foreach ($this->filters as $filter) {
                $code .= $this->generateFilterCode($filter);
            }
        }
        $code .= "            ])\n";

        // Generate actions
        $code .= "            ->actions([\n";
        if (!empty($this->actions)) {
            foreach ($this->actions as $action) {
                $code .= $this->generateActionCode($action);
            }
        } else {
            $code .= "                Tables\\Actions\\EditAction::make(),\n";
            $code .= "                Tables\\Actions\\DeleteAction::make(),\n";
        }
        $code .= "            ])\n";

        // Generate bulk actions
        $code .= "            ->bulkActions([\n";
        if (!empty($this->bulk_actions)) {
            foreach ($this->bulk_actions as $bulkAction) {
                $code .= $this->generateBulkActionCode($bulkAction);
            }
        } else {
            $code .= "                Tables\\Actions\\DeleteBulkAction::make(),\n";
        }
        $code .= "            ])";

        // Add table-specific configurations
        if (!empty($this->table_config)) {
            foreach ($this->table_config as $key => $value) {
                if (is_bool($value)) {
                    $code .= "\n            ->{$key}(" . ($value ? 'true' : 'false') . ")";
                } elseif (is_string($value)) {
                    $code .= "\n            ->{$key}('{$value}')";
                } elseif (is_array($value)) {
                    $code .= "\n            ->{$key}(" . json_encode($value) . ")";
                }
            }
        }

        $code .= ";\n";
        $code .= "    }\n";

        return $code;
    }

    private function generateColumnCode(array $column): string
    {
        $name = $column['name'];
        $type = $column['type'] ?? 'TextColumn';
        $label = $column['label'] ?? ucfirst(str_replace('_', ' ', $name));

        $code = "                Tables\\Columns\\{$type}::make('{$name}')\n";
        $code .= "                    ->label('{$label}')\n";

        // Add column-specific configurations
        if (!empty($column['searchable'])) {
            $code .= "                    ->searchable()\n";
        }
        if (!empty($column['sortable'])) {
            $code .= "                    ->sortable()\n";
        }
        if (!empty($column['limit'])) {
            $code .= "                    ->limit({$column['limit']})\n";
        }
        if (!empty($column['formatStateUsing'])) {
            $code .= "                    ->formatStateUsing(function ({$column['formatStateUsing']['params']}) {\n";
            $code .= "                        {$column['formatStateUsing']['code']}\n";
            $code .= "                    })\n";
        }

        $code .= "                    ->searchable(),\n";

        return $code;
    }

    private function generateFilterCode(array $filter): string
    {
        $name = $filter['name'];
        $type = $filter['type'] ?? 'SelectFilter';

        $code = "                Tables\\Filters\\{$type}::make('{$name}')\n";

        // Add filter-specific configurations
        if (!empty($filter['options'])) {
            $code .= "                    ->options(" . json_encode($filter['options']) . ")\n";
        }
        if (!empty($filter['attribute'])) {
            $code .= "                    ->attribute('{$filter['attribute']}')\n";
        }

        $code .= "                    ->searchable(),\n";

        return $code;
    }

    private function generateActionCode(array $action): string
    {
        $type = $action['type'] ?? 'EditAction';

        $code = "                Tables\\Actions\\{$type}::make()\n";

        // Add action-specific configurations
        if (!empty($action['label'])) {
            $code .= "                    ->label('{$action['label']}')\n";
        }
        if (!empty($action['icon'])) {
            $code .= "                    ->icon('{$action['icon']}')\n";
        }
        if (!empty($action['color'])) {
            $code .= "                    ->color('{$action['color']}')\n";
        }

        $code .= "                    ->searchable(),\n";

        return $code;
    }

    private function generateBulkActionCode(array $bulkAction): string
    {
        $type = $bulkAction['type'] ?? 'DeleteBulkAction';

        $code = "                Tables\\Actions\\{$type}::make()\n";

        // Add bulk action-specific configurations
        if (!empty($bulkAction['label'])) {
            $code .= "                    ->label('{$bulkAction['label']}')\n";
        }
        if (!empty($bulkAction['icon'])) {
            $code .= "                    ->icon('{$bulkAction['icon']}')\n";
        }
        if (!empty($bulkAction['requiresConfirmation'])) {
            $code .= "                    ->requiresConfirmation()\n";
        }

        $code .= "                    ->searchable(),\n";

        return $code;
    }

    public function generateTableClass(): string
    {
        $panel = $this->panel;
        $schema = $panel->model_schema;
        $className = $schema['model_name'] ?? str_replace(' ', '', $panel->name);
        $tableClassName = $className . 'Table';

        $code = "<?php\n\n";
        $code .= "namespace App\\Filament\\Tables;\n\n";
        $code .= "use Filament\\Tables;\n";
        $code .= "use Filament\\Tables\\Table;\n";
        $code .= "use Illuminate\\Database\\Eloquent\\Builder;\n\n";

        $code .= "class {$tableClassName}\n";
        $code .= "{\n";
        $code .= $this->generateTableCode();
        $code .= "}\n";

        return $code;
    }
}
