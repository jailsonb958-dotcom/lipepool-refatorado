<?php

declare(strict_types=1);

namespace LipePool\Repository;

use PDO;

final class LoginThrottleRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function key(string $email, string $ip): string
    {
        return hash('sha256', strtolower(trim($email)) . "\0" . $ip);
    }

    public function isBlocked(string $key): bool
    {
        $statement = $this->pdo->prepare('SELECT locked_until IS NOT NULL AND locked_until > NOW() AS is_blocked FROM login_throttles WHERE throttle_key = :key');
        $statement->execute(['key' => $key]);
        $row = $statement->fetch();
        return is_array($row) && (int) $row['is_blocked'] === 1;
    }

    public function recordFailure(string $key): void
    {
        if (random_int(1, 100) === 1) {
            $this->pdo->exec('DELETE FROM login_throttles WHERE updated_at < DATE_SUB(NOW(), INTERVAL 30 DAY)');
        }
        $statement = $this->pdo->prepare(
            "INSERT INTO login_throttles (throttle_key, failed_attempts, window_started_at, locked_until)
             VALUES (:key, 1, NOW(), NULL)
             ON DUPLICATE KEY UPDATE
               locked_until = CASE
                 WHEN window_started_at < DATE_SUB(NOW(), INTERVAL 15 MINUTE) THEN NULL
                 WHEN failed_attempts + 1 >= 5 THEN DATE_ADD(NOW(), INTERVAL 15 MINUTE)
                 ELSE locked_until END,
               failed_attempts = CASE
                 WHEN window_started_at < DATE_SUB(NOW(), INTERVAL 15 MINUTE) THEN 1
                 ELSE LEAST(failed_attempts + 1, 255) END,
               window_started_at = CASE
                 WHEN window_started_at < DATE_SUB(NOW(), INTERVAL 15 MINUTE) THEN NOW()
                 ELSE window_started_at END"
        );
        $statement->execute(['key' => $key]);
    }

    public function clear(string $key): void
    {
        $statement = $this->pdo->prepare('DELETE FROM login_throttles WHERE throttle_key = :key');
        $statement->execute(['key' => $key]);
    }
}
