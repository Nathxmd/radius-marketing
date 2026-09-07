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

    public function paginate(string $search, int $limit, int $offset): array {
        $where = '';
        $params = [];
        if ($search !== '') {
            $where = ' WHERE s.name LIKE ? OR s.employee_code LIKE ? OR s.referral_code LIKE ?';
            $term = "%{$search}%";
            $params = [$term, $term, $term];
        }

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM staff s{$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->pdo->prepare("SELECT s.*, b.name AS branch_name,
            (SELECT COUNT(*) FROM registrations r WHERE r.staff_id = s.id) AS lead_count,
            (SELECT COUNT(*) FROM registrations r WHERE r.staff_id = s.id AND r.enrollment_status = 'enrolled') AS enrolled_count
            FROM staff s LEFT JOIN branches b ON b.id = s.branch_id{$where}
            ORDER BY s.created_at DESC, s.id DESC LIMIT ? OFFSET ?");
        $position = 1;
        foreach ($params as $param) {
            $stmt->bindValue($position++, $param, PDO::PARAM_STR);
        }
        $stmt->bindValue($position++, $limit, PDO::PARAM_INT);
        $stmt->bindValue($position, $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['rows' => $stmt->fetchAll(), 'total' => $total];
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
        $stmt = $this->pdo->prepare("INSERT INTO staff (name, employee_code, role, branch_id, referral_code, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$data['name'], $data['employee_code'] ?: null, $data['role'], $data['branch_id'] ?: null, $data['referral_code'], $data['status']]);
        return (int) $this->pdo->lastInsertId();
    }

    public function findByEmployeeCode(string $employeeCode): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM staff WHERE employee_code = ? LIMIT 1");
        $stmt->execute([$employeeCode]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByName(string $name): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM staff WHERE name = ? LIMIT 1");
        $stmt->execute([$name]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateEmployee(int $id, string $name, ?string $employeeCode): void {
        $stmt = $this->pdo->prepare("UPDATE staff SET name = ?, employee_code = ? WHERE id = ?");
        $stmt->execute([$name, $employeeCode ?: null, $id]);
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