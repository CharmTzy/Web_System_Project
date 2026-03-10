<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AddressRepository;
use InvalidArgumentException;
use RuntimeException;

final class AddressService
{
    private const MAX_ADDRESSES = 5;

    public function __construct(private readonly AddressRepository $addressRepository)
    {
    }

    public function listForUser(int $userId): array
    {
        return $this->addressRepository->listByUser($userId);
    }

    public function create(int $userId, array $input): array
    {
        $count = $this->addressRepository->countByUser($userId);

        if ($count >= self::MAX_ADDRESSES) {
            throw new RuntimeException('You can save up to ' . self::MAX_ADDRESSES . ' addresses.');
        }

        $data = $this->validateAddress($input);
        $data['user_id'] = $userId;

        if ($count === 0) {
            $data['is_default'] = 1;
        }

        $id = $this->addressRepository->create($data);

        if (!empty($data['is_default'])) {
            $this->addressRepository->setDefault($id, $userId);
        }

        return $this->addressRepository->findById($id);
    }

    public function update(int $addressId, int $userId, array $input): array
    {
        $address = $this->addressRepository->findById($addressId);

        if ($address === null || $address['user_id'] !== $userId) {
            throw new RuntimeException('Address not found.');
        }

        $data = $this->validateAddress($input);
        $this->addressRepository->update($addressId, $data);

        return $this->addressRepository->findById($addressId);
    }

    public function delete(int $addressId, int $userId): void
    {
        $address = $this->addressRepository->findById($addressId);

        if ($address === null || $address['user_id'] !== $userId) {
            throw new RuntimeException('Address not found.');
        }

        $wasDefault = $address['is_default'];
        $this->addressRepository->delete($addressId);

        if ($wasDefault) {
            $remaining = $this->addressRepository->listByUser($userId);
            if ($remaining !== []) {
                $this->addressRepository->setDefault($remaining[0]['id'], $userId);
            }
        }
    }

    public function setDefault(int $addressId, int $userId): array
    {
        $address = $this->addressRepository->findById($addressId);

        if ($address === null || $address['user_id'] !== $userId) {
            throw new RuntimeException('Address not found.');
        }

        $this->addressRepository->setDefault($addressId, $userId);

        return $this->addressRepository->findById($addressId);
    }

    private function validateAddress(array $input): array
    {
        $label = trim((string) ($input['label'] ?? 'Home'));
        $recipient = trim((string) ($input['recipient'] ?? ''));
        $line1 = trim((string) ($input['line_1'] ?? ''));
        $city = trim((string) ($input['city'] ?? ''));
        $state = trim((string) ($input['state'] ?? ''));
        $postalCode = trim((string) ($input['postal_code'] ?? ''));

        if ($recipient === '') {
            throw new InvalidArgumentException('Recipient name is required.');
        }

        if ($line1 === '') {
            throw new InvalidArgumentException('Address line 1 is required.');
        }

        if ($city === '') {
            throw new InvalidArgumentException('City is required.');
        }

        if ($state === '') {
            throw new InvalidArgumentException('State is required.');
        }

        if ($postalCode === '') {
            throw new InvalidArgumentException('Postal code is required.');
        }

        $allowedLabels = ['Home', 'Office', 'Other'];
        if (!in_array($label, $allowedLabels, true)) {
            $label = 'Other';
        }

        return [
            'label' => $label,
            'recipient' => mb_substr($recipient, 0, 120),
            'line_1' => mb_substr($line1, 0, 255),
            'line_2' => trim((string) ($input['line_2'] ?? '')) ?: null,
            'city' => mb_substr($city, 0, 100),
            'state' => mb_substr($state, 0, 100),
            'postal_code' => mb_substr($postalCode, 0, 20),
            'country' => trim((string) ($input['country'] ?? 'Singapore')) ?: 'Singapore',
            'phone' => trim((string) ($input['phone'] ?? '')) ?: null,
            'is_default' => (int) !empty($input['is_default']),
        ];
    }
}
