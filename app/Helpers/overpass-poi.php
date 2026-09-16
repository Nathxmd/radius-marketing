<?php

/**
 * overpass-poi.php
 *
 * Ambil POI (point of interest) kantor + area komersial/industri dalam radius
 * tertentu dari Overpass API (OpenStreetMap). GRATIS, tanpa API key.
 *
 * PENTING — keterbatasan data:
 * Cakupan POI komersial OSM di Indonesia jauh lebih tipis dibanding Google Maps.
 * Hasil fungsi di sini adalah REFERENSI PENDUKUNG, bukan data lengkap/pasti. Jadi
 * setiap tampilan yang memakai data ini WAJIB menyertakan disclaimer sumber
 * OpenStreetMap (lihat card "Insight Area Komersial" di views/branch/detail.php).
 *
 * SOPAN TERHADAP INSTANCE PUBLIK:
 * Cukup SATU percobaan per proses simpan cabang — jangan retry otomatis berkali-kali
 * (biar user yang memicu ulang lewat tombol "Refresh Insight"). Hasilnya selalu
 * disimpan ke tabel commercial_insights, jadi halaman detail tidak query ulang.
 *
 * Bergantung pada curlApiOptions() dari geo-helpers.php (User-Agent + CA bundle).
 */

/**
 * Query Overpass untuk kantor & area komersial/industri dalam radius dari satu titik.
 *
 * @param float $lat Latitude titik pusat (cabang)
 * @param float $lon Longitude titik pusat (cabang)
 * @param int   $radiusMeter Radius dalam meter
 * @return array|null null jika request GAGAL (timeout / layanan down / HTTP error),
 *                    [] jika request berhasil tapi tidak ada POI,
 *                    atau daftar POI: [['type','id','kategori','name'], ...]
 *                    kategori: 'kantor' | 'komersial' | 'industri'
 */
function getPoiKomersialDalamRadius(float $lat, float $lon, int $radiusMeter): ?array
{
    // nwr = node/way/relation sekaligus. "out center" dipakai karena way/relation
    // hanya punya titik pusat, bukan pasangan koordinat seperti node.
    $query = '[out:json][timeout:25];('
        . 'nwr["office"](around:' . $radiusMeter . ',' . $lat . ',' . $lon . ');'
        . 'nwr["landuse"~"^(commercial|retail|industrial)$"](around:' . $radiusMeter . ',' . $lat . ',' . $lon . ');'
        . ');out center tags;';

    $ch = curl_init('https://overpass-api.de/api/interpreter');
    $options = curlApiOptions();
    $options[CURLOPT_POST]           = true;
    $options[CURLOPT_POSTFIELDS]     = http_build_query(['data' => $query]);
    $options[CURLOPT_TIMEOUT]        = 30; // instance publik kadang lambat saat sibuk
    $options[CURLOPT_CONNECTTIMEOUT] = 10;
    curl_setopt_array($ch, $options);

    $response = curl_exec($ch);
    $error    = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($error || !$response || $httpCode !== 200) {
        // Sertakan kode HTTP + potongan body (mis. "runtime error: Query timed out")
        // supaya timeout bisa dibedakan dari 429/403/instance down.
        $body = is_string($response) ? substr(trim($response), 0, 200) : '';
        error_log("Overpass POI error (lat {$lat}, lon {$lon}, {$radiusMeter}m): {$error} (HTTP {$httpCode})" . ($body !== '' ? " — {$body}" : ''));
        return null;
    }

    $data = json_decode($response, true);
    if (!is_array($data) || !isset($data['elements']) || !is_array($data['elements'])) {
        error_log("Overpass POI: respons tidak valid (HTTP {$httpCode})");
        return null;
    }

    $poiList = [];
    $seen    = [];

    foreach ($data['elements'] as $element) {
        $tags = $element['tags'] ?? [];
        $key  = ($element['type'] ?? '?') . '/' . ($element['id'] ?? '?');
        $name = isset($tags['name']) ? trim((string) $tags['name']) : '';
        $name = $name !== '' ? $name : null;

        // Satu elemen bisa masuk lebih dari satu kategori (mis. kantor di dalam
        // area landuse=industrial), jadi diklasifikasi terpisah lalu di-dedupe
        // per kategori — bukan if/elseif.
        $kategoriList = [];
        if (!empty($tags['office'])) {
            $kategoriList[] = 'kantor';
        }

        $landuse = $tags['landuse'] ?? null;
        if ($landuse === 'industrial') {
            $kategoriList[] = 'industri';
        } elseif ($landuse === 'commercial' || $landuse === 'retail') {
            $kategoriList[] = 'komersial';
        }

        foreach ($kategoriList as $kategori) {
            $dedupeKey = $key . '|' . $kategori;
            if (isset($seen[$dedupeKey])) {
                continue;
            }
            $seen[$dedupeKey] = true;

            $poiList[] = [
                'type'     => $element['type'] ?? null,
                'id'       => $element['id'] ?? null,
                'kategori' => $kategori,
                'name'     => $name,
            ];
        }
    }

    return $poiList; // [] = request berhasil, area memang belum terpetakan di OSM
}

/**
 * Ringkas hasil mentah getPoiKomersialDalamRadius() jadi angka yang dipakai UI.
 *
 * @param array $poiList Daftar POI hasil getPoiKomersialDalamRadius()
 * @return array ['jumlah_kantor' => int, 'ada_area_komersial' => bool,
 *                'ada_area_industri' => bool, 'daftar_kantor_bernama' => string[]]
 */
function ringkasPoiKomersial(array $poiList): array
{
    $jumlahKantor = 0;
    $adaKomersial = false;
    $adaIndustri  = false;
    $daftarKantor = [];

    foreach ($poiList as $poi) {
        switch ($poi['kategori'] ?? null) {
            case 'kantor':
                $jumlahKantor++;
                if (!empty($poi['name'])) {
                    $daftarKantor[$poi['name']] = true; // key array = dedupe nama
                }
                break;
            case 'komersial':
                $adaKomersial = true;
                break;
            case 'industri':
                $adaIndustri = true;
                break;
        }
    }

    $daftarKantor = array_keys($daftarKantor);
    sort($daftarKantor);

    return [
        'jumlah_kantor'         => $jumlahKantor,
        'ada_area_komersial'    => $adaKomersial,
        'ada_area_industri'     => $adaIndustri,
        'daftar_kantor_bernama' => $daftarKantor,
    ];
}
