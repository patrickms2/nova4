<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\BookingDepartment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema as DbSchema;

class SupportAccess
{
    public static function canAccess(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if (! self::isEmployee($user)) {
            return false;
        }

        $department = $user->bookingDepartment;

        if (! $department) {
            return false;
        }

        return self::isSupportDepartment($department);
    }

    public static function isSupportDepartment(BookingDepartment $department): bool
    {
        $slug = strtolower(trim((string) ($department->slug ?? '')));
        $name = strtolower(trim((string) ($department->name ?? '')));

        if ($slug !== '' && str_contains($slug, 'soporte')) {
            return true;
        }

        if ($name !== '' && (str_contains($name, 'soporte') || str_contains($name, 'support'))) {
            return true;
        }

        if (DbSchema::hasColumn('booking_departments', 'is_support_department')) {
            return (bool) ($department->is_support_department ?? false);
        }

        return false;
    }

    public static function supportDepartmentId(): ?int
    {
        if (! DbSchema::hasTable('booking_departments')) {
            return null;
        }

        return BookingDepartment::query()
            ->when(DbSchema::hasColumn('booking_departments', 'is_support_department'), function (Builder $query): Builder {
                return $query->where('is_support_department', true);
            })
            ->when(DbSchema::hasColumn('booking_departments', 'has_tickets_service'), function (Builder $query): Builder {
                return $query->where('has_tickets_service', true);
            })
            ->where(function (Builder $query): void {
                $query->where('slug', 'soporte')
                    ->orWhere('slug', 'support')
                    ->orWhere('name', 'like', '%soporte%')
                    ->orWhere('name', 'like', '%support%');
            })
            ->orderBy('name')
            ->value('id');
    }

    private static function isEmployee(User $user): bool
    {
        $role = strtolower(trim((string) ($user->role ?? '')));

        return $role === 'empleado' || (bool) ($user->is_employee ?? false);
    }
}
