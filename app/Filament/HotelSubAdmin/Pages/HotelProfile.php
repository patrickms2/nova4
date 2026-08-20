<?php

namespace App\Filament\HotelSubAdmin\Pages;

use Filament\Support\Icons\Heroicon;

use BackedEnum;
use UnitEnum;
use App\Models\Hotel;
use Filament\Pages\Page;

class HotelProfile extends Page
{
    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedDocumentText;
    protected static string | UnitEnum | null $navigationGroup = 'Hoteles';
    protected static ?string $navigationParentGroup = 'Catálogo';
    protected string $view = 'filament.hotel-sub-admin.pages.hotel-profile';
    
    public $hotel;
    public function mount(): void
    {
        $this->hotel = Hotel::where('admin_id', auth()->id())->firstOrFail();
    }
}
