<?php

declare(strict_types=1);

namespace LipePool\Security;

use LipePool\Support\HttpException;

final class Csrf
{
    public static function token(): string
    {
        if (!isset($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function verify(mixed $candidate): void
    {
        $known = $_SESSION['_csrf'] ?? null;
        if (!is_string($candidate) || !is_string($known) || !hash_equals($known, $candidate)) {
            throw new HttpException(419, 'Sua sessão expirou. Atualize a página e tente novamente.');
        }
    }
}
