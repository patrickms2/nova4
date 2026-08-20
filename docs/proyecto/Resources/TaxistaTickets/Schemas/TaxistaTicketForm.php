<?php

namespace App\Filament\App\Resources\TaxistaTickets\Schemas;

use App\Support\DepartmentManagerAccess;
use App\Models\TaxistaTicket;
use App\Support\PortalTaxistaContext;
use App\Support\SupportAccess;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use App\Models\BookingDepartment;
use App\Models\TaxistaAppointment;
use Filament\Actions\Action;
use Filament\Actions\ButtonAction;
use Filament\Schemas\Components\Actions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
class TaxistaTicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('created_by_user_id')
                    ->default(fn(): ?int => auth()->id()),
                Hidden::make('status')
                    ->default('abierto'),

                    Hidden::make('editing_record_id')
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Set $set, ?TaxistaTicket $record): void {
                            $set('editing_record_id', $record?->getKey());
                        }),

                    Hidden::make('booking_department_label')
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Set $set, ?TaxistaTicket $record): void {
                            if (!$record) {
                                return;
                            }

                            $departmentName = $record->department?->name
                                ?? BookingDepartment::query()->whereKey((int)$record->booking_department_id)->value('name');

                            $set('booking_department_label', is_string($departmentName) ? $departmentName : null);
                        }),

                    Hidden::make('status')
                        ->default(
                        fn(Get $get) => PortalTaxistaContext::isPortalPanel()? 'abierto' : 'en_proceso'),


                    Grid::make([
                        'default' => 1,
                        'lg' => 4,
                    ])
                    ->columnSpanFull()
                    ->schema([
                        Section::make()
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 3,
                            ])
                            ->schema([
                                Select::make('user_id')
                                    ->label('Usuario')
                                    ->relationship(
                                        'user',
                                        'name',
                                        modifyQueryUsing: fn(Builder $query): Builder => PortalTaxistaContext::scopeTaxistaOptions($query)
                                    )
                                    ->default(fn(): ?int => PortalTaxistaContext::isPortalPanel()
                                        ? PortalTaxistaContext::taxistaUserId()
                                        : null)
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set, mixed $state, mixed $old): void {
                                            if (filled($get('editing_record_id')) && ($old === null || (string)$state === (string)$old)) {
                                                return;
                                            }

                                            $set('due_at', null);
                                            $set('title', null);
                                            $set('priority', null);
                                            $set('description', null);
                                            $departmentName = null;
                                            $set('booking_department_label', is_string($departmentName) ? $departmentName : null);
                                            $set('booking_department_id', null);
                                            $set('title', null);
                                        })
                                    ->preload()
                                    ->visible(fn(Get $get): bool => !filled($get('user_id')) && !PortalTaxistaContext::isPortalPanel()),


                                ToggleButtons::make('booking_department_id')
                                        ->label('Seleccione Departamento')
                                        ->options(fn(): array => PortalTaxistaContext::isPortalPanel()
                                            ? BookingDepartment::query()
                                                ->meetingBookable()
                                                ->orderBy('name')
                                                ->pluck('name', 'id')
                                                ->toArray()
                                            : DepartmentManagerAccess::scopeManagedServiceDepartments(
                                                BookingDepartment::query()->meetingBookable()->orderBy('name'),
                                                'has_meetings_service',
                                            )->pluck('name', 'id')->toArray())
                                        ->required()
                                        ->live()
                                        ->inline()
                                        ->dehydratedWhenHidden()
                                        ->visible(fn(Get $get): bool => filled($get('user_id')) && !filled($get('booking_department_id')))
                                        ->helperText('Departamentos con servicio de citas y horarios disponibles.')
                                        ->rule(function () {
                                            return function (string $attribute, mixed $value, \Closure $fail): void {
                                                if (blank($value)) {
                                                    return;
                                                }

                                                $isBookable = BookingDepartment::query()
                                                    ->meetingBookable()
                                                    ->when(! PortalTaxistaContext::isPortalPanel(), fn (Builder $query): Builder => DepartmentManagerAccess::scopeManagedServiceDepartments($query, 'has_meetings_service'))
                                                    ->whereKey((int)$value)
                                                    ->exists();

                                                if (!$isBookable) {
                                                    $fail('El departamento seleccionado no acepta citas actualmente.');
                                                }
                                            };
                                        })
                                        ->afterStateUpdated(function (Get $get, Set $set, mixed $state, mixed $old): void {
                                            if (filled($get('editing_record_id')) && ($old === null || (string)$state === (string)$old)) {
                                                return;
                                            }

                                            $set('due_at', null);
                                            $set('title', null);
                                            $set('priority', null);
                                            $set('description', null);

                                            $departmentName = null; 

                                            if (filled($state)) {
                                                $departmentName = BookingDepartment::query()->whereKey((int)$state)->value('name');
                                            }

                                            $set('booking_department_label', is_string($departmentName) ? $departmentName : null);
                                            $set('title', null);
                                        }),
                            
                                        ToggleButtons::make('ticket_type')
                                    ->label('Tipo de Ticket')
                                    ->inline()
                                    ->options(self::ticketTypeOptions())
                                    ->required()
                                    ->live()
                                    ->visible(fn(Get $get): bool => filled($get('booking_department_id')) && ! filled($get('ticket_type')))

                                    ->default(fn(): ?string => PortalTaxistaContext::isPortalPanel() ? null : 'incidencias')
                                    ->afterStateUpdated(function (Get $get, Set $set, mixed $state, mixed $old): void {
                                        if (filled($get('editing_record_id')) && ($old === null || $state === $old)) {
                                            return;
                                        }

                                        $set('title', self::composeTitle($state));
                                    }),
                            


                                ToggleButtons::make('priority')
                                    ->label('Prioridad')
                                    ->required()
                                    ->live()
                                    ->default(fn (): ?string => PortalTaxistaContext::isPortalPanel() ? null : 'media')
                                    ->inline()
                                    ->visible(fn(Get $get): bool => filled($get('booking_department_id')) &&  filled($get('ticket_type')) && ! filled($get('priority')))
                                    ->options([
                                        'baja' => 'Baja',
                                        'media' => 'Media',
                                        'alta' => 'Alta',
                                        'urgente' => 'Urgente',
                                    ]),


                            
                                                                
                                TextInput::make('title')
                                    ->label('Asunto')
                                    ->required()
                                    ->live()
                                    ->visible(fn(Get $get): bool => filled($get('booking_department_id')) &&  filled($get('ticket_type')) &&  filled($get('priority')))
                                    ->maxLength(255),
                                DateTimePicker::make('due_at')
                                    ->label('Fecha limite')
                                    ->seconds(false)
                                    ->visible(fn(Get $get): bool => filled($get('booking_department_id')) &&  filled($get('ticket_type')) && 
                                    (filled($get('priority')) && ($get('priority') === 'urgente' || $get('priority') === 'alta') && 
                                    (filled($get('status')) && ($get('status') === 'abierto' || $get('status') === 'en_proceso'))))
                                    ->live(),
                                Textarea::make('description')
                                    ->label('Descripcion')
                                    ->rows(4)
                                    ->live()
                                    ->visible(fn(Get $get): bool => filled($get('booking_department_id')) &&  filled($get('ticket_type')) &&  filled($get('priority')))
                                    ->columnSpanFull(),

                                FileUpload::make('attachments')
                                ->label('Adjuntos')
                                ->multiple()
                                ->maxFiles(5)
                                ->maxSize(10240)
                                ->disk('public')
                                ->visibility('public')
                                ->acceptedFileTypes([
                                    'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                                    'application/pdf',
                                    'application/msword',
                                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                ])
                                ->directory('tickets-attachments')
                                ->storeFileNamesIn('attachment_file_names')
                                ->previewable()
                                ->openable()
                                ->downloadable()
                                ->reorderable()
                                ->visible(fn(Get $get): bool => filled($get('booking_department_id')) &&  filled($get('ticket_type')) &&  filled($get('priority')))
                                ->columnSpanFull()
                                ->helperText('Máx. 5 archivos · 10 MB cada uno · PDF, Word o imágenes (JPG, PNG, GIF, WebP)'),

                            ]),
                        Section::make()
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 1,
                            ])
                            ->visible(fn(Get $get): bool =>  filled($get('user_id')) )
                            ->columns([
                                'default' => 1,
                                'md' => 2,
                                'sm' => 2,
                                'lg' => 1,
                                'xl' => 1,
                            ])
                            ->schema([
                                Actions::make([
                                    Action::make('appointment_step_change_user')
                                        ->label('Usuario')
                                        ->icon('heroicon-o-arrow-uturn-left')
                                        ->button()
                                        ->color('red')
                                        ->size('xs')
                                        ->extraAttributes(['class' => 'nova-appointment-choice-pill'])->size('xs')
                                        ->outlined()
                                        ->visible(fn(Get $get): bool => filled($get('user_id')))
                                        ->action(function (Set $set): void {
                                            $set('user_id', null);  
                                            $set('booking_department_id', null);
                                            $set('booking_department_label', null);
                                            $set('ticket_type', null);
                                            $set('due_at', null);
                                            $set('title', null);
                                            $set('priority', null);
                                            $set('description', null);      
                                            $set('attachments', []);  
      
                                        }),
                                    Action::make('appointment_step_change_department')
                                        ->label(fn(Get $get): string => (string)($get('booking_department_label') ?: 'Departamento'))
                                        ->icon('heroicon-o-arrow-uturn-left')
                                        ->button()
                                        ->color('red')
                                        ->size('xs')
                                        ->extraAttributes(['class' => 'nova-appointment-choice-pill'])->size('xs')
                                        ->outlined()
                                        ->visible(fn(Get $get): bool => filled($get('user_id')) && filled($get('booking_department_id')))
                                        ->action(function (Set $set): void {
                                            $set('booking_department_id', null);
                                            $set('booking_department_label', null);
                                            $set('due_at', null);
                                            $set('title', null);
                                            $set('priority', null);
                                            $set('description', null);      
                                            $set('attachments', []);      
                                        }),

                                    Action::make('appointment_step_change_tipo')
                                        ->label(fn(Get $get): string => (string)($get('ticket_type') ?: 'Tipo'))
                                        ->icon('heroicon-o-arrow-uturn-left')
                                        ->button()
                                        ->color('red')
                                        ->size('xs')
                                        ->extraAttributes(['class' => 'nova-appointment-choice-pill'])
                                        ->outlined()
                                        ->action(function (Set $set): void {
                                            $set('ticket_type', null);
                                        })
                                        ->visible(fn(Get $get): bool => filled($get('ticket_type')) && filled($get('booking_department_id'))),
                                    Action::make('appointment_step_change_date')
                                        ->label(fn(Get $get): string => (string)(Carbon::parse((string)$get('due_at'))->format('d/m/Y') ?: 'Fecha'))
                                        ->icon('heroicon-o-arrow-uturn-left')
                                        ->button()
                                        ->color('red')
                                        ->size('xs')
                                        ->extraAttributes(['class' => 'nova-appointment-choice-pill'])
                                        ->outlined()
                                        ->action(function (Set $set): void {
                                            $set('due_at', null);

                                        })
                                        ->visible(fn(Get $get): bool => filled($get('ticket_type')) && filled($get('due_at'))),

                                    Action::make('appointment_step_change_status')
                                        ->label(fn(Get $get): string => (string)($get('status') ?: 'Estado'))
                                        ->icon('heroicon-o-arrow-uturn-left')
                                        ->button()
                                        ->color('red')
                                        ->size('xs')
                                        ->extraAttributes(['class' => 'nova-appointment-choice-pill'])
                                        ->outlined()
                                        ->action(function (Set $set): void {
                                                $set('status', null);

                                        })->visible(fn(Get $get): bool => filled($get('ticket_type')) && filled($get('status'))),
                                       
                                        Action::make('appointment_step_change_priority')
                                        ->label(fn(Get $get): string => (string)($get('priority') ?: 'Prioridad'))
                                        ->icon('heroicon-o-arrow-uturn-left')
                                        ->button()
                                        ->color('red')
                                        ->size('xs')
                                        ->extraAttributes(['class' => 'nova-appointment-choice-pill'])
                                        ->outlined()
                                        ->action(function (Set $set): void {
                                                $set('priority', null); 
                                       })
                                    ->visible(fn(Get $get): bool => filled($get('ticket_type')) && filled($get('priority'))),

                                        Action::make('appointment_step_change_attachments')
                                        ->label(fn(Get $get): string => (string)(count($get('attachments') ?? []) > 0 ? 'Adjuntos (' . count($get('attachments')) . ')' : 'Adjuntos'))
                                        ->icon('heroicon-o-arrow-uturn-left')
                                        ->button()
                                        ->color('red')          
                                        ->size('xs')
                                        ->extraAttributes(['class' => 'nova-appointment-choice-pill'])
                                        ->outlined()
                                        ->action(function (Set $set): void {
                                                $set('attachments', []); 
                                       })->visible(fn(Get $get): bool => filled($get('user_id')) && count($get('attachments') ?? []) > 0),

                                ]),

                            ]),
                    ]),
            ]);
    }

    /**
     * @return array<string, string>
     */
    public static function ticketTypeOptions(): array
    {
        return [
            'incidencias' => 'Incidencias',
            'errores' => 'Errores',
            'turnos' => 'Turnos',
            'objetos' => 'Objetos',
            'reservas' => 'Reservas',
            'objetos_pb' => 'Objetos PB',
            'pagos' => 'Pagos',
            'documentos' => 'Documentos',
            'sugerencia' => 'Sugerencia',
        ];
    }

    public static function composeTitle(?string $ticketType, string $currentTitle = ''): string
    {
        $label = self::ticketTypeOptions()[$ticketType ?? ''] ?? null;

        if ($label === null) {
            return $currentTitle;
        }

        $suffix = now()->format('Y-m-d');

        return strtoupper($label) . ' Tías ' . $suffix;
    }

    public static function inferTicketType(string $title): ?string
    {
        $normalizedTitle = strtoupper(trim($title));

        foreach (self::ticketTypeOptions() as $key => $label) {
            if ($normalizedTitle === '') {
                return null;
            }

            if (str_starts_with($normalizedTitle, strtoupper($label))) {
                return $key;
            }
        }

        return null;
    }
}
