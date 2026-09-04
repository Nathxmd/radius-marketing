<?php

// Load .env
$envFile = __DIR__ . "/../.env";
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === "" || strpos($line, "#") === 0 || strpos($line, "=") === false) {
            continue;
        }
        list($key, $value) = explode("=", $line, 2);
        $key = trim($key);
        $value = trim($value);
        // Strip wrapping quotes
        if (strlen($value) >= 2 && (($value[0] === '"' && $value[strlen($value) - 1] === '"') || ($value[0] === "'" && $value[strlen($value) - 1] === "'"))) {
            $value = substr($value, 1, -1);
        }
        putenv("$key=$value");
    }
}

// Database config
$db_host = getenv("DB_HOST") ?: "localhost";
$db_port = getenv("DB_PORT") ?: 3306;
$db_name = getenv("DB_NAME") ?: "radius_sistem";
$db_user = getenv("DB_USER") ?: "root";
$db_pass = getenv("DB_PASSWORD") ?: "";

// PDO connection
try {
    $pdo = new PDO(
        "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    error_log("[Database] Connection failed: " . $e->getMessage());
    die("Database connection failed. Check config.");
}
