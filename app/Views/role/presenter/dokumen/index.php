<?php
  // Defaults & helpers
  $title          = $title ?? 'Dokumen';
  $activeTab      = $activeTab ?? 'loa'; // 'loa' | 'sertifikat'
  $loa_documents  = $loa_documents ?? [];
  $eligible_loa   = $eligible_loa ?? [];
  $certificates   = $certificates ?? [];
  $eligible_cert  = $eligible_cert ?? [];
  $fmtD  = fn($s)=> $s ? date('d M Y', strtotime($s)) : '-';
  $fmtDT = fn($s)=> $s ? date('d M Y H:i', strtotime($s)) : '-';
  $base = function($p){ return esc(basename((string)$p)); };
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- HERO -->
      <div class="abs-hero mb-3">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="abs-title">Dokumen</div>
            <div class="abs-sub">LOA & Sertifikat untuk event yang kamu ikuti (Presenter).</div>
          </div>
          <div class="d-none d-md-flex gap-2">
            <a href="<?= site_url('presenter/events') ?>" class="btn btn-light text-primary fw-semibold">
              <i class="bi bi-calendar2-event me-1"></i>Lihat Event
            </a>
          </div>
        </div>

        <!-- Tabs -->
        <div class="mt-3">
          <ul class="nav nav-pills">
            <li class="nav-item me-2">
              <a class="nav-link <?= $activeTab==='loa'?'active':'' ?>" href="<?= site_url('presenter/dokumen/loa') ?>">
                <i class="bi bi-file-earmark-text me-1"></i>LOA
                <?php if ($activeTab==='loa'): ?>
                  <span class="badge bg-light text-dark ms-1"><?= count($loa_documents) ?></span>
                <?php endif; ?>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?= $activeTab==='sertifikat'?'active':'' ?>" href="<?= site_url('presenter/dokumen/sertifikat') ?>">
                <i class="bi bi-award me-1"></i>Sertifikat
                <?php if ($activeTab==='sertifikat'): ?>
                  <span class="badge bg-light text-dark ms-1"><?= count($certificates) ?></span>
                <?php endif; ?>
              </a>
            </li>
          </ul>
        </div>
      </div>

      <?php if ($activeTab === 'loa'): ?>
        <!-- === LOA === -->

        <div class="card shadow-sm border-0 mb-3">
          <div class="card-body">
            <h5 class="card-title mb-3">LOA Anda</h5>

            <?php if (!empty($loa_documents)): ?>
              <div class="table-responsive">
                <table class="table table-hover align-middle">
                  <thead class="table-light">
                    <tr>
                      <th>Event</th>
                      <th class="text-nowrap">Tanggal Event</th>
                      <th class="text-nowrap">Diunggah</th>
                      <th>Nama File</th>
                      <th class="text-end">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($loa_documents as $d): ?>
                      <tr>
                        <td>
                          <div class="fw-semibold"><?= esc($d['event_title'] ?? 'Event') ?></div>
                          <div class="small text-muted"><?= esc($d['event_time'] ?? '-') ?></div>
                        </td>
                        <td class="text-nowrap"><?= esc($fmtD($d['event_date'] ?? null)) ?></td>
                        <td class="text-nowrap"><?= esc($fmtDT($d['uploaded_at'] ?? null)) ?></td>
                        <td><?= esc($base($d['file_path'] ?? '')) ?></td>
                        <td class="text-end">
                          <a target="_blank"
                             href="<?= site_url('presenter/dokumen/loa/download/'.rawurlencode($base($d['file_path'] ?? ''))) ?>"
                             class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-download me-1"></i>Unduh
                          </a>
                          <a href="<?= site_url('presenter/events/detail/'.($d['event_id'] ?? 0)) ?>"
                             class="btn btn-sm btn-light">Event</a>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php else: ?>
              <div class="p-4 text-center border rounded-3 bg-light-subtle">
                <div class="mb-2"><i class="bi bi-file-earmark-text fs-3 text-secondary"></i></div>
                <div class="fw-semibold">Belum ada LOA</div>
                <div class="text-muted small">LOA muncul setelah <strong>abstrak diacc</strong> dan <strong>pembayaran terverifikasi</strong>.</div>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="card shadow-sm border-0">
          <div class="card-body">
            <h5 class="card-title mb-3">Event Eligible LOA</h5>

            <?php if (!empty($eligible_loa)): ?>
              <div class="list-group">
                <?php foreach ($eligible_loa as $e): ?>
                  <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                      <div class="fw-semibold"><?= esc($e['title'] ?? 'Event') ?></div>
                      <div class="small text-muted"><?= esc($fmtD($e['event_date'] ?? null)) ?></div>
                      <div class="mt-1">
                        <span class="badge bg-success-subtle text-success border">Abstrak: diacc</span>
                        <span class="badge bg-success-subtle text-success border ms-1">Pembayaran: verified</span>
                      </div>
                    </div>
                    <a href="<?= site_url('presenter/events/detail/'.($e['id'] ?? 0)) ?>" class="btn btn-sm btn-outline-primary">
                      Lihat Event
                    </a>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="text-muted small">Tidak ada event yang memenuhi syarat LOA saat ini.</div>
            <?php endif; ?>
          </div>
        </div>

      <?php else: ?>
        <!-- === SERTIFIKAT === -->

        <div class="card shadow-sm border-0 mb-3">
          <div class="card-body">
            <h5 class="card-title mb-3">Sertifikat Anda</h5>

            <?php if (!empty($certificates)): ?>
              <div class="table-responsive">
                <table class="table table-hover align-middle">
                  <thead class="table-light">
                    <tr>
                      <th>Event</th>
                      <th class="text-nowrap">Tanggal Event</th>
                      <th class="text-nowrap">Diunggah</th>
                      <th>Nama File</th>
                      <th class="text-end">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($certificates as $d): ?>
                      <tr>
                        <td>
                          <div class="fw-semibold"><?= esc($d['event_title'] ?? 'Event') ?></div>
                          <div class="small text-muted"><?= esc($d['event_time'] ?? '-') ?></div>
                        </td>
                        <td class="text-nowrap"><?= esc($fmtD($d['event_date'] ?? null)) ?></td>
                        <td class="text-nowrap"><?= esc($fmtDT($d['uploaded_at'] ?? null)) ?></td>
                        <td><?= esc($base($d['file_path'] ?? '')) ?></td>
                        <td class="text-end">
                          <a target="_blank"
                             href="<?= site_url('presenter/dokumen/sertifikat/download/'.rawurlencode($base($d['file_path'] ?? ''))) ?>"
                             class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-download me-1"></i>Unduh
                          </a>
                          <a href="<?= site_url('presenter/events/detail/'.($d['event_id'] ?? 0)) ?>"
                             class="btn btn-sm btn-light">Event</a>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php else: ?>
              <div class="p-4 text-center border rounded-3 bg-light-subtle">
                <div class="mb-2"><i class="bi bi-award fs-3 text-secondary"></i></div>
                <div class="fw-semibold">Belum ada sertifikat</div>
                <div class="text-muted small">Sertifikat muncul setelah kamu <strong>hadir</strong> di event & pembayaran terverifikasi.</div>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="card shadow-sm border-0">
          <div class="card-body">
            <h5 class="card-title mb-3">Event Eligible Sertifikat</h5>

            <?php if (!empty($eligible_cert)): ?>
              <div class="list-group">
                <?php foreach ($eligible_cert as $e): ?>
                  <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                      <div class="fw-semibold"><?= esc($e['title'] ?? 'Event') ?></div>
                      <div class="small text-muted"><?= esc($fmtD($e['event_date'] ?? null)) ?></div>
                      <div class="mt-1">
                        <span class="badge bg-success-subtle text-success border">Hadir</span>
                        <span class="badge bg-success-subtle text-success border ms-1">Pembayaran: verified</span>
                        <?php if (!empty($e['attendance_time'])): ?>
                          <span class="badge bg-light text-secondary border ms-1">
                            <i class="bi bi-clock me-1"></i><?= esc($fmtDT($e['attendance_time'])) ?>
                          </span>
                        <?php endif; ?>
                      </div>
                    </div>
                    <a href="<?= site_url('presenter/events/detail/'.($e['id'] ?? 0)) ?>" class="btn btn-sm btn-outline-primary">
                      Lihat Event
                    </a>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="text-muted small">Tidak ada event yang memenuhi syarat sertifikat saat ini.</div>
            <?php endif; ?>
          </div>
        </div>

      <?php endif; ?>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
/* Reuse gaya hero & KPI dari contoh pembayaran */
.abs-hero{background:linear-gradient(90deg,#2563eb,#60a5fa);border-radius:16px;color:#fff;padding:14px 16px;box-shadow:0 6px 20px rgba(37,99,235,.18);}
.abs-title{font-weight:800;line-height:1.2;font-size:clamp(18px,4.2vw,24px);}
.abs-sub{opacity:.95;font-size:.95rem;}
</style>
