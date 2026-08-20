<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages;

use App\Filament\App\NovaHub\Resources\NovaBusinesses\NovaBusinessResource;
use App\Models\ExternalPayment;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

final class ManageNovaBusinessExternalPayments extends Page implements HasTable
{
    use InteractsWithRecord;
    use InteractsWithTable;

    protected static string $resource = NovaBusinessResource::class;

    protected string $view = 'filament.app.resources.nova-businesses.pages.manage-nova-business-table';

    protected static ?string $navigationLabel = 'Pagos externos';
    protected static ?string $navigationParentItem = 'Reservas';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static ?int $navigationSort = 16;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        self::authorizeResourceAccess();
    }

    public static function getNavigationBadge(): ?string
    {
        $record = Livewire::current()->getRecord();
        $count = ExternalPayment::query()->where('business_name', $record->name)->count();

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
        return 'Pagos externos asociados a este cliente Nova.';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => ExternalPayment::query()->where('business_name', $this->getRecord()->name))
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('paid_at')->label('Fecha pago')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('amount')
                    ->label('Importe')
                    ->money('EUR')
                    ->sortable()
                    ->summarize([Sum::make()->money('EUR')]),
                TextColumn::make('status')->label('Estado')->badge()->sortable(),
                TextColumn::make('processor')->label('Procesador')->badge()->sortable(),
                TextColumn::make('payment_method')->label('Método')->badge()->toggleable()->sortable(),
                TextColumn::make('service_name')->label('Servicio')->searchable()->sortable(),
                TextColumn::make('customer_name')->label('Cliente')->searchable()->sortable(),
                TextColumn::make('external_booking_id')->label('Reserva externa')->searchable()->toggleable(),
                TextColumn::make('source_label')->label('Origen')->badge()->searchable()->sortable(),
                TextColumn::make('last_synced_at')->label('Sync')->dateTime('d/m/Y H:i')->toggleable()->sortable(),
            ])
            ->filters([
                Filter::make('paid_at')
                    ->label('Fecha pago')
                    ->schema([
                        DatePicker::make('paid_from')->label('Desde'),
                        DatePicker::make('paid_until')->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['paid_from'] ?? null, fn (Builder $query, mixed $date): Builder => $query->whereDate('paid_at', '>=', $date))
                            ->when($data['paid_until'] ?? null, fn (Builder $query, mixed $date): Builder => $query->whereDate('paid_at', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['paid_from'] ?? null) {
                            $indicators['paid_from'] = 'Desde '.Carbon::parse($data['paid_from'])->format('d/m/Y');
                        }

                        if ($data['paid_until'] ?? null) {
                            $indicators['paid_until'] = 'Hasta '.Carbon::parse($data['paid_until'])->format('d/m/Y');
                        }

                        return $indicators;
                    }),
                SelectFilter::make('source_platform')->label('Origen')->options(fn (): array => ExternalPayment::query()
                    ->where('business_name', $this->getRecord()->name)
                    ->distinct()
                    ->pluck('source_platform', 'source_platform')
                    ->filter()
                    ->all()),
                SelectFilter::make('processor')->label('Procesador')->options(fn (): array => ExternalPayment::query()
                    ->where('business_name', $this->getRecord()->name)
                    ->distinct()
                    ->pluck('processor', 'processor')
                    ->filter()
                    ->all()),
                SelectFilter::make('status')->label('Estado')->options(fn (): array => ExternalPayment::query()
                    ->where('business_name', $this->getRecord()->name)
                    ->distinct()
                    ->pluck('status', 'status')
                    ->filter()
                    ->all()),
            ])
            ->defaultSort('paid_at', 'desc');
    }
}
