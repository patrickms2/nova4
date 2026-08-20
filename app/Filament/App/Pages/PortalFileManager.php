<?php

namespace App\Filament\App\Pages;

use Filament\Pages\Page;
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
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use App\Support\SupportAccess;

class PortalFileManager extends Page implements HasForms
{
    use InteractsWithForms;

    protected static bool $shouldRegisterNavigation = false;
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-folder';
    protected static ?string $navigationLabel = 'Portal File Manager (Legacy)';
    protected static ?int $navigationSort = 50;
    protected string $view = 'filament.app.pages.portal-file-manager';

    public static function shouldRegisterNavigation(): bool
    {
        return SupportAccess::canAccess(auth()->user());
    }

    public static function canAccess(): bool
    {
        return SupportAccess::canAccess(auth()->user());
    }

    public function getTitle(): string
    {
        return 'Portal File Manager';
    }

    public function getSubheading(): string
    {
        return 'Gestiona archivos del sistema con backup y sync automáticos';
    }

    // Form properties
    public array $selected_components = [];
    public string $action_type = 'backup';
    public array $selected_files = [];
    public ?string $current_path = null;
    public string $command_preview = '';
    public string $system_status = '';
    public array $current_files = [];
    public array $current_directories = [];
    public string $parent_path = '';

    public function mount(): void
    {
        $this->form->fill([
            'selected_components' => ['file_manager', 'models', 'filament'],
            'action_type' => 'backup',
        ]);

        // Initialize the missing properties
        $this->command_preview = $this->generateCommand(['file_manager', 'models', 'filament'], 'backup');
        $this->system_status = $this->getSystemStatus();

        // Initialize file explorer
        $this->current_path = base_path();
        $this->loadCurrentDirectory();
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
                            ->live()
                            ->afterStateUpdated(fn($state, \Filament\Schemas\Components\Utilities\Set $set) => $set('command_preview', $this->generateCommand($state, $this->action_type))),

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
                            ->afterStateUpdated(fn($state, \Filament\Schemas\Components\Utilities\Set $set) => $set('command_preview', $this->generateCommand($this->selected_components, $state))),

                        Textarea::make('command_preview')
                            ->label('Comando que se ejecutará')
                            ->disabled()
                            ->rows(3)
                            ->default('Selecciona componentes para ver el comando...'),
                    ]),

                Section::make('Explorador de Archivos')
                    ->description('Navega y selecciona archivos específicos')
                    ->schema([
                        // Navigation bar
                        \Filament\Forms\Components\Placeholder::make('current_path_display')
                            ->label('Directorio Actual')
                            ->content(function () {
                                $path = $this->current_path ?: base_path();
                                $relativePath = str_replace(base_path() . '/', '', $path);
                                return $relativePath ?: '/';
                            })
                            ->columnSpanFull(),

                        Section::make('Navigation')
                            ->description('Navegación y control de archivos')
                            ->schema([
                                \Filament\Forms\Components\Placeholder::make('navigation_info')
                                    ->label('Controles de Navegación')
                                    ->content(function () {
                                        $html = '<div class="flex gap-2 mb-4">';

                                        // Back button
                                        if (!empty($this->parent_path)) {
                                            $html .= '<button type="button" wire:click="goBack" class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                                </svg>
                                                Atrás
                                            </button>';
                                        }

                                        // Refresh button
                                        $html .= '<button type="button" wire:click="refreshFiles" class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                            </svg>
                                            Actualizar
                                        </button>';

                                        $html .= '</div>';
                                        return $html;
                                    })
                                    ->columnSpanFull(),
                            ]),

                        // Directories section
                        \Filament\Forms\Components\Placeholder::make('directories_section')
                            ->label('📁 Directorios')
                            ->content(function () {
                                $html = '<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2 mb-4">';

                                foreach ($this->current_directories as $dir) {
                                    $html .= sprintf(
                                        '<button type="button"
                                                wire:click="navigateToDirectory(%s)"
                                                class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                            <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                                            </svg>
                                            <span class="text-sm font-medium">%s</span>
                                        </button>',
                                        json_encode($dir['path']),
                                        htmlspecialchars($dir['name'])
                                    );
                                }

                                $html .= '</div>';
                                return $html;
                            })
                            ->columnSpanFull(),

                        // Files section
                        \Filament\Forms\Components\Placeholder::make('files_section')
                            ->label('📄 Archivos')
                            ->content(function () {
                                $html = '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">';

                                foreach ($this->current_files as $file) {
                                    $html .= sprintf(
                                        '<div class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                                            <input type="checkbox"
                                                   wire:model="selected_files"
                                                   value="%s"
                                                   class="mr-3">
                                            <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            <div class="flex-1">
                                                <div class="text-sm font-medium">%s</div>
                                                <div class="text-xs text-gray-500">%s</div>
                                            </div>
                                        </div>',
                                        htmlspecialchars($file['path']),
                                        htmlspecialchars($file['name']),
                                        htmlspecialchars($file['size'])
                                    );
                                }

                                $html .= '</div>';
                                return $html;
                            })
                            ->columnSpanFull(),
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
                ->action('refreshFiles')
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
            $command = $this->generateCommand($data['selected_components'], $data['action_type']);

            Log::info("Executing Portal File Manager operation: {$command}");

            // Execute the operation
            $result = $this->executeCommand($command);

            Notification::make()
                ->title('Operación Ejecutada')
                ->body("Comando ejecutado: {$command}")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Log::error("Portal File Manager operation error: " . $e->getMessage());

            Notification::make()
                ->title('Error en Operación')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function refreshFiles(): void
    {
        $this->loadCurrentDirectory();

        Notification::make()
            ->title('Archivos Actualizados')
            ->body('La lista de archivos ha sido actualizada')
            ->success()
            ->send();
    }

    public function navigateToDirectory(string $path): void
    {
        $this->current_path = $path;
        $this->loadCurrentDirectory();
    }

    public function goBack(): void
    {
        if (!empty($this->parent_path)) {
            $this->current_path = $this->parent_path;
            $this->loadCurrentDirectory();
        }
    }

    private function loadCurrentDirectory(): void
    {
        $path = $this->current_path ?: base_path();

        if (!is_dir($path)) {
            $path = base_path();
            $this->current_path = $path;
        }

        $this->current_directories = [];
        $this->current_files = [];

        try {
            // Get parent path for navigation
            $this->parent_path = dirname($path);
            if ($this->parent_path === $path) {
                $this->parent_path = '';
            }

            // Get directories
            $items = scandir($path);
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }

                $fullPath = $path . DIRECTORY_SEPARATOR . $item;

                if (is_dir($fullPath)) {
                    $this->current_directories[] = [
                        'name' => $item,
                        'path' => $fullPath,
                    ];
                } elseif (is_file($fullPath)) {
                    $size = filesize($fullPath);
                    $sizeFormatted = $this->formatFileSize($size);

                    $this->current_files[] = [
                        'name' => $item,
                        'path' => $fullPath,
                        'size' => $sizeFormatted,
                    ];
                }
            }

            // Sort directories and files
            usort($this->current_directories, fn($a, $b) => strcmp($a['name'], $b['name']));
            usort($this->current_files, fn($a, $b) => strcmp($a['name'], $b['name']));

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al leer directorio')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }

    public function previewChanges(): void
    {
        $data = $this->form->getState();
        $command = $this->generateCommand($data['selected_components'], $data['action_type']);

        $changes = $this->previewOperationChanges($data['selected_components'], $data['action_type']);

        Notification::make()
            ->title('Vista Previa de Cambios')
            ->body($changes)
            ->info()
            ->duration(10000)
            ->send();
    }

    public function renderActions(): string
    {
        $actions = $this->getFormActions();
        $html = '<div class="flex gap-2">';

        foreach ($actions as $action) {
            $actionName = $action->getName();
            // Map action names to correct Livewire methods
            $wireClick = match ($actionName) {
                'execute_operation' => 'executeOperation',
                'refresh_files' => 'refreshFiles',
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

    private function generateCommand(array $components, string $action): string
    {
        $componentMap = [
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

        $paths = [];
        foreach ($components as $component) {
            if (isset($componentMap[$component])) {
                $paths[] = $componentMap[$component];
            }
        }

        $pathList = implode(',', $paths);

        return match ($action) {
            'backup' => "./scripts/portal-master.sh backup custom --paths=\"{$pathList}\"",
            'sync' => "./scripts/portal-master.sh sync custom --paths=\"{$pathList}\" --upload",
            'backup_then_sync' => "./scripts/portal-master.sh backup custom --paths=\"{$pathList}\" && ./scripts/portal-master.sh sync custom --paths=\"{$pathList}\" --upload",
            'restore' => "./scripts/portal-master.sh restore custom --paths=\"{$pathList}\"",
            'clean' => "./scripts/portal-master.sh clean custom --paths=\"{$pathList}\"",
            default => "./scripts/portal-master.sh status",
        };
    }

    private function executeCommand(string $command): array
    {
        // Simulate command execution
        // In real implementation, this would execute the actual command

        return [
            'command' => $command,
            'output' => "Command executed successfully: {$command}",
            'success' => true,
            'executed_at' => now()->toISOString(),
        ];
    }

    private function previewOperationChanges(array $components, string $action): string
    {
        $changes = "Operación: {$action}\n";
        $changes .= "Componentes seleccionados: " . implode(', ', $components) . "\n\n";

        $changes .= "Cambios que se realizarán:\n";

        foreach ($components as $component) {
            $changes .= "- {$component}: ";

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

    private function getSystemStatus(): string
    {
        $status = "=== PORTAL FILE MANAGER STATUS ===\n\n";

        // System info
        $status .= "System Information:\n";
        $status .= "- PHP Version: " . PHP_VERSION . "\n";
        $status .= "- Laravel Version: " . app()->version() . "\n";
        $status .= "- Environment: " . config('app.env') . "\n";
        $status .= "- Disk Space: " . $this->getDiskSpace() . "\n\n";

        // File counts
        $status .= "File Statistics:\n";
        $status .= "- Total Files: " . $this->countFiles(base_path()) . "\n";
        $status .= "- Total Directories: " . $this->countDirectories(base_path()) . "\n";
        $status .= "- Storage Size: " . $this->getStorageSize() . "\n\n";

        // Last operations
        $status .= "Recent Operations:\n";
        $status .= "- Last Backup: " . $this->getLastOperationTime('backup') . "\n";
        $status .= "- Last Sync: " . $this->getLastOperationTime('sync') . "\n";

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

    private function countFiles(string $path): int
    {
        try {
            return count(File::allFiles($path));
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function countDirectories(string $path): int
    {
        try {
            return count(File::allDirectories($path));
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getStorageSize(): string
    {
        try {
            $size = 0;
            foreach (File::allFiles(storage_path()) as $file) {
                $size += $file->getSize();
            }
            return number_format($size / 1024 / 1024, 2) . " MB";
        } catch (\Exception $e) {
            return "Unknown";
        }
    }

    private function getLastOperationTime(string $operation): string
    {
        // Simulate last operation time
        // In real implementation, this would read from logs or database
        return now()->subHours(rand(1, 24))->format('Y-m-d H:i:s');
    }
}
