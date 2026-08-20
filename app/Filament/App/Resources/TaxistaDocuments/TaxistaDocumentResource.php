<?php

namespace App\Filament\App\Resources\TaxistaDocuments;

use App\Filament\App\Resources\TaxistaDocuments\Pages\CreateTaxistaDocument;
use App\Filament\App\Resources\TaxistaDocuments\Pages\EditTaxistaDocument;
use App\Filament\App\Resources\TaxistaDocuments\Pages\KanbanView;
use App\Filament\App\Resources\TaxistaDocuments\Pages\ListTaxistaDocuments;
use App\Filament\App\Resources\TaxistaDocuments\Pages\TaxistaDocumentKanban;
use App\Filament\App\Resources\TaxistaDocuments\Pages\ViewTaxistaDocument;
use App\Filament\App\Resources\TaxistaDocuments\Schemas\TaxistaDocumentForm;
use App\Filament\App\Resources\TaxistaDocuments\Schemas\TaxistaDocumentInfolist;
use App\Filament\App\Resources\TaxistaDocuments\Tables\TaxistaDocumentsTable;
use App\Filament\App\Resources\TaxistaDocuments\Widgets\TaxistaDocumentStats;
use App\Models\TaxistaDocument;
use App\Support\PortalTaxistaContext;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class TaxistaDocumentResource extends Resource
{
    protected static ?string $model = TaxistaDocument::class;

    protected static bool $isGloballySearchable = true;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Documentos';

    protected static \UnitEnum|string|null $navigationGroup = 'Servicios de Taxista';

    protected static ?int $navigationSort = 7;

    protected static bool $isScopedToTenant = false;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->departmentHasService('documents') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return TaxistaDocumentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TaxistaDocumentsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TaxistaDocumentInfolist::configure($schema);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with([
                'department:id,name',
                'uploadedBy:id,name',
                'taxista:id,name,name_first,name_last',
            ]);

        return PortalTaxistaContext::scopeTaxistaRecordQuery($query);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'title',
            'document_type',
            'status',
            'taxista.name',
            'taxista.licencia',
            'taxista.municipio.nombre',
            'taxista.taxis.license_plate',
        ];
    }

    public static function modifyGlobalSearchQuery(Builder $query, string $search): void
    {
        $search = static::stripGlobalSearchPrefix($search, ['doc']);
        $normalizedSearch = static::normalizeShortcutSearch($search);

        if ($search === '' && $normalizedSearch === '') {
            return;
        }

        $query->orWhere(function (Builder $or) use ($search, $normalizedSearch): void {
            if ($search !== '') {
                $or->where('taxista_documents.title', 'like', "%{$search}%")
                    ->orWhere('taxista_documents.document_type', 'like', "%{$search}%")
                    ->orWhere('taxista_documents.status', 'like', "%{$search}%")
                    ->orWhere('global_search_taxistas.name', 'like', "%{$search}%")
                    ->orWhere('global_search_taxistas.licencia', 'like', "%{$search}%")
                    ->orWhere('global_search_municipios.nombre', 'like', "%{$search}%");
            }

            if ($normalizedSearch !== '') {
                $or->orWhereRaw("
                    LOWER(
                        CONCAT(
                            COALESCE(REGEXP_SUBSTR(COALESCE(global_search_taxistas.licencia, ''), '[0-9]+'), ''),
                            CASE
                                WHEN LOWER(COALESCE(global_search_municipios.nombre, '')) IN ('tías', 'tias') THEN 'ti'
                                WHEN LOWER(COALESCE(global_search_municipios.nombre, '')) = 'teguise' THEN 'te'
                                WHEN LOWER(COALESCE(global_search_municipios.nombre, '')) = 'yaiza' THEN 'ya'
                                WHEN LOWER(COALESCE(global_search_municipios.nombre, '')) = 'tinajo' THEN 'tn'
                                WHEN LOWER(COALESCE(global_search_taxistas.licencia, '')) LIKE '%tías%' OR LOWER(COALESCE(global_search_taxistas.licencia, '')) LIKE '%tias%' THEN 'ti'
                                WHEN LOWER(COALESCE(global_search_taxistas.licencia, '')) LIKE '%teguise%' THEN 'te'
                                WHEN LOWER(COALESCE(global_search_taxistas.licencia, '')) LIKE '%yaiza%' THEN 'ya'
                                WHEN LOWER(COALESCE(global_search_taxistas.licencia, '')) LIKE '%tinajo%' THEN 'tn'
                                ELSE ''
                            END
                        )
                    ) LIKE ?
                ", ['%' . $normalizedSearch . '%'])
                    ->orWhereExists(function ($taxiQuery) use ($normalizedSearch): void {
                        $taxiQuery->selectRaw('1')
                            ->from('taxista_taxis')
                            ->whereColumn('taxista_taxis.taxista_user_id', 'taxista_documents.taxista_user_id')
                            ->whereRaw("LOWER(REPLACE(REPLACE(REPLACE(COALESCE(taxista_taxis.license_plate, ''), ' ', ''), '-', ''), '.', '')) LIKE ?", ['%' . $normalizedSearch . '%']);
                    })
                    ->orWhereExists(function ($taxiQuery) use ($normalizedSearch): void {
                        $taxiQuery->selectRaw('1')
                            ->from('taxis')
                            ->where(function ($taxiMatch): void {
                                $taxiMatch
                                    ->whereColumn('taxis.taxista_id', 'taxista_documents.taxista_user_id')
                                    ->orWhereColumn('taxis.usuario_id', 'taxista_documents.taxista_user_id');
                            })
                            ->whereRaw("LOWER(REPLACE(REPLACE(REPLACE(COALESCE(taxis.matricula, ''), ' ', ''), '-', ''), '.', '')) LIKE ?", ['%' . $normalizedSearch . '%']);
                    });
            }
        });
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()
            ->leftJoin('users as global_search_taxistas', 'taxista_documents.taxista_user_id', '=', 'global_search_taxistas.id')
            ->leftJoin('municipios as global_search_municipios', 'global_search_taxistas.municipio_id', '=', 'global_search_municipios.id')
            ->select('taxista_documents.*')
            ->with([
                'department:id,name',
                'uploadedBy:id,name',
                'taxista:id,name,licencia,municipio_id',
                'taxista.municipio:id,nombre',
                'taxista.taxis:id,taxista_user_id,license_plate',
            ]);
    }

    public static function getNavigationLabel(): string
    {
        if (PortalTaxistaContext::isPortalPanel()) {
            return 'Documentos';
        }

        return 'Documentos';
    }

    public static function getNavigationGroup(): \UnitEnum|string|null
    {
        if (PortalTaxistaContext::isPortalPanel()) {
            return 'Taxista';
        }

        return static::$navigationGroup;
    }

    /*public static function canCreate(): bool
    {
        if (PortalTaxistaContext::isPortalPanel()) {
            return PortalTaxistaContext::taxistaUserId() !== null;
        }

        return parent::canCreate();
    }

    public static function canView(Model $record): bool
    {
        return parent::canView($record) && PortalTaxistaContext::canAccessTaxistaRecord($record);
    }

    public static function canEdit(Model $record): bool
    {
        return parent::canEdit($record) && PortalTaxistaContext::canAccessTaxistaRecord($record);
    }

    public static function canDelete(Model $record): bool
    {
        return parent::canDelete($record) && PortalTaxistaContext::canAccessTaxistaRecord($record);
    }*/

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getWidgets(): array
    {
        if (PortalTaxistaContext::isPortalPanel()) {
            return [];
        }

        return [
            TaxistaDocumentStats::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTaxistaDocuments::route('/'),
            //'create' => CreateTaxistaDocument::route('/create'),
            'view' => ViewTaxistaDocument::route('/{record}'),
            //'edit' => EditTaxistaDocument::route('/{record}/edit'),
            'kanban' => KanbanView::route('/kanban'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        if (PortalTaxistaContext::isPortalPanel()) {
            return null;
        }

        $modelClass = static::$model;
        $panelId = \Filament\Facades\Filament::getCurrentPanel()?->getId() ?? 'panel';
        $scopeId = PortalTaxistaContext::isPortalPanel() ? (string)(PortalTaxistaContext::taxistaUserId() ?? 0) : 'all';

        return (string)Cache::remember(
            sprintf('nav_badge:%s:%s:%s', str_replace('\\', '.', (string)$modelClass), $panelId, $scopeId),
            now()->addSeconds(20),
            static function () use ($modelClass): int {
                $query = $modelClass::query();
                PortalTaxistaContext::scopeTaxistaRecordQuery($query);

                return (int)$query->count();
            },
        );
    }

    private static function stripGlobalSearchPrefix(string $search, array $prefixes): string
    {
        $normalizedSearch = static::normalizeShortcutSearch($search);

        foreach ($prefixes as $prefix) {
            if (Str::startsWith($normalizedSearch, $prefix)) {
                $stripped = substr($normalizedSearch, strlen($prefix));

                return $stripped !== '' ? $stripped : trim($search);
            }
        }

        return trim($search);
    }

    private static function normalizeShortcutSearch(string $value): string
    {
        $ascii = Str::of($value)->ascii()->lower()->value();

        return (string) preg_replace('/[^a-z0-9]+/', '', $ascii);
    }
}
