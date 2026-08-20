<?php

namespace App\Filament\Resources;

use Filament\Support\Icons\Heroicon;

use App\Filament\Resources\ExternalOrderResource\Pages;
use App\Models\ExternalOrder;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas;
use Filament\Schemas\Schema as Form;
use Filament\Tables;
use Filament\Tables\Table;

class ExternalOrderResource extends Resource
{
    protected static ?string $model = ExternalOrder::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptRefund;

    protected static ?int $navigationSort = 8;

    protected static string|\UnitEnum|null $navigationGroup = 'Bookings Externos/Pagos';
    protected static ?string $navigationParentGroup = 'Reservas';
    protected static ?string $navigationLabel = 'Todos los pedidos';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $schema): Form
    {
        return $schema->schema([
            Schemas\Components\Section::make('Order')
                ->schema([
                    Forms\Components\Select::make('server_id')->relationship('server', 'name')->required()->searchable()->preload(),
                    Forms\Components\Select::make('external_source_id')->relationship('externalSource', 'source_label')->required()->searchable()->preload(),
                    Forms\Components\TextInput::make('business_name'),
                    Forms\Components\TextInput::make('source_platform')->required(),
                    Forms\Components\TextInput::make('source_label')->required(),
                    Forms\Components\TextInput::make('external_id')->required(),
                    Forms\Components\TextInput::make('external_increment_id'),
                    Forms\Components\TextInput::make('status'),
                    Forms\Components\TextInput::make('payment_status'),
                    Forms\Components\TextInput::make('customer_name'),
                    Forms\Components\TextInput::make('customer_email')->email(),
                    Forms\Components\TextInput::make('grand_total')->numeric(),
                    Forms\Components\TextInput::make('currency')->maxLength(3),
                    Forms\Components\DateTimePicker::make('ordered_at'),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('external_increment_id')->label('Order')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('customer_name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('grand_total')->money(fn (ExternalOrder $record): string => $record->currency ?: 'EUR')->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->sortable(),
                Tables\Columns\TextColumn::make('payment_status')->badge()->sortable(),
                Tables\Columns\TextColumn::make('source_label')->label('Source')->badge()->searchable()->sortable(),
                Tables\Columns\TextColumn::make('business_name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('source_platform')->badge()->sortable(),
                Tables\Columns\TextColumn::make('server.name')->label('Server')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('ordered_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('last_synced_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('business_name')->options(fn (): array => ExternalOrder::query()->distinct()->pluck('business_name', 'business_name')->filter()->all()),
                Tables\Filters\SelectFilter::make('source_platform')->options(fn (): array => ExternalOrder::query()->distinct()->pluck('source_platform', 'source_platform')->all()),
                Tables\Filters\SelectFilter::make('source_label')->options(fn (): array => ExternalOrder::query()->distinct()->pluck('source_label', 'source_label')->all()),
                Tables\Filters\SelectFilter::make('server')->relationship('server', 'name')->searchable(),
                Tables\Filters\SelectFilter::make('status')->options(fn (): array => ExternalOrder::query()->distinct()->pluck('status', 'status')->filter()->all()),
                Tables\Filters\SelectFilter::make('payment_status')->options(fn (): array => ExternalOrder::query()->distinct()->pluck('payment_status', 'payment_status')->filter()->all()),
            ])
            ->actions([
                Actions\EditAction::make(),
            ])
            ->toolbarActions([
                Actions\DeleteBulkAction::make()->deselectRecordsAfterCompletion(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExternalOrders::route('/'),
            'create' => Pages\CreateExternalOrder::route('/create'),
            'edit' => Pages\EditExternalOrder::route('/{record}/edit'),
        ];
    }
}
