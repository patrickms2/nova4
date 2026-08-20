<?php

namespace App\Services;

use App\Models\Panel;
use App\Models\PanelField;
use App\Models\PanelRelation;
use App\Models\PanelTable;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class CodeGeneratorService
{
    public function generatePanelCode(Panel $panel): array
    {
        return [
            'model' => $this->generateModel($panel),
            'migration' => $this->generateMigration($panel),
            'resource' => $this->generateResource($panel),
            'factory' => $this->generateFactory($panel),
            'seeder' => $this->generateSeeder($panel),
            'test' => $this->generateTest($panel),
        ];
    }

    public function generateModel(Panel $panel): string
    {
        $schema = $panel->model_schema;
        $className = $schema['model_name'] ?? str_replace(' ', '', $panel->name);
        $tableName = $schema['table_name'] ?? strtolower(str_replace(' ', '_', $panel->name));

        $code = "<?php\n\n";
        $code .= "namespace App\Models;\n\n";

        // Add use statements for relations
        $relatedModels = $this->extractRelatedModels($panel);
        foreach ($relatedModels as $model) {
            $code .= "use App\\Models\\{$model};\n";
        }

        $code .= "\n";
        $code .= "use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;\n";
        $code .= "use Illuminate\\Database\\Eloquent\\Model;\n";
        $code .= "use Illuminate\\Database\\Eloquent\\SoftDeletes;\n\n";

        $code .= "class {$className} extends Model\n";
        $code .= "{\n";
        $code .= "    use HasFactory;\n";
        $code .= "    use SoftDeletes;\n\n";

        // Add fillable
        $fillable = $this->getFillableFields($panel);
        $code .= "    /**\n";
        $code .= "     * The attributes that are mass assignable.\n";
        $code .= "     *\n";
        $code .= "     * @var array<int, string>\n";
        $code .= "     */\n";
        $code .= "    protected \$fillable = [\n";
        foreach ($fillable as $field) {
            $code .= "        '{$field}',\n";
        }
        $code .= "    ];\n\n";

        // Add casts
        $casts = $this->getCasts($panel);
        if (!empty($casts)) {
            $code .= "    /**\n";
            $code .= "     * The attributes that should be cast.\n";
            $code .= "     *\n";
            $code .= "     * @var array<string, string>\n";
            $code .= "     */\n";
            $code .= "    protected \$casts = [\n";
            foreach ($casts as $field => $type) {
                $code .= "        '{$field}' => '{$type}',\n";
            }
            $code .= "    ];\n\n";
        }

        // Add table name
        $code .= "    /**\n";
        $code .= "     * The table associated with the model.\n";
        $code .= "     *\n";
        $code .= "     * @var string\n";
        $code .= "     */\n";
        $code .= "    protected \$table = '{$tableName}';\n\n";

        // Add relations
        $relations = $this->generateRelations($panel);
        foreach ($relations as $relation) {
            $code .= $relation . "\n";
        }

        // Add scopes
        $scopes = $this->generateScopes($panel);
        foreach ($scopes as $scope) {
            $code .= $scope . "\n";
        }

        // Add accessors and mutators
        $accessors = $this->generateAccessors($panel);
        foreach ($accessors as $accessor) {
            $code .= $accessor . "\n";
        }

        $code .= "}\n";

        return $code;
    }

    public function generateMigration(Panel $panel): string
    {
        $schema = $panel->model_schema;
        $tableName = $schema['table_name'] ?? strtolower(str_replace(' ', '_', $panel->name));
        $timestamp = now()->format('Y_m_d_His');

        $code = "<?php\n\n";
        $code .= "use Illuminate\\Database\\Migrations\\Migration;\n";
        $code .= "use Illuminate\\Database\\Schema\\Blueprint;\n";
        $code .= "use Illuminate\\Support\\Facades\\Schema;\n\n";

        $code .= "return new class extends Migration\n";
        $code .= "{\n";
        $code .= "    /**\n";
        $code .= "     * Run the migrations.\n";
        $code .= "     */\n";
        $code .= "    public function up(): void\n";
        $code .= "    {\n";
        $code .= "        Schema::create('{$tableName}', function (Blueprint \$table) {\n";
        $code .= "            \$table->id();\n\n";

        // Add fields
        foreach ($panel->fields as $field) {
            $migrationField = $this->generateMigrationField($field);
            $code .= "            {$migrationField}\n";
        }

        // Add foreign keys for relations
        foreach ($panel->relations as $relation) {
            if ($relation->type === 'belongsTo') {
                $foreignKey = $relation->relation_config['foreign_key'] ?? strtolower($relation->related_model) . '_id';
                $relatedTable = $this->getRelatedTableName($relation);
                $code .= "            \$table->foreignId('{$foreignKey}')->constrained('{$relatedTable}');\n";
            }
        }

        $code .= "\n            \$table->timestamps();\n";
        $code .= "            \$table->softDeletes();\n";
        $code .= "        });\n";
        $code .= "    }\n\n";

        $code .= "    /**\n";
        $code .= "     * Reverse the migrations.\n";
        $code .= "     */\n";
        $code .= "    public function down(): void\n";
        $code .= "    {\n";
        $code .= "        Schema::dropIfExists('{$tableName}');\n";
        $code .= "    }\n";
        $code .= "};\n";

        return $code;
    }

    public function generateResource(Panel $panel): string
    {
        $schema = $panel->model_schema;
        $className = $schema['model_name'] ?? str_replace(' ', '', $panel->name);
        $resourceName = $className . 'Resource';

        $code = "<?php\n\n";
        $code .= "namespace App\\Filament\\Resources;\n\n";
        $code .= "use App\\Filament\\Resources\\{$resourceName}\\Pages;\n";
        $code .= "use App\\Models\\{$className};\n";
        $code .= "use Filament\\Forms;\n";
        $code .= "use Filament\\Forms\\Form;\n";
        $code .= "use Filament\\Resources\\Resource;\n";
        $code .= "use Filament\\Tables;\n";
        $code .= "use Filament\\Tables\\Table;\n";
        $code .= "use Illuminate\\Database\\Eloquent\\Builder;\n\n";

        $code .= "class {$resourceName} extends Resource\n";
        $code .= "{\n";
        $code .= "    protected static ?string \$model = {$className}::class;\n\n";

        if ($panel->icon) {
            $code .= "    protected static ?string \$navigationIcon = '{$panel->icon}';\n\n";
        }

        if ($panel->navigation_group) {
            $code .= "    protected static ?string \$navigationGroup = '{$panel->navigation_group}';\n\n";
        }

        if ($panel->navigation_sort) {
            $code .= "    protected static ?int \$navigationSort = {$panel->navigation_sort};\n\n";
        }

        // Generate form
        $code .= $this->generateResourceForm($panel);

        // Generate table
        $code .= $this->generateResourceTable($panel);

        // Generate relations
        $code .= $this->generateResourceRelations($panel);

        // Generate pages
        $code .= $this->generateResourcePages($className);

        $code .= "}\n";

        return $code;
    }

    public function generateFactory(Panel $panel): string
    {
        $schema = $panel->model_schema;
        $className = $schema['model_name'] ?? str_replace(' ', '', $panel->name);
        $factoryName = $className . 'Factory';

        $code = "<?php\n\n";
        $code .= "namespace Database\\Factories;\n\n";
        $code .= "use Illuminate\\Database\\Eloquent\\Factories\\Factory;\n\n";
        $code .= "/**\n";
        $code .= " * @extends \\Illuminate\\Database\\Eloquent\\Factories\\Factory<\\App\\Models\\{$className}>\n";
        $code .= " */\n";
        $code .= "class {$factoryName} extends Factory\n";
        $code .= "{\n";
        $code .= "    /**\n";
        $code .= "     * Define the model's default state.\n";
        $code .= "     *\n";
        $code .= "     * @return array<string, mixed>\n";
        $code .= "     */\n";
        $code .= "    public function definition(): array\n";
        $code .= "    {\n";
        $code .= "        return [\n";

        foreach ($panel->fields as $field) {
            $factoryField = $this->generateFactoryField($field);
            if ($factoryField) {
                $code .= "            {$factoryField},\n";
            }
        }

        $code .= "        ];\n";
        $code .= "    }\n";
        $code .= "}\n";

        return $code;
    }

    public function generateSeeder(Panel $panel): string
    {
        $schema = $panel->model_schema;
        $className = $schema['model_name'] ?? str_replace(' ', '', $panel->name);
        $seederName = $className . 'Seeder';

        $code = "<?php\n\n";
        $code .= "namespace Database\\Seeders;\n\n";
        $code .= "use App\\Models\\{$className};\n";
        $code .= "use Illuminate\\Database\\Seeder;\n\n";

        $code .= "class {$seederName} extends Seeder\n";
        $code .= "{\n";
        $code .= "    /**\n";
        $code .= "     * Run the database seeds.\n";
        $code .= "     */\n";
        $code .= "    public function run(): void\n";
        $code .= "    {\n";
        $code .= "        {$className}::factory(10)->create();\n";
        $code .= "    }\n";
        $code .= "}\n";

        return $code;
    }

    public function generateTest(Panel $panel): string
    {
        $schema = $panel->model_schema;
        $className = $schema['model_name'] ?? str_replace(' ', '', $panel->name);
        $testName = $className . 'Test';

        $code = "<?php\n\n";
        $code .= "namespace Tests\\Feature;\n\n";
        $code .= "use App\\Models\\{$className};\n";
        $code .= "use Illuminate\\Foundation\\Testing\\RefreshDatabase;\n";
        $code .= "use Tests\\TestCase;\n\n";

        $code .= "class {$testName} extends TestCase\n";
        $code .= "{\n";
        $code .= "    use RefreshDatabase;\n\n";

        // Test creation
        $code .= "    public function test_{$className}_can_be_created(): void\n";
        $code .= "    {\n";
        $code .= "        \$data = [\n";
        foreach ($panel->fields as $field) {
            if (!$field->nullable) {
                $code .= "            '{$field->name}' => \$this->faker->{$this->getFakerType($field->type)},\n";
            }
        }
        $code .= "        ];\n\n";
        $code .= "        \$model = {$className}::create(\$data);\n\n";
        $code .= "        \$this->assertInstanceOf({$className}::class, \$model);\n";
        $code .= "        \$this->assertDatabaseHas('{$schema['table_name']}', \$data);\n";
        $code .= "    }\n\n";

        // Test validation
        $code .= "    public function test_{$className}_requires_required_fields(): void\n";
        $code .= "    {\n";
        $requiredFields = $panel->fields->filter(fn($field) => !$field->nullable);
        if ($requiredFields->isNotEmpty()) {
            $code .= "        \$response = \$this->post('/{$this->getRouteName()}', []);\n\n";
            $code .= "        \$response->assertSessionHasErrors([\n";
            foreach ($requiredFields as $field) {
                $code .= "            '{$field->name}',\n";
            }
            $code .= "        ]);\n";
        }
        $code .= "    }\n";

        $code .= "}\n";

        return $code;
    }

    private function getFillableFields(Panel $panel): array
    {
        return $panel->fields->pluck('name')->toArray();
    }

    private function getCasts(Panel $panel): array
    {
        $casts = [];

        foreach ($panel->fields as $field) {
            switch ($field->type) {
                case 'boolean':
                case 'toggle':
                    $casts[$field->name] = 'boolean';
                    break;
                case 'date':
                    $casts[$field->name] = 'date';
                    break;
                case 'datetime':
                    $casts[$field->name] = 'datetime';
                    break;
                case 'json':
                    $casts[$field->name] = 'array';
                    break;
                case 'decimal':
                    $casts[$field->name] = 'decimal:2';
                    break;
            }
        }

        return $casts;
    }

    private function extractRelatedModels(Panel $panel): array
    {
        $models = [];

        foreach ($panel->relations as $relation) {
            $models[] = $relation->related_model;
        }

        return array_unique($models);
    }

    private function generateRelations(Panel $panel): array
    {
        $relations = [];

        foreach ($panel->relations as $relation) {
            $methodName = $relation->relation_config['method_name'] ?? $this->generateRelationMethodName($relation);
            $relatedModel = $relation->related_model;

            $relationCode = "    public function {$methodName}()\n";
            $relationCode .= "    {\n";
            $relationCode .= "        return \$this->{$relation->type}({$relatedModel}::class";

            // Add relation parameters
            switch ($relation->type) {
                case 'belongsTo':
                    $foreignKey = $relation->relation_config['foreign_key'] ?? null;
                    $ownerKey = $relation->relation_config['owner_key'] ?? null;
                    if ($foreignKey) $relationCode .= ", '{$foreignKey}'";
                    if ($ownerKey) $relationCode .= ", '{$ownerKey}'";
                    break;

                case 'hasMany':
                    $foreignKey = $relation->relation_config['foreign_key'] ?? null;
                    $localKey = $relation->relation_config['local_key'] ?? null;
                    if ($foreignKey) $relationCode .= ", '{$foreignKey}'";
                    if ($localKey) $relationCode .= ", '{$localKey}'";
                    break;

                case 'belongsToMany':
                    $pivotTable = $relation->relation_config['pivot_table'] ?? null;
                    $foreignPivotKey = $relation->relation_config['foreign_pivot_key'] ?? null;
                    $relatedPivotKey = $relation->relation_config['related_pivot_key'] ?? null;
                    if ($pivotTable) $relationCode .= ", '{$pivotTable}'";
                    if ($foreignPivotKey) $relationCode .= ", '{$foreignPivotKey}'";
                    if ($relatedPivotKey) $relationCode .= ", '{$relatedPivotKey}'";
                    break;
            }

            $relationCode .= ");\n";
            $relationCode .= "    }";

            $relations[] = $relationCode;
        }

        return $relations;
    }

    private function generateRelationMethodName($relation): string
    {
        switch ($relation->type) {
            case 'belongsTo':
                return strtolower($relation->related_model);
            case 'hasMany':
                return strtolower(str_plural($relation->related_model));
            case 'hasOne':
                return strtolower($relation->related_model);
            case 'belongsToMany':
                return strtolower(str_plural($relation->related_model));
            default:
                return strtolower($relation->related_model);
        }
    }

    private function generateScopes(Panel $panel): array
    {
        $scopes = [];

        // Add common scopes based on fields
        foreach ($panel->fields as $field) {
            if ($field->type === 'boolean' || $field->type === 'toggle') {
                $scopeName = 'where' . ucfirst($field->name);
                $scopeCode = "    public function scope{$scopeName}(Builder \$query, bool \$value = true): Builder\n";
                $scopeCode .= "    {\n";
                $scopeCode .= "        return \$query->where('{$field->name}', \$value);\n";
                $scopeCode .= "    }";

                $scopes[] = $scopeCode;
            }
        }

        return $scopes;
    }

    private function generateAccessors(Panel $panel): array
    {
        $accessors = [];

        // Add accessors for common field patterns
        foreach ($panel->fields as $field) {
            if (str_contains($field->name, 'name') || str_contains($field->name, 'title')) {
                $accessorName = 'get' . ucfirst(str_replace('_', '', $field->name)) . 'Attribute';
                $accessorCode = "    public function {$accessorName}(\$value): string\n";
                $accessorCode .= "    {\n";
                $accessorCode .= "        return ucfirst(\$value);\n";
                $accessorCode .= "    }";

                $accessors[] = $accessorCode;
            }
        }

        return $accessors;
    }

    private function generateMigrationField(PanelField $field): string
    {
        $type = $field->type;
        $name = $field->name;
        $nullable = $field->nullable ? '->nullable()' : '';
        $default = $field->default ? "->default('{$field->default}')" : '';

        return "\$table->{$type}('{$name}')" . $nullable . $default . ";";
    }

    private function generateFactoryField(PanelField $field): ?string
    {
        $fakerType = $this->getFakerType($field->type);

        if (!$fakerType) {
            return null;
        }

        return "'{$field->name}' => \$this->faker->{$fakerType}";
    }

    private function getFakerType(string $fieldType): ?string
    {
        $types = [
            'string' => 'word',
            'text' => 'sentence',
            'integer' => 'numberBetween(1, 100)',
            'bigInteger' => 'numberBetween(1000, 9999)',
            'decimal' => 'randomFloat(2, 0, 1000)',
            'boolean' => 'boolean',
            'date' => 'date',
            'datetime' => 'dateTime',
            'timestamp' => 'dateTime',
            'json' => 'randomElement([["key" => "value"], ["foo" => "bar"]])',
            'email' => 'unique()->safeEmail',
            'url' => 'url',
            'tel' => 'phoneNumber',
        ];

        return $types[$fieldType] ?? null;
    }

    private function getRelatedTableName($relation): string
    {
        if (!empty($relation->relation_config['table_name'])) {
            return $relation->relation_config['table_name'];
        }

        return strtolower(str_plural($relation->related_model));
    }

    private function generateResourceForm(Panel $panel): string
    {
        $code = "    public static function form(Form \$form): Form\n";
        $code .= "    {\n";
        $code .= "        return \$form\n";
        $code .= "            ->schema([\n";

        foreach ($panel->fields as $field) {
            $formField = $this->generateFormField($field);
            $code .= "                {$formField},\n";
        }

        $code .= "            ]);\n";
        $code .= "    }\n\n";

        return $code;
    }

    private function generateResourceTable(Panel $panel): string
    {
        $defaultTable = $panel->tables->firstWhere('is_default', true);

        $code = "    public static function table(Table \$table): Table\n";
        $code .= "    {\n";
        $code .= "        return \$table\n";
        $code .= "            ->columns([\n";

        $columns = $defaultTable ? $defaultTable->columns : [];
        foreach ($columns as $column) {
            $tableColumn = $this->generateTableColumn($column);
            $code .= "                {$tableColumn},\n";
        }

        $code .= "            ])\n";

        // Add filters
        if ($defaultTable && !empty($defaultTable->filters)) {
            $code .= "            ->filters([\n";
            foreach ($defaultTable->filters as $filter) {
                $code .= "                Tables\\Filters\\{$filter['type']}::make('{$filter['name']}'),\n";
            }
            $code .= "            ])\n";
        }

        // Add actions
        if ($defaultTable && !empty($defaultTable->actions)) {
            $code .= "            ->actions([\n";
            foreach ($defaultTable->actions as $action) {
                $code .= "                Tables\\Actions\\{$action['type']}::make(),\n";
            }
            $code .= "            ])\n";
        }

        // Add bulk actions
        if ($defaultTable && !empty($defaultTable->bulk_actions)) {
            $code .= "            ->bulkActions([\n";
            foreach ($defaultTable->bulk_actions as $action) {
                $code .= "                Tables\\Actions\\{$action['type']}::make(),\n";
            }
            $code .= "            ])\n";
        }

        $code .= "            ->emptyStateActions([\n";
        $code .= "                Tables\\Actions\\CreateAction::make(),\n";
        $code .= "            ]);\n";
        $code .= "    }\n\n";

        return $code;
    }

    private function generateFormField(PanelField $field): string
    {
        $type = $field->filament_type;
        $name = $field->name;
        $label = $field->label;

        $code = "Forms\\Components\\{$type}::make('{$name}')";

        if ($label) {
            $code .= "->label('{$label}')";
        }

        if (in_array('required', $field->validation_rules ?? [])) {
            $code .= "->required()";
        }

        if ($field->field_config['placeholder'] ?? null) {
            $code .= "->placeholder('{$field->field_config['placeholder']}')";
        }

        return $code;
    }

    private function generateTableColumn($column): string
    {
        $type = $column['type'];
        $name = $column['name'];
        $label = $column['label'] ?? ucfirst(str_replace('_', ' ', $name));

        $code = "Tables\\Columns\\{$type}::make('{$name}')";

        if ($label) {
            $code .= "->label('{$label}')";
        }

        if ($column['searchable'] ?? false) {
            $code .= "->searchable()";
        }

        if ($column['sortable'] ?? false) {
            $code .= "->sortable()";
        }

        return $code;
    }

    private function generateResourceRelations(Panel $panel): string
    {
        $code = "    public static function getRelations(): array\n";
        $code .= "    {\n";
        $code .= "        return [\n";

        foreach ($panel->relations as $relation) {
            $resourceName = $relation->related_model . 'Resource';
            $code .= "            '{$relation->type}' => [\n";
            $methodName = isset($relation->relation_config['method_name']) ? $relation->relation_config['method_name'] : $this->generateRelationMethodName($relation);
            $code .= "                'relation' => '{$methodName}',\n";
            $code .= "                'resource' => {$resourceName}::class,\n";
            $code .= "            ],\n";
        }

        $code .= "        ];\n";
        $code .= "    }\n\n";

        return $code;
    }

    private function generateResourcePages(string $className): string
    {
        $code = "    public static function getPages(): array\n";
        $code .= "    {\n";
        $code .= "        return [\n";
        $code .= "            'index' => Pages\\List{$className}::route('/'),\n";
        $code .= "            'create' => Pages\\Create{$className}::route('/create'),\n";
        $code .= "            'view' => Pages\\View{$className}::route('/{record}'),\n";
        $code .= "            'edit' => Pages\\Edit{$className}::route('/{record}/edit'),\n";
        $code .= "        ];\n";
        $code .= "    }\n";

        return $code;
    }

    private function getRouteName(): string
    {
        // This would need to be implemented based on your routing structure
        return 'admin.panels.store';
    }
}
