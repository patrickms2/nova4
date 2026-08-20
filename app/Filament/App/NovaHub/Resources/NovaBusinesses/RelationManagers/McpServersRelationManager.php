<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaBusinesses\RelationManagers;

use App\Filament\Resources\ServerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

final class McpServersRelationManager extends RelationManager
{
    protected static string $relationship = 'mcpServers';

    protected static ?string $title = 'MCP';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return ServerResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return ServerResource::table($table)
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        $data['nova_business_id'] = $this->getOwnerRecord()->id;

                        return $data;
                    }),
            ]);
    }
}
