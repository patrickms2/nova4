<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Builder;
class AdminAuditLog extends BaseAdminModel
{
    protected $table = "admin_audit_log";

    protected string $adminLabelColumn = "summary";

    protected $fillable = [
        "actor_name",
        "actor_role",
        "action",
        "resource",
        "table_name",
        "record_id",
        "summary",
        "ip_hash",
        "user_agent",
    ];

    protected $casts = [
            "record_id" => "integer",
            "created_at" => "datetime",
            "updated_at" => "datetime",
        ];
    public function scopeForResource(Builder $query, string $resource): Builder
    {
        return $query->where($this->qualifyColumn("resource"), $resource);
    }
}
