<?php

namespace App\Services;

use App\Models\Admin;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class PublicBookingRequestAssigner
{
    public function resolve(string $type, object $service): array
    {
        if (! Schema::hasTable('admins')) {
            return ['admin' => null, 'source' => 'unassigned'];
        }

        return $this->resolveFromCandidates(
            $type,
            $this->directAdminId($service),
            Admin::query()
                ->select(['id', 'name', 'role', 'section'])
                ->orderByRaw("role = 'super_admin' desc")
                ->orderBy('id')
                ->get()
        );
    }

    public function resolveFromCandidates(string $type, int|string|null $directAdminId, Collection $admins): array
    {
        if ($directAdminId) {
            $directAdmin = $admins->first(fn ($admin) => (int) $admin->id === (int) $directAdminId);

            if ($directAdmin) {
                return ['admin' => $directAdmin, 'source' => 'direct_manager'];
            }
        }

        $sectionManager = $admins->first(fn ($admin) => in_array($admin->role, ['admin', 'sub_admin'], true)
            && $admin->section === $type);

        if ($sectionManager) {
            return ['admin' => $sectionManager, 'source' => 'section_manager'];
        }

        $superAdmin = $admins->first(fn ($admin) => $admin->role === 'super_admin');

        if ($superAdmin) {
            return ['admin' => $superAdmin, 'source' => 'super_admin'];
        }

        return ['admin' => null, 'source' => 'unassigned'];
    }

    private function directAdminId(object $service): int|string|null
    {
        if (isset($service->admin_id)) {
            return $service->admin_id;
        }

        if (isset($service->manager_id)) {
            return $service->manager_id;
        }

        return null;
    }
}
