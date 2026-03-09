<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CatalogRepositoryInterface;
use InvalidArgumentException;
use RuntimeException;

final class CartService
{
    private const SESSION_KEY = 'shopping_cart';

    public function __construct(
        private readonly CatalogRepositoryInterface $repository,
        private readonly array $config
    ) {
    }

    public function summary(): array
    {
        $cart = $this->sessionCart();
        $products = $this->repository->findByIds(array_keys($cart));
        $items = [];
        $normalizedCart = [];

        foreach ($cart as $productId => $quantity) {
            $productId = (int) $productId;
            $quantity = (int) $quantity;

            if (!isset($products[$productId])) {
                continue;
            }

            $product = $products[$productId];

            if ($product['stock_quantity'] < 1) {
                continue;
            }

            $safeQuantity = max(1, min($quantity, $product['stock_quantity']));
            $lineTotal = $safeQuantity * $product['price'];

            $normalizedCart[$productId] = $safeQuantity;
            $items[] = [
                'product_id' => $productId,
                'quantity' => $safeQuantity,
                'line_total' => $lineTotal,
                'line_total_formatted' => money($lineTotal),
                'product' => $product,
            ];
        }

        $_SESSION[self::SESSION_KEY] = $normalizedCart;

        $subtotal = array_reduce(
            $items,
            static fn (float $carry, array $item): float => $carry + $item['line_total'],
            0.0
        );

        $shipping = 0.0;

        if ($subtotal > 0 && $subtotal < (float) $this->config['free_shipping_threshold']) {
            $shipping = (float) $this->config['flat_shipping_rate'];
        }

        return [
            'items' => $items,
            'unique_items' => count($items),
            'total_items' => array_sum(array_column($items, 'quantity')),
            'subtotal' => $subtotal,
            'subtotal_formatted' => money($subtotal),
            'shipping' => $shipping,
            'shipping_formatted' => $shipping > 0 ? money($shipping) : 'FREE',
            'grand_total' => $subtotal + $shipping,
            'grand_total_formatted' => money($subtotal + $shipping),
            'free_shipping_threshold' => (float) $this->config['free_shipping_threshold'],
            'is_empty' => $items === [],
        ];
    }

    public function add(int $productId, int $quantity): array
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1.');
        }

        $product = $this->repository->findById($productId);

        if ($product === null || !$product['is_active']) {
            throw new RuntimeException('The selected product is not available.');
        }

        if ($product['stock_quantity'] < 1) {
            throw new RuntimeException('This product is currently out of stock.');
        }

        $cart = $this->sessionCart();
        $currentQuantity = (int) ($cart[$productId] ?? 0);
        $updatedQuantity = min($product['stock_quantity'], $currentQuantity + $quantity);
        $cart[$productId] = $updatedQuantity;

        $_SESSION[self::SESSION_KEY] = $cart;

        $message = $updatedQuantity === $product['stock_quantity'] && ($currentQuantity + $quantity) > $updatedQuantity
            ? 'Cart updated to the maximum stock available.'
            : $product['name'] . ' added to cart.';

        return [
            'summary' => $this->summary(),
            'message' => $message,
        ];
    }

    public function update(int $productId, int $quantity): array
    {
        $product = $this->repository->findById($productId);

        if ($product === null || !$product['is_active']) {
            throw new RuntimeException('The selected product is no longer available.');
        }

        $cart = $this->sessionCart();

        if (!isset($cart[$productId])) {
            throw new RuntimeException('That product is not in the cart.');
        }

        if ($quantity < 1) {
            unset($cart[$productId]);
            $_SESSION[self::SESSION_KEY] = $cart;

            return [
                'summary' => $this->summary(),
                'message' => 'Item removed from cart.',
            ];
        }

        $cart[$productId] = min($quantity, $product['stock_quantity']);
        $_SESSION[self::SESSION_KEY] = $cart;

        return [
            'summary' => $this->summary(),
            'message' => 'Cart quantity updated.',
        ];
    }

    public function remove(int $productId): array
    {
        $cart = $this->sessionCart();

        if (!isset($cart[$productId])) {
            throw new RuntimeException('That product is not in the cart.');
        }

        unset($cart[$productId]);
        $_SESSION[self::SESSION_KEY] = $cart;

        return [
            'summary' => $this->summary(),
            'message' => 'Item removed from cart.',
        ];
    }

    private function sessionCart(): array
    {
        $cart = $_SESSION[self::SESSION_KEY] ?? [];

        if (!is_array($cart)) {
            return [];
        }

        $normalized = [];

        foreach ($cart as $productId => $quantity) {
            $productId = (int) $productId;
            $quantity = (int) $quantity;

            if ($productId < 1 || $quantity < 1) {
                continue;
            }

            $normalized[$productId] = $quantity;
        }

        return $normalized;
    }
}
