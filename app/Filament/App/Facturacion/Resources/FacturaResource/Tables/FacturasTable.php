<?php

namespace App\Filament\App\Facturacion\Resources\FacturaResource\Tables;

use Filament\Support\Icons\Heroicon;

use App\Enums\PagoEstado;
use App\Mail\FacturaPdfMail;
use App\Models\Factura;
use App\Models\Taxi\Pago;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group as TableGroup;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class FacturasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->toggleable(true)
                    ->label('ID')
                    ->badge(),
                TextColumn::make('codfactura')->label('NºFactura')
                    ->limit(15)
                    ->wrapHeader()
                    ->size('xs')
                    ->extraAttributes(['style' => 'font-weight: bold'])
                    ->sortable()->searchable(),
                TextColumn::make('empresa.empresa')->label('Empresa')->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('cliente.nombretotal')->label('Cliente')
                    ->limit(15)
                    ->wrapHeader()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->extraAttributes(['style' => 'font-weight: bold'])
                    ->size('xs')->wrap()->sortable(),
                TextColumn::make('remesa.nombre')->label('Remesa')->placeholder('—')->sortable(),
                TextColumn::make('notas')->label('Notas')->wrap(),
                TextColumn::make('fechaemitido')->label('Fecha')
                    ->date('d/m/Y')->sortable()->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn ($state): string => $state->format('d/m/Y')),
                TextColumn::make('baseimponible')->label('Base Imponible')->money('EUR')->sortable(),
                TextColumn::make('impuesto')->label('IGIC')->money('EUR')->sortable(),
                TextColumn::make('retenciones')->label('Retenciones')->money('EUR')->sortable(),
                TextColumn::make('importe')->label('Importe')->money('EUR')->sortable(),
                IconColumn::make('pagada')->boolean(),
                TextColumn::make('status')->badge(),
            ])
            ->actions([
                EditAction::make()->iconButton()->tooltip('Editar'),
                Action::make('pdf')
                    ->iconButton()->tooltip('PDF')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->url(fn ($record) => route('factura.pdf', ['codfactura' => $record->codfactura]))
                    ->openUrlInNewTab(),
                Action::make('email')
                    ->iconButton()->tooltip('Email')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->action(fn (Factura $record) => \Mail::to($record->cliente->email ?? config('facturacion.emisor.email'))->send(new FacturaPdfMail($record))),
                Action::make('duplicar')
                    ->iconButton()->tooltip('Duplicar')
                    ->icon(Heroicon::OutlinedDocumentDuplicate)
                    ->action(fn (Factura $record) => $record->duplicate()),
                Action::make('zip')
                    ->iconButton()->tooltip('Zip')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->url(fn ($record) => route('factura.zip', $record)),
                Action::make('cancelar')
                    ->iconButton()->tooltip('Eliminar')
                    ->modal(false)
                    ->requiresConfirmation()
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->action(function (Factura $record, array $data, Action $action, $livewire) {
                        $record->delete();
                        $action->success();
                        $livewire->dispatch('close-modal', id: 'fi-'.$action->getLivewire()->getId().'-action-0');
                    }),
                Action::make('pagar')
                    ->icon('euro')
                    ->visible(false)
                    ->label('Redsys')
                    ->keyBindings(['mod+p'])
                    ->openUrlInNewTab(true)
                    ->action(function (Factura $record, array $data, $action) {
                        $nuevoPagado = $record->pagado + (float) ($data['pagado'] ?? 0);
                        $updateData = [
                            'pagado' => $nuevoPagado,
                            'metodo_Factura' => 'R',
                            'status' => PagoEstado::PENDIENTE,
                            'ref_pago' => $record->ref_pago,
                            'usuario_id' => $record->usuario_id,
                            'fecha_pago' => Carbon::now()->format('Y-m-d H:i:s'),
                        ];
                        if ($nuevoPagado >= $record->importe) {
                            $updateData['status'] = PagoEstado::PAGADO;
                        } elseif ($nuevoPagado > 0) {
                            $updateData['status'] = PagoEstado::PAGO_PARCIAL;
                        } else {
                            $updateData['status'] = PagoEstado::PENDIENTE;
                        }
                        $record->updateOrFail($updateData);
                        Notification::make()->title('Abriendo Redsys')->body('Redirigiendo al TPV en una nueva pestaña...')->success()->send();
                        $action->url(fn (Factura $record) => route('redsys.pay.fromPago', ['pago' => $record->id]), shouldOpenInNewTab: true);
                    }),
                ActionGroup::make([
                    ViewAction::make()->iconButton()->tooltip('Ver')->visible(true),
                    EditAction::make('add-pay')
                        ->icon('euro+')
                        ->iconButton()
                        ->openUrlInNewTab(true)
                        ->closeModalByClickingAway(false)
                        ->extraModalFooterActions(function (Factura $record, Action $action): array {
                            return [
                                $action->getModalSubmitAction()
                                    ->label('Ir a Redsys 4')
                                    ->openUrlInNewTab(true)
                                    ->icon(Heroicon::OutlinedCreditCard)
                                    ->color('warning')
                                    ->close(false)
                                    ->action(function (Factura $record, array $data, $action) {
                                        $nuevoPagado = $record->pagado + (float) ($data['pagado'] ?? 0);
                                        $updateData = [
                                            'pagado' => $nuevoPagado,
                                            'metodo_pago' => 'R',
                                            'status' => PagoEstado::PENDIENTE,
                                            'ref_pago' => $record->ref_pago,
                                            'usuario_id' => $record->usuario_id,
                                            'fecha_pago' => Carbon::now()->format('Y-m-d H:i:s'),
                                        ];
                                        if ($nuevoPagado >= $record->importe) {
                                            $updateData['status'] = PagoEstado::PAGADO;
                                        } elseif ($nuevoPagado > 0) {
                                            $updateData['status'] = PagoEstado::PAGO_PARCIAL;
                                        } else {
                                            $updateData['status'] = PagoEstado::PENDIENTE;
                                        }
                                        $record->updateOrFail($updateData);
                                        Notification::make()->title('Abriendo Redsys')->body('Redirigiendo al TPV en una nueva pestaña...')->success()->send();
                                        $url = route('redsys.pay.fromPago', ['pago' => $record->id]);
                                        $action->url(fn (Factura $record) => route('redsys.pay.fromPago', ['pago' => $record->id]), shouldOpenInNewTab: true);

                                        return $url;
                                    }),
                            ];
                        })
                        ->schema([
                            Section::make(fn (Factura $record) => "Pedido: {$record->referencia} | Pendiente: ".($record->importe - $record->pagado)."€ de {$record->importe}€")
                                ->description('Selecciona el método e ingresa el importe a cobrar')
                                ->schema([
                                    Hidden::make('ref_pago')
                                        ->default(fn () => 'PAG-'.mb_strtoupper(mb_substr(md5(time()), 0, 8))),
                                    TextInput::make('pagado')
                                        ->label('Importe a cobrar')
                                        ->numeric()
                                        ->required()
                                        ->suffix('€')
                                        ->suffixIcon(Heroicon::OutlinedCurrencyEuro)
                                        ->suffixActions([
                                            Action::make('resto')
                                                ->icon(Heroicon::Banknotes)
                                                ->tooltip('Cobrar el resto')
                                                ->action(fn (Set $set, Factura $record) => $set('pagado', $record->importe - $record->pagado)),
                                            Action::make('mitad')
                                                ->icon(Heroicon::CurrencyEuro)
                                                ->tooltip('Cobrar la mitad del resto')
                                                ->action(fn (Set $set, Factura $record) => $set('pagado', ($record->importe - $record->pagado) / 2)),
                                            Action::make('pagar')
                                                ->icon('euro')
                                                ->label('Pagar')
                                                ->keyBindings(['mod+p'])
                                                ->openUrlInNewTab(true)
                                                ->action(function (Factura $record, array $data, $action) {
                                                    $nuevoPagado = $record->pagado + (float) ($data['pagado'] ?? 0);
                                                    $updateData = [
                                                        'pagado' => $nuevoPagado,
                                                        'metodo_pago' => 'R',
                                                        'status' => PagoEstado::PENDIENTE,
                                                        'ref_pago' => $record->ref_pago,
                                                        'usuario_id' => $record->usuario_id,
                                                        'fecha_pago' => Carbon::now()->format('Y-m-d H:i:s'),
                                                    ];
                                                    if ($nuevoPagado >= $record->importe) {
                                                        $updateData['status'] = PagoEstado::PAGADO;
                                                    } elseif ($nuevoPagado > 0) {
                                                        $updateData['status'] = PagoEstado::PAGO_PARCIAL;
                                                    } else {
                                                        $updateData['status'] = PagoEstado::PENDIENTE;
                                                    }
                                                    $record->updateOrFail($updateData);
                                                    Notification::make()->title('Abriendo Redsys')->body('Redirigiendo al TPV en una nueva pestaña...')->success()->send();
                                                    $action->url(fn (Factura $record) => route('redsys.pay.fromPago', ['pago' => $record->id]), shouldOpenInNewTab: true);
                                                }),
                                        ])
                                        ->default(fn (Pago $record) => $record->importe - $record->pagado),
                                    ToggleButtons::make('metodo_pago')
                                        ->label('Método de Factura')
                                        ->options([
                                            'C' => 'Efectivo',
                                            'T' => 'Transferencia',
                                            'R' => 'Redsys (TPV)',
                                            'Z' => 'Bizum',
                                        ])
                                        ->default('R')
                                        ->inline()
                                        ->live(),
                                    DateTimePicker::make('fecha_pago')
                                        ->visible(false)
                                        ->default(now()),
                                ])
                                ->columns(2)
                                ->compact(),
                        ])
                        ->modalSubmitActionLabel('Guardar')
                        ->modalHeading('Pago')
                        ->modalSubheading('Ingresa el importe a cobrar')
                        ->livewireClickHandlerEnabled(true)
                        ->successNotificationTitle('Pago procesado'),
                    Action::make('refund')
                        ->icon('euro-')
                        ->iconButton()
                        ->requiresConfirmation()
                        ->modalHeading('')
                        ->modalSubheading('')
                        ->modalContent(fn (Factura $record) => new HtmlString("Importe total {$record->pagado} €"))
                        ->schema(fn (Factura $record) => [
                            TextInput::make('importe')
                                ->label('Cantidad a devolver')
                                ->required()
                                ->live()
                                ->live()
                                ->suffix(' €')
                                ->default(fn (Factura $record, Get $get, Set $set) => tap($record->pagado, fn ($v) => $set('pagado', $v)))
                                ->suffixActions([
                                    Action::make('resto')
                                        ->icon(Heroicon::CurrencyEuro)->size('md')
                                        ->action(fn (Set $set, Factura $record, Get $get) => $set('pagado', $record->importe - $record->pagado)),
                                    Action::make('mitad')
                                        ->icon(Heroicon::CurrencyEuro)->size('sm')
                                        ->action(fn (Set $set, Factura $record, Get $get) => $set('pagado', ($record->importe - $record->pagado) / 2)),
                                    Action::make('todo')
                                        ->icon(Heroicon::CurrencyEuro)->size('xs')
                                        ->action(function (Set $set, Factura $record, Get $get) {
                                            $set('pagado', $get('importe'));
                                            $nuevoPagado = $record->pagado + $get('importe');
                                            $updateData = [
                                                'pagado' => $nuevoPagado,
                                                'metodo_pago' => 'R',
                                                'status' => PagoEstado::PENDIENTE,
                                                'ref_pago' => $record->ref_pago,
                                                'usuario_id' => $record->usuario_id,
                                                'fecha_pago' => Carbon::now()->format('Y-m-d H:i:s'),
                                            ];
                                            if ($nuevoPagado >= $record->importe) {
                                                $updateData['status'] = PagoEstado::PAGADO;
                                            } elseif ($nuevoPagado > 0) {
                                                $updateData['status'] = PagoEstado::PAGO_PARCIAL;
                                            } else {
                                                $updateData['status'] = PagoEstado::PENDIENTE;
                                            }
                                            $record->updateOrFail($updateData);
                                            Notification::make()->title('Abriendo Redsys')->body('Redirigiendo al TPV en una nueva pestaña...')->success()->send();
                                        }),
                                ]),
                        ])
                        ->action(function (Factura $record, array $data) {
                            $importe = $record->pagado - $data['importe'];
                            if (($record->importe > $record->pagado) && ($importe > 0)) {
                                $record->where('id', $record->id)->update(['referencia' => $record->refID($record->id), 'pagado' => $importe, 'status' => PagoEstado::PAGO_PARCIAL]);
                            } else {
                                $record->where('id', $record->id)->update(['referencia' => $record->refID($record->id), 'pagado' => $importe, 'status' => PagoEstado::PENDIENTE]);
                            }
                            Notification::make()->title('Devolució creada.')->success()->send();
                        }),
                    EditAction::make('pay')
                        ->icon(Heroicon::OutlinedCreditCard)
                        ->iconButton()
                        ->openUrlInNewTab(true)
                        ->closeModalByClickingAway(false)
                        ->modalSubmitActionLabel('Guardar')
                        ->modalHeading('Pago')
                        ->modalSubheading('Ingresa el importe a cobrar')
                        ->livewireClickHandlerEnabled(true)
                        ->url(fn (Factura $record) => route('redsys.pay.fromPago', ['pago' => $record->id]), shouldOpenInNewTab: true)
                        ->successNotificationTitle('Pago procesado'),
                    Action::make('Pagado')
                        ->hiddenLabel()
                        ->icon(Heroicon::Banknotes)
                        ->requiresConfirmation()
                        ->action(function (Factura $record) {
                            $record->status = PagoEstado::PAGADO;
                            $record->pagado = $record->importe;
                            $record->save();
                        }),
                ]),
            ])
            ->groups([
                TableGroup::make('empresa.empresa')->label('Empresa')->titlePrefixedWithLabel(true)->collapsible(true),
                TableGroup::make('cliente.nombretotal')->label('Cliente')->titlePrefixedWithLabel(true)->collapsible(true),
                TableGroup::make('pagada')->label('Pagada')->titlePrefixedWithLabel(true)->collapsible(true),
                TableGroup::make('status')->label('Estado')->titlePrefixedWithLabel(false)->collapsible(true),
            ])
            ->filters([
                SelectFilter::make('empresa_id')
                    ->label('Empresa')
                    ->relationship('empresa', 'empresa')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->empresa ?? 'Empresa #'.$record->id)
                    ->multiple(true)
                    ->preload(),
                SelectFilter::make('cliente_id')
                    ->label('Cliente')
                    ->searchable()
                    ->relationship('cliente', 'nombretotal')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->nombretotal ?? 'Cliente #'.$record->id)
                    ->preload(),
                SelectFilter::make('remesa_id')
                    ->label('Remesa')
                    ->searchable()
                    ->relationship('remesa', 'nombre')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->nombre ?? 'Remesa #'.$record->id)
                    ->preload(),
                TernaryFilter::make('estado'),
                DateRangeFilter::make('fechaemitido')
                    ->label('Fecha Inicio')
                    ->defaultCustom(Carbon::now()->startOfYear(), Carbon::now()->endOfYear()),
            ], layout: FiltersLayout::Dropdown)
            ->filtersFormSchema(fn (array $filters): array => [
                Section::make()
                    ->schema([
                        $filters['empresa_id'],
                        $filters['cliente_id'],
                        $filters['remesa_id'],
                        $filters['estado'],
                        $filters['fechaemitido'],
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ])
            ->filtersFormColumns(3)
            ->filtersFormWidth(Width::FourExtraLarge)
            ->defaultView('edit');
    }
}
