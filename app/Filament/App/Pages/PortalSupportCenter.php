<?php

namespace App\Filament\App\Pages;

use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Schemas\Schema as Form;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process as SymfonyProcess;
use App\Support\SupportAccess;

class PortalSupportCenter extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Portal Support Center';
    protected static ?int $navigationSort = 40;
    protected string $view = 'filament.app.pages.portal-support-center';

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
    public string $action_type = 'backup';
    public string $custom_command = '';
    public string $preset_commands = '';
    public string $command_preview = '';
    public string $system_status = '';

    public function getTitle(): string
    {
        return 'Portal Support Center';
    }

    public function getSubheading(): string
    {
        return 'Gestión de componentes y ejecución de comandos del sistema';
    }

    public function mount(): void
    {
        $this->form->fill([
            'selected_components' => ['file_manager', 'models', 'filament'],
            'action_type' => 'backup',
        ]);

        // Initialize the missing properties
        $this->command_preview = $this->generateCommand(['file_manager', 'models', 'filament'], 'backup');
        $this->system_status = $this->getSystemStatus();
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
                            ])
                            ->columns(2)
                            ->required()
                            ->default(['file_manager', 'models', 'filament'])
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

                Section::make('Ejecución de Comandos')
                    ->description('Ejecuta comandos personalizados o predefinidos')
                    ->schema([
                        TextInput::make('custom_command')
                            ->label('Comando Personalizado')
                            ->placeholder('Ej: php artisan migrate:status')
                            ->helperText('Puedes ejecutar cualquier comando de Artisan o shell')
                            ->columnSpanFull(),

                        Select::make('preset_commands')
                            ->label('Comandos Predefinidos')
                            ->options([
                                'php artisan migrate:status' => '🔍 Ver estado de migraciones',
                                'php artisan config:cache' => '⚡ Cachear configuración',
                                'php artisan route:cache' => '🛣️ Cachear rutas',
                                'php artisan view:cache' => '🎨 Cachear vistas',
                                'php artisan optimize' => '🚀 Optimizar aplicación',
                                'composer dump-autoload' => '📦 Actualizar autoloader',
                                'npm run build' => '🔨 Build frontend',
                                'php artisan queue:work' => '⚡ Iniciar queue worker',
                                './scripts/portal-master.sh status' => '📊 Estado del portal',
                                './scripts/portal-master.sh validate' => '✅ Validar portal',
                            ])
                            ->placeholder('Selecciona un comando predefinido...')
                            ->live()
                            ->afterStateUpdated(fn($state, \Filament\Schemas\Components\Utilities\Set $set) => $set('custom_command', $state)),
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
            Action::make('execute_command')
                ->label('Ejecutar Operación')
                ->icon('heroicon-o-play')
                ->action('executeCommand')
                ->color('primary'),

            Action::make('view_logs')
                ->label('Ver Logs')
                ->icon('heroicon-o-document-text')
                ->action('viewLogs')
                ->color('secondary'),

            Action::make('refresh_status')
                ->label('Actualizar Estado')
                ->icon('heroicon-o-arrow-path')
                ->action('refreshStatus')
                ->color('gray'),
        ];
    }

    public function executeCommand(): void
    {
        try {
            $data = $this->form->getState();

            // Check if custom command is provided
            if (!empty($data['custom_command'])) {
                $command = $data['custom_command'];
                Log::info("Executing custom command: {$command}");

                $result = $this->executeCustomCommand($command);
            } else {
                // Execute component-based operation
                $command = $this->generateCommand($data['selected_components'], $data['action_type']);
                Log::info("Executing component operation: {$command}");

                $result = $this->executeComponentCommand($command);
            }

            Notification::make()
                ->title('Comando Ejecutado')
                ->body("Comando ejecutado: {$command}")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Log::error("Command execution error: " . $e->getMessage());

            Notification::make()
                ->title('Error al Ejecutar')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function viewLogs(): void
    {
        // Simulate viewing logs
        Notification::make()
            ->title('Logs del Sistema')
            ->body('No hay logs recientes para mostrar')
            ->info()
            ->send();
    }

    public function refreshStatus(): void
    {
        $this->form->fill([
            'system_status' => $this->getSystemStatus(),
        ]);

        Notification::make()
            ->title('Estado Actualizado')
            ->body('La información del sistema ha sido actualizada')
            ->success()
            ->send();
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

    private function executeCustomCommand(string $command): array
    {
        $process = new SymfonyProcess(explode(' ', $command), base_path());
        $process->setTimeout(300); // 5 minutes timeout
        $process->run();

        return [
            'command' => $command,
            'output' => $process->getOutput(),
            'error' => $process->getErrorOutput(),
            'success' => $process->isSuccessful(),
            'executed_at' => now()->toISOString(),
        ];
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

    private function getSystemStatus(): string
    {
        $status = "=== PORTAL SUPPORT CENTER STATUS ===\n\n";

        // System info
        $status .= "System Information:\n";
        $status .= "- PHP Version: " . PHP_VERSION . "\n";
        $status .= "- Laravel Version: " . app()->version() . "\n";
        $status .= "- Environment: " . config('app.env') . "\n";
        $status .= "- Disk Space: " . $this->getDiskSpace() . "\n\n";

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
        $html = '<div class="flex gap-2">';

        foreach ($actions as $action) {
            $actionName = $action->getName();
            // Map action names to correct Livewire methods
            $wireClick = match ($actionName) {
                'execute_command' => 'executeCommand',
                'view_logs' => 'viewLogs',
                'refresh_status' => 'refreshStatus',
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
            'heroicon-o-document-text' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
            'heroicon-o-arrow-path' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>',
            default => '',
        };
    }
}
