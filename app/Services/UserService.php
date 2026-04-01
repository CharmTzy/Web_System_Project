<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;
use InvalidArgumentException;
use Throwable;
use RuntimeException;

final class UserService
{
    public function __construct(private readonly UserRepository $userRepository)
    {
    }

    public function getProfile(int $userId): ?array
    {
        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            return null;
        }

        if ($user['role'] === 'seller') {
            $user['seller_profile'] = $this->userRepository->findSellerProfile($userId);
        }

        return $user;
    }

    public function updateProfile(int $userId, array $input): array
    {
        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            throw new RuntimeException('User not found.');
        }

        $data = [];

        $name = sanitize_single_line($input['name'] ?? '', 120);
        if ($name !== '' && $name !== $user['name']) {
            if (mb_strlen($name) > 120) {
                throw new InvalidArgumentException('Name must be 120 characters or less.');
            }
            $data['name'] = $name;
        }

        $email = sanitize_email_address($input['email'] ?? '');
        if ($email !== '' && $email !== $user['email']) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Invalid email address.');
            }
            if ($this->userRepository->emailExists($email, $userId)) {
                throw new RuntimeException('This email is already in use.');
            }
            $data['email'] = $email;
        }

        if (array_key_exists('phone', $input)) {
            $data['phone'] = sanitize_phone_number($input['phone'] ?? '');
        }

        $newPassword = (string) ($input['new_password'] ?? '');
        if ($newPassword !== '') {
            ensure_password_strength($newPassword);
            $data['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        if ($data !== []) {
            $this->userRepository->update($userId, $data);
        }

        $updated = $this->userRepository->findById($userId);

        if (isset($data['name'])) {
            $_SESSION['user_name'] = $data['name'];
        }

        return $updated;
    }

    public function listUsers(array $filters = []): array
    {
        return $this->userRepository->listAll($filters);
    }

    public function adminCreateUser(array $input): array
    {
        $name = sanitize_single_line($input['name'] ?? '', 120);
        $email = sanitize_email_address($input['email'] ?? '');
        $role = (string) ($input['role'] ?? '');
        $password = (string) ($input['password'] ?? '');

        if ($name === '') {
            throw new InvalidArgumentException('Name is required.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Valid email is required.');
        }

        if (!in_array($role, ['admin', 'seller', 'customer'], true)) {
            throw new InvalidArgumentException('Invalid role.');
        }

        ensure_password_strength($password);

        if ($this->userRepository->emailExists($email)) {
            throw new RuntimeException('Email already in use.');
        }

        $userId = $this->userRepository->create([
            'name' => $name,
            'email' => $email,
            'phone' => sanitize_phone_number($input['phone'] ?? ''),
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'is_active' => 1,
        ]);

        if ($role === 'seller') {
            $storeName = sanitize_single_line($input['store_name'] ?? '', 120) ?: $name;
            $storeSlug = sanitize_single_line($input['store_slug'] ?? '', 120) ?: $this->slugify($storeName);

            $this->userRepository->upsertSellerProfile($userId, [
                'store_name' => $storeName,
                'store_slug' => $storeSlug,
                'support_email' => sanitize_email_address($input['support_email'] ?? '') ?: null,
            ]);
        }

        return $this->userRepository->findById($userId);
    }

    public function adminUpdateUser(int $userId, array $input): array
    {
        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            throw new RuntimeException('User not found.');
        }

        $data = [];

        if (isset($input['name'])) {
            $name = sanitize_single_line($input['name'] ?? '', 120);
            if ($name !== '') {
                $data['name'] = $name;
            }
        }

        if (isset($input['email'])) {
            $email = sanitize_email_address($input['email'] ?? '');
            if ($email === '') {
                $email = $user['email'];
            }
            if ($email !== $user['email']) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new InvalidArgumentException('Invalid email.');
                }
                if ($this->userRepository->emailExists($email, $userId)) {
                    throw new RuntimeException('Email already in use.');
                }
                $data['email'] = $email;
            }
        }

        if (array_key_exists('phone', $input)) {
            $data['phone'] = sanitize_phone_number($input['phone'] ?? '');
        }

        if (isset($input['is_active'])) {
            $data['is_active'] = (int) (bool) $input['is_active'];
        }

        $newPassword = trim((string) ($input['new_password'] ?? ''));
        if ($newPassword !== '') {
            ensure_password_strength($newPassword);
            $data['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        if ($data !== []) {
            $this->userRepository->update($userId, $data);
        }

        if ($user['role'] === 'seller' && (isset($input['store_name']) || isset($input['store_slug']))) {
            $profile = $this->userRepository->findSellerProfile($userId);
            $this->userRepository->upsertSellerProfile($userId, [
                'store_name' => sanitize_single_line($input['store_name'] ?? '', 120) ?: ($profile['store_name'] ?? $user['name']),
                'store_slug' => sanitize_single_line($input['store_slug'] ?? '', 120) ?: ($profile['store_slug'] ?? $this->slugify($user['name'])),
                'support_email' => sanitize_email_address($input['support_email'] ?? '') ?: ($profile['support_email'] ?? null),
            ]);
        }

        return $this->getProfile($userId);
    }

    public function toggleActive(int $userId): array
    {
        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            throw new RuntimeException('User not found.');
        }

        $this->userRepository->update($userId, ['is_active' => $user['is_active'] ? 0 : 1]);

        return $this->userRepository->findById($userId);
    }

    public function adminDeleteUser(int $userId, int $actingUserId): void
    {
        if ($userId < 1) {
            throw new InvalidArgumentException('Invalid user selected.');
        }

        if ($userId === $actingUserId) {
            throw new RuntimeException('You cannot delete your own admin account.');
        }

        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            throw new RuntimeException('User not found.');
        }

        try {
            $this->userRepository->delete($userId);
        } catch (Throwable $exception) {
            throw new RuntimeException('This user cannot be deleted yet because related records still exist.');
        }
    }

    public function updateSellerStore(int $userId, array $input): array
    {
        $user = $this->userRepository->findById($userId);

        if ($user === null || $user['role'] !== 'seller') {
            throw new RuntimeException('Seller not found.');
        }

        $storeName = sanitize_single_line($input['store_name'] ?? '', 120);
        $storeSlug = sanitize_single_line($input['store_slug'] ?? '', 120);

        if ($storeName === '') {
            throw new InvalidArgumentException('Store name is required.');
        }

        if ($storeSlug === '') {
            $storeSlug = $this->slugify($storeName);
        }

        $this->userRepository->upsertSellerProfile($userId, [
            'store_name' => $storeName,
            'store_slug' => $storeSlug,
            'support_email' => sanitize_email_address($input['support_email'] ?? '') ?: null,
        ]);

        return $this->getProfile($userId);
    }

    private function slugify(string $text): string
    {
        $slug = mb_strtolower($text);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        return trim($slug, '-');
    }
}
