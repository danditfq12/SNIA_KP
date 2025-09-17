<?php
// ====== DEFAULT VARS WITH NULL CHECKS ======
$title = $title ?? 'Manajemen Dokumen';
$stats = $stats ?? ['total_documents'=>0,'loa_count'=>0,'sertifikat_count'=>0,'recent_uploads'=>0];
$events = $events ?? [];
$documents = $documents ?? [];
$current_event = $current_event ?? '';
$current_tipe = $current_tipe ?? '';
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_admin') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- HEADER -->
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
          <?php if (empty($documents)): ?>
            <!-- Empty state without DataTable -->
            <div class="text-center py-5">
              <i class="bi bi-inbox fs-1 text-muted mb-3"></i>
              <h5 class="text-muted">Belum ada dokumen</h5>
              <p class="text-muted">Upload dokumen LOA atau sertifikat untuk mulai mengelola dokumen.</p>
            </div>
          <?php else: ?>
            <!-- Table with documents -->
            <div class="table-responsive">
              <table id="documentsTable" class="table table-hover align-middle">
                <thead>
                  <tr>
                    <th width="5%">No</th>
                    <th width="10%">Tipe</th>
                    <th width="25%">User</th>
                    <th width="20%">Event</th>
                    <th width="20%">File</th>
                    <th width="10%">Upload</th>
                    <th width="10%">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                <?php 
                  $no = 1; 
                  foreach($documents as $d): 
                    // Ensure all required fields exist with fallbacks
                    $id = (int)($d['id_dokumen'] ?? 0);
                    $type = strtolower($d['tipe'] ?? 'loa');
                    $file = $d['file_path'] ?? '';
                    $nama = $d['nama_lengkap'] ?? 'Unknown';
                    $email = $d['email'] ?? '';
                    $role = $d['role'] ?? '';
                    $eventTitle = $d['event_title'] ?? '';
                    $uploadedAt = $d['uploaded_at'] ?? '';
                    
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    $icon = 'bi-file-earmark';
                    $icColor = 'text-secondary';
                    
                    if ($ext === 'pdf') { 
                      $icon = 'bi-file-earmark-pdf'; 
                      $icColor = 'text-danger'; 
                    } elseif (in_array($ext, ['doc','docx'])) { 
                      $icon = 'bi-file-earmark-word'; 
                      $icColor = 'text-primary'; 
                    } elseif (in_array($ext, ['jpg','jpeg','png'])) { 
                      $icon = 'bi-file-earmark-image'; 
                      $icColor = 'text-success'; 
                    }
                ?>
                  <tr>
                    <td><?= $no++ ?></td>
                    <td>
                      <?php if ($type === 'loa'): ?>
                        <span class="badge bg-success">
                          <i class="bi bi-file-earmark-arrow-up me-1"></i> LOA
                        </span>
                      <?php else: ?>
                        <span class="badge bg-warning text-dark">
                          <i class="bi bi-patch-check me-1"></i> Sertifikat
                        </span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="fw-semibold"><?= esc($nama) ?></div>
                      <?php if ($email): ?>
                        <small class="text-muted"><?= esc($email) ?></small>
                      <?php endif; ?>
                      <?php if ($role): ?>
                        <div>
                          <span class="badge bg-<?= $role === 'presenter' ? 'primary' : 'secondary' ?>">
                            <?= ucfirst($role) ?>
                          </span>
                        </div>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?= $eventTitle ? '<strong>'.esc($eventTitle).'</strong>' : '<span class="text-muted">-</span>' ?>
                    </td>
                    <td>
                      <div class="d-flex align-items-center">
                        <i class="bi <?= $icon ?> fs-5 me-2 <?= $icColor ?>"></i>
                        <div>
                          <div><?= esc(basename($file)) ?></div>
                          <small class="text-muted"><?= strtoupper($ext ?: '-') ?></small>
                        </div>
                      </div>
                    </td>
                    <td>
                      <?= $uploadedAt ? date('d/m/Y H:i', strtotime($uploadedAt)) : '-' ?>
                    </td>
                    <td>
                      <div class="action-buttons">
                        <a href="<?= site_url('admin/dokumen/download/'.$id) ?>" 
                           class="btn-action btn-soft-info" 
                           data-bs-toggle="tooltip" 
                           data-bs-title="Download">
                          <i class="bi bi-download"></i>
                        </a>
                        <button type="button" 
                                class="btn-action btn-soft-danger" 
                                data-bs-toggle="tooltip" 
                                data-bs-title="Hapus"
                                onclick="deleteDocument(<?= $id ?>)">
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

      <!-- MODALS -->
      <!-- Upload LOA -->
      <div class="modal fade" id="uploadLoaModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
          <form action="" method="POST" enctype="multipart/form-data" id="loaForm" class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title"><i class="bi bi-upload me-2"></i>Upload LOA</h5>
              <button class="btn-close" type="button" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <?= csrf_field() ?>
              <div class="mb-3">
                <label class="form-label">Event *</label>
                <select class="form-select" name="event_id" id="loaEventId" required>
                  <option value="">-- Pilih Event --</option>
                  <?php foreach ($events as $e): ?>
                    <option value="<?= $e['id'] ?>"><?= esc($e['title'] ?? 'No Title') ?></option>
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
              <button class="btn-close" type="button" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <?= csrf_field() ?>
              <div class="mb-3">
                <label class="form-label">Event *</label>
                <select class="form-select" name="event_id" id="sertifikatEventId" required>
                  <option value="">-- Pilih Event --</option>
                  <?php foreach ($events as $e): ?>
                    <option value="<?= $e['id'] ?>"><?= esc($e['title'] ?? 'No Title') ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Peserta *</label>
                <select class="form-select" name="user_id" id="sertifikatUserId" required>
                  <option value="">-- Pilih Event terlebih dahulu --</option>
                </select>
                <div class="form-text">
                  <i class="bi bi-info-circle me-1"></i>
                  Menampilkan semua peserta yang hadir: <strong>Presenter</strong>, <strong>Audience Online</strong>, dan <strong>Audience Offline</strong>
                </div>
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
              <button class="btn-close" type="button" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <?= csrf_field() ?>
              <div class="mb-3">
                <label class="form-label">Event *</label>
                <select class="form-select" name="event_id" required>
                  <option value="">-- Pilih Event --</option>
                  <?php foreach ($events as $e): ?>
                    <option value="<?= $e['id'] ?>"><?= esc($e['title'] ?? 'No Title') ?></option>
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
              <button class="btn-close" type="button" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <?= csrf_field() ?>
              <div class="mb-3">
                <label class="form-label">Event *</label>
                <select class="form-select" name="event_id" required>
                  <option value="">-- Pilih Event --</option>
                  <?php foreach ($events as $e): ?>
                    <option value="<?= $e['id'] ?>"><?= esc($e['title'] ?? 'No Title') ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="alert alert-warning mb-0">
                <i class="bi bi-exclamation-triangle me-1"></i>
                Sertifikat akan dibuat untuk <strong>semua peserta yang tercatat hadir</strong>:
                <ul class="mb-0 mt-2">
                  <li><strong>Presenter</strong> (offline)</li>
                  <li><strong>Audience Online</strong></li>
                  <li><strong>Audience Offline</strong></li>
                </ul>
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

<!-- STYLES -->
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
  
  /* Fallback styles for table if DataTable CSS fails */
  #documentsTable { width: 100% !important; }
  #documentsTable td, #documentsTable th { 
    padding: 8px 12px; 
    vertical-align: middle; 
    border-bottom: 1px solid #dee2e6; 
  }
  #documentsTable tbody tr:hover { background-color: #f8f9fa; }
  
  /* Fix for responsive issues */
  .table-responsive { overflow-x: auto; }
  @media (max-width: 768px) {
    .action-buttons { 
      display: flex; 
      flex-direction: column; 
      gap: 0.25rem; 
    }
    .btn-action { 
      font-size: 0.875rem; 
      padding: 0.375rem 0.5rem; 
    }
  }
</style>

<!-- SCRIPTS - FIXED ORDER -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

<script>
  // Init DataTable + Tooltips
  function initTooltips(scope=document){
    return [].slice.call(scope.querySelectorAll('[data-bs-toggle="tooltip"]'))
      .map(el => new bootstrap.Tooltip(el));
  }

  $(function(){
    console.log('DOM ready - initializing page...');
    
    // Initialize tooltips function
    function initTooltips() {
      try {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
          return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        console.log('Tooltips initialized:', tooltipList.length);
      } catch (error) {
        console.log('Tooltip initialization failed:', error);
      }
    }

    // Check if we have documents and table exists
    var $table = $('#documentsTable');
    var hasDocuments = <?= !empty($documents) ? 'true' : 'false' ?>;
    
    console.log('Has documents:', hasDocuments);
    console.log('Table exists:', $table.length > 0);

    if (hasDocuments && $table.length > 0) {
      // Only initialize DataTable if we have documents
      console.log('Initializing DataTable...');
      
      // Check if DataTable is available
      if (typeof $.fn.DataTable === 'undefined') {
        console.error('DataTable library not loaded');
        initTooltips();
        return;
      }

      try {
        // Destroy existing DataTable if it exists
        if ($.fn.DataTable.isDataTable('#documentsTable')) {
          console.log('Destroying existing DataTable instance');
          $('#documentsTable').DataTable().destroy();
        }

        // Verify table structure
        var headerCount = $table.find('thead tr th').length;
        var bodyRowCount = $table.find('tbody tr').length;
        
        console.log('Header columns:', headerCount);
        console.log('Body rows:', bodyRowCount);

        if (headerCount === 7) {
          var dt = $('#documentsTable').DataTable({
            "processing": false,
            "serverSide": false,
            "paging": true,
            "lengthChange": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "pageLength": 25,
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "Semua"]],
            "order": [[ 5, "desc" ]],
            "columnDefs": [
              { "orderable": false, "targets": [6] },
              { "searchable": false, "targets": [0] }
            ],
            "language": {
              "search": "Cari:",
              "lengthMenu": "Tampilkan _MENU_ data per halaman",
              "zeroRecords": "Tidak ada data ditemukan",
              "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
              "infoEmpty": "Menampilkan 0 sampai 0 dari 0 data",
              "infoFiltered": "(difilter dari _MAX_ total data)",
              "paginate": {
                "first": "Pertama",
                "last": "Terakhir",
                "next": "Selanjutnya",
                "previous": "Sebelumnya"
              }
            },
            "drawCallback": function() {
              initTooltips();
            },
            "initComplete": function() {
              console.log('DataTable initialization completed successfully');
            }
          });
          
          console.log('DataTable initialized successfully');
          
        } else {
          console.error('Invalid table structure. Expected 7 columns, found:', headerCount);
        }
        
      } catch (error) {
        console.error('DataTable initialization failed:', error);
      }
    } else {
      console.log('No documents or table not found, skipping DataTable initialization');
    }
    
    // Always initialize tooltips
    initTooltips();
  });

  // LOA Event Change Handler
  $('#loaEventId').on('change', function(){
    const id = $(this).val();
    const $sel = $('#loaUserId');
    
    $sel.html('<option value="">Loading...</option>').prop('disabled', true);
    
    if(id){
      $.get('<?= site_url('admin/dokumen/getVerifiedPresenters/') ?>' + id)
        .done(res => {
          $sel.prop('disabled', false);
          if(res.status === 'success' && (res.data || []).length){
            $sel.html('<option value="">-- Pilih Presenter --</option>');
            res.data.forEach(user => {
              $sel.append(`<option value="${user.id_user}">${user.nama_lengkap} (${user.email})</option>`);
            });
          } else {
            $sel.html('<option value="">Tidak ada presenter yang memenuhi syarat</option>');
          }
        })
        .fail(() => {
          $sel.prop('disabled', false).html('<option value="">Gagal memuat data presenter</option>');
        });
      $('#loaForm').attr('action', '<?= site_url('admin/dokumen/uploadLoa/') ?>' + id);
    } else {
      $sel.prop('disabled', false).html('<option value="">-- Pilih Event terlebih dahulu --</option>');
      $('#loaForm').attr('action', '');
    }
  });

  // Certificate Event Change Handler
  $('#sertifikatEventId').on('change', function(){
    const id = $(this).val();
    const $sel = $('#sertifikatUserId');
    
    $sel.html('<option value="">Loading...</option>').prop('disabled', true);
    
    if(id){
      $.get('<?= site_url('admin/dokumen/getAttendees/') ?>' + id)
        .done(res => {
          $sel.prop('disabled', false);
          if(res.status === 'success' && (res.data || []).length){
            $sel.html('<option value="">-- Pilih Peserta --</option>');
            
            // Group by role for better organization
            const groupedUsers = {};
            res.data.forEach(user => {
              const role = user.role || 'unknown';
              if (!groupedUsers[role]) {
                groupedUsers[role] = [];
              }
              groupedUsers[role].push(user);
            });
            
            // Add options with role grouping
            Object.keys(groupedUsers).sort().forEach(role => {
              if (groupedUsers[role].length > 0) {
                const roleLabel = role.charAt(0).toUpperCase() + role.slice(1);
                const optgroup = $(`<optgroup label="${roleLabel}"></optgroup>`);
                
                groupedUsers[role].forEach(user => {
                  optgroup.append(`<option value="${user.id_user}">${user.nama_lengkap} - ${user.email}</option>`);
                });
                
                $sel.append(optgroup);
              }
            });
          } else {
            $sel.html('<option value="">Tidak ada peserta yang memenuhi syarat</option>');
          }
        })
        .fail(() => {
          $sel.prop('disabled', false).html('<option value="">Gagal memuat data peserta</option>');
        });
      $('#sertifikatForm').attr('action', '<?= site_url('admin/dokumen/uploadSertifikat/') ?>' + id);
    } else {
      $sel.prop('disabled', false).html('<option value="">-- Pilih Event terlebih dahulu --</option>');
      $('#sertifikatForm').attr('action', '');
    }
  });

  // Delete Function
  function deleteDocument(id){
    Swal.fire({
      title: 'Hapus Dokumen?', 
      text: 'File akan dihapus permanen dari server dan database.',
      icon: 'warning', 
      showCancelButton: true,
      confirmButtonColor: '#d33', 
      cancelButtonColor: '#6b7280',
      confirmButtonText: 'Ya, Hapus', 
      cancelButtonText: 'Batal'
    }).then(r => {
      if(r.isConfirmed){
        const form = document.createElement('form'); 
        form.method = 'POST'; 
        form.action = '<?= site_url('admin/dokumen/delete/') ?>' + id;
        
        const csrfInput = document.createElement('input'); 
        csrfInput.type = 'hidden'; 
        csrfInput.name = '<?= csrf_token() ?>'; 
        csrfInput.value = '<?= csrf_hash() ?>'; 
        form.appendChild(csrfInput);
        
        document.body.appendChild(form); 
        form.submit();
      }
    });
  }

  // Form Validation
  $('#loaForm').on('submit', function(e) {
    const eventId = $('#loaEventId').val();
    const userId = $('#loaUserId').val();
    const file = $('input[name="loa_file"]')[0].files[0];
    
    if (!eventId) {
      e.preventDefault();
      Swal.fire('Error', 'Pilih event terlebih dahulu.', 'error');
      return false;
    }
    
    if (!userId) {
      e.preventDefault();
      Swal.fire('Error', 'Pilih presenter terlebih dahulu.', 'error');
      return false;
    }
    
    if (!file) {
      e.preventDefault();
      Swal.fire('Error', 'Pilih file LOA untuk diupload.', 'error');
      return false;
    }
    
    if (file.size > 5242880) {
      e.preventDefault();
      Swal.fire('Error', 'Ukuran file tidak boleh lebih dari 5MB.', 'error');
      return false;
    }
    
    const allowedExtensions = ['pdf', 'doc', 'docx'];
    const fileExtension = file.name.split('.').pop().toLowerCase();
    if (!allowedExtensions.includes(fileExtension)) {
      e.preventDefault();
      Swal.fire('Error', 'File harus berformat PDF, DOC, atau DOCX.', 'error');
      return false;
    }
  });

  $('#sertifikatForm').on('submit', function(e) {
    const eventId = $('#sertifikatEventId').val();
    const userId = $('#sertifikatUserId').val();
    const file = $('input[name="sertifikat_file"]')[0].files[0];
    
    if (!eventId) {
      e.preventDefault();
      Swal.fire('Error', 'Pilih event terlebih dahulu.', 'error');
      return false;
    }
    
    if (!userId) {
      e.preventDefault();
      Swal.fire('Error', 'Pilih peserta terlebih dahulu.', 'error');
      return false;
    }
    
    if (!file) {
      e.preventDefault();
      Swal.fire('Error', 'Pilih file sertifikat untuk diupload.', 'error');
      return false;
    }
    
    if (file.size > 5242880) {
      e.preventDefault();
      Swal.fire('Error', 'Ukuran file tidak boleh lebih dari 5MB.', 'error');
      return false;
    }
    
    const allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
    const fileExtension = file.name.split('.').pop().toLowerCase();
    if (!allowedExtensions.includes(fileExtension)) {
      e.preventDefault();
      Swal.fire('Error', 'File harus berformat PDF, JPG, JPEG, atau PNG.', 'error');
      return false;
    }
  });

  // Bulk Forms Validation
  $('#bulkLoaForm').on('submit', function(e) {
    const eventId = $('select[name="event_id"]', this).val();
    if (!eventId) {
      e.preventDefault();
      Swal.fire('Error', 'Pilih event untuk generate bulk LOA.', 'error');
      return false;
    }
    
    e.preventDefault();
    Swal.fire({
      title: 'Generate Bulk LOA?',
      text: 'LOA akan dibuat untuk semua presenter dengan pembayaran terverifikasi.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#17a2b8',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Ya, Generate',
      cancelButtonText: 'Batal'
    }).then((result) => {
      if (result.isConfirmed) {
        this.submit();
      }
    });
  });

  $('#bulkSertifikatForm').on('submit', function(e) {
    const eventId = $('select[name="event_id"]', this).val();
    if (!eventId) {
      e.preventDefault();
      Swal.fire('Error', 'Pilih event untuk generate bulk sertifikat.', 'error');
      return false;
    }
    
    e.preventDefault();
    Swal.fire({
      title: 'Generate Bulk Sertifikat?',
      text: 'Sertifikat akan dibuat untuk semua peserta yang tercatat hadir.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#6c757d',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Ya, Generate',
      cancelButtonText: 'Batal'
    }).then((result) => {
      if (result.isConfirmed) {
        this.submit();
      }
    });
  });

  // File info display
  $('input[type="file"]').on('change', function() {
    const file = this.files[0];
    const $parent = $(this).closest('.mb-3');
    const $info = $parent.find('.file-info');
    
    $info.remove();
    
    if (file) {
      const sizeInMB = (file.size / 1024 / 1024).toFixed(2);
      const infoHtml = `
        <div class="file-info mt-2 p-2 bg-light rounded">
          <small class="text-muted">
            <i class="bi bi-file-earmark me-1"></i>
            <strong>${file.name}</strong> (${sizeInMB} MB)
          </small>
        </div>
      `;
      $(this).after(infoHtml);
    }
  });

  // Flash Messages
  <?php if (session('success')): ?>
    Swal.fire({ 
      icon: 'success', 
      title: 'Berhasil!', 
      text: '<?= esc(session('success')) ?>', 
      timer: 3000, 
      showConfirmButton: false 
    });
  <?php endif; ?>
  
  <?php if (session('error')): ?>
    Swal.fire({ 
      icon: 'error', 
      title: 'Error!', 
      text: '<?= esc(session('error')) ?>'
    });
  <?php endif; ?>

  // Reset form when modal is closed
  $('.modal').on('hidden.bs.modal', function() {
    const $form = $(this).find('form');
    $form[0].reset();
    $form.find('select').prop('disabled', false);
    $form.find('.file-info').remove();
    $form.attr('action', '');
    
    // Reset specific selects
    $('#loaUserId').html('<option value="">-- Pilih Event terlebih dahulu --</option>');
    $('#sertifikatUserId').html('<option value="">-- Pilih Event terlebih dahulu --</option>');
  });

  // Add loading states
  $('form').on('submit', function() {
    const $btn = $(this).find('button[type="submit"]');
    const originalText = $btn.html();
    $btn.prop('disabled', true).html('<i class="bi bi-hourglass-split me-1"></i>Processing...');
    
    setTimeout(() => {
      $btn.prop('disabled', false).html(originalText);
    }, 10000);
  });
</script>