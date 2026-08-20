<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PanelField extends Model
{
    protected $fillable = [
        'panel_id',
        'name',
        'label',
        'type',
        'filament_type',
        'column_type',
        'nullable',
        'default',
        'validation_rules',
        'order',
        'field_config',
    ];

    protected $casts = [
        'nullable' => 'boolean',
        'validation_rules' => 'array',
        'field_config' => 'array',
    ];

    public function panel(): BelongsTo
    {
        return $this->belongsTo(Panel::class);
    }

    public function getFilamentFieldDefinition(): array
    {
        $config = $this->field_config ?? [];
        $definition = [
            'name' => $this->name,
            'label' => $this->label,
            'type' => $this->filament_type,
        ];

        // Add common field properties
        if ($this->nullable) {
            $definition['nullable'] = true;
        }

        if ($this->default !== null) {
            $definition['default'] = $this->default;
        }

        // Add validation rules
        if (!empty($this->validation_rules)) {
            $definition['rules'] = $this->validation_rules;
        }

        // Add type-specific configurations
        switch ($this->filament_type) {
            case 'TextInput':
                if (!empty($config['maxLength'])) {
                    $definition['maxLength'] = $config['maxLength'];
                }
                if (!empty($config['password'])) {
                    $definition['password'] = true;
                }
                if (!empty($config['email'])) {
                    $definition['email'] = true;
                }
                break;

            case 'Select':
                if (!empty($config['options'])) {
                    $definition['options'] = $config['options'];
                }
                if (!empty($config['multiple'])) {
                    $definition['multiple'] = true;
                }
                break;

            case 'Textarea':
                if (!empty($config['rows'])) {
                    $definition['rows'] = $config['rows'];
                }
                break;

            case 'RichEditor':
                if (!empty($config['toolbarButtons'])) {
                    $definition['toolbarButtons'] = $config['toolbarButtons'];
                }
                break;

            case 'DatePicker':
                if (!empty($config['format'])) {
                    $definition['format'] = $config['format'];
                }
                break;

            case 'Toggle':
                if (!empty($config['onText'])) {
                    $definition['onText'] = $config['onText'];
                }
                if (!empty($config['offText'])) {
                    $definition['offText'] = $config['offText'];
                }
                break;

            case 'CheckboxList':
                if (!empty($config['options'])) {
                    $definition['options'] = $config['options'];
                }
                break;

            case 'FileUpload':
                if (!empty($config['directory'])) {
                    $definition['directory'] = $config['directory'];
                }
                if (!empty($config['image'])) {
                    $definition['image'] = true;
                }
                if (!empty($config['multiple'])) {
                    $definition['multiple'] = true;
                }
                break;
        }

        return $definition;
    }

    public function getTableColumnDefinition(): array
    {
        $config = $this->field_config ?? [];
        $definition = [
            'name' => $this->name,
            'type' => $this->column_type,
            'label' => $this->label,
        ];

        // Add type-specific configurations
        switch ($this->column_type) {
            case 'TextColumn':
                if (!empty($config['searchable'])) {
                    $definition['searchable'] = true;
                }
                if (!empty($config['sortable'])) {
                    $definition['sortable'] = true;
                }
                if (!empty($config['limit'])) {
                    $definition['limit'] = $config['limit'];
                }
                break;

            case 'IconColumn':
                if (!empty($config['icon'])) {
                    $definition['icon'] = $config['icon'];
                }
                break;

            case 'ImageColumn':
                if (!empty($config['size'])) {
                    $definition['size'] = $config['size'];
                }
                if (!empty($config['circular'])) {
                    $definition['circular'] = true;
                }
                break;

            case 'BooleanColumn':
                if (!empty($config['trueIcon'])) {
                    $definition['trueIcon'] = $config['trueIcon'];
                }
                if (!empty($config['falseIcon'])) {
                    $definition['falseIcon'] = $config['falseIcon'];
                }
                break;

            case 'BadgeColumn':
                if (!empty($config['colors'])) {
                    $definition['colors'] = $config['colors'];
                }
                break;
        }

        return $definition;
    }

    public function getMigrationFieldDefinition(): string
    {
        $type = $this->type;
        $name = $this->name;
        $nullable = $this->nullable ? '->nullable()' : '';
        $default = $this->default !== null ? "->default('{$this->default}')" : '';

        return "\$table->{$type}('{$name}')" . $nullable . $default . ";";
    }
}
