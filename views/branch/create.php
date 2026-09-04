<?php
$pageTitle = "Tambah Cabang";
$breadcrumb = "Cabang / Tambah";
ob_start();
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Form Tambah Cabang</h2>
    </div>
    <div class="card-body">
        <?php if ($error = flash("error")): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($warning = flash("warning")): ?>
        <div class="alert alert-warning"><?php echo $warning; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="<?php echo APP_URL; ?>/branch/store">
            <div class="form-row">
                <div class="form-group">
                    <label for="name">Nama Cabang *</label>
                    <input type="text" id="name" name="name" value="<?php echo old("name"); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="city">Kota *</label>
                    <input type="text" id="city" name="city" value="<?php echo old("city"); ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="address">Alamat *</label>
                <input type="text" id="address" name="address" value="<?php echo old("address"); ?>" required>
                <div class="helper-text">Alamat akan dikonversi otomatis ke koordinat peta via Nominatim (OpenStreetMap)</div>
            </div>
            
            <div class="form-group">
                <label for="target_market_notes">Catatan Target Market (opsional)</label>
                <textarea id="target_market_notes" name="target_market_notes" class="notes-textarea" placeholder="Contoh: Area residensial menengah dengan banyak pasangan muda..."><?php echo old("target_market_notes"); ?></textarea>
            </div>
            
            <div class="save-row">
                <a href="<?php echo APP_URL; ?>/branch" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                        <polyline points="17 21 17 13 7 13 7 21"/>
                        <polyline points="7 3 7 8 15 8"/>
                    </svg>
                    Simpan Cabang
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . "/../layouts/main.php";
?>
