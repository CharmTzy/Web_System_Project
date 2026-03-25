<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AddressRepository;
use App\Repositories\OrderRepository;
use App\Repositories\PaymentCardRepository;
use App\Repositories\ProductRepository;
use RuntimeException;

final class CheckoutService
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly AddressRepository $addressRepository,
        private readonly PaymentCardRepository $paymentCardRepository,
        private readonly OrderRepository $orderRepository,
        private readonly ProductRepository $productRepository,
    ) {
    }

    public function checkout(int $userId, int $addressId, int $paymentCardId): array
    {
        $summary = $this->cartService->summary();

        if (!empty($summary['is_empty'])) {
            throw new RuntimeException('Your cart is empty.');
        }

        $address = $this->addressRepository->findById($addressId);
        if ($address === null || $address['user_id'] !== $userId) {
            throw new RuntimeException('Select a valid shipping address.');
        }

        $paymentCard = $this->paymentCardRepository->findById($paymentCardId);
        if ($paymentCard === null || $paymentCard['user_id'] !== $userId) {
            throw new RuntimeException('Select a valid payment method.');
        }

        $items = array_map(
            static fn (array $item): array => [
                'product_id' => (int) $item['product_id'],
                'product_name' => (string) $item['product']['name'],
                'quantity' => (int) $item['quantity'],
                'unit_price' => (float) $item['product']['price'],
                'line_total' => (float) $item['line_total'],
            ],
            $summary['items']
        );

        return $this->orderRepository->transaction(function () use ($userId, $address, $paymentCard, $summary, $items) {
            foreach ($items as $item) {
                $this->productRepository->reduceStock($item['product_id'], $item['quantity']);
            }

            $orderId = $this->orderRepository->create([
                'customer_id' => $userId,
                'order_number' => $this->generateOrderNumber(),
                'status' => 'paid',
                'shipping_recipient' => $address['recipient'],
                'shipping_line_1' => $address['line_1'],
                'shipping_line_2' => $address['line_2'],
                'shipping_city' => $address['city'],
                'shipping_state' => $address['state'],
                'shipping_postal_code' => $address['postal_code'],
                'shipping_country' => $address['country'],
                'shipping_phone' => $address['phone'],
                'payment_card_brand' => $paymentCard['card_brand'],
                'payment_card_last_four' => $paymentCard['card_last_four'],
                'subtotal' => $summary['subtotal'],
                'shipping_fee' => $summary['shipping'],
                'total' => $summary['grand_total'],
            ], $items);

            $this->cartService->clear();

            return $this->orderRepository->findByIdForCustomer($orderId, $userId)
                ?? throw new RuntimeException('The order was placed, but we could not load it afterward.');
        });
    }

    private function generateOrderNumber(): string
    {
        return 'NM-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    }
}
