<?php

declare(strict_types=1);

namespace App\Repositories;

interface CouponRepositoryInterface
{
    public function activeCoupons(): array;

    public function activeCouponsForCustomer(int $customerId): array;

    public function findActiveByCode(string $code): ?array;

    public function hasCustomerUsedCoupon(int $customerId, string $code): bool;
}
