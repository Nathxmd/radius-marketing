<?php

namespace App\Models;

use PDO;

class CoveredArea {
    private $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    public function getByBranch(int $branchId): array {
        $stmt = $this->pdo->prepare("
            SELECT * FROM covered_areas 
            WHERE branch_id = ?
            ORDER BY area_type, distance_km IS NULL, distance_km, area_name
        ");
        $stmt->execute([$branchId]);
        return $stmt->fetchAll();
    }

    public function getByBranchAndType(int $branchId, string $areaType, int $limit, int $offset): array {
        $stmt = $this->pdo->prepare("
            SELECT * FROM covered_areas 
            WHERE branch_id = ? AND area_type = ?
            ORDER BY distance_km IS NULL, distance_km, area_name
            LIMIT ? OFFSET ?
        ");
        $stmt->bindValue(1, $branchId, PDO::PARAM_INT);
        $stmt->bindValue(2, $areaType, PDO::PARAM_STR);
        $stmt->bindValue(3, $limit, PDO::PARAM_INT);
        $stmt->bindValue(4, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countByBranchAndType(int $branchId, string $areaType): int {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM covered_areas 
            WHERE branch_id = ? AND area_type = ?
        ");
        $stmt->execute([$branchId, $areaType]);
        return (int) $stmt->fetchColumn();
    }

    
    public function create(array $data): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO covered_areas (branch_id, area_name, area_type, kecamatan, kabupaten_kota, provinsi, distance_km, source)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data["branch_id"],
            $data["area_name"],
            $data["area_type"],
            $data["kecamatan"] ?? null,
            $data["kabupaten_kota"] ?? null,
            $data["provinsi"] ?? null,
            $data["distance_km"] ?? null,
            $data["source"] ?? "polygon"
        ]);
        return (int) $this->pdo->lastInsertId();
    }
    
    public function deleteByBranch(int $branchId): bool {
        $stmt = $this->pdo->prepare("DELETE FROM covered_areas WHERE branch_id = ?");
        return $stmt->execute([$branchId]);
    }
}
