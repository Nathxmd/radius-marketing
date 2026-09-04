<?php

namespace App\Controllers;

use App\Models\User;
use PDO;

class AuthController {
    private $pdo;
    private $userModel;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->userModel = new User($pdo);
    }
    
    public function login() {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $email = trim($_POST["email"] ?? "");
            $password = $_POST["password"] ?? "";
            
            $user = $this->userModel->findByEmail($email);
            
            if ($user && password_verify($password, $user["password_hash"])) {
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["user"] = $user;
                
                flash("success", "Login berhasil!");
                redirect("dashboard");
            } else {
                flash("error", "Email atau password salah");
                view("auth/login", ["email" => $email]);
            }
        } else {
            view("auth/login");
        }
    }
    
    public function doLogin() {
        $email = trim($_POST["email"] ?? "");
        $password = $_POST["password"] ?? "";
        
        $user = $this->userModel->findByEmail($email);
        
        if ($user && password_verify($password, $user["password_hash"])) {
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["user"] = $user;
            
            flash("success", "Login berhasil!");
            redirect("dashboard");
        } else {
            flash("error", "Email atau password salah");
            $_SESSION["old_input"] = ["email" => $email];
            redirect("login");
        }
    }
    
    public function logout() {
        session_destroy();
        redirect("login");
    }
}
