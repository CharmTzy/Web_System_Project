<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AddressRepository;
use App\Repositories\UserRepository;
use InvalidArgumentException;
use RuntimeException;

final class AdminAddressService
{
    private const MAX_ADDRESSES = 5;

    public function __construct(
        private readonly AddressRepository $addressRepository,
        private readonly UserRepository $userRepository
    ) {
    }

    public function customerOptions(): array
    {
        $customers = $this->userRepository->listAll(['role' => 'customer']);

        return array_map(
            static fn (array $customer): array => [
                'id' => (int) $customer['id'],
                'name' => (string) $customer['name'],
                'email' => (string) $customer['email'],
            ],
            $customers
        );
    }

    public function listAddresses(array $filters = []): array
    {
        return $this->addressRepository->listAll($filters);
    }

    public function getAddress(int $id): ?array
    {
        return $this->addressRepository->findDetailedById($id);
    }

    public function create(array $input): array
    {
        $userId = filter_var($input['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($userId === false) {
            throw new InvalidArgumentException('Please select a customer.');
        }

        $customer = $this->userRepository->findById($userId);

        if ($customer === null || $customer['role'] !== 'customer') {
            throw new InvalidArgumentException('Selected customer is invalid.');
        }

        $count = $this->addressRepository->countByUser($userId);
        if ($count >= self::MAX_ADDRESSES) {
            throw new RuntimeException('This customer already has the maximum of ' . self::MAX_ADDRESSES . ' addresses.');
        }

        $data = $this->validate($input);
        $data['user_id'] = $userId;

        if ($count === 0) {
            $data['is_default'] = 1;
        }

        $id = $this->addressRepository->create($data);

        if (!empty($data['is_default'])) {
            $this->addressRepository->setDefault($id, $userId);
        }

        return $this->addressRepository->findDetailedById($id)
            ?? throw new RuntimeException('Address could not be created.');
    }

    public function update(int $id, array $input): array
    {
        $existing = $this->addressRepository->findById($id);

        if ($existing === null) {
            throw new RuntimeException('Address not found.');
        }

        $data = $this->validate($input);
        $this->addressRepository->update($id, $data);

        if (!empty($data['is_default'])) {
            $this->addressRepository->setDefault($id, (int) $existing['user_id']);
        }

        return $this->addressRepository->findDetailedById($id) ?? $existing;
    }

    public function delete(int $id): void
    {
        $existing = $this->addressRepository->findById($id);

        if ($existing === null) {
            throw new RuntimeException('Address not found.');
        }

        $wasDefault = (bool) $existing['is_default'];
        $userId = (int) $existing['user_id'];
        $this->addressRepository->delete($id);

        if ($wasDefault) {
            $remaining = $this->addressRepository->listByUser($userId);
            if ($remaining !== []) {
                $this->addressRepository->setDefault((int) $remaining[0]['id'], $userId);
            }
        }
    }

    private function validate(array $input): array
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
            'is_default' => (int) bool_from_input($input['is_default'] ?? false),
        ];
    }
}
