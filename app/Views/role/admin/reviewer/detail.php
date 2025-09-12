<?php
// ===== DEFAULT VARS =====
$title       = $title ?? 'Detail Reviewer';
$reviewer    = $reviewer ?? [];        // id_user, nama_lengkap, email, status
$performance = $performance ?? [       // total_reviews, pending_reviews, completed_reviews, completion_rate, acceptance_rate, accepted_reviews, revision_reviews, rejected_reviews, avg_review_time
  'total_reviews'     => 0,
  'pending_reviews'   => 0,
  'completed_reviews' => 0,
  'completion_rate'   => 0,
  'acceptance_rate'   => 0,
  'accepted_reviews'  => 0,
  'revision_reviews'  => 0,
  'rejected_reviews'  => 0,
  'avg_review_time'   => 0,
];
$categories           = $categories ?? [];            // assigned categories: [{id, id_kategori, nama_kategori}]
$available_categories = $available_categories ?? [];  // not yet assigned: [{id_kategori, nama_kategori}]
$reviews              = $reviews ?? [];               // history rows

if (!function_exists('review_status_class')) {
  function review_status_class($status) {
    $s = strtolower((string)$status);
    return $s === 'diterima' ? 'bg-success text-white'
         : ($s === 'ditolak' ? 'bg-danger text-white'
         : ($s === 'revisi'  ? 'bg-warning text-dark'
         : ($s === 'pending' ? 'bg-secondary text-white'
         : 'bg-light text-dark')));
  }
}

$rid    = (int)($reviewer['id_user'] ?? 0);
$rname  = (string)($reviewer['nama_lengkap'] ?? 'Reviewer');
$remail = (string)($reviewer['email'] ?? '');
$rstat  = strtolower((string)($reviewer['status'] ?? 'nonaktif'));
$initial= strtoupper(substr($rname, 0, 1));
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_admin') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- HEADER: BIRU -->
      <div class="header-section header-blue d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center gap-2">
          <a href="<?= site_url('admin/reviewer') ?>" class="btn btn-light btn-sm text-primary">
            <i class="bi bi-arrow-left"></i>
          </a>
          <div>
            <h3 class="welcome-text mb-1">
              <i class="bi bi-person-badge me-2"></i>Detail Reviewer
            </h3>
            <div class="text-muted">Informasi lengkap & performa reviewer</div>
          </div>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-light btn-sm" onclick="toggleStatus(<?= $rid ?>,'<?= $rstat ?>')">
            <i class="bi bi-power me-1"></i><?= $rstat==='aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>
          </button>
          <button class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#assignCategoryModal">
            <i class="bi bi-tags me-1"></i>Assign Kategori
          </button>
        </div>
      </div>

      <div class="row g-4">
        <!-- LEFT: PROFILE + CATEGORIES -->
        <div class="col-lg-4">
          <!-- Profile card (gradient) -->
          <div class="profile-card p-4 text-center shadow-sm">
            <div class="profile-avatar mb-3"><?= $initial ?></div>
            <h4 class="mb-1"><?= esc($rname) ?></h4>
            <div class="text-white-50 mb-3"><?= esc($remail) ?></div>
            <div class="mb-3">
              <span class="badge <?= $rstat==='aktif'?'bg-success':'bg-secondary' ?> fs-6"><?= ucfirst($rstat) ?></span>
            </div>

            <div class="row text-center g-3">
              <div class="col-4">
                <div class="h4 mb-0"><?= (int)$performance['total_reviews'] ?></div>
                <small class="opacity-75">Total</small>
              </div>
              <div class="col-4">
                <div class="h4 mb-0"><?= (int)$performance['completed_reviews'] ?></div>
                <small class="opacity-75">Selesai</small>
              </div>
              <div class="col-4">
                <div class="h4 mb-0"><?= (int)$performance['avg_review_time'] ?></div>
                <small class="opacity-75">Hari rata²</small>
              </div>
            </div>
          </div>

          <!-- Assigned categories -->
          <div class="card shadow-sm mt-4">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0"><i class="bi bi-tags me-2 text-primary"></i>Kategori Review</h6>
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#assignCategoryModal">
                  <i class="bi bi-plus-lg"></i>
                </button>
              </div>

              <?php if (!empty($categories)): ?>
                <div class="vstack gap-2">
                  <?php foreach ($categories as $c): ?>
                    <div class="d-flex justify-content-between align-items-center border rounded-3 px-2 py-1">
                      <span class="badge bg-primary"><?= esc($c['nama_kategori'] ?? '-') ?></span>
                      <button class="btn btn-sm btn-outline-danger"
                              title="Hapus kategori"
                              onclick="removeCategory(<?= (int)($c['id'] ?? 0) ?>)">
                        <i class="bi bi-x-lg"></i>
                      </button>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <div class="text-center text-muted py-4">
                  <i class="bi bi-tags fs-3 mb-2"></i>
                  <div>Belum ada kategori ditugaskan.</div>
                  <button class="btn btn-primary btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#assignCategoryModal">
                    Assign Kategori
                  </button>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- RIGHT: STATS + DISTRIBUTION -->
        <div class="col-lg-8">
          <!-- Top stat cards -->
          <div class="row g-3 mb-3">
            <div class="col-md-3">
              <div class="stat-card shadow-sm h-100">
                <div class="d-flex align-items-center">
                  <div class="stat-icon bg-primary"><i class="bi bi-file-earmark-text"></i></div>
                  <div class="ms-3">
                    <div class="stat-number"><?= (int)$performance['total_reviews'] ?></div>
                    <div class="text-muted">Total Review</div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="stat-card shadow-sm h-100">
                <div class="d-flex align-items-center">
                  <div class="stat-icon bg-warning"><i class="bi bi-clock-history"></i></div>
                  <div class="ms-3">
                    <div class="stat-number"><?= (int)$performance['pending_reviews'] ?></div>
                    <div class="text-muted">Pending</div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="stat-card shadow-sm h-100">
                <div class="d-flex align-items-center">
                  <div class="stat-icon bg-success"><i class="bi bi-check2-circle"></i></div>
                  <div class="ms-3">
                    <div class="stat-number"><?= (int)$performance['completion_rate'] ?>%</div>
                    <div class="text-muted">Completion</div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="stat-card shadow-sm h-100">
                <div class="d-flex align-items-center">
                  <div class="stat-icon bg-info"><i class="bi bi-hand-thumbs-up"></i></div>
                  <div class="ms-3">
                    <div class="stat-number"><?= (int)$performance['acceptance_rate'] ?>%</div>
                    <div class="text-muted">Acceptance</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Distribution card -->
          <?php
            $comp = max(1, (int)$performance['completed_reviews']);
            $accP = round(((int)$performance['accepted_reviews'] / $comp) * 100);
            $revP = round(((int)$performance['revision_reviews'] / $comp) * 100);
            $rejP = round(((int)$performance['rejected_reviews'] / $comp) * 100);
          ?>
          <div class="card shadow-sm">
            <div class="card-body">
              <h6 class="mb-3"><i class="bi bi-bar-chart-line me-2 text-primary"></i>Distribusi Keputusan Review</h6>
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="small text-muted fw-semibold">DITERIMA</label>
                  <div class="performance-meter"><div class="performance-fill bg-success" data-percent="<?= $accP ?>"></div></div>
                  <div class="d-flex justify-content-between">
                    <span class="fw-bold text-success"><?= (int)$performance['accepted_reviews'] ?></span>
                    <span class="text-muted"><?= $accP ?>%</span>
                  </div>
                </div>
                <div class="col-md-4">
                  <label class="small text-muted fw-semibold">REVISI</label>
                  <div class="performance-meter"><div class="performance-fill bg-warning" data-percent="<?= $revP ?>"></div></div>
                  <div class="d-flex justify-content-between">
                    <span class="fw-bold text-warning"><?= (int)$performance['revision_reviews'] ?></span>
                    <span class="text-muted"><?= $revP ?>%</span>
                  </div>
                </div>
                <div class="col-md-4">
                  <label class="small text-muted fw-semibold">DITOLAK</label>
                  <div class="performance-meter"><div class="performance-fill bg-danger" data-percent="<?= $rejP ?>"></div></div>
                  <div class="d-flex justify-content-between">
                    <span class="fw-bold text-danger"><?= (int)$performance['rejected_reviews'] ?></span>
                    <span class="text-muted"><?= $rejP ?>%</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- REVIEW HISTORY -->
      <div class="card shadow-sm mt-3">
        <div class="card-body border-bottom">
          <h6 class="mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>Riwayat Review</h6>
        </div>
        <div class="card-body">
          <?php if (!empty($reviews)): ?>
            <div class="activity-timeline">
              <?php foreach ($reviews as $rv): ?>
                <div class="activity-item">
                  <div class="row">
                    <div class="col-md-8">
                      <h6 class="mb-2 fw-semibold"><?= esc($rv['judul'] ?? '-') ?></h6>
                      <div class="mb-2">
                        <?php if (!empty($rv['nama_kategori'])): ?>
                          <span class="badge bg-info me-2"><?= esc($rv['nama_kategori']) ?></span>
                        <?php endif; ?>
                        <span class="status-badge <?= review_status_class($rv['keputusan'] ?? 'pending') ?>">
                          <?= ucfirst(strtolower($rv['keputusan'] ?? 'pending')) ?>
                        </span>
                      </div>
                      <?php if (!empty($rv['author_name'])): ?>
                        <div class="text-muted mb-2"><strong>Author:</strong> <?= esc($rv['author_name']) ?></div>
                      <?php endif; ?>
                      <?php if (!empty($rv['komentar'])): ?>
                        <div class="mt-2"><strong>Komentar:</strong>
                          <p class="text-muted mb-0"><?= esc($rv['komentar']) ?></p>
                        </div>
                      <?php endif; ?>
                    </div>
                    <div class="col-md-4 text-md-end">
                      <?php if (!empty($rv['tanggal_review'])): ?>
                        <div class="text-muted"><i class="bi bi-calendar-event me-1"></i><?= date('d M Y', strtotime($rv['tanggal_review'])) ?></div>
                        <div class="text-muted mt-1"><i class="bi bi-clock me-1"></i><?= date('H:i', strtotime($rv['tanggal_review'])) ?></div>
                      <?php endif; ?>
                      <?php if (!empty($rv['tanggal_upload']) && !empty($rv['tanggal_review'])):
                        $days = ceil( (strtotime($rv['tanggal_review']) - strtotime($rv['tanggal_upload'])) / 86400 );
                        if ($days < 0) $days = 0;
                      ?>
                        <div class="text-muted mt-2"><small>Review time: <?= $days ?> hari</small></div>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="text-center py-4 text-muted">
              <i class="bi bi-file-earmark-text fs-3 d-block mb-2"></i>
              Belum ada riwayat review.
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </main>
</div>

<!-- MODAL: Assign Category -->
<div class="modal fade" id="assignCategoryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header bg-primary text-white">
      <h5 class="modal-title"><i class="bi bi-tags me-2"></i>Assign Kategori</h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <form action="<?= site_url('admin/reviewer/assignCategory') ?>" method="POST">
      <?= csrf_field() ?>
      <div class="modal-body">
        <input type="hidden" name="reviewer_id" value="<?= $rid ?>">
        <div class="mb-3">
          <label class="form-label">Reviewer</label>
          <input type="text" class="form-control" value="<?= esc($rname) ?>" readonly>
        </div>
        <div class="mb-3">
          <label class="form-label">Pilih Kategori</label>
          <select class="form-select" name="category_id" required>
            <option value="">Pilih Kategori</option>
            <?php if (!empty($available_categories)): ?>
              <?php foreach ($available_categories as $ac): ?>
                <option value="<?= (int)$ac['id_kategori'] ?>"><?= esc($ac['nama_kategori']) ?></option>
              <?php endforeach; ?>
            <?php else: ?>
              <option disabled value="">Tidak ada kategori tersedia</option>
            <?php endif; ?>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i>Assign</button>
      </div>
    </form>
  </div></div>
</div>

<?= $this->include('partials/footer') ?>

<style>
  :root{
    --primary-color:#2563eb; --info-color:#06b6d4; --success-color:#10b981;
    --warning-color:#f59e0b; --danger-color:#ef4444;
  }
  body{ background:linear-gradient(135deg,#f8fafc 0%,#e2e8f0 100%); }

  /* Header biru seragam */
  .header-section.header-blue{
    background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%);
    color:#fff; padding:28px 24px; border-radius:16px; box-shadow:0 8px 28px rgba(0,0,0,.12);
  }
  .header-section.header-blue .welcome-text{ color:#fff; font-weight:800; font-size:2rem; }
  .header-section.header-blue .text-muted,
  .header-section.header-blue strong{ color:rgba(255,255,255,.95)!important; }

  /* Shared stat card */
  .stat-card{
    background:#fff; border-radius:14px; padding:20px; box-shadow:0 8px 28px rgba(0,0,0,.08);
    border-left:4px solid #e9ecef; position:relative; overflow:hidden;
  }
  .stat-card:before{
    content:''; position:absolute; left:0; top:0; height:4px; width:100%;
    background:linear-gradient(90deg,var(--primary-color),var(--info-color));
  }
  .stat-icon{ width:52px; height:52px; border-radius:12px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:20px; }
  .stat-number{ font-size:1.6rem; font-weight:800; color:#1e293b; line-height:1; }

  /* Profile gradient card */
  .profile-card{
    background: linear-gradient(135deg, var(--primary-color), var(--info-color));
    color:#fff; border-radius:16px;
  }
  .profile-avatar{
    width:110px; height:110px; border-radius:50%; display:flex; align-items:center; justify-content:center;
    background: rgba(255,255,255,.2); font-size:2.5rem; font-weight:800; margin:0 auto;
  }

  .performance-meter{ height:10px; background:#e2e8f0; border-radius:6px; overflow:hidden; }
  .performance-fill{ height:100%; width:0; border-radius:6px; transition:width .7s ease; }

  /* Timeline */
  .activity-timeline{ position:relative; padding-left:30px; }
  .activity-timeline:before{ content:''; position:absolute; left:10px; top:0; bottom:0; width:2px; background:linear-gradient(var(--primary-color),var(--info-color)); }
  .activity-item{ position:relative; margin-bottom:16px; padding:14px; background:#f8fafc; border-radius:10px; border-left:3px solid var(--primary-color); }
  .activity-item:before{ content:''; position:absolute; left:-22px; top:16px; width:12px; height:12px; background:var(--primary-color); border-radius:50%; border:3px solid #fff; }

  /* Avatar (initial) used on cards if needed */
  .user-avatar{
    width:48px; height:48px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; color:#fff;
    background:linear-gradient(135deg,var(--primary-color),var(--info-color));
  }

  #content main>.container-fluid{ margin-top:.25rem; }
</style>

<script>
  // Toggle status
  function toggleStatus(id, current){
    const action = current === 'aktif' ? 'Nonaktifkan' : 'Aktifkan';
    if (!window.Swal){
      if(confirm(action+' reviewer ini?')) location.href = '<?= site_url('admin/reviewer/toggleStatus') ?>/'+id;
      return;
    }
    Swal.fire({
      title: action+' Reviewer?',
      text: 'Apakah Anda yakin?',
      icon: 'question',
      showCancelButton:true,
      confirmButtonColor: current==='aktif' ? '#f59e0b' : '#10b981',
      cancelButtonColor:'#6b7280',
      confirmButtonText:'Ya, '+action, cancelButtonText:'Batal'
    }).then(r=>{ if(r.isConfirmed){ location.href = '<?= site_url('admin/reviewer/toggleStatus') ?>/'+id; } });
  }

  // Remove assigned category
  function removeCategory(id){
    if (!id){ Swal?.fire('Error','ID kategori tidak valid','error'); return; }
    if (!window.Swal){
      if(confirm('Hapus assignment kategori ini?')) location.href = '<?= site_url('admin/reviewer/removeCategory') ?>/'+id;
      return;
    }
    Swal.fire({
      title:'Hapus Assignment Kategori?',
      text:'Tindakan ini tidak dapat dibatalkan.',
      icon:'warning', showCancelButton:true,
      confirmButtonColor:'#ef4444', cancelButtonColor:'#6b7280',
      confirmButtonText:'Ya, Hapus', cancelButtonText:'Batal'
    }).then(r=>{ if(r.isConfirmed){ location.href = '<?= site_url('admin/reviewer/removeCategory') ?>/'+id; } });
  }

  // Animate distribution bars
  document.addEventListener('DOMContentLoaded', ()=>{
    setTimeout(()=>{
      document.querySelectorAll('.performance-fill').forEach(el=>{
        const p = parseInt(el.getAttribute('data-percent')||'0',10);
        el.style.width = Math.max(0, Math.min(100, p)) + '%';
      });
    }, 250);

    // Flash messages
    <?php if (session('success')): ?>
      window.Swal?.fire({icon:'success',title:'Berhasil!',text:'<?= esc(session('success')) ?>',timer:2800,showConfirmButton:false});
    <?php endif; ?>
    <?php if (session('error')): ?>
      window.Swal?.fire({icon:'error',title:'Error!',text:'<?= esc(session('error')) ?>'});
    <?php endif; ?>
    <?php if (session('errors')): ?>
      window.Swal?.fire({icon:'error',title:'Validation Error',html:'<?= esc(implode("<br>", (array)session("errors"))) ?>'});
    <?php endif; ?>
  });
</script>
