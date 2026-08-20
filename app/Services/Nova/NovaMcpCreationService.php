<?php

declare(strict_types=1);

namespace App\Services\Nova;

use App\Models\NovaBusiness;
use App\Models\NovaMagentoSyncLog;
use App\Models\Server;
use App\Models\NovaService;
use App\Services\Nova\Magento\LaragentoWrapper;
use App\Services\MCP\TaxilanzMCPServer;

use Illuminate\Support\Facades\Log;

final class NovaMcpCreationService
{
    public function __construct(
        private readonly NovaMcpClient $mcpClient,
    ) {}

    /**
     * Create LatePoint booking via MCP
     */
    public function createLatePointBooking(
        NovaBusiness $business,
        NovaService $service,
        array $bookingData
    ): array {
        $server = $this->getMcpServer($business, $service, 'latepoint');

        if ($server === null) {
            return [
                'success' => false,
                'error' => 'LatePoint MCP server not found',
            ];
        }

        $this->mcpClient->setServer($server);

        return $this->mcpClient->executeTool('create_booking', [
            'service_id' => $bookingData['service_id'] ?? null,
            'date' => $bookingData['date'] ?? null,
            'time' => $bookingData['time'] ?? null,
            'attendees' => $bookingData['attendees'] ?? 1,
            'customer_name' => $bookingData['customer_name'] ?? null,
            'customer_email' => $bookingData['customer_email'] ?? null,
            'customer_phone' => $bookingData['customer_phone'] ?? null,
            'notes' => $bookingData['notes'] ?? null,
        ]);
    }

    /**
     * Create WooCommerce order via MCP
     */
    public function createWooCommerceOrder(
        NovaBusiness $business,
        NovaService $service,
        array $orderData
    ): array {
        $server = $this->getMcpServer($business, $service, 'woocommerce');

        if ($server === null) {
            return [
                'success' => false,
                'error' => 'WooCommerce MCP server not found',
            ];
        }

        $this->mcpClient->setServer($server);

        return $this->mcpClient->executeTool('create_order', [
            'product_id' => $orderData['product_id'] ?? null,
            'quantity' => $orderData['quantity'] ?? 1,
            'customer_name' => $orderData['customer_name'] ?? null,
            'customer_email' => $orderData['customer_email'] ?? null,
            'customer_phone' => $orderData['customer_phone'] ?? null,
            'billing_address' => $orderData['billing_address'] ?? null,
            'shipping_address' => $orderData['shipping_address'] ?? null,
            'payment_method' => $orderData['payment_method'] ?? null,
            'notes' => $orderData['notes'] ?? null,
        ]);
    }

    /**
     * Create Magento order via LaragentoWrapper (instead of direct MCP)
     */
    public function createMagentoOrder(
        NovaBusiness $business,
        NovaService $service,
        array $orderData
    ): array {
        $server = $this->getMcpServer($business, $service, 'magento');

        if ($server === null) {
            return [
                'success' => false,
                'error' => 'Magento MCP server not found',
            ];
        }

        // Use LaragentoWrapper for Magento (abstraction layer)
        $credentials = $server->credentials ?? [];
        $baseUrl = data_get($credentials, 'base_url', data_get($credentials, 'endpoint_url', ''));
        $apiToken = data_get($credentials, 'api_token', data_get($credentials, 'token', ''));

        if ($baseUrl === '' || $apiToken === '') {
            return [
                'success' => false,
                'error' => 'Magento credentials missing (base_url or api_token)',
            ];
        }

        try {
            $wrapper = new LaragentoWrapper($baseUrl, $apiToken);

            $result = $wrapper->createOrder([
                'customer_email' => $orderData['customer_email'] ?? null,
                'customer_firstname' => $orderData['customer_firstname'] ?? $orderData['customer_name'] ?? null,
                'customer_lastname' => $orderData['customer_lastname'] ?? '',
                'items' => $orderData['items'] ?? [],
                'billing_address' => $orderData['billing_address'] ?? [],
                'shipping_address' => $orderData['shipping_address'] ?? [],
                'payment_method' => $orderData['payment_method'] ?? 'checkmo',
                'shipping_method' => $orderData['shipping_method'] ?? 'flatrate_flatrate',
            ]);

            // Log the operation
            if ($result['success']) {
                NovaMagentoSyncLog::create([
                    'bulk_uuid' => null,
                    'operation_key' => null,
                    'status' => 'completed',
                    'error_message' => null,
                    'nova_external_order_id' => null,
                    'magento_order_id' => $result['order_id'] ?? null,
                    'operation_type' => 'order_create',
                    'metadata' => $result,
                ]);
            }

            return $result;
        } catch (\Throwable $exception) {
            Log::error('NovaMcpCreationService createMagentoOrder failed', [
                'business_id' => $business->id,
                'service_id' => $service->id,
                'error' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $exception->getMessage(),
            ];
        }
    }

    /**
     * Create Sirvo reservation via MCP
     */
    public function createSirvoReservation(
        NovaBusiness $business,
        NovaService $service,
        array $reservationData
    ): array {
        $server = $this->getMcpServer($business, $service, 'sirvo');

        if ($server === null) {
            return [
                'success' => false,
                'error' => 'Sirvo MCP server not found',
            ];
        }

        $this->mcpClient->setServer($server);

        return $this->mcpClient->executeTool('create_reservation', [
            'restaurant_id' => $reservationData['restaurant_id'] ?? null,
            'date' => $reservationData['date'] ?? null,
            'time' => $reservationData['time'] ?? null,
            'guests' => $reservationData['guests'] ?? 1,
            'customer_name' => $reservationData['customer_name'] ?? null,
            'customer_phone' => $reservationData['customer_phone'] ?? null,
            'customer_email' => $reservationData['customer_email'] ?? null,
            'notes' => $reservationData['notes'] ?? null,
        ]);
    }

    /**
     * Get MCP server for business, service, and type
     */
    private function getMcpServer(
        NovaBusiness $business,
        NovaService $service,
        string $type
    ): ?Server {
        return Server::query()
            ->where('nova_business_id', $business->id)
            ->where('nova_service_id', $service->id)
            ->where('type', $type)
            ->where('status', 'active')
            ->latest('last_checked_at')
            ->first();
    }

    /**
     * Check if MCP server is available for creation
     */
    public function isAvailableForCreation(
        NovaBusiness $business,
        NovaService $service,
        string $type
    ): bool {
        $server = $this->getMcpServer($business, $service, $type);

        if ($server === null) {
            return false;
        }

        $this->mcpClient->setServer($server);

        return $this->mcpClient->healthCheck();
    }
}
