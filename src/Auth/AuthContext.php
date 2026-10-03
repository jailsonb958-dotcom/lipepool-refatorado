<?php

declare(strict_types=1);

namespace LipePool\Auth;

use LipePool\Support\HttpException;

final class AuthContext
{
    public static function user(): ?array
    {
        $user = $_SESSION['user'] ?? null;
        return is_array($user) ? $user : null;
    }

    public static function requireLogin(): array
    {
        $user = self::user();
        if ($user === null) {
            throw new HttpException(401, 'Entre na sua conta para continuar.');
        }
        return $user;
    }

    public static function requireCustomer(): array
    {
        $user = self::requireLogin();
        if (($user['role'] ?? null) !== 'customer') {
            throw new HttpException(403, 'Esta área é exclusiva para clientes.');
        }
        return $user;
    }

    public static function requireAdmin(): array
    {
        $user = self::requireLogin();
        if (($user['role'] ?? null) !== 'admin') {
            throw new HttpException(403, 'Acesso restrito à administração.');
        }
        return $user;
    }
}
