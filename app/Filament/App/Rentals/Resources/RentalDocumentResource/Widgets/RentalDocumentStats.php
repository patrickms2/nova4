<?php

namespace App\Filament\App\Rentals\Resources\RentalDocumentResource\Widgets;

use App\Models\RentalDocument;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RentalDocumentStats extends BaseWidget
{
    protected function getStats(): array
    {
        $categories = RentalDocument::categories();

        $counts = RentalDocument::query()
            ->selectRaw('LOWER(TRIM(category)) as category_key')
            ->selectRaw('COUNT(*) as total')
            ->whereNotNull('category')
            ->groupBy('category_key')
            ->pluck('total', 'category_key');

        return collect($categories)
            ->map(function (string $label, string $key) use ($counts): Stat {
                return Stat::make(
                    $label,
                    (string) ((int) ($counts[$key] ?? 0)),
                )
                    ->description('documentos')
                    ->descriptionIcon('heroicon-o-document-text')
                    ->color('gray');
            })
            ->values()
            ->all();
    }
}
