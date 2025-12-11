<?php
/**
 * @var array      $event
 * @var array      $speakers
 * @var array      $sponsors
 * @var array|null $poster
 * @var array      $fp_debug   // log debug sinkron FP → speaker
 */

$event     = $event     ?? [];
$speakers  = $speakers  ?? [];
$sponsors  = $sponsors  ?? [];
$poster    = $poster    ?? null;
$fp_debug  = $fp_debug  ?? [];
$titlePage = $title     ?? 'Detail Pengaturan Landing';

$hasPoster = !empty($poster['file_path']) || !empty($event['poster'] ?? null);

$eventDateLabel = '-';
if (!empty($event['event_date'])) {
    $ts    = strtotime($event['event_date']);
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $eventDateLabel = date('d', $ts) . ' ' . $bulan[(int)date('m', $ts)] . ' ' . date('Y', $ts);
}

// Poster URL
$posterUrl = null;
if ($hasPoster) {
    if (!empty($poster['file_path'] ?? null)) {
        $posterUrl = base_url($poster['file_path']);
    } elseif (!empty($event['poster'] ?? null)) {
        $posterUrl = base_url('uploads/poster/' . $event['poster']);
    }
}

// Pisah sponsors: sponsor vs partner
$brands   = [];
$partners = [];
foreach ($sponsors as $sp) {
    $type = strtolower($sp['type'] ?? 'sponsor');
    if ($type === 'partner') {
        $partners[] = $sp;
    } else {
        $brands[] = $sp;
    }
}
$totalSpeaker = count($speakers);
$totalSponsor = count($sponsors);
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_admin') ?>

<!-- Hide alert bootstrap lama (biar cuma SweetAlert yang muncul) -->
<div style="display:none;">
    <?= $this->include('partials/alerts') ?>
</div>

<div id="content">
    <main class="flex-fill landing-detail-wrap">
        <div class="container-xxl px-3 px-md-4 py-4">

            <!-- DEBUG PANEL: SINKRON FULLPAPER → SPEAKER -->
            <div class="mb-3">
                <?php if (!empty($fp_debug) && is_array($fp_debug)): ?>
                    <div class="card border border-warning shadow-sm">
                        <div class="card-header bg-warning-subtle text-warning-emphasis d-flex align-items-center justify-content-between py-2">
                            <div class="fw-bold">
                                <i class="bi bi-bug-fill me-1"></i>
                                Debug Sinkronisasi Presenter (Fullpaper → Speaker)
                            </div>
                            <button class="btn btn-sm btn-outline-warning" type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#fpDebugCollapse"
                                    aria-expanded="false"
                                    aria-controls="fpDebugCollapse">
                                Toggle Debug
                            </button>
                        </div>
                        <div class="collapse show" id="fpDebugCollapse">
                            <div class="card-body p-0" style="max-height:280px; overflow:auto;">
                                <pre class="m-0 p-3 small"
                                     style="
                                         white-space:pre-wrap;
                                         font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;
                                         background:#fff9e6;
                                     "><?= esc(implode("\n", $fp_debug)) ?></pre>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info small py-2 mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        Debug sinkronisasi fullpaper belum menghasilkan log untuk event ini.
                        <span class="d-inline-block ms-1 text-muted">
                            (Pastikan method <code>syncSpeakersFromAcceptedFullpapers()</code> sudah dipanggil dan mengisi <code>$fp_debug</code>.)
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- HERO -->
            <div class="card-hero mb-4">
                <div class="hero-body">
                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <i class="bi bi-display fs-4"></i>
                                <h3 class="hero-title mb-0">
                                    Detail Pengaturan Landing
                                </h3>
                            </div>
                            <div class="text-white-70 small mb-2">
                                Atur poster, pembicara, dan brand yang ditampilkan di halaman landing SNIA.
                            </div>

                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <span class="chip glass-chip">
                                    <i class="bi bi-megaphone me-1"></i>
                                    Event:
                                    <strong class="ms-1">
                                        <?= esc($event['title'] ?? '-') ?>
                                    </strong>
                                </span>

                                <span class="chip glass-chip">
                                    <i class="bi bi-calendar-event me-1"></i>
                                    <?= esc($eventDateLabel) ?>
                                </span>

                                <?php if (!empty($event['location'])): ?>
                                    <span class="chip glass-chip">
                                        <i class="bi bi-geo-alt me-1"></i>
                                        <?= esc($event['location']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="text-end">
                            <?php if (!empty($event['is_landing'])): ?>
                                <div class="badge rounded-pill bg-success-subtle text-success-emphasis px-3 py-2 mb-2 fw-semibold">
                                    <i class="bi bi-stars me-1"></i>Dipakai sebagai Landing
                                </div>
                            <?php else: ?>
                                <div class="badge rounded-pill bg-warning-subtle text-warning-emphasis px-3 py-2 mb-2 fw-semibold">
                                    <i class="bi bi-exclamation-triangle me-1"></i>Bukan Event Landing
                                </div>
                            <?php endif; ?>

                            <a href="<?= site_url('admin/landing') ?>" class="btn btn-sm btn-light-subtle">
                                <i class="bi bi-arrow-left me-1"></i>Kembali ke daftar event
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- GRID: poster kiri, kanan konten -->
            <div class="row g-3 g-lg-4 align-items-start">
                <!-- POSTER -->
                <div class="col-lg-4">
                    <div class="poster-card">
                        <div class="poster-inner">
                            <?php if ($hasPoster && $posterUrl): ?>
                                <div class="poster-frame">
                                    <img src="<?= esc($posterUrl) ?>" alt="Poster event" class="poster-img">
                                </div>
                            <?php else: ?>
                                <div class="poster-frame poster-placeholder">
                                    <i class="bi bi-image poster-placeholder-icon"></i>
                                    <div class="small text-muted mt-2">Poster belum diunggah</div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="poster-actions text-center mt-3">
                            <button type="button"
                                    class="btn btn-outline-primary btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#posterModal">
                                Ganti / Upload Poster
                            </button>
                        </div>
                    </div>
                </div>

                <!-- KANAN: PEMBICARA + BRAND/PARTNER -->
                <div class="col-lg-8">

                    <!-- Pembicara -->
                    <div class="block-card mb-3">
                        <div class="block-head d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="mb-0">Pembicara</h5>
                                <small class="text-muted">
                                    Daftar pembicara yang tampil di landing.
                                    Presenter dengan full paper ACC otomatis masuk ke sini (nonaktif dulu).
                                </small>
                            </div>

                            <div class="d-flex flex-column align-items-end gap-1">
                                <span class="badge <?= $totalSpeaker ? 'bg-success-subtle text-success-emphasis' : 'bg-danger-subtle text-danger' ?> small mb-1">
                                    <?= $totalSpeaker ? number_format($totalSpeaker) . ' data' : 'Belum ada' ?>
                                </span>
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#speakerModal"
                                        id="btnAddSpeaker">
                                    + Pembicara
                                </button>
                            </div>
                        </div>

                        <div class="block-body speaker-body">
                            <?php if ($totalSpeaker === 0): ?>
                                <div class="empty-box">
                                    <div class="empty-icon bg-danger-soft">
                                        <i class="bi bi-person-exclamation"></i>
                                    </div>
                                    <div class="empty-text">
                                        Belum ada pembicara untuk event ini.
                                        <div class="small text-muted mt-1">
                                            Klik tombol <strong>+ Pembicara</strong> atau biarkan sistem menarik otomatis dari presenter full paper ACC.
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <ul class="list-unstyled mb-0 speaker-list">
                                    <?php $no = 1; ?>
                                    <?php foreach ($speakers as $s): ?>
                                        <?php
                                        $photoUrlSpeaker = !empty($s['photo_path'])
                                            ? base_url($s['photo_path'])
                                            : null;
                                        $isActive = !empty($s['is_active']);
                                        ?>
                                        <li class="speaker-item">
                                            <div class="d-flex align-items-start justify-content-between gap-2">
                                                <div class="d-flex align-items-start gap-2">
                                                    <div class="speaker-order-pill">
                                                        <?= $no++ ?>
                                                    </div>
                                                    <div class="avatar-wrapper">
                                                        <?php if ($photoUrlSpeaker): ?>
                                                            <img src="<?= esc($photoUrlSpeaker) ?>"
                                                                 alt="<?= esc($s['name']) ?>"
                                                                 class="avatar-img">
                                                        <?php else: ?>
                                                            <div class="avatar-circle">
                                                                <span><?= strtoupper(mb_substr($s['name'], 0, 1, 'UTF-8')) ?></span>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div>
                                                        <div class="fw-semibold d-flex align-items-center gap-2">
                                                            <?= esc($s['name']) ?>
                                                            <span class="badge rounded-pill <?= $isActive ? 'bg-success-subtle text-success-emphasis' : 'bg-secondary-subtle text-secondary' ?> small">
                                                                <?= $isActive ? 'Aktif' : 'Nonaktif' ?>
                                                            </span>
                                                        </div>
                                                        <?php if (!empty($s['role'])): ?>
                                                            <div class="small text-primary fw-semibold">
                                                                <?= esc($s['role']) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if (!empty($s['affiliation'])): ?>
                                                            <div class="small text-muted">
                                                                <?= esc($s['affiliation']) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if (!empty($s['bio'])): ?>
                                                            <div class="small text-muted mt-1">
                                                                <?= esc($s['bio']) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <div class="d-flex flex-column flex-md-row gap-1">
                                                    <button type="button"
                                                            class="btn btn-outline-secondary btn-xs js-edit-speaker"
                                                            data-id="<?= $s['id'] ?>"
                                                            data-name="<?= esc($s['name']) ?>"
                                                            data-role="<?= esc($s['role'] ?? '') ?>"
                                                            data-affiliation="<?= esc($s['affiliation'] ?? '') ?>"
                                                            data-bio="<?= esc($s['bio'] ?? '') ?>"
                                                            data-is_active="<?= (int)($s['is_active'] ?? 0) ?>"
                                                            data-sort_order="<?= (int)($s['sort_order'] ?? 0) ?>"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#speakerModal">
                                                        Edit
                                                    </button>
                                                    <form action="<?= site_url('admin/landing/speaker/delete/' . ($event['id'] ?? 0) . '/' . $s['id']) ?>"
                                                          method="post"
                                                          class="js-delete-speaker-form">
                                                        <?= csrf_field() ?>
                                                        <button type="submit"
                                                                class="btn btn-outline-danger btn-xs">
                                                            Hapus
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>

                        <div class="block-footer text-center">
                            <span class="small text-muted">
                                Urutan tampil mengikuti urutan data (otomatis). Belum ada fitur drag &amp; drop.
                            </span>
                        </div>
                    </div>

                    <!-- BRAND & PARTNER -->
                    <div class="mb-2 d-flex justify-content-between align-items-center">
                        <h6 class="fw-semibold mb-0">Brand &amp; Partner</h6>
                        <button type="button"
                                class="btn btn-sm btn-outline-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#sponsorModal"
                                id="btnAddSponsor">
                            + Brand/Partner
                        </button>
                    </div>

                    <div class="row g-3">
                        <!-- BRAND (Sponsor) -->
                        <div class="col-md-6">
                            <div class="block-card h-100">
                                <div class="block-head d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-0">Brand (Sponsor)</h6>
                                        <small class="text-muted">Logo brand sponsor di landing.</small>
                                    </div>
                                    <span class="badge <?= count($brands) ? 'bg-success-subtle text-success-emphasis' : 'bg-danger-subtle text-danger' ?> small">
                                        <?= count($brands) ? count($brands).' brand' : 'Belum ada' ?>
                                    </span>
                                </div>

                                <div class="block-body">
                                    <?php if (count($brands) === 0): ?>
                                        <div class="empty-box">
                                            <div class="empty-icon bg-danger-soft">
                                                <i class="bi bi-building"></i>
                                            </div>
                                            <div class="empty-text">
                                                Belum ada brand sponsor.
                                                <div class="small text-muted mt-1">
                                                    Tambahkan lewat tombol <strong>+ Brand/Partner</strong>.
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <ul class="list-unstyled mb-0 sponsor-list">
                                            <?php foreach ($brands as $sp): ?>
                                                <?php $isActive = !empty($sp['is_active']); ?>
                                                <li class="sponsor-item">
                                                    <div class="d-flex justify-content-between align-items-center gap-2">
                                                        <div class="text-truncate">
                                                            <div class="fw-semibold text-truncate">
                                                                <?= esc($sp['name']) ?>
                                                            </div>
                                                            <span class="badge sponsor-badge-sponsor small">
                                                                Sponsor
                                                            </span>
                                                            <span class="badge rounded-pill <?= $isActive ? 'bg-success-subtle text-success-emphasis' : 'bg-secondary-subtle text-secondary' ?> small ms-1">
                                                                <?= $isActive ? 'Aktif' : 'Nonaktif' ?>
                                                            </span>
                                                        </div>
                                                        <div class="d-flex gap-1">
                                                            <button type="button"
                                                                    class="btn btn-outline-secondary btn-xs js-edit-sponsor"
                                                                    data-id="<?= $sp['id'] ?>"
                                                                    data-name="<?= esc($sp['name']) ?>"
                                                                    data-type="sponsor"
                                                                    data-is_active="<?= (int)($sp['is_active'] ?? 1) ?>"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#sponsorModal">
                                                                Edit
                                                            </button>
                                                            <form action="<?= site_url('admin/landing/sponsor/delete/' . ($event['id'] ?? 0) . '/' . $sp['id']) ?>"
                                                                  method="post"
                                                                  class="js-delete-sponsor-form">
                                                                <?= csrf_field() ?>
                                                                <button type="submit"
                                                                        class="btn btn-outline-danger btn-xs">
                                                                    Hapus
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- PARTNER -->
                        <div class="col-md-6">
                            <div class="block-card h-100">
                                <div class="block-head d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-0">Partner</h6>
                                        <small class="text-muted">Brand yang tampil sebagai partner.</small>
                                    </div>
                                    <span class="badge <?= count($partners) ? 'bg-success-subtle text-success-emphasis' : 'bg-danger-subtle text-danger' ?> small">
                                        <?= count($partners) ? count($partners).' partner' : 'Belum ada' ?>
                                    </span>
                                </div>

                                <div class="block-body">
                                    <?php if (count($partners) === 0): ?>
                                        <div class="empty-box">
                                            <div class="empty-icon bg-danger-soft">
                                                <i class="bi bi-people"></i>
                                            </div>
                                            <div class="empty-text">
                                                Belum ada partner yang ditambahkan.
                                                <div class="small text-muted mt-1">
                                                    Tambahkan lewat tombol <strong>+ Brand/Partner</strong>.
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <ul class="list-unstyled mb-0 sponsor-list">
                                            <?php foreach ($partners as $sp): ?>
                                                <?php $isActive = !empty($sp['is_active']); ?>
                                                <li class="sponsor-item">
                                                    <div class="d-flex justify-content-between align-items-center gap-2">
                                                        <div class="text-truncate">
                                                            <div class="fw-semibold text-truncate">
                                                                <?= esc($sp['name']) ?>
                                                            </div>
                                                            <span class="badge sponsor-badge-partner small">
                                                                Partner
                                                            </span>
                                                            <span class="badge rounded-pill <?= $isActive ? 'bg-success-subtle text-success-emphasis' : 'bg-secondary-subtle text-secondary' ?> small ms-1">
                                                                <?= $isActive ? 'Aktif' : 'Nonaktif' ?>
                                                            </span>
                                                        </div>
                                                        <div class="d-flex gap-1">
                                                            <button type="button"
                                                                    class="btn btn-outline-secondary btn-xs js-edit-sponsor"
                                                                    data-id="<?= $sp['id'] ?>"
                                                                    data-name="<?= esc($sp['name']) ?>"
                                                                    data-type="partner"
                                                                    data-is_active="<?= (int)($sp['is_active'] ?? 1) ?>"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#sponsorModal">
                                                                Edit
                                                            </button>
                                                            <form action="<?= site_url('admin/landing/sponsor/delete/' . ($event['id'] ?? 0) . '/' . $sp['id']) ?>"
                                                                  method="post"
                                                                  class="js-delete-sponsor-form">
                                                                <?= csrf_field() ?>
                                                                <button type="submit"
                                                                        class="btn btn-outline-danger btn-xs">
                                                                    Hapus
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Back -->
                    <div class="mt-4">
                        <a href="<?= site_url('admin/landing') ?>" class="btn btn-secondary btn-sm">
                            <i class="bi bi-arrow-left me-1"></i>Kembali ke daftar event landing
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<?= $this->include('partials/footer') ?>

<!-- MODALS -->

<!-- Poster Modal -->
<div class="modal fade" id="posterModal" tabindex="-1" aria-labelledby="posterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="posterModalLabel">
                    <i class="bi bi-image me-1"></i>Upload / Ganti Poster Event
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= site_url('admin/landing/poster/save/' . ($event['id'] ?? 0)) ?>"
                  method="post"
                  enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">File Poster <span class="text-danger">*</span></label>
                        <input type="file"
                               name="poster"
                               class="form-control"
                               accept="image/*"
                               required>
                        <div class="form-text">
                            Rekomendasi ukuran: 1080x1920 px (vertikal). Format JPG/PNG/WEBP, maksimal 2MB.
                        </div>
                    </div>

                    <?php if ($hasPoster && !empty($posterUrl)): ?>
                        <div class="mb-3">
                            <label class="form-label">Preview Poster Saat Ini</label>
                            <div class="text-center">
                                <img src="<?= esc($posterUrl) ?>"
                                     alt="Poster event"
                                     class="img-fluid rounded shadow-sm"
                                     style="max-height:320px; object-fit:contain;">
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>Simpan Poster
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Speaker Modal (Tambah + Edit) -->
<div class="modal fade" id="speakerModal" tabindex="-1" aria-labelledby="speakerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="speakerModalLabel">
                    <i class="bi bi-person-plus me-1"></i><span id="speakerModalTitleText">Tambah Pembicara</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= site_url('admin/landing/speaker/save/' . ($event['id'] ?? 0)) ?>"
                  method="post"
                  enctype="multipart/form-data"
                  id="speakerForm">
                <?= csrf_field() ?>
                <input type="hidden" name="mode" value="create">
                <input type="hidden" name="speaker_id" id="speakerIdField" value="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Pembicara <span class="text-danger">*</span></label>
                        <input type="text"
                               name="name"
                               id="speakerNameField"
                               class="form-control"
                               placeholder="Nama lengkap pembicara"
                               required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Keahlian / Topik Utama</label>
                        <input type="text"
                               name="expertise"
                               id="speakerRoleField"
                               class="form-control"
                               placeholder="Contoh: Ahli Kardiologi, Data Scientist, Pakar Hukum">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Institusi / Affiliasi</label>
                        <input type="text"
                               name="affiliation"
                               id="speakerAffField"
                               class="form-control"
                               placeholder="Contoh: Universitas X, PT ABC, Komunitas Y">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Deskripsi Singkat</label>
                        <textarea name="bio"
                                  id="speakerBioField"
                                  class="form-control"
                                  rows="3"
                                  placeholder="Contoh: Memiliki pengalaman lebih dari 10 tahun di bidang ..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Foto Profil (opsional)</label>
                        <input type="file"
                               name="photo"
                               id="speakerPhotoInput"
                               class="form-control"
                               accept="image/*">
                        <div class="form-text">
                            Format JPG/PNG/WEBP, maksimal 2MB. Disarankan foto rasio kotak (1:1).
                        </div>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input"
                               type="checkbox"
                               name="is_active"
                               id="speakerActive"
                               checked>
                        <label class="form-check-label" for="speakerActive">
                            Aktifkan pembicara ini di landing
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i><span id="speakerSubmitText">Simpan Pembicara</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Sponsor Modal (Tambah + Edit) -->
<div class="modal fade" id="sponsorModal" tabindex="-1" aria-labelledby="sponsorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sponsorModalLabel">
                    <i class="bi bi-building-add me-1"></i><span id="sponsorModalTitleText">Tambah Sponsor / Partner</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="sponsorForm"
                  action="<?= site_url('admin/landing/sponsor/save/' . ($event['id'] ?? 0)) ?>"
                  method="post"
                  enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="mode" value="create">
                <input type="hidden" name="sponsor_id" id="sponsorIdField" value="">

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Sponsor / Partner <span class="text-danger">*</span></label>
                        <input type="text"
                               name="name"
                               id="sponsorNameField"
                               class="form-control"
                               placeholder="Nama institusi / brand"
                               required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Jenis <span class="text-danger">*</span></label>
                        <select name="type"
                                id="sponsorTypeField"
                                class="form-select"
                                required>
                            <option value="sponsor">Sponsor</option>
                            <option value="partner">Partner</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Logo (opsional)</label>
                        <input type="file"
                               name="logo"
                               class="form-control"
                               accept="image/*">
                        <div class="form-text">
                            Format JPG/PNG/WEBP, maksimal 1MB. Jika kosong, hanya nama yang ditampilkan.
                        </div>
                    </div>

                    <div class="form-check mb-1">
                        <input class="form-check-input"
                               type="checkbox"
                               name="is_active"
                               id="sponsorActive"
                               checked>
                        <label class="form-check-label" for="sponsorActive">
                            Tampilkan brand ini di landing
                        </label>
                    </div>
                    <div class="small text-muted">
                        Urutan tampil mengikuti urutan data di sistem (otomatis).
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i><span id="sponsorSubmitText">Simpan Sponsor</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// =========================
//  SPEAKER MODAL HANDLER
// =========================
(function() {
    const speakerForm  = document.getElementById('speakerForm');
    const speakerModal = document.getElementById('speakerModal');

    const spIdField    = document.getElementById('speakerIdField');
    const spNameField  = document.getElementById('speakerNameField');
    const spRoleField  = document.getElementById('speakerRoleField');
    const spAffField   = document.getElementById('speakerAffField');
    const spBioField   = document.getElementById('speakerBioField');
    const spActiveCb   = document.getElementById('speakerActive');
    const spTitleText  = document.getElementById('speakerModalTitleText');
    const spSubmitText = document.getElementById('speakerSubmitText');

    function setSpeakerCreateMode() {
        if (!speakerForm) return;
        speakerForm.querySelector('input[name="mode"]').value = 'create';
        spIdField.value         = '';
        spNameField.value       = '';
        spRoleField.value       = '';
        spAffField.value        = '';
        spBioField.value        = '';
        spActiveCb.checked      = true;
        spTitleText.textContent = 'Tambah Pembicara';
        spSubmitText.textContent= 'Simpan Pembicara';
    }

    function setSpeakerEditMode(btn) {
        if (!speakerForm || !btn) return;
        speakerForm.querySelector('input[name="mode"]').value = 'edit';
        spIdField.value         = btn.dataset.id || '';
        spNameField.value       = btn.dataset.name || '';
        spRoleField.value       = btn.dataset.role || '';
        spAffField.value        = btn.dataset.affiliation || '';
        spBioField.value        = btn.dataset.bio || '';
        spActiveCb.checked      = (btn.dataset.is_active !== '0');
        spTitleText.textContent = 'Edit Pembicara';
        spSubmitText.textContent= 'Update Pembicara';
    }

    // Mode create saat klik tombol "+ Pembicara"
    const btnAddSpeaker = document.getElementById('btnAddSpeaker');
    btnAddSpeaker?.addEventListener('click', function() {
        setSpeakerCreateMode();
    });

    if (speakerModal) {
        speakerModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (button && button.classList && button.classList.contains('js-edit-speaker')) {
                setSpeakerEditMode(button);
            } else {
                setSpeakerCreateMode();
            }
        });
    }
})();

// =========================
//  SPONSOR MODAL HANDLER
// =========================
(function() {
    const sponsorForm   = document.getElementById('sponsorForm');
    const sponsorModal  = document.getElementById('sponsorModal');

    const spnIdField    = document.getElementById('sponsorIdField');
    const spnNameField  = document.getElementById('sponsorNameField');
    const spnTypeField  = document.getElementById('sponsorTypeField');
    const spnActiveCb   = document.getElementById('sponsorActive');
    const spnTitleText  = document.getElementById('sponsorModalTitleText');
    const spnSubmitTxt  = document.getElementById('sponsorSubmitText');

    function setSponsorCreateMode() {
        if (!sponsorForm) return;
        sponsorForm.querySelector('input[name="mode"]').value = 'create';
        spnIdField.value        = '';
        spnNameField.value      = '';
        spnTypeField.value      = 'sponsor';
        spnActiveCb.checked     = true;
        spnTitleText.textContent= 'Tambah Sponsor / Partner';
        spnSubmitTxt.textContent= 'Simpan Sponsor';
    }

    function setSponsorEditMode(btn) {
        if (!sponsorForm || !btn) return;
        sponsorForm.querySelector('input[name="mode"]').value = 'edit';
        spnIdField.value        = btn.dataset.id || '';
        spnNameField.value      = btn.dataset.name || '';
        spnTypeField.value      = btn.dataset.type || 'sponsor';
        spnActiveCb.checked     = (btn.dataset.is_active !== '0');
        spnTitleText.textContent= 'Edit Sponsor / Partner';
        spnSubmitTxt.textContent= 'Update Sponsor';
    }

    const btnAddSponsor = document.getElementById('btnAddSponsor');
    btnAddSponsor?.addEventListener('click', function() {
        setSponsorCreateMode();
    });

    if (sponsorModal) {
        sponsorModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (button && button.classList && button.classList.contains('js-edit-sponsor')) {
                setSponsorEditMode(button);
            } else {
                setSponsorCreateMode();
            }
        });
    }
})();
</script>

<!-- SWEETALERT (BIG MODAL) -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ========= FLASH MESSAGE (success / error / warning / info) =========
    <?php
    $flashSuccess = session()->getFlashdata('success');
    $flashError   = session()->getFlashdata('error');
    $flashWarning = session()->getFlashdata('warning');
    $flashInfo    = session()->getFlashdata('info');
    ?>

    <?php if ($flashSuccess): ?>
    Swal.fire({
        icon: 'success',
        title: 'Berhasil',
        html: <?= json_encode($flashSuccess) ?>,
        confirmButtonText: 'OK',
        confirmButtonColor: '#16a34a',
        width: 520
    });
    <?php elseif ($flashError): ?>
    Swal.fire({
        icon: 'error',
        title: 'Terjadi Kesalahan',
        html: <?= json_encode($flashError) ?>,
        confirmButtonText: 'OK',
        confirmButtonColor: '#dc2626',
        width: 520
    });
    <?php elseif ($flashWarning): ?>
    Swal.fire({
        icon: 'warning',
        title: 'Perhatian',
        html: <?= json_encode($flashWarning) ?>,
        confirmButtonText: 'OK',
        confirmButtonColor: '#eab308',
        width: 520
    });
    <?php elseif ($flashInfo): ?>
    Swal.fire({
        icon: 'info',
        title: 'Informasi',
        html: <?= json_encode($flashInfo) ?>,
        confirmButtonText: 'OK',
        confirmButtonColor: '#3b82f6',
        width: 520
    });
    <?php endif; ?>

    // ========= KONFIRMASI HAPUS SPEAKER (SweetAlert) =========
    document.querySelectorAll('.js-delete-speaker-form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Hapus pembicara ini?',
                text: 'Aksi ini tidak dapat dibatalkan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                width: 520
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });

    // ========= KONFIRMASI HAPUS SPONSOR/PARTNER (SweetAlert) =========
    document.querySelectorAll('.js-delete-sponsor-form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Hapus sponsor / partner ini?',
                text: 'Aksi ini tidak dapat dibatalkan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                width: 520
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
});
</script>

<style>
:root{
    --blue-50:#eff6ff; --blue-700:#1d4ed8; --blue-800:#1e40af;
    --muted:#6b7280; --ink:#0f172a;
    --radius:16px;
}

.landing-detail-wrap{
    min-height:100vh;
    padding-top:72px;
    background:
        radial-gradient(1000px 380px at 10% -10%, rgba(59,130,246,.16), rgba(59,130,246,0) 60%),
        radial-gradient(1000px 380px at 90% 110%, rgba(59,130,246,.12), rgba(59,130,246,0) 70%),
        linear-gradient(180deg, #eff6ff, #ffffff 45%);
}
.container-xxl{
    max-width:min(100%, 1560px);
    margin-inline:auto;
}

/* HERO */
.card-hero{
    border-radius:var(--radius);
    overflow:hidden;
    box-shadow:0 12px 28px rgba(30,64,175,.18);
}
.card-hero .hero-body{
    background:linear-gradient(135deg,var(--blue-700),var(--blue-800));
    color:#fff;
    padding:1.6rem 1.3rem;
}
.hero-title{ font-weight:800; }
.text-white-70{ color:rgba(255,255,255,.85)!important; }

.chip{
    display:inline-flex;
    align-items:center;
    gap:.3rem;
    padding:.3rem .7rem;
    border-radius:999px;
    font-size:.78rem;
    font-weight:700;
}
.glass-chip{
    color:#fff;
    border:1px solid rgba(255,255,255,.26);
    background:rgba(255,255,255,.14);
    backdrop-filter: blur(6px);
}

/* POSTER */
.poster-card{
    background:#fff;
    border-radius:16px;
    border:1px solid rgba(148,163,184,.4);
    padding:1rem;
    box-shadow:0 8px 20px rgba(15,23,42,.08);
}
.poster-inner{
    display:flex;
    justify-content:center;
    align-items:center;
}
.poster-frame{
    width:100%;
    max-width:320px;
    aspect-ratio:1/1;
    border-radius:14px;
    overflow:hidden;
    border:1px solid rgba(148,163,184,.4);
    background:#f3f4f6;
    display:flex;
    align-items:center;
    justify-content:center;
}
.poster-img{
    width:100%;
    height:100%;
    object-fit:cover;
}
.poster-placeholder-icon{
    font-size:3rem;
    color:#d1d5db;
}

/* BLOCK CARD */
.block-card{
    background:#fff;
    border-radius:16px;
    border:1px solid rgba(148,163,184,.35);
    box-shadow:0 10px 24px rgba(15,23,42,.08);
    display:flex;
    flex-direction:column;
}
.block-head{
    padding:.8rem 1rem .4rem 1rem;
    border-bottom:1px dashed rgba(209,213,219,.9);
}
.block-body{
    padding:.5rem 1rem .4rem 1rem;
}
.block-footer{
    padding:.6rem 1rem .8rem 1rem;
    border-top:1px dashed rgba(209,213,219,.9);
}

/* EMPTY */
.empty-box{
    display:flex;
    gap:.75rem;
    padding:.75rem .8rem;
    border-radius:12px;
    background:#f9fafb;
    border:1px dashed rgba(148,163,184,.7);
}
.empty-icon{
    width:34px;
    height:34px;
    border-radius:999px;
    display:flex;
    align-items:center;
    justify-content:center;
}
.empty-text{
    font-size:.9rem;
}
.bg-danger-soft{ background:#fee2e2; color:#b91c1c; }

/* SPEAKER */
.speaker-body{
    max-height:300px;
    overflow:auto;
}
.speaker-list{
    display:flex;
    flex-direction:column;
    gap:.4rem;
}
.speaker-item{
    padding:.4rem 0;
    border-bottom:1px dashed rgba(226,232,240,.9);
}
.speaker-item:last-child{
    border-bottom:0;
}
.avatar-wrapper{
    width:36px;
    height:36px;
    border-radius:999px;
    overflow:hidden;
    flex-shrink:0;
}
.avatar-img{
    width:100%;
    height:100%;
    object-fit:cover;
}
.avatar-circle{
    width:36px;
    height:36px;
    border-radius:999px;
    background:#e0f2fe;
    color:#0f172a;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:.9rem;
    font-weight:700;
}

.speaker-order-pill{
    width:26px;
    height:26px;
    border-radius:999px;
    background:#eff6ff;
    color:#1d4ed8;
    font-size:.75rem;
    font-weight:700;
    display:flex;
    align-items:center;
    justify-content:center;
    margin-top:4px;
}

/* SPONSOR */
.sponsor-list{
    display:flex;
    flex-direction:column;
    gap:.35rem;
}
.sponsor-item{
    padding:.35rem 0;
    border-bottom:1px dashed rgba(226,232,240,.9);
}
.sponsor-item:last-child{
    border-bottom:0;
}
.sponsor-badge-sponsor{
    background:#e5e7eb;
    color:#374151;
}
.sponsor-badge-partner{
    background:#dbeafe;
    color:#1d4ed8;
}

/* BADGE HELPERS */
.bg-success-subtle{ background-color:#d1fae5 !important; }
.text-success-emphasis{ color:#065f46 !important; }
.bg-warning-subtle{ background-color:#fef9c3 !important; }
.text-warning-emphasis{ color:#92400e !important; }
.bg-danger-subtle{ background-color:#fee2e2 !important; }

/* MODAL */
.modal-header{
    background:linear-gradient(135deg,var(--blue-700),var(--blue-800));
    color:#fff;
}
.modal-header .btn-close{
    filter: invert(1);
}

/* SMALL BUTTON */
.btn-xs{
    padding:.1rem .45rem;
    font-size:.72rem;
    border-radius:6px;
}
.btn-light-subtle{
    background:rgba(255,255,255,.2);
    color:#fff;
    border-color:transparent;
}
.btn-light-subtle:hover{
    background:#fff;
    color:#111827;
}

@media (max-width: 767.98px){
    .card-hero .hero-body{
        padding:1.3rem 1rem;
    }
    .speaker-body{
        max-height:none;
    }
}
</style>
