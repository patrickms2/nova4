<?php

declare(strict_types=1);

namespace App\Services\Nova;

use App\Models\Server;
use Illuminate\Support\Facades\Log;

final readonly class NovaLanzaloePurchaseService
{
    public function __construct(
        private NovaMcpClient $mcpClient,
    ) {}

    /**
     * Create a shopping cart for Lanzaloe products
     */
    public function createCart(): array
    {
        $server = $this->getLanzaloeServer();
        if (! $server) {
            return ['success' => false, 'error' => 'Lanzaloe MCP server not found'];
        }

        try {
            $result = $this->mcpClient->setServer($server)->callJsonRpcTool('create_cart', [
                'store_id' => 1,
            ]);

            return [
                'success' => true,
                'cart_id' => $result['cart_id'] ?? null,
                'message' => $result['message'] ?? 'Cart created',
            ];
        } catch (\Throwable $exception) {
            Log::error('Lanzaloe create cart failed', ['error' => $exception->getMessage()]);

            return ['success' => false, 'error' => $exception->getMessage()];
        }
    }

    /**
     * Add product to cart
     */
    public function addToCart(string $cartId, string $sku, int $quantity = 1): array
    {
        $server = $this->getLanzaloeServer();
        if (! $server) {
            return ['success' => false, 'error' => 'Lanzaloe MCP server not found'];
        }

        try {
            $result = $this->mcpClient->setServer($server)->callJsonRpcTool('add_to_cart', [
                'cart_id' => $cartId,
                'sku' => $sku,
                'quantity' => $quantity,
                'store_id' => 1,
            ]);

            return [
                'success' => true,
                'item_id' => $result['item_id'] ?? null,
                'message' => $result['message'] ?? 'Product added to cart',
            ];
        } catch (\Throwable $exception) {
            Log::error('Lanzaloe add to cart failed', [
                'cart_id' => $cartId,
                'sku' => $sku,
                'error' => $exception->getMessage(),
            ]);

            return ['success' => false, 'error' => $exception->getMessage()];
        }
    }

    /**
     * Get cart contents
     */
    public function getCart(string $cartId): array
    {
        $server = $this->getLanzaloeServer();
        if (! $server) {
            return ['success' => false, 'error' => 'Lanzaloe MCP server not found'];
        }

        try {
            $result = $this->mcpClient->setServer($server)->callJsonRpcTool('get_cart', [
                'cart_id' => $cartId,
            ]);

            return [
                'success' => true,
                'cart' => $result,
            ];
        } catch (\Throwable $exception) {
            Log::error('Lanzaloe get cart failed', [
                'cart_id' => $cartId,
                'error' => $exception->getMessage(),
            ]);

            return ['success' => false, 'error' => $exception->getMessage()];
        }
    }

    /**
     * Get available shipping methods
     */
    public function getShippingMethods(string $cartId): array
    {
        $server = $this->getLanzaloeServer();
        if (! $server) {
            return ['success' => false, 'error' => 'Lanzaloe MCP server not found'];
        }

        try {
            $result = $this->mcpClient->setServer($server)->callJsonRpcTool('get_shipping_methods', [
                'cart_id' => $cartId,
            ]);

            return [
                'success' => true,
                'methods' => $result,
            ];
        } catch (\Throwable $exception) {
            Log::error('Lanzaloe get shipping methods failed', [
                'cart_id' => $cartId,
                'error' => $exception->getMessage(),
            ]);

            return ['success' => false, 'error' => $exception->getMessage()];
        }
    }

    /**
     * Get available payment methods
     */
    public function getPaymentMethods(string $cartId): array
    {
        $server = $this->getLanzaloeServer();
        if (! $server) {
            return ['success' => false, 'error' => 'Lanzaloe MCP server not found'];
        }

        try {
            $result = $this->mcpClient->setServer($server)->callJsonRpcTool('get_payment_methods', [
                'cart_id' => $cartId,
            ]);

            return [
                'success' => true,
                'methods' => $result,
            ];
        } catch (\Throwable $exception) {
            Log::error('Lanzaloe get payment methods failed', [
                'cart_id' => $cartId,
                'error' => $exception->getMessage(),
            ]);

            return ['success' => false, 'error' => $exception->getMessage()];
        }
    }

    /**
     * Create order from cart
     */
    public function createOrder(string $cartId, array $customerData, string $shippingMethod, string $paymentMethod): array
    {
        $server = $this->getLanzaloeServer();
        if (! $server) {
            return ['success' => false, 'error' => 'Lanzaloe MCP server not found'];
        }

        try {
            $result = $this->mcpClient->setServer($server)->callJsonRpcTool('create_order', [
                'cart_id' => $cartId,
                'email' => $customerData['email'],
                'firstname' => $customerData['firstname'],
                'lastname' => $customerData['lastname'],
                'street' => $customerData['street'] ?? [''],
                'city' => $customerData['city'] ?? '',
                'postcode' => $customerData['postcode'] ?? '',
                'country_id' => $customerData['country_id'] ?? 'ES',
                'telephone' => $customerData['telephone'] ?? '',
                'region_id' => $customerData['region_id'] ?? null,
                'region' => $customerData['region'] ?? null,
                'shipping_method' => $shippingMethod,
                'payment_method' => $paymentMethod,
            ]);

            return [
                'success' => true,
                'order_id' => $result['order_id'] ?? null,
                'message' => $result['message'] ?? 'Order created',
            ];
        } catch (\Throwable $exception) {
            Log::error('Lanzaloe create order failed', [
                'cart_id' => $cartId,
                'error' => $exception->getMessage(),
            ]);

            return ['success' => false, 'error' => $exception->getMessage()];
        }
    }

    /**
     * Get Lanzaloe MCP server
     */
    private function getLanzaloeServer(): ?Server
    {
        return Server::query()
            ->where('name', 'like', '%magento%')
            ->orWhere('name', 'like', '%lanzaloe%')
            ->where('status', 'active')
            ->first();
    }
}
