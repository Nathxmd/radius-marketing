<?php

namespace App\Controllers;

use App\Models\User;

class AdminController {
    private $userModel;
    
    public function __construct() {
        global $pdo;
        $this->userModel = new User($pdo);
    }
    
    public function users() {
        $users = $this->userModel->getAll();
        
        view("admin/users", [
            "users" => $users
        ]);
    }

    public function create() {
        view("admin/user-create");
    }

    public function store() {
        $name = trim($_POST["name"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $role = trim($_POST["role"] ?? "marketing");
        $password = $_POST["password"] ?? "";
        $confirm = $_POST["password_confirm"] ?? "";

        $_SESSION["old_input"] = $_POST;

        if (!$name || !$email || !$password) {
            flash("error", "Nama, email, dan password wajib diisi");
            redirect("admin/user/create");
            return;
        }

        if (!isValidEmail($email)) {
            flash("error", "Format email tidak valid");
            redirect("admin/user/create");
            return;
        }

        if (strlen($password) < 6) {
            flash("error", "Password minimal 6 karakter");
            redirect("admin/user/create");
            return;
        }

        if ($password !== $confirm) {
            flash("error", "Konfirmasi password tidak cocok");
            redirect("admin/user/create");
            return;
        }

        if ($role !== "admin" && $role !== "marketing") {
            $role = "marketing";
        }

        if ($this->userModel->findByEmail($email)) {
            flash("error", "Email sudah terdaftar");
            redirect("admin/user/create");
            return;
        }

        $this->userModel->create([
            "name" => $name,
            "email" => $email,
            "password_hash" => password_hash($password, PASSWORD_BCRYPT),
            "role" => $role
        ]);

        flash("success", "User berhasil ditambahkan");
        redirect("admin/users");
    }
}
