<?php
/**
 * File: app/Views/role/admin/abstrak/detail.php
 * Expect: $abstrak (array), $reviews (array), $assigned (array)
 */
$abstrak  = $abstrak ?? [];
$reviews  = $reviews ?? [];
$assigned = $assigned ?? [];
$title    = 'Detail Abstrak';
$activeMenu = 'kelola_paper';

$badgeMap = [
  'diterima'        => 'success',
  'ditolak'         => 'danger',
  'menunggu'        => 'secondary',
  'revisi'          => 'warning',
  'sedang_direview' => 'info',
];
$stKey  = strtolower($abstrak['status'] ?? 'menunggu');
$stCls  = $badgeMap[$stKey] ?? 'secondary';
$stText = ($stKey === 'sedang_direview') ? 'Sedang Ditinjau' : ucfirst(str_replace('_',' ', $stKey));

$fmtDT = function($dt){
  if(!$dt) return '-';
  $t = strtotime((string)$dt);
  return date('d M Y H:i', $t);
};
$idAbstrak = (int)($abstrak['id_abstrak'] ?? 0);
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_admin') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl px-3 px-md-4 py-4">

      <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="d-flex align-items-center gap-2">
          <a href="<?= site_url('admin/kelola-paper') ?>" class="btn btn-soft-dark btn-xs">
            <i class="bi bi-arrow-left"></i><span class="ms-1">Kembali</span>
          </a>
          <nav aria-label="breadcrumb" class="small">
            <ol class="breadcrumb mb-0">
              <li class="breadcrumb-item"><a href="<?= site_url('admin/kelola-paper') ?>">Kelola Paper</a></li>
              <?php if (!empty($abstrak['event_id'])): ?>
                <li class="breadcrumb-item">
                  <a href="<?= site_url('admin/kelola-paper/event/'.(int)$abstrak['event_id']) ?>">
                    <?= esc($abstrak['event_title'] ?? 'Event') ?>
                  </a>
                </li>
              <?php endif; ?>
              <li class="breadcrumb-item active" aria-current="page">Detail Abstrak</li>
            </ol>
          </nav>
        </div>
      </div>

      <div class="hero-blue card-glass mb-3 p-3 p-md-4 d-flex justify-content-between align-items-start gap-3">
        <div class="pe-3">
          <h3 class="hero-title mb-1">
            <i class="bi bi-file-earmark-text me-2"></i>Detail Abstrak
          </h3>
          <div class="text-white-75 small line-clip-2"><?= esc($abstrak['judul'] ?? '-') ?></div>
        </div>
        <div class="text-end">
          <div class="mb-2"><span class="badge bg-<?= $stCls ?>"><?= esc($stText) ?></span></div>
          <div class="text-white-75 small">Diunggah</div>
          <div class="fw-semibold text-white"><?= esc($fmtDT($abstrak['tanggal_upload'] ?? null)) ?></div>
        </div>
      </div>

      <div class="row g-3">
        <!-- LEFT -->
        <div class="col-lg-8">
          <!-- Informasi -->
          <div class="card shadow-soft card-glass-plain mb-3">
            <div class="card-header bg-transparent border-0 d-flex align-items-center gap-2 pb-0">
              <span class="badge bg-blue-soft"><i class="bi bi-info-circle"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Informasi</h6>
            </div>
            <div class="card-body">
              <div class="row mb-3">
                <div class="col-md-4 text-muted">Judul</div>
                <div class="col-md-8 fw-semibold line-clip-2"><?= esc($abstrak['judul'] ?? '-') ?></div>
              </div>
              <div class="row mb-3">
                <div class="col-md-4 text-muted">Penulis</div>
                <div class="col-md-8">
                  <div class="fw-semibold"><?= esc($abstrak['nama_lengkap'] ?? '-') ?></div>
                  <div class="small text-muted"><?= esc($abstrak['email'] ?? '-') ?></div>
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-4 text-muted">Kategori</div>
                <div class="col-md-8">
                  <span class="badge bg-primary-subtle"><?= esc($abstrak['nama_kategori'] ?? '-') ?></span>
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-4 text-muted">Event</div>
                <div class="col-md-8">
                  <?php if (!empty($abstrak['event_title'])): ?>
                    <span class="badge bg-secondary-subtle"><?= esc($abstrak['event_title']) ?></span>
                  <?php else: ?>
                    <small class="text-muted">-</small>
                  <?php endif; ?>
                </div>
              </div>
              <div class="row">
                <div class="col-md-4 text-muted">Status</div>
                <div class="col-md-8">
                  <span class="badge bg-<?= $stCls ?>"><?= esc($stText) ?></span>
                </div>
              </div>
            </div>
          </div>

          <!-- PREVIEW PDF + Aksi -->
          <div class="card shadow-soft card-glass-plain mb-3">
            <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-blue-soft"><i class="bi bi-file-earmark-pdf"></i></span>
                <h6 class="mb-0 fw-semibold text-blue-900">Berkas Abstrak</h6>
              </div>
              <div class="d-flex gap-2">
                <a class="btn btn-soft-dark btn-xs"
                   href="<?= site_url('admin/abstrak/download/'.$idAbstrak) ?>">
                  <i class="bi bi-download me-1"></i>Download
                </a>
                <a class="btn btn-ghost btn-xs" target="_blank"
                   href="<?= site_url('admin/abstrak/view/'.$idAbstrak) ?>">
                  <i class="bi bi-box-arrow-up-right me-1"></i>Buka Tab
                </a>
              </div>
            </div>
            <div class="card-body pt-2">
              <div class="pdf-frame-wrap">
                <iframe id="absFrame" class="pdf-frame" title="Preview Abstrak" allow="fullscreen"></iframe>
              </div>
              <script>
                (function(){
                  const frame = document.getElementById('absFrame');
                  fetch('<?= site_url('admin/abstrak/blob/'.$idAbstrak) ?>', {
                    headers: {'X-Requested-With':'XMLHttpRequest'}
                  }).then(r => {
                    if(!r.ok) throw new Error('HTTP '+r.status);
                    return r.blob();
                  }).then(b => {
                    const url = URL.createObjectURL(b);
                    frame.src = url + '#toolbar=1&navpanes=0';
                  }).catch(() => {
                    frame.src = '<?= site_url('admin/abstrak/view/'.$idAbstrak) ?>#toolbar=1&navpanes=0';
                  });
                })();
              </script>
            </div>
          </div>

          <!-- Riwayat Penilaian -->
          <div class="card shadow-soft card-glass-plain">
            <div class="card-header bg-transparent border-0 d-flex align-items-center gap-2 pb-0">
              <span class="badge bg-blue-soft"><i class="bi bi-clock-history"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Riwayat Penilaian</h6>
            </div>
            <div class="card-body">
              <?php if (empty($reviews)): ?>
                <div class="p-4 text-center border rounded-3 bg-light-subtle">
                  <div class="mb-2"><i class="bi bi-inbox fs-3 text-secondary"></i></div>
                  <div class="text-muted">Belum ada penilaian</div>
                </div>
              <?php else: ?>
                <div class="vstack gap-2">
                  <?php foreach ($reviews as $r):
                    $dKey = strtolower($r['keputusan'] ?? 'pending');
                    $dMap = ['diterima'=>'success','ditolak'=>'danger','revisi'=>'warning','pending'=>'secondary','sedang_direview'=>'info'];
                    $dCls = $dMap[$dKey] ?? 'secondary';
                  ?>
                    <div class="p-3 border rounded-3 bg-white">
                      <div class="d-flex justify-content-between align-items-start">
                        <div>
                          <div class="fw-semibold">
                            <?= esc($r['reviewer_name'] ?? '-') ?>
                            <span class="text-muted fw-normal"> — <?= esc($r['reviewer_email'] ?? '') ?></span>
                          </div>
                          <div class="small text-muted"><?= esc($fmtDT($r['tanggal_review'] ?? null)) ?></div>
                        </div>
                        <span class="badge bg-<?= $dCls ?>"><?= ucfirst($dKey) ?></span>
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
          <div class="card shadow-soft card-glass-plain mb-3">
            <div class="card-header bg-transparent border-0 d-flex align-items-center gap-2 pb-0">
              <span class="badge bg-blue-soft"><i class="bi bi-magic"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Aksi</h6>
            </div>
            <div class="card-body">
              <div class="d-grid gap-2">
                <button class="btn btn-primary" onclick="openStatusModal()">
                  <i class="bi bi-pencil-square me-1"></i>Perbarui Status
                </button>

                <button class="btn btn-soft-primary"
                        onclick="openAssign(<?= $idAbstrak ?>,'<?= esc(addslashes($abstrak['judul'] ?? '-')) ?>',<?= (int)($abstrak['id_kategori'] ?? 0) ?>)">
                  <i class="bi bi-person-plus me-1"></i>Tugaskan Reviewer
                </button>

                <a class="btn btn-soft-dark" href="<?= site_url('admin/abstrak/download/'.$idAbstrak) ?>">
                  <i class="bi bi-download me-1"></i>Unduh Berkas
                </a>

                <hr class="my-2">
                <button class="btn btn-danger" onclick="deleteAbstrak(<?= $idAbstrak ?>)"><i class="bi bi-trash me-1"></i>Hapus</button>
              </div>
            </div>
          </div>

          <!-- Reviewer Ditugaskan -->
          <div class="card shadow-soft card-glass-plain">
            <div class="card-header bg-transparent border-0 d-flex align-items-center gap-2 pb-0">
              <span class="badge bg-blue-soft"><i class="bi bi-people"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Reviewer Ditugaskan</h6>
            </div>
            <div class="card-body">
              <?php if (empty($assigned)): ?>
                <div class="text-muted">Belum ada reviewer yang ditugaskan.</div>
              <?php else: ?>
                <div class="vstack gap-2">
                  <?php foreach ($assigned as $a):
                    $k = strtolower($a['status'] ?? 'pending');
                    $cls = ['pending'=>'secondary','diterima'=>'success','ditolak'=>'danger','revisi'=>'warning','sedang_direview'=>'info'][$k] ?? 'secondary';
                  ?>
                  <div class="p-2 rounded border bg-white d-flex justify-content-between align-items-start">
                    <div>
                      <div class="fw-semibold"><?= esc($a['nama']) ?></div>
                      <div class="small text-muted"><?= esc($a['email']) ?></div>
                      <?php if(!empty($a['tanggal'])): ?>
                        <div class="small text-muted"><?= esc($fmtDT($a['tanggal'])) ?></div>
                      <?php endif; ?>
                    </div>
                    <span class="badge bg-<?= $cls ?>"><?= ucfirst($k) ?></span>
                  </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

<!-- Modal Tugaskan Reviewer -->
<div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header bg-primary text-white">
      <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Tugaskan Reviewer</h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <form id="assignForm" method="POST">
      <?= csrf_field() ?>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Judul</label>
          <input type="text" id="assignTitle" class="form-control" readonly>
        </div>
        <div class="mb-3">
          <label class="form-label">Pilih Reviewer</label>
          <select class="form-select" id="reviewerSelect" name="id_reviewer" required>
            <option value="">Pilih reviewer…</option>
          </select>
          <div class="form-text">Daftar disesuaikan dengan kategori.</div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i>Simpan</button>
      </div>
    </form>
  </div></div>
</div>

<!-- Modal Perbarui Status -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header bg-primary text-white">
      <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Perbarui Status</h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <form id="statusForm">
      <?= csrf_field() ?>
      <div class="modal-body">
        <input type="hidden" id="statusAbstrakId" name="id_abstrak" value="<?= $idAbstrak ?>">
        <div class="mb-3">
          <label class="form-label">Status</label>
          <select class="form-select" id="statusSelect" name="status" required>
            <option value="diterima"        <?= $stKey==='diterima'?'selected':''        ?>>Diterima</option>
            <option value="ditolak"         <?= $stKey==='ditolak'?'selected':''         ?>>Ditolak</option>
            <option value="revisi"          <?= $stKey==='revisi'?'selected':''          ?>>Revisi</option>
            <option value="sedang_direview" <?= $stKey==='sedang_direview'?'selected':'' ?>>Sedang Ditinjau</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Catatan (opsional)</label>
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
  /* tambah 2 token ini untuk kontrol lebar & padding */
  --side-pad: clamp(.75rem, 1.2vw, 1.25rem);
  --container-max: 1680px;

  /* existing color tokens */
  --blue-50:#eff6ff; --blue-200:#bfdbfe; --blue-600:#2563eb; --blue-700:#1d4ed8; --blue-800:#1e40af; --blue-900:#1e3a8a;
}

/* latar & navbar offset tetap */
.page-wrap-blue{
  background:linear-gradient(180deg,var(--blue-50),#fff 40%);
  min-height:100vh; padding-top:72px;
}

/* <<< LEBAR KONTEN DIBESARKAN & padding samping dirampingkan */
.container-xxl{
  max-width: min(100%, var(--container-max));
  padding-left: var(--side-pad) !important;
  padding-right: var(--side-pad) !important;
}

/* hero & cards (tetap) */
.hero-blue{
  background:radial-gradient(1200px 400px at 10% -20%,var(--blue-600) 0,var(--blue-700) 40%,var(--blue-800) 100%)!important;
  color:#fff!important; border-radius:16px; border:1px solid rgba(255,255,255,.15);
  box-shadow:0 12px 28px rgba(30,64,175,.10);
}
.text-white-75{ color:rgba(255,255,255,.85)!important; }
.card-glass-plain{ backdrop-filter:blur(6px); background:rgba(255,255,255,.94); border-radius:14px; border:1px solid rgba(30,64,175,.10); }
.shadow-soft{ box-shadow:0 10px 24px rgba(30,64,175,.08); }
.bg-blue-soft{ background:var(--blue-200); color:var(--blue-900); border-radius:12px; padding:.4rem .6rem; font-weight:600; font-size:.85rem; }
.bg-secondary-subtle{ background:#f1f5f9!important; color:#475569!important; }
.bg-primary-subtle{ background:#dbeafe!important; color:var(--blue-700)!important; }
.text-blue-900{ color:var(--blue-900)!important; }
.line-clip-2{ display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }

/* Buttons – sama */
.btn{ border-radius:10px; font-weight:600; letter-spacing:.25px; font-size:.95rem; padding:.55rem 1rem; }
.btn-xs{ padding:.42rem .75rem; font-size:.85rem; line-height:1.2; border-radius:8px; }
.btn-soft-dark{ background:#f1f5f9; color:#111827; border:1px solid #e2e8f0; }
.btn-soft-dark:hover{ background:#111827; color:#fff; border-color:#111827; }
.btn-soft-primary{ background:#e0ecff; color:#123; border:1px solid rgba(37,99,235,.25); }
.btn-soft-primary:hover{ background:#2563eb; color:#fff; border-color:#2563eb; }

/* Tabel */
.table{ width:100%; table-layout:fixed; border-collapse:separate; border-spacing:0; }
.table thead th{
  background-color:#f8fafc!important; border-bottom:1px solid #e5e7eb;
  font-weight:600; color:var(--blue-900); font-size:.82rem; text-transform:uppercase; letter-spacing:.3px;
  padding:.55rem .5rem; white-space:nowrap;
}
.table tbody td{ padding:.55rem .5rem; vertical-align:middle; border-top:none; word-break:break-word; white-space:normal; }

/* PDF frame */
.pdf-frame-wrap{ height:72vh; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; }
.pdf-frame{ width:100%; height:100%; border:0; }

/* breadcrumb */
.breadcrumb .breadcrumb-item + .breadcrumb-item::before{ content: ">"; }

/* optional: sedikit besarkan jarak antar kolom di layar lebar */
@media (min-width: 1200px){
  .row.g-3{ --bs-gutter-x: 1.25rem; }
}

@media (max-width: 768px){
  .pdf-frame-wrap{ height:60vh; }
}
</style>


<script>
  // CSRF
  const csrfName = '<?= csrf_token() ?>';
  let   csrfHash = '<?= csrf_hash() ?>';

  function openStatusModal(){
    new bootstrap.Modal(document.getElementById('statusModal')).show();
  }

  document.getElementById('statusForm')?.addEventListener('submit', function(e){
    e.preventDefault();
    const statusVal = document.getElementById('statusSelect').value;
    if (!['diterima','ditolak','revisi','sedang_direview','menunggu'].includes(statusVal)) {
      return Swal?.fire('Tidak valid','Pilih status yang tersedia','warning');
    }
    const data = new URLSearchParams();
    data.append('id_abstrak', document.getElementById('statusAbstrakId').value);
    data.append('status',     statusVal);
    data.append('komentar',   document.getElementById('statusKomentar').value || '');
    data.append(csrfName, csrfHash);

    fetch('<?= site_url('admin/abstrak/update-status') ?>', {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},
      body:data.toString()
    }).then(r=>r.json()).then(res=>{
      if(res && res.success){
        Swal?.fire('Tersimpan', res.message || 'Status diperbarui', 'success').then(()=>location.reload());
      }else{
        Swal?.fire('Gagal', (res && res.message) || 'Terjadi kesalahan', 'error');
      }
      if(res && res[csrfName]) csrfHash = res[csrfName];
    }).catch(()=> Swal?.fire('Error','Tidak dapat menghubungi server','error'));
  });

  function openAssign(idAbstrak, judul, idKategori){
    document.getElementById('assignTitle').value = judul;
    document.getElementById('assignForm').action = '<?= site_url('admin/abstrak/assign') ?>/'+idAbstrak;

    fetch('<?= site_url('admin/abstrak/reviewers-by-category') ?>/'+idKategori, {
      headers:{'X-Requested-With':'XMLHttpRequest'}
    }).then(r=>r.json())
      .then(res=>{
        const items = Array.isArray(res) ? res : (res.data || []);
        if (!items || items.length === 0) {
          throw new Error('Daftar reviewer kosong');
        }
        const sel = document.getElementById('reviewerSelect');
        sel.innerHTML = '<option value="">Pilih reviewer…</option>';
        items.forEach(rv=>{
          const opt = document.createElement('option');
          opt.value = rv.id_user || rv.id || rv.id_reviewer;
          opt.textContent = (rv.nama || rv.nama_lengkap || '—') + (rv.email ? ' — ' + rv.email : '');
          sel.appendChild(opt);
        });
        new bootstrap.Modal(document.getElementById('assignModal')).show();
      })
      .catch(()=> Swal?.fire('Error','Gagal memuat reviewer','error'));
  }

  function deleteAbstrak(id){
    Swal?.fire({
      title:'Hapus Abstrak?',
      text:'Tindakan ini tidak dapat dibatalkan.',
      icon:'warning', showCancelButton:true,
      confirmButtonColor:'#d33', cancelButtonColor:'#6b7280',
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