<?php

namespace App\Filament\App\Pages;

use App\Actions\CreateEmployeeAction;
use App\Actions\AssociateExistingUserAction;
use BackedEnum;
use App\Filament\App\Resources\Employees\EmployeeResource;
use App\Models\User;
use App\Models\BookingDepartment;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Forms\Components\TextInput as SchemaTextInput;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Enums\IconPosition;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use CodeWithDennis\SimpleAlert\Components\SimpleAlert;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema as DbSchema;
use App\Enums\Icons\PhosphorIcons;

class DashboardWithEmployeeActions extends BaseDashboard
{
    use InteractsWithForms;
    use BaseDashboard\Concerns\HasFiltersForm;

    protected static ?string $title = '';

    protected static bool $isDiscovered = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-pie';
    public bool $hasUnreadAnnouncements = false;
    public ?int $taxistaId = null;

    public function mount(): void
    {
        $this->hasUnreadAnnouncements = auth()->user()->unreadAnnouncements()->exists();
        $this->taxistaId = $this->resolveTaxistaIdForCurrentUser();
    }

    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 3,
        ];
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\App\Widgets\OptimizedStatsOverviewWidget::class,
            \App\Filament\App\Widgets\OptimizedConfirmedAppointmentsWidget::class,
            \App\Filament\App\Widgets\OptimizedPendingAppointmentsWidget::class,
            \App\Filament\App\Widgets\OptimizedOpenTicketsWidget::class,
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getNavigationLabel(): string
    {
        return 'Dashboard';
    }

    public static function getNavigationBadge(): ?string
    {
        return Str::ucfirst(Auth::user()->name) ?? null;
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-user-circle';
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public function announcementsAction(): Action
    {
        return Action::make('unreadAnnouncements')
            ->modalWidth(Width::ExtraLarge)
            ->color('warning')
            ->extraAttributes([
                'style' => 'display:none;'
            ])
            ->label(__('filament.app.dashboard.new_announcements'))
            ->visible(fn() => $this->hasUnreadAnnouncements)
            ->icon(PhosphorIcons::Bell)
            ->modalHeading(fn($record) => $record?->title ?? __('filament.app.dashboard.no_new_announcements'))
            ->modalDescription(fn($record) => $record ? __('filament.app.dashboard.announcement_from') . ' ' . $record->user->name : null)
            ->modalIcon(PhosphorIcons::Bell)
            ->formWrapper(false)
            ->closeModalByClickingAway(false)
            ->closeModalByEscaping(false)
            ->record(function () {
                return auth()->user()->nextUnreadAnnouncement();
            })
            ->modalCloseButton(fn($record) => !filled($record))
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->extraModalFooterActions(function ($action) {
                $record = $action->getRecord();

                if (!$record) {
                    return [
                        Action::make('close')
                            ->label(__('filament.app.dashboard.close'))
                            ->color('gray')
                            ->close()
                            ->icon(PhosphorIcons::X),
                    ];
                }
                return [
                    Action::make('next')
                        ->label('Leer Anuncio')
                        ->link()
                        ->visible(fn($record) => filled($record))
                        ->icon(PhosphorIcons::Check)
                        ->color('success')
                        ->action(function ($record, Action $action) {
                            $user = auth()->user();

                            $user->markAnnouncementAsRead($record);

                            $next = $user->nextUnreadAnnouncement();

                            if ($next) {
                                $action->record($next);
                                $action->getRecord()->refresh();
                            } else {
                                $action->record(null);

                            }
                        }),
                ];
            })
            ->schema([
                TextEntry::make('content')
                    ->hiddenLabel()
                    ->html()
                    ->visible(fn($record) => filled($record)),

                SimpleAlert::make('announcement-banner')
                    ->warning()
                    ->visible(fn($record) => blank($record))
                    ->border()
                    ->columnSpanFull()
                    ->icon(PhosphorIcons::CheckCircleBold)
                    ->title(__('filament.app.dashboard.no_unread_announcements'))
            ]);
    }

    public function getTitle(): string|Htmlable
    {
        return 'Departamento ' . Str::ucfirst(Auth::user()->bookingDepartment?->name);
    }

    public function getHeaderWidgets(): array
    {
        return [
            /*TaxistaUpcomingAppointmentsWidget::class,
            TaxistaRecentDocumentsWidget::class,
            TaxistaRecentTicketsWidget::class,
            TaxistaTaxisWidget::class,
            StatsOverview::class,
            AppointmentsTodayWidget::class,
            RevenueChart::class,*/
        ];
    }



    private function resolveTaxistaIdForCurrentUser(): ?int
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        $hasEmail = filled($user->email ?? null);
        $hasOib = filled($user->oib ?? null);

        if ($hasEmail || $hasOib) {
            $matchByIdentity = DB::table('usuarios')
                ->where('tipo_id', 4)
                ->where(function ($query) use ($user, $hasEmail, $hasOib): void {
                    if ($hasEmail) {
                        $query->where('email', (string) $user->email);
                    }

                    if ($hasOib) {
                        $query->orWhereRaw("UPPER(COALESCE(cif, '')) = ?", [mb_strtoupper(trim((string) $user->oib))]);
                    }
                })
                ->value('id');

            if ($matchByIdentity) {
                return (int) $matchByIdentity;
            }
        }

        if (DbSchema::hasColumn('usuarios', 'user_id')) {
            $matchByUserId = DB::table('usuarios')
                ->where('tipo_id', 4)
                ->where('user_id', (int) $user->id)
                ->value('id');

            if ($matchByUserId) {
                return (int) $matchByUserId;
            }
        }

        $direct = DB::table('usuarios')
            ->where('tipo_id', 4)
            ->where('id', (int) $user->id)
            ->value('id');

        return $direct ? (int) $direct : null;
    }
    // 🚀 ACCIÓN PRINCIPAL PARA GESTIÓN DE EMPLEADOS
    public function getHeaderActions(): array
    {
        return [
            Action::make('openAnnouncements')
                ->label('Avisos')
                ->icon(Heroicon::OutlinedBell)
                ->color('gray')
                ->outlined()
                ->visible(fn (): bool => method_exists(auth()->user(), 'recentAnnouncements') && auth()->user()->recentAnnouncements()->exists())
                ->action(function (): void {
                    $this->dispatch('open-app-announcements');
                }),
            Action::make('manageEmployees')
                ->label('Gestionar Empleados')
                ->icon('heroicon-o-user-group')
                ->iconPosition(IconPosition::Before)
                ->size('sm')
                ->color('primary')
                ->button()
                ->visible(false)
                ->extraAttributes([
                    'class' => 'bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700',
                ])
                ->form([
                    Section::make('¿Qué acción deseas realizar?')
                        ->description('Elige si quieres crear un nuevo empleado o asociar un usuario existente.')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Action::make('createNewEmployee')
                                        ->label('Crear Nuevo Empleado')
                                        ->icon('heroicon-o-plus-circle')
                                        ->color('success')
                                        ->action(function (array $data) {
                                            $this->createNewEmployeeAction();
                                        })
                                        ->extraAttributes([
                                            'class' => 'w-full',
                                        ]),

                                    Action::make('associateExistingUser')
                                        ->label('Asociar Usuario Existente')
                                        ->icon('heroicon-o-link')
                                        ->color('warning')
                                        ->action(function (array $data) {
                                            $this->associateExistingUserAction();
                                        })
                                        ->extraAttributes([
                                            'class' => 'w-full',
                                        ]),
                                ]),
                        ]),
                ])
                ->modalHeading('Gestión de Empleados')
                ->modalDescription('Selecciona una opción para gestionar los empleados del sistema')
                ->modalWidth(Width::TwoExtraLarge)
                ->slideOver(),
        ];
    }

    // 🎯 ACCIÓN PARA CREAR NUEVO EMPLEADO
    public function createNewEmployeeAction(): Action
    {
        return Action::make('createNewEmployee')
            ->label('Crear Nuevo Empleado')
            ->icon('heroicon-o-user-plus')
            ->color('success')
            ->form([
                SchemaSection::make('Información del Empleado')
                    ->description('Completa los datos para crear un nuevo empleado')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                SchemaTextInput::make('name')
                                    ->label('Nombre Completo')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Ej: Juan Pérez'),

                                SchemaTextInput::make('email')
                                    ->label('Correo Electrónico')
                                    ->required()
                                    ->email()
                                    ->unique('users', 'email')
                                    ->placeholder('ejemplo@correo.com'),

                                SchemaTextInput::make('password')
                                    ->label('Contraseña Temporal')
                                    ->required()
                                    ->password()
                                    ->default(fn() => Str::random(12))
                                    ->helperText('Se generará automáticamente si no especificas una'),

                                Select::make('booking_department_id')
                                    ->label('Departamento')
                                    ->required()
                                    ->options(fn() => BookingDepartment::pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Selecciona un departamento'),

                                Toggle::make('send_welcome_email')
                                    ->label('Enviar correo de bienvenida')
                                    ->default(true)
                                    ->helperText('Se enviará un correo con las credenciales de acceso'),

                                Toggle::make('is_active')
                                    ->label('Empleado Activo')
                                    ->default(true)
                                    ->helperText('El empleado podrá acceder al sistema inmediatamente'),
                            ]),
                    ]),
            ])
            ->action(function (array $data) {
                try {
                    // Crear el nuevo empleado
                    $employee = User::create([
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'password' => bcrypt($data['password']),
                        'booking_department_id' => $data['booking_department_id'],
                        'role' => 'empleado',
                        'status' => $data['is_active'] ? 1 : 0,
                        'created_by_user_id' => Auth::id(),
                    ]);

                    // Enviar correo de bienvenida si se solicita
                    if ($data['send_welcome_email']) {
                        // TODO: Implementar envío de correo de bienvenida
                        // Mail::to($employee->email)->send(new WelcomeEmail($employee, $data['password']));
                    }

                    Notification::make()
                        ->title('✅ Empleado Creado Exitosamente')
                        ->body("Se ha creado el empleado {$employee->name} con correo {$employee->email}")
                        ->success()
                        ->send();

                    // Redirigir a la página del empleado
                    $this->redirect(EmployeeResource::getUrl('edit', ['record' => $employee->id]));

                } catch (\Exception $e) {
                    Notification::make()
                        ->title('❌ Error al Crear Empleado')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            })
            ->modalHeading('Crear Nuevo Empleado')
            ->modalDescription('Completa el formulario para agregar un nuevo empleado al sistema')
            ->modalWidth(MaxWidth::TwoExtraLarge)
            ->modalSubmitActionLabel('Crear Empleado')
            ->modalCancelActionLabel('Cancelar');
    }

    // 🔗 ACCIÓN PARA ASOCIAR USUARIO EXISTENTE
    public function associateExistingUserAction(): Action
    {
        return Action::make('associateExistingUser')
            ->label('Asociar Usuario Existente')
            ->icon('heroicon-o-link')
            ->color('warning')
            ->form([
                SchemaSection::make('Asociar Usuario Existente')
                    ->description('Busca y asocia un usuario existente como empleado')
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                Select::make('user_id')
                                    ->label('Buscar Usuario')
                                    ->required()
                                    ->searchable()
                                    ->getSearchResultsUsing(function (string $search) {
                                        return User::where('name', 'like', "%{$search}%")
                                            ->orWhere('email', 'like', "%{$search}%")
                                            ->where(function ($query) {
                                                $query->whereNull('role')
                                                    ->orWhere('role', '!=', 'empleado');
                                            })
                                            ->limit(10)
                                            ->get()
                                            ->map(function ($user) {
                                                $roleLabel = $user->role ? "({$user->role})" : "(sin rol)";
                                                return [
                                                    'id' => $user->id,
                                                    'name' => "{$user->name} - {$user->email} {$roleLabel}",
                                                ];
                                            })
                                            ->pluck('name', 'id');
                                    })
                                    ->getOptionLabelUsing(function ($value) {
                                        $user = User::find($value);
                                        if (!$user) return '';
                                        $roleLabel = $user->role ? "({$user->role})" : "(sin rol)";
                                        return "{$user->name} - {$user->email} {$roleLabel}";
                                    })
                                    ->placeholder('Busca por nombre o correo...'),

                                Select::make('booking_department_id')
                                    ->label('Asignar Departamento')
                                    ->required()
                                    ->options(fn() => BookingDepartment::pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Selecciona un departamento'),

                                Toggle::make('send_notification')
                                    ->label('Enviar notificación')
                                    ->default(true)
                                    ->helperText('Se notificará al usuario sobre su nuevo rol de empleado'),

                                Toggle::make('is_active')
                                    ->label('Empleado Activo')
                                    ->default(true)
                                    ->helperText('El usuario podrá acceder como empleado inmediatamente'),
                            ]),
                    ]),
            ])
            ->action(function (array $data) {
                try {
                    $user = User::findOrFail($data['user_id']);

                    // Actualizar el usuario a empleado
                    $user->update([
                        'booking_department_id' => $data['booking_department_id'],
                        'role' => 'empleado',
                        'status' => $data['is_active'] ? 1 : 0,
                        'updated_by_user_id' => Auth::id(),
                    ]);

                    // Enviar notificación si se solicita
                    if ($data['send_notification']) {
                        // TODO: Implementar notificación de rol asignado
                        // $user->notify(new EmployeeRoleAssignedNotification());
                    }

                    Notification::make()
                        ->title('✅ Usuario Asociado Exitosamente')
                        ->body("Se ha asociado a {$user->name} como empleado del departamento")
                        ->success()
                        ->send();

                    // Redirigir a la página del empleado
                    $this->redirect(EmployeeResource::getUrl('edit', ['record' => $user->id]));

                } catch (\Exception $e) {
                    Notification::make()
                        ->title('❌ Error al Asociar Usuario')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            })
            ->modalHeading('Asociar Usuario Existente')
            ->modalDescription('Busca un usuario existente y asígnalo como empleado')
            ->modalWidth(MaxWidth::TwoExtraLarge)
            ->modalSubmitActionLabel('Asociar como Empleado')
            ->modalCancelActionLabel('Cancelar');
    }
}
