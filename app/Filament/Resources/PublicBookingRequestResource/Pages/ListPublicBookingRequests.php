<?php

namespace App\Filament\Resources\PublicBookingRequestResource\Pages;

use App\Filament\Resources\PublicBookingRequestResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Archilex\AdvancedTables\AdvancedTables;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPublicBookingRequests extends ListRecords
{

    protected static string $resource = PublicBookingRequestResource::class;

}

