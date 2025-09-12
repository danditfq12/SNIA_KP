<?php
// ====== DEFAULT VARS ======
$abstrak  = $abstrak ?? [];     // id_abstrak, judul, nama_lengkap, email, nama_kategori, id_kategori, event_id, event_title, tanggal_upload, revisi_ke, status, file_abstrak
$reviews  = $reviews ?? [];     // reviewer_name, tanggal_review, keputusan, komentar
$title    = 'Detail Abstrak';

// mapping status → badge
$badgeMap = [
  'menunggu'        => 'warning',
  'sedang_direview' => 'info',
  'diterima'        => 'success',
  'ditolak'         => 'danger',
  'revisi'          => 'secondary',
];
$stKey  = strtolower($abstrak['status'] ?? 'menunggu');
$stCls  = $badgeMap[$stKey] ?? 'secondary';
$stText = ucfirst(str_replace('_',' ', $stKey));
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_admin') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- HEADER (override: header-blue) -->
      <div class="header-section header-blue d-flex justify-content-between align-items-start mb-3">
        <div class="pe-3">
          <h3 class="welcome-text mb-1">
            <i class="bi bi-file-earmark-text me-2"></i>Detail Abstrak
          </h3>
          <div class="text-muted"><?= esc($abstrak['judul'] ?? '-') ?></div>
        </div>
        <div class="text-end">
          <div class="mb-2">
            <span class="badge bg-<?= $stCls ?>"><?= $stText ?></span>
          </div>
          <small class="text-muted d-block">Diunggah</small>
          <strong><?= !empty($abstrak['tanggal_upload']) ? date('d M Y, H:i', strtotime($abstrak['tanggal_upload'])) : '-' ?></strong>
        </div>
      </div>

      <div class="row g-3">
        <!-- LEFT -->
        <div class="col-lg-8">
          <!-- Info Abstrak -->
          <div class="card shadow-sm mb-3">
            <div class="card-body">
              <h5 class="card-title mb-3">
                <i class="bi bi-info-circle text-primary me-2"></i>Informasi Abstrak
              </h5>

              <div class="row mb-3">
                <div class="col-md-4 text-muted">Judul</div>
                <div class="col-md-8 fw-semibold"><?= esc($abstrak['judul'] ?? '-') ?></div>
              </div>

              <div class="row mb-3">
                <div class="col-md-4 text-muted">Penulis</div>
                <div class="col-md-8">
                  <?= esc($abstrak['nama_lengkap'] ?? '-') ?><br>
                  <small class="text-muted"><?= esc($abstrak['email'] ?? '-') ?></small>
                </div>
              </div>

              <div class="row mb-3">
                <div class="col-md-4 text-muted">Kategori</div>
                <div class="col-md-8">
                  <span class="badge bg-info"><?= esc($abstrak['nama_kategori'] ?? '-') ?></span>
                </div>
              </div>

              <div class="row mb-3">
                <div class="col-md-4 text-muted">Event</div>
                <div class="col-md-8">
                  <?php if (!empty($abstrak['event_title'])): ?>
                    <span class="badge bg-secondary"><?= esc($abstrak['event_title']) ?></span>
                  <?php else: ?>
                    <small class="text-muted">-</small>
                  <?php endif; ?>
                </div>
              </div>

              <div class="row mb-3">
                <div class="col-md-4 text-muted">Revisi</div>
                <div class="col-md-8">
                  <span class="badge bg-secondary">Ke-<?= (int)($abstrak['revisi_ke'] ?? 0) ?></span>
                </div>
              </div>

              <div class="row">
                <div class="col-md-4 text-muted">Status</div>
                <div class="col-md-8">
                  <span class="badge bg-<?= $stCls ?>"><?= $stText ?></span>
                </div>
              </div>
            </div>
          </div>

          <!-- File Abstrak -->
          <div class="card shadow-sm mb-3">
            <div class="card-body">
              <h5 class="card-title mb-3">
                <i class="bi bi-file-pdf text-danger me-2"></i>File Abstrak
              </h5>

              <div class="p-4 border rounded-3 bg-light-subtle text-center">
                <div class="mb-2"><i class="bi bi-file-earmark-pdf fs-1 text-danger"></i></div>
                <div class="fw-semibold mb-2"><?= esc($abstrak['file_abstrak'] ?? 'abstrak.pdf') ?></div>
                <div class="text-muted mb-3">Klik tombol di bawah untuk mengunduh atau melihat di tab baru.</div>
                <div class="d-flex gap-2 justify-content-center">
                  <a class="btn btn-primary"
                     href="<?= site_url('admin/abstrak/download/'.(int)($abstrak['id_abstrak'] ?? 0)) ?>">
                    <i class="bi bi-download me-1"></i>Download
                  </a>
                  <a class="btn btn-outline-info"
                     target="_blank"
                     href="<?= site_url('admin/abstrak/download/'.(int)($abstrak['id_abstrak'] ?? 0)) ?>">
                    <i class="bi bi-eye me-1"></i>Preview
                  </a>
                </div>
              </div>
            </div>
          </div>

          <!-- Riwayat Review -->
          <div class="card shadow-sm">
            <div class="card-body">
              <h5 class="card-title mb-3">
                <i class="bi bi-clock-history text-info me-2"></i>Riwayat Review
              </h5>

              <?php if (empty($reviews)): ?>
                <div class="p-4 text-center border rounded-3 bg-light-subtle">
                  <div class="mb-2"><i class="bi bi-inbox fs-3 text-secondary"></i></div>
                  <div class="text-muted">Belum ada review untuk abstrak ini</div>
                </div>
              <?php else: ?>
                <div class="vstack gap-2">
                  <?php foreach ($reviews as $r):
                    $dMap = ['pending'=>'warning','diterima'=>'success','ditolak'=>'danger','revisi'=>'info'];
                    $dCls = $dMap[strtolower($r['keputusan'] ?? 'pending')] ?? 'secondary';
                  ?>
                    <div class="p-3 border rounded-3 bg-white">
                      <div class="d-flex justify-content-between align-items-start">
                        <div>
                          <div class="fw-semibold"><?= esc($r['reviewer_name'] ?? '-') ?></div>
                          <div class="small text-muted"><?= !empty($r['tanggal_review']) ? date('d M Y, H:i', strtotime($r['tanggal_review'])) : '-' ?></div>
                        </div>
                        <span class="badge bg-<?= $dCls ?>"><?= ucfirst($r['keputusan'] ?? 'pending') ?></span>
                      </div>
                      <?php if (!empty($r['komentar'])): ?>
                        <div class="mt-2 text-secondary"><?= nl2br(esc($r['komentar'])) ?></div>
                      <?php endif; ?>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- RIGHT -->
        <div class="col-lg-4">
          <!-- Aksi Cepat -->
          <div class="card shadow-sm mb-3">
            <div class="card-body">
              <h5 class="card-title mb-3">
                <i class="bi bi-tools text-warning me-2"></i>Aksi Cepat
              </h5>
              <div class="d-grid gap-2">
                <button class="btn btn-primary" onclick="openStatusModal(<?= (int)($abstrak['id_abstrak'] ?? 0) ?>)">
                  <i class="bi bi-pencil-square me-1"></i>Update Status
                </button>

                <?php if ($stKey === 'menunggu'): ?>
                  <button class="btn btn-success"
                          onclick="openAssign(<?= (int)($abstrak['id_abstrak'] ?? 0) ?>,'<?= esc(addslashes($abstrak['judul'] ?? '-')) ?>',<?= (int)($abstrak['id_kategori'] ?? 0) ?>)">
                    <i class="bi bi-person-plus me-1"></i>Assign Reviewer
                  </button>
                <?php endif; ?>

                <a class="btn btn-outline-info"
                   href="<?= site_url('admin/abstrak/download/'.(int)($abstrak['id_abstrak'] ?? 0)) ?>">
                  <i class="bi bi-download me-1"></i>Download File
                </a>

                <hr class="my-2">
                <button class="btn btn-danger" onclick="deleteAbstrak(<?= (int)($abstrak['id_abstrak'] ?? 0) ?>)">
                  <i class="bi bi-trash me-1"></i>Hapus Abstrak
                </button>
              </div>
            </div>
          </div>

          <!-- Statistik singkat -->
          <div class="card shadow-sm mb-3">
            <div class="card-body">
              <h5 class="card-title mb-3">
                <i class="bi bi-graph-up text-success me-2"></i>Statistik
              </h5>
              <div class="d-flex justify-content-between py-2 border-bottom">
                <span class="text-muted">Total Review</span>
                <span class="badge bg-primary"><?= count($reviews) ?></span>
              </div>
              <div class="d-flex justify-content-between py-2 border-bottom">
                <span class="text-muted">Review Pending</span>
                <?php $pending = array_filter($reviews, fn($x)=>strtolower($x['keputusan'] ?? '')==='pending'); ?>
                <span class="badge bg-warning text-dark"><?= count($pending) ?></span>
              </div>
              <div class="d-flex justify-content-between py-2">
                <span class="text-muted">Waktu Upload</span>
                <?php
                  $days = '-';
                  if (!empty($abstrak['tanggal_upload'])) {
                    $days = floor((time() - strtotime($abstrak['tanggal_upload'])) / 86400) . ' hari lalu';
                  }
                ?>
                <span class="badge bg-info"><?= $days ?></span>
              </div>
            </div>
          </div>

          <!-- Navigasi -->
          <div class="card shadow-sm">
            <div class="card-body">
              <h5 class="card-title mb-3">
                <i class="bi bi-compass text-info me-2"></i>Navigasi
              </h5>
              <div class="d-grid gap-2">
                <a class="btn btn-outline-secondary" href="<?= site_url('admin/abstrak') ?>">
                  <i class="bi bi-arrow-left me-1"></i>Kembali ke Daftar
                </a>
                <a class="btn btn-outline-primary" href="<?= site_url('admin/users') ?>">
                  <i class="bi bi-person me-1"></i>Lihat Profil Penulis
                </a>
                <?php if (!empty($abstrak['event_id'])): ?>
                  <a class="btn btn-outline-success" href="<?= site_url('admin/event/detail/'.(int)$abstrak['event_id']) ?>">
                    <i class="bi bi-calendar-event me-1"></i>Detail Event
                  </a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

<!-- Assign Reviewer Modal -->
<div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header bg-primary text-white">
      <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Assign Reviewer</h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <form id="assignForm" method="POST">
      <?= csrf_field() ?>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Judul Abstrak</label>
          <input type="text" id="assignTitle" class="form-control" readonly>
        </div>
        <div class="mb-3">
          <label class="form-label">Pilih Reviewer</label>
          <select class="form-select" id="reviewerSelect" name="id_reviewer" required>
            <option value="">-- Pilih Reviewer --</option>
          </select>
          <div class="form-text">Ditampilkan reviewer sesuai kategori.</div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i>Assign</button>
      </div>
    </form>
  </div></div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header bg-primary text-white">
      <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Update Status Abstrak</h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <form id="statusForm">
      <?= csrf_field() ?>
      <div class="modal-body">
        <input type="hidden" id="statusAbstrakId" name="id_abstrak" value="<?= (int)($abstrak['id_abstrak'] ?? 0) ?>">
        <div class="mb-3">
          <label class="form-label">Status</label>
          <select class="form-select" id="statusSelect" name="status" required>
            <option value="menunggu"        <?= $stKey==='menunggu'?'selected':'' ?>>Menunggu</option>
            <option value="sedang_direview" <?= $stKey==='sedang_direview'?'selected':'' ?>>Sedang Review</option>
            <option value="diterima"        <?= $stKey==='diterima'?'selected':'' ?>>Diterima</option>
            <option value="ditolak"         <?= $stKey==='ditolak'?'selected':'' ?>>Ditolak</option>
            <option value="revisi"          <?= $stKey==='revisi'?'selected':'' ?>>Perlu Revisi</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Komentar (opsional)</label>
          <textarea class="form-control" id="statusKomentar" name="komentar" rows="3"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i>Simpan</button>
      </div>
    </form>
  </div></div>
</div>

<?= $this->include('partials/footer') ?>

<style>
  :root{
    --primary-color:#2563eb; --success-color:#10b981; --warning-color:#f59e0b; --danger-color:#ef4444; --info-color:#06b6d4;
  }
  body{ background:linear-gradient(135deg,#f8fafc 0%,#e2e8f0 100%); font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; }

  /* Default header style (dashboard) */
  .header-section{ background:#fff; padding:20px; border-radius:14px; box-shadow:0 4px 20px rgba(0,0,0,.05); }
  .welcome-text{ color:var(--primary-color); font-weight:700; }

  /* Override khusus halaman: box biru teks putih */
  .header-section.header-blue{
    background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%);
    color:#fff;
    padding:28px 24px;
    border-radius:16px;
    box-shadow:0 8px 28px rgba(0,0,0,.12);
  }
  .header-section.header-blue .welcome-text{ color:#fff; font-weight:800; font-size:2rem; }
  .header-section.header-blue .text-muted{ color: rgba(255,255,255,.9) !important; }
  .header-section.header-blue strong{ color:#fff; }

  /* Cards/komponen seragam dengan dashboard */
  .stat-card{
    background:#fff; border-radius:14px; padding:20px; box-shadow:0 8px 28px rgba(0,0,0,.08);
    border-left:4px solid #e9ecef; position:relative; overflow:hidden;
  }
  .stat-card:before{
    content:''; position:absolute; left:0; top:0; height:4px; width:100%;
    background:linear-gradient(90deg,var(--primary-color),var(--info-color));
  }

  /* biar konten turun sedikit dari header global */
  #content main>.container-fluid{ margin-top:.25rem; }
</style>

<script>
  // ====== CSRF (CI4) ======
  const csrfName = '<?= csrf_token() ?>';
  let   csrfHash = '<?= csrf_hash() ?>';

  // Update Status (open modal)
  function openStatusModal(){
    new bootstrap.Modal(document.getElementById('statusModal')).show();
  }

  // Submit Update Status (AJAX)
  document.getElementById('statusForm')?.addEventListener('submit', function(e){
    e.preventDefault();
    const data = new URLSearchParams();
    data.append('id_abstrak', document.getElementById('statusAbstrakId').value);
    data.append('status',     document.getElementById('statusSelect').value);
    data.append('komentar',   document.getElementById('statusKomentar').value || '');
    data.append(csrfName, csrfHash);

    fetch('<?= site_url('admin/abstrak/update-status') ?>', {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},
      body:data.toString()
    }).then(r=>r.json()).then(res=>{
      if(res && res.success){
        Swal?.fire('Berhasil!', res.message || 'Status diperbarui', 'success').then(()=>location.reload());
      }else{
        Swal?.fire('Gagal!', (res && res.message) || 'Terjadi kesalahan', 'error');
      }
      if(res && res[csrfName]) csrfHash = res[csrfName];
    }).catch(()=> Swal?.fire('Error','Gagal menghubungi server','error'));
  });

  // Assign reviewer (open + load reviewers)
  function openAssign(idAbstrak, judul, idKategori){
    document.getElementById('assignTitle').value = judul;
    document.getElementById('assignForm').action = '<?= site_url('admin/abstrak/assign') ?>/'+idAbstrak;

    fetch('<?= site_url('admin/abstrak/reviewers-by-category') ?>/'+idKategori, {headers:{'X-Requested-With':'XMLHttpRequest'}})
      .then(r=>r.json())
      .then(list=>{
        const sel = document.getElementById('reviewerSelect');
        sel.innerHTML = '<option value="">-- Pilih Reviewer --</option>';
        list.forEach(rv=>{
          const opt = document.createElement('option');
          opt.value = rv.id_user; opt.textContent = rv.nama_lengkap;
          sel.appendChild(opt);
        });
        new bootstrap.Modal(document.getElementById('assignModal')).show();
      })
      .catch(()=> Swal?.fire('Error','Gagal memuat reviewer','error'));
  }

  // Hapus (POST + CSRF)
  function deleteAbstrak(id){
    Swal?.fire({
      title:'Hapus Abstrak?', text:'Tindakan ini tidak dapat dibatalkan.',
      icon:'warning', showCancelButton:true, confirmButtonColor:'#d33', cancelButtonColor:'#6b7280',
      confirmButtonText:'Ya, hapus', cancelButtonText:'Batal'
    }).then(r=>{
      if(!r.isConfirmed) return;
      const f = document.createElement('form');
      f.method='POST'; f.action='<?= site_url('admin/abstrak/delete') ?>/'+id;
      const i = document.createElement('input'); i.type='hidden'; i.name=csrfName; i.value=csrfHash; f.appendChild(i);
      document.body.appendChild(f); f.submit();
    });
  }
</script>