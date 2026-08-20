<?php

declare(strict_types=1);

namespace Novagestion\OrderApi\Api;

interface CreateOrderInterface
{
    /**
     * Create a Lanzaloe order from NovaGestion bundle data.
     *
     * @param  string  $sku  Product SKU to add to the order
     * @param  float  $qty  Quantity to order
     * @param  mixed[]  $customer  Customer data (email, firstname, lastname, phone, address, city, postcode, country_id)
     * @param  string  $shippingMethod  Shipping method code (e.g. amstrates7)
     * @param  string  $shippingCarrier  Shipping carrier code (e.g. amstrates)
     * @param  string  $paymentMethod  Payment method code (e.g. banktransfer)
     * @return mixed[]
     */
    public function execute(
        string $sku,
        float $qty,
        array $customer,
        string $shippingMethod,
        string $shippingCarrier,
        string $paymentMethod
    ): array;
}
