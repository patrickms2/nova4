<?php

declare(strict_types=1);

namespace App\Filament\Resources\VillaBookingResource\Pages;

use App\Contracts\PricingStrategyInterface;
use App\Models\Rental;
use App\Models\RentalType;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\VillaBookingResource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Enums\IconSize;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Illuminate\Support\HtmlString;

class CreateVillas extends CreateRecord
{
    protected static string $resource = VillaBookingResource::class;

}
