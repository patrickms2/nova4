<?php

namespace App\Support;

use App\Models\Community;
use App\Models\Employee;
use App\Models\Person;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Support\Collection;

class CommunityPortalContext
{
    public static function person(?User $user = null): ?Person
    {
        $user ??= auth()->user();
        if (! $user) {
            return null;
        }

        return $user->person
            ?? Person::query()->where('email', $user->email)->first();
    }

    public static function employee(?User $user = null): ?Employee
    {
        $user ??= auth()->user();
        if (! $user) {
            return null;
        }

        return $user->employee
            ?? Employee::query()->where('email', $user->email)->first();
    }

    public static function isOwner(?User $user = null): bool
    {
        $person = static::person($user);
        $person->query()->with('roles','properties','comunnity_owners');

        return $person !== null && ($person->roles()->where('role', 'owner')->exists()
            || $person->communities()->wherePivot('role', 'owner')->exists());
    }

    public static function isEmployee(?User $user = null): bool
    {
        $user ??= auth()->user();

        return $user !== null && (static::employee($user) !== null
            || (bool) $user->is_employee
            || in_array(strtolower((string) $user->role), ['employee', 'empleado'], true));
    }

    public static function portalType(?User $user = null): string
    {
        if (static::isEmployee($user)) {
            return 'employee';
        }

        if (static::isOwner($user)) {
            return 'owner';
        }

        return 'taxista';
    }

    /** @return Collection<int, int> */
    public static function employeeCommunityIds(?User $user = null): Collection
    {
        $employee = static::employee($user);

        if (! $employee) {
            return collect();
        }

        $departments = $employee->communityDepartments()->get(['community_departments.id', 'community_departments.community_id']);

        if ($departments->contains(fn ($department): bool => $department->community_id === null)) {
            return Community::query()->pluck('id')->map(fn ($id): int => (int) $id);
        }

        return $departments->pluck('community_id')->filter()->map(fn ($id): int => (int) $id)->values();
    }

    public static function canAccessWorkOrder(WorkOrder $workOrder, ?User $user = null): bool
    {
        $user ??= auth()->user();
        if (! $user) {
            return false;
        }

        if (in_array(strtolower((string) $user->role), ['admin', 'super', 'administrator'], true)) {
            return true;
        }

        $employee = static::employee($user);
        if (! $employee) {
            return false;
        }

        return $workOrder->communityShifts()->where('employee_id', $employee->id)->exists()
            || static::employeeCommunityIds($user)->contains((int) $workOrder->community_id);
    }
}
