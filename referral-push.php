<?php
/**
 * referral-push.php
 *
 * Mengirim (push) data kode referral staff ke aplikasi eksternal setiap kali
 * staff ditambah/diedit. Setiap percobaan push dicatat ke referral_push_logs
 * supaya bisa di-retry manual/otomatis kalau gagal.
 *
 * KONFIGURASI:
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';
if (!defined('EXTERNAL_APP_PUSH_URL') || !defined('REFERRAL_SHARED_SECRET')) {
    throw new RuntimeException('Konfigurasi referral belum dimuat');
}

/**
 * Susun promos + rules (termasuk branch_pricing untuk klinik) menjadi array
 * sesuai kontrak payload ke aplikasi eksternal.
 *
 * @param PDO $pdo
 * @return array
 */
function buildReferralPromosPayload(PDO $pdo): array
{
    $promos = [];

    // Promo aktif + rules-nya, lengkap dengan nama cabang untuk override klinik.
    $stmt = $pdo->query(
        "SELECT p.type, r.beneficiary, r.rule_type, r.value,
                r.duration_count, r.visit_range_start, r.visit_range_end,
                r.branch_id, b.name AS branch_name
         FROM promos p
         LEFT JOIN promo_rules r ON r.promo_id = p.id
         LEFT JOIN branches b ON b.id = r.branch_id
         WHERE p.active = 1
         ORDER BY p.id, r.beneficiary"
    );
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $rulesByType = [];
    foreach ($rows as $row) {
        $rulesByType[$row['type']][] = $row;
    }

    foreach ($rulesByType as $type => $rules) {
        $parent = null;
        $staff  = null;
        $branchPricing = [];

        foreach ($rules as $r) {
            $rule = [
                'rule_type' => $r['rule_type'],
                'value'     => (float) $r['value'],
            ];
            if ($r['rule_type'] === 'recurring_monthly') {
                $rule['duration_months'] = (int) $r['duration_count'];
            }
            if ($r['rule_type'] === 'per_visit_tiered') {
                $rule['visit_range_start'] = (int) $r['visit_range_start'];
                $rule['visit_range_end']   = (int) $r['visit_range_end'];
            }

            if ($r['beneficiary'] === 'staff') {
                // staff selalu one_value global; tidak pernah per cabang
                $staff = $rule;
            } else {
                // parent: jika ada branch_id berarti override khusus cabang (klinik)
                if ($r['branch_id'] !== null) {
                    $branchPricing[] = [
                        'branch_id'   => (int) $r['branch_id'],
                        'branch_name' => $r['branch_name'],
                        'value'       => (float) $r['value'],
                    ];
                } else {
                    $parent = $rule;
                }
            }
        }

        // Promo klinik: jika ada branch_pricing, gabungkan ke rule parent.
        if ($type === 'klinik' && $parent && count($branchPricing) > 0) {
            $parent = array_merge($parent, ['branch_pricing' => $branchPricing]);
        }

        $promos[] = [
            'type'  => $type,
            'rules' => [
                'parent' => $parent,
                'staff'  => $staff, // null jika tidak ada komisi staff (klinik)
            ],
        ];
    }

    return $promos;
}

/**
 * Push satu data staff ke aplikasi eksternal.
 *
 * @param PDO $pdo
 * @param array $staff ['id'=>, 'name'=>, 'referral_code'=>, 'branch_id'=>, 'status'=>]
 * @return bool true jika berhasil (HTTP 2xx), false jika gagal
 */
function pushReferralToExternalApp(PDO $pdo, array $staff): bool
{
    $promos = buildReferralPromosPayload($pdo);

    $payload = json_encode([
        'event' => 'referral.sync',
        'data'  => [
            'staff_id'      => $staff['id'],
            'employee_name' => $staff['name'],
            'referral_code' => $staff['referral_code'],
            'branch_id'     => $staff['branch_id'],
            'status'        => $staff['status'], // 'aktif' | 'nonaktif'
            'promos'        => $promos,
        ],
        'timestamp' => date('c'),
    ]);

    // Tanda tangani payload supaya aplikasi eksternal bisa verifikasi ini benar dari kita
    if (!$payload || EXTERNAL_APP_PUSH_URL === '' || REFERRAL_SHARED_SECRET === '') {
        $error = 'Konfigurasi push referral belum lengkap';
        $response = null;
        $httpCode = 0;
        $success = false;
    } else {
        $signature = hash_hmac('sha256', $payload, REFERRAL_SHARED_SECRET);

        $ch = curl_init(EXTERNAL_APP_PUSH_URL);
        curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-Api-Key: ' . EXTERNAL_APP_API_KEY,
            'X-Signature: ' . $signature,
        ],
        ]);

        if (defined('CURL_CAINFO') && CURL_CAINFO !== '') curl_setopt($ch, CURLOPT_CAINFO, CURL_CAINFO);
        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        $success = ($httpCode >= 200 && $httpCode < 300);
    }

    // Log setiap percobaan push -> penting untuk debugging & basis retry
    $stmt = $pdo->prepare(
        "INSERT INTO referral_push_logs (staff_id, payload, http_status, response_body, success, error_message, created_at)
         VALUES (:staff_id, :payload, :http_status, :response_body, :success, :error_message, NOW())"
    );
    $stmt->execute([
        'staff_id'      => $staff['id'],
        'payload'       => $payload,
        'http_status'   => $httpCode,
        'response_body' => $response ?: null,
        'success'       => $success ? 1 : 0,
        'error_message' => $error ?: null,
    ]);

    if (!$success) {
        error_log("Push referral gagal untuk staff_id={$staff['id']}: HTTP {$httpCode} - {$error}");
    }

    return $success;
}

/**
 * CONTOH PEMAKAIAN — panggil ini setelah INSERT/UPDATE staff berhasil.
 *
 * $staff = [
 *     'id' => $newStaffId,
 *     'name' => $_POST['name'],
 *     'referral_code' => $generatedCode,
 *     'branch_id' => $_POST['branch_id'],
 *     'status' => 'aktif',
 * ];
 * pushReferralToExternalApp($pdo, $staff);
 *
 * PENTING: jangan biarkan proses simpan staff GAGAL total hanya karena push
 * ke aplikasi eksternal gagal/timeout. Simpan staff dulu ke DB kita, baru push
 * -- kalau push gagal, itu ter-log dan bisa di-retry belakangan (lihat
 * referral-push-retry.php), user (admin) tidak perlu tahu/terganggu saat itu juga.
 */
