<?php

namespace App\Filament\TourSubAdmin\Pages;

use Filament\Support\Icons\Heroicon;

use BackedEnum;

use App\Models\Tour;
use Filament\Pages\Page;

class TourProfile extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;
    protected static string|\UnitEnum|null $navigationGroup = 'Tours';
    protected static ?string $navigationParentGroup = 'Catálogo';

    protected string $view = 'filament.tour-sub-admin.pages.tour-profile';
    public $tour;

    public function mount(): void
    {
        $this->tour = Tour::with(['admin', 'schedules'])
            ->firstOrFail();
    }
}
