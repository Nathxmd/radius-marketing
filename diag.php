<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

header("Content-Type: text/plain; charset=utf-8");
echo "===== PHP INFO =====" . PHP_EOL;
echo "PHP version: " . phpversion() . PHP_EOL;
echo "Server: " . ($_SERVER["SERVER_SOFTWARE"] ?? "n/a") . PHP_EOL;

echo PHP_EOL . "===== .env presence =====" . PHP_EOL;
$envFile = __DIR__ . "/.env";
echo "env file exists: " . (file_exists($envFile) ? "YES" : "NO") . PHP_EOL;
echo "cwd: " . getcwd() . PHP_EOL;

echo PHP_EOL . "===== Load .env (redacted) =====" . PHP_EOL;
$parsed = [];
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === "" || strpos($line, "#") === 0 || strpos($line, "=") === false) continue;
        list($k, $v) = explode("=", $line, 2);
        $k = trim($k); $v = trim($v);
        if (strlen($v) >= 2 && (($v[0] === '"' && $v[strlen($v)-1] === '"') || ($v[0] === "'" && $v[strlen($v)-1] === "'"))) {
            $v = substr($v, 1, -1);
        }
        $parsed[$k] = $v;
    }
}
foreach ($parsed as $k => $v) {
    if (stripos($k, "PASSWORD") !== false || stripos($k, "SECRET") !== false) {
        echo "$k = [HIDDEN]" . PHP_EOL;
    } else {
        echo "$k = $v" . PHP_EOL;
    }
}
if (!$parsed) echo "(empty or not parsed)" . PHP_EOL;

echo PHP_EOL . "===== DB Connection test =====" . PHP_EOL;
$db_host = $parsed["DB_HOST"] ?? "localhost";
$db_port = $parsed["DB_PORT"] ?? "3306";
$db_name = $parsed["DB_NAME"] ?? "";
$db_user = $parsed["DB_USER"] ?? "";
$db_pass = $parsed["DB_PASSWORD"] ?? "";

try {
    $pdo = new PDO(
        "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
    );
    echo "DB connection: OK" . PHP_EOL;
    echo "Server version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . PHP_EOL;
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables: " . implode(", ", $tables) . PHP_EOL;
} catch (Exception $e) {
    echo "DB connection: FAIL - " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "===== Last PHP error in log =====" . PHP_EOL;
$logFile = ini_get("error_log");
echo "error_log path: " . ($logFile ?: "(none)") . PHP_EOL;
$candidates = [__DIR__ . "/error_log", __DIR__ . "/logs/error.log", ini_get("error_log")];
$shown = false;
foreach ($candidates as $c) {
    if ($c && file_exists($c)) {
        echo "--- tail of " . $c . " ---" . PHP_EOL;
        $lines = array_slice(file($c), -15);
        echo implode("", $lines) . PHP_EOL;
        $shown = true;
    }
}
if (!$shown) echo "(no log file found in common locations)" . PHP_EOL;

echo PHP_EOL . "===== index.php syntax check =====" . PHP_EOL;
if (file_exists(__DIR__ . "/index.php")) {
    echo "index.php exists, size=" . filesize(__DIR__ . "/index.php") . PHP_EOL;
} else {
    echo "index.php MISSING!" . PHP_EOL;
}
echo PHP_EOL . "DONE" . PHP_EOL;
