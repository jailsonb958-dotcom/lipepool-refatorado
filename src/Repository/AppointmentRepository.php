<?php

declare(strict_types=1);

namespace LipePool\Repository;

use PDO;

final class AppointmentRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function create(int $customerId, int $serviceId, string $date, string $time, string $notes): int
    {
        $statement = $this->pdo->prepare(
            "INSERT INTO appointments (customer_id, service_id, appointment_date, appointment_time, notes, status)
             VALUES (:customer_id, :service_id, :appointment_date, :appointment_time, :notes, 'requested')"
        );
        $statement->execute([
            'customer_id' => $customerId,
            'service_id' => $serviceId,
            'appointment_date' => $date,
            'appointment_time' => $time,
            'notes' => $notes === '' ? null : $notes,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function forCustomer(int $customerId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT a.id, a.appointment_date, a.appointment_time, a.notes, a.status, s.name AS service_name
             FROM appointments a INNER JOIN services s ON s.id = a.service_id
             WHERE a.customer_id = :customer_id
             ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT 200'
        );
        $statement->execute(['customer_id' => $customerId]);
        return $statement->fetchAll();
    }

    public function forAdmin(string $status = ''): array
    {
        $sql = 'SELECT a.id, a.appointment_date, a.appointment_time, a.notes, a.status,
                       u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone,
                       s.name AS service_name
                FROM appointments a
                INNER JOIN users u ON u.id = a.customer_id
                INNER JOIN services s ON s.id = a.service_id';
        $params = [];
        if (in_array($status, ['requested', 'completed', 'cancelled'], true)) {
            $sql .= ' WHERE a.status = :status';
            $params['status'] = $status;
        }
        $sql .= ' ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT 500';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function changeStatus(int $id, string $status, ?int $ownerId = null): bool
    {
        if (!in_array($status, ['completed', 'cancelled'], true)) {
            throw new \InvalidArgumentException('Estado de agendamento inválido.');
        }
        $sql = "UPDATE appointments SET status = :status
                WHERE id = :id AND status = 'requested'";
        $params = ['status' => $status, 'id' => $id];
        if ($ownerId !== null) {
            $sql .= ' AND customer_id = :owner_id';
            $params['owner_id'] = $ownerId;
        }
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return $statement->rowCount() === 1;
    }
}
