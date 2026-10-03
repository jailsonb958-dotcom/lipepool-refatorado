<?php

declare(strict_types=1);

namespace LipePool\Tests;

use LipePool\Repository\ServiceRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class ServiceRepositoryTest extends TestCase
{
    private PDO $pdo;
    private ServiceRepository $repository;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE services (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, description TEXT, duration_minutes INTEGER, is_active INTEGER)');
        $this->pdo->exec("INSERT INTO services (name, description, duration_minutes, is_active) VALUES ('Manutenção', 'Serviço original', 60, 1)");
        $this->repository = new ServiceRepository($this->pdo);
    }

    public function testCreateAndUpdateService(): void
    {
        $id = $this->repository->create('Limpeza', 'Limpeza periódica', 90);
        self::assertGreaterThan(1, $id);
        self::assertSame('Limpeza', $this->repository->findActive($id)['name']);
        self::assertTrue($this->repository->update($id, 'Limpeza especial', '', null));
        self::assertSame('Limpeza especial', $this->repository->findActive($id)['name']);
    }

    public function testDeactivateHidesServiceFromNewBookingsButPreservesRecord(): void
    {
        self::assertTrue($this->repository->deactivate(1));
        self::assertNull($this->repository->findActive(1));
        self::assertCount(0, $this->repository->active());
        self::assertSame(0, (int) $this->repository->all()[0]['is_active']);
        self::assertFalse($this->repository->deactivate(1));
    }
}
