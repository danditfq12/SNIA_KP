<?php
// ====== DEFAULT VARS ======
$title         = $title ?? 'Manajemen Dokumen';
$stats         = $stats ?? [
    'total_documents'  => 0,
    'loa_count'        => 0,
    'sertifikat_count' => 0,
    'lainnya_count'    => 0,
    'recent_uploads'   => 0
];
$events        = $events ?? [];
$documents     = $documents ?? [];
$current_event = $current_event ?? '';
$current_tipe  = $current_tipe ?? '';

// Pastikan semua key stats ada dengan default value
$stats['total_documents']  = $stats['total_documents'] ?? 0;
$stats['loa_count']        = $stats['loa_count'] ?? 0;
$stats['sertifikat_count'] = $stats['sertifikat_count'] ?? 0;
$stats['lainnya_count']    = $stats['lainnya_count'] ?? 0;
$stats['recent_uploads']   = $stats['recent_uploads'] ?? 0;
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_admin') ?>
<?= $this->include('partials/alerts') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/dokumen_admin.css'); ?>">

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- HEADER -->
      <div class="header-section header-blue d-flex justify-content-between align-items-center mb-3"> 
        <div>
          <h3 class="welcome-text mb-1">
            <i class="bi bi-folder2-open me-2"></i><?= esc($title) ?>
          </h3>
          <div class="text-white-50">Kelola LOA, Sertifikat & Dokumen Event</div>
        </div>
        <div class="text-end d-none d-md-block">
          <small class="text-white-50 d-block">Terakhir update</small>
          <strong class="text-white"><?= date('d M Y, H:i') ?></strong>
        </div>
      </div>

      <!-- KPI -->
      <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
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
        <div class="col-6 col-lg-3">
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
        <div class="col-6 col-lg-3">
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
        <div class="col-6 col-lg-3">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-info"><i class="bi bi-folder-symlink"></i></div>
              <div class="ms-3">
                <div class="stat-number"><?= number_format((int)$stats['lainnya_count']) ?></div>
                <div class="text-muted">Dokumen Lainnya</div>
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
                      <?= esc($e['title'] ?? 'No Title') ?>
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
                  <option value="lainnya" <?= $current_tipe==='lainnya'?'selected':'' ?>>Dokumen Lainnya</option>
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
        <button class="btn btn-info btn-custom" data-bs-toggle="modal" data-bs-target="#uploadDokumenLainModal">
          <i class="bi bi-upload me-1"></i> Upload Dokumen Lainnya
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
          <?php if (empty($documents)): ?>
            <div class="text-center py-5">
              <i class="bi bi-inbox fs-1 text-muted mb-3"></i>
              <h5 class="text-muted">Belum ada dokumen</h5>
              <p class="text-muted">Upload dokumen LOA, sertifikat, atau dokumen lainnya.</p>
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table id="documentsTable" class="table table-hover align-middle">
                <thead>
                  <tr>
                    <th width="5%">No</th>
                    <th width="10%">Tipe</th>
                    <th width="25%">Info</th>
                    <th width="20%">Event</th>
                    <th width="20%">File</th>
                    <th width="10%">Upload</th>
                    <th width="10%">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                <?php $no=1; foreach($documents as $d):
                    $id=(int)($d['id_dokumen']??0); $type=strtolower($d['tipe']??'loa'); $file=$d['file_path']??'';
                    $nama=$d['nama_lengkap']??''; $email=$d['email']??''; $role=$d['role']??'';
                    $eventTitle=$d['event_title']??''; $uploadedAt=$d['uploaded_at']??'';
                    $syarat=$d['syarat']??''; $keterangan=$d['keterangan']??'';
                    $ext=strtolower(pathinfo($file,PATHINFO_EXTENSION));
                    
                    $icon='bi-file-earmark'; $icColor='text-secondary';
                    if($ext==='pdf'){ $icon='bi-file-earmark-pdf'; $icColor='text-danger'; }
                    elseif(in_array($ext,['doc','docx'])){ $icon='bi-file-earmark-word'; $icColor='text-primary'; }
                    elseif(in_array($ext,['ppt','pptx'])){ $icon='bi-file-earmark-easel'; $icColor='text-warning'; }
                    elseif(in_array($ext,['xls','xlsx'])){ $icon='bi-file-earmark-excel'; $icColor='text-success'; }
                    elseif(in_array($ext,['jpg','jpeg','png'])){ $icon='bi-file-earmark-image'; $icColor='text-info'; }
                    elseif(in_array($ext,['zip','rar'])){ $icon='bi-file-earmark-zip'; $icColor='text-secondary'; }
                ?>
                  <tr>
                    <td><?= $no++ ?></td>
                    <td>
                      <?php if($type==='loa'): ?>
                        <span class="badge bg-success"><i class="bi bi-file-earmark-arrow-up me-1"></i> LOA</span>
                      <?php elseif($type==='sertifikat'): ?>
                        <span class="badge bg-warning text-dark"><i class="bi bi-patch-check me-1"></i> Sertifikat</span>
                      <?php else: ?>
                        <span class="badge bg-info"><i class="bi bi-folder-symlink me-1"></i> Lainnya</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($type === 'lainnya'): ?>
                        <div class="fw-semibold"><?= esc($syarat ?: 'Dokumen') ?></div>
                        <div><span class="badge bg-secondary-subtle text-secondary">Dokumen Event</span></div>
                      <?php else: ?>
                        <div class="fw-semibold"><?= esc($nama ?: 'Unknown') ?></div>
                        <?php if ($email): ?><small class="text-muted"><?= esc($email) ?></small><?php endif; ?>
                        <?php if ($role): ?><div><span class="badge bg-<?= $role==='presenter'?'primary':'secondary' ?>"><?= ucfirst($role) ?></span></div><?php endif; ?>
                      <?php endif; ?>
                    </td>
                    <td><?= $eventTitle ? '<strong>'.esc($eventTitle).'</strong>' : '<span class="text-muted">-</span>' ?></td>
                    <td>
                      <div class="d-flex align-items-center">
                        <i class="bi <?= $icon ?> fs-5 me-2 <?= $icColor ?>"></i>
                        <div>
                          <div class="text-truncate" style="max-width:180px;" title="<?= esc(basename($file)) ?>"><?= esc(basename($file)) ?></div>
                          <small class="text-muted"><?= strtoupper($ext ?: '-') ?></small>
                        </div>
                      </div>
                    </td>
                    <td><?= $uploadedAt ? date('d/m/Y H:i', strtotime($uploadedAt)) : '-' ?></td>
                    <td>
                      <div class="action-buttons">
                        <a href="<?= site_url('admin/dokumen/download/'.$id) ?>" class="btn-action btn-soft-info" data-bs-toggle="tooltip" data-bs-title="Download">
                          <i class="bi bi-download"></i>
                        </a>
                        <button type="button" class="btn-action btn-soft-danger" data-bs-toggle="tooltip" data-bs-title="Hapus" onclick="deleteDocument(<?= $id ?>)">
                          <i class="bi bi-trash3"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<!-- ====== SCRIPTS ====== -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

<script>
  // ===== HELPER FUNCTIONS =====
  function escapeHtml(str){ 
    return (str||'').replace(/[&<>"']/g, s=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;', "'":'&#039;' }[s])); 
  }

  function initTooltips(scope=document){
    const tooltipTriggerList = scope.querySelectorAll('[data-bs-toggle="tooltip"]');
    return Array.from(tooltipTriggerList).map(el => new bootstrap.Tooltip(el));
  }

  function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
  }

  function getFileIcon(filename) {
    const ext = filename.split('.').pop().toLowerCase();
    const iconMap = {
      'pdf': { icon: 'bi-file-earmark-pdf', color: 'text-danger' },
      'doc': { icon: 'bi-file-earmark-word', color: 'text-primary' },
      'docx': { icon: 'bi-file-earmark-word', color: 'text-primary' },
      'ppt': { icon: 'bi-file-earmark-easel', color: 'text-warning' },
      'pptx': { icon: 'bi-file-earmark-easel', color: 'text-warning' },
      'xls': { icon: 'bi-file-earmark-excel', color: 'text-success' },
      'xlsx': { icon: 'bi-file-earmark-excel', color: 'text-success' },
      'jpg': { icon: 'bi-file-earmark-image', color: 'text-info' },
      'jpeg': { icon: 'bi-file-earmark-image', color: 'text-info' },
      'png': { icon: 'bi-file-earmark-image', color: 'text-info' },
      'zip': { icon: 'bi-file-earmark-zip', color: 'text-secondary' },
      'rar': { icon: 'bi-file-earmark-zip', color: 'text-secondary' }
    };
    return iconMap[ext] || { icon: 'bi-file-earmark', color: 'text-secondary' };
  }

  // ===== DELETE FUNCTION =====
  function deleteDocument(id){
    Swal.fire({
      title:'Hapus Dokumen?', 
      text:'File akan dihapus permanen dari server dan database.',
      icon:'warning', 
      showCancelButton:true, 
      confirmButtonColor:'#d33', 
      cancelButtonColor:'#6b7280',
      confirmButtonText:'Ya, Hapus', 
      cancelButtonText:'Batal'
    }).then(r => {
      if(r.isConfirmed){
        const f=document.createElement('form'); 
        f.method='POST'; 
        f.action='<?= site_url('admin/dokumen/delete/') ?>'+encodeURIComponent(id);
        const csrfName='<?= csrf_token() ?>', csrfVal='<?= csrf_hash() ?>';
        const i=document.createElement('input'); 
        i.type='hidden'; 
        i.name=csrfName; 
        i.value=csrfVal; 
        f.appendChild(i);
        document.body.appendChild(f); 
        f.submit();
      }
    });
  }

  $(function(){
    const $table = $('#documentsTable');
    const hasDocuments = <?= !empty($documents) ? 'true' : 'false' ?>;
    if (hasDocuments && $table.length && typeof $.fn.DataTable !== 'undefined') {
      if ($.fn.DataTable.isDataTable('#documentsTable')) $('#documentsTable').DataTable().destroy();
      $('#documentsTable').DataTable({
        paging:true,lengthChange:true,searching:true,ordering:true,info:true,autoWidth:false,responsive:true,
        pageLength:25,lengthMenu:[[10,25,50,-1],[10,25,50,'Semua']],order:[[5,'desc']],
        columnDefs:[{orderable:false,targets:[6]},{searchable:false,targets:[0]}],
        language:{search:'Cari:',lengthMenu:'Tampilkan _MENU_ data per halaman',zeroRecords:'Tidak ada data ditemukan',info:'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',infoEmpty:'Menampilkan 0 sampai 0 dari 0 data',infoFiltered:'(difilter dari _MAX_ total data)',paginate:{first:'Pertama',last:'Terakhir',next:'Selanjutnya',previous:'Sebelumnya'}},
        drawCallback:function(){ initTooltips(); }
      });
    }
    initTooltips();
  });

  // Flash messages
  <?php if ($msg = session()->getFlashdata('success')): ?>
    Swal.fire({ icon:'success', title:'Berhasil!', text:'<?= esc($msg) ?>', timer:3000, showConfirmButton:false });
  <?php endif; ?>
  <?php if ($msg = session()->getFlashdata('error')): ?>
    Swal.fire({ icon:'error', title:'Error!', text:'<?= esc($msg) ?>' });
  <?php endif; ?>
  <?php if ($errors = session()->getFlashdata('errors')): ?>
    Swal.fire({ icon:'error', title:'Validasi gagal', html:'<ul style="text-align:left; margin:0; padding-left:18px;"><?php foreach((array)$errors as $e){ echo "<li>".esc($e)."</li>"; } ?></ul>' });
  <?php endif; ?>
</script>

<!-- ================= MODALS ================= -->

<!-- Upload LOA -->
<div class="modal fade" id="uploadLoaModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form action="<?= site_url('admin/dokumen/uploadLoa') ?>" method="POST" enctype="multipart/form-data" id="loaForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-upload me-2"></i>Upload LOA</h5>
        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <?= csrf_field() ?>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Event *</label>
            <select class="form-select" name="event_id" id="loaEventId" required>
              <option value="">-- Pilih Event --</option>
              <?php foreach ($events as $e): ?>
                <option value="<?= (int)$e['id'] ?>"><?= esc($e['title'] ?? 'No Title') ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">File LOA *</label>
            <input type="file" class="form-control" name="loa_file" accept=".pdf,.doc,.docx" required>
            <div class="form-text">PDF, DOC, DOCX · maks 5MB</div>
          </div>
        </div>

        <hr class="my-3">

        <input type="hidden" name="user_id" id="loaUserIdHidden" required>
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h6 class="mb-0"><i class="bi bi-people me-2"></i>Pilih User pada event</h6>
          <span id="loaCountBadge" class="badge bg-light text-dark d-none">0 ditemukan</span>
        </div>
        <div id="loaUserListWrap" class="border rounded p-2" style="max-height:330px; overflow:auto;">
          <div class="text-muted small">Pilih event terlebih dahulu.</div>
        </div>
        <div class="form-text mt-1">User yang sudah punya LOA tetap ditampilkan dengan label <em>"Sudah dapat LOA"</em>. Mengirim ulang akan ditolak oleh sistem.</div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-success" type="submit" id="loaSubmitBtn"><i class="bi bi-upload me-1"></i><span>Upload LOA</span></button>
      </div>
    </form>
  </div>
</div>

<!-- Upload Sertifikat -->
<div class="modal fade" id="uploadSertifikatModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form action="<?= site_url('admin/dokumen/uploadSertifikat') ?>" method="POST" enctype="multipart/form-data" id="sertifikatForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-upload me-2"></i>Upload Sertifikat</h5>
        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <?= csrf_field() ?>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Event *</label>
            <select class="form-select" name="event_id" id="sertifikatEventId" required>
              <option value="">-- Pilih Event --</option>
              <?php foreach ($events as $e): ?>
                <option value="<?= (int)$e['id'] ?>"><?= esc($e['title'] ?? 'No Title') ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">File Sertifikat *</label>
            <input type="file" class="form-control" name="sertifikat_file" accept=".pdf,.jpg,.jpeg,.png" required>
            <div class="form-text">PDF / JPG / PNG · maks 5MB</div>
          </div>
        </div>

        <hr class="my-3">

        <input type="hidden" name="user_id" id="sertifikatUserIdHidden" required>
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h6 class="mb-0"><i class="bi bi-people me-2"></i>Pilih User pada event</h6>
          <span id="sertifikatCountBadge" class="badge bg-light text-dark d-none">0 ditemukan</span>
        </div>
        <div id="sertifikatUserListWrap" class="border rounded p-2" style="max-height:330px; overflow:auto;">
          <div class="text-muted small">Pilih event terlebih dahulu.</div>
        </div>
        <div class="form-text mt-1">
          Menampilkan semua pendaftar event. Yang <strong>belum absen</strong> akan diberi label <em>"Belum Absen"</em>.
          Jika sudah punya sertifikat akan diberi label <em>"Sudah ada Sertifikat"</em>. Mengirim ulang akan ditolak oleh sistem.
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-warning" type="submit" id="sertifikatSubmitBtn"><i class="bi bi-upload me-1"></i><span>Upload Sertifikat</span></button>
      </div>
    </form>
  </div>
</div>

<!-- Upload Dokumen Lainnya - FIXED VERSION -->
<div class="modal fade" id="uploadDokumenLainModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form action="<?= site_url('admin/dokumen/uploadDokumenLainnya') ?>" method="POST" enctype="multipart/form-data" id="dokumenLainForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-upload me-2"></i>Upload Dokumen Lainnya</h5>
        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <?= csrf_field() ?>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Event *</label>
            <select class="form-select" name="event_id" id="dokumenLainEventId" required>
              <option value="">-- Pilih Event --</option>
              <?php foreach ($events as $e): ?>
                <option value="<?= (int)$e['id'] ?>"><?= esc($e['title'] ?? 'No Title') ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">File Dokumen *</label>
            <input type="file" 
                   class="form-control d-none" 
                   name="document_file[]" 
                   id="dokumenFileInput"
                   accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip,.rar,.jpg,.jpeg,.png" 
                   multiple>
            <label for="dokumenFileInput" class="file-input-label">
              <i class="bi bi-plus-circle"></i> Pilih File
            </label>
            <div class="form-text">PDF, Office, ZIP, Image · maks 10MB per file · bisa pilih multiple files</div>
            
            <!-- File Preview Container -->
            <div id="filePreviewContainer" class="file-preview-container d-none"></div>
          </div>
        </div>

        <hr class="my-3">

        <!-- MODE SELECTION -->
        <div class="mb-3">
          <label class="form-label fw-semibold">Mode Upload *</label>
          <div class="btn-group w-100" role="group">
            <input type="radio" class="btn-check" name="upload_mode" id="modeSingle" value="single" checked>
            <label class="btn btn-outline-primary" for="modeSingle">
              <i class="bi bi-person me-1"></i> Upload ke 1 User
            </label>
            
            <input type="radio" class="btn-check" name="upload_mode" id="modeBulk" value="bulk">
            <label class="btn btn-outline-success" for="modeBulk">
              <i class="bi bi-people-fill me-1"></i> Upload ke Semua User
            </label>
          </div>
          <div class="form-text mt-2">
            <strong>Single:</strong> Pilih 1 user dari daftar<br>
            <strong>Bulk:</strong> Kirim dokumen ke semua user terdaftar (yang belum punya dokumen ini)
          </div>
        </div>

        <!-- SINGLE USER SELECTION -->
        <div id="singleUserSection">
          <input type="hidden" name="user_id" id="dokumenLainUserIdHidden">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0"><i class="bi bi-person-check me-2"></i>Pilih User</h6>
            <span id="dokumenLainCountBadge" class="badge bg-light text-dark d-none">0 ditemukan</span>
          </div>
          <div id="dokumenLainUserListWrap" class="border rounded p-2" style="max-height:330px; overflow:auto;">
            <div class="text-muted small">Pilih event terlebih dahulu.</div>
          </div>
          <div class="form-text mt-1">User bisa menerima multiple dokumen. Jumlah dokumen yang sudah dimiliki ditampilkan pada badge.</div>
        </div>

        <!-- BULK CONFIRMATION -->
        <div id="bulkConfirmSection" style="display:none;">
          <div class="alert alert-info d-flex align-items-start">
            <i class="bi bi-info-circle fs-5 me-2"></i>
            <div>
              <strong>Mode Bulk Upload</strong><br>
              Dokumen akan dikirim ke <strong id="bulkUserCount">semua</strong> user yang terdaftar pada event ini.<br>
              <small class="text-muted">User yang sudah memiliki dokumen yang sama akan dilewati otomatis.</small>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-info" type="submit" id="dokumenLainSubmitBtn">
          <i class="bi bi-upload me-1"></i>
          <span id="dokumenLainBtnText">Upload Dokumen</span>
        </button>
      </div>
    </form>
  </div>
</div>

<script>
  // ====== FILE MANAGEMENT FOR DOKUMEN LAINNYA ======
  let selectedFiles = [];
  const fileInput = document.getElementById('dokumenFileInput');
  const previewContainer = document.getElementById('filePreviewContainer');

  // Handle file selection
  fileInput.addEventListener('change', function(e) {
    const newFiles = Array.from(e.target.files);
    
    // Validate and add new files
    newFiles.forEach(file => {
      // Check if file already exists
      const exists = selectedFiles.some(f => f.name === file.name && f.size === file.size);
      if (!exists) {
        selectedFiles.push(file);
      }
    });
    
    // Update preview
    updateFilePreview();
    
    // Reset input so same file can be selected again if removed
    fileInput.value = '';
  });

  function updateFilePreview() {
    if (selectedFiles.length === 0) {
      previewContainer.classList.add('d-none');
      previewContainer.innerHTML = '';
      return;
    }

    previewContainer.classList.remove('d-none');
    previewContainer.innerHTML = '';

    selectedFiles.forEach((file, index) => {
      const fileItem = document.createElement('div');
      fileItem.className = 'file-preview-item';
      
      const iconInfo = getFileIcon(file.name);
      
      fileItem.innerHTML = `
        <div class="file-info">
          <i class="bi ${iconInfo.icon} ${iconInfo.color} file-icon"></i>
          <div class="file-details">
            <div class="file-name" title="${escapeHtml(file.name)}">${escapeHtml(file.name)}</div>
            <div class="file-size">${formatFileSize(file.size)}</div>
          </div>
        </div>
        <button type="button" class="file-remove-btn" onclick="removeFile(${index})" title="Hapus file">
          <i class="bi bi-x-circle"></i>
        </button>
      `;
      
      previewContainer.appendChild(fileItem);
    });
  }

  function removeFile(index) {
    selectedFiles.splice(index, 1);
    updateFilePreview();
  }

  // Update form submission to use selectedFiles
  $('#dokumenLainForm').on('submit', function(e) {
    e.preventDefault();
    
    const eventId = $('#dokumenLainEventId').val();
    const uploadMode = $('input[name="upload_mode"]:checked').val();
    const userId = $('#dokumenLainUserIdHidden').val();

    // Validation
    if (!eventId) {
      Swal.fire('Error', 'Pilih event terlebih dahulu.', 'error');
      return false;
    }

    if (uploadMode === 'single' && !userId) {
      Swal.fire('Error', 'Pilih user pada daftar.', 'error');
      return false;
    }

    if (selectedFiles.length === 0) {
      Swal.fire('Error', 'Pilih minimal 1 file dokumen.', 'error');
      return false;
    }

    // Validate files
    const validExts = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'zip', 'rar', 'jpg', 'jpeg', 'png'];
    let totalSize = 0;

    for (let file of selectedFiles) {
      const ext = file.name.split('.').pop().toLowerCase();

      if (file.size > 10485760) {
        Swal.fire('Error', `File "${file.name}" melebihi 10MB.`, 'error');
        return false;
      }

      if (!validExts.includes(ext)) {
        Swal.fire('Error', `Format file "${file.name}" tidak didukung.`, 'error');
        return false;
      }

      totalSize += file.size;
    }

    if (totalSize > 52428800) {
      Swal.fire('Error', 'Total ukuran file tidak boleh lebih dari 50MB.', 'error');
      return false;
    }

    // Create FormData and add selected files
    const formData = new FormData(this);
    
    // Remove old file input data
    formData.delete('document_file[]');
    
    // Add selected files
    selectedFiles.forEach(file => {
      formData.append('document_file[]', file);
    });

    // Disable submit button
    const btn = $('#dokumenLainSubmitBtn');
    btn.prop('disabled', true).find('span').text(`Mengupload ${selectedFiles.length} file...`);

    // Submit via AJAX
    $.ajax({
      url: $(this).attr('action'),
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function(response) {
        // Redirect to refresh page with success message
        window.location.href = '<?= site_url('admin/dokumen') ?>?success=1';
      },
      error: function(xhr) {
        btn.prop('disabled', false).find('span').text('Upload Dokumen');
        Swal.fire('Error', 'Gagal mengupload dokumen. Silakan coba lagi.', 'error');
      }
    });

    return false;
  });

  // Reset form when modal is closed
  $('#uploadDokumenLainModal').on('hidden.bs.modal', function() {
    selectedFiles = [];
    updateFilePreview();
    $('#dokumenLainForm')[0].reset();
    $('#dokumenLainEventId').val('').trigger('change');
    $('#dokumenLainUserIdHidden').val('');
    $('#dokumenLainUserListWrap').html('<div class="text-muted small">Pilih event terlebih dahulu.</div>');
    $('#dokumenLainCountBadge').addClass('d-none').text('0 ditemukan');
    $('input[name="upload_mode"][value="single"]').prop('checked', true).trigger('change');
  });

  // ====== MODE TOGGLE ======
  $('input[name="upload_mode"]').on('change', function() {
    const mode = $(this).val();
    if (mode === 'single') {
      $('#singleUserSection').show();
      $('#bulkConfirmSection').hide();
      $('#dokumenLainUserIdHidden').prop('required', true);
      $('#dokumenLainBtnText').text('Upload Dokumen');
    } else {
      $('#singleUserSection').hide();
      $('#bulkConfirmSection').show();
      $('#dokumenLainUserIdHidden').prop('required', false).val('');
      $('#dokumenLainBtnText').text('Upload ke Semua User');
      
      // Update bulk user count
      const eventId = $('#dokumenLainEventId').val();
      if (eventId) {
        updateBulkUserCount(eventId);
      }
    }
  });

  function updateBulkUserCount(eventId) {
    $.get('<?= site_url('admin/dokumen/users-for-dokumen-lain/') ?>' + encodeURIComponent(eventId))
      .done(res => {
        if (res && res.status === 'success') {
          const count = (res.data || []).length;
          $('#bulkUserCount').text(count + ' user');
        }
      });
  }

  // ====== LOA PICK LIST ======
  const $loaEvent  = $('#loaEventId');
  const $loaWrap   = $('#loaUserListWrap');
  const $loaHidden = $('#loaUserIdHidden');
  const $loaCount  = $('#loaCountBadge');

  function renderLoaUsers(items){
    $loaWrap.empty();
    $loaHidden.val('');
    
    if(!items || !items.length){
      $loaWrap.html('<div class="text-muted small">Tidak ada user pada event ini.</div>');
      $loaCount.addClass('d-none').text('0 ditemukan');
      return;
    }
    
    $loaCount.removeClass('d-none').text(items.length+' ditemukan');

    items.forEach(u=>{
      const hasLoa = !!u.has_loa;
      const badgeLoa = hasLoa
        ? '<span class="badge bg-success-subtle text-success border border-success-subtle">Sudah dapat LOA</span>'
        : '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Belum dapat LOA</span>';
      const role = u.role ? `<span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1">${escapeHtml(u.role)}</span>` : '';

      const disabled = hasLoa ? 'disabled' : '';
      const $row = $(`
        <div class="picklist-item d-flex justify-content-between align-items-start mb-2 ${disabled}" data-id="${u.id_user}">
          <div>
            <div class="fw-semibold">${escapeHtml(u.nama_lengkap||'-')}</div>
            <div class="small text-muted">${escapeHtml(u.email||'')}</div>
          </div>
          <div class="text-end">
            ${badgeLoa} ${role}
          </div>
        </div>
      `);

      if (!hasLoa) {
        $row.on('click', function(){
          $('.picklist-item', $loaWrap).removeClass('active');
          $(this).addClass('active');
          $loaHidden.val($(this).data('id'));
        });
      }

      $loaWrap.append($row);
    });
  }

  $loaEvent.on('change', function(){
    const id = $(this).val();
    $loaWrap.html('<div class="text-muted small">Memuat user...</div>');
    $loaHidden.val('');
    $loaCount.addClass('d-none').text('0 ditemukan');
    
    if(!id){ 
      $loaWrap.html('<div class="text-muted small">Pilih event terlebih dahulu.</div>'); 
      return; 
    }
    
    $.get('<?= site_url('admin/dokumen/users-for-loa/') ?>'+encodeURIComponent(id))
      .done(res => {
        if(res && res.status==='success'){ 
          renderLoaUsers(res.data||[]); 
        } else { 
          $loaWrap.html('<div class="text-danger small">Gagal memuat user: ' + (res.message || 'Unknown error') + '</div>'); 
        }
      })
      .fail((xhr, status, error) => {
        console.error('LOA users fetch error:', error, xhr.responseText);
        $loaWrap.html('<div class="text-danger small">Gagal memuat user. Cek console untuk detail.</div>');
      });
  });

  // ====== SERTIFIKAT PICK LIST ======
  const $sertEvent   = $('#sertifikatEventId');
  const $sertWrap    = $('#sertifikatUserListWrap');
  const $sertHidden  = $('#sertifikatUserIdHidden');
  const $sertCount   = $('#sertifikatCountBadge');

  function renderCertificateUsers(items){
    $sertWrap.empty();
    $sertHidden.val('');
    
    if(!items || !items.length){
      $sertWrap.html('<div class="text-muted small">Belum ada pendaftar pada event ini.</div>');
      $sertCount.addClass('d-none').text('0 ditemukan');
      return;
    }
    
    $sertCount.removeClass('d-none').text(items.length+' ditemukan');

    items.forEach(u=>{
      const attended = !!u.attended;
      const hasCert  = !!(u.has_cert ?? u.has_certificate);
      const role     = (u.role||'').toString();

      const badgeAttend = attended
        ? '<span class="badge bg-success-subtle text-success border border-success-subtle">Hadir</span>'
        : '<span class="badge bg-warning-subtle text-warning border border-warning-subtle">Belum Absen</span>';

      const badgeCert = hasCert
        ? '<span class="badge bg-info-subtle text-info border border-info-subtle ms-1">Sudah ada Sertifikat</span>'
        : '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle ms-1">Belum ada Sertifikat</span>';

      const badgeRole = role
        ? `<span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1">${escapeHtml(role)}</span>`
        : '';

      const disabled = hasCert ? 'disabled' : '';
      const $row = $(`
        <div class="picklist-item d-flex justify-content-between align-items-start mb-2 ${disabled}" data-id="${u.id_user}">
          <div>
            <div class="fw-semibold">${escapeHtml(u.nama_lengkap||'-')}</div>
            <div class="small text-muted">${escapeHtml(u.email||'')}</div>
          </div>
          <div class="text-end">
            ${badgeAttend} ${badgeCert} ${badgeRole}
          </div>
        </div>
      `);

      if (!hasCert) {
        $row.on('click', function(){
          $('.picklist-item', $sertWrap).removeClass('active');
          $(this).addClass('active');
          $sertHidden.val($(this).data('id'));
        });
      }

      $sertWrap.append($row);
    });
  }

  $sertEvent.on('change', function(){
    const id = $(this).val();
    $sertWrap.html('<div class="text-muted small">Memuat user...</div>');
    $sertHidden.val('');
    $sertCount.addClass('d-none').text('0 ditemukan');
    
    if(!id){ 
      $sertWrap.html('<div class="text-muted small">Pilih event terlebih dahulu.</div>'); 
      return; 
    }
    
    $.get('<?= site_url('admin/dokumen/users-for-certificate/') ?>' + encodeURIComponent(id))
      .done(res => {
        if(res && res.status === 'success'){ 
          renderCertificateUsers(res.data||[]); 
        } else { 
          $sertWrap.html('<div class="text-danger small">Gagal memuat peserta: ' + (res.message || 'Unknown error') + '</div>'); 
        }
      })
      .fail((xhr, status, error) => { 
        console.error('Certificate users fetch error:', error, xhr.responseText);
        $sertWrap.html('<div class="text-danger small">Gagal memuat peserta. Cek console untuk detail.</div>'); 
      });
  });

  // ====== DOKUMEN LAINNYA PICK LIST ======
  const $dokLainEvent  = $('#dokumenLainEventId');
  const $dokLainWrap   = $('#dokumenLainUserListWrap');
  const $dokLainHidden = $('#dokumenLainUserIdHidden');
  const $dokLainCount  = $('#dokumenLainCountBadge');

  function renderDokumenLainUsers(items){
    console.log('renderDokumenLainUsers called with', items);
    
    $dokLainWrap.empty();
    $dokLainHidden.val('');
    
    if(!items || !items.length){
      $dokLainWrap.html('<div class="text-muted small">Tidak ada user pada event ini.</div>');
      if ($dokLainCount && $dokLainCount.length) {
        $dokLainCount.addClass('d-none').text('0 ditemukan');
      }
      return;
    }
    
    if ($dokLainCount && $dokLainCount.length) {
      $dokLainCount.removeClass('d-none').text(items.length+' ditemukan');
    }

    items.forEach(u=>{
      const docCount = parseInt(u.doc_count) || 0;
      const badgeDokumen = docCount > 0
        ? `<span class="badge bg-info-subtle text-info border border-info-subtle">${docCount} Dokumen</span>`
        : '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Belum ada Dokumen</span>';
      const role = u.role ? `<span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1">${escapeHtml(u.role)}</span>` : '';
      const paymentBadge = u.payment_verified 
        ? '<span class="badge bg-success-subtle text-success border border-success-subtle ms-1">Verified</span>'
        : '<span class="badge bg-warning-subtle text-warning border border-warning-subtle ms-1">Pending</span>';

      const $row = $(`
        <div class="picklist-item d-flex justify-content-between align-items-start mb-2" data-id="${u.id_user}">
          <div>
            <div class="fw-semibold">${escapeHtml(u.nama_lengkap||'-')}</div>
            <div class="small text-muted">${escapeHtml(u.email||'')}</div>
          </div>
          <div class="text-end">
            ${badgeDokumen} ${paymentBadge} ${role}
          </div>
        </div>
      `);

      $row.on('click', function(){
        $('.picklist-item', $dokLainWrap).removeClass('active');
        $(this).addClass('active');
        $dokLainHidden.val($(this).data('id'));
      });

      $dokLainWrap.append($row);
    });
  }

  $dokLainEvent.on('change', function(){
    const id = $(this).val();
    console.log('Dokumen Lain event changed to:', id);
    
    $dokLainWrap.html('<div class="text-muted small">Memuat user...</div>');
    $dokLainHidden.val('');
    
    if ($dokLainCount && $dokLainCount.length) {
      $dokLainCount.addClass('d-none').text('0 ditemukan');
    }
    
    if(!id){ 
      $dokLainWrap.html('<div class="text-muted small">Pilih event terlebih dahulu.</div>'); 
      return; 
    }
    
    const url = '<?= site_url('admin/dokumen/users-for-dokumen-lain/') ?>' + encodeURIComponent(id);
    console.log('Fetching from:', url);
    
    $.ajax({
      url: url,
      method: 'GET',
      dataType: 'json',
      timeout: 10000,
      success: function(res) {
        console.log('AJAX success response:', res);
        
        if(res && res.status === 'success'){ 
          console.log('Rendering users:', res.data);
          renderDokumenLainUsers(res.data || []);
          
          // Update bulk user count if in bulk mode
          if ($('input[name="upload_mode"]:checked').val() === 'bulk') {
            const count = (res.data || []).length;
            $('#bulkUserCount').text(count + ' user');
          }
        } else { 
          const errMsg = res.message || 'Unknown error';
          console.error('API returned error:', errMsg);
          $dokLainWrap.html('<div class="text-danger small">Gagal memuat user: ' + escapeHtml(errMsg) + '</div>'); 
        }
      },
      error: function(xhr, status, error) {
        console.error('AJAX error:', {
          status: status,
          error: error,
          responseText: xhr.responseText,
          statusCode: xhr.status
        });
        
        let errMsg = 'Gagal memuat user. ';
        if (xhr.status === 404) {
          errMsg += 'Endpoint tidak ditemukan.';
        } else if (xhr.status === 500) {
          errMsg += 'Server error. Cek log server.';
        } else if (status === 'timeout') {
          errMsg += 'Request timeout.';
        } else {
          errMsg += 'Cek console untuk detail.';
        }
        
        $dokLainWrap.html('<div class="text-danger small">' + errMsg + '</div>');
      }
    });
  });

  // Validasi submit LOA
  $('#loaForm').on('submit', function(e){
    const eventId = $('#loaEventId').val();
    const userId  = $('#loaUserIdHidden').val();
    const fileInp = this.querySelector('input[name="loa_file"]');
    const file    = fileInp && fileInp.files[0];

    if (!eventId){ e.preventDefault(); Swal.fire('Error','Pilih event terlebih dahulu.','error'); return; }
    if (!userId){ e.preventDefault(); Swal.fire('Error','Pilih user pada daftar.','error'); return; }
    if (!file){ e.preventDefault(); Swal.fire('Error','Pilih file LOA.','error'); return; }
    if (file.size > 5242880){ e.preventDefault(); Swal.fire('Error','Ukuran file tidak boleh lebih dari 5MB.','error'); return; }
    const ext = (file.name.split('.').pop()||'').toLowerCase();
    if (!['pdf','doc','docx'].includes(ext)){ e.preventDefault(); Swal.fire('Error','File harus PDF/DOC/DOCX.','error'); return; }

    const btn = $('#loaSubmitBtn'); 
    if(btn.length) btn.prop('disabled',true).find('span').text('Memproses...');
  });

  // Validasi submit Sertifikat
  $('#sertifikatForm').on('submit', function(e){
    const eventId = $('#sertifikatEventId').val();
    const userId  = $('#sertifikatUserIdHidden').val();
    const fileInp = this.querySelector('input[name="sertifikat_file"]');
    const file    = fileInp && fileInp.files[0];

    if (!eventId){ e.preventDefault(); Swal.fire('Error','Pilih event terlebih dahulu.','error'); return; }
    if (!userId){ e.preventDefault(); Swal.fire('Error','Pilih user pada daftar.','error'); return; }
    if (!file){ e.preventDefault(); Swal.fire('Error','Pilih file Sertifikat.','error'); return; }
    if (file.size > 5242880){ e.preventDefault(); Swal.fire('Error','Ukuran file tidak boleh lebih dari 5MB.','error'); return; }
    const ext = (file.name.split('.').pop()||'').toLowerCase();
    if (!['pdf','jpg','jpeg','png'].includes(ext)){ e.preventDefault(); Swal.fire('Error','File harus PDF/JPG/PNG.','error'); return; }

    const btn = $('#sertifikatSubmitBtn'); 
    if(btn.length) btn.prop('disabled',true).find('span').text('Memproses...');
  });
</script>