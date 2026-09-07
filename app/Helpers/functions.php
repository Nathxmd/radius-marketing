<?php

// View helper - render template (meng-echo output)
function view(string $view, array $data = []) {
    $viewPath = __DIR__ . "/../../views/{$view}.php";
    
    if (!file_exists($viewPath)) {
        throw new Exception("View not found: {$view}");
    }
    
    extract($data);
    ob_start();
    include $viewPath;
    echo ob_get_clean();
}

// Redirect helper
function redirect(string $route) {
    $url = APP_URL . "/" . $route;
    header("Location: {$url}");
    exit;
}

// Is authenticated
function isAuthenticated(): bool {
    return isset($_SESSION["user_id"]);
}

// Get current user
function currentUser() {
    return $_SESSION["user"] ?? null;
}

// Is admin
function isAdmin(): bool {
    $user = currentUser();
    return $user && ($user["role"] ?? "") === ROLE_ADMIN;
}

// Flash message
function flash(string $name, string $message = "") {
    if ($message) {
        $_SESSION["flash_{$name}"] = $message;
    } else {
        $msg = $_SESSION["flash_{$name}"] ?? "";
        unset($_SESSION["flash_{$name}"]);
        return $msg;
    }
}

// Old input
function old(string $key, $default = "") {
    return $_SESSION["old_input"][$key] ?? $default;
}

// Validate email
function isValidEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function generateReferralCode(PDO $pdo, string $name): string {
    $words = preg_split('/\s+/', strtoupper(trim($name)), -1, PREG_SPLIT_NO_EMPTY);
    $prefix = "STAFF";
    if (!empty($words)) {
        $prefix = preg_replace('/[^A-Z0-9]/', '', count($words) === 1 ? substr($words[0], 0, 4) : implode('', array_map(function ($word) {
            return substr($word, 0, 1);
        }, array_slice($words, 0, 4))));
        $prefix = $prefix ?: "STAFF";
    }
    $prefix = substr($prefix, 0, 8);

    for ($attempt = 0; $attempt < 10; $attempt++) {
        $code = $prefix . "-" . random_int(1000, 9999);
        $stmt = $pdo->prepare("SELECT id FROM staff WHERE referral_code = ? LIMIT 1");
        $stmt->execute([$code]);
        if (!$stmt->fetch()) {
            return $code;
        }
    }

    throw new RuntimeException("Gagal membuat kode referral unik");
}

function validReferralCode(string $code): bool {
    return strlen($code) <= 40 && preg_match('/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/', $code) === 1;
}

function normalizeEmployeeCode($value): ?string {
    $code = strtoupper(trim((string) $value));
    if ($code === '') return null;
    $code = preg_replace('/^(EMP-)+/', 'EMP-', $code);
    return preg_match('/^EMP-[A-Z0-9-]+$/', $code) ? $code : null;
}

function sanitizeParentPhone($phone): ?string {
    $phone = trim((string) $phone);
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    return $phone !== "" && strlen($phone) <= 30 ? $phone : null;
}
