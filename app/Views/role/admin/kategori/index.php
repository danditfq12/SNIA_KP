<?php
// ====== DEFAULT VARS ======
$title    = $title ?? 'Manajemen Kategori Abstrak';
$stats    = $stats ?? ['total_kategori'=>0];
$kategori = $kategori ?? [];
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_admin') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- ===================== PAGE HERO (gaya "kelola paper") ===================== -->
      <div class="paper-hero d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
        <div class="d-flex align-items-start gap-3">
          <div class="paper-hero__icon">
            <i class="bi bi-journal-text"></i>
          </div>
          <div>
            <h1 class="h3 mb-1 fw-bold text-white"><?= esc($title) ?></h1>
            <div class="text-white-50 small">Kelola kategori untuk klasifikasi abstrak</div>
            <div class="mt-3 d-flex flex-wrap gap-2">
              <span class="chip chip-light">
                <i class="bi bi-tags me-1"></i>Total Kategori:
                <strong class="ms-1"><?= number_format($stats['total_kategori']) ?></strong>
              </span>
            </div>
          </div>
        </div>

        <button class="btn btn-light btn-sm mt-3 mt-md-0 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahKategori">
          <i class="bi bi-plus-circle me-1"></i>
          <span class="d-none d-sm-inline">Tambah Kategori</span>
          <span class="d-sm-none">Tambah</span>
        </button>
      </div>

      <!-- ===================== TOOLBAR ===================== -->
      <div class="paper-toolbar card border-0 shadow-sm mb-3">
        <div class="card-body py-3 d-flex flex-column flex-md-row align-items-stretch align-items-md-center gap-2">
          <div class="input-group input-group-sm w-100 w-md-50">
            <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
            <input type="text" class="form-control" id="searchInput" placeholder="Cari kategori…">
          </div>

          <div class="ms-md-auto d-flex gap-2">
            <button class="btn btn-outline-secondary btn-sm" id="resetSearch" type="button">
              <i class="bi bi-x-circle me-1"></i>Bersihkan
            </button>
            <button class="btn btn-primary btn-sm d-md-none" data-bs-toggle="modal" data-bs-target="#modalTambahKategori" type="button">
              <i class="bi bi-plus"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- ===================== TABLE WRAPPER ===================== -->
      <div class="card card-paper border-0 shadow-sm">
        <div class="card-header bg-white border-0 pb-0">
          <h5 class="card-title mb-2">Daftar Kategori Abstrak</h5>
          <p class="text-muted small mb-0">Gunakan pencarian untuk memfilter kategori. Aksi tersedia di kolom terakhir.</p>
        </div>

        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="kategoriTable">
              <thead class="table-light sticky-top">
                <tr>
                  <th class="ps-4" style="width:72px">No</th>
                  <th>Nama Kategori</th>
                  <th class="d-none d-md-table-cell">Deskripsi</th>
                  <th width="120" class="text-center">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($kategori)): ?>
                  <tr>
                    <td colspan="4" class="py-5">
                      <div class="empty-state text-center">
                        <div class="empty-state__icon">
                          <i class="bi bi-inbox"></i>
                        </div>
                        <h6 class="mb-1">Belum ada kategori</h6>
                        <div class="text-muted small mb-3">Buat kategori pertama Anda untuk mulai mengelompokkan abstrak.</div>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambahKategori">
                          <i class="bi bi-plus-circle me-1"></i>Tambah Kategori
                        </button>
                      </div>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($kategori as $index => $k): ?>
                    <tr>
                      <td class="ps-4"><?= $index + 1 ?></td>
                      <td>
                        <div class="fw-semibold text-dark"><?= esc($k['nama_kategori']) ?></div>
                        <div class="d-md-none small text-muted"><?= esc($k['deskripsi']) ?></div>
                      </td>
                      <td class="d-none d-md-table-cell text-muted">
                        <?= esc($k['deskripsi']) ?: '-' ?>
                      </td>
                      <td class="text-center">
                        <div class="btn-group" role="group" aria-label="Aksi Kategori">
                          <button class="btn btn-action btn-outline-primary btn-sm" 
                                  onclick="editKategori(<?= (int)$k['id_kategori'] ?>)" title="Edit">
                            <i class="bi bi-pencil"></i>
                          </button>
                          <button class="btn btn-action btn-outline-danger btn-sm" 
                                  onclick="confirmDelete(<?= (int)$k['id_kategori'] ?>, '<?= esc($k['nama_kategori']) ?>')" title="Hapus">
                            <i class="bi bi-trash"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <?php if (!empty($kategori)): ?>
        <div class="card-footer bg-white border-0 py-3 d-flex justify-content-between align-items-center small text-muted">
          <div><i class="bi bi-info-circle me-1"></i>Menampilkan <?= count($kategori) ?> kategori</div>
          <a href="javascript:void(0)" class="text-decoration-none" onclick="window.scrollTo({top:0,behavior:'smooth'})">
            Kembali ke atas <i class="bi bi-arrow-up-short"></i>
          </a>
        </div>
        <?php endif; ?>
      </div>

    </div>
  </main>
</div>

<!-- ===================== MODAL: TAMBAH KATEGORI ===================== -->
<div class="modal fade" id="modalTambahKategori" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content modal-paper">
      <div class="modal-header modal-paper__header">
        <h5 class="modal-title text-white">
          <i class="bi bi-plus-circle me-2"></i>Tambah Kategori Baru
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form id="formTambahKategori">
        <div class="modal-body">
          <div class="mb-3">
            <label for="nama_kategori" class="form-label">Nama Kategori <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="nama_kategori" name="nama_kategori"
                   placeholder="Contoh: Artificial Intelligence" required>
            <div class="invalid-feedback"></div>
          </div>
          <div class="mb-3">
            <label for="deskripsi" class="form-label">Deskripsi</label>
            <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"
                      placeholder="Deskripsi singkat tentang kategori ini..."></textarea>
            <div class="form-text">Opsional - maksimal 500 karakter</div>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">
            <span class="spinner-border spinner-border-sm me-1 d-none" id="loadingTambah"></span>
            Simpan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ===================== MODAL: EDIT KATEGORI ===================== -->
<div class="modal fade" id="modalEditKategori" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content modal-paper">
      <div class="modal-header modal-paper__header">
        <h5 class="modal-title text-white">
          <i class="bi bi-pencil me-2"></i>Edit Kategori
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form id="formEditKategori">
        <input type="hidden" id="edit_id_kategori" name="id_kategori">
        <div class="modal-body">
          <div class="mb-3">
            <label for="edit_nama_kategori" class="form-label">Nama Kategori <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="edit_nama_kategori" name="nama_kategori" required>
            <div class="invalid-feedback"></div>
          </div>
          <div class="mb-3">
            <label for="edit_deskripsi" class="form-label">Deskripsi</label>
            <textarea class="form-control" id="edit_deskripsi" name="deskripsi" rows="3"></textarea>
            <div class="form-text">Opsional - maksimal 500 karakter</div>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">
            <span class="spinner-border spinner-border-sm me-1 d-none" id="loadingEdit"></span>
            Perbarui
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ===================== SCRIPTS ===================== -->
<!-- jQuery & SweetAlert2 tetap sesuai aslinya -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.1/sweetalert2.min.js"></script>

<script>
// Pastikan DOM siap
$(document).ready(function() {
  // Search sederhana
  $('#searchInput').on('keyup', function() {
    const value = $(this).val().toLowerCase();
    $('#kategoriTable tbody tr').filter(function() {
      const hasColspan = $(this).find('td[colspan]').length > 0;
      if (!hasColspan) $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
    });
  });

  // Reset pencarian
  $('#resetSearch').on('click', function() {
    $('#searchInput').val('').trigger('keyup');
    $('#searchInput').focus();
  });

  // Tambah
  $('#formTambahKategori').on('submit', function(e) {
    e.preventDefault();
    const form = $(this);
    const btn  = form.find('button[type="submit"]');
    const spin = $('#loadingTambah');
    form.find('.is-invalid').removeClass('is-invalid');
    spin.removeClass('d-none'); btn.prop('disabled', true);

    $.ajax({
      url: '<?= site_url('admin/kategori/store') ?>',
      type: 'POST',
      data: form.serialize(),
      dataType: 'json',
      success: function(res){
        if(res.success){
          $('#modalTambahKategori').modal('hide');
          showAlert('success', res.message);
          setTimeout(()=>location.reload(), 900);
        }else{
          if(res.errors){ handleFormErrors(form, res.errors); }
          else{ showAlert('error', res.message || 'Terjadi kesalahan.'); }
        }
      },
      error: function(){ showAlert('error', 'Terjadi kesalahan sistem'); },
      complete: function(){ spin.addClass('d-none'); btn.prop('disabled', false); }
    });
  });

  // Edit
  $('#formEditKategori').on('submit', function(e) {
    e.preventDefault();
    const form = $(this);
    const id   = $('#edit_id_kategori').val();
    if(!id) return showAlert('error','ID kategori tidak ditemukan');

    const btn  = form.find('button[type="submit"]');
    const spin = $('#loadingEdit');
    form.find('.is-invalid').removeClass('is-invalid');
    spin.removeClass('d-none'); btn.prop('disabled', true);

    $.ajax({
      url: `<?= site_url('admin/kategori/update') ?>/${id}`,
      type: 'POST',
      data: form.serialize(),
      dataType: 'json',
      success: function(res){
        if(res.success){
          $('#modalEditKategori').modal('hide');
          showAlert('success', res.message);
          setTimeout(()=>location.reload(), 900);
        }else{
          if(res.errors){ handleFormErrors(form, res.errors); }
          else{ showAlert('error', res.message || 'Terjadi kesalahan.'); }
        }
      },
      error: function(){ showAlert('error','Terjadi kesalahan sistem'); },
      complete: function(){ spin.addClass('d-none'); btn.prop('disabled', false); }
    });
  });

  // Reset form saat modal ditutup
  $('#modalTambahKategori, #modalEditKategori').on('hidden.bs.modal', function() {
    const form = $(this).find('form');
    if (form.length) {
      form[0].reset();
      form.find('.is-invalid').removeClass('is-invalid');
    }
  });
});

// Edit (load data)
function editKategori(id){
  if(!id) return showAlert('error','ID kategori tidak valid');

  $.ajax({
    url: `<?= site_url('admin/kategori/show') ?>/${id}`,
    type: 'GET',
    dataType: 'json',
    success: function(res){
      if(res.success && res.data){
        const d = res.data;
        $('#edit_id_kategori').val(d.id_kategori);
        $('#edit_nama_kategori').val(d.nama_kategori || '');
        $('#edit_deskripsi').val(d.deskripsi || '');
        $('#modalEditKategori').modal('show');
      }else{
        showAlert('error', res.message || 'Data tidak ditemukan');
      }
    },
    error: function(){ showAlert('error','Gagal memuat data kategori'); }
  });
}

// Konfirmasi hapus
function confirmDelete(id, nama){
  if(!id) return showAlert('error','Data tidak valid');

  Swal.fire({
    title: 'Hapus Kategori?',
    html: `Yakin ingin menghapus kategori <strong>"${nama}"</strong>?<br><small class="text-muted">Kategori yang sedang digunakan tidak dapat dihapus.</small>`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc3545',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Ya, Hapus',
    cancelButtonText: 'Batal',
    reverseButtons: true
  }).then((r)=>{
    if(r.isConfirmed) deleteKategori(id);
  });
}

// Hapus
function deleteKategori(id){
  $.ajax({
    url: `<?= site_url('admin/kategori/delete') ?>/${id}`,
    type: 'POST',
    data: {'_method':'DELETE'},
    dataType: 'json',
    headers: { 'X-HTTP-Method-Override':'DELETE', 'X-Requested-With':'XMLHttpRequest' },
    success: function(res){
      if(res.success){
        showAlert('success', res.message);
        setTimeout(()=>location.reload(), 900);
      }else{
        showAlert('error', res.message || 'Gagal menghapus kategori');
      }
    },
    error: function(){ showAlert('error','Gagal menghapus kategori'); }
  });
}

// Helpers
function handleFormErrors(form, errors){
  if(typeof errors === 'object'){
    Object.keys(errors).forEach((f)=>{
      const input = form.find(`[name="${f}"]`);
      if(input.length){
        input.addClass('is-invalid');
        const fb = input.siblings('.invalid-feedback');
        if(fb.length) fb.text(errors[f]);
      }
    });
  }
}
function showAlert(type, message){
  if(!message) return;
  const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
  const icon = type === 'success' ? 'check-circle' : 'exclamation-triangle';
  const html = `
    <div class="alert ${alertClass} alert-dismissible fade show shadow-sm" role="alert">
      <i class="bi bi-${icon} me-2"></i>${message}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>`;
  $('.alert').remove();
  const container = $('#content .container-fluid');
  if(container.length) container.prepend(html);
  setTimeout(()=>{$('.alert').fadeOut();}, 5000);
}
</script>

<!-- ===================== STYLES (gaya "kelola paper") ===================== -->
<style>
  :root{
    --paper-primary: #4f46e5;   /* indigo-600 */
    --paper-secondary: #7c3aed; /* violet-600 */
    --paper-muted: #64748b;     /* slate-500 */
  }

  .paper-hero{
    background: linear-gradient(135deg, var(--paper-primary) 0%, var(--paper-secondary) 100%);
    border-radius: 16px;
    padding: 22px 20px;
    box-shadow: 0 10px 28px rgba(79,70,229,.25);
  }
  .paper-hero__icon{
    width: 48px; height: 48px; border-radius: 12px;
    display: grid; place-items: center;
    background: rgba(255,255,255,.2);
    color: #fff; font-size: 22px;
    box-shadow: inset 0 0 0 1px rgba(255,255,255,.25);
  }
  .chip{
    display:inline-flex; align-items:center; gap:.35rem;
    padding:.35rem .6rem; border-radius:999px; font-size:.78rem; font-weight:600;
  }
  .chip-light{ background: rgba(255,255,255,.15); color:#fff; border:1px solid rgba(255,255,255,.22); }

  .paper-toolbar .form-control:focus{
    box-shadow: 0 0 0 .15rem rgba(79,70,229,.25);
    border-color: var(--paper-primary);
  }

  .card-paper .card-title{ font-weight:700; }
  .table thead th{ font-size:.85rem; letter-spacing:.3px; text-transform:none; }
  .table tbody td{ vertical-align: middle; }

  .btn-action{
    width: 34px; height: 34px; display: inline-grid; place-items:center;
  }

  .empty-state{
    max-width: 460px; margin: 0 auto;
  }
  .empty-state__icon{
    width: 72px; height: 72px; margin: 0 auto .75rem;
    border-radius: 16px; display:grid; place-items:center;
    background: #f1f5f9; color: var(--paper-muted); font-size: 30px;
  }

  .modal-paper{ overflow: hidden; border: 0; box-shadow: 0 14px 40px rgba(0,0,0,.18); }
  .modal-paper__header{
    background: linear-gradient(135deg, var(--paper-primary) 0%, var(--paper-secondary) 100%);
    color:#fff; border:0;
  }
</style>

<?= $this->include('partials/footer') ?>