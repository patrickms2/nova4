<?php

namespace App\Filament\App\Community\Resources\Employees\Pages;

use App\Filament\App\Community\Resources\Employees\EmployeeResource;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
class ManageEmployeeAttendances extends ManageRelatedRecords
{
    protected static string $resource = EmployeeResource::class;

    protected static string $relationship = 'communityAttendances';

    protected static ?string $navigationLabel = 'Presencia y ausencias';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([Select::make('communities')->label('Comunidades visitadas')->relationship('communities', 'name')->multiple()->searchable()->preload()->required(),

            DatePicker::make('attendance_date')->label('Fecha')->required(), Select::make('type')->options(['presence' => 'Presencia', 'absence' => 'Ausencia', 'vacation' => 'Vacaciones', 'sick_leave' => 'Baja', 'permission' => 'Permiso'])->default('presence'), DateTimePicker::make('checked_in_at')->label('Entrada'), DateTimePicker::make('checked_out_at')->label('Salida'), Select::make('status')->options(['recorded' => 'Registrado', 'approved' => 'Aprobado', 'rejected' => 'Rechazado'])->default('recorded')]);
    }

    public function table(Table $table): Table
    {
        return $table->defaultSort('attendance_date', 'desc')->columns([TextColumn::make('attendance_date')->label('Fecha')->date(), TextColumn::make('communities.name')->label('Comunidades')->badge(), TextColumn::make('type')->label('Tipo')->badge(), TextColumn::make('checked_in_at')->label('Entrada')->dateTime('H:i'), TextColumn::make('checked_out_at')->label('Salida')->dateTime('H:i'), TextColumn::make('transcription_status')->label('Audio')->badge(), TextColumn::make('notes')->label('Notas / transcripción')->limit(60)->wrap(), TextColumn::make('status')->label('Estado')->badge()])->recordActions([
            Action::make('audio')->label('Escuchar')->icon('heroicon-o-speaker-wave')->url(fn ($record): string => route('comunigest.attendances.audio', $record))->openUrlInNewTab()->visible(fn ($record): bool => filled($record->closing_audio_path)),
            Action::make('mapaEntrada')->label('Entrada')->icon('heroicon-o-map-pin')->url(fn ($record): string => "https://www.google.com/maps?q={$record->check_in_latitude},{$record->check_in_longitude}")->openUrlInNewTab()->visible(fn ($record): bool => filled($record->check_in_latitude)),
            Action::make('mapaSalida')->label('Salida')->icon('heroicon-o-map-pin')->url(fn ($record): string => "https://www.google.com/maps?q={$record->check_out_latitude},{$record->check_out_longitude}")->openUrlInNewTab()->visible(fn ($record): bool => filled($record->check_out_latitude)),
        ]);
    }
}
