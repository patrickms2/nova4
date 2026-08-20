<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages;

use App\Filament\App\NovaHub\Resources\NovaBusinesses\NovaBusinessResource;
use App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages\Concerns\CanSyncNovaBusinessIntegrations;
use App\Models\Booking;
use App\Models\ExternalSyncMapping;
use App\Models\NovaBusiness;
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

final class ManageNovaBusinessBookings extends Page implements HasTable
{
    use CanSyncNovaBusinessIntegrations;
    use InteractsWithRecord;
    use InteractsWithTable;

    protected static string $resource = NovaBusinessResource::class;

    protected string $view = 'filament.app.resources.nova-businesses.pages.manage-nova-business-table';

    protected static ?string $navigationLabel = 'Reservas';
    protected static ?string $navigationParentItem = 'Reservas';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?int $navigationSort = 13;

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
                    fn () => self::bookingQueryForBusiness((int) $record->id)->count()
                );
    }

    public function getHeading(): string|Htmlable|null
    {
        return $this->getRecord()->name;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Reservas internas generadas por tours vinculados a este cliente.';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => self::bookingQueryForBusiness((int) $this->getRecord()->id))
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('booking_reference')->label('Referencia')->searchable()->sortable()->weight('bold'),
                TextColumn::make('user.email')->label('Usuario')->searchable(),
                TextColumn::make('tourBooking.tour.name')->label('Tour')->searchable()->toggleable(),
                TextColumn::make('booking_date')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('total_price')->label('Total')->money('EUR')->sortable(),
                TextColumn::make('payment_status')->label('Pago')->badge()->sortable(),
                TextColumn::make('status')->label('Estado')->badge()->sortable(),
            ])
            ->filters([
                SelectFilter::make('payment_status')->label('Pago')->options([
                    'Pending' => 'Pendiente',
                    'Paid' => 'Pagado',
                    'Refunded' => 'Reembolsado',
                    'Failed' => 'Fallido',
                ]),
                SelectFilter::make('status')->label('Estado')->options([
                    'Pending' => 'Pendiente',
                    'Confirmed' => 'Confirmada',
                    'Cancelled' => 'Cancelada',
                    'Completed' => 'Completada',
                ]),
            ])
            ->defaultSort('booking_date', 'desc');
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->syncIntegrationsAction(),
        ];
    }

    private static function bookingQueryForBusiness(int $businessId): Builder
    {
        $business = NovaBusiness::query()->whereKey($businessId)->first();
        $recognitionTerms = collect($business?->settings['recognition_terms'] ?? [])
            ->push($business?->name)
            ->filter()
            ->values();

        $tourIds = ExternalSyncMapping::query()
            ->where(function (Builder $query) use ($recognitionTerms): void {
                foreach ($recognitionTerms as $term) {
                    $query->orWhere('business_name', 'like', '%'.(string) $term.'%');
                }
            })
            ->where('target_model', 'tour')
            ->whereNotNull('target_id')
            ->select('target_id');

        return Booking::query()
            ->with(['user', 'tourBooking.tour'])
            ->whereHas('tourBooking', fn (Builder $query): Builder => $query->whereIn('tour_id', $tourIds));
    }
}
