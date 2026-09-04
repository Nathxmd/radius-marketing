<?php

namespace App\Controllers;

use App\Models\Branch;

class DashboardController {
    private $branchModel;
    
    public function __construct() {
        global $pdo;
        $this->branchModel = new Branch($pdo);
    }
    
    public function index() {
        $branches = $this->branchModel->getAll();
        $totalBranches = count($branches);
        
        view("dashboard/index", [
            "total_branches" => $totalBranches,
            "branches" => $branches
        ]);
    }
}
