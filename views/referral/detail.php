<?php $pageTitle = 'Detail Referral'; $breadcrumb = 'Referral / Detail'; ob_start(); ?>
<div class="card">
  <div class="card-header">
    <h2 class="card-title"><?php echo htmlspecialchars($staff['name']); ?> · <code><?php echo htmlspecialchars($staff['referral_code']); ?></code> · <span class="badge <?php echo $staff['status'] === 'aktif' ? 'badge-info' : 'badge-warning'; ?>"><?php echo ucfirst($staff['status']); ?></span></h2>
    <a class="btn btn-secondary" href="<?php echo APP_URL . '/referral/edit/' . $staff['id']; ?>">Edit Staff</a>
  </div>
  <div class="card-body">
    <?php if (empty($registrations)): ?>
      <p class="text-muted">Belum ada registrasi untuk kode referral ini.</p>
    <?php else: ?>
    <table class="table">
      <thead><tr><th>Orang Tua</th><th>Anak</th><th>Promo</th><th>Status</th><th>Komisi Staff</th><th>Perkembangan Promo</th><th>Aksi</th></tr></thead>
      <tbody>
      <?php foreach ($registrations as $row): ?>
        <tr>
          <td><?php echo htmlspecialchars($row['parent_name'] ?: '-'); ?><br><small><?php echo htmlspecialchars($row['parent_phone'] ?: '-'); ?></small></td>
          <td><?php echo htmlspecialchars($row['child_name'] ?: '-'); ?></td>
          <td><span class="badge <?php echo $row['promo_type'] === 'klinik' ? 'badge-accent' : 'badge-info'; ?>"><?php echo htmlspecialchars(ucfirst($row['promo_type'] ?? '-')); ?></span></td>
          <td><?php echo ucfirst(str_replace('_', ' ', $row['enrollment_status'])); ?></td>
          <td>
            <?php
              $comm = $row['staff_commission_status'] ?? '';
              if ($comm === 'tidak_ada') { echo '<span class="badge badge-warning">Tidak ada komisi</span>'; }
              else { echo htmlspecialchars(str_replace('_', ' ', ucfirst($comm))); }
            ?>
          </td>
          <td>
            <?php $reds = $redemptionsByReg[$row['id']] ?? []; ?>
            <?php if (empty($reds)): ?>
              <span class="text-muted">-</span>
            <?php else: ?>
              <?php foreach ($reds as $revRow): ?>
                <?php
                  $bLabel = $revRow['beneficiary'] === 'staff' ? 'Komisi staff' : (($row['promo_type'] === 'klinik') ? 'Kunjungan' : 'Bulan') . ' ' . ($revRow['period_or_visit_number'] ?? '-');
                ?>
                <div class="text-small">
                  <span class="badge <?php echo $revRow['beneficiary'] === 'staff' ? 'badge-info' : 'badge-neutral'; ?>"><?php echo htmlspecialchars($bLabel); ?></span>
                  Rp <?php echo number_format((float) $revRow['value_applied'], 0, ',', '.'); ?>
                  (<?php echo htmlspecialchars($revRow['status']); ?>)
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($comm !== 'tidak_ada'): ?>
              <?php if ($comm === 'pending'): ?>
                <form method="POST" action="<?php echo APP_URL; ?>/referral/commission">
                  <input type="hidden" name="registration_id" value="<?php echo (int) $row['id']; ?>">
                  <input type="hidden" name="staff_id" value="<?php echo (int) $staff['id']; ?>">
                  <button name="status" value="disetujui" class="btn btn-secondary">Setujui</button>
                  <button name="status" value="dibayar" class="btn btn-primary">Sudah Dibayar</button>
                </form>
              <?php elseif ($comm === 'disetujui'): ?>
                <form method="POST" action="<?php echo APP_URL; ?>/referral/commission">
                  <input type="hidden" name="registration_id" value="<?php echo (int) $row['id']; ?>">
                  <input type="hidden" name="staff_id" value="<?php echo (int) $staff['id']; ?>">
                  <button name="status" value="dibayar" class="btn btn-primary">Sudah Dibayar</button>
                </form>
              <?php endif; ?>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/main.php'; ?>