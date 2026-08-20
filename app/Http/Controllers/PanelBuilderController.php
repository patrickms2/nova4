<?php

namespace App\Http\Controllers;

use App\Models\Panel;
use App\Models\PanelField;
use App\Models\PanelRelation;
use App\Models\PanelTable;
use App\Services\CodeGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class PanelBuilderController extends Controller
{
    protected CodeGeneratorService $codeGenerator;

    public function __construct()
    {
        $this->codeGenerator = new CodeGeneratorService();
    }

    /**
     * Display the panel builder dashboard.
     */
    public function index()
    {
        $panels = Panel::with(['fields', 'relations', 'tables'])
            ->orderBy('navigation_group')
            ->orderBy('navigation_sort')
            ->get();

        return view('panel-builder.index', compact('panels'));
    }

    /**
     * Show the visual panel builder.
     */
    public function visual()
    {
        $panels = Panel::orderBy('navigation_group')->orderBy('navigation_sort')->get();

        return view('panel-builder.visual', compact('panels'));
    }

    /**
     * Show the field configurator.
     */
    public function fieldConfigurator()
    {
        return view('panel-builder.field-configurator');
    }

    /**
     * Get panel data for API requests.
     */
    public function getPanel(Panel $panel): JsonResponse
    {
        $panel->load(['fields', 'relations', 'tables']);

        return response()->json([
            'panel' => $panel,
            'fields' => $panel->fields,
            'relations' => $panel->relations,
            'tables' => $panel->tables,
        ]);
    }

    /**
     * Create a new panel.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'navigation_group' => 'nullable|string|max:255',
            'navigation_sort' => 'nullable|integer',
            'icon' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $panel = Panel::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => isset($validated['description']) ? $validated['description'] : '',
            'navigation_group' => isset($validated['navigation_group']) ? $validated['navigation_group'] : '',
            'navigation_sort' => isset($validated['navigation_sort']) ? $validated['navigation_sort'] : 0,
            'icon' => isset($validated['icon']) ? $validated['icon'] : 'heroicon-o-cube',
            'is_active' => isset($validated['is_active']) ? $validated['is_active'] : true,
            'model_schema' => [
                'model_name' => str_replace(' ', '', $validated['name']),
                'table_name' => strtolower(str_replace(' ', '_', $validated['name'])),
                'fields' => [],
                'relations' => [],
            ],
        ]);

        return response()->json([
            'message' => 'Panel created successfully',
            'panel' => $panel,
        ]);
    }

    /**
     * Update a panel.
     */
    public function update(Request $request, Panel $panel): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'navigation_group' => 'nullable|string|max:255',
            'navigation_sort' => 'nullable|integer',
            'icon' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $panel->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => isset($validated['description']) ? $validated['description'] : '',
            'navigation_group' => isset($validated['navigation_group']) ? $validated['navigation_group'] : '',
            'navigation_sort' => isset($validated['navigation_sort']) ? $validated['navigation_sort'] : 0,
            'icon' => isset($validated['icon']) ? $validated['icon'] : 'heroicon-o-cube',
            'is_active' => isset($validated['is_active']) ? $validated['is_active'] : true,
        ]);

        return response()->json([
            'message' => 'Panel updated successfully',
            'panel' => $panel,
        ]);
    }

    /**
     * Delete a panel.
     */
    public function destroy(Panel $panel): JsonResponse
    {
        $panel->delete();

        return response()->json([
            'message' => 'Panel deleted successfully',
        ]);
    }

    /**
     * Add a field to a panel.
     */
    public function addField(Request $request, Panel $panel): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'label' => 'nullable|string|max:255',
            'type' => 'required|string|max:255',
            'filament_type' => 'required|string|max:255',
            'column_type' => 'required|string|max:255',
            'nullable' => 'boolean',
            'default' => 'nullable|string',
            'validation_rules' => 'nullable|array',
            'field_config' => 'nullable|array',
            'order' => 'nullable|integer',
        ]);

        $field = PanelField::create([
            'panel_id' => $panel->id,
            'name' => $validated['name'],
            'label' => isset($validated['label']) ? $validated['label'] : ucfirst(str_replace('_', ' ', $validated['name'])),
            'type' => $validated['type'],
            'filament_type' => $validated['filament_type'],
            'column_type' => $validated['column_type'],
            'nullable' => isset($validated['nullable']) ? $validated['nullable'] : false,
            'default' => isset($validated['default']) ? $validated['default'] : null,
            'validation_rules' => isset($validated['validation_rules']) ? $validated['validation_rules'] : [],
            'field_config' => isset($validated['field_config']) ? $validated['field_config'] : [],
            'order' => isset($validated['order']) ? $validated['order'] : 0,
        ]);

        // Update panel schema
        $this->updatePanelSchema($panel);

        return response()->json([
            'message' => 'Field added successfully',
            'field' => $field,
        ]);
    }

    /**
     * Update a field.
     */
    public function updateField(Request $request, PanelField $field): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'label' => 'nullable|string|max:255',
            'type' => 'required|string|max:255',
            'filament_type' => 'required|string|max:255',
            'column_type' => 'required|string|max:255',
            'nullable' => 'boolean',
            'default' => 'nullable|string',
            'validation_rules' => 'nullable|array',
            'field_config' => 'nullable|array',
            'order' => 'nullable|integer',
        ]);

        $field->update($validated);

        // Update panel schema
        $this->updatePanelSchema($field->panel);

        return response()->json([
            'message' => 'Field updated successfully',
            'field' => $field,
        ]);
    }

    /**
     * Delete a field.
     */
    public function destroyField(PanelField $field): JsonResponse
    {
        $panel = $field->panel;
        $field->delete();

        // Update panel schema
        $this->updatePanelSchema($panel);

        return response()->json([
            'message' => 'Field deleted successfully',
        ]);
    }

    /**
     * Add a relation to a panel.
     */
    public function addRelation(Request $request, Panel $panel): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|in:belongsTo,hasMany,hasOne,belongsToMany',
            'related_model' => 'required|string|max:255',
            'foreign_key' => 'nullable|string|max:255',
            'local_key' => 'nullable|string|max:255',
            'method_name' => 'nullable|string|max:255',
            'relation_config' => 'nullable|array',
        ]);

        $relation = PanelRelation::create([
            'panel_id' => $panel->id,
            'type' => $validated['type'],
            'related_model' => $validated['related_model'],
            'foreign_key' => isset($validated['foreign_key']) ? $validated['foreign_key'] : null,
            'local_key' => isset($validated['local_key']) ? $validated['local_key'] : null,
            'method_name' => isset($validated['method_name']) ? $validated['method_name'] : null,
            'relation_config' => isset($validated['relation_config']) ? $validated['relation_config'] : [],
        ]);

        // Update panel schema
        $this->updatePanelSchema($panel);

        return response()->json([
            'message' => 'Relation added successfully',
            'relation' => $relation,
        ]);
    }

    /**
     * Update a relation.
     */
    public function updateRelation(Request $request, PanelRelation $relation): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|in:belongsTo,hasMany,hasOne,belongsToMany',
            'related_model' => 'required|string|max:255',
            'foreign_key' => 'nullable|string|max:255',
            'local_key' => 'nullable|string|max:255',
            'method_name' => 'nullable|string|max:255',
            'relation_config' => 'nullable|array',
        ]);

        $relation->update($validated);

        // Update panel schema
        $this->updatePanelSchema($relation->panel);

        return response()->json([
            'message' => 'Relation updated successfully',
            'relation' => $relation,
        ]);
    }

    /**
     * Delete a relation.
     */
    public function destroyRelation(PanelRelation $relation): JsonResponse
    {
        $panel = $relation->panel;
        $relation->delete();

        // Update panel schema
        $this->updatePanelSchema($panel);

        return response()->json([
            'message' => 'Relation deleted successfully',
        ]);
    }

    /**
     * Add a table to a panel.
     */
    public function addTable(Request $request, Panel $panel): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'columns' => 'required|array',
            'filters' => 'nullable|array',
            'actions' => 'nullable|array',
            'bulk_actions' => 'nullable|array',
            'table_config' => 'nullable|array',
            'is_default' => 'boolean',
        ]);

        $table = PanelTable::create([
            'panel_id' => $panel->id,
            'name' => $validated['name'],
            'title' => $validated['title'],
            'description' => isset($validated['description']) ? $validated['description'] : '',
            'columns' => $validated['columns'],
            'filters' => isset($validated['filters']) ? $validated['filters'] : [],
            'actions' => isset($validated['actions']) ? $validated['actions'] : [],
            'bulk_actions' => isset($validated['bulk_actions']) ? $validated['bulk_actions'] : [],
            'table_config' => isset($validated['table_config']) ? $validated['table_config'] : [],
            'is_default' => isset($validated['is_default']) ? $validated['is_default'] : false,
        ]);

        return response()->json([
            'message' => 'Table added successfully',
            'table' => $table,
        ]);
    }

    /**
     * Update a table.
     */
    public function updateTable(Request $request, PanelTable $table): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'columns' => 'required|array',
            'filters' => 'nullable|array',
            'actions' => 'nullable|array',
            'bulk_actions' => 'nullable|array',
            'table_config' => 'nullable|array',
            'is_default' => 'boolean',
        ]);

        $table->update($validated);

        return response()->json([
            'message' => 'Table updated successfully',
            'table' => $table,
        ]);
    }

    /**
     * Delete a table.
     */
    public function destroyTable(PanelTable $table): JsonResponse
    {
        $table->delete();

        return response()->json([
            'message' => 'Table deleted successfully',
        ]);
    }

    /**
     * Generate code for a panel.
     */
    public function generateCode(Panel $panel): JsonResponse
    {
        try {
            $code = $this->codeGenerator->generatePanelCode($panel);

            // Save files
            $this->saveGeneratedFiles($code, $panel);

            return response()->json([
                'message' => 'Code generated successfully',
                'code' => $code,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error generating code: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Preview generated code.
     */
    public function previewCode(Panel $panel): JsonResponse
    {
        try {
            $code = $this->codeGenerator->generatePanelCode($panel);

            return response()->json([
                'code' => $code,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error generating preview: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export panel configuration.
     */
    public function export(Panel $panel): JsonResponse
    {
        $panel->load(['fields', 'relations', 'tables']);

        return response()->json([
            'panel' => $panel,
            'fields' => $panel->fields,
            'relations' => $panel->relations,
            'tables' => $panel->tables,
        ]);
    }

    /**
     * Import panel configuration.
     */
    public function import(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'panel_data' => 'required|array',
            'fields_data' => 'nullable|array',
            'relations_data' => 'nullable|array',
            'tables_data' => 'nullable|array',
        ]);

        try {
            \DB::beginTransaction();

            // Create panel
            $panel = Panel::create($validated['panel_data']);

            // Create fields
            if (!empty($validated['fields_data'])) {
                foreach ($validated['fields_data'] as $fieldData) {
                    $fieldData['panel_id'] = $panel->id;
                    PanelField::create($fieldData);
                }
            }

            // Create relations
            if (!empty($validated['relations_data'])) {
                foreach ($validated['relations_data'] as $relationData) {
                    $relationData['panel_id'] = $panel->id;
                    PanelRelation::create($relationData);
                }
            }

            // Create tables
            if (!empty($validated['tables_data'])) {
                foreach ($validated['tables_data'] as $tableData) {
                    $tableData['panel_id'] = $panel->id;
                    PanelTable::create($tableData);
                }
            }

            \DB::commit();

            return response()->json([
                'message' => 'Panel imported successfully',
                'panel' => $panel,
            ]);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json([
                'message' => 'Error importing panel: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available field types.
     */
    public function getFieldTypes(): JsonResponse
    {
        $fieldTypes = [
            'basic' => [
                ['type' => 'text', 'name' => 'Text Input', 'icon' => '📝'],
                ['type' => 'textarea', 'name' => 'Textarea', 'icon' => '📄'],
                ['type' => 'number', 'name' => 'Number', 'icon' => '🔢'],
                ['type' => 'email', 'name' => 'Email', 'icon' => '📧'],
                ['type' => 'password', 'name' => 'Password', 'icon' => '🔐'],
            ],
            'choice' => [
                ['type' => 'select', 'name' => 'Select', 'icon' => '📋'],
                ['type' => 'checkbox', 'name' => 'Checkbox', 'icon' => '☑️'],
                ['type' => 'radio', 'name' => 'Radio', 'icon' => '🔘'],
                ['type' => 'toggle', 'name' => 'Toggle', 'icon' => '🔄'],
            ],
            'datetime' => [
                ['type' => 'date', 'name' => 'Date', 'icon' => '📅'],
                ['type' => 'datetime', 'name' => 'Date Time', 'icon' => '📆'],
                ['type' => 'time', 'name' => 'Time', 'icon' => '⏰'],
            ],
            'media' => [
                ['type' => 'file', 'name' => 'File Upload', 'icon' => '📎'],
                ['type' => 'image', 'name' => 'Image', 'icon' => '🖼️'],
                ['type' => 'richeditor', 'name' => 'Rich Editor', 'icon' => '📝'],
            ],
            'relation' => [
                ['type' => 'belongsTo', 'name' => 'Belongs To', 'icon' => '🔗'],
                ['type' => 'hasMany', 'name' => 'Has Many', 'icon' => '📚'],
                ['type' => 'belongsToMany', 'name' => 'Belongs to Many', 'icon' => '🔀'],
                ['type' => 'hasOne', 'name' => 'Has One', 'icon' => '1️⃣'],
            ],
        ];

        return response()->json($fieldTypes);
    }

    /**
     * Update panel schema when fields or relations change.
     */
    private function updatePanelSchema(Panel $panel): void
    {
        $panel->load(['fields', 'relations']);

        $schema = $panel->model_schema;

        // Update fields in schema
        $schema['fields'] = $panel->fields->map(function ($field) {
            return [
                'name' => $field->name,
                'label' => $field->label,
                'type' => $field->type,
                'filament_type' => $field->filament_type,
                'column_type' => $field->column_type,
                'nullable' => $field->nullable,
                'default' => $field->default,
                'validation_rules' => $field->validation_rules,
                'field_config' => $field->field_config,
            ];
        })->toArray();

        // Update relations in schema
        $schema['relations'] = $panel->relations->map(function ($relation) {
            return [
                'type' => $relation->type,
                'related_model' => $relation->related_model,
                'foreign_key' => $relation->foreign_key,
                'local_key' => $relation->local_key,
                'method_name' => $relation->method_name,
                'relation_config' => $relation->relation_config,
            ];
        })->toArray();

        $panel->update(['model_schema' => $schema]);
    }

    /**
     * Save generated files to the filesystem.
     */
    private function saveGeneratedFiles(array $code, Panel $panel): void
    {
        $schema = $panel->model_schema;
        $className = isset($schema['model_name']) ? $schema['model_name'] : str_replace(' ', '', $panel->name);
        $tableName = isset($schema['table_name']) ? $schema['table_name'] : strtolower(str_replace(' ', '_', $panel->name));

        // Save model
        $modelPath = app_path("Models/{$className}.php");
        file_put_contents($modelPath, $code['model']);

        // Save migration
        $timestamp = now()->format('Y_m_d_His');
        $migrationPath = database_path("migrations/{$timestamp}_create_{$tableName}_table.php");
        file_put_contents($migrationPath, $code['migration']);

        // Save resource
        $resourceName = $className . 'Resource';
        $resourceDir = app_path("Filament/Resources");
        if (!is_dir($resourceDir)) {
            mkdir($resourceDir, 0755, true);
        }
        $resourcePath = app_path("Filament/Resources/{$resourceName}.php");
        file_put_contents($resourcePath, $code['resource']);

        // Save factory
        $factoryPath = database_path("factories/{$className}Factory.php");
        file_put_contents($factoryPath, $code['factory']);

        // Save seeder
        $seederPath = database_path("seeders/{$className}Seeder.php");
        file_put_contents($seederPath, $code['seeder']);

        // Save test
        $testPath = base_path("tests/Feature/{$className}Test.php");
        if (!is_dir(dirname($testPath))) {
            mkdir(dirname($testPath), 0755, true);
        }
        file_put_contents($testPath, $code['test']);
    }
}
