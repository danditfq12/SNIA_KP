<?php
// ====== DEFAULT VARS ======
$title            = $title ?? 'Kelola Voucher';
$vouchers         = $vouchers ?? []; // id_voucher,kode_voucher,tipe,nilai,kuota,used_count,masa_berlaku,status,is_expired,remaining
$total_vouchers   = (int)($total_vouchers   ?? count($vouchers));
$active_vouchers  = (int)($active_vouchers  ?? count(array_filter($vouchers, fn($v)=>($v['status']??'')==='aktif')));
$expired_vouchers = (int)($expired_vouchers ?? count(array_filter($vouchers, fn($v)=>($v['status']??'')==='expired')));
$used_vouchers    = (int)($used_vouchers    ?? count(array_filter($vouchers, fn($v)=>($v['status']??'')==='habis')));
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
            <i class="bi bi-ticket-perforated me-2"></i>Kelola Voucher
          </h3>
          <div class="text-white-50">Kelola voucher diskon untuk pembayaran registrasi event</div>
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
              <div class="stat-icon bg-primary"><i class="bi bi-ticket-perforated"></i></div>
              <div class="ms-3">
                <div class="stat-number"><?= number_format($total_vouchers) ?></div>
                <div class="text-muted">Total Voucher</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-success"><i class="bi bi-check2-circle"></i></div>
              <div class="ms-3">
                <div class="stat-number"><?= number_format($active_vouchers) ?></div>
                <div class="text-muted">Voucher Aktif</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-warning"><i class="bi bi-clock-history"></i></div>
              <div class="ms-3">
                <div class="stat-number"><?= number_format($expired_vouchers) ?></div>
                <div class="text-muted">Expired</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-danger"><i class="bi bi-slash-circle"></i></div>
              <div class="ms-3">
                <div class="stat-number"><?= number_format($used_vouchers) ?></div>
                <div class="text-muted">Kuota Habis</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- TABLE WRAPPER -->
      <div class="card shadow-sm">
        <div class="card-header bg-gradient-primary text-white">
          <div class="d-flex flex-wrap justify-content-between align-items-center">
            <h5 class="mb-2 mb-md-0"><i class="bi bi-list-ul me-2"></i>Daftar Voucher</h5>
            <div class="header-actions">
              <button class="btn btn-light btn-custom" data-bs-toggle="modal" data-bs-target="#createModal" data-bs-toggle-second="tooltip" data-bs-title="Tambah voucher baru">
                <i class="bi bi-plus-lg me-1"></i>Tambah Voucher
              </button>
              <button class="btn btn-light btn-custom" onclick="exportData()" data-bs-toggle="tooltip" data-bs-title="Export data ke file">
                <i class="bi bi-download me-1"></i>Export
              </button>
            </div>
          </div>
        </div>

        <div class="card-body">
          <div class="table-responsive">
            <table id="voucherTable" class="table table-hover align-middle">
              <thead>
                <tr>
                  <th>No</th>
                  <th>Kode Voucher</th>
                  <th>Tipe</th>
                  <th>Nilai</th>
                  <th>Kuota</th>
                  <th>Digunakan</th>
                  <th>Sisa</th>
                  <th>Masa Berlaku</th>
                  <th>Status</th>
                  <th style="min-width:240px;">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php $no=1; foreach ($vouchers as $v): 
                  $id   = (int)($v['id_voucher'] ?? 0);
                  $kode = $v['kode_voucher'] ?? '-';
                  $tipe = $v['tipe'] ?? 'fixed'; // fixed|percentage
                  $nilai= (float)($v['nilai'] ?? 0);
                  $kuota= (int)($v['kuota'] ?? 0);
                  $used = (int)($v['used_count'] ?? 0);
                  $sisa = $v['remaining'] ?? max(0, $kuota - $used);
                  $tgl  = $v['masa_berlaku'] ?? date('Y-m-d');
                  $status = strtolower($v['status'] ?? 'nonaktif');
                  $isExpired = !empty($v['is_expired']);
                  $statusClass = [
                    'aktif'    => 'success',
                    'nonaktif' => 'secondary',
                    'expired'  => 'warning',
                    'habis'    => 'danger'
                  ][$status] ?? 'secondary';
                ?>
                <tr>
                  <td><?= $no++ ?></td>
                  <td><span class="voucher-code badge bg-dark"><?= esc($kode) ?></span></td>
                  <td>
                    <?php if ($tipe==='percentage'): ?>
                      <span class="badge bg-info">Persentase</span>
                    <?php else: ?>
                      <span class="badge bg-secondary">Fixed</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($tipe==='percentage'): ?>
                      <strong><?= (int)$nilai ?>%</strong>
                    <?php else: ?>
                      <strong>Rp <?= number_format($nilai,0,',','.') ?></strong>
                    <?php endif; ?>
                  </td>
                  <td><?= number_format($kuota) ?></td>
                  <td><?= number_format($used) ?></td>
                  <td>
                    <span class="badge bg-<?= ($sisa>0?'success':'danger') ?>"><?= number_format($sisa) ?></span>
                  </td>
                  <td>
                    <?= date('d/m/Y', strtotime($tgl)) ?>
                    <?php if ($isExpired): ?>
                      <br><small class="text-danger"><i class="bi bi-clock"></i> Expired</small>
                    <?php endif; ?>
                  </td>
                  <td><span class="badge bg-<?= $statusClass ?>"><?= ucfirst($status) ?></span></td>
                  <td>
                    <!-- Aksi: lebih lega, soft color, tooltip -->
                    <div class="action-buttons">
                      <button class="btn-action btn-soft-info" data-bs-toggle="tooltip" data-bs-title="Lihat detail"
                              onclick="viewDetail(<?= $id ?>)">
                        <i class="bi bi-eye"></i>
                      </button>

                      <button class="btn-action btn-soft-warning" data-bs-toggle="tooltip" data-bs-title="Edit voucher"
                              onclick="editVoucher(<?= $id ?>)">
                        <i class="bi bi-pencil-square"></i>
                      </button>

                      <?php if (!in_array($status,['expired','habis'])): ?>
                        <?php if ($status==='aktif'): ?>
                          <button class="btn-action btn-soft-secondary" data-bs-toggle="tooltip" data-bs-title="Nonaktifkan"
                                  onclick="toggleStatus(<?= $id ?>)">
                            <i class="bi bi-pause-fill"></i>
                          </button>
                        <?php else: ?>
                          <button class="btn-action btn-soft-success" data-bs-toggle="tooltip" data-bs-title="Aktifkan"
                                  onclick="toggleStatus(<?= $id ?>)">
                            <i class="bi bi-play-fill"></i>
                          </button>
                        <?php endif; ?>
                      <?php endif; ?>

                      <button class="btn-action btn-soft-danger" data-bs-toggle="tooltip" data-bs-title="Hapus voucher"
                              onclick="deleteVoucher(<?= $id ?>)">
                        <i class="bi bi-trash3"></i>
                      </button>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- MODAL: CREATE -->
      <div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-plus-lg me-2"></i>Tambah Voucher Baru</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <form action="/admin/voucher/store" method="POST" id="createForm">
            <?= csrf_field() ?>
            <div class="modal-body">
              <div class="row">
                <div class="col-md-6"><div class="mb-3">
                  <label class="form-label">Kode Voucher <span class="text-danger">*</span></label>
                  <!-- HAPUS tombol generate; input manual -->
                  <input type="text" class="form-control" name="kode_voucher" id="createKode" required maxlength="50" placeholder="KODEUNIK">
                  <div class="form-text">Kode harus unik, huruf besar</div>
                </div></div>
                <div class="col-md-6"><div class="mb-3">
                  <label class="form-label">Tipe Diskon <span class="text-danger">*</span></label>
                  <select class="form-select" name="tipe" id="createTipe" required>
                    <option value="">-- Pilih Tipe --</option>
                    <option value="percentage">Persentase (%)</option>
                    <option value="fixed">Fixed Amount (Rp)</option>
                  </select>
                </div></div>
              </div>

              <div class="row">
                <div class="col-md-6"><div class="mb-3">
                  <label class="form-label">Nilai Diskon <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <span class="input-group-text" id="createPrefix">%</span>
                    <input type="number" class="form-control" name="nilai" id="createNilai" required min="1" placeholder="0">
                  </div>
                  <div class="form-text" id="createHelp">Maksimal 100 untuk persentase</div>
                </div></div>
                <div class="col-md-6"><div class="mb-3">
                  <label class="form-label">Kuota Penggunaan <span class="text-danger">*</span></label>
                  <input type="number" class="form-control" name="kuota" required min="1" placeholder="100">
                  <div class="form-text">Jumlah maksimal penggunaan</div>
                </div></div>
              </div>

              <div class="mb-3">
                <label class="form-label">Masa Berlaku <span class="text-danger">*</span></label>
                <input type="date" class="form-control" name="masa_berlaku" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                <div class="form-text">Tanggal terakhir voucher dapat digunakan</div>
              </div>
            </div>
            <div class="modal-footer">
              <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Batal</button>
              <button class="btn btn-success" type="submit"><i class="bi bi-save me-1"></i>Simpan Voucher</button>
            </div>
          </form>
        </div></div>
      </div>

      <!-- MODAL: EDIT -->
      <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Voucher</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <form action="" method="POST" id="editForm">
            <?= csrf_field() ?>
            <div class="modal-body">
              <div class="row">
                <div class="col-md-6"><div class="mb-3">
                  <label class="form-label">Kode Voucher <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" name="kode_voucher" id="editKode" required maxlength="50">
                </div></div>
                <div class="col-md-6"><div class="mb-3">
                  <label class="form-label">Tipe Diskon <span class="text-danger">*</span></label>
                  <select class="form-select" name="tipe" id="editTipe" required>
                    <option value="percentage">Persentase (%)</option>
                    <option value="fixed">Fixed Amount (Rp)</option>
                  </select>
                </div></div>
              </div>

              <div class="row">
                <div class="col-md-6"><div class="mb-3">
                  <label class="form-label">Nilai Diskon <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <span class="input-group-text" id="editPrefix">%</span>
                    <input type="number" class="form-control" name="nilai" id="editNilai" required min="1">
                  </div>
                </div></div>
                <div class="col-md-6"><div class="mb-3">
                  <label class="form-label">Kuota Penggunaan <span class="text-danger">*</span></label>
                  <input type="number" class="form-control" name="kuota" id="editKuota" required min="1">
                </div></div>
              </div>

              <div class="row">
                <div class="col-md-6"><div class="mb-3">
                  <label class="form-label">Masa Berlaku <span class="text-danger">*</span></label>
                  <input type="date" class="form-control" name="masa_berlaku" id="editMasaBerlaku" required>
                </div></div>
                <div class="col-md-6"><div class="mb-3">
                  <label class="form-label">Status <span class="text-danger">*</span></label>
                  <select class="form-select" name="status" id="editStatus" required>
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Nonaktif</option>
                    <option value="expired">Expired</option>
                    <option value="habis">Habis</option>
                  </select>
                </div></div>
              </div>
            </div>
            <div class="modal-footer">
              <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Batal</button>
              <button class="btn btn-warning" type="submit"><i class="bi bi-save me-1"></i>Update Voucher</button>
            </div>
          </form>
        </div></div>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<!-- STYLES (khusus halaman ini) -->
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

  /* Header actions */
  .header-actions{ display:flex; flex-wrap:wrap; gap:.5rem; }
  .btn-custom{ border-radius:10px; padding:.55rem .9rem; font-weight:600; transition:.2s; }
  .btn-custom:hover{ transform:translateY(-1px); box-shadow:0 6px 14px rgba(15,23,42,.12); }

  /* Aksi button (soft) */
  .action-buttons{ display:flex; flex-wrap:wrap; align-items:center; gap:.5rem; }
  .btn-action{
    display:inline-flex; align-items:center; justify-content:center; gap:.35rem;
    padding:.45rem .6rem; border-radius:10px; border:1px solid #e8eef5; background:#fff; color:#334155;
    box-shadow:0 2px 6px rgba(15,23,42,.04); transition:.18s ease; font-weight:600;
  }
  .btn-action:hover{ transform:translateY(-1px); box-shadow:0 8px 18px rgba(15,23,42,.10); }
  .btn-soft-info{     background:rgba(6,182,212,.12);   color:#0e7490;  border-color:rgba(6,182,212,.25); }
  .btn-soft-warning{  background:rgba(245,158,11,.12);  color:#92400e;  border-color:rgba(245,158,11,.25); }
  .btn-soft-success{  background:rgba(16,185,129,.12);  color:#065f46;  border-color:rgba(16,185,129,.25); }
  .btn-soft-secondary{background:rgba(100,116,139,.12); color:#334155;  border-color:rgba(100,116,139,.25); }
  .btn-soft-danger{   background:rgba(239,68,68,.12);   color:#991b1b;  border-color:rgba(239,68,68,.25); }

  #voucherTable thead th{ background:#f8fafc; }
  .voucher-code{ font-family: 'Courier New', monospace; font-weight:700; letter-spacing:1px; }
</style>

<!-- SCRIPTS (khusus halaman ini) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.13.4/css/dataTables.bootstrap5.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.12/sweetalert2.min.js"></script>

<script>
  // ===== Init DataTable + Tooltips =====
  function initTooltips(scope=document){
    return [].slice.call(scope.querySelectorAll('[data-bs-toggle="tooltip"]'))
      .map(el => new bootstrap.Tooltip(el));
  }

  $(document).ready(function(){
    const dt = $('#voucherTable').DataTable({
      language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/id.json' },
      order: [[7,'desc']],
      pageLength: 25,
      responsive: true,
      columnDefs: [
        { orderable:false, targets:[9] }
      ]
    });

    initTooltips(document);
    dt.on('draw', function(){ initTooltips(document.getElementById('content')); });

    // Animasi angka ringan
    document.querySelectorAll('.stat-number').forEach(el=>{
      const final = parseInt((el.textContent||'0').replace(/\D/g,'')) || 0;
      let cur=0, step=Math.max(1, Math.round(final/40));
      const intv=setInterval(()=>{
        cur+=step; 
        if(cur>=final){ el.textContent=final.toLocaleString('id-ID'); clearInterval(intv); }
        else { el.textContent=cur.toLocaleString('id-ID'); }
      }, 18);
    });
  });

  // ===== Behavior (fungsi lain tetap) =====
  function exportData(){ window.open('/admin/voucher/export','_blank'); }
  function viewDetail(id){ window.open('/admin/voucher/detail/'+id,'_blank'); }
  function editVoucher(id){
    $.get('/admin/voucher/edit/'+id, function(d){
      $('#editKode').val(d.kode_voucher);
      $('#editTipe').val(d.tipe).trigger('change');
      $('#editNilai').val(d.nilai);
      $('#editKuota').val(d.kuota);
      $('#editMasaBerlaku').val(d.masa_berlaku);
      $('#editStatus').val(d.status);
      $('#editForm').attr('action','/admin/voucher/update/'+id);
      new bootstrap.Modal(document.getElementById('editModal')).show();
    });
  }
  function toggleStatus(id){
    Swal.fire({ title:'Ubah Status Voucher?', text:'Status voucher akan diubah', icon:'question', showCancelButton:true, confirmButtonText:'Ya, Ubah!', cancelButtonText:'Batal' })
      .then(r=>{ if(r.isConfirmed){ location.href = '/admin/voucher/toggle-status/'+id; } });
  }
  function deleteVoucher(id){
    Swal.fire({ title:'Hapus Voucher?', text:'Data tidak dapat dikembalikan setelah dihapus!', icon:'warning', showCancelButton:true, confirmButtonColor:'#d33', cancelButtonColor:'#3085d6', confirmButtonText:'Ya, Hapus!', cancelButtonText:'Batal' })
      .then(r=>{ if(r.isConfirmed){ location.href = '/admin/voucher/delete/'+id; } });
  }

  // Uppercase untuk kode voucher
  document.addEventListener('input', (e)=>{
    if(e.target && e.target.name==='kode_voucher'){
      e.target.value = (e.target.value||'').toUpperCase();
    }
  });

  // Tipe Diskon: sesuaikan prefix & batas nilai
  $('#createTipe, #editTipe').on('change', function() {
    const isCreate = this.id === 'createTipe';
    const prefix = isCreate ? 'create' : 'edit';
    const tipe = this.value;
    if (tipe === 'percentage') {
      $('#'+prefix+'Prefix').text('%');
      $('#'+prefix+'Nilai').attr('max','100');
      if (isCreate) $('#createHelp').text('Maksimal 100 untuk persentase');
    } else {
      $('#'+prefix+'Prefix').text('Rp');
      $('#'+prefix+'Nilai').removeAttr('max');
      if (isCreate) $('#createHelp').text('Nominal dalam rupiah');
    }
  });

  // Flash via SweetAlert
  <?php if (session('success')): ?>
    Swal.fire({ icon:'success', title:'Berhasil!', text:'<?= esc(session('success')) ?>', timer:2800, showConfirmButton:false });
  <?php endif; ?>
  <?php if (session('error')): ?>
    Swal.fire({ icon:'error', title:'Error!', text:'<?= esc(session('error')) ?>' });
  <?php endif; ?>
</script>
