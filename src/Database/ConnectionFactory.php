<?php

declare(strict_types=1);

namespace LipePool\Database;

use LipePool\Support\Config;
use PDO;

final class ConnectionFactory
{
    public static function mysql(): PDO
    {
        return self::connect(Config::required('DB_USERNAME'), Config::required('DB_PASSWORD'));
    }

    public static function mysqlForMigrations(): PDO
    {
        return self::connect(Config::required('DB_MIGRATOR_USERNAME'), Config::required('DB_MIGRATOR_PASSWORD'));
    }

    private static function connect(string $username, string $password): PDO
    {
        $host = Config::required('DB_HOST');
        $port = Config::get('DB_PORT', '3306');
        $database = Config::required('DB_DATABASE');
        $charset = Config::get('DB_CHARSET', 'utf8mb4');
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $charset)) {
            throw new \RuntimeException('Charset de banco inválido.');
        }

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $host, $port, $database, $charset);
        return new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ]);
    }
}
