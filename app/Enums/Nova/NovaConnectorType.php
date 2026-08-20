<?php
declare(strict_types=1);

namespace App\Enums\Nova;

enum NovaConnectorType: string
{
    case Rest = 'rest';
    case GraphQl = 'graphql';
    case Mcp = 'mcp';
    case Webhook = 'webhook';
    case Database = 'database';
    case Queue = 'queue';
    case WhatsApp = 'whatsapp';
    case Magento = 'magento';
    case WooCommerce = 'woocommerce';
    case WordPress = 'wordpress';
    case LatePoint = 'latepoint';
    case Custom = 'custom';
}
