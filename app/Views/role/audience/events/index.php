<?php
  $title = 'Event Tersedia';
// Controller kirim: $events = (new EventModel())->getEventsWithOpenRegistration();
  $events = $events ?? [];
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
        <h3 class="mb-0">Event Tersedia</h3>
        <!-- (opsional) tombol kembali ke dashboard -->
        <a href="<?= site_url('audience/dashboard') ?>" class="btn btn-sm btn-outline-secondary d-none d-md-inline-flex">
          <i class="bi bi-house me-1"></i> Dashboard
        </a>
      </div>

      <!-- (opsional) pencarian ringan -->
      <form class="mb-3" method="get" action="">
        <div class="row g-2">
          <div class="col-12 col-md-6 col-lg-4">
            <input type="text" name="q" value="<?= esc($_GET['q'] ?? '') ?>" class="form-control" placeholder="Cari judul / lokasi...">
          </div>
          <div class="col-6 col-md-3 col-lg-2">
            <select name="format" class="form-select">
              <?php $fmt = $_GET['format'] ?? ''; ?>
              <option value="">Semua Format</option>
              <option value="online"  <?= $fmt==='online'?'selected':'' ?>>Online</option>
              <option value="offline" <?= $fmt==='offline'?'selected':'' ?>>Offline</option>
              <option value="both"    <?= $fmt==='both'?'selected':'' ?>>Hybrid</option>
            </select>
          </div>
          <div class="col-6 col-md-3 col-lg-2">
            <button class="btn btn-primary w-100"><i class="bi bi-search me-1"></i> Cari</button>
          </div>
        </div>
      </form>

      <?php
        // filter sederhana di view (boleh pindah ke controller nanti)
        $q = strtolower(trim($_GET['q'] ?? ''));
        $fmt = $_GET['format'] ?? '';
        $filtered = array_values(array_filter($events, function($e) use($q,$fmt){
          $ok = true;
          if ($q !== '') {
            $hay = strtolower(($e['title'] ?? '').' '.($e['location'] ?? ''));
            $ok = $ok && str_contains($hay, $q);
          }
          if ($fmt !== '') {
            $ok = $ok && (($e['format'] ?? '') === $fmt);
          }
          return $ok;
        }));
      ?>

      <?php if (!empty($filtered)): ?>
        <div class="row g-3">
          <?php foreach ($filtered as $e): ?>
            <?php
              $priceOnline  = (float)($e['audience_fee_online']  ?? 0);
              $priceOffline = (float)($e['audience_fee_offline'] ?? 0);
            ?>
            <div class="col-12 col-md-6 col-lg-4">
              <div class="card h-100 shadow-sm border-0">
                <div class="card-body d-flex flex-column">
                  <div class="d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-1"><?= esc($e['title'] ?? 'Event') ?></h5>
                    <span class="badge bg-secondary-subtle text-secondary">
                      <?= esc(strtoupper($e['format'] ?? '-')) ?>
                    </span>
                  </div>
                  <small class="text-muted">
                    <?= esc(isset($e['event_date']) ? date('d M Y', strtotime($e['event_date'])) : '-') ?>
                    · <?= esc($e['event_time'] ?? '-') ?>
                  </small>

                  <div class="mt-2 small text-muted">
                    Lokasi: <?= esc($e['location'] ?? '-') ?>
                  </div>

                  <div class="mt-3">
                    <div class="small text-muted">Harga Audience</div>
                    <div class="d-flex gap-3 flex-wrap">
                      <span class="badge bg-info-subtle text-info">
                        Online: Rp <?= number_format($priceOnline, 0, ',', '.') ?>
                      </span>
                      <span class="badge bg-primary-subtle text-primary">
                        Offline: Rp <?= number_format($priceOffline, 0, ',', '.') ?>
                      </span>
                    </div>
                  </div>

                  <div class="mt-auto pt-3 d-flex gap-2">
                    <a href="<?= site_url('audience/events/detail/'.($e['id'] ?? 0)) ?>" class="btn btn-sm btn-outline-secondary flex-fill">
                      Detail
                    </a>
                    <a href="<?= site_url('audience/events/register/'.($e['id'] ?? 0)) ?>" class="btn btn-sm btn-primary flex-fill">
                      Daftar
                    </a>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="p-4 text-center border rounded-3 bg-light-subtle">
          <div class="mb-2"><i class="bi bi-calendar2-event fs-3 text-secondary"></i></div>
          <div class="fw-semibold">Belum ada event yang cocok</div>
          <div class="text-muted small">Coba ubah kata kunci atau format.</div>
        </div>
      <?php endif; ?>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>
