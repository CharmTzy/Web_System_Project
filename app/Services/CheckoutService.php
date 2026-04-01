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

        $items = $this->buildOrderItems($summary);
        $orderData = $this->buildOrderData($userId, $address, $summary);

        return $this->orderRepository->transaction(function () use ($userId, $items, $orderData) {
            foreach ($items as $item) {
                $this->productRepository->reduceStock($item['product_id'], $item['quantity']);
            }

            $orderId = $this->orderRepository->create([
                ...$orderData,
                'order_number' => $this->generateOrderNumber(),
            ], $items);

            $this->cartService->clear();

            return $this->orderRepository->findByIdForCustomer($orderId, $userId)
                ?? throw new RuntimeException('The order was placed, but we could not load it afterward.');
        });
    }

    public function createPendingOrder(int $userId, int $addressId, ?array $summaryOverride = null, ?string $couponCode = null): array
    {
        $summary = $summaryOverride ?? $this->cartService->summary();

        if (!empty($summary['is_empty'])) {
            throw new RuntimeException('Your cart is empty.');
        }

        $address = $this->addressRepository->findById($addressId);
        if ($address === null || $address['user_id'] !== $userId) {
            throw new RuntimeException('Select a valid shipping address.');
        }

        $items = $this->buildOrderItems($summary);
        $orderData = $this->buildOrderData($userId, $address, $summary, $couponCode);

        return $this->orderRepository->transaction(function () use ($userId, $items, $orderData) {
            $existingOrder = $this->orderRepository->findMatchingPendingOrderForCustomer($userId, $orderData, $items);

            if ($existingOrder !== null) {
                $this->orderRepository->deleteMatchingPendingOrdersForCustomerExcept($userId, (int) $existingOrder['id'], $orderData, $items);

                return $existingOrder;
            }

            $orderId = $this->orderRepository->create([
                ...$orderData,
                'order_number' => $this->generateOrderNumber(),
            ], $items);

            $createdOrder = $this->orderRepository->findByIdForCustomer($orderId, $userId)
                ?? throw new RuntimeException('The order was created, but we could not load it afterward.');

            $this->orderRepository->deleteMatchingPendingOrdersForCustomerExcept($userId, $orderId, $orderData, $items);

            return $createdOrder;
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
            $paidOrder = $this->orderRepository->findByOrderNumber($orderNumber) ?? $order;
            $this->orderRepository->deleteMatchingPendingOrdersForCustomerExcept(
                (int) $paidOrder['customer_id'],
                (int) $paidOrder['id'],
                $this->buildOrderDataFromOrder($paidOrder),
                $this->buildItemsFromOrder($paidOrder)
            );
            $this->cartRepository->clearActiveCartForUser((int) $order['customer_id']);

            return $paidOrder;
        });
    }

    public function attachStripeSessionId(string $orderNumber, string $stripeSessionId): void
    {
        $normalizedOrderNumber = trim($orderNumber);
        $normalizedSessionId = trim($stripeSessionId);

        if ($normalizedOrderNumber === '' || $normalizedSessionId === '') {
            return;
        }

        $this->orderRepository->attachStripeSessionId($normalizedOrderNumber, $normalizedSessionId);
    }

    public function reconcilePendingOrdersForCustomer(int $userId, string $stripeSecretKey, int $limit = 5): void
    {
        if ($userId < 1 || trim($stripeSecretKey) === '') {
            return;
        }

        foreach ($this->orderRepository->pendingOrdersWithStripeSessionForCustomer($userId, $limit) as $order) {
            $sessionId = trim((string) ($order['stripe_session_id'] ?? ''));
            $orderNumber = trim((string) ($order['order_number'] ?? ''));

            if ($sessionId === '' || $orderNumber === '') {
                continue;
            }

            try {
                $session = stripe_api_request(
                    'GET',
                    'checkout/sessions/' . rawurlencode($sessionId),
                    $stripeSecretKey
                );

                $sessionOrder = trim((string) ($session['metadata']['order_number'] ?? ''));
                $paymentStatus = (string) ($session['payment_status'] ?? '');

                if ($sessionOrder === $orderNumber && $paymentStatus === 'paid') {
                    $this->finalizePendingOrder($orderNumber);
                }
            } catch (\Throwable $exception) {
                report_exception($exception, 'checkout.reconcile-pending');
            }
        }
    }

    private function generateOrderNumber(): string
    {
        return 'NM-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    }

    private function buildOrderItems(array $summary): array
    {
        return array_map(
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
    }

    private function buildOrderData(int $userId, array $address, array $summary, ?string $couponCode = null): array
    {
        return [
            'customer_id' => $userId,
            'status' => 'pending',
            'coupon_code' => $couponCode !== null && trim($couponCode) !== '' ? strtoupper(trim($couponCode)) : null,
            'shipping_recipient' => $address['recipient'],
            'shipping_line_1' => $address['line_1'],
            'shipping_line_2' => $address['line_2'],
            'shipping_city' => $address['city'],
            'shipping_state' => $address['state'],
            'shipping_postal_code' => $address['postal_code'],
            'shipping_country' => $address['country'],
            'shipping_phone' => $address['phone'],
            'subtotal' => (float) $summary['subtotal'],
            'shipping_fee' => (float) $summary['shipping'],
            'total' => (float) $summary['grand_total'],
        ];
    }

    private function buildOrderDataFromOrder(array $order): array
    {
        return [
            'customer_id' => (int) $order['customer_id'],
            'status' => (string) $order['status'],
            'coupon_code' => $order['coupon_code'] ?? null,
            'shipping_recipient' => (string) $order['shipping_recipient'],
            'shipping_line_1' => (string) $order['shipping_line_1'],
            'shipping_line_2' => $order['shipping_line_2'],
            'shipping_city' => (string) $order['shipping_city'],
            'shipping_state' => (string) $order['shipping_state'],
            'shipping_postal_code' => (string) $order['shipping_postal_code'],
            'shipping_country' => (string) $order['shipping_country'],
            'shipping_phone' => $order['shipping_phone'],
            'subtotal' => (float) $order['subtotal'],
            'shipping_fee' => (float) $order['shipping_fee'],
            'total' => (float) $order['total'],
        ];
    }

    private function buildItemsFromOrder(array $order): array
    {
        return array_map(
            static fn (array $item): array => [
                'product_id' => (int) ($item['product_id'] ?? 0),
                'product_name' => (string) ($item['product_name'] ?? ''),
                'seller_id' => (int) ($item['seller_id'] ?? 0),
                'quantity' => (int) ($item['quantity'] ?? 0),
                'unit_price' => (float) ($item['unit_price'] ?? 0),
                'line_total' => (float) ($item['line_total'] ?? 0),
            ],
            $order['items'] ?? []
        );
    }
}
