<aside class="sidebar">
    <div class="sidebar-logo">
        <span class="dot"></span>Radius
    </div>

    <div class="nav-group-label">Menu</div>
    <a href="<?php echo APP_URL; ?>/dashboard" class="nav-item">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="3" width="7" height="7" rx="1"/>
            <rect x="14" y="3" width="7" height="7" rx="1"/>
            <rect x="3" y="14" width="7" height="7" rx="1"/>
            <rect x="14" y="14" width="7" height="7" rx="1"/>
        </svg>
        Dashboard Cabang
    </a>
    <a href="<?php echo APP_URL; ?>/branch" class="nav-item">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z"/>
        </svg>
        Daftar Cabang
    </a>
    <a href="<?php echo APP_URL; ?>/branch/create" class="nav-item">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 5v14M5 12h14"/>
        </svg>
        Tambah Cabang
    </a>

    <?php if (isAdmin()): ?>
    <a href="<?php echo APP_URL; ?>/referral" class="nav-item">Kode Referral Karyawan</a>
    <a href="<?php echo APP_URL; ?>/admin/users" class="nav-item">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="8" r="4"/>
            <path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>
        </svg>
        Pengaturan User
    </a>
    <?php endif; ?>

    <div class="sidebar-footer">
        <div class="avatar"><?php echo strtoupper(substr(currentUser()["name"] ?? "U", 0, 2)); ?></div>
        <div class="sidebar-footer-text">
            <div class="name"><?php echo htmlspecialchars(currentUser()["name"] ?? "User"); ?></div>
            <div class="role"><?php echo ucfirst(currentUser()["role"] ?? "guest"); ?></div>
        </div>
    </div>
</aside>
