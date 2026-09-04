<?php
$pageTitle = "Cabang: " . $branch["name"];
$breadcrumb = "Cabang / " . $branch["name"];
ob_start();
?>

<?php if ($success = flash("success")): ?>
<div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<div class="stat-row">
    <div class="stat-card">
        <div class="stat-label">Radius analisis</div>
        <div class="stat-value accent">5 km</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Wilayah tercakup</div>
        <div class="stat-value primary"><?php echo count($covered_areas); ?> wilayah</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Koordinat cabang</div>
        <div class="stat-value" style="font-size:15px;"><?php echo $branch["latitude"] ? number_format($branch["latitude"], 4) . ", " . number_format($branch["longitude"], 4) : "-"; ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Status Geocoding</div>
        <div class="stat-value">
            <?php
            $badgeClass = $branch["geocoding_status"] === "verified" ? "badge-success"
                : ($branch["geocoding_status"] === "manual" ? "badge-info"
                : ($branch["geocoding_status"] === "pending" ? "badge-warning" : "badge-error"));
            ?>
            <span class="badge <?php echo $badgeClass; ?>"><?php echo ucfirst($branch["geocoding_status"]); ?></span>
        </div>
    </div>
</div>

<div class="grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Peta Radius 5km</h2>
        </div>
        <div class="map-wrap" id="map" data-lat="<?php echo $branch["latitude"]; ?>" data-lon="<?php echo $branch["longitude"]; ?>">
            <?php if (!$branch["latitude"]): ?>
            <div class="map-placeholder">
                <p>Koordinat belum tersedia</p>
                <a href="<?php echo APP_URL; ?>/branch/edit/<?php echo $branch["id"]; ?>" class="btn btn-secondary">Input Koordinat Manual</a>
            </div>
            <?php endif; ?>
        </div>
        <div class="map-attribution">© OpenStreetMap contributors</div>
    </div>
    
    <div style="display:flex; flex-direction:column; gap:20px;">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Wilayah Cakupan</h2>
            </div>
            <div class="card-body">
                <?php if (empty($covered_areas)): ?>
                <p class="text-muted">Belum ada data wilayah. Data akan otomatis terisi setelah geocoding berhasil.</p>
                <?php else: ?>
                <div class="tabs">
                    <button type="button" class="tab-btn is-active" data-tab="kelurahan">
                        Kelurahan <span class="tab-count"><?php echo $total_kelurahan; ?></span>
                    </button>
                    <button type="button" class="tab-btn" data-tab="kecamatan">
                        Kecamatan <span class="tab-count"><?php echo $total_kecamatan; ?></span>
                    </button>
                </div>

                <div class="tab-panel is-active" id="tab-kelurahan">
                    <?php if (empty($kelurahan_list)): ?>
                    <p class="text-muted">Belum ada kelurahan yang tercakup.</p>
                    <?php else: ?>
                    <ul class="area-list">
                        <?php foreach ($kelurahan_list as $area): ?>
                        <li class="area-item">
                            <div>
                                <div class="area-name"><?php echo htmlspecialchars($area["area_name"]); ?></div>
                                <div class="area-meta"><?php echo htmlspecialchars($area["kecamatan"] . ", " . $area["kabupaten_kota"]); ?><?php if ($area["distance_km"] !== null): ?> · ±<?php echo number_format((float) $area["distance_km"], 2, ",", "."); ?> km<?php endif; ?></div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($kelurahan_pages > 1): ?>
                    <div class="pagination">
                        <a href="<?php echo APP_URL; ?>/branch/<?php echo $branch["id"]; ?>?page=<?php echo max(1, $kelurahan_page - 1); ?>#tab-kelurahan" class="page-btn <?php echo $kelurahan_page <= 1 ? "is-disabled" : ""; ?>">‹</a>
                        <?php for ($i = 1; $i <= $kelurahan_pages; $i++): ?>
                        <a href="<?php echo APP_URL; ?>/branch/<?php echo $branch["id"]; ?>?page=<?php echo $i; ?>#tab-kelurahan" class="page-btn <?php echo $i === $kelurahan_page ? "is-active" : ""; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                        <a href="<?php echo APP_URL; ?>/branch/<?php echo $branch["id"]; ?>?page=<?php echo min($kelurahan_pages, $kelurahan_page + 1); ?>#tab-kelurahan" class="page-btn <?php echo $kelurahan_page >= $kelurahan_pages ? "is-disabled" : ""; ?>">›</a>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="tab-panel" id="tab-kecamatan">
                    <?php if ($total_kecamatan === 0): ?>
                    <p class="text-muted">Belum ada kecamatan yang tercakup.</p>
                    <?php else: ?>
                    <ul class="area-list">
                        <?php foreach ($covered_areas as $area): ?>
                        <?php if ($area["area_type"] !== "kecamatan") continue; ?>
                        <li class="area-item">
                            <div>
                                <div class="area-name"><?php echo htmlspecialchars($area["area_name"]); ?></div>
                                <div class="area-meta"><?php echo htmlspecialchars($area["kabupaten_kota"] ?? ""); ?><?php if ($area["distance_km"] !== null): ?> · ±<?php echo number_format((float) $area["distance_km"], 2, ",", "."); ?> km<?php endif; ?></div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Info Cabang</h2>
            </div>
            <div class="card-body">
                <div class="branch-info-row">
                    <span class="branch-info-label">Nama</span>
                    <span class="branch-info-value"><?php echo htmlspecialchars($branch["name"]); ?></span>
                </div>
                <div class="branch-info-row">
                    <span class="branch-info-label">Alamat</span>
                    <span class="branch-info-value"><?php echo htmlspecialchars($branch["address"]); ?></span>
                </div>
                <div class="branch-info-row">
                    <span class="branch-info-label">Kota</span>
                    <span class="branch-info-value"><?php echo htmlspecialchars($branch["city"]); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Catatan Target Market</h2>
    </div>
    <div class="card-body">
        <form method="POST" action="<?php echo APP_URL; ?>/branch/notes/<?php echo $branch["id"]; ?>">
            <textarea name="target_market_notes" class="notes-textarea" placeholder="Tulis catatan target market untuk cabang ini..."><?php echo htmlspecialchars($branch["target_market_notes"] ?? ""); ?></textarea>
            <div class="helper-text">Catatan ini terlihat oleh seluruh tim marketing dan tersimpan otomatis ke riwayat cabang.</div>
            <div class="save-row">
                <button type="submit" class="btn btn-primary">Simpan Catatan</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
$actions = '
<a href="' . APP_URL . '/branch/edit/' . $branch["id"] . '" class="btn btn-secondary">Edit Cabang</a>
' . (isAdmin() ? '<form method="POST" action="' . APP_URL . '/branch/delete/' . $branch["id"] . '" style="display:inline" onsubmit="return confirm(\'Hapus cabang ini beserta wilayah cakupannya? Tindakan ini tidak dapat dibatalkan.\');"><button type="submit" class="btn btn-secondary" style="color:var(--color-error);">Hapus Cabang</button></form>' : '') . '
';
include __DIR__ . "/../layouts/main.php";
?>
