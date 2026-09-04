<?php

// Entry point
session_start();

// Load config
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/config/app.php";

// Autoload (composer)
$autoload = __DIR__ . "/vendor/autoload.php";
if (file_exists($autoload)) {
    require_once $autoload;
}

// Fallback autoloader untuk App\ (jika composer belum di-install)
spl_autoload_register(function ($class) {
    if (strpos($class, "App\\") === 0) {
        $path = __DIR__ . "/app/" . str_replace("\\", "/", substr($class, 4)) . ".php";
        if (file_exists($path)) {
            require_once $path;
        }
    }
});

// Helper functions
require_once __DIR__ . "/app/Helpers/functions.php";
require_once __DIR__ . "/app/Helpers/geo-helpers.php";

// Routing
$route = $_GET["route"] ?? "dashboard";
$routes = require_once __DIR__ . "/config/routes.php";

// Check if route is a dynamic pattern like "branch/{id}"
$matched = null;
$params = [];

foreach ($routes as $pattern => $config) {
    if (strpos($pattern, "{id}") !== false) {
        $regex = "#^" . preg_quote($pattern, "#") . "$#";
        $regex = str_replace("\\{id\\}", "(\d+)", $regex);
        if (preg_match($regex, $route, $matches)) {
            $matched = $config;
            $params["id"] = (int) $matches[1];
            break;
        }
    }
}

// Fallback: exact match
if (!$matched && isset($routes[$route])) {
    $matched = $routes[$route];
}

if ($matched) {
    $controller = $matched["controller"];
    $action = $matched["action"];
    
    // Check auth
    if ($matched["auth"] ?? true) {
        if (!isAuthenticated()) {
            redirect("login");
            exit;
        }
    }
    
    // Check role restriction
    if (isset($matched["role"]) && !isAdmin()) {
        flash("error", "Anda tidak memiliki akses ke halaman ini");
        redirect("dashboard");
        exit;
    }
    
    $controllerFile = $controller . "Controller";
    $controllerPath = __DIR__ . "/app/Controllers/{$controllerFile}.php";
    if (file_exists($controllerPath)) {
        require_once $controllerPath;
        $class = "App\\Controllers\\{$controllerFile}";
        $controllerObj = new $class();
        $controllerObj->$action(...array_values($params));
    } else {
        http_response_code(404);
        view("errors/404");
    }
} else {
    http_response_code(404);
    view("errors/404");
}
