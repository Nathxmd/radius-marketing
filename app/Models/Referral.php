<?php

namespace App\Models;

use PDO;

class Referral {
    private $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function logs(string $type = 'all'): array {
        if ($type === 'push') return $this->pdo->query("SELECT l.*, s.name AS staff_name FROM referral_push_logs l JOIN staff s ON s.id = l.staff_id ORDER BY l.created_at DESC LIMIT 200")->fetchAll();
        if ($type === 'webhook') return $this->pdo->query("SELECT * FROM webhook_logs ORDER BY created_at DESC LIMIT 200")->fetchAll();
        return [];
    }

    public function setting(): array {
        return $this->pdo->query("SELECT * FROM referral_settings WHERE id = 1")->fetch() ?: ['id' => 1, 'reward_type' => 'nominal', 'discount_value_parent' => 0, 'commission_value_staff' => 0];
    }

    public function saveSetting(string $type, float $discount, float $commission): void {
        $stmt = $this->pdo->prepare("UPDATE referral_settings SET reward_type = ?, discount_value_parent = ?, commission_value_staff = ? WHERE id = 1");
        $stmt->execute([$type, $discount, $commission]);
    }

    public function updateCommission(int $registrationId, string $status): void {
        $stmt = $this->pdo->prepare("UPDATE registrations SET commission_status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $registrationId]);
    }
}