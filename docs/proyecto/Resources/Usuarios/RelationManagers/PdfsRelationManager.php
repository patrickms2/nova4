<?php

namespace App\Filament\App\Resources\Usuarios\RelationManagers;

use App\Models\Taxi\Documento as Pdfs;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema as Form;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PdfsRelationManager extends RelationManager
{
    protected static string $relationship = 'documentos';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('taxista_id')
                    ->integer(),

                TextInput::make('file_name'),

                TextInput::make('attachment_file_names'),

                Placeholder::make('created_at')
                    ->label('Created Date')
                    ->content(fn(?Pdfs $record): string => $record?->created_at?->diffForHumans() ?? '-'),

                Placeholder::make('updated_at')
                    ->label('Last Modified Date')
                    ->content(fn(?Pdfs $record): string => $record?->updated_at?->diffForHumans() ?? '-'),

                TextInput::make('tipo'),

                TextInput::make('nif'),

                TextInput::make('year'),

                TextInput::make('mes'),

                TextInput::make('tipo_id')
                    ->integer(),

                TextInput::make('departamento_id')
                    ->integer(),

                TextInput::make('file_size'),

                TextInput::make('file_ext'),

                TextInput::make('file_type'),

                TextInput::make('favorito')
                    ->integer(),

                TextInput::make('descripcion'),

                Select::make('user_id')
                    ->relationship('usuario', 'name')
                    ->searchable(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->headerActions([
                CreateAction::make()
                    ->fillForm(fn() => [
                        'usuario_id' => $this->getOwnerRecord()->id,
                    ])
                    // Asegura que siempre se guarde asociado al taxista actual
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['usuario_id'] = $this->getOwnerRecord()->id;
                        return $data;
                    })
                    ->slideOver(),
            ])
            ->columns([
                TextColumn::make('taxista_id'),

                TextColumn::make('file_name'),

                TextColumn::make('attachment_file_names'),

                TextColumn::make('tipo'),

                TextColumn::make('nif'),

                TextColumn::make('year'),

                TextColumn::make('mes'),

                TextColumn::make('tipo_id'),

                TextColumn::make('departamento_id'),

                TextColumn::make('file_size'),

                TextColumn::make('file_ext'),

                TextColumn::make('file_type'),

                TextColumn::make('favorito'),

                TextColumn::make('descripcion'),

                TextColumn::make('usuario.name')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
