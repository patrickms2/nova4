<?php

declare(strict_types=1);

namespace App\Services\Nova\Magento;

use Illuminate\Support\Facades\Log;

final class MagentoBulkOperation
{
    public function __construct(
        private readonly LaragentoWrapper $wrapper,
    ) {}

    /**
     * Sync multiple products via bulk operation
     */
    public function syncProducts(array $skus): array
    {
        try {
            $operations = array_map(fn ($sku) => [
                'topic_name' => 'catalog_product_update',
                'data' => ['sku' => $sku],
            ], $skus);

            $bulkUuid = $this->wrapper->createBulk($operations);

            return [
                'success' => true,
                'bulk_uuid' => $bulkUuid,
                'operation_count' => count($skus),
                'message' => 'Bulk operation created for '.count($skus).' products',
            ];
        } catch (\Throwable $exception) {
            Log::error('MagentoBulkOperation syncProducts failed', [
                'skus' => $skus,
                'error' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $exception->getMessage(),
            ];
        }
    }

    /**
     * Sync multiple orders via bulk operation
     */
    public function syncOrders(array $orderIds): array
    {
        try {
            $operations = array_map(fn ($orderId) => [
                'topic_name' => 'sales_order_update',
                'data' => ['order_id' => $orderId],
            ], $orderIds);

            $bulkUuid = $this->wrapper->createBulk($operations);

            return [
                'success' => true,
                'bulk_uuid' => $bulkUuid,
                'operation_count' => count($orderIds),
                'message' => 'Bulk operation created for '.count($orderIds).' orders',
            ];
        } catch (\Throwable $exception) {
            Log::error('MagentoBulkOperation syncOrders failed', [
                'order_ids' => $orderIds,
                'error' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $exception->getMessage(),
            ];
        }
    }

    /**
     * Check bulk operation status
     */
    public function checkStatus(string $bulkUuid): array
    {
        try {
            $status = $this->wrapper->getBulkStatus($bulkUuid);

            return [
                'success' => true,
                'bulk_uuid' => $bulkUuid,
                'status' => $status['status'] ?? 'unknown',
                'operation_count' => $status['operation_count'] ?? 0,
                'operations' => $status['operations'] ?? [],
            ];
        } catch (\Throwable $exception) {
            Log::error('MagentoBulkOperation checkStatus failed', [
                'bulk_uuid' => $bulkUuid,
                'error' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $exception->getMessage(),
            ];
        }
    }

    /**
     * Wait for bulk operation to complete
     */
    public function waitForCompletion(string $bulkUuid, int $timeoutSeconds = 300): array
    {
        $startTime = now();
        $checkInterval = 5; // Check every 5 seconds

        while (now()->diffInSeconds($startTime) < $timeoutSeconds) {
            $status = $this->checkStatus($bulkUuid);

            if (! $status['success']) {
                return $status;
            }

            if (in_array($status['status'], ['complete', 'completed'], true)) {
                return [
                    'success' => true,
                    'bulk_uuid' => $bulkUuid,
                    'status' => 'completed',
                    'message' => 'Bulk operation completed successfully',
                ];
            }

            if (in_array($status['status'], ['failed', 'error'], true)) {
                return [
                    'success' => false,
                    'bulk_uuid' => $bulkUuid,
                    'status' => 'failed',
                    'error' => 'Bulk operation failed',
                ];
            }

            sleep($checkInterval);
        }

        return [
            'success' => false,
            'bulk_uuid' => $bulkUuid,
            'status' => 'timeout',
            'error' => 'Bulk operation timed out',
        ];
    }
}
