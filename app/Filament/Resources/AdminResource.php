<?php

namespace App\Filament\Resources;

use Filament\Support\Icons\Heroicon;

use App\Filament\Resources\AdminResource\Pages;
use App\Models\Admin;
use App\Models\User;
use App\Notifications\OTPNotification;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema as Form;
use Filament\Tables;
use Filament\Tables\Table;

class AdminResource extends Resource
{
    protected static ?string $model = User::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|\UnitEnum|null $navigationGroup = 'Ajustes';
    protected static ?string $navigationParentGroup = 'Users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('first_name')
                    ->email(),
                Forms\Components\TextInput::make('last_name')
                    ->email(),
                Forms\Components\TextInput::make('email')
                    ->email(),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email(),
                Forms\Components\Select::make('role')
                    ->options(/*function () {
                        $user = auth()->user();
                        if ($user->role === 'super_admin') {
                            return*/ [
                        'super_admin' => 'Super Admin',
                        'admin' => 'Admin',
                        'sub_admin' => 'Sub Admin',
                    ])/*;
                        }
                        if ($user->role === 'admin' || $user->role === 'sub_admin') {
                            return [];
                        }
                        return [];
                   })*/
                    ->required(),

                Forms\Components\TextInput::make('section')
                    ->default(fn() => auth()->user()?->section)
                    ->dehydrated(true),
            ]);
    }
    // public static function afterCreate(Admin $record): void
    // {
    //     $record->code = rand(1000, 9999);
    //     $record->code_expires_at = now()->addMinutes(6);
    //     $record->save();
    //     $record->notify(new OTPNotification());
    // }
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('role')
                    ->searchable(),
                Tables\Columns\TextColumn::make('section')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->modifyQueryUsing(function ($query) {
                $user = auth()->user();
                if ($user->role === 'admin') {
                    $query->where('section', $user->section);
                }
                if ($user->role === 'sub_admin') {
                    $query->where('id', $user->id);
                }
            });
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdmins::route('/'),
            'create' => Pages\CreateAdmin::route('/create'),
            'view' => Pages\ViewAdmin::route('/{record}'),
            'edit' => Pages\EditAdmin::route('/{record}/edit'),
        ];
    }
}
