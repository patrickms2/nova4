<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Models\BuilderSchema;

use Livewire\WithFileUploads;

class BuilderGenerator extends Component
{
    public $modelName = '';
    public $fieldList = '';
    public $relationList = '';
    public $menuGroup = '';
    public $menuOrder = 0;
    public $models = [];
    public $importFile;

    public function mount()
    {
        // Cargar desde la base de datos
        $this->models = BuilderSchema::all()->map(function ($item) {
            return json_decode($item->schema, true);
        })->toArray();
    }

    public function addModel()
    {
        $fields = array_filter(array_map('trim', explode("\n", $this->fieldList)));
        $relations = array_filter(array_map('trim', explode("\n", $this->relationList)));

        $model = [
            'model' => $this->modelName,
            'fields' => array_map(fn($f) => ['name' => $f, 'type' => 'string'], $fields),
            'relations' => array_map(function ($r) {
                [$type, $model] = explode(':', $r);
                return ['type' => $type, 'model' => $model];
            }, $relations),
            'menu' => [
                'group' => $this->menuGroup,
                'order' => $this->menuOrder,
            ],
        ];

        BuilderSchema::create([
            'model' => $this->modelName,
            'schema' => json_encode($model),
        ]);

        $this->models[] = $model;

        $this->modelName = '';
        $this->fieldList = '';
        $this->relationList = '';
        $this->menuGroup = '';
        $this->menuOrder = 0;
    }

    public function generate()
    {
        foreach ($this->models as $resource) {
            $model = $resource['model'];
            $fields = $resource['fields'];
            $relations = $resource['relations'];

            Artisan::call("make:model $model -m");

            $migrationPath = base_path("database/migrations");
            $latestFile = collect(File::files($migrationPath))
                ->sortByDesc(fn($file) => $file->getCTime())
                ->first();

            $schema = "";
            foreach ($fields as $field) {
                $schema .= "\$table->{$field['type']}('{$field['name']}');\n            ";
            }

            foreach ($relations as $rel) {
                if ($rel['type'] === 'belongsTo') {
                    $foreignModel = strtolower($rel['model']);
                    $foreignKey = $foreignModel . '_id';
                    $schema .= "\$table->unsignedBigInteger('$foreignKey');\n            ";
                    $schema .= "\$table->foreign('$foreignKey')->references('id')->on('" . Str::plural($foreignModel) . "');\n            ";
                }
            }

            File::put($latestFile->getPathname(), preg_replace(
                '/Schema::create\(.*?function \(Blueprint \$table\) {/',
                "\$0\n            $schema",
                File::get($latestFile->getPathname())
            ));

            // Métodos en modelo
            $modelPath = app_path("Models/{$model}.php");
            if (File::exists($modelPath)) {
                $modelCode = File::get($modelPath);
                $relationMethods = "";
                foreach ($relations as $rel) {
                    $fnName = Str::camel($rel['type'] === 'belongsTo' ? strtolower($rel['model']) : Str::pluralStudly(strtolower($rel['model'])));
                    $relationMethods .= "\n    public function {$fnName}()\n    {\n        return \$this->{$rel['type']}({$rel['model']}::class);\n    }\n";
                }
                $modelCode = preg_replace('/\}\s*$/', "$relationMethods\n}", $modelCode);
                File::put($modelPath, $modelCode);
            }

            Artisan::call("make:filament-resource $model");

            $resourcePath = app_path("Filament/Resources/{$model}Resource.php");
            if (File::exists($resourcePath)) {
                $resourceCode = File::get($resourcePath);

                $navGroup = "public static function getNavigationGroup(): string { return '" . addslashes($resource['menu']['group']) . "'; }\n";
                $navSort  = "public static function getNavigationSort(): int { return " . intval($resource['menu']['order']) . "; }\n";

                $formBlock = "Form::make()->schema([\n";
                $tableBlock = "Table::make()->columns([\n";
                foreach ($fields as $field) {
                    $formBlock .= "TextInput::make('{$field['name']}'),\n";
                    $tableBlock .= "TextColumn::make('{$field['name']}'),\n";
                }
                $formBlock .= "]);";
                $tableBlock .= "]);";

                $resourceCode = preg_replace('/}\s*$/', "    $navGroup    $navSort}", $resourceCode);
                $resourceCode = preg_replace('/form\(.*?\{.*?return.*?;\s*\}/s', "public static function form(Form \$form) { return $formBlock }", $resourceCode);
                $resourceCode = preg_replace('/table\(.*?\{.*?return.*?;\s*\}/s', "public static function table(Table \$table) { return $tableBlock }", $resourceCode);

                File::put($resourcePath, $resourceCode);
            }
        }

        session()->flash('success', 'Todo generado y guardado en BD.');
    }

    public function render()
    {
return view('livewire.builder-generator')->layout('layouts.app');
    }

    public function exportSchemas()
    {
        $data = BuilderSchema::all()->pluck('schema')->map(fn($json) => json_decode($json, true))->values()->toArray();
        $filename = 'schemas_export_' . now()->timestamp . '.json';
        $path = storage_path('app/' . $filename);
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));

        return response()->download($path)->deleteFileAfterSend();
    }

    public function importSchemas()
    {
        $file = $this->importFile->getRealPath();
        $json = json_decode(file_get_contents($file), true);

        foreach ($json as $schema) {
            BuilderSchema::updateOrCreate(
                ['model' => $schema['model']],
                ['schema' => json_encode($schema)]
            );
        }

        $this->mount(); // recargar modelos
        session()->flash('success', 'Esquemas importados con éxito.');
    }

    public function loadModel($index)
    {
        $model = $this->models[$index];
        $this->modelName = $model['model'];
        $this->fieldList = implode("\n", array_column($model['fields'], 'name'));
        $this->relationList = implode("\n", array_map(fn($r) => $r['type'] . ':' . $r['model'], $model['relations']));
        $this->menuGroup = $model['menu']['group'] ?? '';
        $this->menuOrder = $model['menu']['order'] ?? 0;

        session()->flash('editing', $model['model']);
    }

    public function deleteModel($index)
    {
        $model = $this->models[$index]['model'];
        BuilderSchema::where('model', $model)->delete();
        unset($this->models[$index]);
        $this->models = array_values($this->models);
    }
}
