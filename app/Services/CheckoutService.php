<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AddressRepository;
use App\Repositories\CartRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use RuntimeException;

final class CheckoutService
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly AddressRepository $addressRepository,
        private readonly OrderRepository $orderRepository,
        private readonly ProductRepository $productRepository,
        private readonly CartRepository $cartRepository,
    ) {
    }

    public function checkout(int $userId, int $addressId): array
    {
        $summary = $this->cartService->summary();

        if (!empty($summary['is_empty'])) {
            throw new RuntimeException('Your cart is empty.');
        }

        $address = $this->addressRepository->findById($addressId);
        if ($address === null || $address['user_id'] !== $userId) {
            throw new RuntimeException('Select a valid shipping address.');
        }

        $items = array_map(
            static fn (array $item): array => [
                'product_id' => (int) $item['product_id'],
                'product_name' => (string) $item['product']['name'],
                'seller_id' => (int) ($item['product']['seller_id'] ?? 0),
                'quantity' => (int) $item['quantity'],
                'unit_price' => (float) $item['product']['price'],
                'line_total' => (float) $item['line_total'],
            ],
            $summary['items']
        );

        return $this->orderRepository->transaction(function () use ($userId, $address, $summary, $items) {
            foreach ($items as $item) {
                $this->productRepository->reduceStock($item['product_id'], $item['quantity']);
            }

            $orderId = $this->orderRepository->create([
                'customer_id' => $userId,
                'order_number' => $this->generateOrderNumber(),
                'status' => 'pending',
                'shipping_recipient' => $address['recipient'],
                'shipping_line_1' => $address['line_1'],
                'shipping_line_2' => $address['line_2'],
                'shipping_city' => $address['city'],
                'shipping_state' => $address['state'],
                'shipping_postal_code' => $address['postal_code'],
                'shipping_country' => $address['country'],
                'shipping_phone' => $address['phone'],
                'subtotal' => $summary['subtotal'],
                'shipping_fee' => $summary['shipping'],
                'total' => $summary['grand_total'],
            ], $items);

            $this->cartService->clear();

            return $this->orderRepository->findByIdForCustomer($orderId, $userId)
                ?? throw new RuntimeException('The order was placed, but we could not load it afterward.');
        });
    }

    public function createPendingOrder(int $userId, int $addressId, ?array $summaryOverride = null): array
    {
        $summary = $summaryOverride ?? $this->cartService->summary();

        if (!empty($summary['is_empty'])) {
            throw new RuntimeException('Your cart is empty.');
        }

        $address = $this->addressRepository->findById($addressId);
        if ($address === null || $address['user_id'] !== $userId) {
            throw new RuntimeException('Select a valid shipping address.');
        }

        $items = array_map(
            static fn (array $item): array => [
                'product_id' => (int) $item['product_id'],
                'product_name' => (string) $item['product']['name'],
                'seller_id' => (int) ($item['product']['seller_id'] ?? 0),
                'quantity' => (int) $item['quantity'],
                'unit_price' => (float) $item['product']['price'],
                'line_total' => (float) $item['line_total'],
            ],
            $summary['items']
        );

        return $this->orderRepository->transaction(function () use ($userId, $address, $summary, $items) {
            $orderId = $this->orderRepository->create([
                'customer_id' => $userId,
                'order_number' => $this->generateOrderNumber(),
                'status' => 'pending',
                'shipping_recipient' => $address['recipient'],
                'shipping_line_1' => $address['line_1'],
                'shipping_line_2' => $address['line_2'],
                'shipping_city' => $address['city'],
                'shipping_state' => $address['state'],
                'shipping_postal_code' => $address['postal_code'],
                'shipping_country' => $address['country'],
                'shipping_phone' => $address['phone'],
                'subtotal' => $summary['subtotal'],
                'shipping_fee' => $summary['shipping'],
                'total' => $summary['grand_total'],
            ], $items);

            return $this->orderRepository->findByIdForCustomer($orderId, $userId)
                ?? throw new RuntimeException('The order was created, but we could not load it afterward.');
        });
    }

    public function finalizePendingOrder(string $orderNumber): ?array
    {
        return $this->orderRepository->transaction(function () use ($orderNumber) {
            $order = $this->orderRepository->findByOrderNumber($orderNumber);

            if ($order === null) {
                return null;
            }

            if ($order['status'] !== 'pending') {
                return $order;
            }

            foreach ($order['items'] as $item) {
                $this->productRepository->reduceStock((int) $item['product_id'], (int) $item['quantity']);
            }

            $this->orderRepository->markPaidByOrderNumber($orderNumber);
            $this->cartRepository->clearActiveCartForUser((int) $order['customer_id']);

            return $this->orderRepository->findByOrderNumber($orderNumber);
        });
    }

    private function generateOrderNumber(): string
    {
        return 'NM-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    }
}
