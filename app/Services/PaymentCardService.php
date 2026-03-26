<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\PaymentCardRepository;
use InvalidArgumentException;
use RuntimeException;

final class PaymentCardService
{
    private const MAX_CARDS = 5;
    private const ALLOWED_BRANDS = ['visa', 'mastercard', 'amex', 'discover', 'other'];

    public function __construct(private readonly PaymentCardRepository $paymentCardRepository)
    {
    }

    public function listForUser(int $userId): array
    {
        return $this->paymentCardRepository->listByUser($userId);
    }

    public function create(int $userId, array $input): array
    {
        unset($userId, $input);

        throw new RuntimeException('Online card saving is disabled until a verified payment processor is integrated.');
    }

    public function delete(int $cardId, int $userId): void
    {
        $card = $this->paymentCardRepository->findById($cardId);

        if ($card === null || $card['user_id'] !== $userId) {
            throw new RuntimeException('Payment card not found.');
        }

        $wasDefault = $card['is_default'];
        $this->paymentCardRepository->delete($cardId);

        if ($wasDefault) {
            $remaining = $this->paymentCardRepository->listByUser($userId);

            if ($remaining !== []) {
                $this->paymentCardRepository->setDefault($remaining[0]['id'], $userId);
            }
        }
    }

    public function setDefault(int $cardId, int $userId): array
    {
        $card = $this->paymentCardRepository->findById($cardId);

        if ($card === null || $card['user_id'] !== $userId) {
            throw new RuntimeException('Payment card not found.');
        }

        $this->paymentCardRepository->setDefault($cardId, $userId);

        return $this->paymentCardRepository->findById($cardId)
            ?? throw new RuntimeException('Unable to load the payment card.');
    }

    private function validateCard(array $input): array
    {
        $label = trim((string) ($input['label'] ?? ''));
        $cardholderName = trim((string) ($input['cardholder_name'] ?? ''));
        $cardBrand = trim((string) ($input['card_brand'] ?? 'visa'));
        $cardNumber = preg_replace('/\D+/', '', (string) ($input['card_number'] ?? '')) ?? '';
        $expiryMonth = filter_var($input['expiry_month'] ?? null, FILTER_VALIDATE_INT);
        $expiryYear = filter_var($input['expiry_year'] ?? null, FILTER_VALIDATE_INT);

        if ($label === '') {
            $label = 'My Card';
        }

        if ($cardholderName === '' || mb_strlen($cardholderName) > 120) {
            throw new InvalidArgumentException('Cardholder name is required (max 120 characters).');
        }

        if (!in_array($cardBrand, self::ALLOWED_BRANDS, true)) {
            $cardBrand = 'other';
        }

        if (strlen($cardNumber) < 12 || strlen($cardNumber) > 19) {
            throw new InvalidArgumentException('Enter a valid card number.');
        }

        if ($expiryMonth === false || $expiryMonth < 1 || $expiryMonth > 12) {
            throw new InvalidArgumentException('Choose a valid expiry month.');
        }

        $currentYear = (int) date('Y');
        if ($expiryYear === false || $expiryYear < $currentYear || $expiryYear > $currentYear + 20) {
            throw new InvalidArgumentException('Choose a valid expiry year.');
        }

        $currentMonth = (int) date('n');
        if ($expiryYear === $currentYear && $expiryMonth < $currentMonth) {
            throw new InvalidArgumentException('This card is already expired.');
        }

        return [
            'label' => mb_substr($label, 0, 50),
            'cardholder_name' => mb_substr($cardholderName, 0, 120),
            'card_last_four' => substr($cardNumber, -4),
            'card_brand' => $cardBrand,
            'expiry_month' => $expiryMonth,
            'expiry_year' => $expiryYear,
            'is_default' => (int) !empty($input['is_default']),
        ];
    }
}
