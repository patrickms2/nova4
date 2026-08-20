<?php

namespace App\Filament\App\NovaHub\Resources\Servicios\Servicios\RelationManagers;

use App\Filament\App\NovaHub\Resources\Servicios\Invoices\InvoiceResource;

use App\Models\Invoice;
use Filament\Forms;
use Filament\Schemas\Schema as Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;


class InvoicesRelationManager extends RelationManager
{
    /**
     * Nombre de la relación que queremos gestionar (debe coincidir con el método
     * de relación en el modelo `Usuario`).
     */
    protected static string $relationship = 'invoice';

    /**
     * Atributo de título para los registros.
     */
    protected static ?string $recordTitleAttribute = 'nombre';

    /**
     * Configura la tabla que mostrará las ubicaciones relacionadas.
     */

    public function form(Form $form): Form
    {
        return (new InvoiceResource())->form($form);
    }

    public function table(Table $table): Table
    {
        return (new InvoiceResource())->table($table);
    }


}
