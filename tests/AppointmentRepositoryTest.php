<?php

declare(strict_types=1);

namespace LipePool\Tests;

use LipePool\Repository\AppointmentRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class AppointmentRepositoryTest extends TestCase
{
    private PDO $pdo;
    private AppointmentRepository $repository;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('PRAGMA foreign_keys = ON');
        $this->pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT, phone TEXT, role TEXT, is_active INTEGER, created_at TEXT)');
        $this->pdo->exec('CREATE TABLE services (id INTEGER PRIMARY KEY, name TEXT)');
        $this->pdo->exec("CREATE TABLE appointments (id INTEGER PRIMARY KEY AUTOINCREMENT, customer_id INTEGER, service_id INTEGER, appointment_date TEXT, appointment_time TEXT, notes TEXT, status TEXT, FOREIGN KEY(customer_id) REFERENCES users(id), FOREIGN KEY(service_id) REFERENCES services(id))");
        $this->pdo->exec("INSERT INTO users VALUES (1, 'Cliente', 'a@example.com', '21999999999', 'customer', 1, '2026-01-01')");
        $this->pdo->exec("INSERT INTO users VALUES (2, 'Outra pessoa', 'b@example.com', '21888888888', 'customer', 1, '2026-01-01')");
        $this->pdo->exec("INSERT INTO services VALUES (1, 'Manutenção')");
        $this->repository = new AppointmentRepository($this->pdo);
    }

    public function testCreateAndReadUsesTheRequestedCustomer(): void
    {
        $id = $this->repository->create(1, 1, '2030-01-15', '08:00:00', '<script>alert(1)</script>');
        self::assertGreaterThan(0, $id);
        self::assertCount(1, $this->repository->forCustomer(1));
        self::assertCount(0, $this->repository->forCustomer(2));
    }

    public function testCustomerCanOnlyChangeTheirOwnPendingBooking(): void
    {
        $id = $this->repository->create(1, 1, '2030-01-15', '08:00:00', '');
        self::assertFalse($this->repository->changeStatus($id, 'cancelled', 2));
        self::assertTrue($this->repository->changeStatus($id, 'cancelled', 1));
        self::assertFalse($this->repository->changeStatus($id, 'cancelled', 1));
    }

    public function testAdminCanCompleteOnlyPendingBookings(): void
    {
        $id = $this->repository->create(1, 1, '2030-01-15', '08:00:00', '');
        self::assertTrue($this->repository->changeStatus($id, 'completed'));
        self::assertFalse($this->repository->changeStatus($id, 'completed'));
    }

    public function testInvalidTransitionIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->repository->changeStatus(1, 'admin', 1);
    }
}
