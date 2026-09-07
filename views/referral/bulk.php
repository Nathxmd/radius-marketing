<?php $pageTitle = 'Bulk Tambah Staff'; $breadcrumb = 'Referral / Bulk Import'; ob_start(); ?>
<div class="card">
    <div class="card-header"><h2 class="card-title">Bulk Tambah Staff</h2></div>
    <div class="card-body">
        <?php if ($message = flash('error')): ?><div class="alert alert-error"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <p class="text-muted">Upload CSV dengan header <code>name,employeeCode</code>. Kode referral dibuat otomatis. Import ulang tidak membuat duplikat.</p>
        <form method="POST" enctype="multipart/form-data" action="<?php echo APP_URL; ?>/referral/bulk/store">
            <div class="form-group"><label for="staff_csv">File CSV</label><input type="file" id="staff_csv" name="staff_csv" accept=".csv,text/csv" required></div>
            <div class="save-row"><a class="btn btn-secondary" href="<?php echo APP_URL; ?>/referral">Batal</a><button class="btn btn-primary" type="submit">Import dan Generate Referral</button></div>
        </form>
    </div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/main.php'; ?>
