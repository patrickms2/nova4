<?php

namespace App\Filament\App\Community\Resources\Owners\Pages;

use App\Filament\App\Community\Resources\CommunityAppointments\Schemas\CommunityAppointmentForm;
use App\Filament\App\Community\Resources\CommunityAppointments\Tables\CommunityAppointmentsTable;
use App\Filament\App\Community\Resources\Owners\OwnerResource;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
class ManageOwnerAppointments extends ManageRelatedRecords
{
    protected static string $resource = OwnerResource::class;

    protected static string $relationship = 'communityAppointments';

    protected static ?string $navigationLabel = 'Citas';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->mutateDataUsing(fn (array $data): array => [...$data, 'created_by' => auth()->id()])];
    }

    public function form(Schema $schema): Schema
    {
        return CommunityAppointmentForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return CommunityAppointmentsTable::configure($table);
    }
}
