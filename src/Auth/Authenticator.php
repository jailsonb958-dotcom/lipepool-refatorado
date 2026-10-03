<?php

declare(strict_types=1);

namespace LipePool\Auth;

use LipePool\Repository\UserRepository;

final class Authenticator
{
    private const DUMMY_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';

    public function __construct(private readonly UserRepository $users) {}

    public function authenticate(string $email, string $password): ?array
    {
        $user = $this->users->findByEmail($email);
        $hash = is_array($user) ? (string) $user['password_hash'] : self::DUMMY_HASH;
        $valid = password_verify($password, $hash);
        if (!$valid || !is_array($user) || (int) $user['is_active'] !== 1) {
            return null;
        }
        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            $this->users->updatePasswordHash((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
        }
        unset($user['password_hash']);
        return $user;
    }
}
