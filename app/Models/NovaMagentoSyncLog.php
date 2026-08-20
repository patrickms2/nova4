<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NovaMagentoSyncLog extends Model
{
    protected $fillable = [
        'bulk_uuid',
        'operation_key',
        'status',
        'error_message',
        'nova_external_order_id',
        'magento_order_id',
        'operation_type',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function externalOrder()
    {
        return $this->belongsTo(NovaExternalOrder::class, 'nova_external_order_id');
    }
}
