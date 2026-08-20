<?php

namespace App\Filament\App\Resources\Attendances;

use App\Filament\App\Resources\Attendances\Fields\AttendanceDetails;
use Filament\Schemas\Schema as Form;

class FormSchema
{
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                ...AttendanceDetails::make(),
            ]);
    }

    /**
     * Original form content from generated resource:
     * You can use this as reference or replace the schema above
     */
    public static function originalForm(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }
}
