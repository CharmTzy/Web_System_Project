<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CouponRepository;
use App\Repositories\UserRepository;
use DateTimeImmutable;
use InvalidArgumentException;
use PDOException;
use RuntimeException;
use Throwable;

final class AdminCouponService
{
    public function __construct(
        private readonly CouponRepository $couponRepository,
        private readonly UserRepository $userRepository
    ) {
    }

    public function listCoupons(array $filters = []): array
    {
        $filters['search'] = trim((string) ($filters['search'] ?? ''));
        $filters['coupon_type'] = trim((string) ($filters['coupon_type'] ?? ''));

        return $this->couponRepository->listAll($filters);
    }

    public function getCoupon(int $id): ?array
    {
        return $this->couponRepository->findById($id);
    }

    public function sellerOptions(): array
    {
        $sellers = $this->userRepository->listAll(['role' => 'seller']);

        return array_map(function (array $seller): array {
            $profile = $this->userRepository->findSellerProfile((int) $seller['id']);

            return [
                'id' => (int) $seller['id'],
                'name' => (string) ($profile['store_name'] ?? $seller['name']),
            ];
        }, $sellers);
    }

    public function create(array $input): array
    {
        $data = $this->validate($input);

        try {
            $id = $this->couponRepository->create($data);
        } catch (PDOException $exception) {
            throw new RuntimeException('Coupon code must be unique.');
        }

        $coupon = $this->couponRepository->findById($id);

        if ($coupon === null) {
            throw new RuntimeException('Coupon could not be created.');
        }

        return $coupon;
    }

    public function update(int $id, array $input): array
    {
        $existing = $this->couponRepository->findById($id);

        if ($existing === null) {
            throw new RuntimeException('Coupon not found.');
        }

        $data = $this->validate($input, $existing);

        try {
            $this->couponRepository->update($id, $data);
        } catch (PDOException $exception) {
            throw new RuntimeException('Coupon code must be unique.');
        }

        return $this->couponRepository->findById($id) ?? $existing;
    }

    public function delete(int $id): void
    {
        $coupon = $this->couponRepository->findById($id);

        if ($coupon === null) {
            throw new RuntimeException('Coupon not found.');
        }

        $this->couponRepository->delete($id);
    }

    private function validate(array $input, ?array $existing = null): array
    {
        $code = strtoupper(trim((string) ($input['code'] ?? '')));
        $title = trim((string) ($input['title'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $couponType = trim((string) ($input['coupon_type'] ?? ''));
        $discountType = trim((string) ($input['discount_type'] ?? ''));
        $discountValue = is_numeric($input['discount_value'] ?? null) ? (float) $input['discount_value'] : null;
        $minimumSpend = trim((string) ($input['minimum_spend'] ?? ''));
        $sellerId = filter_var($input['seller_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $startsAt = trim((string) ($input['starts_at'] ?? ''));
        $endsAt = trim((string) ($input['ends_at'] ?? ''));
        $allowedTypes = ['limited_time', 'free_shipping', 'shop'];
        $allowedDiscounts = ['percentage', 'fixed_amount', 'shipping'];

        if ($code === '' || mb_strlen($code) > 40) {
            throw new InvalidArgumentException('Coupon code is required and must be 40 characters or less.');
        }

        if ($title === '') {
            throw new InvalidArgumentException('Coupon title is required.');
        }

        if ($description === '') {
            throw new InvalidArgumentException('Coupon description is required.');
        }

        if (!in_array($couponType, $allowedTypes, true)) {
            throw new InvalidArgumentException('Please select a valid coupon type.');
        }

        if (!in_array($discountType, $allowedDiscounts, true)) {
            throw new InvalidArgumentException('Please select a valid discount type.');
        }

        if ($couponType === 'free_shipping') {
            $discountType = 'shipping';
            $discountValue = 0;
        }

        if ($discountType !== 'shipping' && ($discountValue === null || $discountValue <= 0)) {
            throw new InvalidArgumentException('Discount value must be greater than 0.');
        }

        if ($couponType === 'shop' && $sellerId === false) {
            throw new InvalidArgumentException('Please select a seller for shop coupons.');
        }

        if ($couponType !== 'shop') {
            $sellerId = null;
        }

        if ($startsAt === '' || $endsAt === '') {
            throw new InvalidArgumentException('Start and end dates are required.');
        }

        $startsAtDate = $this->parseDateTime($startsAt, 'Please enter a valid start date.');
        $endsAtDate = $this->parseDateTime($endsAt, 'Please enter a valid end date.');

        if ($endsAtDate <= $startsAtDate) {
            throw new InvalidArgumentException('End date must be after the start date.');
        }

        $minimumSpendValue = $minimumSpend === '' ? null : (float) $minimumSpend;

        if ($minimumSpendValue !== null && $minimumSpendValue < 0) {
            throw new InvalidArgumentException('Minimum spend cannot be negative.');
        }

        return [
            'code' => $code,
            'title' => $title,
            'description' => $description,
            'coupon_type' => $couponType,
            'discount_type' => $discountType,
            'discount_value' => $discountType === 'shipping' ? 0 : $discountValue,
            'minimum_spend' => $minimumSpendValue,
            'seller_id' => $sellerId === false ? ($existing['seller_id'] ?? null) : $sellerId,
            'starts_at' => $startsAtDate->format('Y-m-d H:i:s'),
            'ends_at' => $endsAtDate->format('Y-m-d H:i:s'),
            'is_featured' => bool_from_input($input['is_featured'] ?? false) ? 1 : 0,
            'is_active' => bool_from_input($input['is_active'] ?? true) ? 1 : 0,
        ];
    }

    private function parseDateTime(string $value, string $message): DateTimeImmutable
    {
        try {
            $date = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $value)
                ?: new DateTimeImmutable($value);
        } catch (Throwable $exception) {
            throw new InvalidArgumentException($message);
        }

        return $date;
    }
}
