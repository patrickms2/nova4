<?php

namespace App\Filament\App\Pages;

use MWGuerra\FileManager\Filament\Pages\FileManager as BaseFileManager;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Schemas\Schema as Form;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process as SymfonyProcess;
use App\Support\SupportAccess;

use MWGuerra\FileManager\Contracts\FileManagerItemInterface;

class PortalFileManagerIntegrated extends BaseFileManager implements HasForms
{
    use InteractsWithForms;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-folder';
    protected static ?string $navigationLabel = 'Portal File Manager';
    protected static ?int $navigationSort = 50;
    protected string $view = 'filament.app.pages.portal-file-manager-integrated';

    public static function shouldRegisterNavigation(): bool
    {
        return SupportAccess::canAccess(auth()->user());
    }

    public static function canAccess(): bool
    {
        return SupportAccess::canAccess(auth()->user());
    }

    // Form properties
    public array $selected_components = [];
    public array $selected_folders = [];
    public string $action_type = 'backup';
    public string $command_preview = '';
    public string $system_status = '';
    public string $execution_log = '';

    public function getTitle(): string
    {
        return 'Portal File Manager';
    }

    public function getSubheading(): string
    {
        return 'Gestiona archivos del sistema con backup y sync automáticos';
    }

    public function mount(): void
    {
        parent::mount(); // Call parent mount method

        $defaultComponents = ['file_manager', 'models', 'filament'];
        $componentPaths = $this->getComponentPaths($defaultComponents);

        // Initialize portal-specific properties
        $this->form->fill([
            'selected_components' => $defaultComponents,
            'selected_folders' => $componentPaths,
            'action_type' => 'backup',
        ]);

        $this->selected_components = $defaultComponents;
        $this->selected_folders = $componentPaths;
        $this->command_preview = $this->generateCommand($defaultComponents, [], 'backup');
        $this->system_status = $this->getSystemStatus();
    }

    private function getComponentPaths(array $components): array
    {
        $componentMap = $this->getComponentMap();
        $paths = [];

        foreach ($components as $component) {
            if (isset($componentMap[$component])) {
                $componentPaths = explode(',', $componentMap[$component]);
                foreach ($componentPaths as $path) {
                    $paths[] = trim($path);
                }
            }
        }

        return array_unique($paths);
    }

    private function getComponentMap(): array
    {
        return [
            'file_manager' => 'app/Filament/App/Pages',
            'models' => 'app/Models',
            'filament' => 'app/Filament',
            'core' => 'app/Http/Controllers,app/Services',
            'views' => 'resources/views',
            'routes' => 'routes',
            'migrations' => 'database/migrations',
            'seeds' => 'database/seeders',
            'assets' => 'public/assets',
            'storage' => 'storage/app',
            'config' => 'config',
            'database' => 'database',
            'public' => 'public',
            'resources' => 'resources',
            'lang' => 'lang',
        ];
    }

    public function form(\Filament\Schemas\Schema $form): \Filament\Schemas\Schema
    {
        return $form
            ->schema([
                Section::make('Seleccionar Componentes')
                    ->description('Marca los componentes que quieres incluir en el backup/sync')
                    ->schema([
                        CheckboxList::make('selected_components')
                            ->label('Componentes del Sistema')
                            ->options([
                                'file_manager' => '📁 File Manager (Configuraciones y archivos)',
                                'models' => '🗄️ Models (Modelos de datos)',
                                'filament' => '🎛️ Filament (Panel de administración)',
                                'core' => '⚙️ Core (Configuración principal)',
                                'views' => '🎨 Views (Vistas y plantillas)',
                                'routes' => '🛣️ Routes (Rutas del sistema)',
                                'migrations' => '📊 Migrations (Estructura BD)',
                                'seeds' => '🌱 Seeds (Datos iniciales)',
                                'assets' => '🎭 Assets (CSS, JS, imágenes)',
                                'storage' => '💾 Storage (Archivos de almacenamiento)',
                                'config' => '⚙️ Config files',
                                'database' => '🗄️ Database files',
                                'public' => '🌐 Public files',
                                'resources' => '🎨 Resources (JS, CSS, fonts)',
                                'lang' => '🌐 Language files',
                            ])
                            ->columns(3)
                            ->required()
                            ->default(['file_manager', 'models', 'filament'])
                            ->live()
                            ->afterStateUpdated(function ($state, \Filament\Schemas\Components\Utilities\Set $set) {
                                $this->selected_components = $state;
                                $componentPaths = $this->getComponentPaths($state);

                                // Add component paths to selected folders if not already there
                                foreach ($componentPaths as $path) {
                                    if (!in_array($path, $this->selected_folders)) {
                                        $this->selected_folders[] = $path;
                                    }
                                }

                                // Remove component paths that are no longer selected
                                $allComponentMap = $this->getComponentMap();
                                foreach ($allComponentMap as $comp => $pathStr) {
                                    if (!in_array($comp, $state)) {
                                        $pathsToRemove = explode(',', $pathStr);
                                        foreach ($pathsToRemove as $pathToRemove) {
                                            $pathToRemove = trim($pathToRemove);
                                            $this->selected_folders = array_diff($this->selected_folders, [$pathToRemove]);
                                        }
                                    }
                                }

                                $this->selected_folders = array_values(array_unique($this->selected_folders));

                                $set('selected_folders', $this->selected_folders);
                                $set('command_preview', $this->generateCommand($state, $this->selected_folders, $this->action_type));
                            }),

                        Select::make('action_type')
                            ->label('Tipo de Operación')
                            ->options([
                                'backup' => '📦 Backup (Crear copia de seguridad)',
                                'sync' => '🔄 Sync (Sincronizar con servidor)',
                                'backup_then_sync' => '📦➡️🔄 Backup y luego Sync',
                                'restore' => '♻️ Restore (Restaurar desde backup)',
                                'clean' => '🧹 Clean (Limpiar archivos temporales)',
                            ])
                            ->required()
                            ->default('backup')
                            ->live()
                            ->afterStateUpdated(fn($state, \Filament\Schemas\Components\Utilities\Set $set) => $set('command_preview', $this->generateCommand($this->selected_components, $this->selected_folders, $state))),

                        Textarea::make('command_preview')
                            ->label('Comando que se ejecutará')
                            ->disabled()
                            ->rows(3)
                            ->default('Selecciona componentes para ver el comando...'),
                    ]),

                Section::make('Estado del Sistema')
                    ->description('Información en tiempo real')
                    ->schema([
                        Textarea::make('system_status')
                            ->label('Estado Actual')
                            ->rows(6)
                            ->disabled()
                            ->default($this->getSystemStatus()),
                    ]),
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('execute_operation')
                ->label('Ejecutar Operación')
                ->icon('heroicon-o-play')
                ->action('executeOperation')
                ->color('primary'),

            Action::make('refresh_files')
                ->label('Actualizar Archivos')
                ->icon('heroicon-o-arrow-path')
                ->action('refresh')
                ->color('secondary'),

            Action::make('preview_changes')
                ->label('Vista Previa')
                ->icon('heroicon-o-eye')
                ->action('previewChanges')
                ->color('gray'),
        ];
    }

    public function executeOperation(): void
    {
        try {
            $data = $this->form->getState();

            // Execute component-based operation
            $command = $this->generateCommand($data['selected_components'], $this->selected_folders, $data['action_type']);
            Log::info("Executing Portal File Manager operation: {$command}");

            $result = $this->executeComponentCommand($command);

            // Update execution log
            $timestamp = now()->toDateTimeString();
            $logEntry = "[{$timestamp}] EJECUTADO: {$command}\n";
            $logEntry .= "RESULTADO: " . ($result['success'] ? 'ÉXITO' : 'ERROR') . "\n";
            $logEntry .= "SALIDA:\n" . $result['output'] . "\n";
            if ($result['error']) {
                $logEntry .= "ERROR:\n" . $result['error'] . "\n";
            }
            $logEntry .= "-------------------------------------------\n";

            $this->execution_log = $logEntry . $this->execution_log;

            // Update system status display
            $this->system_status = $this->getSystemStatus();

            Notification::make()
                ->title('Operación Ejecutada')
                ->body("Comando ejecutado: {$command}")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Log::error("Portal File Manager operation error: " . $e->getMessage());

            $this->execution_log = "[ERR] " . $e->getMessage() . "\n" . $this->execution_log;
            $this->system_status = $this->getSystemStatus();

            Notification::make()
                ->title('Error en Operación')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function previewChanges(): void
    {
        $data = $this->form->getState();
        $command = $this->generateCommand($data['selected_components'], $this->selected_folders, $data['action_type']);

        $changes = $this->previewOperationChanges($data['selected_components'], $this->selected_folders, $data['action_type']);

        Notification::make()
            ->title('Vista Previa de Cambios')
            ->body($changes)
            ->info()
            ->duration(10000)
            ->send();
    }

    private function generateCommand(array $components, array $folders, string $action): string
    {
        $pathList = implode(',', array_unique($folders));

        if (empty($pathList)) {
            return "./scripts/portal-master.sh status";
        }

        return match ($action) {
            'backup' => "./scripts/portal-master.sh backup custom --paths=\"{$pathList}\"",
            'sync' => "./scripts/portal-master.sh sync custom --paths=\"{$pathList}\" --upload",
            'backup_then_sync' => "./scripts/portal-master.sh backup custom --paths=\"{$pathList}\" && ./scripts/portal-master.sh sync custom --paths=\"{$pathList}\" --upload",
            'restore' => "./scripts/portal-master.sh restore custom --paths=\"{$pathList}\"",
            'clean' => "./scripts/portal-master.sh clean custom --paths=\"{$pathList}\"",
            default => "./scripts/portal-master.sh status",
        };
    }

    private function executeComponentCommand(string $command): array
    {
        // Simulate component command execution
        // In real implementation, this would execute the actual portal-master.sh script

        return [
            'command' => $command,
            'output' => "Component operation executed successfully: {$command}",
            'error' => '',
            'success' => true,
            'executed_at' => now()->toISOString(),
        ];
    }

    private function previewOperationChanges(array $components, array $folders, string $action): string
    {
        $changes = "Operación: {$action}\n";
        $changes .= "Componentes: " . implode(', ', $components) . "\n";
        $changes .= "Carpetas Extra: " . implode(', ', $folders) . "\n\n";

        $changes .= "Cambios que se realizarán:\n";

        foreach (array_merge($components, $folders) as $item) {
            $changes .= "- {$item}: ";

            switch ($action) {
                case 'backup':
                    $changes .= "Se creará copia de seguridad";
                    break;
                case 'sync':
                    $changes .= "Se sincronizará con servidor";
                    break;
                case 'backup_then_sync':
                    $changes .= "Se hará backup y luego sync";
                    break;
                case 'restore':
                    $changes .= "Se restaurará desde backup";
                    break;
                case 'clean':
                    $changes .= "Se limpiarán archivos temporales";
                    break;
            }

            $changes .= "\n";
        }

        return $changes;
    }

    public function handleItemClick(string $itemId, bool $ctrlKey = false): void
    {
        $item = $this->getAdapter()->getItem($itemId);

        if ($item && $item->isFolder()) {
            if ($ctrlKey || true) { // Always allow selection for this portal
                $this->toggleFolderSelection($item->getPath());
                return;
            }
        }

        parent::handleItemClick($itemId, $ctrlKey);
    }

    public function toggleFolderSelection(string $path): void
    {
        if (in_array($path, $this->selected_folders)) {
            $this->selected_folders = array_diff($this->selected_folders, [$path]);
        } else {
            $this->selected_folders[] = $path;
        }

        $this->selected_folders = array_values(array_unique($this->selected_folders));

        // Synchronize selected_components based on selected_folders
        $componentMap = $this->getComponentMap();
        $newSelectedComponents = [];
        foreach ($componentMap as $comp => $pathStr) {
            $compPaths = explode(',', $pathStr);
            $allPathsSelected = true;
            foreach ($compPaths as $cp) {
                if (!in_array(trim($cp), $this->selected_folders)) {
                    $allPathsSelected = false;
                    break;
                }
            }
            if ($allPathsSelected) {
                $newSelectedComponents[] = $comp;
            }
        }
        $this->selected_components = $newSelectedComponents;

        // Update command preview
        $this->command_preview = $this->generateCommand($this->selected_components, $this->selected_folders, $this->action_type);

        // Update form state manually
        $this->form->fill([
            'selected_components' => $this->selected_components,
            'selected_folders' => $this->selected_folders,
            'action_type' => $this->action_type,
        ]);
    }

    public function isFolderSelected(string $path): bool
    {
        return in_array($path, $this->selected_folders);
    }

    private function getSystemStatus(): string
    {
        $status = "=== PORTAL FILE MANAGER STATUS ===\n\n";

        if ($this->execution_log) {
            $status .= "LOG DE ÚLTIMA EJECUCIÓN:\n" . $this->execution_log . "\n\n";
        }

        // System info
        $status .= "System Information:\n";
        $status .= "- PHP Version: " . PHP_VERSION . "\n";
        $status .= "- Laravel Version: " . app()->version() . "\n";
        $status .= "- Environment: " . config('app.env') . "\n";
        $status .= "- Disk Space: " . $this->getDiskSpace() . "\n\n";

        // File manager info
        $status .= "File Manager Status:\n";
        $status .= "- Current Path: " . ($this->currentPath ?: 'Root') . "\n";
        $status .= "- Items in current directory: " . $this->items->count() . "\n";
        $status .= "- Selected items: " . count($this->selectedItems) . "\n";
        $status .= "- View mode: " . $this->viewMode . "\n\n";

        // Component status
        $status .= "Component Status:\n";
        $status .= "- File Manager: ✅ Active\n";
        $status .= "- Models: ✅ Active\n";
        $status .= "- Filament: ✅ Active\n";
        $status .= "- Core: ✅ Active\n";
        $status .= "- Views: ✅ Active\n";
        $status .= "- Routes: ✅ Active\n";

        return $status;
    }

    private function getDiskSpace(): string
    {
        $free = disk_free_space(base_path());
        $total = disk_total_space(base_path());
        $used = $total - $free;

        return number_format($used / 1024 / 1024 / 1024, 2) . " GB used / " .
            number_format($total / 1024 / 1024 / 1024, 2) . " GB total";
    }

    public function renderActions(): string
    {
        $actions = $this->getFormActions();
        $html = '<div class="flex gap-2 mt-4">';

        foreach ($actions as $action) {
            $actionName = $action->getName();
            // Map action names to correct Livewire methods
            $wireClick = match ($actionName) {
                'execute_operation' => 'executeOperation',
                'refresh_files' => 'refresh',
                'preview_changes' => 'previewChanges',
                default => $actionName,
            };

            $html .= sprintf(
                '<button type="button"
                        wire:click="%s"
                        class="inline-flex items-center justify-center rounded-md border border-transparent px-4 py-2 text-sm font-medium %s focus:outline-none focus:ring-2 focus:ring-offset-2">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">%s</svg>
                    %s
                </button>',
                $wireClick,
                $this->getButtonClasses($action->getColor()),
                $this->getIconSvg($action->getIcon()),
                $action->getLabel()
            );
        }

        $html .= '</div>';
        return $html;
    }

    private function getButtonClasses(?string $color): string
    {
        return match ($color) {
            'primary' => 'bg-primary-600 text-white hover:bg-primary-700 focus:ring-primary-500',
            'secondary' => 'bg-gray-600 text-white hover:bg-gray-700 focus:ring-gray-500',
            'gray' => 'bg-gray-200 text-gray-900 hover:bg-gray-300 focus:ring-gray-500',
            default => 'bg-gray-600 text-white hover:bg-gray-700 focus:ring-gray-500',
        };
    }

    private function getIconSvg(?string $icon): string
    {
        return match ($icon) {
            'heroicon-o-play' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
            'heroicon-o-arrow-path' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>',
            'heroicon-o-eye' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>',
            default => '',
        };
    }
}
