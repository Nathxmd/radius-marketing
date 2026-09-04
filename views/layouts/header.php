<header class="topbar">
    <div>
        <?php if (isset($breadcrumb)): ?>
        <div class="breadcrumb"><?php echo $breadcrumb; ?></div>
        <?php endif; ?>
        <h1 class="page-title"><?php echo $pageTitle ?? "Dashboard"; ?></h1>
    </div>
    <div class="topbar-actions">
        <?php if (isset($actions)) echo $actions; ?>
    </div>
</header>
