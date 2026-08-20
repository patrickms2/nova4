<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class BaseAdminModel extends Model
{
    public const READY_STATUSES = ["active", "available", "confirmed", "paid", "verified", "completed", "issued"];
    public const ATTENTION_STATUSES = ["draft", "pending", "reserved", "in_progress", "paid_pending_provider_confirmation"];
    public const RISK_STATUSES = ["cancelled", "unavailable", "failed", "overdue", "refunded", "low_stock"];

    protected $guarded = ["id"];

    protected string $adminLabelColumn = "name";

    public $timestamps = true;

    protected $dateFormat = "Y-m-d H:i:s";

    public function scopeWithStatus(Builder $query, string|array|null $status): Builder
    {
        if ($status === null || $status === [] || $status === "") {
            return $query;
        }

        $statuses = is_array($status) ? $status : [$status];

        return $query->whereIn($this->qualifyColumn("status"), array_values(array_filter($statuses, fn ($value) => $value !== null && $value !== "")));
    }

    public function scopeReady(Builder $query): Builder
    {
        return $query->whereIn($this->qualifyColumn("status"), self::READY_STATUSES);
    }

    public function scopeNeedsAttention(Builder $query): Builder
    {
        return $query->whereIn($this->qualifyColumn("status"), self::ATTENTION_STATUSES);
    }

    public function scopeAtRisk(Builder $query): Builder
    {
        return $query->whereIn($this->qualifyColumn("status"), self::RISK_STATUSES);
    }

    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where($this->qualifyColumn("updated_at"), ">=", now("UTC")->subDays(max(1, $days))->toDateTimeString());
    }

    public function scopeSearchLike(Builder $query, string $term, array $columns): Builder
    {
        $term = trim($term);
        $columns = array_values(array_filter($columns));

        if ($term === "" || $columns === []) {
            return $query;
        }

        return $query->where(function (Builder $subQuery) use ($columns, $term) {
            foreach ($columns as $column) {
                $subQuery->orWhere($this->qualifyColumn((string) $column), "like", "%" . $term . "%");
            }
        });
    }

    public function adminLabel(): string
    {
        $value = $this->getAttribute($this->adminLabelColumn)
            ?? $this->getAttribute("reference")
            ?? $this->getAttribute("invoice_number")
            ?? $this->getAttribute("customer_name")
            ?? $this->getAttribute("email");

        return $value !== null && $value !== "" ? (string) $value : "#" . (string) $this->getKey();
    }

    public function getAdminLabelAttribute(): string
    {
        return $this->adminLabel();
    }
}
