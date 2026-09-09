<?php

namespace App\Models;

use PDO;

class Referral {
    private $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function logs(string $type = 'all'): array {
        if ($type === 'push') return $this->pdo->query("SELECT l.*, s.name AS staff_name FROM referral_push_logs l JOIN staff s ON s.id = l.staff_id ORDER BY l.created_at DESC LIMIT 200")->fetchAll();
        if ($type === 'webhook') return $this->pdo->query("SELECT * FROM webhook_logs ORDER BY created_at DESC LIMIT 200")->fetchAll();
        if ($type === 'failed') return $this->pdo->query("SELECT * FROM webhook_logs WHERE error_message IS NOT NULL AND error_message <> '' ORDER BY created_at DESC LIMIT 200")->fetchAll();
        return [];
    }

    public function setting(): array {
        return $this->pdo->query("SELECT * FROM referral_settings WHERE id = 1")->fetch() ?: ['id' => 1, 'reward_type' => 'nominal', 'discount_value_parent' => 0, 'commission_value_staff' => 0];
    }

    public function saveSetting(string $type, float $discount, float $commission): void {
        $stmt = $this->pdo->prepare("UPDATE referral_settings SET reward_type = ?, discount_value_parent = ?, commission_value_staff = ? WHERE id = 1");
        $stmt->execute([$type, $discount, $commission]);
    }

    /**
     * Ambil daftar promo (daycare/klinik) + rules terkait.
     */
    public function promos(): array {
        return $this->pdo->query(
            "SELECT p.*, r.id AS rule_id, r.beneficiary, r.rule_type, r.value,
                    r.duration_count, r.visit_range_start, r.visit_range_end, r.branch_id
             FROM promos p
             LEFT JOIN promo_rules r ON r.promo_id = p.id
             ORDER BY p.type, r.beneficiary, r.branch_id"
        )->fetchAll();
    }

    /**
     * Ambil semua aturan promo daycare (global, branch NULL) + klinik per cabang.
     */
    public function promoRules(): array {
        return $this->pdo->query(
            "SELECT r.*, p.type, b.name AS branch_name
             FROM promo_rules r
             JOIN promos p ON p.id = r.promo_id
             LEFT JOIN branches b ON b.id = r.branch_id
             ORDER BY p.type, r.beneficiary, r.branch_id"
        )->fetchAll();
    }

    /**
     * Simpan/update satu aturan promo (UPSERT by promo+beneficiary+branch).
     *
     * @param int    $promoId
     * @param string $beneficiary 'parent' | 'staff'
     * @param array  $fields  ['value'=>, 'duration_count'=>, 'visit_range_start'=>,
     *                         'visit_range_end'=>, 'branch_id'=>, 'rule_type'=>]
     */
    public function upsertPromoRule(int $promoId, string $beneficiary, array $fields): void {
        $ruleType = $fields['rule_type'] ?? 'recurring_monthly';
        $branchId = $fields['branch_id'] ?? null;

        // Cek baris existing (branch_id NULL dihitung sebagai unique sendiri).
        $check = $this->pdo->prepare(
            "SELECT id FROM promo_rules WHERE promo_id = ? AND beneficiary = ? AND branch_id "
            . ($branchId ? "= ?" : "IS NULL")
        );
        $params = $branchId ? [$promoId, $beneficiary, $branchId] : [$promoId, $beneficiary];
        $check->execute($params);
        $existingId = $check->fetchColumn();

        $value       = $fields['value'] ?? 0;
        $duration    = $fields['duration_count'] ?? null;
        $rangeStart  = $fields['visit_range_start'] ?? null;
        $rangeEnd    = $fields['visit_range_end'] ?? null;

        if ($existingId) {
            $stmt = $this->pdo->prepare(
                "UPDATE promo_rules
                 SET value = ?, duration_count = ?, visit_range_start = ?, visit_range_end = ?, rule_type = ?
                 WHERE id = ?"
            );
            $stmt->execute([$value, $duration, $rangeStart, $rangeEnd, $ruleType, $existingId]);
        } else {
            $stmt = $this->pdo->prepare(
                "INSERT INTO promo_rules
                    (promo_id, beneficiary, branch_id, value, duration_count, visit_range_start, visit_range_end, rule_type)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$promoId, $beneficiary, $branchId, $value, $duration, $rangeStart, $rangeEnd, $ruleType]);
        }
    }

    /**
     * Ambil id promo berdasarkan type.
     */
    public function promoIdByType(string $type): ?int {
        $stmt = $this->pdo->prepare("SELECT id FROM promos WHERE type = ? LIMIT 1");
        $stmt->execute([$type]);
        $id = $stmt->fetchColumn();
        return $id !== false ? (int) $id : null;
    }

    /**
     * Ambil semua promo_redemptions untuk suatu registrasi.
     */
    public function redemptions(int $registrationId): array {
        $stmt = $this->pdo->prepare("SELECT * FROM promo_redemptions WHERE registration_id = ? ORDER BY period_or_visit_number");
        $stmt->execute([$registrationId]);
        return $stmt->fetchAll();
    }

    /**
     * Total komisi staff (sum value_applied) untuk semua registrasi daycare milik staff.
     * Klinik selalu 0.
     */
    public function staffTotalCommission(int $staffId): float {
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(SUM(pr.value_applied), 0) AS total
             FROM promo_redemptions pr
             JOIN registrations r ON r.id = pr.registration_id
             WHERE r.staff_id = ? AND pr.beneficiary = 'staff' AND r.promo_type = 'daycare'"
        );
        $stmt->execute([$staffId]);
        return (float) $stmt->fetchColumn();
    }

    public function updateCommission(int $registrationId, string $status): void {
        // Hanya untuk status yang valid pada alur komisi staff (bukan 'tidak_ada').
        if (in_array($status, ['disetujui', 'dibayar'], true)) {
            $stmt = $this->pdo->prepare("UPDATE registrations SET staff_commission_status = ?, updated_at = NOW() WHERE id = ? AND staff_commission_status <> 'tidak_ada'");
            $stmt->execute([$status, $registrationId]);
        }
    }
}