<?php
/**
 * @var array      $events
 * @var array|null $currentLanding
 * @var string     $title
 */

$title          = $title ?? 'Kelola Landing Page';
$events         = $events ?? [];
$currentLanding = $currentLanding ?? null;

// ID event yang benar-benar dipakai di landing (hanya satu)
$landingId      = isset($currentLanding['id']) ? (int)$currentLanding['id'] : 0;

// FLASH
$flashSuccess = session()->getFlashdata('success');
$flashError   = session()->getFlashdata('error');
$flashWarning = session()->getFlashdata('warning');
$flashInfo    = session()->getFlashdata('info');
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_admin') ?>

<div id="content">
    <main class="flex-fill page-wrap-blue">
        <div class="container-xxl px-3 px-md-4 py-4">

            <!-- ===== HERO ===== -->
            <div class="card-hero mb-4">
                <div class="hero-body">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div class="flex-grow-1">
                            <h3 class="hero-title mb-1">
                                <i class="bi bi-display me-2"></i><?= esc($title) ?>
                            </h3>
                            <div class="text-white-70 small">
                                Pilih <strong>satu event</strong> sebagai konten utama di landing SNIA.
                            </div>
                        </div>

                        <div class="hero-tools flex-grow-1" style="max-width:620px;">
                            <div class="input-group input-group-lg hero-search">
                                <span class="input-group-text bg-white border-0">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input id="searchInput" type="text" class="form-control border-0"
                                       placeholder="Cari event berdasarkan judul, lokasi, atau deskripsi…">
                                <button id="clearSearch" type="button" class="btn btn-light border-0 d-none">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap mt-3 gap-2 align-items-center">
                        <span class="chip glass-chip">
                            <i class="bi bi-hash me-1"></i>Total event
                            <strong class="ms-1"><?= number_format(count($events)) ?></strong>
                        </span>

                        <?php if (!empty($currentLanding)): ?>
                            <span class="chip glass-chip">
                                <i class="bi bi-stars me-1"></i>
                                Event landing:
                                <strong class="ms-1"><?= esc($currentLanding['title'] ?? '-') ?></strong>
                            </span>

                            <!-- CLEAR LANDING -->
                            <form action="<?= site_url('admin/landing/clear') ?>" method="post">
                                <?= csrf_field() ?>
                                <button type="submit"
                                        class="chip glass-chip btn btn-link p-0 border-0 text-decoration-none js-confirm"
                                        data-msg="Kosongkan event landing? Landing akan tidak menampilkan event apapun.">
                                    <i class="bi bi-x-circle me-1"></i>Hapus Pengaturan Landing
                                </button>
                            </form>
                        <?php else: ?>
                            <span class="chip glass-chip">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                Belum ada event yang ditandai sebagai landing
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ===== GRID EVENT ===== -->
            <div id="gridWrap" class="row g-3 row-cols-1 row-cols-md-2 row-cols-xl-3 row-cols-xxl-4">

                <?php if (empty($events)): ?>
                    <div class="col">
                        <div class="empty-hint">
                            <i class="bi bi-inbox me-1"></i>Belum ada event.
                        </div>
                    </div>
                <?php endif; ?>

                <?php foreach ($events as $e): ?>
                    <?php
                    $id         = (int)($e['id'] ?? 0);
                    $titleEvent = (string)($e['title'] ?? '-');
                    $desc       = trim((string)($e['description'] ?? ''));
                    $date       = $e['event_date'] ?? null;

                    $isLanding  = ($landingId > 0 && $id === $landingId);
                    $dateLabel  = $date ? date('d M Y', strtotime($date)) : '-';

                    // indikator poster & sponsor/partner
                    $hasPoster  = !empty($e['has_poster']);               // diisi di controller
                    $hasSponsor = (int)($e['sponsor_count'] ?? 0) > 0;    // diisi di controller

                    $searchable = strtolower(
                        $titleEvent . ' ' . $desc . ' ' . $dateLabel . ' ' . ($e['location'] ?? '')
                    );
                    ?>
                    <div class="col">
                        <div class="fp-card js-card <?= $isLanding ? 'fp-card--landing' : '' ?>"
                             data-id="<?= $id ?>"
                             data-search="<?= esc($searchable) ?>">

                            <!-- Ribbon Landing -->
                            <?php if ($isLanding): ?>
                                <div class="fp-ribbon">
                                    <i class="bi bi-stars me-1"></i>Landing Aktif
                                </div>
                            <?php endif; ?>

                            <div class="fp-head">
                                <div class="fp-head-main">
                                    <h6 class="mb-0 fp-title text-truncate" title="<?= esc($titleEvent) ?>">
                                        <?= esc($titleEvent) ?>
                                    </h6>
                                    <div class="fp-sub small text-muted">
                                        <?php if (!empty($date)): ?>
                                            <?= $dateLabel ?>
                                            <?php if (!empty($e['event_time'])): ?>
                                                • <?= date('H:i', strtotime($e['event_time'])) ?> WIB
                                            <?php endif; ?>
                                        <?php else: ?>
                                            Tanggal belum diatur
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($e['location'])): ?>
                                        <div class="fp-sub small text-muted">
                                            <i class="bi bi-geo-alt me-1"></i><?= esc($e['location']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <span class="status-pill <?= $isLanding ? 'pill-success' : 'pill-muted' ?> small">
                                    <?= $isLanding ? 'Sedang Dipakai' : 'Bukan Landing' ?>
                                </span>
                            </div>

                            <?php if ($desc !== ''): ?>
                                <div class="fp-desc small text-muted mb-2">
                                    <?= esc(mb_strimwidth($desc, 0, 110, '…')) ?>
                                </div>
                            <?php endif; ?>

                            <!-- ===== INDICATOR POSTER & SPONSOR/PARTNER ===== -->
                            <div class="fp-indicators mb-2">
                                <span class="ind-pill <?= $hasPoster ? 'ind-ok' : 'ind-miss' ?>">
                                    <i class="bi bi-image me-1"></i>Poster
                                </span>
                                <span class="ind-pill <?= $hasSponsor ? 'ind-ok' : 'ind-miss' ?>">
                                    <i class="bi bi-people me-1"></i>Sponsor/Partner
                                </span>
                            </div>

                            <ul class="meta-list">
                                <li>
                                    <span><i class="bi bi-calendar-event me-1"></i>Tanggal</span>
                                    <strong class="text-end"><?= esc($dateLabel) ?></strong>
                                </li>
                                <li>
                                    <span><i class="bi bi-broadcast-pin me-1"></i>Status Event</span>
                                    <span class="text-end">
                                        <?php if (!empty($e['is_active'])): ?>
                                            <span class="badge bg-success-subtle text-success-emphasis small">Event Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary-emphasis small">Event Nonaktif</span>
                                        <?php endif; ?>
                                    </span>
                                </li>
                                <li>
                                    <span><i class="bi bi-person-check me-1"></i>Pendaftaran</span>
                                    <span class="text-end">
                                        <?php if (!empty($e['registration_active'])): ?>
                                            <span class="badge bg-primary-subtle text-primary-emphasis small">Dibuka</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning-emphasis small">Ditutup</span>
                                        <?php endif; ?>
                                    </span>
                                </li>
                            </ul>

                            <div class="fp-foot flex-column align-items-stretch">
                                <div class="d-flex gap-2">
                                    <!-- DETAIL -->
                                    <a href="<?= site_url('admin/landing/detail/' . $id) ?>"
                                       class="btn btn-outline-secondary flex-fill btn-sm">
                                        <i class="bi bi-gear me-1"></i>Detail
                                    </a>

                                    <!-- SET / UNSET -->
                                    <?php if ($isLanding): ?>
                                        <form action="<?= site_url('admin/landing/unset-event/' . $id) ?>"
                                              method="post" class="flex-fill d-flex">
                                            <?= csrf_field() ?>
                                            <button type="submit"
                                                    class="btn btn-success flex-fill btn-sm js-confirm"
                                                    data-msg="Lepas event ini dari landing page?">
                                                <i class="bi bi-check2-circle me-1"></i>Lepas Landing
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form action="<?= site_url('admin/landing/set-event/' . $id) ?>"
                                              method="post" class="flex-fill d-flex">
                                            <?= csrf_field() ?>
                                            <button type="submit"
                                                    class="btn btn-primary flex-fill btn-sm js-confirm"
                                                    data-msg="Jadikan event ini sebagai event utama di landing? Event lain akan otomatis dilepas.">
                                                <i class="bi bi-toggle-on me-1"></i>Jadikan Landing
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>

                        </div>
                    </div>
                <?php endforeach; ?>

            </div>
        </div>
    </main>
</div>

<?= $this->include('partials/footer') ?>

<!-- ========== STYLE (CSS) S ATU FILE ========== -->
<style>
:root{
    --blue-50:#eff6ff; --blue-100:#dbeafe; --blue-200:#bfdbfe; --blue-300:#93c5fd;
    --blue-400:#60a5fa; --blue-500:#3b82f6; --blue-600:#2563eb; --blue-700:#1d4ed8; --blue-800:#1e40af; --blue-900:#1e3a8a;
    --ink:#0f172a;
    --radius:14px;
    --side-pad:clamp(1rem, 2.3vw, 2.2rem);
    --card-min-h: 260px;
}

/* Layout & background */
.container-xxl{
    max-width:min(100%, 1560px);
    padding-left:var(--side-pad)!important;
    padding-right:var(--side-pad)!important;
    margin-inline:auto;
}
body{
    font-family:'Segoe UI', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
    font-size:15.5px;
    line-height:1.6;
    color:var(--ink);
}
.page-wrap-blue{
    min-height:100vh;
    padding-top:72px;
    background:
        radial-gradient(1000px 380px at 10% -10%, rgba(59,130,246,.18), rgba(59,130,246,0) 60%),
        radial-gradient(1000px 380px at 90% 110%, rgba(59,130,246,.12), rgba(59,130,246,0) 70%),
        linear-gradient(180deg, var(--blue-50), #fff 40%);
}

/* HERO */
.card-hero{
    border:0;
    border-radius:var(--radius);
    overflow:hidden;
    box-shadow:0 12px 28px rgba(30,64,175,.18);
}
.card-hero .hero-body{
    background:radial-gradient(circle at 0 0, rgba(59,130,246,.45), transparent 55%),
               radial-gradient(circle at 100% 100%, rgba(30,64,175,.55), transparent 55%),
               linear-gradient(135deg, var(--blue-700), var(--blue-900));
    color:#fff;
    padding:1.8rem 1.4rem;
}
.hero-title{
    font-weight:800;
}
.text-white-70{
    color:rgba(255,255,255,.88)!important;
}
.hero-search{
    border-radius:12px;
    overflow:hidden;
    box-shadow:0 10px 24px rgba(15,23,42,.35);
}
.hero-search .input-group-text{
    border:0;
}
.hero-search .form-control{
    border:0;
}
.hero-search .btn{
    border:0;
}

/* Chips */
.chip{
    display:inline-flex;
    align-items:center;
    gap:.25rem;
    padding:.26rem .7rem;
    border-radius:999px;
    border:1px solid rgba(255,255,255,.35);
    font-size:.85rem;
    font-weight:600;
    color:#e5e7eb;
    background:rgba(15,23,42,.15);
    backdrop-filter:blur(8px);
}
.glass-chip{
    background:rgba(15,23,42,.22);
}

/* Cards */
.fp-card{
    position:relative;
    border-radius:14px;
    background:#ffffff;
    border:1px solid rgba(148,163,184,.45);
    box-shadow:0 12px 28px rgba(15,23,42,.16);
    padding:0.85rem 0.9rem 0.9rem;
    display:flex;
    flex-direction:column;
    min-height:var(--card-min-h);
    transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease, background .18s ease;
}
.fp-card:hover{
    transform:translateY(-2px);
    box-shadow:0 16px 32px rgba(15,23,42,.2);
    border-color:rgba(59,130,246,.8);
}
.fp-card--landing{
    border-width:2px;
    border-color:var(--blue-500);
    box-shadow:0 18px 40px rgba(59,130,246,.35);
}

/* Ribbon */
.fp-ribbon{
    position:absolute;
    top:10px;
    left:-5px;
    padding:.25rem .7rem .25rem 1rem;
    font-size:.78rem;
    font-weight:700;
    color:#fefce8;
    background:linear-gradient(135deg,#facc15,#f97316);
    border-radius:0 999px 999px 0;
    box-shadow:0 10px 22px rgba(248,250,252,.35);
}
.fp-ribbon::before{
    content:'';
    position:absolute;
    left:0;
    bottom:-6px;
    border-width:6px 6px 0 0;
    border-style:solid;
    border-color:#b45309 transparent transparent transparent;
}

/* Card Head */
.fp-head{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:.75rem;
    margin-bottom:.35rem;
}
.fp-head-main{
    min-width:0;
}
.fp-title{
    line-height:1.35;
    max-width:72%;
    color:#0f172a;
    font-weight:700;
}
.fp-sub{
    color:#6b7280;
}

/* Status pill */
.status-pill{
    font-weight:800;
    font-size:.8rem;
    padding:.28rem .6rem;
    border-radius:999px;
    white-space:nowrap;
    border:1px solid rgba(148,163,184,.7);
}
.pill-success{
    background:#dcfce7;
    color:#166534;
    border-color:#4ade80;
}
.pill-muted{
    background:#f3f4f6;
    color:#4b5563;
}

/* Desc */
.fp-desc{
    margin-bottom:.25rem;
}

/* Indicator bar */
.fp-indicators{
    display:flex;
    flex-wrap:wrap;
    gap:.35rem;
}
.ind-pill{
    display:inline-flex;
    align-items:center;
    padding:.18rem .5rem;
    font-size:.78rem;
    border-radius:999px;
    border:1px solid #e5e7eb;
    font-weight:600;
}
.ind-ok{
    background:#dcfce7;
    color:#166534;
    border-color:#4ade80;
}
.ind-miss{
    background:#fef2f2;
    color:#b91c1c;
    border-color:#fecaca;
}

/* Meta list */
.meta-list{
    list-style:none;
    padding:0;
    margin:0 0 .4rem;
    border-top:1px dashed rgba(148,163,184,.7);
    padding-top:.4rem;
    font-size:.85rem;
}
.meta-list li{
    display:flex;
    justify-content:space-between;
    gap:.75rem;
    padding:.12rem 0;
    color:#4b5563;
}

/* Foot */
.fp-foot{
    display:flex;
    gap:.6rem;
    margin-top:auto;
    padding-top:.55rem;
    border-top:1px dashed rgba(148,163,184,.7);
}
.btn{
    border-radius:10px;
    font-weight:700;
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

/* Responsive */
@media (max-width:767.98px){
    .card-hero .hero-body{
        padding:1.4rem 1.1rem;
    }
    .hero-title{
        font-size:1.2rem;
    }
    .fp-title{
        max-width:68%;
    }
    :root{
        --card-min-h: 240px;
    }
}
@media (max-width:575.98px){
    .container-xxl{
        padding-left:calc(var(--side-pad) - .25rem)!important;
        padding-right:calc(var(--side-pad) - .25rem)!important;
    }
}
</style>

<!-- ========== SCRIPT ========== -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
/* ================= FLASH MESSAGE -> SWEETALERT ================= */
(function () {
    const flash = {
        success: <?= json_encode($flashSuccess) ?>,
        error: <?= json_encode($flashError) ?>,
        warning: <?= json_encode($flashWarning) ?>,
        info: <?= json_encode($flashInfo) ?>
    };

    for (const type in flash) {
        if (flash[type]) {
            Swal.fire({
                icon: type,
                title: type === 'success' ? 'Berhasil' :
                       type === 'error'   ? 'Gagal' :
                       type === 'warning' ? 'Peringatan' :
                                            'Informasi',
                text: flash[type],
                showConfirmButton: false,
                timer: 1800
            });
        }
    }
})();

/* ================= SWEETALERT CONFIRM BUTTON ================= */
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.js-confirm');
    if (!btn) return;

    e.preventDefault();

    const form = btn.closest('form');
    if (!form) return;

    const msg  = btn.dataset.msg || "Lanjutkan tindakan ini?";

    Swal.fire({
        title: 'Konfirmasi',
        text: msg,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya',
        cancelButtonText: 'Batal'
    }).then((res) => {
        if (res.isConfirmed) form.submit();
    });
});

/* ================= SEARCH FILTER ================= */
(function () {
    const input    = document.getElementById('searchInput');
    const clearBtn = document.getElementById('clearSearch');
    const grid     = document.getElementById('gridWrap');

    if (!input || !grid) return;

    function cards() {
        return [...grid.querySelectorAll('.js-card')];
    }

    function applyFilter() {
        const q = (input.value || '').toLowerCase().trim();

        cards().forEach(card => {
            const hay = (card.dataset.search || card.textContent || '').toLowerCase();
            card.closest('.col').style.display = !q || hay.includes(q) ? '' : 'none';
        });

        if (clearBtn) {
            clearBtn.classList.toggle('d-none', !q);
        }
    }

    input.addEventListener('input', applyFilter);

    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            input.value = '';
            applyFilter();
            input.focus();
        });
    }

    // Initial render
    applyFilter();
})();
</script>
