<?php
// ====== DEFAULT VARS ======
$title             = $title ?? 'Kelola Reviewer';
$reviewers         = $reviewers ?? [];         // setiap item: id_user,nama_lengkap,email,status,total_reviews,pending_reviews,categories:[{id_kategori,nama_kategori}]
$categories        = $categories ?? [];        // id_kategori,nama_kategori
$total_reviewers   = (int)($total_reviewers   ?? 0);
$active_reviewers  = (int)($active_reviewers  ?? 0);
$total_categories  = (int)($total_categories  ?? 0);
$activation_rate   = $total_reviewers>0 ? round(($active_reviewers/$total_reviewers)*100) : 0;
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_admin') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- HEADER (seragam dengan dashboard) -->
      <div class="header-section header-blue d-flex justify-content-between align-items-center mb-3"> 
        <div>
          <h3 class="welcome-text mb-1">
            <i class="bi bi-person-check me-2"></i>Kelola Reviewer
          </h3>
          <div class="text-muted">Manajemen reviewer untuk proses review abstrak</div>
        </div>
        <div class="text-end d-none d-md-block">
          <small class="text-muted d-block">Terakhir update</small>
          <strong><?= date('d M Y, H:i') ?></strong>
        </div>
      </div>

      <!-- KPI -->
      <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-primary"><i class="bi bi-people"></i></div>
              <div class="ms-3">
                <div class="stat-number"><?= number_format($total_reviewers) ?></div>
                <div class="text-muted">Total Reviewer</div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-success"><i class="bi bi-check2-circle"></i></div>
              <div class="ms-3">
                <div class="stat-number"><?= number_format($active_reviewers) ?></div>
                <div class="text-muted">Reviewer Aktif</div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-info"><i class="bi bi-tags"></i></div>
              <div class="ms-3">
                <div class="stat-number"><?= number_format($total_categories) ?></div>
                <div class="text-muted">Total Kategori</div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-warning"><i class="bi bi-percent"></i></div>
              <div class="ms-3">
                <div class="stat-number"><?= $activation_rate ?>%</div>
                <div class="text-muted">Tingkat Aktivasi</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- FILTERS -->
      <div class="card shadow-sm mb-3">
        <div class="card-body">
          <div class="row g-2">
            <div class="col-md-4">
              <div class="position-relative">
                <input type="text" class="form-control ps-5" id="searchReviewer" placeholder="Cari reviewer (nama/email)...">
                <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
              </div>
            </div>
            <div class="col-md-3">
              <select class="form-select" id="filterStatus">
                <option value="">Semua Status</option>
                <option value="aktif">Aktif</option>
                <option value="nonaktif">Nonaktif</option>
              </select>
            </div>
            <div class="col-md-3">
              <select class="form-select" id="filterCategory">
                <option value="">Semua Kategori</option>
                <?php foreach ($categories as $c): ?>
                  <option value="<?= strtolower(esc($c['nama_kategori'])) ?>"><?= esc($c['nama_kategori']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-2 text-md-end">
              <div class="d-grid d-md-block">
                <button class="btn btn-outline-secondary me-2 mb-2 mb-md-0" onclick="resetFilters()">
                  <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                </button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addReviewerModal">
                  <i class="bi bi-plus-lg me-1"></i>Tambah
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- GRID REVIEWERS -->
      <div class="row g-3" id="reviewersGrid">
        <?php if (!empty($reviewers)): ?>
          <?php foreach ($reviewers as $rv):
            $name   = $rv['nama_lengkap'] ?? '-';
            $email  = $rv['email'] ?? '-';
            $status = strtolower($rv['status'] ?? 'nonaktif');
            $tot    = (int)($rv['total_reviews'] ?? 0);
            $pend   = (int)($rv['pending_reviews'] ?? 0);
            $done   = max(0, $tot - $pend);
            $pct    = $tot > 0 ? round(($done / $tot) * 100) : 0;
            $cats   = $rv['categories'] ?? []; // array of ['id_kategori','nama_kategori']
            $catsStr= strtolower(implode(',', array_map(fn($x)=>$x['nama_kategori'] ?? '', $cats)));
          ?>
            <div class="col-lg-6 col-xl-4 reviewer-item"
                 data-name="<?= strtolower(esc($name)) ?>"
                 data-email="<?= strtolower(esc($email)) ?>"
                 data-status="<?= esc($status) ?>"
                 data-categories="<?= esc($catsStr) ?>">
              <div class="card shadow-sm h-100">
                <div class="card-body">
                  <div class="d-flex align-items-start">
                    <div class="user-avatar me-3">
                      <?= strtoupper(substr($name,0,1)) ?>
                    </div>
                    <div class="flex-fill">
                      <div class="d-flex justify-content-between">
                        <div>
                          <div class="fw-semibold"><?= esc($name) ?></div>
                          <small class="text-muted"><?= esc($email) ?></small>
                          <div class="mt-2">
                            <span class="badge <?= $status==='aktif'?'bg-success':'bg-secondary' ?>"><?= ucfirst($status) ?></span>
                          </div>
                        </div>
                        <div class="dropdown">
                          <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
                            <i class="bi bi-three-dots-vertical"></i>
                          </button>
                          <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                              <a class="dropdown-item" href="<?= site_url('admin/reviewer/detail/'.(int)$rv['id_user']) ?>">
                                <i class="bi bi-eye me-2"></i>Detail
                              </a>
                            </li>
                            <li>
                              <a class="dropdown-item" href="#"
                                 onclick="toggleStatus(<?= (int)$rv['id_user'] ?>,'<?= $status ?>')">
                                <i class="bi bi-power me-2"></i><?= $status==='aktif'?'Nonaktifkan':'Aktifkan' ?>
                              </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                              <a class="dropdown-item text-danger" href="#"
                                 onclick="deleteReviewer(<?= (int)$rv['id_user'] ?>,'<?= esc(addslashes($name)) ?>')">
                                <i class="bi bi-trash me-2"></i>Hapus
                              </a>
                            </li>
                          </ul>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Stats -->
                  <div class="row text-center mt-3">
                    <div class="col-4">
                      <div class="fw-bold text-primary"><?= $tot ?></div>
                      <small class="text-muted">Total</small>
                    </div>
                    <div class="col-4">
                      <div class="fw-bold text-warning"><?= $pend ?></div>
                      <small class="text-muted">Pending</small>
                    </div>
                    <div class="col-4">
                      <div class="fw-bold text-success"><?= $pct ?>%</div>
                      <small class="text-muted">Selesai</small>
                    </div>
                  </div>

                  <div class="progress mt-2" style="height:8px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= $pct ?>%;"></div>
                  </div>

                  <!-- Categories -->
                  <div class="mt-3">
                    <label class="small text-muted fw-semibold d-block mb-1">Kategori review:</label>
                    <?php if (!empty($cats)): ?>
                      <div class="d-flex flex-wrap gap-1">
                        <?php foreach ($cats as $c): ?>
                          <span class="badge bg-primary"><?= esc($c['nama_kategori']) ?></span>
                        <?php endforeach; ?>
                      </div>
                    <?php else: ?>
                      <small class="text-muted">Belum ada kategori</small>
                    <?php endif; ?>
                  </div>

                  <!-- Actions -->
                  <div class="d-flex gap-2 mt-3">
                    <button class="btn btn-outline-info btn-sm flex-fill"
                            onclick="assignCategory(<?= (int)$rv['id_user'] ?>,'<?= esc(addslashes($name)) ?>')">
                      <i class="bi bi-plus-lg me-1"></i>Kategori
                    </button>
                    <a class="btn btn-outline-primary btn-sm flex-fill"
                       href="<?= site_url('admin/reviewer/detail/'.(int)$rv['id_user']) ?>">
                      <i class="bi bi-graph-up me-1"></i>Performa
                    </a>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="col-12">
            <div class="p-4 text-center border rounded-3 bg-light-subtle">
              <div class="mb-2"><i class="bi bi-people fs-3 text-secondary"></i></div>
              <div class="fw-semibold">Belum ada reviewer</div>
              <div class="text-muted small mb-3">Tambah reviewer pertama untuk memulai proses review.</div>
              <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addReviewerModal">
                <i class="bi bi-plus-lg me-1"></i>Tambah Reviewer
              </button>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <!-- No Results -->
      <div class="d-none" id="noResults">
        <div class="p-4 text-center border rounded-3 bg-light-subtle mt-3">
          <div class="mb-2"><i class="bi bi-search fs-3 text-secondary"></i></div>
          <div class="fw-semibold">Tidak ada hasil</div>
          <div class="text-muted small mb-3">Ubah kata kunci atau reset filter.</div>
          <button class="btn btn-outline-primary" onclick="resetFilters()">
            <i class="bi bi-arrow-counterclockwise me-1"></i>Reset Filter
          </button>
        </div>
      </div>

    </div>
  </main>
</div>

<!-- MODALS -->
<!-- Add Reviewer -->
<div class="modal fade" id="addReviewerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header bg-primary text-white">
      <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Tambah Reviewer Baru</h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <form action="<?= site_url('admin/reviewer/store') ?>" method="POST" id="addReviewerForm">
      <?= csrf_field() ?>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-6"><div class="mb-3">
            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="nama_lengkap" required>
            <div class="invalid-feedback"></div>
          </div></div>
          <div class="col-md-6"><div class="mb-3">
            <label class="form-label">Email <span class="text-danger">*</span></label>
            <input type="email" class="form-control" name="email" required>
            <div class="invalid-feedback"></div>
          </div></div>
        </div>
        <div class="mb-3">
          <label class="form-label">Password <span class="text-danger">*</span></label>
          <div class="input-group">
            <input type="password" class="form-control" name="password" id="password" minlength="6" required>
            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
              <i id="passwordToggle" class="bi bi-eye"></i>
            </button>
          </div>
          <small class="text-muted">Minimal 6 karakter</small>
          <div class="invalid-feedback"></div>
        </div>
        <div class="mb-2">
          <label class="form-label">Kategori Review <span class="text-danger">*</span></label>
          <div class="row">
            <?php if (!empty($categories)): ?>
              <?php foreach ($categories as $c): ?>
                <div class="col-md-6 mb-2">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="categories[]" value="<?= (int)$c['id_kategori'] ?>" id="cat_<?= (int)$c['id_kategori'] ?>">
                    <label class="form-check-label" for="cat_<?= (int)$c['id_kategori'] ?>"><?= esc($c['nama_kategori']) ?></label>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="col-12">
                <div class="alert alert-warning mb-0">
                  <i class="bi bi-exclamation-triangle me-2"></i>Belum ada kategori. Buat kategori terlebih dahulu.
                </div>
              </div>
            <?php endif; ?>
          </div>
          <div class="invalid-feedback d-block" id="categoriesError" style="display:none!important;"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i>Simpan</button>
      </div>
    </form>
  </div></div>
</div>

<!-- Assign Category -->
<div class="modal fade" id="assignCategoryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header bg-primary text-white">
      <h5 class="modal-title"><i class="bi bi-tags me-2"></i>Assign Kategori</h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <form action="<?= site_url('admin/reviewer/assignCategory') ?>" method="POST">
      <?= csrf_field() ?>
      <div class="modal-body">
        <input type="hidden" name="reviewer_id" id="assignReviewerId">
        <div class="mb-3">
          <label class="form-label">Reviewer</label>
          <input type="text" class="form-control" id="assignReviewerName" readonly>
        </div>
        <div class="mb-3">
          <label class="form-label">Pilih Kategori</label>
          <select class="form-select" name="category_id" required>
            <option value="">Pilih Kategori</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?= (int)$c['id_kategori'] ?>"><?= esc($c['nama_kategori']) ?></option>
            <?php endforeach; ?>
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
    --primary-color:#2563eb; --info-color:#06b6d4; --success-color:#10b981; --warning-color:#f59e0b; --danger-color:#ef4444;
  }
  body{ background:linear-gradient(135deg,#f8fafc 0%,#e2e8f0 100%); font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; }

  .header-section.header-blue{
    background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%);
    color:#fff; padding:28px 24px; border-radius:16px; box-shadow:0 8px 28px rgba(0,0,0,.12);
  }
  .header-section.header-blue .welcome-text{ color:#fff; font-weight:800; font-size:2rem; }
  .header-section.header-blue .text-muted, .header-section.header-blue strong{ color:rgba(255,255,255,.9)!important; }
  
  .welcome-text{ color:var(--primary-color); font-weight:700; }

  .stat-card{
    background:#fff; border-radius:14px; padding:20px; box-shadow:0 8px 28px rgba(0,0,0,.08);
    border-left:4px solid #e9ecef; position:relative; overflow:hidden;
  }
  .stat-card:before{
    content:''; position:absolute; left:0; top:0; height:4px; width:100%;
    background:linear-gradient(90deg,var(--primary-color),var(--info-color));
  }
  .stat-icon{ width:56px; height:56px; border-radius:12px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:22px; }
  .stat-number{ font-size:2rem; font-weight:800; color:#1e293b; line-height:1; }

  .user-avatar{
    width:48px; height:48px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; color:#fff;
    background:linear-gradient(135deg,var(--primary-color),var(--info-color));
  }

  #content main>.container-fluid{ margin-top:.25rem; }
</style>

<script>
  // ===== Filter logic =====
  function filterReviewers(){
    const q   = (document.getElementById('searchReviewer').value || '').toLowerCase();
    const st  = document.getElementById('filterStatus').value;
    const cat = (document.getElementById('filterCategory').value || '').toLowerCase();

    let visible = 0;
    document.querySelectorAll('.reviewer-item').forEach(card=>{
      const name = card.dataset.name || '';
      const mail = card.dataset.email || '';
      const s    = card.dataset.status || '';
      const cats = card.dataset.categories || '';

      const okQ   = !q   || name.includes(q) || mail.includes(q);
      const okSt  = !st  || s === st;
      const okCat = !cat || cats.includes(cat);

      const show = okQ && okSt && okCat;
      card.style.display = show ? '' : 'none';
      if (show) visible++;
    });

    const noRes = document.getElementById('noResults');
    if (visible === 0 && document.querySelectorAll('.reviewer-item').length > 0) {
      noRes.classList.remove('d-none');
    } else {
      noRes.classList.add('d-none');
    }
  }
  function resetFilters(){
    document.getElementById('searchReviewer').value = '';
    document.getElementById('filterStatus').value = '';
    document.getElementById('filterCategory').value = '';
    filterReviewers();
  }

  // ===== Misc helpers =====
  function togglePassword(){
    const field = document.getElementById('password');
    const icon  = document.getElementById('passwordToggle');
    if(field.type === 'password'){ field.type='text'; icon.classList.remove('bi-eye'); icon.classList.add('bi-eye-slash'); }
    else{ field.type='password'; icon.classList.remove('bi-eye-slash'); icon.classList.add('bi-eye'); }
  }

  function assignCategory(id, name){
    document.getElementById('assignReviewerId').value   = id;
    document.getElementById('assignReviewerName').value = name;
    new bootstrap.Modal(document.getElementById('assignCategoryModal')).show();
  }

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

  function deleteReviewer(id, name){
    if (!window.Swal){
      if(confirm('Hapus reviewer "'+name+'"?')) location.href = '<?= site_url('admin/reviewer/delete') ?>/'+id;
      return;
    }
    Swal.fire({
      title:'Hapus Reviewer?', text:'"' + name + '" akan dihapus permanen.',
      icon:'warning', showCancelButton:true, confirmButtonColor:'#ef4444', cancelButtonColor:'#6b7280',
      confirmButtonText:'Ya, Hapus', cancelButtonText:'Batal'
    }).then(r=>{ if(r.isConfirmed){ location.href = '<?= site_url('admin/reviewer/delete') ?>/'+id; } });
  }

  // ===== Form validation (add reviewer) =====
  document.getElementById('addReviewerForm')?.addEventListener('submit', function(e){
    let ok = true;
    const reqs = this.querySelectorAll('[required]');
    reqs.forEach(f=>{
      f.classList.remove('is-invalid');
      const fb = f.parentNode.querySelector('.invalid-feedback'); if(fb) fb.textContent='';
      if(!f.value.trim()){ f.classList.add('is-invalid'); if(fb) fb.textContent='Field ini wajib diisi.'; ok=false; }
    });
    const checked = this.querySelectorAll('input[name="categories[]"]:checked');
    const catErr  = document.getElementById('categoriesError');
    if(checked.length===0){ catErr.textContent='Pilih setidaknya satu kategori review.'; catErr.style.display='block'; ok=false; }
    else { catErr.style.display='none'; }
    if(!ok){ e.preventDefault(); if(window.Swal) Swal.fire('Error!','Mohon lengkapi data wajib','error'); }
  });

  // ===== Bind filters & flash =====
  document.addEventListener('DOMContentLoaded', ()=>{
    document.getElementById('searchReviewer')?.addEventListener('input', filterReviewers);
    document.getElementById('filterStatus')?.addEventListener('change', filterReviewers);
    document.getElementById('filterCategory')?.addEventListener('change', filterReviewers);

    <?php if (session('success')): ?>
      window.Swal?.fire({icon:'success',title:'Berhasil!',text:'<?= esc(session('success')) ?>',timer:2800,showConfirmButton:false});
    <?php endif; ?>
    <?php if (session('error')): ?>
      window.Swal?.fire({icon:'error',title:'Error!',text:'<?= esc(session('error')) ?>'});
    <?php endif; ?>
    <?php if (session('errors')): ?>
      window.Swal?.fire({icon:'error',title:'Validation Error!',html:'<?= esc(implode("<br>", (array)session("errors"))) ?>'});
    <?php endif; ?>
  });
</script>
