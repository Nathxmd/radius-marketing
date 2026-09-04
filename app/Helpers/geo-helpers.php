<?php

/**
 * geo-helpers.php
 *
 * Kumpulan fungsi untuk:
 * 1. Geocoding alamat cabang -> koordinat (via Nominatim / OpenStreetMap)
 * 2. Mencari wilayah kelurahan/kecamatan dalam radius tertentu dari satu titik (via BIG Geoservice)
 *
 * Kedua layanan ini GRATIS, tapi:
 * - Nominatim: wajib max 1 request/detik, wajib kirim header User-Agent yang jelas.
 * - BIG Geoservice: tidak ada dokumentasi rate limit resmi, tapi tetap perlakukan
 *   dengan sopan (jangan panggil berkali-kali tanpa perlu). Simpan hasilnya ke DB,
 *   jangan query ulang setiap kali halaman dibuka.
 */

// ============================================================
// 1. GEOCODING — Nominatim (OpenStreetMap)
// ============================================================

/**
 * Opsi curl umum: timeout, User-Agent, dan CA bundle bila dikonfigurasi.
 * @return array
 */
function curlApiOptions(): array
{
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'User-Agent: ' . NOMINATIM_USER_AGENT,
        ],
    ];

    if (defined("CURL_CAINFO") && CURL_CAINFO !== "") {
        $opts[CURLOPT_CAINFO] = CURL_CAINFO;
    }

    return $opts;
}

/**
 * Konversi alamat menjadi koordinat (latitude, longitude).
 *
 * @param string $address Alamat lengkap, misal "Boulevard Harapan Indah, Bekasi"
 * @return array|null ['lat' => float, 'lon' => float, 'display_name' => string] atau null jika tidak ketemu
 */
function geocodeAddress(string $address): ?array
{
    $url = "https://nominatim.openstreetmap.org/search?" . http_build_query([
        'q'              => $address,
        'format'         => 'json',
        'limit'          => 1,
        'countrycodes'   => 'id', // batasi ke Indonesia biar hasil lebih relevan
        'addressdetails' => 1,
    ]);

    $ch = curl_init($url);
    $options = curlApiOptions();
    $options[CURLOPT_TIMEOUT] = 10; // WAJIB: Nominatim usage policy mengharuskan User-Agent yang jelas & bisa dihubungi
    curl_setopt_array($ch, $options);

    $response = curl_exec($ch);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error || !$response) {
        error_log("Geocoding error: " . $error);
        return null;
    }

    $data = json_decode($response, true);

    if (empty($data) || !isset($data[0]['lat'], $data[0]['lon'])) {
        return null; // alamat tidak ditemukan -> perlu input koordinat manual oleh admin
    }

    return [
        'lat'          => (float) $data[0]['lat'],
        'lon'          => (float) $data[0]['lon'],
        'display_name' => $data[0]['display_name'] ?? '',
    ];
}

// ============================================================
// 2. WILAYAH DALAM RADIUS — BIG Geoservice
// ============================================================

/**
 * Query satu layer Batas_Administrasi BIG Geoservice.
 *
 * Struktur MapServer:
 *   - Layer 0: Batas Wilayah Kabupaten/Kota
 *   - Layer 1: Batas Wilayah Kecamatan       (WADMKC, TIPADM=3)
 *   - Layer 2: Batas Wilayah Kelurahan/Desa  (WADMKD, TIPADM=1 desa / 2 kelurahan)
 *
 * @param float $lat Latitude titik pusat (cabang)
 * @param float $lon Longitude titik pusat (cabang)
 * @param int   $radiusMeter Radius dalam meter
 * @param int   $layerId ID layer (1 = kecamatan, 2 = kelurahan/desa)
 * @param bool  $includeGeometry Sertakan polygon geometry atau tidak
 * @return array Daftar feature mentah (properties), atau [] jika gagal/tidak ada
 */
function queryBIGWilayah(float $lat, float $lon, int $radiusMeter, int $layerId, bool $includeGeometry = false): array
{
    $geometry = json_encode([
        'x' => $lon,
        'y' => $lat,
        'spatialReference' => ['wkid' => 4326],
    ]);

    $params = [
        'f'              => 'geojson',
        'geometry'       => $geometry,
        'geometryType'   => 'esriGeometryPoint',
        'inSR'           => 4326,
        'distance'       => $radiusMeter,
        'units'          => 'esriSRUnit_Meter',
        'spatialRel'     => 'esriSpatialRelIntersects',
        'outFields'      => 'WADMKD,WADMKC,WADMKK,WADMPR,TIPADM',
        'returnGeometry' => $includeGeometry ? 'true' : 'false',
        'outSR'          => 4326,
    ];

    $url = "https://geoservices.big.go.id/gis/rest/services/BAPANAS/Batas_Administrasi/MapServer/{$layerId}/query?"
        . http_build_query($params);

    $ch = curl_init($url);
    $options = curlApiOptions();
    $options[CURLOPT_TIMEOUT] = 15; // layanan gov kadang lambat, kasih timeout lebih longgar
    curl_setopt_array($ch, $options);

    $response = curl_exec($ch);
    $error    = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($error || $httpCode !== 200 || !$response) {
        error_log("BIG geoservice error (layer {$layerId}): {$error} (HTTP {$httpCode})");
        return [];
    }

    $data = json_decode($response, true);

    if (empty($data['features']) || !is_array($data['features'])) {
        return [];
    }

    $features = [];
    foreach ($data['features'] as $feature) {
        $features[] = [
            'properties' => $feature['properties'] ?? [],
            'geometry'   => $includeGeometry ? ($feature['geometry'] ?? null) : null,
        ];
    }

    return $features;
}

/**
 * Cari kelurahan/kecamatan yang bersinggungan dengan radius tertentu dari satu titik.
 *
 * Query layer 2 (kelurahan/desa) DAN layer 1 (kecamatan) lalu gabungkan hasilnya.
 *
 * @param float $lat Latitude titik pusat (cabang)
 * @param float $lon Longitude titik pusat (cabang)
 * @param int   $radiusMeter Radius dalam meter, default 5000 (5km)
 * @param bool  $includeGeometry Sertakan polygon geometry (untuk digambar di peta) atau tidak
 * @return array Daftar wilayah, masing-masing ['area_name'=>, 'area_type'=>, 'kecamatan'=>, 'kabupaten_kota'=>, 'provinsi'=>, 'geometry'=>]
 */
function getWilayahDalamRadius(float $lat, float $lon, int $radiusMeter = 5000, bool $includeGeometry = false): array
{
    // Layer 2: kelurahan/desa (WADMKD terisi)
    $kelurahan = queryBIGWilayah($lat, $lon, $radiusMeter, 2, $includeGeometry);

    // Layer 1: kecamatan (WADMKD kosong, fallback ke WADMKC)
    $kecamatan = queryBIGWilayah($lat, $lon, $radiusMeter, 1, $includeGeometry);

    $results = [];
    $seen    = [];

    $appendArea = function (array $props, $geometry) use (&$results, &$seen, $lat, $lon) {
        // TIPADM: 1 = Desa, 2 = Kelurahan, 3 = Kecamatan
        $areaType = match ($props['TIPADM'] ?? null) {
            3       => 'kecamatan',
            1       => 'desa',
            default => 'kelurahan',
        };

        if ($areaType === 'kecamatan') {
            $name = $props['WADMKC'] ?? null;
            $kec  = $name;
        } else {
            $name = $props['WADMKD'] ?? null;
            $kec  = $props['WADMKC'] ?? null;
        }

        if (!$name) {
            return;
        }

        // dedupe berdasarkan kombinasi nama wilayah + kecamatan
        $key = $name . '|' . $kec;
        if (isset($seen[$key])) {
            return;
        }
        $seen[$key] = true;

        $results[] = [
            'area_name'      => $name,
            'area_type'      => $areaType,
            'kecamatan'      => $kec,
            'kabupaten_kota' => $props['WADMKK'] ?? null,
            'provinsi'       => $props['WADMPR'] ?? null,
            'geometry'       => $geometry,
            'distance_km'    => calculateGeometryDistanceKm($lat, $lon, $geometry),
        ];
    };

    foreach ($kelurahan as $feature) {
        $appendArea($feature['properties'], $feature['geometry']);
    }

    foreach ($kecamatan as $feature) {
        $appendArea($feature['properties'], $feature['geometry']);
    }

    return $results;
}

function calculateGeometryDistanceKm(float $lat, float $lon, ?array $geometry): ?float
{
    if (empty($geometry['coordinates'])) {
        return null;
    }

    $points = [];
    $collectPoints = function ($coordinates) use (&$collectPoints, &$points) {
        if (is_array($coordinates) && isset($coordinates[0], $coordinates[1]) && is_numeric($coordinates[0]) && is_numeric($coordinates[1])) {
            $points[] = [(float) $coordinates[1], (float) $coordinates[0]];
            return;
        }
        if (is_array($coordinates)) {
            foreach ($coordinates as $coordinate) {
                $collectPoints($coordinate);
            }
        }
    };
    $collectPoints($geometry['coordinates']);

    if (empty($points)) {
        return null;
    }

    $centerLat = array_sum(array_column($points, 0)) / count($points);
    $centerLon = array_sum(array_column($points, 1)) / count($points);
    $earthRadiusKm = 6371;
    $latDelta = deg2rad($centerLat - $lat);
    $lonDelta = deg2rad($centerLon - $lon);
    $a = sin($latDelta / 2) ** 2 + cos(deg2rad($lat)) * cos(deg2rad($centerLat)) * sin($lonDelta / 2) ** 2;
    return round($earthRadiusKm * 2 * asin(min(1, sqrt($a))), 2);
}
