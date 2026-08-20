<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PanelRelation extends Model
{
    protected $fillable = [
        'panel_id',
        'type',
        'related_model',
        'related_panel_id',
        'foreign_key',
        'local_key',
        'method_name',
        'relation_config',
    ];

    protected $casts = [
        'relation_config' => 'array',
    ];

    public function panel(): BelongsTo
    {
        return $this->belongsTo(Panel::class);
    }

    public function relatedPanel(): BelongsTo
    {
        return $this->belongsTo(Panel::class, 'related_panel_id');
    }

    public function generateRelationMethod(): string
    {
        $methodName = $this->method_name ?: $this->generateDefaultMethodName();
        $relatedModel = $this->related_model;
        $type = $this->type;

        $method = "    public function {$methodName}()\n";
        $method .= "    {\n";
        $method .= "        return \$this->{$type}({$relatedModel}::class";

        // Add relation-specific parameters
        switch ($type) {
            case 'belongsTo':
                if ($this->foreign_key) {
                    $method .= ", '{$this->foreign_key}'";
                }
                if ($this->local_key && $this->local_key !== 'id') {
                    $method .= ", '{$this->local_key}'";
                }
                break;

            case 'hasMany':
                if ($this->foreign_key) {
                    $method .= ", '{$this->foreign_key}'";
                }
                if ($this->local_key && $this->local_key !== 'id') {
                    $method .= ", '{$this->local_key}'";
                }
                break;

            case 'hasOne':
                if ($this->foreign_key) {
                    $method .= ", '{$this->foreign_key}'";
                }
                if ($this->local_key && $this->local_key !== 'id') {
                    $method .= ", '{$this->local_key}'";
                }
                break;

            case 'belongsToMany':
                if ($this->foreign_key) {
                    $method .= ", '{$this->foreign_key}'";
                }
                if ($this->local_key && $this->local_key !== 'id') {
                    $method .= ", '{$this->local_key}'";
                }
                // Add pivot table if specified
                if (!empty($this->relation_config['pivot_table'])) {
                    $method .= ", '" . $this->relation_config['pivot_table'] . "'";
                }
                break;
        }

        $method .= ");\n";
        $method .= "    }\n";

        return $method;
    }

    public function generateMigrationForeignKey(): string
    {
        if ($this->type !== 'belongsTo') {
            return '';
        }

        $foreignKey = $this->foreign_key ?: strtolower($this->related_model) . '_id';
        $relatedTable = $this->getRelatedTableName();

        return "\$table->foreignId('{$foreignKey}')->constrained('{$relatedTable}');";
    }

    private function generateDefaultMethodName(): string
    {
        switch ($this->type) {
            case 'belongsTo':
                return strtolower($this->related_model);
            case 'hasMany':
                return strtolower(str_plural($this->related_model));
            case 'hasOne':
                return strtolower($this->related_model);
            case 'belongsToMany':
                return strtolower(str_plural($this->related_model));
            default:
                return strtolower($this->related_model);
        }
    }

    private function getRelatedTableName(): string
    {
        if (!empty($this->relation_config['table_name'])) {
            return $this->relation_config['table_name'];
        }

        // Default table naming convention
        return strtolower(str_plural($this->related_model));
    }

    public function getFilamentRelationManagerConfig(): array
    {
        $config = $this->relation_config ?? [];
        $relatedModel = $this->related_model;
        $methodName = $this->method_name ?: $this->generateDefaultMethodName();

        $relationManager = [
            'relation' => $methodName,
            'model' => $relatedModel,
            'type' => $this->type,
        ];

        // Add relation manager specific configurations
        switch ($this->type) {
            case 'hasMany':
            case 'belongsToMany':
                $relationManager['columns'] = $config['columns'] ?? [
                    ['name' => 'id', 'type' => 'TextColumn', 'label' => 'ID'],
                ];
                $relationManager['filters'] = $config['filters'] ?? [];
                $relationManager['headerActions'] = $config['header_actions'] ?? [
                    'CreateAction',
                ];
                $relationManager['actions'] = $config['actions'] ?? [
                    'EditAction',
                    'DeleteAction',
                ];
                $relationManager['bulkActions'] = $config['bulk_actions'] ?? [
                    'DeleteBulkAction',
                ];
                break;

            case 'hasOne':
            case 'belongsTo':
                $relationManager['form_schema'] = $config['form_schema'] ?? [];
                break;
        }

        return $relationManager;
    }
}
