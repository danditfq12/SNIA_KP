<?php
// ====== DEFAULT VARS ======
$title   = $title ?? 'Manajemen Dokumen';
$stats   = $stats ?? ['total_documents'=>0,'loa_count'=>0,'sertifikat_count'=>0,'recent_uploads'=>0];
$events  = $events ?? [];           // id, title
$documents = $documents ?? [];      // id_dokumen, tipe, nama_lengkap, email, role, event_title, file_path, uploaded_at
$current_event = $current_event ?? '';
$current_tipe  = $current_tipe  ?? '';
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_admin') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- HEADER (seragam template) -->
      <div class="header-section header-blue d-flex justify-content-between align-items-center mb-3"> 
        <div>
          <h3 class="welcome-text mb-1">
            <i class="bi bi-folder2-open me-2"></i><?= esc($title) ?>
          </h3>
          <div class="text-white-50">Kelola LOA & Sertifikat untuk setiap event</div>
        </div>
        <div class="text-end d-none d-md-block">
          <small class="text-white-50 d-block">Terakhir update</small>
          <strong class="text-white"><?= date('d M Y, H:i') ?></strong>
        </div>
      </div>

      <!-- KPI -->
      <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-primary"><i class="bi bi-file-earmark-text"></i></div>
              <div class="ms-3">
                <div class="stat-number"><?= number_format((int)$stats['total_documents']) ?></div>
                <div class="text-muted">Total Dokumen</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-success"><i class="bi bi-file-earmark-arrow-up"></i></div>
              <div class="ms-3">
                <div class="stat-number"><?= number_format((int)$stats['loa_count']) ?></div>
                <div class="text-muted">LOA</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-warning"><i class="bi bi-patch-check"></i></div>
              <div class="ms-3">
                <div class="stat-number"><?= number_format((int)$stats['sertifikat_count']) ?></div>
                <div class="text-muted">Sertifikat</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-info"><i class="bi bi-clock-history"></i></div>
              <div class="ms-3">
                <div class="stat-number"><?= number_format((int)$stats['recent_uploads']) ?></div>
                <div class="text-muted">Upload Minggu Ini</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- FILTER -->
      <div class="card shadow-sm mb-3">
        <div class="card-body">
          <form method="GET" action="<?= site_url('admin/dokumen') ?>">
            <div class="row g-3 align-items-end">
              <div class="col-md-4">
                <label class="form-label">Filter Event</label>
                <select name="event_id" class="form-select">
                  <option value="">-- Semua Event --</option>
                  <?php foreach ($events as $e): ?>
                    <option value="<?= $e['id'] ?>" <?= (string)$current_event===(string)$e['id']?'selected':''; ?>>
                      <?= esc($e['title']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Filter Tipe</label>
                <select name="tipe" class="form-select">
                  <option value="">-- Semua Tipe --</option>
                  <option value="loa" <?= $current_tipe==='loa'?'selected':'' ?>>LOA</option>
                  <option value="sertifikat" <?= $current_tipe==='sertifikat'?'selected':'' ?>>Sertifikat</option>
                </select>
              </div>
              <div class="col-md-4">
                <button class="btn btn-primary btn-custom me-2" type="submit">
                  <i class="bi bi-funnel me-1"></i>Filter
                </button>
                <a class="btn btn-outline-secondary btn-custom" href="<?= site_url('admin/dokumen') ?>">
                  <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                </a>
              </div>
            </div>
          </form>
        </div>
      </div>

      <!-- ACTIONS -->
      <div class="d-flex flex-wrap gap-2 mb-3">
        <button class="btn btn-success btn-custom" data-bs-toggle="modal" data-bs-target="#uploadLoaModal">
          <i class="bi bi-upload me-1"></i> Upload LOA
        </button>
        <button class="btn btn-warning btn-custom" data-bs-toggle="modal" data-bs-target="#uploadSertifikatModal">
          <i class="bi bi-upload me-1"></i> Upload Sertifikat
        </button>
        <button class="btn btn-info btn-custom" data-bs-toggle="modal" data-bs-target="#bulkLoaModal">
          <i class="bi bi-stars me-1"></i> Generate Bulk LOA
        </button>
        <button class="btn btn-secondary btn-custom" data-bs-toggle="modal" data-bs-target="#bulkSertifikatModal">
          <i class="bi bi-stars me-1"></i> Generate Bulk Sertifikat
        </button>
      </div>

      <!-- TABLE -->
      <div class="card shadow-sm">
        <div class="card-header bg-gradient-primary text-white">
          <div class="d-flex flex-wrap justify-content-between align-items-center">
            <h5 class="mb-2 mb-md-0"><i class="bi bi-list-ul me-2"></i>Daftar Dokumen</h5>
            <span class="badge bg-light text-dark"><?= count($documents) ?> dokumen</span>
          </div>
        </div>

        <div class="card-body">
          <div class="table-responsive">
            <table id="documentsTable" class="table table-hover align-middle">
              <thead>
                <tr>
                  <th>No</th>
                  <th>Tipe</th>
                  <th>User</th>
                  <th>Event</th>
                  <th>File</th>
                  <th>Upload</th>
                  <th style="min-width:160px;">Aksi</th>
                </tr>
              </thead>
              <tbody>
              <?php if (empty($documents)): ?>
                <tr>
                  <td colspan="7" class="text-center py-4 text-muted">
                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                    Belum ada dokumen
                  </td>
                </tr>
              <?php else: $no=1; foreach($documents as $d): 
                $id = (int)($d['id_dokumen'] ?? 0);
                $type = strtolower($d['tipe'] ?? 'loa'); // loa|sertifikat
                $file = $d['file_path'] ?? '';
                $ext  = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                $icon = 'bi-file-earmark';
                $icColor = 'text-secondary';
                if ($ext==='pdf'){ $icon='bi-file-earmark-pdf'; $icColor='text-danger'; }
                elseif (in_array($ext,['doc','docx'])){ $icon='bi-file-earmark-word'; $icColor='text-primary'; }
                elseif (in_array($ext,['jpg','jpeg','png'])){ $icon='bi-file-earmark-image'; $icColor='text-success'; }
                elseif ($ext==='html'){ $icon='bi-file-earmark-code'; $icColor='text-info'; }
              ?>
                <tr>
                  <td><?= $no++ ?></td>
                  <td>
                    <?php if ($type==='loa'): ?>
                      <span class="badge bg-success"><i class="bi bi-file-earmark-arrow-up me-1"></i> LOA</span>
                    <?php else: ?>
                      <span class="badge bg-warning text-dark"><i class="bi bi-patch-check me-1"></i> Sertifikat</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="fw-semibold"><?= esc($d['nama_lengkap'] ?? 'Unknown') ?></div>
                    <small class="text-muted"><?= esc($d['email'] ?? '') ?></small>
                    <?php if (!empty($d['role'])): ?>
                      <div><span class="badge bg-<?= $d['role']==='presenter'?'primary':'secondary' ?>"><?= ucfirst($d['role']) ?></span></div>
                    <?php endif; ?>
                  </td>
                  <td><?= !empty($d['event_title']) ? '<strong>'.esc($d['event_title']).'</strong>' : '<span class="text-muted">-</span>' ?></td>
                  <td>
                    <div class="d-flex align-items-center">
                      <i class="bi <?= $icon ?> fs-5 me-2 <?= $icColor ?>"></i>
                      <div>
                        <div><?= esc(basename($file)) ?></div>
                        <small class="text-muted"><?= strtoupper($ext ?: '-') ?></small>
                      </div>
                    </div>
                  </td>
                  <td><?= isset($d['uploaded_at']) ? date('d/m/Y H:i', strtotime($d['uploaded_at'])) : '-' ?></td>
                  <td>
                    <div class="action-buttons">
                      <a href="<?= site_url('admin/dokumen/download/'.$id) ?>" class="btn-action btn-soft-info" data-bs-toggle="tooltip" data-bs-title="Download">
                        <i class="bi bi-download"></i>
                      </a>
                      <button type="button" class="btn-action btn-soft-danger" data-bs-toggle="tooltip" data-bs-title="Hapus"
                              onclick="deleteDocument(<?= $id ?>)">
                        <i class="bi bi-trash3"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- MODALS -->
      <!-- Upload LOA -->
      <div class="modal fade" id="uploadLoaModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
          <form action="" method="POST" enctype="multipart/form-data" id="loaForm" class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title"><i class="bi bi-upload me-2"></i>Upload LOA</h5>
              <button class="btn-close btn-close-white" type="button" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <?= csrf_field() ?>
              <div class="mb-3">
                <label class="form-label">Event *</label>
                <select class="form-select" name="event_id" id="loaEventId" required>
                  <option value="">-- Pilih Event --</option>
                  <?php foreach ($events as $e): ?>
                    <option value="<?= $e['id'] ?>"><?= esc($e['title']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">User/Presenter *</label>
                <select class="form-select" name="user_id" id="loaUserId" required>
                  <option value="">-- Pilih Event terlebih dahulu --</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">File LOA *</label>
                <input type="file" class="form-control" name="loa_file" accept=".pdf,.doc,.docx" required>
                <div class="form-text">PDF, DOC, DOCX · maks 5MB</div>
              </div>
            </div>
            <div class="modal-footer">
              <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Batal</button>
              <button class="btn btn-success" type="submit"><i class="bi bi-upload me-1"></i>Upload LOA</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Upload Sertifikat -->
      <div class="modal fade" id="uploadSertifikatModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
          <form action="" method="POST" enctype="multipart/form-data" id="sertifikatForm" class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title"><i class="bi bi-upload me-2"></i>Upload Sertifikat</h5>
              <button class="btn-close btn-close-white" type="button" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <?= csrf_field() ?>
              <div class="mb-3">
                <label class="form-label">Event *</label>
                <select class="form-select" name="event_id" id="sertifikatEventId" required>
                  <option value="">-- Pilih Event --</option>
                  <?php foreach ($events as $e): ?>
                    <option value="<?= $e['id'] ?>"><?= esc($e['title']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">User/Peserta *</label>
                <select class="form-select" name="user_id" id="sertifikatUserId" required>
                  <option value="">-- Pilih Event terlebih dahulu --</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">File Sertifikat *</label>
                <input type="file" class="form-control" name="sertifikat_file" accept=".pdf,.jpg,.jpeg,.png" required>
                <div class="form-text">PDF / JPG / PNG · maks 5MB</div>
              </div>
            </div>
            <div class="modal-footer">
              <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Batal</button>
              <button class="btn btn-warning" type="submit"><i class="bi bi-upload me-1"></i>Upload Sertifikat</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Bulk LOA -->
      <div class="modal fade" id="bulkLoaModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
          <form action="<?= site_url('admin/dokumen/generateBulkLOA') ?>" method="POST" id="bulkLoaForm" class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title"><i class="bi bi-stars me-2"></i>Generate Bulk LOA</h5>
              <button class="btn-close btn-close-white" type="button" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <?= csrf_field() ?>
              <div class="mb-3">
                <label class="form-label">Event *</label>
                <select class="form-select" name="event_id" required>
                  <option value="">-- Pilih Event --</option>
                  <?php foreach ($events as $e): ?>
                    <option value="<?= $e['id'] ?>"><?= esc($e['title']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="alert alert-info mb-0"><i class="bi bi-info-circle me-1"></i>
                LOA digenerate untuk presenter dengan pembayaran <strong>terverifikasi</strong>.
              </div>
            </div>
            <div class="modal-footer">
              <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Batal</button>
              <button class="btn btn-info" type="submit"><i class="bi bi-stars me-1"></i>Generate LOA</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Bulk Sertifikat -->
      <div class="modal fade" id="bulkSertifikatModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
          <form action="<?= site_url('admin/dokumen/generateBulkSertifikat') ?>" method="POST" id="bulkSertifikatForm" class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title"><i class="bi bi-stars me-2"></i>Generate Bulk Sertifikat</h5>
              <button class="btn-close btn-close-white" type="button" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <?= csrf_field() ?>
              <div class="mb-3">
                <label class="form-label">Event *</label>
                <select class="form-select" name="event_id" required>
                  <option value="">-- Pilih Event --</option>
                  <?php foreach ($events as $e): ?>
                    <option value="<?= $e['id'] ?>"><?= esc($e['title']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="alert alert-warning mb-0"><i class="bi bi-exclamation-triangle me-1"></i>
                Sertifikat dibuat untuk peserta yang <strong>tercatat hadir</strong>.
              </div>
            </div>
            <div class="modal-footer">
              <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Batal</button>
              <button class="btn btn-secondary" type="submit"><i class="bi bi-stars me-1"></i>Generate Sertifikat</button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<!-- ====== STYLES (seragam dengan voucher) ====== -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.13.4/css/dataTables.bootstrap5.min.css">
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

  .bg-gradient-primary{ background: linear-gradient(135deg, var(--primary-color), var(--info-color)); }

  .btn-custom{ border-radius:10px; padding:.55rem .9rem; font-weight:600; transition:.2s; }
  .btn-custom:hover{ transform:translateY(-1px); box-shadow:0 6px 14px rgba(15,23,42,.12); }

  .action-buttons{ display:flex; flex-wrap:wrap; align-items:center; gap:.5rem; }
  .btn-action{
    display:inline-flex; align-items:center; justify-content:center;
    padding:.45rem .6rem; border-radius:10px; border:1px solid #e8eef5; background:#fff; color:#334155;
    box-shadow:0 2px 6px rgba(15,23,42,.04); transition:.18s ease; font-weight:600;
  }
  .btn-action:hover{ transform:translateY(-1px); box-shadow:0 8px 18px rgba(15,23,42,.10); }
  .btn-soft-info{     background:rgba(6,182,212,.12);   color:#0e7490;  border-color:rgba(6,182,212,.25); }
  .btn-soft-danger{   background:rgba(239,68,68,.12);   color:#991b1b;  border-color:rgba(239,68,68,.25); }

  #documentsTable thead th{ background:#f8fafc; white-space:nowrap; }
</style>

<!-- ====== SCRIPTS ====== -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
  // ===== Init DataTable + Tooltips =====
  function initTooltips(scope=document){
    return [].slice.call(scope.querySelectorAll('[data-bs-toggle="tooltip"]'))
      .map(el => new bootstrap.Tooltip(el));
  }

  $(function(){
    const dt = $('#documentsTable').DataTable({
      language:{ url:'https://cdn.datatables.net/plug-ins/1.13.4/i18n/id.json' },
      order:[[5,'desc']], pageLength:25, responsive:true,
      columnDefs:[{ orderable:false, targets:[6] }]
    });
    initTooltips(document);
    dt.on('draw', ()=>initTooltips(document.getElementById('content')));
  });

  // ===== Dependent selects & form action =====
  $('#loaEventId').on('change', function(){
    const id=$(this).val(), $sel=$('#loaUserId');
    $sel.html('<option value="">Loading...</option>').prop('disabled', true);
    if(id){
      $.get('<?= site_url('admin/dokumen/getVerifiedPresenters/') ?>'+id)
        .done(res=>{
          $sel.prop('disabled', false);
          if(res.status==='success' && (res.data||[]).length){
            $sel.html('<option value="">-- Pilih Presenter --</option>');
            res.data.forEach(u=> $sel.append(`<option value="${u.id_user}">${u.nama_lengkap} (${u.email})</option>`));
          }else{
            $sel.html('<option value="">Tidak ada presenter yang memenuhi syarat</option>');
          }
        }).fail(()=>{ $sel.prop('disabled', false).html('<option value="">Gagal memuat</option>'); });
      $('#loaForm').attr('action','<?= site_url('admin/dokumen/uploadLoa/') ?>'+id);
    }else{
      $sel.prop('disabled', false).html('<option value="">-- Pilih Event terlebih dahulu --</option>');
      $('#loaForm').attr('action','');
    }
  });

  $('#sertifikatEventId').on('change', function(){
    const id=$(this).val(), $sel=$('#sertifikatUserId');
    $sel.html('<option value="">Loading...</option>').prop('disabled', true);
    if(id){
      $.get('<?= site_url('admin/dokumen/getAttendees/') ?>'+id)
        .done(res=>{
          $sel.prop('disabled', false);
          if(res.status==='success' && (res.data||[]).length){
            $sel.html('<option value="">-- Pilih Peserta --</option>');
            res.data.forEach(u=>{
              const role = u.role ? ` - ${u.role}` : '';
              $sel.append(`<option value="${u.id_user}">${u.nama_lengkap} (${u.email})${role}</option>`);
            });
          }else{
            $sel.html('<option value="">Tidak ada peserta yang memenuhi syarat</option>');
          }
        }).fail(()=>{ $sel.prop('disabled', false).html('<option value="">Gagal memuat</option>'); });
      $('#sertifikatForm').attr('action','<?= site_url('admin/dokumen/uploadSertifikat/') ?>'+id);
    }else{
      $sel.prop('disabled', false).html('<option value="">-- Pilih Event terlebih dahulu --</option>');
      $('#sertifikatForm').attr('action','');
    }
  });

  // ===== Delete =====
  function deleteDocument(id){
    Swal.fire({
      title:'Hapus Dokumen?', text:'File akan dihapus permanen.',
      icon:'warning', showCancelButton:true,
      confirmButtonColor:'#d33', cancelButtonColor:'#6b7280',
      confirmButtonText:'Ya, Hapus', cancelButtonText:'Batal'
    }).then(r=>{
      if(r.isConfirmed){
        const form=document.createElement('form'); form.method='POST'; form.action='<?= site_url('admin/dokumen/delete/') ?>'+id;
        <?php if (function_exists('csrf_token')): ?>
          const i=document.createElement('input'); i.type='hidden'; i.name='<?= csrf_token() ?>'; i.value='<?= csrf_hash() ?>'; form.appendChild(i);
        <?php endif; ?>
        document.body.appendChild(form); form.submit();
      }
    });
  }

  // ===== Flash SweetAlert =====
  <?php if (session('success')): ?>
    Swal.fire({ icon:'success', title:'Berhasil!', text:'<?= esc(session('success')) ?>', timer:2600, showConfirmButton:false });
  <?php endif; ?>
  <?php if (session('error')): ?>
    Swal.fire({ icon:'error', title:'Error!', text:'<?= esc(session('error')) ?>' });
  <?php endif; ?>
</script>
