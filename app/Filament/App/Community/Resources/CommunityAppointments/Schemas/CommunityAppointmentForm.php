<?php

namespace App\Filament\App\Community\Resources\CommunityAppointments\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CommunityAppointmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([Section::make('Cita')->schema([
                Select::make('community_id')->label('Comunidad')->relationship('community', 'name')->searchable()->preload()->required(),
                Select::make('person_id')->label('Propietario / usuario')->relationship('person', 'display_name')->searchable()->preload(),
                Select::make('property_id')->label('Propiedad')->relationship('property', 'name')->searchable()->preload(),
                Select::make('community_department_id')->label('Departamento')->relationship('department', 'name')->searchable()->preload(),
                TextInput::make('title')->label('Motivo')->required()->columnSpanFull(),
                DateTimePicker::make('starts_at')->label('Inicio')->required(), DateTimePicker::make('ends_at')->label('Fin')->after('starts_at'),
                Select::make('status')->label('Estado')->options(['scheduled' => 'Pendiente', 'confirmed' => 'Confirmada', 'completed' => 'Finalizada', 'cancelled' => 'Cancelada'])->default('scheduled')->required(),
                TextInput::make('location')->label('Lugar'), Textarea::make('description')->label('Notas')->columnSpanFull(),
            ])->columns(2)]);
    }
}
