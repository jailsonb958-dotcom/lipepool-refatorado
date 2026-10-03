<?php

declare(strict_types=1);

namespace LipePool\Support;

use Dotenv\Dotenv;

final class Config
{
    public static function load(string $root): void
    {
        if (is_file($root . '/.env')) {
            Dotenv::createImmutable($root)->safeLoad();
        }

        date_default_timezone_set(self::get('APP_TIMEZONE', 'America/Sao_Paulo'));
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        return $value === false || $value === null || $value === '' ? $default : (string) $value;
    }

    public static function required(string $key): string
    {
        $value = self::get($key);
        if ($value === null) {
            throw new \RuntimeException('Configuração obrigatória ausente: ' . $key);
        }
        return $value;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);
        return $value === null ? $default : filter_var($value, FILTER_VALIDATE_BOOL);
    }
}
