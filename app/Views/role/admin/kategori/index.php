<?php
// ====== DEFAULT VARS ======
$title   = $title ?? 'Manajemen Kategori Abstrak';
$stats   = $stats ?? ['total_kategori'=>0];
$kategori = $kategori ?? [];
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_admin') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">
      
      <!-- Page Header -->
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
        <div>
          <h1 class="h3 mb-0 fw-bold text-dark"><?= $title ?></h1>
          <p class="text-muted mb-0">Kelola kategori untuk klasifikasi abstrak</p>
        </div>
        <button class="btn btn-primary btn-sm mt-3 mt-md-0" data-bs-toggle="modal" data-bs-target="#modalTambahKategori">
          <i class="bi bi-plus-circle me-1"></i>
          <span class="d-none d-sm-inline">Tambah Kategori</span>
          <span class="d-sm-none">Tambah</span>
        </button>
      </div>

      <!-- Stats Cards -->
      <div class="row mb-4">
        <div class="col-md-6 col-lg-3 mb-3">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                  <div class="bg-primary bg-gradient rounded p-3">
                    <i class="bi bi-tags text-white fs-4"></i>
                  </div>
                </div>
                <div class="flex-grow-1 ms-3">
                  <div class="text-muted small">Total Kategori</div>
                  <div class="fs-4 fw-bold text-dark"><?= number_format($stats['total_kategori']) ?></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Table Card -->
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
          <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
            <h5 class="card-title mb-0">Daftar Kategori Abstrak</h5>
            <div class="mt-2 mt-md-0">
              <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="Cari kategori...">
            </div>
          </div>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0" id="kategoriTable">
              <thead class="table-light">
                <tr>
                  <th class="ps-4">No</th>
                  <th>Nama Kategori</th>
                  <th class="d-none d-md-table-cell">Deskripsi</th>
                  <th width="120" class="text-center">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($kategori)): ?>
                <tr>
                  <td colspan="4" class="text-center py-4 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                    Belum ada kategori
                  </td>
                </tr>
                <?php else: ?>
                  <?php foreach ($kategori as $index => $k): ?>
                  <tr>
                    <td class="ps-4 align-middle"><?= $index + 1 ?></td>
                    <td class="align-middle">
                      <div class="fw-semibold text-dark"><?= esc($k['nama_kategori']) ?></div>
                      <div class="d-md-none small text-muted"><?= esc($k['deskripsi']) ?></div>
                    </td>
                    <td class="d-none d-md-table-cell align-middle text-muted">
                      <?= esc($k['deskripsi']) ?: '-' ?>
                    </td>
                    <td class="align-middle text-center">
                      <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-outline-primary" 
                                onclick="editKategori(<?= $k['id_kategori'] ?>)"
                                title="Edit">
                          <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" 
                                onclick="confirmDelete(<?= $k['id_kategori'] ?>, '<?= esc($k['nama_kategori']) ?>')"
                                title="Hapus">
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
      </div>
    </div>
  </main>
</div>

<!-- Modal Tambah Kategori -->
<div class="modal fade" id="modalTambahKategori" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="bi bi-plus-circle me-2"></i>
          Tambah Kategori Baru
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">
            <span class="spinner-border spinner-border-sm me-1 d-none" id="loadingTambah"></span>
            Simpan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Edit Kategori -->
<div class="modal fade" id="modalEditKategori" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="bi bi-pencil me-2"></i>
          Edit Kategori
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">
            <span class="spinner-border spinner-border-sm me-1 d-none" id="loadingEdit"></span>
            Perbarui
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- JavaScript -->
<!-- Pastikan jQuery dimuat terlebih dahulu -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.1/sweetalert2.min.js"></script>

<script>
// Pastikan DOM sudah siap dan jQuery tersedia
$(document).ready(function() {
    console.log('jQuery loaded:', typeof $ !== 'undefined');
    
    // Search functionality
    const searchInput = $('#searchInput');
    if (searchInput.length) {
        searchInput.on('keyup', function() {
            const value = $(this).val().toLowerCase();
            $('#kategoriTable tbody tr').filter(function() {
                const hasColspan = $(this).find('td[colspan]').length > 0;
                if (!hasColspan) {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                }
            });
        });
    }

    // Form Tambah Kategori
    $('#formTambahKategori').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const loading = $('#loadingTambah');
        
        // Reset validasi
        form.find('.is-invalid').removeClass('is-invalid');
        
        // Show loading
        if (loading.length) {
            loading.removeClass('d-none');
        }
        submitBtn.prop('disabled', true);
        
        $.ajax({
            url: '<?= site_url('admin/kategori/store') ?>',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#modalTambahKategori').modal('hide');
                    showAlert('success', response.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    if (response.errors) {
                        handleFormErrors(form, response.errors);
                    } else {
                        showAlert('error', response.message);
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Ajax error:', error);
                showAlert('error', 'Terjadi kesalahan sistem');
            },
            complete: function() {
                if (loading.length) {
                    loading.addClass('d-none');
                }
                submitBtn.prop('disabled', false);
            }
        });
    });

    // Form Edit Kategori
    $('#formEditKategori').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const loading = $('#loadingEdit');
        const id = $('#edit_id_kategori').val();
        
        if (!id) {
            showAlert('error', 'ID kategori tidak ditemukan');
            return;
        }
        
        // Reset validasi
        form.find('.is-invalid').removeClass('is-invalid');
        
        // Show loading
        if (loading.length) {
            loading.removeClass('d-none');
        }
        submitBtn.prop('disabled', true);
        
        $.ajax({
            url: `<?= site_url('admin/kategori/update') ?>/${id}`,
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#modalEditKategori').modal('hide');
                    showAlert('success', response.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    if (response.errors) {
                        handleFormErrors(form, response.errors);
                    } else {
                        showAlert('error', response.message);
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Ajax error:', error);
                showAlert('error', 'Terjadi kesalahan sistem');
            },
            complete: function() {
                if (loading.length) {
                    loading.addClass('d-none');
                }
                submitBtn.prop('disabled', false);
            }
        });
    });

    // Reset form ketika modal ditutup
    $('#modalTambahKategori, #modalEditKategori').on('hidden.bs.modal', function() {
        const form = $(this).find('form');
        if (form.length) {
            form[0].reset();
            form.find('.is-invalid').removeClass('is-invalid');
        }
    });
});

// Fungsi untuk edit kategori
function editKategori(id) {
    if (!id) {
        showAlert('error', 'ID kategori tidak valid');
        return;
    }
    
    $.ajax({
        url: `<?= site_url('admin/kategori/show') ?>/${id}`,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                const data = response.data;
                $('#edit_id_kategori').val(data.id_kategori);
                $('#edit_nama_kategori').val(data.nama_kategori || '');
                $('#edit_deskripsi').val(data.deskripsi || '');
                $('#modalEditKategori').modal('show');
            } else {
                showAlert('error', response.message || 'Data tidak ditemukan');
            }
        },
        error: function(xhr, status, error) {
            console.error('Ajax error:', error);
            showAlert('error', 'Gagal memuat data kategori');
        }
    });
}

// Fungsi untuk konfirmasi delete
function confirmDelete(id, nama) {
    if (!id || !nama) {
        showAlert('error', 'Data tidak valid');
        return;
    }
    
    // Cek apakah SweetAlert2 tersedia
    if (typeof Swal === 'undefined') {
        if (confirm(`Yakin ingin menghapus kategori "${nama}"?`)) {
            deleteKategori(id);
        }
        return;
    }
    
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
    }).then((result) => {
        if (result.isConfirmed) {
            deleteKategori(id);
        }
    });
}

// Fungsi untuk delete kategori
function deleteKategori(id) {
    if (!id) {
        showAlert('error', 'ID kategori tidak valid');
        return;
    }
    
    $.ajax({
        url: `<?= site_url('admin/kategori/delete') ?>/${id}`,
        type: 'POST',
        data: {
            '_method': 'DELETE'
        },
        dataType: 'json',
        headers: {
            'X-HTTP-Method-Override': 'DELETE',
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert('error', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Ajax error:', error);
            showAlert('error', 'Gagal menghapus kategori');
        }
    });
}

// Fungsi helper untuk menangani error form
function handleFormErrors(form, errors) {
    if (typeof errors === 'object') {
        Object.keys(errors).forEach(field => {
            const input = form.find(`[name="${field}"]`);
            if (input.length) {
                input.addClass('is-invalid');
                const feedback = input.siblings('.invalid-feedback');
                if (feedback.length) {
                    feedback.text(errors[field]);
                }
            }
        });
    }
}

// Fungsi helper untuk alert
function showAlert(type, message) {
    if (!message) return;
    
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const icon = type === 'success' ? 'check-circle' : 'exclamation-triangle';
    
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            <i class="bi bi-${icon} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Hapus alert lama
    $('.alert').remove();
    
    // Tambahkan alert baru
    const container = $('#content .container-fluid');
    if (container.length) {
        container.prepend(alertHtml);
    }
    
    // Auto hide setelah 5 detik
    setTimeout(() => {
        $('.alert').fadeOut();
    }, 5000);
}
</script>

<?= $this->include('partials/footer') ?>