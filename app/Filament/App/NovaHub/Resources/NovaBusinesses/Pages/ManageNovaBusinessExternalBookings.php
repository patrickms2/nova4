<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages;

use App\Filament\App\NovaHub\Resources\NovaBusinesses\NovaBusinessResource;
use App\Models\NovaExternalBooking;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Livewire;

final class ManageNovaBusinessExternalBookings extends Page implements HasTable
{
    use InteractsWithRecord;
    use InteractsWithTable;

    protected static string $resource = NovaBusinessResource::class;

    protected string $view = 'filament.app.resources.nova-businesses.pages.manage-nova-business-table';

    protected static ?string $navigationLabel = 'Reservas externas';
    protected static ?string $navigationParentItem = 'Reservas';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowTopRightOnSquare;

    protected static ?int $navigationSort = 14;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        self::authorizeResourceAccess();
    }

    public static function getNavigationBadge(): ?string
    {
        $record = Livewire::current()->getRecord();

        return (string) cache()->remember(
                    static::class . '.' . $record->id . '.navigation-badge',
                    now()->addMinute(),
                    fn () => $record->externalBookings()->count()
                );
    }

    public function getHeading(): string|Htmlable|null
    {
        return $this->getRecord()->name;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Reservas externas sincronizadas para este cliente Nova.';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => NovaExternalBooking::query()->where('nova_business_id', $this->getRecord()->id))
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('service_name')->label('Servicio')->searchable()->sortable()->weight('bold')->limit(45),
                TextColumn::make('source')->label('Origen')->badge()->sortable(),
                TextColumn::make('external_id')->label('ID externo')->searchable()->toggleable(),
                TextColumn::make('customer_name')->label('Cliente')->searchable()->limit(35),
                TextColumn::make('booking_starts_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('total')->label('Total')->money('EUR')->sortable(),
                TextColumn::make('payment_status')->label('Pago')->badge()->sortable(),
                TextColumn::make('booking_status')->label('Reserva')->badge()->sortable(),
            ])
            ->filters([
                SelectFilter::make('source')->label('Origen')->options([
                    'woo' => 'WooCommerce',
                    'latepoint' => 'LatePoint',
                    'sirvo' => 'Sirvo',
                ]),
                SelectFilter::make('payment_status')->label('Pago'),
                SelectFilter::make('booking_status')->label('Reserva'),
            ])
            ->defaultSort('booking_starts_at', 'desc');
    }
}
