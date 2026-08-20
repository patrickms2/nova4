<?php

namespace App\Filament\App\NovaHub\Resources\NovaBusinesses\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class NovaBusinessesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withCount([
                'services',
                'mcpServers',
                'tools',
                'aiProfiles',
                'whatsappChannels',
                'facturas',
                'aiKnowledge',
                'listingCategories',
                'crossSellingRules',
                'intentRules',
            ]))
            ->columns([
                TextColumn::make('name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('business_type')
                    ->label('Tipo')
                    ->badge()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->sortable(),
                TextColumn::make('services_count')
                    ->label('Servicios')
                    ->badge()
                    ->sortable(),
                TextColumn::make('mcp_servers_count')
                    ->label('MCP')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'gray')
                    ->sortable(),
                TextColumn::make('tools_count')
                    ->label('Tools')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'info' : 'gray')
                    ->sortable(),
                TextColumn::make('ai_profiles_count')
                    ->label('IA')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'warning' : 'gray')
                    ->sortable(),
                TextColumn::make('whatsapp_channels_count')
                    ->label('WA')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'gray')
                    ->sortable(),
                TextColumn::make('facturas_count')
                    ->label('Facturas')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'gray')
                    ->sortable(),
                TextColumn::make('ai_knowledge_count')
                    ->label('Conocimiento')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('listing_categories_count')
                    ->label('Listing')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('cross_selling_rules_count')
                    ->label('Cross-selling')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('intent_rules_count')
                    ->label('Intents')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('business_type')
                    ->label('Tipo')
                    ->options([
                        'taxi' => 'Taxi / traslados',
                        'hotel' => 'Hotel / apartamentos',
                        'restaurant' => 'Restaurante',
                        'activity' => 'Actividad / visitas',
                        'commerce' => 'Comercio',
                        'winery' => 'Bodega',
                        'magento' => 'Magento',
                        'woocommerce' => 'WooCommerce',
                        'other' => 'Otro',
                    ]),
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'active' => 'Activo',
                        'trial' => 'Prueba',
                        'paused' => 'Pausado',
                        'draft' => 'Borrador',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
