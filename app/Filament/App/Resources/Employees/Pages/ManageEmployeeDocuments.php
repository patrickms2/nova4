<?php

namespace App\Filament\App\Resources\Employees\Pages;

use App\Filament\App\Resources\Employees\EmployeeResource;
use App\Filament\App\Resources\TaxistaDocuments\Schemas\TaxistaDocumentForm;
use App\Filament\App\Resources\TaxistaDocuments\Tables\TaxistaDocumentsTable;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Livewire;

class ManageEmployeeDocuments extends ManageRelatedRecords
{
    protected static string $resource = EmployeeResource::class;

    protected static string $relationship = 'documents';

    protected static ?string $navigationLabel = 'Documentos';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 6;

    public static function getNavigationBadge(): ?string
    {
        $record = Livewire::current()->getRecord();

        return (string) $record->documents()->count();
    }

    public function getHeading(): string|Htmlable|null
    {
        return $this->getRecord()->name;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Documentos del empleado.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo documento')
                ->fillForm(fn (): array => [
                    'taxista_user_id' => (int) $this->getRecord()->id,
                    'uploaded_by_user_id' => auth()->id(),
                    'status' => 'activo',
                ])
                ->mutateFormDataUsing(function (array $data): array {
                    $data['taxista_user_id'] = (int) $this->getRecord()->id;
                    $data['uploaded_by_user_id'] = auth()->id();

                    return $data;
                }),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return TaxistaDocumentForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return TaxistaDocumentsTable::configure($table)
            ->modifyQueryUsing(fn ($query) => $query->where('taxista_user_id', (int) $this->getRecord()->id));
    }
}
