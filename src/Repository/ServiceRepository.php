<?php

declare(strict_types=1);

namespace LipePool\Repository;

use PDO;

final class ServiceRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function active(): array
    {
        return $this->pdo->query('SELECT id, name, description, duration_minutes FROM services WHERE is_active = 1 ORDER BY name ASC')->fetchAll();
    }

    public function all(): array
    {
        return $this->pdo->query('SELECT id, name, description, duration_minutes, is_active FROM services ORDER BY is_active DESC, name ASC')->fetchAll();
    }

    public function findActive(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT id, name FROM services WHERE id = :id AND is_active = 1 LIMIT 1');
        $statement->execute(['id' => $id]);
        return $statement->fetch() ?: null;
    }

    public function create(string $name, string $description, ?int $durationMinutes): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO services (name, description, duration_minutes, is_active) VALUES (:name, :description, :duration, 1)'
        );
        $statement->execute([
            'name' => $name,
            'description' => $description === '' ? null : $description,
            'duration' => $durationMinutes,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, string $name, string $description, ?int $durationMinutes): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE services SET name = :name, description = :description, duration_minutes = :duration WHERE id = :id AND is_active = 1'
        );
        $statement->execute([
            'name' => $name,
            'description' => $description === '' ? null : $description,
            'duration' => $durationMinutes,
            'id' => $id,
        ]);
        return $statement->rowCount() === 1;
    }

    public function deactivate(int $id): bool
    {
        $statement = $this->pdo->prepare('UPDATE services SET is_active = 0 WHERE id = :id AND is_active = 1');
        $statement->execute(['id' => $id]);
        return $statement->rowCount() === 1;
    }
}
