<?php

declare(strict_types=1);

namespace Novagestion\OrderApi\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\AddressInterfaceFactory;
use Magento\Quote\Api\GuestCartRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Novagestion\OrderApi\Api\CreateOrderInterface;

final class CreateOrder implements CreateOrderInterface
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly CustomerFactory $customerFactory,
        private readonly CustomerRepository $customerRepository,
        private readonly CartManagementInterface $cartManagement,
        private readonly CartRepositoryInterface $cartRepository,
        private readonly GuestCartRepositoryInterface $guestCartRepository,
        private readonly AddressInterfaceFactory $addressFactory,
        private readonly OrderRepositoryInterface $orderRepository,
    ) {}

    public function execute(
        string $sku,
        float $qty,
        array $customer,
        string $shippingMethod,
        string $shippingCarrier,
        string $paymentMethod
    ): array {
        try {
            $storeId = 1;
            $websiteId = 1;

            // Find or create customer
            $customerEmail = $customer['email'] ?? '';
            try {
                $customerModel = $this->customerRepository->get($customerEmail, $websiteId);
                $customerId = (int) $customerModel->getId();
            } catch (LocalizedException) {
                $customerModel = $this->customerFactory->create();
                $customerModel->setWebsiteId($websiteId);
                $customerModel->setStoreId($storeId);
                $customerModel->setEmail($customerEmail);
                $customerModel->setFirstname($customer['firstname'] ?? 'Nova');
                $customerModel->setLastname($customer['lastname'] ?? 'Customer');
                $customerModel->save();
                $customerId = (int) $customerModel->getId();
            }

            // Create cart for the customer
            $cartId = $this->cartManagement->createEmptyCartForCustomer($customerId);
            $quote = $this->cartRepository->get($cartId);

            // Add product
            $product = $this->productRepository->get($sku);
            $quote->addProduct($product, $qty);
            $quote->collectTotals();

            // Set addresses
            $address = $this->buildAddress($customer);
            $quote->setBillingAddress($address);
            $quote->setShippingAddress($address);

            // Collect shipping rates and set method
            $quote->getShippingAddress()->setCollectShippingRates(true);
            $quote->getShippingAddress()->collectShippingRates();
            $quote->getShippingAddress()->setShippingMethod($shippingCarrier.'_'.$shippingMethod);

            // Set payment method
            $quote->getPayment()->setMethod($paymentMethod);
            $quote->setPaymentMethod($paymentMethod);

            // Save quote and place order
            $this->cartRepository->save($quote);
            $orderId = $this->cartManagement->placeOrder($cartId);

            $order = $this->orderRepository->get($orderId);

            return [
                'success' => true,
                'order_id' => $order->getIncrementId(),
                'order_entity_id' => $order->getId(),
                'customer_id' => $customerId,
                'grand_total' => (float) $order->getGrandTotal(),
                'message' => 'Order created successfully',
            ];
        } catch (\Throwable $exception) {
            return [
                'success' => false,
                'error' => $exception->getMessage(),
            ];
        }
    }

    private function buildAddress(array $customer): AddressInterface
    {
        $street = $customer['street'] ?? [$customer['address'] ?? 'Nova Address'];
        if (! is_array($street)) {
            $street = [$street];
        }

        /** @var AddressInterface $address */
        $address = $this->addressFactory->create();
        $address->setEmail($customer['email'] ?? '');
        $address->setFirstname($customer['firstname'] ?? 'Nova');
        $address->setLastname($customer['lastname'] ?? 'Customer');
        $address->setTelephone($customer['telephone'] ?? $customer['phone'] ?? '000000000');
        $address->setStreet($street);
        $address->setCity($customer['city'] ?? 'Arrecife');
        $address->setPostcode($customer['postcode'] ?? '35500');
        $address->setCountryId($customer['country_id'] ?? 'ES');
        $address->setRegionCode($customer['region_code'] ?? 'Las Palmas');
        $address->setCompany($customer['company'] ?? '');

        return $address;
    }
}
