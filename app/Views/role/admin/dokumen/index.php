<?php
// ====== DEFAULT VARS ======
$title         = $title ?? 'Manajemen Dokumen';
$stats         = $stats ?? ['total_documents'=>0,'loa_count'=>0,'sertifikat_count'=>0,'recent_uploads'=>0];
$events        = $events ?? [];
$documents     = $documents ?? [];
$current_event = $current_event ?? '';
$current_tipe  = $current_tipe ?? '';
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
        <div class="col-6 col-xl-3"><div class="stat-card shadow-sm h-100"><div class="d-flex align-items-center">
          <div class="stat-icon bg-primary"><i class="bi bi-file-earmark-text"></i></div>
          <div class="ms-3"><div class="stat-number"><?= number_format((int)$stats['total_documents']) ?></div><div class="text-muted">Total Dokumen</div></div>
        </div></div></div>
        <div class="col-6 col-xl-3"><div class="stat-card shadow-sm h-100"><div class="d-flex align-items-center">
          <div class="stat-icon bg-success"><i class="bi bi-file-earmark-arrow-up"></i></div>
          <div class="ms-3"><div class="stat-number"><?= number_format((int)$stats['loa_count']) ?></div><div class="text-muted">LOA</div></div>
        </div></div></div>
        <div class="col-6 col-xl-3"><div class="stat-card shadow-sm h-100"><div class="d-flex align-items-center">
          <div class="stat-icon bg-warning"><i class="bi bi-patch-check"></i></div>
          <div class="ms-3"><div class="stat-number"><?= number_format((int)$stats['sertifikat_count']) ?></div><div class="text-muted">Sertifikat</div></div>
        </div></div></div>
        <div class="col-6 col-xl-3"><div class="stat-card shadow-sm h-100"><div class="d-flex align-items-center">
          <div class="stat-icon bg-info"><i class="bi bi-clock-history"></i></div>
          <div class="ms-3"><div class="stat-number"><?= number_format((int)$stats['recent_uploads']) ?></div><div class="text-muted">Upload Minggu Ini</div></div>
        </div></div></div>
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
              <p class="text-muted">Upload dokumen LOA atau sertifikat untuk mulai mengelola dokumen.</p>
            </div>
          <?php else: ?>
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
                <?php $no=1; foreach($documents as $d):
                    $id=(int)($d['id_dokumen']??0); $type=strtolower($d['tipe']??'loa'); $file=$d['file_path']??'';
                    $nama=$d['nama_lengkap']??'Unknown'; $email=$d['email']??''; $role=$d['role']??'';
                    $eventTitle=$d['event_title']??''; $uploadedAt=$d['uploaded_at']??'';
                    $ext=strtolower(pathinfo($file,PATHINFO_EXTENSION));
                    $icon='bi-file-earmark'; $icColor='text-secondary';
                    if($ext==='pdf'){ $icon='bi-file-earmark-pdf'; $icColor='text-danger'; }
                    elseif(in_array($ext,['doc','docx'])){ $icon='bi-file-earmark-word'; $icColor='text-primary'; }
                    elseif(in_array($ext,['jpg','jpeg','png'])){ $icon='bi-file-earmark-image'; $icColor='text-success'; }
                ?>
                  <tr data-row-id="<?= $id ?>">
                    <td><?= $no++ ?></td>
                    <td><?= $type==='loa'
                      ? '<span class="badge bg-success"><i class="bi bi-file-earmark-arrow-up me-1"></i> LOA</span>'
                      : '<span class="badge bg-warning text-dark"><i class="bi bi-patch-check me-1"></i> Sertifikat</span>' ?></td>
                    <td>
                      <div class="fw-semibold"><?= esc($nama) ?></div>
                      <?php if ($email): ?><small class="text-muted"><?= esc($email) ?></small><?php endif; ?>
                      <?php if ($role): ?><div><span class="badge bg-<?= $role==='presenter'?'primary':'secondary' ?>"><?= ucfirst($role) ?></span></div><?php endif; ?>
                    </td>
                    <td><?= $eventTitle ? '<strong>'.esc($eventTitle).'</strong>' : '<span class="text-muted">-</span>' ?></td>
                    <td>
                      <div class="d-flex align-items-center">
                        <i class="bi <?= $icon ?> fs-5 me-2 <?= $icColor ?>"></i>
                        <div>
                          <div><?= esc(basename($file)) ?></div>
                          <small class="text-muted"><?= strtoupper($ext ?: '-') ?></small>
                        </div>
                      </div>
                    </td>
                    <td><?= $uploadedAt ? date('d/m/Y H:i', strtotime($uploadedAt)) : '-' ?></td>
                    <td>
                      <div class="action-buttons">
                        <a href="<?= site_url('admin/dokumen/download/'.$id) ?>" class="btn-action btn-soft-info" data-bs-toggle="tooltip" data-bs-title="Download"><i class="bi bi-download"></i></a>

                        <button type="button"
                                class="btn-action btn-soft-danger js-del"
                                data-id="<?= $id ?>"
                                data-bs-toggle="tooltip"
                                data-bs-title="Hapus">
                          <i class="bi bi-trash3"></i>
                        </button>
                      </div>

                      <!-- Fallback POST (non-AJAX) -->
                      <form class="d-none" id="del-form-<?= $id ?>" method="POST" action="<?= site_url('admin/dokumen/delete/'.$id) ?>">
                        <?= csrf_field() ?>
                      </form>
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

<!-- ====== STYLES ====== -->
<style>
  :root{ --primary-color:#2563eb; --info-color:#06b6d4; --success-color:#10b981; --warning-color:#f59e0b; --danger-color:#ef4444; }
  body{ background:linear-gradient(135deg,#f8fafc 0%,#e2e8f0 100%); font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; }
  .header-section.header-blue{ background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%); color:#fff; padding:28px 24px; border-radius:16px; box-shadow:0 8px 28px rgba(0,0,0,.12); }
  .header-section.header-blue .welcome-text{ color:#fff; font-weight:800; font-size:2rem; }
  .stat-card{ background:#fff; border-radius:14px; padding:20px; box-shadow:0 8px 28px rgba(0,0,0,.08); border-left:4px solid #e9ecef; position:relative; overflow:hidden; }
  .stat-card:before{ content:''; position:absolute; left:0; top:0; height:4px; width:100%; background:linear-gradient(90deg,var(--primary-color),var(--info-color)); }
  .stat-icon{ width:56px; height:56px; border-radius:12px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:22px; }
  .stat-number{ font-size:2rem; font-weight:800; color:#1e293b; line-height:1; }
  .bg-gradient-primary{ background: linear-gradient(135deg, var(--primary-color), var(--info-color)); }
  .btn-custom{ border-radius:10px; padding:.55rem .9rem; font-weight:600; transition:.2s; }
  .btn-custom:hover{ transform:translateY(-1px); box-shadow:0 6px 14px rgba(15,23,42,.12); }
  .action-buttons{ display:flex; flex-wrap:wrap; align-items:center; gap:.5rem; }
  .btn-action{ display:inline-flex; align-items:center; justify-content:center; padding:.45rem .6rem; border-radius:10px; border:1px solid #e8eef5; background:#fff; color:#334155; box-shadow:0 2px 6px rgba(15,23,42,.04); transition:.18s ease; font-weight:600; }
  .btn-action:hover{ transform:translateY(-1px); box-shadow:0 8px 18px rgba(15,23,42,.10); }
  .btn-soft-info{ background:rgba(6,182,212,.12); color:#0e7490; border-color:rgba(6,182,212,.25); }
  .btn-soft-danger{ background:rgba(239,68,68,.12); color:#991b1b; border-color:rgba(239,68,68,.25); }
  #documentsTable thead th{ background:#f8fafc; white-space:nowrap; }
  #documentsTable { width:100%!important; }
  #documentsTable td, #documentsTable th { padding:8px 12px; vertical-align:middle; border-bottom:1px solid #dee2e6; }
  #documentsTable tbody tr:hover { background:#f8f9fa; }
  .table-responsive { overflow-x:auto; }
  .picklist-item { cursor:pointer; border:1px solid #e5e7eb; border-radius:10px; padding:10px 12px; background:#fff; transition: .15s ease; }
  .picklist-item:hover { box-shadow:0 8px 18px rgba(15,23,42,.08); transform: translateY(-1px); }
  .picklist-item.disabled { opacity:1; }
  .picklist-item.active { outline:2px solid var(--primary-color); }
  .badge-fp-accepted { background: rgba(16,185,129,.12); color:#065f46; border:1px solid rgba(16,185,129,.25); }
  .badge-fp-other    { background: #f1f5f9; color:#475569; border:1px solid #e2e8f0; }
</style>

<!-- ====== SCRIPTS ====== -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

<script>
  // Tooltips
  function initTooltips(scope=document){
    return [].slice.call(scope.querySelectorAll('[data-bs-toggle="tooltip"]'))
      .map(el => new bootstrap.Tooltip(el));
  }

  // CSRF
  const CSRF_NAME = '<?= csrf_token() ?>';
  let   CSRF_HASH = '<?= csrf_hash() ?>';
  function refreshCsrf(newHash){
    if(!newHash) return;
    CSRF_HASH = newHash;
    document.querySelectorAll('input[name="'+CSRF_NAME+'"]').forEach(i => i.value = newHash);
  }

  // DataTable init
  let dt;
  $(function(){
    const $table = $('#documentsTable');
    const hasDocuments = <?= !empty($documents) ? 'true' : 'false' ?>;
    if (hasDocuments && $table.length && typeof $.fn.DataTable !== 'undefined') {
      if ($.fn.DataTable.isDataTable('#documentsTable')) $('#documentsTable').DataTable().destroy();
      dt = $('#documentsTable').DataTable({
        paging:true,lengthChange:true,searching:true,ordering:true,info:true,autoWidth:false,responsive:true,
        pageLength:25,lengthMenu:[[10,25,50,-1],[10,25,50,'Semua']],order:[[5,'desc']],
        columnDefs:[{orderable:false,targets:[6]},{searchable:false,targets:[0]}],
        language:{search:'Cari:',lengthMenu:'Tampilkan _MENU_ data per halaman',zeroRecords:'Tidak ada data ditemukan',info:'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',infoEmpty:'Menampilkan 0 sampai 0 dari 0 data',infoFiltered:'(difilter dari _MAX_ total data)',paginate:{first:'Pertama',last:'Terakhir',next:'Selanjutnya',previous:'Sebelumnya'}],
        drawCallback:function(){ initTooltips(); }
      });
    }
    initTooltips();
  });

  // ====== DELETE (AJAX + fallback) ======
  async function performDelete(id){
    const url = '<?= site_url('admin/dokumen/delete/') ?>'+encodeURIComponent(id);
    try{
      const fd = new FormData();
      fd.append(CSRF_NAME, CSRF_HASH);
      const res = await fetch(url, {
        method: 'POST',
        headers: {'X-Requested-With':'XMLHttpRequest'},
        body: fd,
        credentials: 'same-origin'
      });
      const data = await res.json().catch(()=>null);
      if (data && data.csrf_hash) refreshCsrf(data.csrf_hash);

      if (data && data.status === 'success') {
        const tr = document.querySelector('tr[data-row-id="'+id+'"]');
        if (tr && dt) dt.row(tr).remove().draw(false);
        else if (tr) tr.remove();
        Swal.fire({icon:'success',title:'Dokumen berhasil dihapus',timer:1200,showConfirmButton:false});
        return;
      }
      throw new Error('Not success');
    }catch(err){
      // fallback POST biasa (untuk non-AJAX/redirect)
      const form = document.getElementById('del-form-'+id);
      if (form){
        const input = form.querySelector('input[name="'+CSRF_NAME+'"]');
        if (input) input.value = CSRF_HASH;
        form.submit();
      }
    }
  }

  // Delegasi klik (aman saat DataTables redraw/responsive)
  $(document).on('click', '.js-del', function(e){
    e.preventDefault();
    const id = $(this).data('id');
    Swal.fire({
      title:'Hapus Dokumen?',
      text:'File akan dihapus permanen dari server dan database.',
      icon:'warning',
      showCancelButton:true,
      confirmButtonColor:'#d33',
      cancelButtonColor:'#6b7280',
      confirmButtonText:'Ya, Hapus',
      cancelButtonText:'Batal'
    }).then(r => { if (r.isConfirmed) performDelete(id); });
  });

  // ====== PICK LIST LOA ======
  const $loaEvent  = $('#loaEventId');
  const $loaWrap   = $('#loaUserListWrap');
  const $loaHidden = $('#loaUserIdHidden');
  const $loaCount  = $('#loaCountBadge');

  function escapeHtml(str){ return (str||'').replace(/[&<>"']/g, s=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;', "'":'&#039;' }[s])); }

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
      const hasLoa   = !!u.has_loa;
      const role     = (u.role||'').toString();
      const fpStatus = (u.fp_status||'').toString().toLowerCase();
      const eligible = !!u.eligible;

      const badgeLoa = hasLoa
        ? '<span class="badge bg-success-subtle text-success border border-success-subtle">Sudah dapat LOA</span>'
        : '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Belum dapat LOA</span>';

      const isAccepted = fpStatus === 'accepted';
      const textFp = isAccepted ? 'FP Accepted' : (fpStatus ? ('FP '+fpStatus.charAt(0).toUpperCase()+fpStatus.slice(1)) : 'FP belum accepted');
      const badgeFp = `<span class="badge ${isAccepted ? 'badge-fp-accepted' : 'badge-fp-other'} ms-1">${textFp}</span>`;

      const badgeRole = role ? `<span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1">${role}</span>` : '';

      const $row = $(`
        <div class="picklist-item d-flex justify-content-between align-items-start mb-2" data-id="${u.id_user}">
          <div>
            <div class="fw-semibold">${escapeHtml(u.nama_lengkap||'-')}</div>
            <div class="small text-muted">${escapeHtml(u.email||'')}</div>
          </div>
          <div class="text-end">
            ${badgeLoa} ${badgeFp} ${badgeRole}
          </div>
        </div>
      `);

      $row.on('click', function(){
        if(hasLoa){ Swal.fire('Info','User ini sudah punya LOA.','info'); return; }
        if(!eligible){ Swal.fire('Tidak memenuhi syarat','LOA hanya untuk Presenter dengan Full Paper ACCEPTED.','warning'); return; }
        $('.picklist-item', $loaWrap).removeClass('active');
        $(this).addClass('active');
        $loaHidden.val($(this).data('id'));
      });

      $loaWrap.append($row);
    });
  }

  $loaEvent.on('change', function(){
    const id = $(this).val();
    $loaWrap.html('<div class="text-muted small">Memuat user...</div>');
    $loaHidden.val('');
    $loaCount.addClass('d-none').text('0 ditemukan');
    if(!id){ $loaWrap.html('<div class="text-muted small">Pilih event terlebih dahulu.</div>'); return; }
    $.get('<?= site_url('admin/dokumen/users-for-loa/') ?>'+encodeURIComponent(id))
      .done(res => { if(res && res.status==='success'){ renderLoaUsers(res.data||[]); } else { $loaWrap.html('<div class="text-danger small">Gagal memuat user.</div>'); } })
      .fail(()=> $loaWrap.html('<div class="text-danger small">Gagal memuat user.</div>'));
  });

  // ====== PICK LIST SERTIFIKAT ======
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

      const badgeRole = role ? `<span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1">${role}</span>` : '';

      const $row = $(`
        <div class="picklist-item d-flex justify-content-between align-items-start mb-2" data-id="${u.id_user}">
          <div>
            <div class="fw-semibold">${escapeHtml(u.nama_lengkap||'-')}</div>
            <div class="small text-muted">${escapeHtml(u.email||'')}</div>
          </div>
          <div class="text-end">
            ${badgeAttend} ${badgeCert} ${badgeRole}
          </div>
        </div>
      `);

      $row.on('click', function(){
        if(hasCert){ Swal.fire('Info','User ini sudah punya sertifikat.','info'); return; }
        if(!attended){ Swal.fire('Tidak memenuhi syarat','Sertifikat hanya untuk peserta yang hadir.','warning'); return; }
        $('.picklist-item', $sertWrap).removeClass('active');
        $(this).addClass('active');
        $sertHidden.val($(this).data('id'));
      });

      $sertWrap.append($row);
    });
  }

  $sertEvent.on('change', function(){
    const id = $(this).val();
    $sertWrap.html('<div class="text-muted small">Memuat user...</div>');
    $sertHidden.val('');
    $sertCount.addClass('d-none').text('0 ditemukan');
    if(!id){ $sertWrap.html('<div class="text-muted small">Pilih event terlebih dahulu.</div>'); return; }
    $.get('<?= site_url('admin/dokumen/users-for-certificate/') ?>' + encodeURIComponent(id))
      .done(res => { if(res && res.status === 'success'){ renderCertificateUsers(res.data||[]); } else { $sertWrap.html('<div class="text-danger small">Gagal memuat peserta.</div>'); } })
      .fail(() => { $sertWrap.html('<div class="text-danger small">Gagal memuat peserta.</div>'); });
  });

  // ====== Validasi Upload LOA ======
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

    const btn = $('#loaSubmitBtn'); btn.prop('disabled',true).find('span').text('Memproses...');
  });

  // ====== Validasi Upload Sertifikat ======
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

    const btn = $('#sertifikatSubmitBtn'); btn.prop('disabled',true).find('span').text('Memproses...');
  });

  // Flash
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
        <div class="form-text mt-1">
          LOA hanya untuk <strong>Presenter</strong> dengan <strong>Full Paper diterima (ACCEPTED)</strong>.
          User yang sudah punya LOA tetap ditampilkan (akan ditolak saat kirim).
        </div>
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
          Menampilkan semua pendaftar event. Yang <strong>belum absen</strong> diberi label <em>“Belum Absen”</em>.
          Jika sudah punya sertifikat diberi label <em>“Sudah ada Sertifikat”</em>.
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-warning" type="submit" id="sertifikatSubmitBtn"><i class="bi bi-upload me-1"></i><span>Upload Sertifikat</span></button>
      </div>
    </form>
  </div>
</div>

<!-- Bulk LOA -->
<div class="modal fade" id="bulkLoaModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form action="<?= site_url('admin/dokumen/generateBulkLOA') ?>" method="POST" id="bulkLoaForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-stars me-2"></i>Generate Bulk LOA</h5>
        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label">Event *</label>
          <select class="form-select" name="event_id" required>
            <option value="">-- Pilih Event --</option>
            <?php foreach ($events as $e): ?>
              <option value="<?= (int)$e['id'] ?>"><?= esc($e['title'] ?? 'No Title') ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="alert alert-info mb-0"><i class="bi bi-info-circle me-1"></i>LOA digenerate untuk presenter dengan Full Paper diterima.</div>
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
  <div class="modal-dialog modal-dialog-centered">
    <form action="<?= site_url('admin/dokumen/generateBulkSertifikat') ?>" method="POST" id="bulkSertifikatForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-stars me-2"></i>Generate Bulk Sertifikat</h5>
        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label">Event *</label>
          <select class="form-select" name="event_id" required>
            <option value="">-- Pilih Event --</option>
            <?php foreach ($events as $e): ?>
              <option value="<?= (int)$e['id'] ?>"><?= esc($e['title'] ?? 'No Title') ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="alert alert-warning mb-0"><i class="bi bi-exclamation-triangle me-1"></i>Sertifikat dibuat untuk semua peserta yang hadir.</div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-secondary" type="submit"><i class="bi bi-stars me-1"></i>Generate Sertifikat</button>
      </div>
    </form>
  </div>
</div>

<script>
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
      const hasLoa   = !!u.has_loa;
      const role     = (u.role||'').toString();
      const fpStatus = (u.fp_status||'').toString().toLowerCase();
      const eligible = !!u.eligible;

      // badge LOA
      const badgeLoa = hasLoa
        ? '<span class="badge bg-success-subtle text-success border border-success-subtle">Sudah dapat LOA</span>'
        : '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Belum dapat LOA</span>';

      // badge FP (hijau kalau accepted, abu jika selain itu/kosong)
      const isAccepted = fpStatus === 'accepted';
      const textFp = isAccepted ? 'FP Accepted' : (fpStatus ? ('FP '+fpStatus.charAt(0).toUpperCase()+fpStatus.slice(1)) : 'FP belum accepted');
      const badgeFp = `<span class="badge ${isAccepted ? 'badge-fp-accepted' : 'badge-fp-other'} ms-1">${textFp}</span>`;

      const badgeRole = role
        ? `<span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1">${role}</span>`
        : '';

      const $row = $(`
        <div class="picklist-item d-flex justify-content-between align-items-start mb-2" data-id="${u.id_user}">
          <div>
            <div class="fw-semibold">${escapeHtml(u.nama_lengkap||'-')}</div>
            <div class="small text-muted">${escapeHtml(u.email||'')}</div>
          </div>
          <div class="text-end">
            ${badgeLoa} ${badgeFp} ${badgeRole}
          </div>
        </div>
      `);

      $row.on('click', function(){
        if(hasLoa){
          Swal.fire('Info','User ini sudah punya LOA.','info');
          return;
        }
        if(!eligible){
          Swal.fire('Tidak memenuhi syarat','LOA hanya untuk Presenter dengan Full Paper ACCEPTED.','warning');
          return;
        }
        $('.picklist-item', $loaWrap).removeClass('active');
        $(this).addClass('active');
        $loaHidden.val($(this).data('id'));
      });

      $loaWrap.append($row);
    });
  }

  function escapeHtml(str){ return (str||'').replace(/[&<>"']/g, s=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;', "'":'&#039;' }[s])); }

  $loaEvent.on('change', function(){
    const id = $(this).val();
    $loaWrap.html('<div class="text-muted small">Memuat user...</div>');
    $loaHidden.val('');
    $loaCount.addClass('d-none').text('0 ditemukan');
    if(!id){ $loaWrap.html('<div class="text-muted small">Pilih event terlebih dahulu.</div>'); return; }
    $.get('<?= site_url('admin/dokumen/users-for-loa/') ?>'+encodeURIComponent(id))
      .done(res => {
        if(res && res.status==='success'){ renderLoaUsers(res.data||[]); }
        else { $loaWrap.html('<div class="text-danger small">Gagal memuat user.</div>'); }
      })
      .fail(()=> $loaWrap.html('<div class="text-danger small">Gagal memuat user.</div>'));
  });

  // ====== SERTIFIKAT PICK LIST (STYLE SAMA) ======
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
        ? `<span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1">${role}</span>`
        : '';

      const $row = $(`
        <div class="picklist-item d-flex justify-content-between align-items-start mb-2" data-id="${u.id_user}">
          <div>
            <div class="fw-semibold">${escapeHtml(u.nama_lengkap||'-')}</div>
            <div class="small text-muted">${escapeHtml(u.email||'')}</div>
          </div>
          <div class="text-end">
            ${badgeAttend} ${badgeCert} ${badgeRole}
          </div>
        </div>
      `);

      $row.on('click', function(){
        if(hasCert){
          Swal.fire('Info','User ini sudah punya sertifikat.','info');
          return;
        }
        if(!attended){
          Swal.fire('Tidak memenuhi syarat','Sertifikat hanya untuk peserta yang hadir.','warning');
          return;
        }
        $('.picklist-item', $sertWrap).removeClass('active');
        $(this).addClass('active');
        $sertHidden.val($(this).data('id'));
      });

      $sertWrap.append($row);
    });
  }

  $sertEvent.on('change', function(){
    const id = $(this).val();
    $sertWrap.html('<div class="text-muted small">Memuat user...</div>');
    $sertHidden.val('');
    $sertCount.addClass('d-none').text('0 ditemukan');
    if(!id){ $sertWrap.html('<div class="text-muted small">Pilih event terlebih dahulu.</div>'); return; }
    $.get('<?= site_url('admin/dokumen/users-for-certificate/') ?>' + encodeURIComponent(id))
      .done(res => {
        if(res && res.status === 'success'){ renderCertificateUsers(res.data||[]); }
        else { $sertWrap.html('<div class="text-danger small">Gagal memuat peserta.</div>'); }
      })
      .fail(() => { $sertWrap.html('<div class="text-danger small">Gagal memuat peserta.</div>'); });
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

    const btn = $('#loaSubmitBtn'); btn.prop('disabled',true).find('span').text('Memproses...');
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

    const btn = $('#sertifikatSubmitBtn'); btn.prop('disabled',true).find('span').text('Memproses...');
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