<?php

namespace App\Filament\App\Community\Resources\Employees\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
class AttendancesRelationManager extends RelationManager
{
    protected static string $relationship = 'communityAttendances';

    protected static ?string $title = 'Registros';

    public function form(Schema $s): Schema
    {
        return $s->components([Select::make('communities')->label('Comunidades visitadas')->relationship('communities', 'name')->multiple()->searchable()->preload()->required(), DatePicker::make('attendance_date')->required(), Select::make('type')->options(['presence' => 'Presencia', 'absence' => 'Ausencia', 'vacation' => 'Vacaciones', 'sick_leave' => 'Baja', 'permission' => 'Permiso'])->default('presence'), DateTimePicker::make('checked_in_at'), DateTimePicker::make('checked_out_at'), Textarea::make('notes')->label('Notas / transcripción')->columnSpanFull(), Select::make('status')->options(['recorded' => 'Registrado', 'approved' => 'Aprobado', 'rejected' => 'Rechazado'])->default('recorded')]);
    }

    public function table(Table $t): Table
    {
        return $t->columns([TextColumn::make('attendance_date')->date(), TextColumn::make('communities.name')->label('Comunidades')->badge(), TextColumn::make('type')->badge(), TextColumn::make('checked_in_at')->dateTime('H:i'), TextColumn::make('checked_out_at')->dateTime('H:i'), TextColumn::make('transcription_status')->label('Audio')->badge(), TextColumn::make('notes')->label('Notas')->limit(50)->toggleable(), TextColumn::make('status')->badge()])->recordActions([
            Action::make('Escuchar audio')->icon('heroicon-o-speaker-wave')->url(fn ($record): string => route('comunigest.attendances.audio', $record))->openUrlInNewTab()->visible(fn ($record): bool => filled($record->closing_audio_path)),
            EditAction::make('Editar'),
            DeleteAction::make('Borrar'),

        ])->headerActions([CreateAction::make()]);
    }
}
