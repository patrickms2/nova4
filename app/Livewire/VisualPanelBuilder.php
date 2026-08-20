<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Panel;
use App\Models\PanelField;
use App\Models\PanelRelation;
use App\Models\PanelTable;
use Illuminate\Support\Str;

class VisualPanelBuilder extends Component
{
    public $currentPanelId = null;
    public $panelData = [];
    public $fields = [];
    public $relations = [];
    public $tableConfig = [];

    protected $listeners = [
        'panel-created' => 'refreshPanels',
        'field-updated' => 'refreshFields',
    ];

    public function mount()
    {
        $this->initializeEmptyPanel();
    }

    public function initializeEmptyPanel()
    {
        $this->panelData = [
            'name' => '',
            'modelName' => '',
            'description' => '',
            'navigationGroup' => '',
            'navigationSort' => 0,
            'icon' => 'heroicon-o-cube',
            'isActive' => true,
        ];

        $this->fields = [];
        $this->relations = [];
        $this->tableConfig = [
            'name' => 'default',
            'columns' => [
                ['name' => 'id', 'enabled' => true, 'type' => 'TextColumn'],
                ['name' => 'name', 'enabled' => true, 'type' => 'TextColumn'],
                ['name' => 'created_at', 'enabled' => false, 'type' => 'DateColumn'],
                ['name' => 'updated_at', 'enabled' => false, 'type' => 'DateColumn'],
            ],
            'filters' => [],
            'actions' => ['edit', 'delete'],
            'bulkActions' => ['delete'],
        ];
    }

    public function saveVisualPanel($data)
    {
        $panelData = $data['panelData'];
        $fields = $data['fields'];
        $tableConfig = $data['tableConfig'];

        // Validate required fields
        if (empty($panelData['name'])) {
            $this->dispatch('show-notification', [
                'type' => 'error',
                'message' => 'Panel name is required'
            ]);
            return;
        }

        try {
            \DB::beginTransaction();

            // Create or update panel
            $panel = Panel::updateOrCreate(
                ['id' => $this->currentPanelId],
                [
                    'name' => $panelData['name'],
                    'slug' => Str::slug($panelData['name']),
                    'description' => $panelData['description'] ?? '',
                    'icon' => $panelData['icon'] ?? 'heroicon-o-cube',
                    'navigation_group' => $panelData['navigationGroup'] ?? '',
                    'navigation_sort' => $panelData['navigationSort'] ?? 0,
                    'is_active' => $panelData['isActive'] ?? true,
                    'model_schema' => [
                        'model_name' => $panelData['modelName'] ?: str_replace(' ', '', $panelData['name']),
                        'table_name' => strtolower(str_replace(' ', '_', $panelData['name'])),
                        'fields' => $this->formatFieldsForSchema($fields),
                        'relations' => $this->formatRelationsForSchema($this->relations),
                    ],
                ]
            );

            // Clear existing fields and recreate
            if ($this->currentPanelId) {
                PanelField::where('panel_id', $panel->id)->delete();
            }

            // Create fields
            foreach ($fields as $index => $fieldData) {
                PanelField::create([
                    'panel_id' => $panel->id,
                    'name' => $fieldData['name'],
                    'label' => $fieldData['label'] ?? ucfirst(str_replace('_', ' ', $fieldData['name'])),
                    'type' => $this->getMigrationType($fieldData['type']),
                    'filament_type' => $this->getFilamentFieldType($fieldData['type']),
                    'column_type' => $this->getFilamentColumnType($fieldData['type']),
                    'nullable' => $fieldData['nullable'] ?? false,
                    'default' => $fieldData['default'] ?? null,
                    'validation_rules' => $fieldData['required'] ? ['required'] : [],
                    'order' => $index,
                    'field_config' => [
                        'placeholder' => $fieldData['placeholder'] ?? '',
                    ],
                ]);
            }

            // Create or update default table
            PanelTable::updateOrCreate(
                ['panel_id' => $panel->id, 'is_default' => true],
                [
                    'name' => $tableConfig['name'] ?? 'default',
                    'title' => $panelData['name'],
                    'description' => $panelData['description'] ?? '',
                    'columns' => $this->formatColumnsForTable($tableConfig['columns'] ?? []),
                    'filters' => $tableConfig['filters'] ?? [],
                    'actions' => $this->formatActionsForTable($tableConfig['actions'] ?? []),
                    'bulk_actions' => $this->formatActionsForTable($tableConfig['bulkActions'] ?? []),
                    'table_config' => [],
                    'is_default' => true,
                ]
            );

            $this->currentPanelId = $panel->id;

            \DB::commit();

            $this->dispatch('show-notification', [
                'type' => 'success',
                'message' => 'Panel saved successfully!'
            ]);

        } catch (\Exception $e) {
            \DB::rollBack();
            $this->dispatch('show-notification', [
                'type' => 'error',
                'message' => 'Error saving panel: ' . $e->getMessage()
            ]);
        }
    }

    public function generateVisualPanelCode($data)
    {
        $panelData = $data['panelData'];
        $fields = $data['fields'];
        $tableConfig = $data['tableConfig'];

        if (!$this->currentPanelId) {
            $this->dispatch('show-notification', [
                'type' => 'error',
                'message' => 'Please save the panel first'
            ]);
            return;
        }

        try {
            $panel = Panel::find($this->currentPanelId);
            if (!$panel) {
                $this->dispatch('show-notification', [
                    'type' => 'error',
                    'message' => 'Panel not found'
                ]);
                return;
            }

            // Generate model file
            $this->generateModelFile($panel);

            // Generate migration file
            $this->generateMigrationFile($panel);

            // Generate resource file
            $this->generateResourceFile($panel);

            $this->dispatch('show-notification', [
                'type' => 'success',
                'message' => 'Code generated successfully!'
            ]);

        } catch (\Exception $e) {
            $this->dispatch('show-notification', [
                'type' => 'error',
                'message' => 'Error generating code: ' . $e->getMessage()
            ]);
        }
    }

    public function loadVisualPanel($panelId)
    {
        $panel = Panel::with(['fields', 'relations', 'tables'])->find($panelId);

        if (!$panel) {
            return [
                'panelData' => [],
                'fields' => [],
                'tableConfig' => [],
            ];
        }

        $this->currentPanelId = $panelId;

        return [
            'panelData' => [
                'name' => $panel->name,
                'modelName' => $panel->model_schema['model_name'] ?? '',
                'description' => $panel->description,
                'navigationGroup' => $panel->navigation_group,
                'navigationSort' => $panel->navigation_sort,
                'icon' => $panel->icon,
                'isActive' => $panel->is_active,
            ],
            'fields' => $this->formatFieldsForBuilder($panel->fields),
            'tableConfig' => $this->formatTableConfigForBuilder($panel->tables->firstWhere('is_default', true)),
        ];
    }

    protected function formatFieldsForSchema($fields)
    {
        return collect($fields)->map(function ($field) {
            return [
                'name' => $field['name'] ?? 'unknown',
                'label' => $field['label'] ?? '',
                'type' => $this->getMigrationType($field['type'] ?? 'text'),
                'filament_type' => $this->getFilamentFieldType($field['type'] ?? 'text'),
                'column_type' => $this->getFilamentColumnType($field['type'] ?? 'text'),
                'nullable' => $field['nullable'] ?? false,
                'default' => $field['default'] ?? null,
                'required' => $field['required'] ?? false,
                'validation_rules' => $field['required'] ? ['required'] : [],
                'field_config' => [
                    'placeholder' => $field['placeholder'] ?? '',
                ],
            ];
        })->toArray();
    }

    protected function formatFieldsForBuilder($fields)
    {
        return $fields->map(function ($field) {
            return [
                'id' => $field->id,
                'name' => $field->name,
                'label' => $field->label,
                'type' => $this->getBuilderFieldType($field->filament_type),
                'placeholder' => $field->field_config['placeholder'] ?? '',
                'required' => in_array('required', $field->validation_rules ?? []),
                'nullable' => $field->nullable,
                'default' => $field->default,
            ];
        })->toArray();
    }

    protected function formatRelationsForSchema($relations)
    {
        return collect($relations)->map(function ($relation) {
            return [
                'type' => $relation['type'],
                'related_model' => $relation['related_model'],
                'foreign_key' => $relation['foreign_key'] ?? null,
                'local_key' => $relation['local_key'] ?? null,
                'method_name' => $relation['method_name'] ?? null,
                'relation_config' => $relation['relation_config'] ?? [],
            ];
        })->toArray();
    }

    protected function formatColumnsForTable($columns)
    {
        return collect($columns)->filter(fn($col) => $col['enabled'] ?? false)->map(function ($column) {
            return [
                'name' => $column['name'] ?? 'unknown',
                'type' => $column['type'] ?? 'TextColumn',
                'label' => ucfirst(str_replace('_', ' ', $column['name'] ?? 'unknown')),
                'searchable' => true,
                'sortable' => true,
            ];
        })->toArray();
    }

    protected function formatActionsForTable($actions)
    {
        return collect($actions)->map(function ($action) {
            return [
                'type' => ucfirst($action) . 'Action',
                'label' => ucfirst($action),
            ];
        })->toArray();
    }

    protected function formatTableConfigForBuilder($table)
    {
        if (!$table) {
            return [
                'name' => 'default',
                'columns' => [
                    ['name' => 'id', 'enabled' => true, 'type' => 'TextColumn'],
                    ['name' => 'name', 'enabled' => true, 'type' => 'TextColumn'],
                    ['name' => 'created_at', 'enabled' => false, 'type' => 'DateColumn'],
                    ['name' => 'updated_at', 'enabled' => false, 'type' => 'DateColumn'],
                ],
            ];
        }

        return [
            'name' => $table->name,
            'columns' => collect($table->columns)->map(function ($column) {
                return [
                    'name' => $column['name'] ?? 'unknown',
                    'enabled' => true,
                    'type' => $column['type'] ?? 'TextColumn',
                ];
            })->toArray(),
        ];
    }

    protected function getMigrationType($builderType)
    {
        $types = [
            'text' => 'string',
            'textarea' => 'text',
            'number' => 'integer',
            'email' => 'string',
            'password' => 'string',
            'select' => 'string',
            'checkbox' => 'boolean',
            'radio' => 'string',
            'toggle' => 'boolean',
            'date' => 'date',
            'datetime' => 'datetime',
            'time' => 'time',
            'file' => 'string',
            'image' => 'string',
            'richeditor' => 'text',
        ];

        return $types[$builderType] ?? 'string';
    }

    protected function getFilamentFieldType($builderType)
    {
        $types = [
            'text' => 'TextInput',
            'textarea' => 'Textarea',
            'number' => 'TextInput',
            'email' => 'TextInput',
            'password' => 'TextInput',
            'select' => 'Select',
            'checkbox' => 'Checkbox',
            'radio' => 'Radio',
            'toggle' => 'Toggle',
            'date' => 'DatePicker',
            'datetime' => 'DateTimePicker',
            'time' => 'TimePicker',
            'file' => 'FileUpload',
            'image' => 'FileUpload',
            'richeditor' => 'RichEditor',
        ];

        return $types[$builderType] ?? 'TextInput';
    }

    protected function getFilamentColumnType($builderType)
    {
        $types = [
            'text' => 'TextColumn',
            'textarea' => 'TextColumn',
            'number' => 'TextColumn',
            'email' => 'TextColumn',
            'password' => 'TextColumn',
            'select' => 'TextColumn',
            'checkbox' => 'IconColumn',
            'radio' => 'TextColumn',
            'toggle' => 'IconColumn',
            'date' => 'DateColumn',
            'datetime' => 'DateTimeColumn',
            'time' => 'TimeColumn',
            'file' => 'TextColumn',
            'image' => 'ImageColumn',
            'richeditor' => 'TextColumn',
        ];

        return $types[$builderType] ?? 'TextColumn';
    }

    protected function getBuilderFieldType($filamentType)
    {
        $types = [
            'TextInput' => 'text',
            'Textarea' => 'textarea',
            'RichEditor' => 'richeditor',
            'Select' => 'select',
            'Checkbox' => 'checkbox',
            'Toggle' => 'toggle',
            'Radio' => 'radio',
            'DatePicker' => 'date',
            'DateTimePicker' => 'datetime',
            'TimePicker' => 'time',
            'FileUpload' => 'file',
        ];

        return $types[$filamentType] ?? 'text';
    }

    protected function generateModelFile($panel)
    {
        $code = $panel->generateModelCode();
        $className = $panel->model_schema['model_name'] ?? str_replace(' ', '', $panel->name);

        $modelPath = app_path("Models/{$className}.php");
        file_put_contents($modelPath, $code);
    }

    protected function generateMigrationFile($panel)
    {
        $code = $panel->generateMigrationCode();
        $tableName = $panel->model_schema['table_name'] ?? strtolower(str_replace(' ', '_', $panel->name));
        $timestamp = now()->format('Y_m_d_His');

        $migrationPath = database_path("migrations/{$timestamp}_create_{$tableName}_table.php");
        file_put_contents($migrationPath, $code);
    }

    protected function generateResourceFile($panel)
    {
        $code = $panel->generateResourceCode();
        $className = $panel->model_schema['model_name'] ?? str_replace(' ', '', $panel->name);
        $resourceName = $className . 'Resource';

        $resourceDir = app_path("Filament/Resources");
        if (!is_dir($resourceDir)) {
            mkdir($resourceDir, 0755, true);
        }

        $resourcePath = app_path("Filament/Resources/{$resourceName}.php");
        file_put_contents($resourcePath, $code);
    }

    public function refreshPanels()
    {
        // Refresh panel list
    }

    public function refreshFields()
    {
        // Refresh fields
    }

    public function render()
    {
        $panels = Panel::orderBy('navigation_group')->orderBy('navigation_sort')->get();

        return view('livewire.visual-panel-builder', [
            'panels' => $panels,
        ]);
    }
}
