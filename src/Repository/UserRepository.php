<?php

declare(strict_types=1);

namespace LipePool\Repository;

use PDO;

final class UserRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function findByEmail(string $email): ?array
    {
        $statement = $this->pdo->prepare('SELECT id, name, email, phone, password_hash, role, is_active FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => $email]);
        return $statement->fetch() ?: null;
    }

    public function createCustomer(string $name, string $email, string $phone, string $passwordHash): int
    {
        $statement = $this->pdo->prepare(
            "INSERT INTO users (name, email, phone, password_hash, role) VALUES (:name, :email, :phone, :password_hash, 'customer')"
        );
        $statement->execute([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password_hash' => $passwordHash,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function findActiveById(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT id, name, email, phone, role FROM users WHERE id = :id AND is_active = 1 LIMIT 1');
        $statement->execute(['id' => $id]);
        return $statement->fetch() ?: null;
    }

    public function updatePasswordHash(int $id, string $hash): void
    {
        $statement = $this->pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id AND is_active = 1');
        $statement->execute(['hash' => $hash, 'id' => $id]);
    }

    public function allCustomers(string $search = ''): array
    {
        $sql = "SELECT id, name, email, phone, created_at FROM users WHERE role = 'customer' AND is_active = 1";
        $params = [];
        if ($search !== '') {
            $sql .= ' AND (name LIKE :name OR email LIKE :email)';
            $needle = '%' . $search . '%';
            $params = ['name' => $needle, 'email' => $needle];
        }
        $sql .= ' ORDER BY name ASC LIMIT 200';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }
}
