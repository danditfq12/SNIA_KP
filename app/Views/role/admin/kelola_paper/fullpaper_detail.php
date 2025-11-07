<?php
// ===================== Data dari controller =====================
$submission       = $submission ?? [];
$author           = $author ?? ['name'=>null,'email'=>null];
$coauthors        = $coauthors ?? [];
$history          = $history ?? [];
$reviewers        = $reviewers ?? [];

// HANYA reviewer aktif (accepted/pending) – dari controller
$assigned         = $assignedReviewers ?? [];

// Daftar reviewer yang MENOLAK – dari controller
$declined         = $declinedReviewers ?? [];

$absReviewers     = $abstractReviewers ?? [];
$abstractStatus   = strtolower((string)($abstractStatus ?? ''));
$fpReviews        = $fpReviews ?? [];

// Tambahan dari controller (kategori & people abstrak)
$absKategoriName  = trim((string)($absKategoriName ?? ''));
$absContributors  = is_array($absContributors ?? null) ? $absContributors : [];

// ===================== View-Model =====================
$VM = $vm ?? [];
$status        = $VM['status']        ?? 'NONE';
$badgeMap      = $VM['badgeMap']      ?? [];
$statusText    = $VM['statusText']    ?? [];
$uploadedAt    = $VM['uploadedAt']    ?? '—';

// Zero-based revision display (upload pertama = 0)
$rawRevisiKe   = (int)($VM['revisiKe'] ?? 1);
$revisiKe      = max(0, $rawRevisiKe - 1);

$submissionId  = (int)($VM['submissionId'] ?? 0);
$backUrl       = $VM['backUrl']       ?? site_url('admin/kelola-paper');
$downloadUrl   = $VM['downloadUrl']   ?? '';
$previewUrl    = $VM['previewUrl']    ?? '';
$gdocs         = $VM['gdocs']         ?? '';

$rvStatMap     = $VM['rvStatMap']     ?? [];
$assignedCount = (int)($VM['assignedCount'] ?? 0);
$completedCount= (int)($VM['completedCount'] ?? 0);
$maxReviewer   = (int)($VM['maxReviewer'] ?? 3);
$quotaFull     = (bool)($VM['quotaFull'] ?? false);

$hasReviewDecision = (bool)($VM['hasReviewDecision'] ?? false);
$assignStatusMap   = $VM['assignStatusMap'] ?? ['default'=>['secondary','Pending']];

// Formatter kecil
$fmtDT = fn($s)=> $s ? date('d M Y H:i', strtotime($s)) : '—';

// Badge kelas
$badge = $badgeMap[$status] ?? 'secondary';

// ==== Count penolakan dari variabel khusus ====
$declinedCount = is_array($declined) ? count($declined) : 0;

// Ambil daftar ID reviewer yang menolak untuk validasi frontend
$declinedIds = [];
if ($declined) {
  foreach ($declined as $d) $declinedIds[] = (int)($d['reviewer_id'] ?? 0);
}
$declinedIds = array_values(array_unique(array_filter($declinedIds)));

// ===================== FLAG: Abstrak belum direview? =====================
$absHasDecision = false;
if (is_array($absReviewers) && $absReviewers) {
  foreach ($absReviewers as $rv) {
    $k = strtolower((string)($rv['status'] ?? ''));
    if (in_array($k, ['accepted','diterima','rejected','ditolak','revision','revisi'], true)) {
      $absHasDecision = true;
      break;
    }
  }
}
$absNotReviewed = !$absHasDecision;
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_admin') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl px-3 px-md-4 py-4">

      <!-- HERO -->
      <div class="card-hero mb-4">
        <div class="hero-body">
          <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
            <div>
              <h3 class="hero-title mb-1">
                <i class="bi bi-file-earmark-text me-2"></i>Detail Full Paper
              </h3>
              <div class="text-white-70 small">
                <?= esc($submission['event_title'] ?? '-') ?>
                <?php if(!empty($submission['title'])): ?>
                  <span class="d-inline-block mx-2">•</span>
                  <span class="fw-semibold"><?= esc($submission['title']) ?></span>
                <?php endif; ?>
              </div>
              <div class="mt-2 d-flex flex-wrap align-items-center gap-2">
                <span class="badge bg-<?= $badge ?>"><?= esc($statusText[$status] ?? $status) ?></span>
                <?php if ($absKategoriName !== ''): ?>
                  <span class="badge bg-secondary-subtle"><i class="bi bi-tags me-1"></i><?= esc($absKategoriName) ?></span>
                <?php endif; ?>
                <span class="badge bg-secondary-subtle">Revisi ke-<?= (int)$revisiKe ?></span>
                <?php if ($uploadedAt !== '—'): ?>
                  <span class="badge bg-secondary-subtle"><i class="bi bi-clock me-1"></i><?= esc($uploadedAt) ?></span>
                <?php endif; ?>
              </div>
            </div>
            <div class="d-flex gap-2 ms-auto">
              <a href="<?= esc($backUrl) ?>" class="btn btn-light btn-sm">
                <i class="bi bi-arrow-left"></i> Kembali
              </a>
              <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#statusModal">
                <i class="bi bi-flag me-1"></i> Keputusan
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-3">
        <!-- KIRI (konten utama) -->
        <div class="col-12 col-xl-8">
          <!-- Info FP -->
          <div class="card shadow-soft card-glass-plain mb-3">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-info-circle"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Informasi Full Paper</h6>
            </div>
            <div class="card-body">
              <ul class="meta-list mb-3">
                <li><span>Diunggah</span><strong class="text-blue-900"><?= esc($uploadedAt) ?></strong></li>
                <li><span>Judul</span><strong class="text-blue-900"><?= esc($submission['title'] ?? '-') ?></strong></li>
                <li><span>Penulis</span>
                  <strong class="text-blue-900">
                    <?= esc($author['name'] ?? '-') ?>
                    <?php if(!empty($author['email'])): ?>
                      <small class="text-muted ms-1">&lt;<?= esc($author['email']) ?>&gt;</small>
                    <?php endif; ?>
                  </strong>
                </li>
                <li><span>Kategori Abstrak</span>
                  <strong class="text-blue-900"><?= $absKategoriName !== '' ? esc($absKategoriName) : '—' ?></strong>
                </li>
              </ul>

              <?php if (!empty($absContributors)): ?>
                <div class="mt-1">
                  <div class="fw-semibold mb-1">Contributor</div>
                  <div class="chip-row">
                    <?php foreach ($absContributors as $nm): ?>
                      <span class="chip"><i class="bi bi-person me-1"></i><?= esc($nm) ?></span>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Preview PDF -->
          <div class="card shadow-soft card-glass-plain mb-3">
            <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-blue-soft"><i class="bi bi-file-earmark-pdf"></i></span>
                <h6 class="mb-0 fw-semibold text-blue-900">Pratinjau Full Paper</h6>
              </div>
              <div class="btn-group btn-group-sm">
                <?php if ($downloadUrl): ?>
                  <a class="btn btn-outline-secondary" href="<?= esc($downloadUrl) ?>"><i class="bi bi-download"></i></a>
                <?php endif; ?>
                <?php if ($previewUrl): ?>
                  <a class="btn btn-outline-secondary" target="_blank" rel="noopener" href="<?= esc($previewUrl) ?>"><i class="bi bi-box-arrow-up-right"></i></a>
                <?php endif; ?>
                <button type="button" class="btn btn-outline-secondary" id="btnToggleSize" data-state="max"><i class="bi bi-arrows-fullscreen"></i></button>
              </div>
            </div>
            <div class="card-body">
              <?php if ($previewUrl): ?>
                <div class="pdf-wrap is-min" id="pdfWrap">
                  <iframe class="pdf-frame" title="Preview PDF" id="pdfFrame"
                          src="<?= esc($previewUrl) ?>#toolbar=1&navpanes=0"></iframe>
                </div>
                <div class="small text-muted mt-2">
                  Jika pratinjau kosong, klik ikon <b>↗</b> untuk membuka di tab baru
                  <?php if (!empty($gdocs)): ?>
                    atau gunakan <a target="_blank" rel="noopener" href="<?= esc($gdocs) ?>">Google Docs Viewer</a>.
                  <?php endif; ?>
                </div>
              <?php else: ?>
                <div class="empty-hint"><i class="bi bi-info-circle me-1"></i> Belum ada file untuk dipratinjau.</div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Riwayat Revisi -->
          <?php if ($hasReviewDecision): ?>
            <div class="card shadow-soft card-glass-plain">
              <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
                <span class="badge bg-blue-soft"><i class="bi bi-clock-history"></i></span>
                <h6 class="mb-0 fw-semibold text-blue-900">Riwayat Revisi</h6>
              </div>
              <div class="card-body pt-2">
                <?php if (!empty($history)): ?>
                  <div class="table-responsive">
                    <table class="table table-sm align-middle">
                      <thead class="table-light">
                        <tr><th>#</th><th>Revisi</th><th>Status</th><th>Waktu Upload</th><th>Aksi</th></tr>
                      </thead>
                      <tbody>
                        <?php $no=1; foreach ($history as $h):
                          $st = strtoupper($h['full_paper_status'] ?? 'NONE');
                          $cls = $badgeMap[$st] ?? 'secondary';
                          $ts  = !empty($h['full_paper_uploaded_at']) ? date('d M Y H:i', strtotime($h['full_paper_uploaded_at'])) : '—';
                          $revRaw = (int)($h['revisi_ke'] ?? 1);
                          $revDisp = max(0, $revRaw - 1);
                        ?>
                          <tr>
                            <td><?= $no++ ?></td>
                            <td><?= (int)$revDisp ?></td>
                            <td><span class="badge bg-<?= $cls ?>"><?= esc($statusText[$st] ?? $st) ?></span></td>
                            <td><?= esc($ts) ?></td>
                            <td><a class="btn btn-ghost btn-xs" href="<?= site_url('admin/fullpaper/detail/'.(int)$h['id']) ?>"><i class="bi bi-eye me-1"></i>Lihat</a></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                <?php else: ?>
                  <div class="empty-hint"><i class="bi bi-info-circle me-1"></i> Belum ada riwayat revisi.</div>
                <?php endif; ?>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <!-- KANAN (sidebar) -->
        <div class="col-12 col-xl-4">
          <div class="sticky-aside">
            <!-- Status FP -->
            <div class="card shadow-soft card-glass-plain mb-3">
              <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
                <span class="badge bg-blue-soft"><i class="bi bi-flag"></i></span>
                <h6 class="mb-0 fw-semibold text-blue-900">Status</h6>
              </div>
              <div class="card-body">
                <?php if ($status === 'NONE'): ?>
                  <div class="alert bg-blue-soft-2 text-blue-900 border-0 fw-semibold mb-0"><i class="bi bi-info-circle me-1"></i> Belum ada Full Paper yang diunggah.</div>
                <?php elseif ($status === 'UPLOADED'): ?>
                  <div class="alert bg-blue-soft-2 text-blue-900 border-0 fw-semibold mb-0"><i class="bi bi-hourglass-split me-1"></i> Sedang direview.</div>
                <?php elseif ($status === 'REVISION'): ?>
                  <div class="alert alert-warning fw-semibold mb-0"><i class="bi bi-arrow-repeat me-1"></i> Perlu revisi.</div>
                <?php elseif ($status === 'REJECTED'): ?>
                  <div class="alert alert-danger fw-semibold mb-0"><i class="bi bi-x-octagon me-1"></i> Ditolak.</div>
                <?php elseif ($status === 'ACCEPTED'): ?>
                  <div class="alert alert-success fw-semibold mb-0"><i class="bi bi-check2-circle me-1"></i> Diterima.</div>
                <?php endif; ?>
              </div>
            </div>

            <!-- === Satu Alert Saja: Abstrak belum direview === -->
            <?php if ($absNotReviewed): ?>
              <div class="alert alert-warning shadow-soft border-0 mb-3">
                <i class="bi bi-exclamation-triangle me-2"></i>
                Abstrak belum direview. Penugasan reviewer Full Paper dinonaktifkan sementara.
              </div>
            <?php endif; ?>

            <!-- Reviewer Ditugaskan (AKTIF SAJA) -->
            <div class="card shadow-soft card-glass-plain mb-3">
              <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                  <span class="badge bg-blue-soft"><i class="bi bi-people"></i></span>
                  <h6 class="mb-0 fw-semibold text-blue-900">Reviewer Ditugaskan</h6>
                </div>
                <span class="badge bg-light text-muted"><?= $assignedCount ?> / max <?= $maxReviewer ?><?= $quotaFull ? ' — Penuh' : '' ?></span>
              </div>
              <div class="card-body">
                <?php if (empty($assigned)): ?>
                  <div class="empty-hint"><i class="bi bi-info-circle me-1"></i> Belum ada reviewer yang ditugaskan.</div>
                <?php else: ?>
                  <div class="vstack gap-2">
                    <?php foreach ($assigned as $ar):
                      // status review (progress)
                      $k = strtolower($ar['status'] ?? 'pending');
                      $m = $rvStatMap[$k] ?? ['—','secondary'];

                      $stAt = !empty($ar['status_at']) ? $fmtDT($ar['status_at']) : '—';
                      $asAt = !empty($ar['assigned_at']) ? $fmtDT($ar['assigned_at']) : '—';

                      // assignment status (seharusnya accepted/pending di sini)
                      $assignKey = strtolower((string)($ar['assignment_status'] ?? ''));
                      $assignKey = $assignKey ?: 'default';
                      [$csa, $lsa] = $assignStatusMap[$assignKey] ?? $assignStatusMap['default'];

                      // headline badge
                      $headlineBadge = ($assignKey === 'accepted') ? $m : ['Pending','secondary'];

                      $unassignUrl = site_url('admin/fullpaper/unassign/'.$submissionId.'/'.(int)$ar['reviewer_id']);
                    ?>
                    <div class="p-2 border rounded-3">
                      <div class="d-flex align-items-start justify-content-between gap-2">
                        <div class="me-2">
                          <div class="fw-semibold text-blue-900">
                            <?= esc($ar['name'] ?? '-') ?>
                            <?php if (!empty($ar['email'])): ?><small class="text-muted ms-1">&lt;<?= esc($ar['email']) ?>&gt;</small><?php endif; ?>
                          </div>
                          <div class="small text-muted">Tugas: <span class="badge bg-<?= $csa ?>"><?= $lsa ?></span></div>
                        </div>
                        <div class="text-end">
                          <span class="badge bg-<?= esc($headlineBadge[1]) ?>"><?= esc($headlineBadge[0]) ?></span>
                          <div class="mt-2">
                            <a href="#"
                               class="btn btn-outline-danger btn-xs js-unassign"
                               data-url="<?= esc($unassignUrl) ?>"
                               data-name="<?= esc($ar['name'] ?? 'Reviewer') ?>">
                              <i class="bi bi-x-circle me-1"></i>Batalkan
                            </a>
                          </div>
                        </div>
                      </div>
                      <div class="mt-1 small text-muted">
                        Update: <?= esc($stAt) ?> • Ditugaskan: <?= esc($asAt) ?>
                      </div>
                    </div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>

                <!-- Tambah reviewer -->
                <div class="mt-3 p-2 rounded border bg-light">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="fw-semibold">Tambah Reviewer</div>
                    <small class="text-muted">Review masuk: <?= (int)$completedCount ?></small>
                  </div>
                  <?php if (!$quotaFull): ?>
                    <form method="post" action="<?= site_url('admin/fullpaper/assign/'.$submissionId) ?>" class="row g-2" id="assignForm">
                      <?= csrf_field() ?>
                      <div class="col-12">
                        <select name="reviewer_id"
                                class="form-select form-select-sm"
                                required id="reviewerSelect"
                                <?= $absNotReviewed ? 'disabled' : '' ?>>
                          <option value=""><?= $absNotReviewed ? '-- menunggu review abstrak --' : '-- pilih --' ?></option>
                          <?php if (!empty($reviewers)): ?>
                            <?php $declinedLookup = array_flip($declinedIds); ?>
                            <?php foreach ($reviewers as $rv):
                              $rid = (int)($rv['id'] ?? 0);
                              $isDeclinedBefore = isset($declinedLookup[$rid]);
                              $label = ($rv['name'] ?? 'Reviewer') . (!empty($rv['email']) ? ' — '.$rv['email'] : '');
                              if ($isDeclinedBefore) $label .= ' (menolak)';
                            ?>
                              <option
                                value="<?= $rid ?>"
                                <?= $isDeclinedBefore ? 'disabled' : '' ?>
                                data-declined="<?= $isDeclinedBefore ? '1' : '0' ?>">
                                <?= esc($label) ?>
                              </option>
                            <?php endforeach; ?>
                          <?php else: ?>
                            <option value="">Semua reviewer sudah ditugaskan</option>
                          <?php endif; ?>
                        </select>
                        <div class="form-text">Maksimal <?= $maxReviewer ?> orang.</div>
                      </div>
                      <div class="col-12 d-grid">
                        <button class="btn btn-primary btn-sm" id="btnAssign" <?= $absNotReviewed ? 'disabled' : '' ?>>
                          <i class="bi bi-person-plus me-1"></i>Tugaskan
                        </button>
                      </div>
                    </form>
                  <?php else: ?>
                    <div class="alert alert-success-subtle border-0 mb-0">Kuota reviewer penuh.</div>
                  <?php endif; ?>
                </div>

                <!-- Reviewer Abstrak -->
                <div class="mt-3 p-2 rounded border bg-light-subtle">
                  <div class="fw-semibold mb-2"><i class="bi bi-people-fill me-1"></i>Reviewer Abstrak</div>
                  <?php if (empty($absReviewers)): ?>
                    <div class="text-muted small">Tidak ada data reviewer abstrak.</div>
                  <?php else: ?>
                    <div class="table-responsive">
                      <table class="table table-sm align-middle mb-0">
                        <thead class="table-light"><tr><th>#</th><th>Nama</th><th>Email</th><th>Status</th></tr></thead>
                        <tbody>
                          <?php $i=1; foreach ($absReviewers as $rv):
                            $k = strtolower($rv['status'] ?? '');
                            $m = $rvStatMap[$k] ?? ['—','secondary'];
                          ?>
                            <tr>
                              <td><?= $i++ ?></td>
                              <td><?= esc($rv['name'] ?? '-') ?></td>
                              <td><?= esc($rv['email'] ?? '-') ?></td>
                              <td><span class="badge bg-<?= $m[1] ?>"><?= esc($m[0]) ?></span></td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                    <div class="small text-muted mt-2">Info tahap abstrak; boleh menugaskan reviewer yang sama untuk full paper.</div>
                  <?php endif; ?>
                </div>

              </div>
            </div>

            <!-- ============== LIST PENOLAKAN ============== -->
            <div class="card shadow-soft card-glass-plain">
              <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                  <span class="badge bg-blue-soft"><i class="bi bi-slash-circle"></i></span>
                  <h6 class="mb-0 fw-semibold text-blue-900">List Penolakan</h6>
                </div>
                <span class="badge bg-light text-muted"><?= (int)$declinedCount ?> Penolakan</span>
              </div>
              <div class="card-body">
                <?php if ($declinedCount === 0): ?>
                  <div class="empty-hint"><i class="bi bi-info-circle me-1"></i> Belum ada reviewer yang menolak penugasan.</div>
                <?php else: ?>
                  <div class="table-responsive">
                    <table class="table table-sm align-middle">
                      <thead class="table-light">
                        <tr>
                          <th>#</th>
                          <th>Reviewer</th>
                          <th>Email</th>
                          <th>Alasan Penolakan</th>
                          <th>Waktu Penolakan</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php $n=1; foreach ($declined as $d): ?>
                          <tr>
                            <td><?= $n++ ?></td>
                            <td class="fw-semibold text-blue-900"><?= esc($d['name'] ?? '-') ?></td>
                            <td><?= esc($d['email'] ?? '—') ?></td>
                            <td><?= esc($d['decline_reason'] ?? '—') ?></td>
                            <td><?= !empty($d['declined_at']) ? esc($fmtDT($d['declined_at'])) : '—' ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                  <div class="small text-muted mt-2">
                    Catatan: Admin dapat langsung menambahkan reviewer pengganti di panel <b>“Tambah Reviewer”</b> di atas.
                  </div>
                <?php endif; ?>
              </div>
            </div>
            <!-- ============ END LIST PENOLAKAN ============ -->

          </div>
        </div> <!-- /KANAN -->
      </div>

    </div>
  </main>
</div>

<!-- Modal Keputusan -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="bi bi-flag me-2"></i>Keputusan Full Paper</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="<?= site_url('admin/fullpaper/set-status/'.$submissionId) ?>" method="post" id="statusForm">
        <?= csrf_field() ?>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Status</label>
            <select class="form-select" name="status" id="statusSelect" required>
              <?php foreach (['UPLOADED','REVISION','ACCEPTED','REJECTED'] as $opt): ?>
                <option value="<?= $opt ?>" <?= $status===$opt?'selected':'' ?>><?= $statusText[$opt] ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-0">
            <label class="form-label">Komentar (opsional)</label>
            <textarea class="form-control" name="komentar" id="komentarField" rows="3" placeholder="Catatan untuk penulis atau internal…"></textarea>
            <div class="form-text" id="komentarHint" style="display:none;">Komentar minimal 10 karakter untuk Revisi/Ditolak.</div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Batal</button>
          <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i>Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?= $this->include('partials/footer') ?>

<!-- SweetAlert2 (CDN). Hapus jika sudah dimuat di layout -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
:root{
  --blue-50:#eff6ff; --blue-100:#dbeafe; --blue-200:#bfdbfe;
  --blue-300:#93c5fd; --blue-400:#60a5fa; --blue-500:#3b82f6;
  --blue-600:#2563eb; --blue-700:#1d4ed8; --blue-800:#1e40af; --blue-900:#1e3a8a;
  --muted:#6b7280;
  --side-pad: clamp(1rem, 2.3vw, 2.2rem);
}
.container-xxl{ max-width:min(100%, 1560px); padding-inline: var(--side-pad)!important; margin-inline:auto; }
body{ font-size:15.5px; line-height:1.6; }
.page-wrap-blue{
  min-height:100vh; padding-top:72px; background:
  radial-gradient(900px 300px at 20% -10%, rgba(59,130,246,.17), rgba(59,130,246,0) 60%),
  radial-gradient(900px 300px at 80% 110%, rgba(59,130,246,.14), rgba(59,130,246,0) 70%),
  linear-gradient(180deg, var(--blue-50), #fff 40%);
}
.card-hero{ border:0; border-radius:16px; overflow:hidden; box-shadow:0 12px 28px rgba(30,64,175,.10); }
.card-hero .hero-body{
  background:radial-gradient(1100px 360px at 10% -10%,var(--blue-600) 0,var(--blue-700) 45%,var(--blue-800) 100%);
  color:#fff; padding:1.8rem 1.2rem; min-height:176px;
}
.hero-title{ font-weight:800; letter-spacing:.25px; font-size:1.5rem; }
.text-white-70{ color:rgba(255,255,255,.85)!important; }

.card-glass-plain{ backdrop-filter:blur(6px); background:rgba(255,255,255,.96); border-radius:14px; border:1px solid rgba(30,64,175,.10); }
.shadow-soft{ box-shadow:0 10px 24px rgba(30,64,175,.08); }
.meta-list{ list-style:none; padding-left:0; margin:0; }
.meta-list li{ display:flex; align-items:center; justify-content:space-between; gap:.75rem; padding:.45rem 0; }
.meta-list li span{ color:var(--muted); }
.text-blue-900{ color:var(--blue-900)!important; }
.bg-blue-soft{ background:var(--blue-200); color:var(--blue-800); border-radius:12px; padding:.5rem .7rem; font-weight:600; font-size:.9rem; }
.bg-secondary-subtle{ background:#f1f5f9!important; color:#475569!important; }
.bg-blue-soft-2{ background:linear-gradient(180deg,#eef4ff,#eaf2ff); }
.btn{ font-weight:700; border-radius:10px; font-size:.98rem; padding:.62rem 1.05rem; }
.btn-sm{ padding:.5rem .9rem; font-size:.92rem; }
.btn-xs{ padding:.25rem .5rem; font-size:.8rem; border-radius:.5rem; }
.btn-primary{ background:var(--blue-600); border-color:var(--blue-600); box-shadow:0 4px 12px rgba(37,99,235,.2); }

/* Sidebar sticky */
.sticky-aside{ position: sticky; top: 84px; }

/* PDF viewer */
.chip-row{ display:flex; flex-wrap:wrap; gap:.5rem; }
.chip{ display:inline-flex; align-items:center; padding:.28rem .6rem; font-size:.88rem; border-radius:999px; background:#eef3ff; color:#1e3a8a; border:1px solid rgba(30,64,175,.15); font-weight:600; }
.pdf-wrap{ border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; background:#f8fafc; transition:height .2s ease; }
.pdf-wrap.is-min{ height:52vh; }
.pdf-wrap.is-max{ height:82vh; }
.pdf-frame{ width:100%; height:100%; border:0; }

.empty-hint{ color:#567; background:#f6f9ff; border:1px dashed rgba(30,64,175,.18); border-radius:12px; padding:.8rem 1rem; font-weight:600; }

@media (max-width:767.98px){
  .card-hero .hero-body{ padding:1.4rem 1rem; min-height:165px; }
  .pdf-wrap.is-min{ height:46vh; }
}
@media (max-width:575.98px){
  .container-xxl{ padding-inline: calc(var(--side-pad) - .25rem)!important; }
  .hero-title{ font-size:1.35rem; }
  .pdf-wrap.is-min{ height:42vh; }
  .pdf-wrap.is-max{ height:75vh; }
}
</style>

<script>
(function(){
  // Toggle PDF
  const wrap = document.getElementById('pdfWrap');
  const btn  = document.getElementById('btnToggleSize');
  if(wrap && btn){
    function setState(state){
      if(state==='max'){
        wrap.classList.remove('is-min'); wrap.classList.add('is-max');
        btn.dataset.state = 'min';
        btn.innerHTML = '<i class="bi bi-arrows-angle-contract"></i>';
        btn.title = 'Minimize';
      }else{
        wrap.classList.remove('is-max'); wrap.classList.add('is-min');
        btn.dataset.state = 'max';
        btn.innerHTML = '<i class="bi bi-arrows-fullscreen"></i>';
        btn.title = 'Maximize';
      }
    }
    wrap.classList.add('is-min');
    btn.addEventListener('click', ()=> setState(btn.dataset.state || 'max'));
  }

  // ==== Validasi assign: blokir yang pernah menolak ====
  const declinedIds = <?= json_encode($declinedIds) ?>;
  const form   = document.getElementById('assignForm');
  const select = document.getElementById('reviewerSelect');

  function showDeclinedAlert(nameText){
    if (window.Swal) {
      Swal.fire({
        icon: 'warning',
        title: 'Tidak bisa ditugaskan',
        text: (nameText ? nameText + ' ' : '') + 'sudah menolak penugasan sebelumnya untuk full paper ini.',
        footer: 'Silakan pilih reviewer lain.',
        confirmButtonText: 'Mengerti',
      });
    } else {
      alert((nameText? nameText+' ': '') + 'sudah menolak penugasan sebelumnya. Pilih reviewer lain.');
    }
  }

  form?.addEventListener('submit', function(e){
    const val = parseInt(select?.value || '0', 10);
    if (declinedIds.includes(val)) {
      e.preventDefault();
      const nameText = select?.selectedOptions?.[0]?.textContent?.trim() || '';
      showDeclinedAlert(nameText);
    }
  });

  select?.addEventListener('change', function(){
    const opt = this.selectedOptions?.[0];
    if (!opt) return;
    const isDeclined = opt.getAttribute('data-declined') === '1';
    if (isDeclined) {
      this.value = '';
      showDeclinedAlert(opt.textContent?.trim() || '');
    }
  });

  // ==== SweetAlert konfirmasi Unassign ====
  document.querySelectorAll('.js-unassign').forEach(function(el){
    el.addEventListener('click', function(e){
      e.preventDefault();
      const url  = this.getAttribute('data-url');
      const name = this.getAttribute('data-name') || 'reviewer';

      Swal.fire({
        title: 'Cabut Penugasan?',
        html: `<div class="text-start">Penugasan untuk <b>${name}</b> akan dicabut dari full paper ini.</div>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Cabut',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        customClass: {
          confirmButton: 'btn btn-danger',
          cancelButton: 'btn btn-secondary'
        },
        buttonsStyling: false
      }).then((res)=>{
        if(res.isConfirmed){
          window.location.href = url;
        }
      });
    });
  });

  // ==== Validasi “Keputusan” (komentar wajib untuk REVISION/REJECTED) ====
  const statusForm   = document.getElementById('statusForm');
  const statusSelect = document.getElementById('statusSelect');
  const komentar     = document.getElementById('komentarField');
  const hint         = document.getElementById('komentarHint');

  function toggleKomentarHint(){
    const v = statusSelect?.value || '';
    const need = (v === 'REVISION' || v === 'REJECTED');
    hint.style.display = need ? 'block':'none';
  }
  toggleKomentarHint();
  statusSelect?.addEventListener('change', toggleKomentarHint);

  statusForm?.addEventListener('submit', function(e){
    const v = statusSelect?.value || '';
    const need = (v === 'REVISION' || v === 'REJECTED');
    const note = (komentar?.value || '').trim();
    if (need && note.length < 10) {
      e.preventDefault();
      Swal.fire({
        icon: 'warning',
        title: 'Komentar belum memadai',
        text: 'Untuk status Revisi/Ditolak, komentar minimal 10 karakter.',
        confirmButtonText: 'Mengerti'
      });
    }
  });

})();
</script>
