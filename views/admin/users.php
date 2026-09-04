<?php
$pageTitle = "Pengaturan User";
$breadcrumb = "Pengaturan / User";
ob_start();
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Daftar User</h2>
        <a href="<?php echo APP_URL; ?>/admin/user/create" class="btn btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 5v14M5 12h14"/>
            </svg>
            Tambah User
        </a>
    </div>
    <div class="card-body">
        <?php if (empty($users)): ?>
        <p class="text-muted">Belum ada user terdaftar.</p>
        <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Dibuat</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td class="text-strong"><?php echo htmlspecialchars($user["name"]); ?></td>
                    <td><?php echo htmlspecialchars($user["email"]); ?></td>
                    <td>
                        <span class="badge <?php echo $user["role"] === "admin" ? "badge-info" : "badge-kelurahan"; ?>">
                            <?php echo ucfirst($user["role"]); ?>
                        </span>
                    </td>
                    <td><?php echo date("d M Y", strtotime($user["created_at"])); ?></td>
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
