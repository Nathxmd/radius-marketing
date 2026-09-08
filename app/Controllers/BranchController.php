<?php

namespace App\Controllers;

use App\Models\Branch;
use App\Models\CoveredArea;

class BranchController {
    private $branchModel;
    private $coveredAreaModel;
    
    public function __construct() {
        global $pdo;
        $this->branchModel = new Branch($pdo);
        $this->coveredAreaModel = new CoveredArea($pdo);
    }
    
    public function index() {
        $search = $_GET["search"] ?? "";
        if ($search) {
            $branches = $this->branchModel->search($search);
        } else {
            $branches = $this->branchModel->getAll();
        }
        
        view("branch/index", ["branches" => $branches, "search" => $search]);
    }
    
    public function create() {
        view("branch/create");
    }
    
    public function store() {
        $name = trim($_POST["name"] ?? "");
        $address = trim($_POST["address"] ?? "");
        $city = trim($_POST["city"] ?? "");
        $notes = trim($_POST["target_market_notes"] ?? "");
        $manualLat = trim($_POST["latitude"] ?? "");
        $manualLon = trim($_POST["longitude"] ?? "");
        
        $_SESSION["old_input"] = $_POST;
        
        if (!$name || !$address || !$city) {
            flash("error", "Nama, alamat, dan kota wajib diisi");
            redirect("branch/create");
            return;
        }
        
        // Jika admin mengisi koordinat manual, langsung pakai itu
        if ($manualLat !== "" && $manualLon !== "") {
            $lat = (float) $manualLat;
            $lon = (float) $manualLon;
            $status = "manual";
            
            // Hitung wilayah dari koordinat manual juga
            $wilayah = getWilayahDalamRadius($lat, $lon, 5000, true);
            
            $branchId = $this->branchModel->create([
                "name" => $name,
                "address" => $address,
                "city" => $city,
                "latitude" => $lat,
                "longitude" => $lon,
                "target_market_notes" => $notes,
                "geocoding_status" => $status,
                "created_by" => currentUser()["id"] ?? null
            ]);
            
            $this->syncCoveredAreas($branchId, $wilayah);
            
            flash("success", "Cabang berhasil ditambahkan dengan koordinat manual");
            redirect("branch/" . $branchId);
            return;
        }
        
        // Auto-geocoding via Nominatim
        $geocodingResult = geocodeAddress("$address, $city");
        
        if ($geocodingResult) {
            $lat = $geocodingResult["lat"];
            $lon = $geocodingResult["lon"];
            $status = "verified";
            
            $branchId = $this->branchModel->create([
                "name" => $name,
                "address" => $address,
                "city" => $city,
                "latitude" => $lat,
                "longitude" => $lon,
                "target_market_notes" => $notes,
                "geocoding_status" => $status,
                "created_by" => currentUser()["id"] ?? null
            ]);
            
            $wilayah = getWilayahDalamRadius($lat, $lon, 5000, true);
            $this->syncCoveredAreas($branchId, $wilayah);
            
            flash("success", "Cabang berhasil ditambahkan dengan geocoding otomatis");
            redirect("branch/" . $branchId);
            
        } else {
            // Geocoding gagal - simpan tanpa koordinat, status pending
            $branchId = $this->branchModel->create([
                "name" => $name,
                "address" => $address,
                "city" => $city,
                "latitude" => null,
                "longitude" => null,
                "target_market_notes" => $notes,
                "geocoding_status" => "pending",
                "created_by" => currentUser()["id"] ?? null
            ]);
            
            flash("warning", "Geocoding gagal — alamat tidak ditemukan. Silakan edit cabang dan input koordinat manual.");
            redirect("branch/edit/" . $branchId);
        }
    }
    
    public function edit(int $id) {
        $branch = $this->branchModel->getById($id);
        if (!$branch) {
            flash("error", "Cabang tidak ditemukan");
            redirect("branch");
            return;
        }
        
        $coveredAreas = $this->coveredAreaModel->getByBranch($id);
        
        view("branch/edit", [
            "branch" => $branch,
            "covered_areas" => $coveredAreas
        ]);
    }
    
    public function update(int $id) {
        $branch = $this->branchModel->getById($id);
        if (!$branch) {
            flash("error", "Cabang tidak ditemukan");
            redirect("branch");
            return;
        }
        
        $name = trim($_POST["name"] ?? "");
        $address = trim($_POST["address"] ?? "");
        $city = trim($_POST["city"] ?? "");
        $notes = trim($_POST["target_market_notes"] ?? "");
        $manualLat = trim($_POST["latitude"] ?? "");
        $manualLon = trim($_POST["longitude"] ?? "");
        
        $_SESSION["old_input"] = $_POST;
        
        if (!$name || !$address || !$city) {
            flash("error", "Nama, alamat, dan kota wajib diisi");
            redirect("branch/edit/$id");
            return;
        }
        
        $lat = $branch["latitude"];
        $lon = $branch["longitude"];
        $status = $branch["geocoding_status"];
        $coordsChanged = false;
        
        // Jika user mengisi koordinat manual, gunakan itu
        if ($manualLat !== "" && $manualLon !== "") {
            $newLat = (float) $manualLat;
            $newLon = (float) $manualLon;
            if ((float) $lat !== $newLat || (float) $lon !== $newLon) {
                $lat = $newLat;
                $lon = $newLon;
                $status = "manual";
                $coordsChanged = true;
            }
        } else {
            // Tidak ada input manual: geocode ulang jika alamat berubah
            $addressChanged = ($address !== $branch["address"]) || ($city !== $branch["city"]);

            if ($addressChanged) {
                $geocodingResult = geocodeAddress("$address, $city");
                if ($geocodingResult) {
                    $lat = $geocodingResult["lat"];
                    $lon = $geocodingResult["lon"];
                    $status = "verified";
                    $coordsChanged = true;
                } else {
                    // Alamat baru tidak bisa di-geocode: pertahankan koordinat lama
                    flash("warning", "Geocoding alamat baru gagal — koordinat lama dipertahankan.");
                }
            }
        }
        
        $this->branchModel->update($id, [
            "name" => $name,
            "address" => $address,
            "city" => $city,
            "latitude" => $lat,
            "longitude" => $lon,
            "target_market_notes" => $notes,
            "geocoding_status" => $status
        ]);
        
        // Refresh covered areas jika koordinat berubah ATAU coverage masih kosong
        // (mis. cabang lama ditambahkan langsung ke DB sehingga belum pernah dihitung).
        $hasCoverage = !empty($this->coveredAreaModel->getByBranch($id));
        $needsRecalc = $lat !== null && ($coordsChanged || !$hasCoverage);

        if ($needsRecalc) {
            $wilayah = getWilayahDalamRadius((float) $lat, (float) $lon, 5000, true);

            if (empty($wilayah) && !$coordsChanged) {
                // Coverage memang kosong & tidak ada data lama yang dihapus: biarkan,
                // beri tahu admin supaya bisa cek koneksi / pakai tombol proses ulang.
                flash("warning", "Wilayah cakupan belum terhitung — BIG Geoservice tidak merespons. Coba tombol 'Proses Ulang Wilayah'.");
            } else {
                $this->syncCoveredAreas($id, $wilayah);
            }
        }
        
        flash("success", "Cabang berhasil diupdate");
        redirect("branch/" . $id);
    }
    
    /**
     * Simpan catatan target market saja (dari halaman detail).
     */
    public function updateNotes(int $id) {
        $branch = $this->branchModel->getById($id);
        if (!$branch) {
            flash("error", "Cabang tidak ditemukan");
            redirect("branch");
            return;
        }
        
        $notes = trim($_POST["target_market_notes"] ?? "");
        
        $this->branchModel->updateNotes($id, $notes);
        
        flash("success", "Catatan target market berhasil disimpan");
        redirect("branch/" . $id);
    }
    
    /**
     * Hitung ulang wilayah cakupan (covered_areas) dari koordinat yang tersimpan.
     * Dipicu manual oleh admin lewat tombol di halaman detail cabang.
     */
    public function reprocess(int $id) {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            flash("error", "Proses ulang wilayah harus dilakukan melalui formulir");
            redirect("branch");
            return;
        }

        $branch = $this->branchModel->getById($id);
        if (!$branch) {
            flash("error", "Cabang tidak ditemukan");
            redirect("branch");
            return;
        }

        if ($branch["latitude"] === null || $branch["longitude"] === null) {
            flash("error", "Koordinat belum tersedia — input koordinat manual terlebih dahulu");
            redirect("branch/edit/" . $id);
            return;
        }

        $wilayah = getWilayahDalamRadius((float) $branch["latitude"], (float) $branch["longitude"], 5000, true);

        if (empty($wilayah)) {
            // Jangan panggil syncCoveredAreas supaya data lama tidak terhapus saat layanan error.
            flash("warning", "BIG Geoservice tidak mengembalikan data wilayah. Data lama (jika ada) dipertahankan. Cek koneksi server / error_log lalu coba lagi.");
            redirect("branch/" . $id);
            return;
        }

        $this->syncCoveredAreas($id, $wilayah);

        flash("success", "Wilayah cakupan diperbarui: " . count($wilayah) . " wilayah");
        redirect("branch/" . $id);
    }
    
    public function delete(int $id) {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            flash("error", "Penghapusan cabang harus dilakukan melalui formulir");
            redirect("branch");
            return;
        }

        $branch = $this->branchModel->getById($id);
        if (!$branch) {
            flash("error", "Cabang tidak ditemukan");
            redirect("branch");
            return;
        }

        if ($this->branchModel->delete($id)) {
            flash("success", "Cabang berhasil dihapus");
        } else {
            flash("error", "Gagal menghapus cabang");
        }

        redirect("branch");
    }
    
    public function detail(int $id) {
        $branch = $this->branchModel->getById($id);
        if (!$branch) {
            flash("error", "Cabang tidak ditemukan");
            redirect("branch");
            return;
        }

        $perPage = 10;
        $page = max(1, (int) ($_GET["page"] ?? 1));
        $offset = ($page - 1) * $perPage;

        $coveredAreas = $this->coveredAreaModel->getByBranch($id);
        $totalKelurahan = $this->coveredAreaModel->countByBranchAndType($id, "kelurahan");
        $totalKecamatan = $this->coveredAreaModel->countByBranchAndType($id, "kecamatan");
        $totalDesa = $this->coveredAreaModel->countByBranchAndType($id, "desa");

        $kelurahanList = $this->coveredAreaModel->getByBranchAndType($id, "kelurahan", $perPage, $offset);
        $totalKelurahanPages = max(1, (int) ceil($totalKelurahan / $perPage));
        $page = min($page, $totalKelurahanPages);

        view("branch/detail", [
            "branch" => $branch,
            "covered_areas" => $coveredAreas,
            "kelurahan_list" => $kelurahanList,
            "total_kelurahan" => $totalKelurahan,
            "total_kecamatan" => $totalKecamatan,
            "total_desa" => $totalDesa,
            "kelurahan_page" => $page,
            "kelurahan_pages" => $totalKelurahanPages,
        ]);
    }
    
    /**
     * Hapus covered_areas lama untuk branch, lalu insert yang baru.
     */
    private function syncCoveredAreas(int $branchId, ?array $wilayah) {
        $this->coveredAreaModel->deleteByBranch($branchId);
        
        if (empty($wilayah)) {
            return;
        }
        
        foreach ($wilayah as $area) {
            $this->coveredAreaModel->create([
                "branch_id" => $branchId,
                "area_name" => $area["area_name"],
                "area_type" => $area["area_type"],
                "kecamatan" => $area["kecamatan"] ?? null,
                "kabupaten_kota" => $area["kabupaten_kota"] ?? null,
                "provinsi" => $area["provinsi"] ?? null,
                "source" => $area["source"] ?? "polygon",
                "distance_km" => $area["distance_km"] ?? null
            ]);
        }
    }
}
