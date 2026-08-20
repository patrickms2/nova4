<?php

namespace App\Filament\App\Resources\TaxistaDocuments\Widgets;

use App\Models\TaxiCentral\DocumentType;
use App\Models\TaxistaDocument;
use App\Support\PortalTaxistaContext;
use App\Support\TaxistaDocumentTypes;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TaxistaDocumentStats extends BaseWidget
{
    protected function getStats(): array
    {
        $favoriteTypes = $this->favoriteTypes();

        if ($favoriteTypes->isEmpty()) {
            return [];
        }

        $countsByType = $this->query()
            ->selectRaw('LOWER(TRIM(document_type)) as type_key')
            ->selectRaw('COUNT(*) as total')
            ->whereNotNull('document_type')
            ->groupBy('type_key')
            ->pluck('total', 'type_key');

        return $favoriteTypes
            ->map(function (DocumentType $type) use ($countsByType): Stat {
                $resolvedTypeKey = $this->resolveTypeKey($type);

                return Stat::make(
                    (string)$type->name,
                    (string)((int)($countsByType[$resolvedTypeKey] ?? 0)),
                )
                    ->description($type->description)
                    ->descriptionIcon($type->icon ?: 'heroicon-o-document-text')
                    ->color('gray');
            })
            ->values()
            ->all();
    }

    private function query(): Builder
    {
        return PortalTaxistaContext::scopeTaxistaRecordQuery(TaxistaDocument::query());
    }

    private function cacheKey(): string
    {
        $panelId = \Filament\Facades\Filament::getCurrentPanel()?->getId() ?? 'panel';
        $scopeId = PortalTaxistaContext::isPortalPanel() ? (string)(PortalTaxistaContext::taxistaUserId() ?? 0) : 'all';

        return sprintf('stats:%s:%s:%s', static::class, $panelId, $scopeId);
    }

    private function favoriteTypes(): Collection
    {
        return Cache::remember(
            sprintf('stats:favorite-doc-types:%s', \Filament\Facades\Filament::getCurrentPanel()?->getId() ?? 'panel'),
            now()->addSeconds(15),
            fn(): Collection => DocumentType::query()
                ->where('favorito', true)
                ->where('is_active', true)
                ->orderBy('order')
                ->limit(4)
                ->get(['name', 'description', 'color', 'order', 'favorito', 'code', 'icon']),
        );
    }

    private function resolveTypeKey(DocumentType $type): string
    {
        $options = TaxistaDocumentTypes::options();
        $optionKeys = array_keys($options);

        $aliasMap = [
            'nomina' => 'nomina',
            'nominas' => 'nomina',
            'cuota' => 'cuotas',
            'cuotas' => 'cuotas',
            'agencia' => 'agencias',
            'agencias' => 'agencias',
            'repuesto' => 'repuestos',
            'repuestos' => 'repuestos',
            'seguro' => 'seguro',
            'seguros' => 'seguro',
            'impuesto' => 'impuesto',
            'impuestos' => 'impuesto',
            'certificado' => 'certificado',
            'certificados' => 'certificado',
            'otro' => 'otros',
            'otros' => 'otros',
        ];

        $labelToKey = collect($options)
            ->mapWithKeys(fn(string $label, string $key): array => [$this->normalizeTypeValue($label) => $key])
            ->all();

        foreach ([(string)($type->code ?? ''), (string)($type->name ?? '')] as $candidateRaw) {
            $candidate = $this->normalizeTypeValue($candidateRaw);

            if ($candidate === '') {
                continue;
            }

            if (in_array($candidate, $optionKeys, true)) {
                return $candidate;
            }

            if (array_key_exists($candidate, $aliasMap)) {
                return $aliasMap[$candidate];
            }

            if (array_key_exists($candidate, $labelToKey)) {
                return $labelToKey[$candidate];
            }
        }

        return 'otros';
    }

    private function normalizeTypeValue(string $value): string
    {
        return (string)Str::of($value)
            ->lower()
            ->ascii()
            ->replace(['-', '_'], ' ')
            ->squish()
            ->replace(' ', '');
    }
}
