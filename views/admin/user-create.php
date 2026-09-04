<?php
$pageTitle = "Tambah User";
$breadcrumb = "Pengaturan / User / Tambah";
ob_start();
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Form Tambah User</h2>
    </div>
    <div class="card-body">
        <?php if ($error = flash("error")): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success = flash("success")): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" action="<?php echo APP_URL; ?>/admin/user/store">
            <div class="form-row">
                <div class="form-group">
                    <label for="name">Nama Lengkap *</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars(old("name")); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars(old("email")); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role" class="form-select">
                    <option value="marketing" <?php echo old("role", "marketing") === "marketing" ? "selected" : ""; ?>>Marketing</option>
                    <option value="admin" <?php echo old("role", "marketing") === "admin" ? "selected" : ""; ?>>Admin</option>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required minlength="6">
                    <div class="helper-text">Minimal 6 karakter</div>
                </div>

                <div class="form-group">
                    <label for="password_confirm">Konfirmasi Password *</label>
                    <input type="password" id="password_confirm" name="password_confirm" required minlength="6">
                </div>
            </div>

            <div class="save-row">
                <a href="<?php echo APP_URL; ?>/admin/users" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan User</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . "/../layouts/main.php";
?>
