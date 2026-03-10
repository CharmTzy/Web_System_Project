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
        $email = trim((string) ($input['email'] ?? ''));
        $phone = trim((string) ($input['phone'] ?? '')) ?: null;
        $password = (string) ($input['password'] ?? '');
        $passwordConfirm = (string) ($input['password_confirm'] ?? '');

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

        $userId = $this->userRepository->create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'customer',
            'is_active' => 1,
        ]);

        $user = $this->userRepository->findById($userId);

        $this->setSession($user);

        return $user;
    }

    public function login(string $email, string $password): array
    {
        $user = $this->userRepository->findByEmail($email);

        if ($user === null) {
            throw new RuntimeException('Invalid email or password.');
        }

        if (!$user['is_active']) {
            throw new RuntimeException('This account has been deactivated. Contact support.');
        }

        if (!password_verify($password, $user['password_hash'])) {
            throw new RuntimeException('Invalid email or password.');
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

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['name'];
    }
}
