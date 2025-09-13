<?php
$title   = $title ?? 'Abstrak';
$events  = $events ?? [];
$history = $history ?? [];
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- Header Biru -->
      <div class="header-section header-blue d-flex justify-content-between align-items-center mb-3">
        <div>
          <h3 class="welcome-text mb-1"><i class="bi bi-journal-text me-2"></i>Abstrak</h3>
          <div class="text-white-50">Upload & pantau status abstrak</div>
        </div>
        <div class="text-end d-none d-md-block">
          <small class="text-white-50 d-block">Hari ini</small>
          <strong class="text-white"><?= date('d M Y') ?></strong>
        </div>
      </div>

      <!-- Daftar Abstrak Per Event -->
      <div class="card shadow-sm mb-4 border-0 overflow-hidden">
        <div class="card-header bg-gradient-primary text-white">
          <h5 class="mb-0"><i class="bi bi-lightning-charge me-2"></i>Event Terdaftar</h5>
        </div>
        <div class="card-body">
          <?php if (empty($events)): ?>
            <div class="text-muted">Anda belum terdaftar di event mana pun.</div>
          <?php else: ?>
            <div class="row g-3">
              <?php foreach ($events as $eid => $row):
                $abs        = $row['abstract'] ?? null;
                $absStatus  = strtolower($abs['status'] ?? '');
                $payStatus  = strtolower($row['payment_status'] ?? ''); // OPTIONAL: kirim dari controller kalau ada
                // Badge di pojok
                $badgeClass = [
                  'menunggu'        => 'warning',
                  'sedang_direview' => 'info',
                  'diterima'        => 'success',
                  'ditolak'         => 'danger',
                  'revisi'          => 'primary',
                ][$absStatus] ?? 'secondary';
                $badgeLabel = $abs ? ucfirst($absStatus) : 'Belum Upload';

                // Hint/keterangan di bawah
                if (!$abs) {
                  $hint = 'Menunggu abstrak';
                } elseif (in_array($absStatus, ['menunggu','sedang_direview'])) {
                  $hint = 'Menunggu review abstrak';
                } elseif ($absStatus === 'revisi') {
                  $hint = 'Diminta revisi';
                } elseif ($absStatus === 'diterima') {
                  if ($payStatus === 'verified')      $hint = 'Terdaftar';
                  elseif ($payStatus === 'pending')    $hint = 'Pembayaran pending';
                  elseif ($payStatus === 'rejected')   $hint = 'Pembayaran ditolak';
                  else                                  $hint = 'Silakan lakukan pembayaran';
                } elseif ($absStatus === 'ditolak') {
                  $hint = 'Abstrak ditolak';
                } else {
                  $hint = '';
                }
              ?>
              <div class="col-12 col-md-6 col-xl-4">
                <div class="event-card h-100 shadow-sm">
                  <div class="d-flex align-items-start justify-content-between mb-2">
                    <h5 class="mb-0"><?= esc($row['event']['title'] ?? '-') ?></h5>
                    <span class="badge bg-<?= $badgeClass ?>"><?= esc($badgeLabel) ?></span>
                  </div>
                  <div class="small text-muted mb-2">
                    Tanggal Event:
                    <strong><?= isset($row['event']['event_date']) ? date('d M Y', strtotime($row['event']['event_date'])) : '-' ?></strong><br>
                    Deadline Abstrak:
                    <strong><?= !empty($row['event']['abstract_deadline']) ? date('d M Y H:i', strtotime($row['event']['abstract_deadline'])) : '-' ?></strong><br>
                    Format:
                    <strong>
                      <?php
                        $format = strtolower($row['event']['format'] ?? '');
                        echo $format === 'both' ? 'Hybrid' : ucfirst($format ?: '-');
                      ?>
                    </strong>
                  </div>
                  <?php if ($hint): ?>
                    <div class="text-muted small mb-2"><?= esc($hint) ?></div>
                  <?php endif; ?>
                  <div class="d-flex gap-2">
                    <?php if ($row['can_upload']): ?>
                      <a class="btn btn-primary flex-fill" href="/presenter/abstrak/create/<?= (int)$eid ?>">
                        <i class="bi bi-upload"></i> Upload Abstrak
                      </a>
                    <?php elseif ($abs): ?>
                      <a class="btn btn-primary flex-fill" href="/presenter/abstrak/detail/<?= (int)$abs['id_abstrak'] ?>">
                        <i class="bi bi-list"></i> Daftar
                      </a>
                    <?php else: ?>
                      <button class="btn btn-secondary flex-fill" disabled>
                        <i class="bi bi-ban"></i> Tidak Bisa Upload
                      </button>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Riwayat Abstrak -->
      <div class="card shadow-sm mb-4 border-0 overflow-hidden">
        <div class="card-header bg-gradient-primary text-white">
          <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Riwayat Abstrak</h5>
        </div>
        <div class="card-body">
          <?php if (empty($history)): ?>
            <div class="text-muted">Belum ada riwayat abstrak diterima/ditolak.</div>
          <?php else: ?>
            <div class="row g-3">
              <?php foreach ($history as $h):
                $badgeClass = $h['status'] === 'diterima' ? 'success' : 'danger';
              ?>
              <div class="col-12 col-md-6 col-xl-4">
                <div class="event-card h-100 shadow-sm">
                  <h6 class="mb-1"><?= esc($h['judul']) ?></h6>
                  <div class="small text-muted mb-2"><?= esc($h['nama_kategori'] ?? '-') ?></div>
                  <span class="badge bg-<?= $badgeClass ?>"><?= ucfirst($h['status']) ?></span>
                  <div class="small text-muted mt-2">
                    <?= !empty($h['tanggal_upload']) ? date('d M Y H:i', strtotime($h['tanggal_upload'])) : '-' ?>
                  </div>
                  <a class="btn btn-outline-primary btn-sm mt-2" href="/presenter/abstrak/detail/<?= (int)$h['id_abstrak'] ?>">
                    <i class="bi bi-eye"></i> Detail
                  </a>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
  :root{
    --primary-color:#2563eb; --info-color:#06b6d4; --success-color:#10b981; --secondary:#475569;
  }
  .header-section.header-blue{
    background:linear-gradient(135deg,var(--primary-color),#1e40af);
    color:#fff; padding:24px; border-radius:16px; box-shadow:0 8px 28px rgba(0,0,0,.12);
  }
  .welcome-text{ font-weight:700; }
  .bg-gradient-primary{ background:linear-gradient(135deg,var(--primary-color),var(--info-color))!important; }
  .event-card{
    background:#f3f4f6; /* lebih gelap */
    border-radius:14px;
    padding:16px;
    border:1px solid #e5e7eb;
  }
  @media (max-width: 767.98px){
    .event-card{ padding:14px; }
    .header-section.header-blue{ padding:18px; }
  }
</style>