<?php

namespace App\Filament\App\Resources\TaxistaAppointments\Schemas;

use App\Models\TaxistaAppointment;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class TaxistaAppointmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Resumen de la cita')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                        ])
                        ->extraAttributes([
                                        'x-effect' => "
                                            const footer = \$el.closest('.fi-modal-window')?.querySelector('.fi-modal-footer');
                                            if (! footer) return;
                                            const hasTime = !!(\$wire.mountedActions?.[0]?.data?.title ?? null);
                                            footer.style.display = hasTime ? 'flex' : 'none';
                                        ",
                                    ])
                            ->columnSpanFull()
                            ->schema([
                                TextEntry::make('title')
                                    ->label('Título')
                                    ->weight(FontWeight::SemiBold)
                                    ->columnSpanFull()
                                    ->placeholder('Sin título'),
                                TextEntry::make('taxista.name')
                                    ->label('Taxista')
                                    ->placeholder('Sin asignar'),
                                TextEntry::make('department.name')
                                    ->label('Departamento')
                                    ->placeholder('Sin departamento'),
                                TextEntry::make('tipo.nombre')
                                    ->label('Tipo de cita')
                                    ->placeholder('Sin tipo'),
                                TextEntry::make('status')
                                    ->label('Estado')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'pendiente' => 'Pendiente',
                                        'confirmada' => 'Confirmada',
                                        'cancelada' => 'Cancelada',
                                        default => 'Sin definir',
                                    }),
                                TextEntry::make('createdBy.name')
                                    ->label('Creado por')
                                    ->placeholder('Sin información'),
                                TextEntry::make('starts_at')
                                    ->label('Inicio')
                                    ->formatStateUsing(fn (mixed $state, TaxistaAppointment $record): string => $record->starts_at?->format('d/m/Y H:i') ?? '-'),
                                TextEntry::make('ends_at')
                                    ->label('Fin')
                                    ->formatStateUsing(fn (mixed $state, TaxistaAppointment $record): string => $record->ends_at?->format('d/m/Y H:i') ?? '-'),
                                TextEntry::make('notes')
                                    ->label('Notas')
                                    ->placeholder('Sin notas')
                                    ->columnSpanFull(),
                                ButtonAction::make('cancel')
                                            ->submit(
                                                'cancel'
                                            ),
                            ]),
                    ]),
            ]);
    }
}
