<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use Filament\Support\Icons\Heroicon;

use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\AvailabilitySlotResource\Pages;
use App\Models\VillaAvailability;

/**
 * Filament 5 Resource — blokady dostepnosci (serwis, wyjazdy prywatne).
 *
 * @see KML-0053 (D3)
 */
class AvailabilitySlotResource extends Resource
{
    protected static ?string $model = VillaAvailability::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNoSymbol;

    protected static ?string $navigationLabel = 'Disponibilidad Villa';
    protected static string|\UnitEnum|null $navigationGroup = 'Villas';
    
        public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
    protected static ?string $navigationParentGroup = 'Villas';

    protected static ?string $modelLabel = 'Alquiler';

    protected static ?string $pluralModelLabel = 'Alquileres';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Zasob')
                ->schema([
                    Select::make('rentable_type')
                        ->label('Typ zasobu')
                        ->options(fn () => self::rentableTypeOptions())
                        ->required()
                        ->live()
                        ->default(fn () => array_key_first(self::rentableTypeOptions())),
                    Select::make('villa_id')
                        ->label('Konkretny zasob')
                        ->options(function (Get $get) {
                            $type = $get('rentable_type');
                            if (! $type || ! class_exists($type)) {
                                return [];
                            }
                            /** @var Model $model */
                            $model = new $type;
                            $query = $model->newQuery();
                            if (method_exists($model, 'newQueryWithoutScopes')) {
                                $query = $model->newQueryWithoutScopes();
                            }

                            return $query
                                ->limit(500)
                                ->get()
                                ->mapWithKeys(fn ($r) => [
                                    $r->getKey() => $r->name ?? $r->title ?? $r->getKey(),
                                ])
                                ->toArray();
                        })
                        ->searchable()
                        ->required()
                        ->preload(),
                ])
                ->columns(2),

            Section::make('Termin blokady')
                ->schema([
                    TextInput::make('available_pers')
                        ->label('Pers.')
                        ->numeric()
                        ->default(4),
                    TextInput::make('price')
                        ->label('Precio')
                        ->numeric()
                        ->default(0),
                    Toggle::make('is_unidadpersona')
                        ->label('Precio por persona')
                        ->default(true),
                    DatePicker::make('start_date')
                        ->label('Inicio')
                        ->required()
                        ->native(false)
                        ->displayFormat('d.m.Y'),
                    DatePicker::make('end_date')
                        ->label('Fin')
                        ->required()
                        ->native(false)
                        ->displayFormat('d.m.Y')
                        ->afterOrEqual('start_date'),
                ])
                ->columns(2),

            Section::make('Powod')
                ->schema([
                    Toggle::make('is_blocked')
                        ->label('Blokada aktywna')
                        ->helperText('Wylaczenie pozwala zachowac historyczny wpis bez blokowania dostepnosci.')
                        ->default(true),
                    Textarea::make('reason')
                        ->label('Powod blokady')
                        ->rows(3)
                        ->maxLength(500)
                        ->helperText('np. "Serwis", "Wyjazd prywatny", "Wymiana opon"')
                        ->columnSpanFull(),
                ])
                ->columns(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('rentable_type')
                    ->label('Typ')->searchable()
                    ->formatStateUsing(fn (string $state) => class_basename($state))
                    ->toggleable(),
                TextColumn::make('rentable')
                    ->label('Zasob')
                    ->getStateUsing(function (VillaAvailability $s): string {
                        $r = $s->rentable;
                        if (! $r) {
                            return class_basename($s->rentable_type).' #'.$s->rentable_id;
                        }

                        return $r->name ?? $r->title ?? class_basename($s->rentable_type).' #'.$r->getKey();
                    }),
                TextColumn::make('start_date')
                    ->label('Od')
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('Do')
                    ->date('d.m.Y')
                    ->sortable(),
                IconColumn::make('is_blocked')
                    ->label('Aktywna')
                    ->boolean(),
                TextColumn::make('reason')
                    ->label('Powod')
                    ->limit(40)
                    ->tooltip(fn (?string $state) => $state)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Utworzono')
                    ->dateTime('d.m.Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('rentable_type')
                    ->label('Typ zasobu')
                    ->options(fn () => self::rentableTypeOptions()),
                TernaryFilter::make('is_blocked')
                    ->label('Aktywna')
                    ->placeholder('Wszystkie')
                    ->trueLabel('Tylko aktywne')
                    ->falseLabel('Tylko wylaczone'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('start_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAvailabilitySlots::route('/'),
            'create' => Pages\CreateAvailabilitySlot::route('/create'),
            'edit' => Pages\EditAvailabilitySlot::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['rentable']);
    }

    /**
     * Mapa rentable type FQCN => label.
     *
     * Czyta z config('rental.rentable_models') (array) z fallbackiem do
     * config('rental.rentable_model') (single FQCN string).
     *
     * @return array<class-string, string>
     */
    protected static function rentableTypeOptions(): array
    {
        $configured = config('rental.rentable_models');

        if (is_array($configured) && $configured !== []) {
            $opts = [];
            foreach ($configured as $key => $value) {
                if (is_int($key)) {
                    // ['App\Models\Motorcycle']
                    $opts[$value] = class_basename($value);
                } else {
                    // ['App\Models\Motorcycle' => 'Motocykle']
                    $opts[$key] = $value;
                }
            }

            return $opts;
        }

        $single = config('rental.rentable_model');
        if (is_string($single) && $single !== '') {
            return [$single => class_basename($single)];
        }

        return [];
    }
}
