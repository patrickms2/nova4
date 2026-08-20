<?php

namespace App\Filament\App\Pages;

use BackedEnum;
use Filament\Pages\Page;

class EmployeeProfile extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationLabel = 'Perfil empleado';

    protected static ?string $title = 'Perfil empleado';

    protected static ?string $slug = 'employee-profile';

    protected static string|\UnitEnum|null $navigationGroup = 'Servicios de Empleados';

    protected static ?int $navigationSort = 11;
    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.app.pages.employee-profile';

    public static function canAccess(): bool
    {
        $user = auth('web')->user();

        if (!$user) {
            return false;
        }

        $role = strtolower(trim((string)($user->role ?? '')));
        $isAdminRole = in_array($role, ['admin', 'super', 'super_admin', 'superadmin'], true);

        return (bool)($user->status ?? false)
            && ((bool)($user->is_employee ?? false) || $role === 'empleado' || $isAdminRole);
    }
}
