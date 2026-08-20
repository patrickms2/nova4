<?php

namespace App\Filament\App\Resources\Attendances\Fields;

use App\Enums\AttendanceCategory;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TimePicker;

class AttendanceDetails
{
    public static function make(): array
    {
        return [
            Section::make('')
                ->label('Detalles')
                ->columnSpanFull()
                ->schema([
                    Select::make('employee_id')
                        ->label('Empleado')
                        ->relationship('employee', 'name')
                        ->default(auth()->id())
                        ->required()
                        ->columns(2)
                        ->columnSpan(2),
                    TextInput::make('description')
                        ->columns(1)
                        ->columnSpan(1),
                    DatePicker::make('date')
                        ->label('Date')
                        ->native(false)
                        ->default(now())
                        ->required(),
                    TimePicker::make('startDate')
                        ->label('CheckIn')
                        ->native(false)
                        ->columnSpan(1)
                        ->date(false)
                        ->seconds(false)
                        ->time(true)
                        ->autofocus(true)
                        ->displayFormat('H:i')
                        ->default(fn() => now())
                        ->required(),
                    TimePicker::make('endDate')
                        ->label('CheckOut')
                        ->native(false)
                        ->columns(1)
                        ->date(false)
                        ->displayFormat('H:i')
                        ->seconds(false)
                        ->columnSpan(1)
                        ->time(true)
                        ->reactive(),
                    TextInput::make('duration')
                        ->label('Horas')
                        ->Numeric(true)
                        ->columns(1)
                        ->columnSpan(1)
                        ->visible(false),

                ])
                ->columns(3),
        ];
    }
}
