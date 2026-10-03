<?php

declare(strict_types=1);

use LipePool\Database\ConnectionFactory;
use LipePool\Support\Config;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
Config::load($root);
$pdo = ConnectionFactory::mysqlForMigrations();
$pdo->exec(
    "CREATE TABLE IF NOT EXISTS schema_migrations (
        version VARCHAR(190) NOT NULL PRIMARY KEY,
        checksum CHAR(64) NOT NULL,
        applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$files = glob($root . '/database/migrations/*.sql') ?: [];
sort($files, SORT_STRING);
if (!$files) {
    fwrite(STDOUT, "Nenhuma migration encontrada.\n");
    exit(0);
}

foreach ($files as $file) {
    $version = basename($file);
    $checksum = hash_file('sha256', $file);
    $check = $pdo->prepare('SELECT checksum FROM schema_migrations WHERE version = :version');
    $check->execute(['version' => $version]);
    $existing = $check->fetchColumn();
    if (is_string($existing)) {
        if (!hash_equals($existing, $checksum)) {
            fwrite(STDERR, "A migration aplicada foi alterada: {$version}. Não modifique migrations já executadas.\n");
            exit(1);
        }
        fwrite(STDOUT, "Já aplicada: {$version}\n");
        continue;
    }

    fwrite(STDOUT, "Aplicando: {$version}\n");
    $sql = file_get_contents($file);
    if ($sql === false) {
        throw new RuntimeException('Não foi possível ler migration: ' . $version);
    }
    // As migrations deste projeto usam comentários SQL em linhas próprias e não contêm
    // delimitadores procedurais; esta separação evita habilitar multi-statements no PDO.
    $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
    foreach (explode(';', $sql) as $statement) {
        if (trim($statement) !== '') {
            $pdo->exec($statement);
        }
    }
    $record = $pdo->prepare('INSERT INTO schema_migrations (version, checksum) VALUES (:version, :checksum)');
    $record->execute(['version' => $version, 'checksum' => $checksum]);
}

fwrite(STDOUT, "Todas as migrations estão aplicadas.\n");
