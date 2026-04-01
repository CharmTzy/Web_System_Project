<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CouponRepository;
use RuntimeException;

final class CheckoutCouponService
{
    public function __construct(private readonly CouponRepository $couponRepository)
    {
    }

    public function checkoutOptions(array $summary): array
    {
        return array_map(
            fn (array $coupon): array => $this->presentCouponForCheckout($coupon, $summary),
            $this->couponRepository->activeCoupons()
        );
    }

    public function applyCoupon(array $summary, ?string $couponCode): array
    {
        $normalizedCode = strtoupper(trim((string) $couponCode));

        if ($normalizedCode === '') {
            return [
                'summary' => $summary + [
                    'discount_amount' => 0.0,
                    'discount_amount_formatted' => money(0),
                    'applied_coupon' => null,
                    'has_discount' => false,
                ],
                'coupon' => null,
            ];
        }

        $coupon = $this->couponRepository->findActiveByCode($normalizedCode);

        if ($coupon === null) {
            throw new RuntimeException('That coupon is not available right now.');
        }

        $evaluation = $this->evaluateCoupon($coupon, $summary);

        if (!$evaluation['is_eligible']) {
            throw new RuntimeException((string) $evaluation['message']);
        }

        $discountAmount = (float) $evaluation['discount_amount'];
        $baseGrandTotal = (float) ($summary['grand_total'] ?? 0.0);
        $adjustedGrandTotal = max(0, round($baseGrandTotal - $discountAmount, 2));
        $presentedCoupon = $this->presentCouponForCheckout($coupon, $summary) + [
            'discount_amount' => $discountAmount,
            'discount_amount_formatted' => money($discountAmount),
        ];

        return [
            'summary' => $summary + [
                'discount_amount' => $discountAmount,
                'discount_amount_formatted' => money($discountAmount),
                'applied_coupon' => $presentedCoupon,
                'has_discount' => $discountAmount > 0,
                'original_grand_total' => $baseGrandTotal,
                'original_grand_total_formatted' => money($baseGrandTotal),
                'grand_total' => $adjustedGrandTotal,
                'grand_total_formatted' => money($adjustedGrandTotal),
            ],
            'coupon' => $presentedCoupon,
        ];
    }

    private function presentCouponForCheckout(array $coupon, array $summary): array
    {
        $evaluation = $this->evaluateCoupon($coupon, $summary);

        return $coupon + [
            'code' => strtoupper((string) $coupon['code']),
            'discount_label' => $this->discountLabel($coupon),
            'scope_label' => $this->scopeLabel($coupon),
            'option_label' => sprintf(
                '%s — %s',
                strtoupper((string) $coupon['code']),
                $this->discountLabel($coupon)
            ),
            'minimum_spend_label' => $coupon['minimum_spend'] !== null
                ? 'Minimum spend ' . money((float) $coupon['minimum_spend'])
                : 'No minimum spend',
            'is_eligible' => (bool) $evaluation['is_eligible'],
            'eligibility_message' => (string) $evaluation['message'],
        ];
    }

    private function evaluateCoupon(array $coupon, array $summary): array
    {
        $eligibleSubtotal = $this->eligibleSubtotal($coupon, $summary);

        if ($eligibleSubtotal <= 0) {
            return [
                'is_eligible' => false,
                'discount_amount' => 0.0,
                'message' => $coupon['coupon_type'] === 'shop'
                    ? 'This coupon only applies to items from ' . ((string) ($coupon['seller_name'] ?? 'the selected shop')) . '.'
                    : 'This coupon does not match the items in your cart.',
            ];
        }

        $minimumSpend = $coupon['minimum_spend'] !== null ? (float) $coupon['minimum_spend'] : null;

        if ($minimumSpend !== null && $eligibleSubtotal < $minimumSpend) {
            return [
                'is_eligible' => false,
                'discount_amount' => 0.0,
                'message' => 'Spend at least ' . money($minimumSpend) . ' on eligible items to use this coupon.',
            ];
        }

        $shipping = (float) ($summary['shipping'] ?? 0.0);

        if ((string) $coupon['discount_type'] === 'shipping') {
            if ($shipping <= 0) {
                return [
                    'is_eligible' => false,
                    'discount_amount' => 0.0,
                    'message' => 'This coupon only applies when a shipping fee is charged.',
                ];
            }

            return [
                'is_eligible' => true,
                'discount_amount' => min($shipping, (float) ($summary['grand_total'] ?? 0.0)),
                'message' => '',
            ];
        }

        $discountAmount = match ((string) $coupon['discount_type']) {
            'percentage' => round($eligibleSubtotal * ((float) $coupon['discount_value'] / 100), 2),
            'fixed_amount' => min((float) $coupon['discount_value'], $eligibleSubtotal),
            default => 0.0,
        };

        if ($discountAmount <= 0) {
            return [
                'is_eligible' => false,
                'discount_amount' => 0.0,
                'message' => 'This coupon cannot be applied to the current cart.',
            ];
        }

        return [
            'is_eligible' => true,
            'discount_amount' => $discountAmount,
            'message' => '',
        ];
    }

    private function eligibleSubtotal(array $coupon, array $summary): float
    {
        if ((string) $coupon['coupon_type'] !== 'shop') {
            return (float) ($summary['subtotal'] ?? 0.0);
        }

        $sellerId = (int) ($coupon['seller_id'] ?? 0);

        return array_reduce(
            $summary['items'] ?? [],
            static function (float $carry, array $item) use ($sellerId): float {
                $itemSellerId = (int) (($item['product']['seller_id'] ?? 0));

                if ($itemSellerId !== $sellerId) {
                    return $carry;
                }

                return $carry + (float) ($item['line_total'] ?? 0);
            },
            0.0
        );
    }

    private function discountLabel(array $coupon): string
    {
        return match ((string) $coupon['discount_type']) {
            'percentage' => rtrim(rtrim(number_format((float) $coupon['discount_value'], 2), '0'), '.') . '% off',
            'fixed_amount' => money((float) $coupon['discount_value']) . ' off',
            'shipping' => 'Free shipping',
            default => 'Special offer',
        };
    }

    private function scopeLabel(array $coupon): string
    {
        return match ((string) $coupon['coupon_type']) {
            'shop' => 'Applies to ' . (string) ($coupon['seller_name'] ?? 'selected shop') . ' items',
            'free_shipping' => 'Applies to eligible delivery fees',
            default => 'Applies to eligible cart items',
        };
    }
}
