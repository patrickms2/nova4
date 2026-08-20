<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Taxistas\Pages;

use App\Filament\App\Resources\Taxistas\TaxistaResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;
use Livewire\Livewire;

class ManageConductoresTaxista extends ManageRelatedRecords
{
    protected static string $resource = TaxistaResource::class;

    protected static string $relationship = 'conductores';

    protected static ?string $navigationLabel = 'Conductores';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        $record = Livewire::current()->getRecord();

        return (string) $record->conductores()->count();
    }

    public function getHeading(): string|Htmlable|null
    {
        return (string) ($this->getRecord()->name ?? 'Taxista');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Gestiona conductores asociados a este taxista.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('attach_existing_conductor')
                ->label('Asociar conductor existente')
                ->icon('heroicon-o-paper-clip')
                ->form([
                    Select::make('conductor_id')
                        ->label('Conductor')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->options(fn (): array => User::query()
                            ->where('role', 'conductor')
                            ->whereNull('taxista_id')
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray()),
                ])
                ->action(function (array $data): void {
                    $owner = $this->getRecord();

                    $conductor = User::query()
                        ->where('role', 'conductor')
                        ->whereNull('taxista_id')
                        ->whereKey((int) $data['conductor_id'])
                        ->first();

                    if (! $conductor) {
                        Notification::make()
                            ->title('El conductor ya no está disponible para asociar')
                            ->danger()
                            ->send();

                        return;
                    }

                    $conductor->update([
                        'taxista_id' => (int) $owner->id,
                    ]);

                    Notification::make()
                        ->title('Conductor asociado correctamente')
                        ->success()
                        ->send();
                }),
            CreateAction::make()
                ->label('Añadir conductor')
                ->fillForm(fn (): array => [
                    'taxista_id' => $this->getRecord()->id,
                    'role' => 'conductor',
                    'status' => true,
                ])
                ->mutateFormDataUsing(function (array $data): array {
                    $data['taxista_id'] = (int) $this->getRecord()->id;
                    $data['role'] = 'conductor';
                    $data['status'] = (bool) ($data['status'] ?? true);
                    $data['password'] = Str::random(40);

                    return $data;
                }),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('taxista_id'),
                Hidden::make('role')->default('conductor'),
                Hidden::make('status')->default(true),
                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique('users', 'email', ignoreRecord: true),
                TextInput::make('phone')
                    ->label('Teléfono')
                    ->tel()
                    ->maxLength(50),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('phone')
                    ->label('Teléfono')
                    ->toggleable(),
                IconColumn::make('status')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('unassign')
                    ->label('Desasociar')
                    ->icon('heroicon-o-link-slash')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn (User $record): bool => $record->update(['taxista_id' => null])),
                DeleteAction::make(),
            ])
            ->defaultSort('name');
    }
}
