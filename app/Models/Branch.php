<?php

namespace App\Models;

use PDO;

class Branch {
    private $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    public function getAll(): array {
        $stmt = $this->pdo->query("
            SELECT b.*, u.name as created_by_name,
                   (SELECT COUNT(*) FROM covered_areas ca WHERE ca.branch_id = b.id) as total_areas
            FROM branches b
            LEFT JOIN users u ON b.created_by = u.id
            ORDER BY b.created_at DESC
        ");
        return $stmt->fetchAll();
    }
    
    public function getById(int $id): ?array {
        $stmt = $this->pdo->prepare("
            SELECT b.*, u.name as created_by_name
            FROM branches b
            LEFT JOIN users u ON b.created_by = u.id
            WHERE b.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function create(array $data): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO branches (name, address, city, latitude, longitude, target_market_notes, geocoding_status, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data["name"],
            $data["address"],
            $data["city"],
            $data["latitude"] ?? null,
            $data["longitude"] ?? null,
            $data["target_market_notes"] ?? null,
            $data["geocoding_status"] ?? "pending",
            $data["created_by"] ?? null
        ]);
        return (int) $this->pdo->lastInsertId();
    }
    
    public function update(int $id, array $data): bool {
        $stmt = $this->pdo->prepare("
            UPDATE branches 
            SET name = ?, address = ?, city = ?, latitude = ?, longitude = ?, 
                target_market_notes = ?, geocoding_status = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data["name"],
            $data["address"],
            $data["city"],
            $data["latitude"] ?? null,
            $data["longitude"] ?? null,
            $data["target_market_notes"] ?? null,
            $data["geocoding_status"] ?? "pending",
            $id
        ]);
    }
    
    public function updateNotes(int $id, ?string $notes): bool {
        $stmt = $this->pdo->prepare("
            UPDATE branches SET target_market_notes = ? WHERE id = ?
        ");
        return $stmt->execute([$notes, $id]);
    }
    
    public function delete(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM branches WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    public function search(string $query): array {
        $stmt = $this->pdo->prepare("
            SELECT b.*, u.name as created_by_name,
                   (SELECT COUNT(*) FROM covered_areas ca WHERE ca.branch_id = b.id) as total_areas
            FROM branches b
            LEFT JOIN users u ON b.created_by = u.id
            WHERE b.name LIKE ? OR b.city LIKE ? OR b.address LIKE ?
            ORDER BY b.created_at DESC
        ");
        $search = "%$query%";
        $stmt->execute([$search, $search, $search]);
        return $stmt->fetchAll();
    }
}
