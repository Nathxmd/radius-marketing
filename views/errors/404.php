<?php
$pageTitle = "404";
ob_start();
?>

<div class="card">
    <div class="card-body" style="text-align:center; padding:48px 24px;">
        <h2 style="font-size:28px; margin:0 0 8px;">404</h2>
        <p class="text-muted" style="margin:0 0 24px;">Halaman tidak ditemukan.</p>
        <a href="<?php echo APP_URL; ?>/dashboard" class="btn btn-primary">Kembali ke Dashboard</a>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . "/../layouts/main.php";
?>
