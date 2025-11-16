<?php
// app/Views/role/presenter/events/register.php

// ===== Data dari controller =====
$event       = $event       ?? [];
$options     = $options     ?? [];     // ['online'], ['offline'], ['online','offline']
$modePricing = $modePricing ?? [];     // ['online' => 500000, 'offline' => 700000]
$waveInfo    = $waveInfo    ?? null;   // ['wave_number','start','deadline']
$title       = $title       ?? 'Daftar sebagai Presenter';

// ===== Utils =====
$fmt = fn($s, $withTime = false) => $s
    ? ($withTime ? date('d M Y H:i', strtotime($s)) : date('d M Y', strtotime($s)))
    : '-';

$formatLabel = function($f){
    $f = strtolower((string)$f);
    return ($f === 'both' || $f === 'hybrid') ? 'Hybrid' : ucfirst($f ?: '-');
};

$eventId   = (int)($event['id'] ?? 0);
$submitUrl = site_url('presenter/events/register/'.$eventId);

// Mode yang diperbolehkan
$opts       = (array)$options;
$canOffline = in_array('offline', $opts, true);
$canOnline  = in_array('online',  $opts, true);

// Harga dari modePricing (bukan dari pricing matrix)
$offlinePrice = $modePricing['offline'] ?? null;
$onlinePrice  = $modePricing['online']  ?? null;

// Info gelombang aktif
$activeWaveNum      = $waveInfo['wave_number'] ?? null;
$activeWaveDeadline = $waveInfo['deadline']    ?? null;
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl px-3 px-md-4 py-4">

      <!-- HERO -->
      <div class="card-hero mb-4">
        <div class="hero-body">
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="flex-grow-1">
              <div class="d-inline-flex align-items-center mb-2">
                <span class="hero-tag me-2"><i class="bi bi-person-standing me-1"></i>Presenter</span>
              </div>
              <h3 class="hero-title mb-1">
                Pilih Mode Pendaftaran
              </h3>
              <div class="text-white-70 small">
                <?= esc($event['title'] ?? 'Event') ?> • <?= esc($formatLabel($event['format'] ?? 'both')) ?>
              </div>
              <div class="text-white-70 small mt-1">
                Tanggal event:
                <strong>
                  <?= esc($fmt($event['event_date'] ?? null)) ?>
                  <?= !empty($event['event_time']) ? ' • '.esc($event['event_time']) : '' ?>
                </strong>
              </div>
            </div>

            <!-- Info Gelombang -->
            <div class="hero-side card-glass-plain shadow-soft ms-md-3">
              <div class="small text-white-70 mb-1">Gelombang Aktif</div>
              <?php if ($activeWaveNum): ?>
                <div class="fw-bold lh-sm">
                  Gelombang <?= (int)$activeWaveNum ?><br>
                  <?php if ($activeWaveDeadline): ?>
                    <span class="small text-white-70">
                      s.d. <?= esc($fmt($activeWaveDeadline, true)) ?>
                    </span>
                  <?php endif; ?>
                </div>
              <?php else: ?>
                <div class="fw-bold lh-sm">Belum diatur</div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Footer Hero -->
        <div class="hero-tabs d-flex align-items-center justify-content-between flex-wrap gap-2">
          <div class="d-flex align-items-center gap-2">
            <a href="<?= site_url('presenter/events/detail/'.$eventId) ?>" class="btn btn-sm btn-outline-primary">
              <i class="bi bi-arrow-left"></i> Kembali ke Detail
            </a>
          </div>
          <?php if ($activeWaveDeadline): ?>
            <div class="small text-muted d-flex align-items-center gap-1">
              <i class="bi bi-hourglass-split"></i>
              <span>Batas pendaftaran gelombang ini:</span>
              <strong><?= esc($fmt($activeWaveDeadline, true)) ?></strong>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- MODE PICKER SECTION -->
      <div class="row g-3 align-items-stretch">

        <!-- Column: Mode Cards -->
        <div class="col-12 col-lg-7">
          <div class="row g-3">

            <!-- OFFLINE CARD -->
            <?php if ($canOffline): ?>
              <div class="col-12">
                <form class="mode-card mode-offline" method="post" action="<?= esc($submitUrl) ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="mode_kehadiran" value="offline">

                  <div class="mode-header d-flex align-items-center justify-content-between gap-2">
                    <div class="d-flex align-items-center gap-2">
                      <div class="mode-icon">
                        <i class="bi bi-building"></i>
                      </div>
                      <div>
                        <div class="mode-label">
                          <span class="badge bg-mode-offline">Rekomendasi</span>
                        </div>
                        <h5 class="mode-title mb-0">Offline (Onsite)</h5>
                      </div>
                    </div>
                    <?php if ($offlinePrice !== null): ?>
                      <div class="price-main text-end">
                        <div class="price-value">
                          Rp <?= number_format((float)$offlinePrice, 0, ',', '.') ?>
                        </div>
                        <div class="price-caption small text-muted">Tarif presenter offline</div>
                      </div>
                    <?php endif; ?>
                  </div>

                  <div class="mode-body">
                    <p class="mode-desc mb-2">
                      Hadir langsung di lokasi konferensi, bertemu reviewer & peserta lain secara tatap muka.
                    </p>
                    <ul class="mode-benefit list-unstyled small mb-0">
                      <li><i class="bi bi-check-circle-fill me-1"></i>Interaksi langsung dengan audiens</li>
                      <li><i class="bi bi-check-circle-fill me-1"></i>Foto dokumentasi onsite</li>
                      <li><i class="bi bi-check-circle-fill me-1"></i>Relasi & networking lebih luas</li>
                    </ul>
                  </div>

                  <div class="mode-footer d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                      Pilih Offline
                      <i class="bi bi-arrow-right-short ms-1"></i>
                    </button>
                  </div>
                </form>
              </div>
            <?php endif; ?>

            <!-- ONLINE CARD -->
            <?php if ($canOnline): ?>
              <div class="col-12">
                <form class="mode-card mode-online" method="post" action="<?= esc($submitUrl) ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="mode_kehadiran" value="online">

                  <div class="mode-header d-flex align-items-center justify-content-between gap-2">
                    <div class="d-flex align-items-center gap-2">
                      <div class="mode-icon online">
                        <i class="bi bi-wifi"></i>
                      </div>
                      <div>
                        <div class="mode-label">
                          <span class="badge bg-mode-online">Fleksibel</span>
                        </div>
                        <h5 class="mode-title mb-0">Online (Virtual)</h5>
                      </div>
                    </div>
                    <?php if ($onlinePrice !== null): ?>
                      <div class="price-main text-end">
                        <div class="price-value">
                          Rp <?= number_format((float)$onlinePrice, 0, ',', '.') ?>
                        </div>
                        <div class="price-caption small text-muted">Tarif presenter online</div>
                      </div>
                    <?php endif; ?>
                  </div>

                  <div class="mode-body">
                    <p class="mode-desc mb-2">
                      Presentasi dari mana saja melalui platform konferensi daring (Zoom/Meet).
                    </p>
                    <ul class="mode-benefit list-unstyled small mb-0">
                      <li><i class="bi bi-check-circle-fill me-1"></i>Tidak perlu datang ke lokasi</li>
                      <li><i class="bi bi-check-circle-fill me-1"></i>Cocok untuk presenter luar kota/negara</li>
                      <li><i class="bi bi-check-circle-fill me-1"></i>Sertifikat & LoA tetap sama</li>
                    </ul>
                  </div>

                  <div class="mode-footer d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-outline-primary flex-grow-1">
                      Pilih Online
                      <i class="bi bi-arrow-right-short ms-1"></i>
                    </button>
                  </div>
                </form>
              </div>
            <?php endif; ?>

            <?php if (!$canOffline && !$canOnline): ?>
              <div class="col-12">
                <div class="empty-hint">
                  <i class="bi bi-lock me-1"></i>
                  Mode pendaftaran belum tersedia untuk presenter saat ini.
                  <div class="small mt-1">Silakan kembali ke detail event atau hubungi panitia.</div>
                </div>
              </div>
            <?php endif; ?>

          </div>
        </div>

        <!-- Column: Info Singkat -->
        <div class="col-12 col-lg-5">
          <div class="card card-glass-plain shadow-soft mb-3">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-info-circle"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Petunjuk Singkat</h6>
            </div>
            <div class="card-body">
              <ol class="small mb-0 ps-3">
                <li>Pilih mode kehadiran yang ingin kamu gunakan.</li>
                <li>Setelah memilih mode, kamu akan diarahkan untuk melengkapi data kontributor.</li>
                <li>Selanjutnya, kamu bisa mengunggah abstrak dan full paper sesuai alur.</li>
              </ol>
              <hr class="my-3">
              <div class="small text-muted">
                Jika kamu ragu memilih mode, kamu bisa menghubungi panitia terlebih dahulu
                atau membaca informasi lengkap di halaman detail event.
              </div>
            </div>
          </div>

          <div class="card card-glass-plain shadow-soft">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-shield-check"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Ringkasan Event</h6>
            </div>
            <div class="card-body">
              <div class="small text-muted mb-1">Nama Event</div>
              <div class="fw-semibold text-blue-900 mb-2"><?= esc($event['title'] ?? '-') ?></div>

              <div class="small text-muted mb-1">Format</div>
              <div class="fw-semibold mb-2"><?= esc($formatLabel($event['format'] ?? 'both')) ?></div>

              <div class="small text-muted mb-1">Tanggal & Waktu</div>
              <div class="fw-semibold mb-2">
                <?= esc($fmt($event['event_date'] ?? null)) ?>
                <?= !empty($event['event_time']) ? ' • '.esc($event['event_time']) : '' ?>
              </div>

              <?php if (!empty($event['location'])): ?>
                <div class="small text-muted mb-1">Lokasi</div>
                <div class="fw-semibold mb-2"><?= esc($event['location']) ?></div>
              <?php endif; ?>

              <?php if (!empty($event['zoom_link'])): ?>
                <div class="small text-muted mb-1">Tautan Online</div>
                <div class="fw-semibold mb-2">
                  <a href="<?= esc($event['zoom_link']) ?>" target="_blank" rel="noopener noreferrer">
                    Buka Link
                  </a>
                </div>
              <?php endif; ?>
            </div>
          </div>

        </div>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
:root{
  --blue-50:#eff6ff; --blue-100:#dbeafe; --blue-200:#bfdbfe; --blue-300:#93c5fd;
  --blue-400:#60a5fa; --blue-500:#3b82f6; --blue-600:#2563eb; --blue-700:#1d4ed8; --blue-800:#1e40af; --blue-900:#1e3a8a;
  --ink:#0f172a;
  --radius:16px;
  --side-pad: clamp(1rem, 2.3vw, 2.2rem);
}

.container-xxl{
  max-width:min(100%, 1560px);
  padding-left:var(--side-pad)!important;
  padding-right:var(--side-pad)!important;
  margin-inline:auto;
}

.page-wrap-blue{
  min-height:100vh;
  padding-top:72px;
  background:
    radial-gradient(1000px 380px at 10% -10%, rgba(59,130,246,.16), rgba(59,130,246,0) 60%),
    radial-gradient(1000px 380px at 90% 110%, rgba(59,130,246,.12), rgba(59,130,246,0) 70%),
    linear-gradient(180deg, var(--blue-50), #fff 40%);
}

/* HERO */
.card-hero{
  border:0;
  border-radius:var(--radius);
  overflow:hidden;
  box-shadow:0 12px 28px rgba(30,64,175,.16);
}
.card-hero .hero-body{
  background:linear-gradient(135deg,var(--blue-700),var(--blue-800));
  color:#fff;
  padding:1.8rem 1.2rem;
}
.hero-title{
  font-weight:800;
}
.hero-tag{
  display:inline-flex;
  align-items:center;
  padding:.16rem .6rem;
  border-radius:999px;
  font-size:.8rem;
  background:rgba(15,23,42,.22);
  border:1px solid rgba(148,163,184,.6);
}
.text-white-70{ color:rgba(255,255,255,.85)!important; }
.hero-side{
  min-width:200px;
  padding:.75rem 1rem;
  border-radius:14px;
  border:1px solid rgba(191,219,254,.6);
  background:linear-gradient(135deg,rgba(59,130,246,.18),rgba(15,23,42,.24));
}

/* hero footer */
.hero-tabs{
  background:#fff;
  padding:.6rem .8rem;
  border:1px solid rgba(30,64,175,.14);
  border-top:0;
  border-radius:0 0 var(--radius) var(--radius);
}

/* CARDS MODE */
.mode-card{
  border-radius:16px;
  border:1px solid rgba(148,163,184,.35);
  background:#ffffff;
  padding:1.1rem 1.1rem 1rem;
  display:flex;
  flex-direction:column;
  gap:.75rem;
  box-shadow:0 10px 26px rgba(15,23,42,.06);
  transition:transform .16s ease, box-shadow .16s ease, border-color .16s ease, background .16s ease;
}
.mode-card:hover{
  transform:translateY(-2px);
  border-color:rgba(37,99,235,.5);
  box-shadow:0 16px 32px rgba(15,23,42,.12);
}
.mode-card.mode-offline{
  background:radial-gradient(circle at 0 0,rgba(254,249,195,.45),transparent 60%), #ffffff;
}
.mode-card.mode-online{
  background:radial-gradient(circle at 100% 0,rgba(219,234,254,.6),transparent 60%), #ffffff;
}

.mode-header{
  border-bottom:1px dashed rgba(148,163,184,.55);
  padding-bottom:.7rem;
}
.mode-icon{
  width:44px;
  height:44px;
  border-radius:12px;
  display:grid;
  place-items:center;
  font-size:1.25rem;
  background:#eff6ff;
  color:#1e3a8a;
}
.mode-icon.online{
  background:#ecfdf5;
  color:#0f766e;
}
.mode-title{
  font-weight:800;
  color:#0f172a;
}
.mode-label .badge{
  font-size:.72rem;
  font-weight:700;
  border-radius:999px;
}
.bg-mode-offline{
  background:#fef3c7;
  color:#92400e;
}
.bg-mode-online{
  background:#dcfce7;
  color:#166534;
}

.mode-body{
  padding-top:.4rem;
}
.mode-desc{
  color:#4b5563;
}
.mode-benefit li{
  display:flex;
  align-items:flex-start;
  gap:.35rem;
  color:#475569;
  margin-bottom:.15rem;
}
.mode-benefit i{
  font-size:.9rem;
  margin-top:.12rem;
}

/* Prices */
.price-main .price-value{
  font-weight:800;
  font-size:1rem;
  color:#111827;
}
.price-caption{
  color:#6b7280;
}

/* Footer mode */
.mode-footer{
  margin-top:.3rem;
  border-top:1px dashed rgba(148,163,184,.45);
  padding-top:.65rem;
}

/* Generic cards */
.card-glass-plain{
  backdrop-filter:blur(6px);
  background:rgba(255,255,255,.96);
  border-radius:14px;
  border:1px solid rgba(148,163,184,.25);
}
.shadow-soft{
  box-shadow:0 10px 24px rgba(15,23,42,.08);
}
.bg-blue-soft{
  background:var(--blue-100);
  color:var(--blue-800);
  border-radius:12px;
  padding:.4rem .65rem;
  font-size:.84rem;
}

/* Buttons */
.btn{
  font-weight:800;
  border-radius:10px;
  font-size:.96rem;
  padding:.55rem 1rem;
}
.btn-primary{
  background:var(--blue-600);
  border-color:var(--blue-600);
  box-shadow:0 4px 12px rgba(37,99,235,.2);
}
.btn-outline-primary{
  border-color:var(--blue-300);
}

/* Empty state */
.empty-hint{
  color:#475569;
  background:#f8fafc;
  border:1px dashed #e2e8f0;
  border-radius:10px;
  padding:.9rem 1rem;
  font-weight:600;
}

.text-blue-900{ color:var(--blue-900)!important; }

/* Responsive */
@media (max-width:991.98px){
  .hero-side{
    width:100%;
    margin-top:.75rem;
  }
}
@media (max-width:767.98px){
  .card-hero .hero-body{ padding:1.4rem 1rem; }
}
@media (max-width:575.98px){
  .container-xxl{
    padding-left: calc(var(--side-pad) - .25rem) !important;
    padding-right: calc(var(--side-pad) - .25rem) !important;
  }
}
</style>