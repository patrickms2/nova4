<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Widgets\OptimizedConfirmedAppointmentsWidget;
use App\Filament\App\Widgets\OptimizedOpenTicketsWidget;
use App\Filament\App\Widgets\OptimizedPendingAppointmentsWidget;
use App\Filament\App\Widgets\OptimizedStatsOverviewWidget;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class OptimizedDashboard extends BaseDashboard
{
    use BaseDashboard\Concerns\HasFiltersForm;

    protected static ?string $title = '';

    // Dashboard optimizado para rendimiento
    protected static bool $isDiscovered = false;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-pie';

    public function getWidgets(): array
    {
        return [
            OptimizedStatsOverviewWidget::class,
            OptimizedConfirmedAppointmentsWidget::class,
            OptimizedPendingAppointmentsWidget::class,
            OptimizedOpenTicketsWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 3; // Mantener layout de 3 columnas
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false; // No mostrar en navegación, usar como reemplazo
    }

    public static function getNavigationLabel(): string
    {
        return 'Dashboard Optimizado';
    }

    public static function getNavigationBadge(): ?string
    {
        return Str::ucfirst(Auth::user()->name) ?? null;
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-rocket'; // Icono diferente para indicar que es optimizado
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
