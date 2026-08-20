<?php

namespace App\Filament\App\Rentals\Resources\RentalReservationResource\RelationManagers;

use App\Filament\App\Rentals\Resources\RentalReservationResource;
use App\Models\RentalDocument;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema as Form;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    public function form(Form $form): Form
    {
        return $form
            ->components([
                Select::make('category')
                    ->options(RentalDocument::categories())
                    ->required(),
                TextInput::make('title')->label('Título')->required(),
                FileUpload::make('file_path')->label('Archivo')->directory('rental-documents')->nullable(),
                DatePicker::make('expiry_date')->label('Caducidad'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')->label('Título')->searchable(),
                TextColumn::make('category')
                    ->label('Categoría')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => RentalDocument::categories()[$state] ?? $state),
                TextColumn::make('expiry_date')->label('Caducidad')->date('d M Y'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        $data['documentable_type'] = $this->getOwnerRecord()->getMorphClass();
                        $data['documentable_id'] = $this->getOwnerRecord()->id;

                        return $data;
                    }),
            ]);
    }
}
