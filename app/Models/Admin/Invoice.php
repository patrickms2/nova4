<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Builder;
class Invoice extends BaseAdminModel
{
    protected $table = "admin_invoices";

    protected string $adminLabelColumn = "invoice_number";

    protected $fillable = [
        "invoice_number",
        "customer_name",
        "type",
        "status",
        "issued_at",
        "due_at",
        "subtotal",
        "tax_amount",
        "total_amount",
        "reference",
        "email",
        "notes",
    ];

    protected $casts = [
            "issued_at" => "date",
            "due_at" => "date",
            "subtotal" => "decimal:2",
            "tax_amount" => "decimal:2",
            "total_amount" => "decimal:2",
        ];
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn($this->qualifyColumn("status"), ["draft", "issued", "pending", "overdue"]);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn("status"), "overdue")->orWhere(function (Builder $subQuery) {
            $subQuery->whereNotIn($this->qualifyColumn("status"), ["paid", "completed", "cancelled", "refunded"])
                ->whereDate($this->qualifyColumn("due_at"), "<", now("UTC")->toDateString());
        });
    }
}
