<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Admin;
use App\Models\Super;
use App\Models\Taxista;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

class ActivityAccess
{
    public static function canViewAny(?Authenticatable $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user instanceof Admin || $user instanceof Super) {
            return true;
        }

        if ($user instanceof User) {
            return true;
        }

        if ($user instanceof Taxista) {
            return true;
        }

        return false;
    }

    public static function canViewActivity(Authenticatable $user, Activity $activity): bool
    {
        if ($user instanceof Admin || $user instanceof Super) {
            return true;
        }

        if ($user instanceof Taxista) {
            return self::matchesTaxista($user, $activity);
        }

        if ($user instanceof User) {
            if (self::matchesUser($user, $activity)) {
                return true;
            }

            if (self::isDepartmentManager($user) && $user->booking_department_id) {
                return self::matchesDepartmentUser($activity, (int) $user->booking_department_id);
            }
        }

        return false;
    }

    public static function scopeFor(Builder $query, Authenticatable $user): Builder
    {
        if ($user instanceof Admin || $user instanceof Super) {
            return $query;
        }

        if ($user instanceof Taxista) {
            return $query->where(function (Builder $builder) use ($user): void {
                $builder
                    ->where(function (Builder $inner) use ($user): void {
                        $inner->where('causer_type', Taxista::class)
                            ->where('causer_id', $user->getKey());
                    })
                    ->orWhere(function (Builder $inner) use ($user): void {
                        $inner->where('subject_type', Taxista::class)
                            ->where('subject_id', $user->getKey());
                    });
            });
        }

        if ($user instanceof User) {
            $query->where(function (Builder $builder) use ($user): void {
                $builder
                    ->where(function (Builder $inner) use ($user): void {
                        $inner->where('causer_type', User::class)
                            ->where('causer_id', $user->getKey());
                    })
                    ->orWhere(function (Builder $inner) use ($user): void {
                        $inner->where('subject_type', User::class)
                            ->where('subject_id', $user->getKey());
                    });

                if (self::isDepartmentManager($user) && $user->booking_department_id) {
                    $departmentId = (int) $user->booking_department_id;

                    $builder->orWhere(function (Builder $inner) use ($departmentId): void {
                        $inner->whereHasMorph(
                            'subject',
                            [User::class],
                            fn (Builder $subject) => $subject->where('booking_department_id', $departmentId),
                        )->orWhereHasMorph(
                            'causer',
                            [User::class],
                            fn (Builder $causer) => $causer->where('booking_department_id', $departmentId),
                        );
                    });
                }
            });
        }

        return $query;
    }

    private static function matchesUser(User $user, Activity $activity): bool
    {
        return ($activity->causer_type === User::class && (int) $activity->causer_id === (int) $user->getKey())
            || ($activity->subject_type === User::class && (int) $activity->subject_id === (int) $user->getKey());
    }

    private static function matchesTaxista(Taxista $user, Activity $activity): bool
    {
        return ($activity->causer_type === Taxista::class && (int) $activity->causer_id === (int) $user->getKey())
            || ($activity->subject_type === Taxista::class && (int) $activity->subject_id === (int) $user->getKey());
    }

    private static function matchesDepartmentUser(Activity $activity, int $departmentId): bool
    {
        if ($activity->subject_type === User::class && $activity->subject instanceof User) {
            if ((int) $activity->subject->booking_department_id === $departmentId) {
                return true;
            }
        }

        if ($activity->causer_type === User::class && $activity->causer instanceof User) {
            if ((int) $activity->causer->booking_department_id === $departmentId) {
                return true;
            }
        }

        return false;
    }

    private static function isDepartmentManager(User $user): bool
    {
        $role = strtolower(trim((string) ($user->role ?? '')));

        return in_array($role, ['manager', 'encargado'], true);
    }
}
