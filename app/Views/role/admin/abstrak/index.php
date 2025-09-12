<?php
// ====== DEFAULT VARS ======
$title            = $title ?? 'Kelola Abstrak';
$total_abstrak    = (int)($total_abstrak ?? 0);
$abstrak_pending  = (int)($abstrak_pending ?? 0);
$abstrak_diterima = (int)($abstrak_diterima ?? 0);
$abstrak_ditolak  = (int)($abstrak_ditolak ?? 0);
$abstraks         = $abstraks ?? [];
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_admin') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- HEADER (khusus halaman ini: header-blue) -->
      <div class="header-section header-blue d-flex justify-content-between align-items-center mb-3">
        <div>
          <h3 class="welcome-text mb-1">
            <i class="bi bi-file-earmark-text me-2"></i>Kelola Abstrak
          </h3>
          <div class="text-muted">Kelola dan review semua abstrak yang masuk ke sistem SNIA</div>
        </div>
        <div class="text-end d-none d-md-block">
          <small class="text-muted d-block">Terakhir update</small>
          <strong><?= date('d M Y, H:i') ?></strong>
        </div>
      </div>

      <!-- KPI 4 KOTAK (selaras dashboard) -->
      <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-primary"><i class="bi bi-file-earmark-text"></i></div>
              <div class="ms-3">
                <div class="stat-number" data-num="<?= $total_abstrak ?>"><?= number_format($total_abstrak) ?></div>
                <div class="text-muted">Total Abstrak</div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-warning"><i class="bi bi-clock-history"></i></div>
              <div class="ms-3">
                <div class="stat-number" data-num="<?= $abstrak_pending ?>"><?= number_format($abstrak_pending) ?></div>
                <div class="text-muted">Menunggu Review</div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-success"><i class="bi bi-check2-circle"></i></div>
              <div class="ms-3">
                <div class="stat-number" data-num="<?= $abstrak_diterima ?>"><?= number_format($abstrak_diterima) ?></div>
                <div class="text-muted">Diterima</div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-danger"><i class="bi bi-x-circle"></i></div>
              <div class="ms-3">
                <div class="stat-number" data-num="<?= $abstrak_ditolak ?>"><?= number_format($abstrak_ditolak) ?></div>
                <div class="text-muted">Ditolak</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- TABEL -->
      <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
          <h6 class="mb-0"><i class="bi bi-list me-2"></i>Daftar Abstrak</h6>
          <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="<?= site_url('admin/abstrak/export') ?>">
              <i class="bi bi-download me-1"></i>Export
            </a>
            <button class="btn btn-outline-secondary btn-sm" onclick="location.reload()">
              <i class="bi bi-arrow-clockwise me-1"></i>Refresh
            </button>
          </div>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th>No</th>
                  <th>Judul</th>
                  <th>Penulis</th>
                  <th>Kategori</th>
                  <th>Event</th>
                  <th>Status</th>
                  <th>Upload</th>
                  <th>Reviewer</th>
                  <th class="text-end">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($abstraks)): ?>
                  <?php $no=1; foreach($abstraks as $ab): ?>
                    <tr>
                      <td><?= $no++ ?></td>
                      <td>
                        <div class="fw-semibold"><?= esc($ab['judul']) ?></div>
                        <small class="text-muted">Revisi ke-<?= (int)($ab['revisi_ke'] ?? 0) ?></small>
                      </td>
                      <td>
                        <div><?= esc($ab['nama_lengkap']) ?></div>
                        <small class="text-muted"><?= esc($ab['email']) ?></small>
                      </td>
                      <td><span class="badge bg-info"><?= esc($ab['nama_kategori']) ?></span></td>
                      <td>
                        <?php if (!empty($ab['event_title'])): ?>
                          <span class="badge bg-secondary"><?= esc($ab['event_title']) ?></span>
                        <?php else: ?>
                          <small class="text-muted">-</small>
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php
                          $map = ['menunggu'=>'warning','sedang_direview'=>'info','diterima'=>'success','ditolak'=>'danger','revisi'=>'secondary'];
                          $cls = $map[$ab['status']] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?= $cls ?>"><?= ucfirst(str_replace('_',' ',$ab['status'])) ?></span>
                      </td>
                      <td><?= date('d/m/Y H:i', strtotime($ab['tanggal_upload'])) ?></td>
                      <td>
                        <?php if ($ab['status']==='menunggu'): ?>
                          <button class="btn btn-outline-primary btn-sm"
                                  onclick="showAssignModal(<?= (int)$ab['id_abstrak'] ?>,'<?= esc(addslashes($ab['judul'])) ?>', <?= (int)$ab['id_kategori'] ?>)">
                            <i class="bi bi-person-plus me-1"></i>Assign
                          </button>
                        <?php else: ?>
                          <small class="text-success"><i class="bi bi-check2"></i> Assigned</small>
                        <?php endif; ?>
                      </td>
                      <td class="text-end">
                        <div class="btn-group btn-group-sm">
                          <a class="btn btn-info" href="<?= site_url('admin/abstrak/detail/'.(int)$ab['id_abstrak']) ?>">
                            <i class="bi bi-eye"></i>
                          </a>
                          <a class="btn btn-primary" href="<?= site_url('admin/abstrak/download/'.(int)$ab['id_abstrak']) ?>">
                            <i class="bi bi-download"></i>
                          </a>
                          <button class="btn btn-warning" onclick="openStatusModal(<?= (int)$ab['id_abstrak'] ?>)">
                            <i class="bi bi-pencil-square"></i>
                          </button>
                          <button class="btn btn-danger" onclick="deleteAbstrak(<?= (int)$ab['id_abstrak'] ?>)">
                            <i class="bi bi-trash"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="9" class="text-center py-5 text-muted">Belum ada data.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
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
      <h5 class="modal-title"><i class="bi bi-user-plus me-2"></i>Assign Reviewer</h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <form id="assignForm" method="POST">
      <?= csrf_field() ?>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Judul Abstrak</label>
          <input type="text" class="form-control" id="assignTitle" readonly>
        </div>
        <div class="mb-3">
          <label class="form-label">Pilih Reviewer</label>
          <select class="form-select" name="id_reviewer" id="reviewerSelect" required>
            <option value="">-- Pilih Reviewer --</option>
          </select>
          <div class="form-text">Hanya reviewer dengan keahlian kategori yang cocok.</div>
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
        <input type="hidden" id="statusAbstrakId" name="id_abstrak">
        <div class="mb-3">
          <label class="form-label">Status</label>
          <select class="form-select" id="statusSelect" name="status" required>
            <option value="menunggu">Menunggu</option>
            <option value="sedang_direview">Sedang Review</option>
            <option value="diterima">Diterima</option>
            <option value="ditolak">Ditolak</option>
            <option value="revisi">Perlu Revisi</option>
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

  /* ====== HEADER DEFAULT (dari dashboard) ====== */
  .header-section{ background:#fff; padding:20px; border-radius:14px; box-shadow:0 4px 20px rgba(0,0,0,.05); }
  .welcome-text{ color:var(--primary-color); font-weight:700; }

  /* ====== OVERRIDE KHUSUS HALAMAN INI ====== */
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

  /* ====== KOMPONEN (selaras dashboard) ====== */
  .stat-card{
    background:#fff; border-radius:14px; padding:20px; box-shadow:0 8px 28px rgba(0,0,0,.08);
    border-left:4px solid #e9ecef; position:relative; overflow:hidden;
  }
  .stat-card:before{
    content:''; position:absolute; left:0; top:0; height:4px; width:100%;
    background:linear-gradient(90deg,var(--primary-color),var(--info-color));
  }
  .stat-icon{
    width:56px; height:56px; border-radius:12px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:22px;
  }
  .stat-number{ font-size:2rem; font-weight:800; color:#1e293b; line-height:1; }

  /* jarak dari header global */
  #content main>.container-fluid{ margin-top:.25rem; }
</style>

<script>
  // Animasi angka KPI (ringan)
  (function(){
    document.querySelectorAll('.stat-number').forEach(el=>{
      const target = parseInt(el.getAttribute('data-num')||'0',10);
      let cur = 0; const step = Math.max(1, Math.round(target/40));
      const id = setInterval(()=>{
        cur += step;
        if(cur >= target){ cur = target; clearInterval(id); }
        el.textContent = new Intl.NumberFormat('id-ID').format(cur);
      }, 18);
    });
  })();

  // ====== CSRF (CI4) ======
  const csrfName = '<?= csrf_token() ?>';
  let   csrfHash = '<?= csrf_hash() ?>';

  // ====== ASSIGN REVIEWER ======
  function showAssignModal(idAbstrak, judul, idKategori){
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

  // ====== UPDATE STATUS ======
  function openStatusModal(idAbstrak){
    document.getElementById('statusAbstrakId').value = idAbstrak;
    new bootstrap.Modal(document.getElementById('statusModal')).show();
  }

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
        Swal.fire('Berhasil!', res.message || 'Status diperbarui', 'success').then(()=>location.reload());
      }else{
        Swal.fire('Gagal!', (res && res.message) || 'Terjadi kesalahan', 'error');
      }
      if(res && res[csrfName]) csrfHash = res[csrfName];
    }).catch(()=> Swal.fire('Error','Gagal menghubungi server','error'));
  });

  // ====== DELETE (POST + CSRF) ======
  function deleteAbstrak(id){
    Swal.fire({
      title:'Hapus Abstrak?', text:'Tindakan ini tidak dapat dibatalkan.',
      icon:'warning', showCancelButton:true, confirmButtonColor:'#d33', cancelButtonColor:'#6b7280',
      confirmButtonText:'Ya, hapus', cancelButtonText:'Batal'
    }).then(r=>{
      if(!r.isConfirmed) return;
      const f = document.createElement('form');
      f.method='POST'; f.action='<?= site_url('admin/abstrak/delete') ?>/'+id;
      const i = document.createElement('input');
      i.type='hidden'; i.name=csrfName; i.value=csrfHash; f.appendChild(i);
      document.body.appendChild(f); f.submit();
    });
  }
</script>