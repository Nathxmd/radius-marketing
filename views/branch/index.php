<?php
$pageTitle = "Cabang";
$breadcrumb = "";
ob_start();
?>

<?php if ($success = flash("success")): ?>
<div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if ($warning = flash("warning")): ?>
<div class="alert alert-warning"><?php echo $warning; ?></div>
<?php endif; ?>

<div class="stat-row">
    <div class="stat-card">
        <div class="stat-label">Total Cabang</div>
        <div class="stat-value primary"><?php echo count($branches); ?></div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Daftar Cabang</h2>
        <a href="<?php echo APP_URL; ?>/branch/create" class="btn btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 5v14M5 12h14"/>
            </svg>
            Tambah Cabang
        </a>
    </div>
    <div class="card-body">
        <form method="GET" action="<?php echo APP_URL; ?>/branch" style="margin-bottom: 16px; display: flex; gap: 8px;">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search ?? ""); ?>" placeholder="Cari nama cabang / kota..." style="flex:1; height:36px; padding:0 12px; border:1px solid var(--color-border); border-radius:var(--radius-sm); font-size:13px;">
            <button type="submit" class="btn btn-secondary">Cari</button>
        </form>

        <?php if (empty($branches)): ?>
        <p class="text-muted">Belum ada cabang. Klik "Tambah Cabang" untuk memulai.</p>
        <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Nama Cabang</th>
                    <th>Kota</th>
                    <th>Status Geocoding</th>
                    <th>Wilayah Cakupan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($branches as $branch): ?>
                <tr>
                    <td class="text-strong"><?php echo htmlspecialchars($branch["name"]); ?></td>
                    <td><?php echo htmlspecialchars($branch["city"]); ?></td>
                    <td>
                        <?php
                        $badgeClass = $branch["geocoding_status"] === "verified" ? "badge-success"
                            : ($branch["geocoding_status"] === "manual" ? "badge-info"
                            : ($branch["geocoding_status"] === "pending" ? "badge-warning" : "badge-error"));
                        ?>
                        <span class="badge <?php echo $badgeClass; ?>"><?php echo ucfirst($branch["geocoding_status"]); ?></span>
                    </td>
                    <td><?php echo (int) $branch["total_areas"]; ?> wilayah</td>
                    <td>
                        <a href="<?php echo APP_URL; ?>/branch/<?php echo $branch["id"]; ?>" class="btn-text">Lihat</a>
                        <a href="<?php echo APP_URL; ?>/branch/edit/<?php echo $branch["id"]; ?>" class="btn-text">Edit</a>
                        <?php if (isAdmin()): ?>
                        <form method="POST" action="<?php echo APP_URL; ?>/branch/delete/<?php echo $branch["id"]; ?>" style="display:inline" onsubmit="return confirm('Hapus cabang ini beserta wilayah cakupannya? Tindakan ini tidak dapat dibatalkan.');">
                            <button type="submit" class="btn-text" style="color:var(--color-error); border:0; background:none; cursor:pointer; padding:0;">Hapus</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . "/../layouts/main.php";
?>
