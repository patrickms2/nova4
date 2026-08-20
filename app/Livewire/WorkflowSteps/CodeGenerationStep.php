<?php

namespace App\Livewire\WorkflowSteps;

use Livewire\Component;
use App\Models\Panel;

class CodeGenerationStep extends Component
{
    public $workflowData;
    public $stepValidation = [];
    public $generatedCode = [];
    public $codePreview = [];
    public $reviewStatus = 'pending';
    public $activeTab = 'model';

    protected $rules = [
        'reviewStatus' => 'required|in:pending,approved,rejected',
    ];

    public function mount($workflowData = [])
    {
        $this->workflowData = $workflowData;
        $this->generateCodePreview();
    }

    public function generateCodePreview()
    {
        $panelData = $this->workflowData['panel_setup'] ?? [];
        $modelData = $this->workflowData['model_design'] ?? [];
        $relationsData = $this->workflowData['relations_setup'] ?? [];
        $resourceData = $this->workflowData['resource_config'] ?? [];

        $this->codePreview = [
            'model' => $this->generateModelCode($panelData, $modelData, $relationsData),
            'migration' => $this->generateMigrationCode($panelData, $modelData),
            'resource' => $this->generateResourceCode($panelData, $modelData, $resourceData),
            'factory' => $this->generateFactoryCode($panelData, $modelData),
            'seeder' => $this->generateSeederCode($panelData, $modelData),
        ];

        $this->generatedCode = [
            'files' => [
                'app/Models/' . $modelData['model_name'] . '.php',
                'database/migrations/' . date('Y_m_d_His') . '_create_' . $modelData['table_name'] . '_table.php',
                'app/Filament/Resources/' . $modelData['model_name'] . 'Resource.php',
                'database/factories/' . $modelData['model_name'] . 'Factory.php',
                'database/seeders/' . $modelData['model_name'] . 'Seeder.php',
            ],
            'status' => 'preview',
        ];
    }

    private function generateModelCode($panelData, $modelData, $relationsData)
    {
        $className = $modelData['model_name'] ?? 'Model';
        $tableName = $modelData['table_name'] ?? 'table';
        $fields = $modelData['fields'] ?? [];
        $relations = $relationsData['relations'] ?? [];

        $code = "<?php\n\n";
        $code .= "namespace App\Models;\n\n";

        // Add use statements for relations
        $relatedModels = [];
        foreach ($relations as $relation) {
            $relatedModel = $relation['related_model'] ?? '';
            if (!empty($relatedModel) && !in_array($relatedModel, $relatedModels)) {
                $code .= "use App\\Models\\{$relatedModel};\n";
                $relatedModels[] = $relatedModel;
            }
        }

        $code .= "\nclass {$className} extends Model\n{\n";

        // Add fillable
        if (!empty($fields)) {
            $fillable = array_column($fields, 'name');
            $code .= "    protected \$fillable = ['" . implode("', '", $fillable) . "'];\n\n";
        }

        // Add casts if needed
        $casts = [];
        foreach ($fields as $field) {
            if ($field['type'] === 'boolean') {
                $casts[] = $field['name'];
            } elseif ($field['type'] === 'json') {
                $casts[] = $field['name'];
            } elseif ($field['type'] === 'date') {
                $casts[] = $field['name'];
            } elseif ($field['type'] === 'datetime') {
                $casts[] = $field['name'];
            }
        }

        if (!empty($casts)) {
            $code .= "    protected \$casts = [\n";
            foreach ($casts as $cast) {
                $fieldType = array_column($fields, 'type', 'name')[$cast] ?? 'string';
                $castType = match($fieldType) {
                    'boolean' => 'boolean',
                    'json' => 'array',
                    'date' => 'date',
                    'datetime' => 'datetime',
                    default => 'string',
                };
                $code .= "        '{$cast}' => '{$castType}',\n";
            }
            $code .= "    ];\n\n";
        }

        // Add relations
        if (!empty($relations)) {
            foreach ($relations as $relation) {
                $methodName = $relation['method_name'] ?? strtolower($relation['type'] . '_' . $relation['related_model']);
                $relatedModel = $relation['related_model'] ?? 'Model';

                $code .= "    public function {$methodName}()\n    {\n";
                $code .= "        return \$this->{$relation['type']}({$relatedModel}::class";

                // Add foreign key for belongsTo
                if ($relation['type'] === 'belongsTo' && !empty($relation['foreign_key'])) {
                    $code .= ", '{$relation['foreign_key']}'";
                }

                $code .= ");\n";
                $code .= "    }\n\n";
            }
        }

        $code .= "}\n";

        return $code;
    }

    private function generateMigrationCode($panelData, $modelData)
    {
        $tableName = $modelData['table_name'] ?? 'table';
        $fields = $modelData['fields'] ?? [];
        $relations = $this->workflowData['relations_setup']['relations'] ?? [];

        $code = "<?php\n\n";
        $code .= "use Illuminate\\Database\\Migrations\\Migration;\n";
        $code .= "use Illuminate\\Database\\Schema\\Blueprint;\n";
        $code .= "use Illuminate\\Support\\Facades\\Schema;\n\n";
        $code .= "return new class extends Migration\n";
        $code .= "{\n";
        $code .= "    public function up(): void\n";
        $code .= "    {\n";
        $code .= "        Schema::create('{$tableName}', function (Blueprint \$table) {\n";
        $code .= "            \$table->id();\n\n";

        // Add fields
        if (!empty($fields)) {
            foreach ($fields as $field) {
                $type = $field['type'] ?? 'string';
                $name = $field['name'];
                $nullable = $field['nullable'] ?? false;
                $default = $field['default'] ?? null;

                $line = "            \$table->{$type}('{$name}')";
                if ($nullable) $line .= "->nullable()";
                if ($default !== null) $line .= "->default('{$default}')";
                $line .= ";\n";
                $code .= $line;
            }
            $code .= "\n";
        }

        // Add foreign keys for relations
        if (!empty($relations)) {
            foreach ($relations as $relation) {
                if ($relation['type'] === 'belongsTo') {
                    $foreignKey = $relation['foreign_key'] ?? strtolower($relation['related_model']) . '_id';
                    $relatedTable = strtolower(str_replace(' ', '_', $relation['related_model'])) . 's';
                    $code .= "            \$table->foreignId('{$foreignKey}')->constrained('{$relatedTable}');\n";
                }
            }
        }

        $code .= "            \$table->timestamps();\n";
        $code .= "        });\n";
        $code .= "    }\n\n";
        $code .= "    public function down(): void\n";
        $code .= "    {\n";
        $code .= "        Schema::dropIfExists('{$tableName}');\n";
        $code .= "    }\n";
        $code .= "};\n";

        return $code;
    }

    private function generateResourceCode($panelData, $modelData, $resourceData)
    {
        $className = $modelData['model_name'] ?? 'Model';
        $resourceName = $className . 'Resource';
        $panelName = $panelData['name'] ?? 'Panel';
        $panelIcon = $panelData['icon'] ?? 'heroicon-o-cube';
        $navigationGroup = $panelData['navigation_group'] ?? '';
        $navigationSort = $panelData['navigation_sort'] ?? 0;

        $formFields = $resourceData['form_fields'] ?? [];
        $tableColumns = $resourceData['table_columns'] ?? [];

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
        $code .= "    protected static ?string \$navigationIcon = '{$panelIcon}';\n\n";

        if (!empty($navigationGroup)) {
            $code .= "    protected static ?string \$navigationGroup = '{$navigationGroup}';\n\n";
        }

        if ($navigationSort > 0) {
            $code .= "    protected static ?int \$navigationSort = {$navigationSort};\n\n";
        }

        $code .= "    public static function form(Form \$form): Form\n";
        $code .= "    {\n";
        $code .= "        return \$form\n";
        $code .= "            ->schema([\n";

        // Add form fields
        if (!empty($formFields)) {
            foreach ($formFields as $field) {
                $fieldName = $field['name'];
                $fieldLabel = $field['label'];
                $fieldType = $field['type'];
                $required = $field['required'] ?? false;

                $code .= "                Forms\\Components\\{$fieldType}::make('{$fieldName}')\n";
                $code .= "                    ->label('{$fieldLabel}')\n";
                if ($required) $code .= "                    ->required()\n";
                $code .= "                    ->maxLength(255),\n";
            }
        }

        $code .= "            ]);\n";
        $code .= "    }\n\n";

        $code .= "    public static function table(Table \$table): Table\n";
        $code .= "    {\n";
        $code .= "        return \$table\n";
        $code .= "            ->columns([\n";

        // Add table columns
        if (!empty($tableColumns)) {
            foreach ($tableColumns as $column) {
                $columnName = $column['name'];
                $columnLabel = $column['label'];
                $columnType = $column['type'];
                $searchable = $column['searchable'] ?? false;
                $sortable = $column['sortable'] ?? false;

                $code .= "                Tables\\Columns\\{$columnType}::make('{$columnName}')\n";
                $code .= "                    ->label('{$columnLabel}')\n";
                if ($searchable) $code .= "                    ->searchable()\n";
                if ($sortable) $code .= "                    ->sortable()\n";
                $code .= ",\n";
            }
        }

        $code .= "            ])\n";
        $code .= "            ->filters([\n";
        $code .= "                //\n";
        $code .= "            ])\n";
        $code .= "            ->actions([\n";
        $code .= "                Tables\\Actions\\EditAction::make(),\n";
        $code .= "                Tables\\Actions\\DeleteAction::make(),\n";
        $code .= "            ])\n";
        $code .= "            ->bulkActions([\n";
        $code .= "                Tables\\Actions\\DeleteBulkAction::make(),\n";
        $code .= "            ]);\n";
        $code .= "    }\n\n";

        $code .= "    public static function getRelations(): array\n";
        $code .= "    {\n";
        $code .= "        return [\n";
        $code .= "            //\n";
        $code .= "        ];\n";
        $code .= "    }\n\n";

        $code .= "    public static function getPages(): array\n";
        $code .= "    {\n";
        $code .= "        return [\n";
        $code .= "            'index' => Pages\\List{$className}::route('/'),\n";
        $code .= "            'create' => Pages\\Create{$className}::route('/create'),\n";
        $code .= "            'edit' => Pages\\Edit{$className}::route('/{record}/edit'),\n";
        $code .= "        ];\n";
        $code .= "    }\n";
        $code .= "}\n";

        return $code;
    }

    private function generateFactoryCode($panelData, $modelData)
    {
        $className = $modelData['model_name'] ?? 'Model';
        $fields = $modelData['fields'] ?? [];

        $code = "<?php\n\n";
        $code .= "namespace Database\\Factories;\n\n";
        $code .= "use Illuminate\\Database\\Eloquent\\Factories\\Factory;\n";
        $code .= "use App\\Models\\{$className};\n\n";

        $code .= "/**\n";
        $code .= " * @extends \\Illuminate\\Database\\Eloquent\\Factories\\Factory<\\App\\Models\\{$className}>\n";
        $code .= " */\n";
        $code .= "class {$className}Factory extends Factory\n";
        $code .= "{\n";
        $code .= "    protected \$model = {$className}::class;\n\n";

        $code .= "    public function definition(): array\n";
        $code .= "    {\n";
        $code .= "        return [\n";

        if (!empty($fields)) {
            foreach ($fields as $field) {
                $fieldName = $field['name'];
                $fieldType = $field['type'];
                $nullable = $field['nullable'] ?? false;

                if ($fieldName === 'id') continue;

                $fakerMethod = $this->getFakerMethod($fieldType, $nullable);
                $code .= "            '{$fieldName}' => {$fakerMethod},\n";
            }
        }

        $code .= "        ];\n";
        $code .= "    }\n";
        $code .= "}\n";

        return $code;
    }

    private function generateSeederCode($panelData, $modelData)
    {
        $className = $modelData['model_name'] ?? 'Model';
        $tableName = $modelData['table_name'] ?? 'table';

        $code = "<?php\n\n";
        $code .= "namespace Database\\Seeders;\n\n";
        $code .= "use Illuminate\\Database\\Console\\Seeds\\WithoutModelEvents;\n";
        $code .= "use Illuminate\\Database\\Seeder;\n";
        $code .= "use App\\Models\\{$className};\n\n";

        $code .= "class {$className}Seeder extends Seeder\n";
        $code .= "{\n";
        $code .= "    public function run(): void\n";
        $code .= "    {\n";
        $code .= "        {$className}::factory()->count(10)->create();\n";
        $code .= "    }\n";
        $code .= "}\n";

        return $code;
    }

    private function getFakerMethod($fieldType, $nullable = false)
    {
        $fakerMethods = [
            'string' => 'fake()->sentence()',
            'text' => 'fake()->paragraph()',
            'integer' => 'fake()->numberBetween(1, 1000)',
            'bigint' => 'fake()->numberBetween(1, 1000000)',
            'decimal' => 'fake()->randomFloat(2, 0, 1000)',
            'boolean' => 'fake()->boolean()',
            'date' => 'fake()->date()',
            'datetime' => 'fake()->datetime()',
            'timestamp' => 'fake()->datetime()',
            'json' => 'fake()->randomElement([\'a\', \'b\', \'c\'])',
        ];

        $method = $fakerMethods[$fieldType] ?? 'fake()->sentence()';

        return $nullable ? "fake()->randomElement([{$method}, null])" : $method;
    }

    public function approveCode()
    {
        $this->reviewStatus = 'approved';
        $this->workflowData['code_generation']['review_status'] = 'approved';
        $this->workflowData['code_generation']['generated_files'] = $this->generatedCode['files'];
    }

    public function rejectCode()
    {
        $this->reviewStatus = 'rejected';
        $this->workflowData['code_generation']['review_status'] = 'rejected';
    }

    public function validateStep()
    {
        $this->validate();

        if ($this->reviewStatus === 'rejected') {
            $this->stepValidation['review'] = 'Code must be approved before proceeding';
            return false;
        }

        $this->stepValidation = [];
        return true;
    }

    public function getStepData()
    {
        return $this->workflowData['code_generation'] ?? [];
    }

    public function render()
    {
        return view('livewire.workflow-steps.code-generation-step', [
            'codePreview' => $this->codePreview,
            'generatedFiles' => $this->generatedCode['files'] ?? [],
        ]);
    }
}
