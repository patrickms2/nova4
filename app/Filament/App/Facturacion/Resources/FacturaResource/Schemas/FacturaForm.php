<?php

namespace App\Filament\App\Facturacion\Resources\FacturaResource\Schemas;

use App\Models\Concepto;
use App\Models\Factura;
use App\Models\Remesa;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class FacturaForm
{
    public static function configure(Schema $schema): Schema
    {
        $conceptos = Concepto::get();

        return $schema
            ->schema([
                Section::make()
                    ->columnSpanFull()
                    ->schema([
                        Section::make()
                            ->schema(static::getDetailsComponents())
                            ->columns(2),

                        Section::make('Servicios')
                            ->afterHeader([
                                Action::make('reset')
                                    ->modalHeading('Are you sure?')
                                    ->modalDescription('All existing items will be removed from the order.')
                                    ->requiresConfirmation()
                                    ->color('danger')
                                    ->action(fn (Set $set) => $set('registros', [])),
                            ])
                            ->schema([

                                Repeater::make('registros')
                                    ->hiddenLabel()
                                    ->table([
                                        TableColumn::make('Concepto')
                                            ->width(220),
                                        TableColumn::make('Desc.')->width(110),

                                        TableColumn::make('Cantidad')
                                            ->width(40),


                                        TableColumn::make('Unid')
                                            ->width(110),
                                        TableColumn::make('Precio')
                                            ->width(110),
                                        TableColumn::make('Igic')
                                            ->width(40),
                                        TableColumn::make('Ret.')
                                            ->width(40),
                                        TableColumn::make('Importe')
                                            ->width(110),
                                    ])
                                    ->compact()
                                    ->itemLabel(function (array $state) use ($conceptos): ?string {
                                        if (! empty($state['concepto_id'])) {
                                            $concepto = $conceptos->firstWhere('id', $state['concepto_id']);
                                            if ($concepto) {
                                                return $concepto->concepto;
                                            }
                                        }

                                        return $state['concepto'] ?? 'Producto';
                                    })
                                    ->reorderable()
                                    ->addActionLabel('Añadir Concepto')
                                    ->defaultItems(0)
                                    ->columnSpanFull()
                                    ->live()
                                    ->relationship()
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        $set('impuesto', 7);
                                        $set('retenciones', 15);
                                        self::updateTotals($get, $set);
                                    })
                                    ->deleteAction(
                                        fn (Action $action) => $action->after(fn (Get $get, Set $set) => self::updateTotals($get, $set)),
                                    )
                                    ->reorderable(true)
                                    ->columns(9)
                                    ->schema([
                                        Select::make('concepto_id')
                                            ->columnSpan(2)
                                            ->label('Producto')
                                            ->options(
                                                $conceptos->mapWithKeys(function (Concepto $concepto) {
                                                    return [$concepto->id => sprintf('%s ($%s)', $concepto->concepto, $concepto->precio)];
                                                })
                                            )
                                            ->distinct()
                                            ->afterStateUpdated(function ($state, Get $get, Set $set) {

                                                $concepto = Concepto::find($state);
                                                $precio = $concepto ? $concepto->precio : 0;
                                                $set('cantidad', 1);
                                                $valorimpuesto = $concepto ? $concepto->impuesto/100 * $precio *($get('cantidad') ?? 1) : 0;
                                                $valorretenciones = $concepto ? $concepto->retenciones/100 * $precio *($get('cantidad') ?? 1) : 0;
                                                $set('impuesto', $concepto->impuesto);
                                                $set('retenciones',$concepto->retenciones);
                                                $set('unidad', $concepto->unidad);
                                                $set('valorimpuesto', $valorimpuesto);
                                                $set('valorretenciones', $valorretenciones);
                                                $set('concepto', $concepto->concepto);
                                                $set('precio', $precio);
                                                $set('importe', $precio * ($get('cantidad') ?? 1)+$valorimpuesto-$valorretenciones);

                                            })
                                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                            ->required(),
                                        TextInput::make('descripcion')
                                            ->label('Desc.'),
                                        TextInput::make('cantidad')
                                            ->label('Cant.')
                                            ->numeric()
                                            ->default(1)
                                            ->required()
                                            ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                                $set('cantidad', $state);
                                                $valorimpuesto = $get('impuesto')/100 * $get('precio') * $get('cantidad');
                                                $valorretenciones = $get('retenciones')/100 * $get('precio') * $get('cantidad');
                                                $set('valorimpuesto', $valorimpuesto);
                                                $set('valorretenciones', $valorretenciones);
                                                $set('importe', $state * $get('precio')-$get('valorretenciones')+$get('valorimpuesto'));
                                            })
                                            ->live(),
                                        TextInput::make('unidad')
                                            ->label('Unid.'),
                                        TextInput::make('precio')
                                            ->label('Precio')
                                            ->dehydrated()
                                            ->numeric()
                                            ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                                $set('precio',$state);
                                                $valorimpuesto = $get('impuesto')/100 * $get('precio') * $get('cantidad');
                                                $valorretenciones = $get('retenciones')/100 * $get('precio') * $get('cantidad');
                                                $set('valorimpuesto', $valorimpuesto);
                                                $set('valorretenciones', $valorretenciones);
                                                $set('importe', $get('cantidad') * $get('precio')-$get('valorretenciones')+$get('valorimpuesto'));
                                            })
                                            ->live()
                                            ->required(),
                                        TextInput::make('impuesto')
                                            ->label('Igic')
                                            ->numeric()
                                            ->default(1)
                                            ->required()
                                            ->afterStateUpdated(function ($state, Get $get, Set $set) {

                                                $set('impuesto',$state);
                                                $valorimpuesto = $get('impuesto')/100 * $get('precio') * $get('cantidad');
                                                $valorretenciones = $get('retenciones')/100 * $get('precio') * $get('cantidad');
                                                $set('valorimpuesto', $valorimpuesto);
                                                $set('valorretenciones', $valorretenciones);
                                                $set('importe', $get('cantidad') * $get('precio')-$get('valorretenciones')+$get('valorimpuesto'));
                                            })
                                            ->live(),
                                        TextInput::make('retenciones')
                                            ->label('Ret.')
                                            ->numeric()
                                            ->default(1)
                                            ->required()
                                            ->afterStateUpdated(function ($state, Get $get, Set $set) {

                                                $set('retenciones',$state);
                                                $valorimpuesto = $get('impuesto')/100 * $get('precio') * $get('cantidad');
                                                $valorretenciones = $get('retenciones')/100 * $get('precio') * $get('cantidad');
                                                $set('valorimpuesto', $valorimpuesto);
                                                $set('valorretenciones', $valorretenciones);
                                                $set('importe', $get('cantidad') * $get('precio')-$get('valorretenciones')+$get('valorimpuesto'));
                                            })
                                            ->live(),                                        TextInput::make('importe')
                                            ->label('Total')
                                            ->columnSpan(2)
                                            ->dehydrated()
                                            ->numeric()
                                            ->default(0)
                                            ->suffix('€')
                                            ->live()
                                            ->suffixActions([

                                            ])->columns(9),

                                    ])->columns(2),
                            ]),
                        Section::make()
                            ->schema(static::getTotalsComponents())
                            ->columns(4)
                    ])
                    ->columnSpan(2),
            ]);
    }

    public static function updateTotals(Get $get, Set $set): void
    {
        $registros = collect($get('registros'));
        $items = $registros->filter(fn ($item) => ! empty($item['concepto_id']));

        $subtotal = 0.0;
        $totalIgic = 0.0;
        $totalRet = 0.0;

        foreach ($items as $item) {
            $precio = (float) ($item['precio'] ?? 0);
            $cantidad = (float) ($item['cantidad'] ?? 1);
            $igicPct = (float) ($item['impuesto'] ?? 0);
            $retPct = (float) ($item['retenciones'] ?? 0);

            $base = $precio * $cantidad;
            $subtotal += $base;
            $totalIgic += $base * $igicPct / 100;
            $totalRet += $base * $retPct / 100;
        }

        $importe = $subtotal + $totalIgic - $totalRet;

        $set('baseimponible', number_format($subtotal, 2, '.', ''));
        $set('impuesto', number_format($totalIgic, 2, '.', ''));
        $set('retenciones', number_format($totalRet, 2, '.', ''));
        $set('importe', number_format($importe, 2, '.', ''));
    }

    public static function monthName(mixed $date): string
    {
        if (! $date) {
            return ucfirst(now()->locale('es')->translatedFormat('F'));
        }

        $carbon = Carbon::parse($date);

        return ucfirst($carbon->locale('es')->translatedFormat('F'));
    }

    /**
     * @return array<Component>
     */
    public static function getDetailsComponents(): array
    {
        return [
            Section::make()
                ->columnSpanFull()
                ->schema([
                    Section::make('Factura')
                        ->columns(2)
                        ->schema([
                            TextInput::make('codfactura')
                                ->label('Nº factura')
                                ->default(fn (): string => Factura::suggestNumero())
                                ->readOnly()
                                ->dehydrated()
                                ->required()
                                ->maxLength(32)
                                ->unique(Factura::class, 'codfactura'),
                            DatePicker::make('fechaemitido')
                                ->label('Fecha emisión')
                                ->native(false)
                                ->default(now()->format('Y-m-d'))
                                ->displayFormat('d/m/Y')
                                ->live()
                                ->afterStateUpdated(fn ($state, Set $set) => $set('notas', static::monthName($state)))
                                ->required(),
                            TextInput::make('notas')
                                ->label('Notas')
                                ->default(fn (): string => static::monthName(now()))
                                ->dehydrated()
                                ->required()
                                ->maxLength(25)
                                ->columnSpan(1),
                            Select::make('cliente_id')
                                ->relationship('cliente', 'nombretotal')
                                ->getOptionLabelFromRecordUsing(fn ($record): string => $record->nombretotal ?? ($record->nombre ?? 'Cliente #'.$record->id))
                                ->searchable()
                                ->required()
                                ->preload()
                                ->columnSpan(1)
                                ->live()
    ->afterStateUpdated(function (Set $set, ?int $state) {
        $set('concepto_id', null);

        if ($state) {
            $conceptos = Concepto::where('cliente_id', $state)->get();

            if ($conceptos->count() === 1) {
                $set('concepto_id', $conceptos->first()->id);
            }
        }
    })
                                ->createOptionForm([
                                    TextInput::make('nombretotal')
                                        ->label('Nombre')
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('email')
                                        ->label('Email')
                                        ->required()
                                        ->email()
                                        ->maxLength(255)
                                        ->unique(),
                                    TextInput::make('telefono')
                                        ->label('Teléfono')
                                        ->maxLength(255),
                                ])
                                ->createOptionAction(fn (Action $action) => $action
                                    ->modalHeading('Crear cliente')
                                    ->modalSubmitActionLabel('Nuevo Cliente')
                                    ->modalWidth('lg')),
                            Select::make('remesa_id')
                                ->label('Remesa')
                                ->relationship('remesa', 'nombre')
                                ->getOptionLabelFromRecordUsing(fn ($record): string => $record->nombre ?? 'Remesa #'.$record->id)
                                ->searchable()
                                ->preload()
                                ->placeholder('— Ninguna —')
                                ->nullable(),
                            TextInput::make('observaciones')
                                ->label('Observaciones')
                                ->placeholder('Notas internas de la factura')
                                ->maxLength(500)
                                ->columnSpanFull()
                                ->nullable(),
                        ]),

                ]),
        ];
    }

    /**
     * @return array<Component>
     */
    public static function getTotalsComponents(): array
    {
        return [
            TextInput::make('baseimponible')
                ->label('Base')
                ->inlineLabel()
                ->readOnly()
                ->numeric()
                ->suffix('€')
                ->afterStateHydrated(fn (Get $get, Set $set) => static::updateTotals($get, $set)),
            TextInput::make('impuesto')
                ->label('IGIC')
                ->inlineLabel()
                ->readOnly()
                ->numeric()
                ->suffix('€'),
            TextInput::make('retenciones')
                ->label('Ret.')
                ->inlineLabel()
                ->readOnly()
                ->numeric()
                ->suffix('€'),
            TextInput::make('importe')
                ->label('Total')
                ->inlineLabel()
                ->readOnly()
                ->numeric()
                ->suffix('€')
                ->extraAttributes(['style' => 'color: red; font-weight: bold']),
        ];
    }
}
