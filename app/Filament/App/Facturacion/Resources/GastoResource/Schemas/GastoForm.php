<?php

namespace App\Filament\App\Facturacion\Resources\GastoResource\Schemas;

use App\Models\Category;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Gasto;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class GastoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Tabs::make('Gasto')
                    ->tabs([
                        Tab::make('Gasto')
                            ->icon(Heroicon::OutlinedReceiptPercent)
                            ->schema([
                                Section::make('Documento')
                                    ->description('Haz una foto o adjunta el ticket/factura del gasto.')
                                    ->collapsible()
                                    ->schema([
                                        FileUpload::make('documento')
                                            ->label('Ticket o factura')
                                            ->directory('gastos')
                                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                                            ->maxSize(2048)
                                            ->downloadable()
                                            ->image()
                                            ->nullable(),
                                    ])
                                    ->columnSpanFull(),
                                Section::make('Datos del gasto')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('codigo')
                                            ->label('Código')
                                            ->placeholder('código')
                                            ->maxLength(50)
                                            ->nullable(),
                                        Select::make('category_id')
                                            ->label('Categoría')
                                            ->relationship('category', 'name')
                                            ->getOptionLabelFromRecordUsing(fn ($record): string => $record->name ?? 'Categoría #'.$record->id)
                                            ->searchable()
                                            ->preload()
                                            ->placeholder('Sin categoría')
                                            ->nullable()
                                            ->createOptionForm([
                                                TextInput::make('name')
                                                    ->label('Nombre')
                                                    ->required()
                                                    ->maxLength(255),
                                            ])
                                            ->createOptionUsing(function (array $data): ?int {
                                                $category = Category::query()->create([
                                                    'name' => $data['name'],
                                                    'type' => 'expense',
                                                ]);

                                                return $category?->id;
                                            }),
                                        Select::make('proveedor_id')
                                            ->label('Proveedor')
                                            ->relationship('cliente', 'nombretotal')
                                            ->getOptionLabelFromRecordUsing(fn ($record): string => $record->nombretotal ?? ($record->nombre ?? 'Cliente #'.$record->id))
                                            ->searchable()
                                            ->preload()
                                            ->placeholder('Sin proveedor')
                                            ->nullable(),
                                        DatePicker::make('fecha')
                                            ->label('Fecha')
                                            ->required()
                                            ->default(now())
                                            ->native(false)
                                            ->displayFormat('d/m/Y'),
                                        TextInput::make('descripcion')
                                            ->label('Descripción')
                                            ->placeholder('Ej. Alquiler oficina')
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpanFull(),
                                        TextInput::make('total')
                                            ->label('Importe')
                                            ->placeholder('0,00')
                                            ->numeric()
                                            ->suffix('€')
                                            ->required()
                                            ->default(0)
                                            ->live()
                                            ->afterStateUpdated(function (mixed $state, Set $set): void {
                                                $set('base_imponible', (float) $state);
                                                $set('impuesto', 0);
                                            }),
                                    ]),
                            ]),
                    ]),
            ])
            ->columns(1);
    }
}

