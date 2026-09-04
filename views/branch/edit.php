<?php
$pageTitle = "Edit Cabang: " . $branch["name"];
$breadcrumb = "Cabang / Edit";
ob_start();
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Form Edit Cabang</h2>
    </div>
    <div class="card-body">
        <?php if ($error = flash("error")): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($warning = flash("warning")): ?>
        <div class="alert alert-warning"><?php echo $warning; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="<?php echo APP_URL; ?>/branch/update/<?php echo $branch["id"]; ?>">
            <div class="form-row">
                <div class="form-group">
                    <label for="name">Nama Cabang *</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars(old("name", $branch["name"])); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="city">Kota *</label>
                    <input type="text" id="city" name="city" value="<?php echo htmlspecialchars(old("city", $branch["city"])); ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="address">Alamat *</label>
                <input type="text" id="address" name="address" value="<?php echo htmlspecialchars(old("address", $branch["address"])); ?>" required>
            </div>
            
            <div class="form-section">
                <h3 class="form-section-title">Koordinat (opsional - biarkan kosong untuk auto-geocoding)</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="latitude">Latitude</label>
                        <input type="number" step="0.0000001" id="latitude" name="latitude" value="<?php echo htmlspecialchars(old("latitude", $branch["latitude"] ?? "")); ?>" placeholder="Contoh: -6.1829">
                    </div>
                    <div class="form-group">
                        <label for="longitude">Longitude</label>
                        <input type="number" step="0.0000001" id="longitude" name="longitude" value="<?php echo htmlspecialchars(old("longitude", $branch["longitude"] ?? "")); ?>" placeholder="Contoh: 106.9860">
                    </div>
                </div>
                <div class="helper-text">Input koordinat manual jika geocoding kurang akurat</div>
            </div>
            
            <div class="form-section">
                <h3 class="form-section-title">Catatan Target Market</h3>
                <div class="form-group">
                    <label for="target_market_notes">Catatan marketing untuk cabang ini</label>
                    <textarea id="target_market_notes" name="target_market_notes" class="notes-textarea" placeholder="Contoh: Area residensial menengah dengan banyak pasangan muda dual-income, dekat kawasan perkantoran..."><?php echo htmlspecialchars(old("target_market_notes", $branch["target_market_notes"] ?? "")); ?></textarea>
                </div>
                <div class="helper-text">Catatan ini terlihat oleh seluruh tim marketing dan tersimpan di detail cabang.</div>
            </div>
            
            <div class="save-row">
                <a href="<?php echo APP_URL; ?>/branch/<?php echo $branch["id"]; ?>" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Update Cabang</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . "/../layouts/main.php";
?>
