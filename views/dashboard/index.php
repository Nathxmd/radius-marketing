<?php
$pageTitle = "Dashboard";
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
        <div class="stat-value primary"><?php echo $total_branches; ?></div>
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
                    <td><?php echo $branch["total_areas"]; ?> wilayah</td>
                    <td>
                        <a href="<?php echo APP_URL; ?>/branch/<?php echo $branch["id"]; ?>" class="btn-text">Lihat</a>
                        <a href="<?php echo APP_URL; ?>/branch/edit/<?php echo $branch["id"]; ?>" class="btn-text">Edit</a>
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
