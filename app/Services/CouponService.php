<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CouponRepositoryInterface;
use DateTimeImmutable;

final class CouponService
{
    public function __construct(
        private readonly CouponRepositoryInterface $repository,
        private readonly string $source
    ) {
    }

    public function browse(): array
    {
        $coupons = array_map(
            fn (array $coupon): array => $this->presentCoupon($coupon),
            $this->repository->activeCoupons()
        );

        $limitedTime = array_values(array_filter(
            $coupons,
            static fn (array $coupon): bool => $coupon['coupon_type'] === 'limited_time'
        ));
        $freeShipping = array_values(array_filter(
            $coupons,
            static fn (array $coupon): bool => $coupon['coupon_type'] === 'free_shipping'
        ));
        $shopCoupons = array_values(array_filter(
            $coupons,
            static fn (array $coupon): bool => $coupon['coupon_type'] === 'shop'
        ));

        $featured = array_values(array_filter(
            $coupons,
            static fn (array $coupon): bool => $coupon['is_featured']
        ));

        if ($featured === []) {
            $featured = array_slice($coupons, 0, 3);
        } else {
            $featured = array_slice($featured, 0, 3);
        }

        $endingSoon = count(array_filter(
            $coupons,
            static fn (array $coupon): bool => $coupon['days_left'] !== null && $coupon['days_left'] <= 7
        ));

        return [
            'featured' => $featured,
            'limited_time' => $limitedTime,
            'free_shipping' => $freeShipping,
            'shop_coupons' => $shopCoupons,
            'stats' => [
                'available_count' => count($coupons),
                'ending_soon_count' => $endingSoon,
                'shipping_count' => count($freeShipping),
            ],
            'source' => $this->source,
        ];
    }

    private function presentCoupon(array $coupon): array
    {
        $now = new DateTimeImmutable('now');
        $endsAt = new DateTimeImmutable((string) $coupon['ends_at']);
        $daysLeft = max(0, (int) $now->diff($endsAt)->format('%a'));

        return $coupon + [
            'accent' => $this->accent($coupon['coupon_type']),
            'type_label' => $this->typeLabel($coupon['coupon_type']),
            'discount_label' => $this->discountLabel($coupon),
            'scope_label' => $this->scopeLabel($coupon),
            'minimum_spend_label' => $coupon['minimum_spend'] !== null
                ? 'Min. spend ' . money((float) $coupon['minimum_spend'])
                : 'No minimum spend',
            'expiry_label' => $daysLeft === 0
                ? 'Ends today'
                : ($daysLeft === 1 ? '1 day left' : ($daysLeft <= 7 ? $daysLeft . ' days left' : 'Ends ' . $endsAt->format('d M Y'))),
            'validity_label' => sprintf(
                'Valid %s - %s',
                (new DateTimeImmutable((string) $coupon['starts_at']))->format('d M'),
                $endsAt->format('d M Y')
            ),
            'days_left' => $daysLeft,
        ];
    }

    private function accent(string $couponType): string
    {
        return match ($couponType) {
            'limited_time' => 'limited',
            'free_shipping' => 'shipping',
            'shop' => 'shop',
            default => 'neutral',
        };
    }

    private function typeLabel(string $couponType): string
    {
        return match ($couponType) {
            'limited_time' => 'Limited time',
            'free_shipping' => 'Free shipping',
            'shop' => 'Shop coupon',
            default => 'Coupon',
        };
    }

    private function discountLabel(array $coupon): string
    {
        return match ($coupon['discount_type']) {
            'percentage' => rtrim(rtrim(number_format((float) $coupon['discount_value'], 2), '0'), '.') . '% off',
            'fixed_amount' => money((float) $coupon['discount_value']) . ' off',
            'shipping' => 'Free shipping',
            default => 'Special offer',
        };
    }

    private function scopeLabel(array $coupon): string
    {
        return match ($coupon['coupon_type']) {
            'shop' => 'For shop: ' . (string) ($coupon['seller_name'] ?? 'Selected shop'),
            'free_shipping' => 'For eligible delivery orders',
            default => 'Across selected NovaMarket deals',
        };
    }
}
