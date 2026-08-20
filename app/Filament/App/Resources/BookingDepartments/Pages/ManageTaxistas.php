<?php

namespace App\Filament\App\Resources\BookingDepartments\Pages;

use App\Filament\App\Resources\BookingDepartments\BookingDepartmentResource;
use App\Filament\App\Resources\Taxistas\Tables\TaxistasTable;
use App\Services\Taxistas\TaxistaDocumentMultipageImporter;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;

class ManageTaxistas extends ManageRelatedRecords
{
    protected static string $resource = BookingDepartmentResource::class;

    protected static string $relationship = 'taxistas';

    protected static ?string $navigationLabel = 'Taxistas';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 4;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nueva Taxista')
                ->icon('heroicon-o-plus')
                ->color('danger')
                ->fillForm(fn(): array => [
                    'status' => 1,
                ])
                ->mutateFormDataUsing(function (array $data): array {
                    $data['status'] = $data['status'] ?? 1;

                    return $data;
                }),
            \Filament\Actions\Action::make('help')
                ->label('Ayuda')
                ->icon('heroicon-o-question-mark-circle')
                ->color('gray')
                ->modalContent(fn(): string => view('components.employee-help-popup-content', ['page' => 'department-taxistas'])->render())
                ->modalHeading('Ayuda - Taxistas del Departamento')
                ->modalFooterActions([
                    \Filament\Actions\Action::make('close')
                        ->label('Entendido')
                        ->color('danger')
                        ->close(),
                ]),
        ];
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'Taxistas del Departamento';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Gestión de todos los taxistas del sistema.';
    }

    private function makeMultipageImportAction(string $name, string $label, string $documentType): Action
    {
        return Action::make($name)
            ->label($label)
            ->icon('heroicon-s-squares-plus')
            ->color('warning')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar')
            ->form([
                Toggle::make('skip_duplicates')
                    ->label('Omitir duplicados (por taxista + tipo + referencia)')
                    ->default(true),

                FileUpload::make('pdf')
                    ->label("PDF multipagina ({$label})")
                    ->disk('local')
                    ->directory('imports/taxista-documents/multipage')
                    ->acceptedFileTypes(['application/pdf'])
                    ->preserveFilenames()
                    ->previewable(false)
                    ->required()
                    ->reactive()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (mixed $state, \Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get) use ($documentType): void {
                        if ($state === null || is_string($state)) {
                            $pdfPath = is_string($state) && $state !== ''
                                ? storage_path('app/' . ltrim($state, '/'))
                                : null;
                        } else {
                            $pdfPath = method_exists($state, 'getPathname') ? $state->getPathname() : null;
                        }

                        if (!$pdfPath || !is_file($pdfPath)) {
                            return;
                        }

                        $importer = app(TaxistaDocumentMultipageImporter::class);
                        $summary = $importer->importFromPdfPath(
                            pdfPath: $pdfPath,
                            documentType: $documentType,
                            uploadedByUserId: auth()->id(),
                            skipDuplicates: (bool)$get('skip_duplicates'),
                            forcedTaxistaUserId: null,
                        );

                        $logMessage = $summary['log_path'] !== ''
                            ? "\n\nLog: storage/app/{$summary['log_path']}"
                            : '';

                        $set('notes', $summary['notes'] . $logMessage);

                        $notification = Notification::make()
                            ->title('Importacion multipagina completada')
                            ->body($summary['notes'] . $logMessage);

                        if ($summary['errors'] !== []) {
                            $notification->warning();
                        } else {
                            $notification->success();
                        }

                        $notification->send();
                    }),

                Textarea::make('notes')
                    ->label('Notas / incidencias')
                    ->rows(4)
                    ->dehydrated(false)
                    ->placeholder('Sube un PDF para procesar...'),
            ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public function table(Table $table): Table
    {
        return TaxistasTable::configure($table)
            ->relationship(null)
            ->query(\App\Models\Taxista::query()->with(['type', 'municipio'])->withCount([
                'taxis',
                'conductores',
                'appointments',
                'documents',
                'tickets',
            ]));
    }
}
