<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Panel extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'navigation_group',
        'navigation_sort',
        'model_schema',
        'resource_config',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'model_schema' => 'array',
        'resource_config' => 'array',
        'is_active' => 'boolean',
    ];

    public function fields(): HasMany
    {
        return $this->hasMany(PanelField::class)->orderBy('order');
    }

    public function relations(): HasMany
    {
        return $this->hasMany(PanelRelation::class);
    }

    public function tables(): HasMany
    {
        return $this->hasMany(PanelTable::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function generateModelCode(): string
    {
        $schema = $this->model_schema;
        $className = $schema['model_name'] ?? str_replace(' ', '', $this->name);

        $code = "<?php\n\nnamespace App\Models;\n\n";

        // Add use statements for relations
        if (!empty($schema['relations'])) {
            foreach ($schema['relations'] as $relation) {
                if ($relation['type'] === 'belongsTo') {
                    $relatedModel = $relation['related_model'] ?? $relation['model'];
                    $code .= "use App\\Models\\{$relatedModel};\n";
                }
            }
        }

        $code .= "\nclass {$className} extends Model\n{\n";

        // Add fillable
        if (!empty($schema['fields'])) {
            $fillable = array_column($schema['fields'], 'name');
            $code .= "    protected \$fillable = ['" . implode("', '", $fillable) . "'];\n\n";
        }

        // Add relations
        if (!empty($schema['relations'])) {
            foreach ($schema['relations'] as $relation) {
                $methodName = $relation['method_name'] ?? strtolower($relation['type'] . '_' . $relation['model']);
                $code .= "    public function {$methodName}()\n    {\n";
                $code .= "        return \$this->{$relation['type']}({$relation['model']}::class);\n";
                $code .= "    }\n\n";
            }
        }

        $code .= "}\n";

        return $code;
    }

    public function generateMigrationCode(): string
    {
        $schema = $this->model_schema;
        $tableName = $schema['table_name'] ?? strtolower(str_replace(' ', '_', $this->name));

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
        if (!empty($schema['fields'])) {
            foreach ($schema['fields'] as $field) {
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
        if (!empty($schema['relations'])) {
            foreach ($schema['relations'] as $relation) {
                if ($relation['type'] === 'belongsTo') {
                    $foreignKey = $relation['foreign_key'] ?? strtolower($relation['model']) . '_id';
                    $relatedTable = $relation['related_table'] ?? strtolower(str_replace(' ', '_', $relation['model'])) . 's';
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

    public function generateResourceCode(): string
    {
        $schema = $this->model_schema;
        $className = $schema['model_name'] ?? str_replace(' ', '', $this->name);
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
        $code .= "    protected static ?string \$navigationIcon = '{$this->icon}';\n\n";

        if ($this->navigation_group) {
            $code .= "    protected static ?string \$navigationGroup = '{$this->navigation_group}';\n\n";
        }

        if ($this->navigation_sort) {
            $code .= "    protected static ?int \$navigationSort = {$this->navigation_sort};\n\n";
        }

        $code .= "    public static function form(Form \$form): Form\n";
        $code .= "    {\n";
        $code .= "        return \$form\n";
        $code .= "            ->schema([\n";

        // Add form fields
        if (!empty($schema['fields'])) {
            foreach ($schema['fields'] as $field) {
                $fieldName = $field['name'];
                $fieldLabel = $field['label'] ?? ucfirst(str_replace('_', ' ', $fieldName));
                $fieldType = $field['filament_type'] ?? 'TextInput';
                $required = !($field['nullable'] ?? false);

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
        if (!empty($schema['fields'])) {
            foreach ($schema['fields'] as $field) {
                $fieldName = $field['name'];
                $fieldLabel = $field['label'] ?? ucfirst(str_replace('_', ' ', $fieldName));
                $columnType = $field['column_type'] ?? 'TextColumn';

                $code .= "                Tables\\Columns\\{$columnType}::make('{$fieldName}')\n";
                $code .= "                    ->label('{$fieldLabel}')\n";
                $code .= "                    ->searchable(),\n";
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
}
