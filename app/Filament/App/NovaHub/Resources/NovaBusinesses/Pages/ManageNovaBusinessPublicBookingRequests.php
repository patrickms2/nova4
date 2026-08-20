<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages;

use App\Filament\App\NovaHub\Resources\NovaBusinesses\NovaBusinessResource;
use App\Models\ExternalSyncMapping;
use App\Models\NovaBusiness;
use App\Models\PublicBookingRequest;
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

final class ManageNovaBusinessPublicBookingRequests extends Page implements HasTable
{
    use InteractsWithRecord;
    use InteractsWithTable;

    protected static string $resource = NovaBusinessResource::class;

    protected string $view = 'filament.app.resources.nova-businesses.pages.manage-nova-business-table';

    protected static ?string $navigationLabel = 'Solicitudes públicas';
    protected static ?string $navigationParentItem = 'Catálogo';
    protected static \UnitEnum|string|null $navigationGroup = 'Catálogo';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static ?int $navigationSort = 15;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        self::authorizeResourceAccess();
    }

    public static function getNavigationBadge(): ?string
    {
        $record = Livewire::current()->getRecord();
        $count = self::requestQueryForBusiness((int) $record->id)->count();

        return (string) cache()->remember(
                    static::class . '.' . $record->id . '.navigation-badge',
                    now()->addMinute(),
                    fn () => $count > 0 ? (string) $count : null
                );
    }

    public function getHeading(): string|Htmlable|null
    {
        return $this->getRecord()->name;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Solicitudes creadas desde Explore para servicios vinculados a este cliente.';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => self::requestQueryForBusiness((int) $this->getRecord()->id))
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('request_reference')->label('Ref')->searchable()->sortable()->weight('bold'),
                TextColumn::make('type')->label('Tipo')->badge()->sortable(),
                TextColumn::make('service_name')->label('Servicio')->searchable()->sortable(),
                TextColumn::make('customer_name')->label('Cliente')->searchable()->sortable(),
                TextColumn::make('customer_email')->label('Email')->searchable()->toggleable(),
                TextColumn::make('tour_date')->label('Fecha tour')->date('d/m/Y')->sortable()->toggleable(),
                TextColumn::make('tour_schedule')->label('Hora')->toggleable(),
                TextColumn::make('remote_external_id')->label('ID externo')->searchable()->toggleable(),
                TextColumn::make('payment_status')->label('Pago')->badge()->sortable(),
                TextColumn::make('status')->label('Estado')->badge()->sortable(),
                TextColumn::make('created_at')->label('Creada')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')->label('Tipo')->options([
                    'hotel' => 'Hotel',
                    'restaurant' => 'Restaurante',
                    'taxi' => 'Taxi',
                    'tour' => 'Tour',
                ]),
                SelectFilter::make('status')->label('Estado')->options([
                    'pending' => 'Pendiente',
                    'approved' => 'Aprobada',
                    'cancelled' => 'Cancelada',
                ]),
                SelectFilter::make('payment_status')->label('Pago')->options([
                    'pending' => 'Pendiente',
                    'paid' => 'Pagado',
                    'failed' => 'Fallido',
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    private static function requestQueryForBusiness(int $businessId): Builder
    {
        $businessName = NovaBusiness::query()->whereKey($businessId)->value('name');
        $tourIds = ExternalSyncMapping::query()
            ->where('business_name', $businessName)
            ->where('target_model', 'tour')
            ->whereNotNull('target_id')
            ->select('target_id');

        return PublicBookingRequest::query()
            ->with('assignedAdmin')
            ->where(function (Builder $query) use ($businessName, $tourIds): void {
                $query
                    ->whereIn('service_id', $tourIds)
                    ->orWhere('remote_source_label', 'like', '%'.(string) $businessName.'%');
            });
    }
}
