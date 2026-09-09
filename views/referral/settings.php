<?php $pageTitle = 'Pengaturan Promo Referral'; $breadcrumb = 'Referral / Pengaturan Promo'; ob_start(); ?>
<?php
// Susun aturan yang sudah ada agar mudah dibaca di form.
$ruleMap = [];
foreach ($rules as $r) {
    $ruleMap[$r['type']][$r['beneficiary']][$r['branch_id'] ?? 0] = $r;
}
$daycareParent = $ruleMap['daycare']['parent'][0];
$daycareStaff  = $ruleMap['daycare']['staff'][0];
?>
<div class="card">
  <div class="card-header">
    <h2 class="card-title">Pengaturan Promo</h2>
    <div>
      <form style="display:inline" method="POST" action="<?php echo APP_URL; ?>/referral/resync">
        <button class="btn btn-secondary" type="submit">Resync semua referral</button>
      </form>
      <a class="btn btn-secondary" href="<?php echo APP_URL; ?>/referral/logs">Log Integrasi</a>
    </div>
  </div>
  <div class="card-body">
    <?php if ($message = flash('success')): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($message = flash('error')): ?><div class="alert alert-error"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

    <form method="POST" action="<?php echo APP_URL; ?>/referral/settings/save">

      <div class="form-group">
        <label>Jenis nilai reward</label>
        <select name="reward_type">
          <option value="nominal" <?php echo ($setting['reward_type'] ?? 'nominal') === 'nominal' ? 'selected' : ''; ?>>Nominal</option>
          <option value="persen" <?php echo ($setting['reward_type'] ?? '') === 'persen' ? 'selected' : ''; ?>>Persen</option>
        </select>
      </div>

      <h3>Promo Daycare</h3>
      <p class="helper-text">Berlaku sama untuk semua cabang. Diskon parent recurring tiap bulan, komisi staff dibayar sekali saat enroll.</p>
      <div class="form-row">
        <div class="form-group">
          <label>Diskon parent / bulan (Rp)</label>
          <input type="number" min="0" step="1000" name="daycare_parent_value" value="<?php echo htmlspecialchars($daycareParent['value'] ?? 500000); ?>">
        </div>
        <div class="form-group">
          <label>Lama diskon (bulan)</label>
          <input type="number" min="1" name="daycare_parent_duration" value="<?php echo htmlspecialchars($daycareParent['duration_count'] ?? 3); ?>">
        </div>
        <div class="form-group">
          <label>Komisi staff (Rp)</label>
          <input type="number" min="0" step="1000" name="daycare_staff_value" value="<?php echo htmlspecialchars($daycareStaff['value'] ?? 250000); ?>">
        </div>
      </div>

      <h3>Promo Klinik</h3>
      <p class="helper-text">Harga spesial per kunjungan (kunjungan 1–4), berbeda tiap cabang. Staff tidak mendapat komisi untuk klinik. Cabang tanpa harga akan ditolak webhook kunjungan.</p>
      <table class="table">
        <thead><tr><th>Cabang</th><th style="text-align:right">Harga khusus per kunjungan (Rp)</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($branches as $branch): ?>
          <?php $branchRule = $ruleMap['klinik']['parent'][(int) $branch['id']] ?? null; ?>
          <tr>
            <td class="text-strong"><?php echo htmlspecialchars($branch['name']); ?></td>
            <td style="text-align:right">
              <input type="number" min="0" step="1000" name="klinik_value[<?php echo (int) $branch['id']; ?>]" value="<?php echo htmlspecialchars($branchRule['value'] ?? ''); ?>" placeholder="0" style="text-align:right">
            </td>
            <td>
              <?php if ($branchRule): ?>
                <span class="badge badge-info">Terkonfigurasi</span>
              <?php else: ?>
                <span class="badge badge-warning">Belum dikonfigurasi</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>

      <div class="save-row">
        <a class="btn btn-secondary" href="<?php echo APP_URL; ?>/referral">Batal</a>
        <button class="btn btn-primary">Simpan &amp; Sinkronkan</button>
      </div>
    </form>
  </div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/main.php'; ?>