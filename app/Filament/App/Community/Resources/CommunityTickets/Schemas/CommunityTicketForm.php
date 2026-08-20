<?php

namespace App\Filament\App\Community\Resources\CommunityTickets\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\Page;

class CommunityTicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([Section::make('Ticket')->schema([
                Select::make('community_id')->label('Comunidad')->relationship('community', 'name')->searchable()->preload()->required(), Select::make('person_id')->label('Propietario / usuario')->relationship('person', 'display_name')->searchable()->preload(),
                Select::make('property_id')->label('Propiedad')->relationship('property', 'name')->searchable()->preload(), Select::make('community_department_id')->label('Departamento')->relationship('department', 'name')->searchable()->preload(),
                Select::make('type')->label('Tipo')->options(['incidencia' => 'Incidencia', 'gasto' => 'Gasto', 'ticket' => 'Ticket'])->required()
                ->default($tipo),
                TextInput::make('title')->label('Asunto')->required()->columnSpanFull(), Textarea::make('description')->label('Descripción')->required()->columnSpanFull(),
                Select::make('priority')->label('Prioridad')->options(['low' => 'Baja', 'normal' => 'Normal', 'high' => 'Alta', 'urgent' => 'Urgente'])->default('normal')->required(),
                Select::make('status')->label('Estado')->options(['open' => 'Abierto', 'in_progress' => 'En curso', 'resolved' => 'Resuelto', 'closed' => 'Cerrado'])->default('open')->required(), DateTimePicker::make('due_at')->label('Vence'),
            ])->columns(2)]);
    }
}
