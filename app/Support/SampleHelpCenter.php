<?php

declare(strict_types=1);

namespace App\Support;

final class SampleHelpCenter
{
    public static function categories(): array
    {
        return [
            [
                'id' => 1,
                'name' => 'Shop with NovaMarket',
                'slug' => 'shop-with-novamarket',
                'description' => 'Buying basics, gift options, and everyday marketplace support.',
                'icon_key' => 'shop',
                'sort_order' => 1,
            ],
            [
                'id' => 2,
                'name' => 'Deals & Rewards',
                'slug' => 'deals-rewards',
                'description' => 'Promo codes, vouchers, reward points, and campaign deals.',
                'icon_key' => 'tag',
                'sort_order' => 2,
            ],
            [
                'id' => 3,
                'name' => 'Payments',
                'slug' => 'payments',
                'description' => 'Cards, wallet payments, billing checks, and failed checkouts.',
                'icon_key' => 'wallet',
                'sort_order' => 3,
            ],
            [
                'id' => 4,
                'name' => 'Orders & Shipping',
                'slug' => 'orders-shipping',
                'description' => 'Order tracking, delivery timing, address edits, and shipping issues.',
                'icon_key' => 'truck',
                'sort_order' => 4,
            ],
            [
                'id' => 5,
                'name' => 'Returns & Refunds',
                'slug' => 'returns-refunds',
                'description' => 'Return requests, refund timing, and damaged-item support.',
                'icon_key' => 'refresh',
                'sort_order' => 5,
            ],
            [
                'id' => 6,
                'name' => 'Account & Security',
                'slug' => 'account-security',
                'description' => 'Passwords, phone numbers, sign-up issues, and login safety.',
                'icon_key' => 'shield',
                'sort_order' => 6,
            ],
            [
                'id' => 7,
                'name' => 'Policies',
                'slug' => 'policies',
                'description' => 'Store rules, return eligibility, and marketplace policies.',
                'icon_key' => 'document',
                'sort_order' => 7,
            ],
            [
                'id' => 8,
                'name' => 'Regulations & Notices',
                'slug' => 'regulations-notices',
                'description' => 'Compliance notes, restricted items, and legal notices.',
                'icon_key' => 'notice',
                'sort_order' => 8,
            ],
        ];
    }

    public static function hotQuestions(): array
    {
        return [
            [
                'id' => 1,
                'category_id' => 6,
                'category_name' => 'Account & Security',
                'category_slug' => 'account-security',
                'question' => 'How do I reset my account password if I forgot it?',
                'answer' => 'Open the sign in page, choose "Forgot password", and follow the reset link sent to your email address. If the email does not arrive, check your spam folder and confirm that you entered the same address used on your NovaMarket account.',
                'is_hot' => true,
                'sort_order' => 1,
            ],
            [
                'id' => 2,
                'category_id' => 6,
                'category_name' => 'Account & Security',
                'category_slug' => 'account-security',
                'question' => 'Why can’t I change my mobile number?',
                'answer' => 'For security, some number changes require a recent login, account verification, or a cooling-off period after password updates. Make sure your account email is verified first, then retry from your profile settings.',
                'is_hot' => true,
                'sort_order' => 2,
            ],
            [
                'id' => 3,
                'category_id' => 1,
                'category_name' => 'Shop with NovaMarket',
                'category_slug' => 'shop-with-novamarket',
                'question' => 'Why can’t I create a new account?',
                'answer' => 'Account creation may be blocked if the email is already registered, the password does not meet the minimum rules, or repeated sign-up attempts triggered a temporary security check. Try again with a different email or wait a few minutes before retrying.',
                'is_hot' => true,
                'sort_order' => 3,
            ],
            [
                'id' => 4,
                'category_id' => 4,
                'category_name' => 'Orders & Shipping',
                'category_slug' => 'orders-shipping',
                'question' => 'How do I track my order after payment?',
                'answer' => 'Go to your profile or orders page, open the order, and review the shipping status shown by the seller or courier. Tracking updates usually appear shortly after the order is packed and handed over for delivery.',
                'is_hot' => true,
                'sort_order' => 4,
            ],
            [
                'id' => 5,
                'category_id' => 5,
                'category_name' => 'Returns & Refunds',
                'category_slug' => 'returns-refunds',
                'question' => 'How do I request a return or refund?',
                'answer' => 'Open the order, choose the return or refund option, describe the issue clearly, and attach supporting photos if needed. Requests are reviewed based on the seller’s return window and NovaMarket policy.',
                'is_hot' => true,
                'sort_order' => 5,
            ],
            [
                'id' => 6,
                'category_id' => 3,
                'category_name' => 'Payments',
                'category_slug' => 'payments',
                'question' => 'Why did my payment fail at checkout?',
                'answer' => 'Payment can fail if your card details are incorrect, the bank blocks the transaction, the payment session expires, or the selected method has insufficient balance. Recheck the details or try another payment method.',
                'is_hot' => true,
                'sort_order' => 6,
            ],
            [
                'id' => 7,
                'category_id' => 7,
                'category_name' => 'Policies',
                'category_slug' => 'policies',
                'question' => 'What items are not eligible for return?',
                'answer' => 'Used personal-care goods, customized products, digital items, and products marked as non-returnable are usually excluded unless they arrive damaged or incorrect. Always check the product listing and policy notes before purchase.',
                'is_hot' => true,
                'sort_order' => 7,
            ],
            [
                'id' => 8,
                'category_id' => 2,
                'category_name' => 'Deals & Rewards',
                'category_slug' => 'deals-rewards',
                'question' => 'How do I use a promo code or reward voucher?',
                'answer' => 'Apply the promo code during checkout in the voucher section before placing the order. Some rewards require a minimum spend, a valid campaign period, or specific product eligibility, so check the promotion terms if the discount is not applied.',
                'is_hot' => true,
                'sort_order' => 8,
            ],
        ];
    }
}
