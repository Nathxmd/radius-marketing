<?php

namespace App\Models;

use PDO;

class Staff {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function all(): array {
        return $this->pdo->query("SELECT s.*, b.name AS branch_name,
            (SELECT COUNT(*) FROM registrations r WHERE r.staff_id = s.id) AS lead_count,
            (SELECT COUNT(*) FROM registrations r WHERE r.staff_id = s.id AND r.enrollment_status = 'enrolled') AS enrolled_count
            FROM staff s LEFT JOIN branches b ON b.id = s.branch_id ORDER BY s.created_at DESC")->fetchAll();
    }

    public function find(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT s.*, b.name AS branch_name FROM staff s LEFT JOIN branches b ON b.id = s.branch_id WHERE s.id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function registrations(int $staffId): array {
        $stmt = $this->pdo->prepare("SELECT * FROM registrations WHERE staff_id = ? ORDER BY created_at DESC");
        $stmt->execute([$staffId]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int {
        $stmt = $this->pdo->prepare("INSERT INTO staff (name, role, branch_id, referral_code, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$data['name'], $data['role'], $data['branch_id'] ?: null, $data['referral_code'], $data['status']]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void {
        $stmt = $this->pdo->prepare("UPDATE staff SET name = ?, role = ?, branch_id = ?, referral_code = ?, status = ? WHERE id = ?");
        $stmt->execute([$data['name'], $data['role'], $data['branch_id'] ?: null, $data['referral_code'], $data['status'], $id]);
    }

    public function existsCode(string $code, ?int $ignoreId = null): bool {
        $sql = "SELECT id FROM staff WHERE referral_code = ?" . ($ignoreId ? " AND id <> ?" : "") . " LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($ignoreId ? [$code, $ignoreId] : [$code]);
        return (bool) $stmt->fetch();
    }
}