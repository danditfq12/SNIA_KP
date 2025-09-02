<?php
  $title  = 'Event Tersedia';
// Controller harus mengirim $events (event open registration)
  $events = $events ?? [];

// Status pencarian (apakah user mengisi kata kunci / memilih format?)
  $qRaw = $_GET['q'] ?? '';
  $fmt  = $_GET['format'] ?? '';
  $q    = strtolower(trim($qRaw));
  $isSearching = ($q !== '') || ($fmt !== '');

// Filter di view (boleh dipindah ke controller nanti)
  $filtered = array_values(array_filter($events, function($e) use($q,$fmt){
    // match keyword di title atau location
    if ($q !== '') {
      $hay = strtolower(($e['title'] ?? '').' '.($e['location'] ?? ''));
      if (!str_contains($hay, $q)) return false;
    }
    // match format (online/offline/both); jika pilih online/offline, sertakan event both (hybrid)
    if ($fmt !== '') {
      $evFmt = $e['format'] ?? '';
      if ($fmt === 'online'  && !in_array($evFmt, ['online','both'], true))  return false;
      if ($fmt === 'offline' && !in_array($evFmt, ['offline','both'], true)) return false;
      if ($fmt === 'both'    && $evFmt !== 'both')                           return false;
    }
    return true;
  }));
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>

<div id="content">
  <main class="flex-fill">
    <div class="container-fluid p-3 p-md-4">

      <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
        <h3 class="mb-0">Event Tersedia</h3>
        <a href="<?= site_url('audience/dashboard') ?>" class="btn btn-sm btn-outline-secondary d-none d-md-inline-flex">
          <i class="bi bi-house me-1"></i> Dashboard
        </a>
      </div>

      <form class="mb-3" method="get" action="">
        <div class="row g-2 align-items-stretch">
          <div class="col-12 col-md-6 col-lg-4">
            <input type="text" name="q" value="<?= esc($qRaw) ?>" class="form-control" placeholder="Cari judul / lokasi...">
          </div>
          <div class="col-6 col-md-3 col-lg-2">
            <select name="format" class="form-select">
              <option value="">Semua Format</option>
              <option value="online"  <?= $fmt==='online'  ? 'selected':'' ?>>Online</option>
              <option value="offline" <?= $fmt==='offline' ? 'selected':'' ?>>Offline</option>
              <option value="both"    <?= $fmt==='both'    ? 'selected':'' ?>>Hybrid</option>
            </select>
          </div>
          <div class="col-6 col-md-3 col-lg-2 d-flex gap-2">
            <button class="btn btn-primary w-100"><i class="bi bi-search me-1"></i> Cari</button>
            <?php if ($isSearching): ?>
              <a href="<?= current_url() ?>" class="btn btn-outline-secondary" title="Reset filter"><i class="bi bi-x-lg"></i></a>
            <?php endif; ?>
          </div>
          <?php if (!$isSearching && !empty($events)): ?>
            <div class="col-12 col-lg text-muted small d-flex align-items-center justify-content-lg-end">
              Menampilkan <?= count($events) ?> event.
            </div>
          <?php elseif ($isSearching): ?>
            <div class="col-12 col-lg text-muted small d-flex align-items-center justify-content-lg-end">
              Hasil: <?= count($filtered) ?> event.
            </div>
          <?php endif; ?>
        </div>
      </form>

      <?php
        // Pilih sumber data untuk ditampilkan:
        // - jika sedang mencari -> pakai $filtered
        // - jika tidak mencari   -> pakai $events (asal)
        $list = $isSearching ? $filtered : $events;
      ?>

      <?php if (!empty($list)): ?>
        <div class="row g-3">
          <?php foreach ($list as $e): ?>
            <?php
              $priceOnline  = (float)($e['audience_fee_online']  ?? 0);
              $priceOffline = (float)($e['audience_fee_offline'] ?? 0);
            ?>
            <div class="col-12 col-md-6 col-lg-4">
              <div class="card h-100 shadow-sm border-0">
                <div class="card-body d-flex flex-column">
                  <div class="d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-1"><?= esc($e['title'] ?? 'Event') ?></h5>
                    <span class="badge bg-secondary-subtle text-secondary"><?= esc(strtoupper($e['format'] ?? '-')) ?></span>
                  </div>
                  <small class="text-muted">
                    <?= esc(isset($e['event_date']) ? date('d M Y', strtotime($e['event_date'])) : '-') ?>
                    · <?= esc($e['event_time'] ?? '-') ?>
                  </small>
                  <div class="mt-2 small text-muted">Lokasi: <?= esc($e['location'] ?? '-') ?></div>

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
                    <a href="<?= site_url('audience/events/detail/'.($e['id'] ?? 0)) ?>" class="btn btn-sm btn-outline-secondary flex-fill">Detail</a>
                    <a href="<?= site_url('audience/events/register/'.($e['id'] ?? 0)) ?>" class="btn btn-sm btn-primary flex-fill">Daftar</a>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <?php if ($isSearching): ?>
          <div class="p-4 text-center border rounded-3 bg-light-subtle">
            <div class="mb-2"><i class="bi bi-calendar2-event fs-3 text-secondary"></i></div>
            <div class="fw-semibold">Belum ada event yang cocok</div>
            <div class="text-muted small">Coba ubah kata kunci atau format.</div>
          </div>
        <?php else: ?>
          <div class="p-4 text-center border rounded-3 bg-light-subtle">
            <div class="mb-2"><i class="bi bi-calendar-x fs-3 text-secondary"></i></div>
            <div class="fw-semibold">Event Belum Tersedia</div>
            <div class="text-muted small">Tunggu informasi berikutnya ya.</div>
          </div>
        <?php endif; ?>
      <?php endif; ?>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>
