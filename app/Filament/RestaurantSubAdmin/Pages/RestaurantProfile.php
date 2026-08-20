<?php

namespace App\Filament\RestaurantSubAdmin\Pages;

use Filament\Support\Icons\Heroicon;

use App\Models\Restaurant;
use BackedEnum;
use UnitEnum;
use Filament\Pages\Page;

class RestaurantProfile extends Page
{
    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected string $view = 'filament.restaurant-sub-admin.pages.restaurant-profile';
    protected static string | UnitEnum | null $navigationGroup = 'Catalogo';
    protected static ?string $navigationParentGroup = 'Restaurant';
    public $restaurant;
    // public function shouldRegisterNavigation(): bool
    // {
    //     return auth()->check(); 
    // }

    public function mount(): void
    {
        $this->restaurant = Restaurant::where('admin_id', auth()->id())->firstOrFail();
    }
}
