<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;
use InvalidArgumentException;
use RuntimeException;

final class AuthService
{
    public function __construct(private readonly UserRepository $userRepository)
    {
    }

    public function register(array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        $email = mb_strtolower(trim((string) ($input['email'] ?? '')));
        $phone = trim((string) ($input['phone'] ?? '')) ?: null;
        $password = (string) ($input['password'] ?? '');
        $passwordConfirm = (string) ($input['password_confirm'] ?? '');
        $accountType = (string) ($input['account_type'] ?? 'customer');
        $storeName = trim((string) ($input['store_name'] ?? ''));

        if ($name === '' || mb_strlen($name) > 120) {
            throw new InvalidArgumentException('Name is required (max 120 characters).');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
            throw new InvalidArgumentException('A valid email address is required.');
        }

        if (mb_strlen($password) < 8) {
            throw new InvalidArgumentException('Password must be at least 8 characters.');
        }

        if ($password !== $passwordConfirm) {
            throw new InvalidArgumentException('Passwords do not match.');
        }

        if ($this->userRepository->emailExists($email)) {
            throw new RuntimeException('An account with this email already exists.');
        }

        $isSeller = $accountType === 'seller';

        if ($isSeller && ($storeName === '' || mb_strlen($storeName) > 120)) {
            throw new InvalidArgumentException('Store name is required for seller accounts (max 120 characters).');
        }

        // Sellers are created as inactive (pending admin approval)
        $userId = $this->userRepository->create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $isSeller ? 'seller' : 'customer',
            'is_active' => $isSeller ? 0 : 1,
        ]);

        // Create seller profile if registering as seller
        if ($isSeller) {
            $this->userRepository->upsertSellerProfile($userId, [
                'store_name' => $storeName,
                'store_slug' => $this->slugify($storeName),
                'support_email' => $email,
            ]);
        }

        $user = $this->userRepository->findById($userId);

        // Only auto-login customers; sellers must wait for approval
        if (!$isSeller) {
            $this->setSession($user);
        }

        $user['pending_approval'] = $isSeller;

        return $user;
    }

    private function slugify(string $text): string
    {
        $slug = mb_strtolower(trim($text));
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug) ?? $slug;
        $slug = preg_replace('/[\s-]+/', '-', $slug) ?? $slug;
        return trim($slug, '-');
    }

    public function login(string $email, string $password): array
    {
        $email = mb_strtolower(trim($email));
        $user = $this->userRepository->findByEmail($email);

        if ($user === null) {
            throw new RuntimeException('Invalid email or password.');
        }

        if (!$user['is_active']) {
            throw new RuntimeException('Invalid email or password.');
        }

        if (!password_verify($password, $user['password_hash'])) {
            throw new RuntimeException('Invalid email or password.');
        }

        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $rehash = password_hash($password, PASSWORD_DEFAULT);
            $this->userRepository->update((int) $user['id'], [
                'password_hash' => $rehash,
            ]);
            $user['password_hash'] = $rehash;
        }

        unset($user['password_hash']);
        $this->setSession($user);

        return $user;
    }

    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    private function setSession(array $user): void
    {
        session_regenerate_id(true);

        unset($_SESSION['csrf_token']);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['name'];
    }
}
