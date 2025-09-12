<?php
  // ====== DEFAULT VARS ======
  $title          = $title ?? 'Manajemen User';
  $users          = $users ?? [];
  $total_users    = (int)($total_users ?? 0);
  $active_users   = (int)($active_users ?? 0);
  $inactive_users = (int)($inactive_users ?? 0);
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_admin') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- HEADER -->
      <div class="header-section d-flex justify-content-between align-items-center mb-3">
        <div>
          <h3 class="welcome-text mb-1">
            <i class="bi bi-people-fill me-2"></i>Manajemen User
          </h3>
          <div class="text-muted">Kelola semua user dalam sistem SNIA</div>
        </div>
        <!-- HAPUS tombol tambah user -->
      </div>

      <!-- KPI 4 KOTAK -->
      <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-primary"><i class="bi bi-people"></i></div>
              <div class="ms-3">
                <div class="stat-number"><?= number_format($total_users) ?></div>
                <div class="text-muted">Total User</div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-success"><i class="bi bi-person-check"></i></div>
              <div class="ms-3">
                <div class="stat-number"><?= number_format($active_users) ?></div>
                <div class="text-muted">User Aktif</div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-warning"><i class="bi bi-person-x"></i></div>
              <div class="ms-3">
                <div class="stat-number"><?= number_format($inactive_users) ?></div>
                <div class="text-muted">User Nonaktif</div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-info"><i class="bi bi-percent"></i></div>
              <div class="ms-3">
                <div class="stat-number">
                  <?= $total_users>0 ? round(($active_users/$total_users)*100) : 0 ?>%
                </div>
                <div class="text-muted">Tingkat Aktivasi</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- TABEL USER -->
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="row g-2 align-items-center mb-3">
            <div class="col-md-6">
              <div class="position-relative">
                <input type="text" class="form-control ps-5" id="searchInput" placeholder="Cari user...">
                <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
              </div>
            </div>
            <div class="col-md-6 text-md-end">
              <select class="form-select d-inline-block w-auto" id="roleFilter">
                <option value="">Semua Role</option>
                <option value="admin">Admin</option>
                <option value="presenter">Presenter</option>
                <option value="audience">Audience</option>
                <option value="reviewer">Reviewer</option>
              </select>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-hover align-middle" id="usersTable">
              <thead class="table-light">
                <tr>
                  <th>User</th>
                  <th>Email</th>
                  <th>Role</th>
                  <th>Status</th>
                  <th>Tanggal Daftar</th>
                  <th class="text-end">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($users)): ?>
                  <?php foreach ($users as $u): ?>
                    <tr>
                      <td>
                        <div class="d-flex align-items-center">
                          <div class="user-avatar me-3"><?= strtoupper(substr($u['nama_lengkap'],0,1)) ?></div>
                          <div>
                            <div class="fw-semibold"><?= esc($u['nama_lengkap']) ?></div>
                            <small class="text-muted">ID: <?= (int)$u['id_user'] ?></small>
                          </div>
                        </div>
                      </td>
                      <td><?= esc($u['email']) ?></td>
                      <td>
                        <?php
                          $roleClass = match($u['role']){
                            'admin' => 'bg-danger',
                            'presenter' => 'bg-primary',
                            'audience' => 'bg-secondary',
                            'reviewer' => 'bg-success',
                            default => 'bg-dark'
                          };
                        ?>
                        <span class="badge-role <?= $roleClass ?> text-white"><?= ucfirst($u['role']) ?></span>
                      </td>
                      <td>
                        <span class="badge <?= ($u['status']==='aktif'?'bg-success':'bg-warning') ?>">
                          <?= ucfirst($u['status']) ?>
                        </span>
                      </td>
                      <td><?= date('d/m/Y H:i', strtotime($u['created_at'])) ?></td>
                      <td class="text-end">
                        <div class="btn-group">
                          <button class="btn btn-sm btn-outline-primary"
                                  onclick="editUser(<?= (int)$u['id_user'] ?>)" title="Edit">
                            <i class="bi bi-pencil-square"></i>
                          </button>
                          <?php if ((int)$u['id_user'] !== (int)session('id_user')): ?>
                            <button class="btn btn-sm btn-outline-danger"
                                    onclick="deleteUser(<?= (int)$u['id_user'] ?>,'<?= esc($u['nama_lengkap']) ?>')" title="Hapus">
                              <i class="bi bi-trash3"></i>
                            </button>
                          <?php endif; ?>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="6" class="text-center py-5">
                      <i class="bi bi-people fs-1 text-muted d-block mb-2"></i>
                      <div class="text-muted">Belum ada user yang terdaftar</div>
                    </td>
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

<!-- Edit ONLY (Tambah user dihapus) -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header bg-primary text-white">
      <h5 class="modal-title"><i class="bi bi-person-gear me-2"></i>Edit User</h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <form id="editUserForm" method="POST">
      <?= csrf_field() ?>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-6"><div class="mb-3">
            <label class="form-label">Nama Lengkap</label>
            <input type="text" class="form-control" name="nama_lengkap" id="editNamaLengkap" required>
          </div></div>
          <div class="col-md-6"><div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" id="editEmail" required>
          </div></div>
        </div>
        <div class="row">
          <div class="col-md-6"><div class="mb-3">
            <label class="form-label">Password <small class="text-muted">(kosongkan jika tidak diubah)</small></label>
            <input type="password" class="form-control" name="password" id="editPassword">
          </div></div>
          <div class="col-md-6"><div class="mb-3">
            <label class="form-label">Role</label>
            <select class="form-select" name="role" id="editRole" required>
              <option value="admin">Admin</option>
              <option value="presenter">Presenter</option>
              <option value="audience">Audience</option>
              <option value="reviewer">Reviewer</option>
            </select>
          </div></div>
        </div>
        <div class="mb-3">
          <label class="form-label">Status</label>
          <select class="form-select" name="status" id="editStatus" required>
            <option value="aktif">Aktif</option>
            <option value="nonaktif">Nonaktif</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Batal</button>
        <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i>Update</button>
      </div>
    </form>
  </div></div>
</div>

<?= $this->include('partials/footer') ?>

<style>
  :root{
    --primary-color:#2563eb; --info-color:#06b6d4; --success-color:#10b981; --warning-color:#f59e0b;
  }
  body{ background:linear-gradient(135deg,#f8fafc 0%,#e2e8f0 100%); font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

  /* HEADER BOX: biru, lebih besar, teks putih */
  .header-section{
    background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%);
    color:#fff;
    padding: 28px 24px;
    border-radius:16px;
    box-shadow:0 8px 28px rgba(0,0,0,.12);
  }
  .header-section .welcome-text{
    color:#fff;
    font-weight:800;
    font-size:2rem;
  }
  .header-section .text-muted{
    color: rgba(255,255,255,.85) !important;
  }

  .stat-card{
    background:#fff; border-radius:14px; padding:20px; box-shadow:0 8px 28px rgba(0,0,0,.08);
    border-left:4px solid #e9ecef; position:relative; overflow:hidden;
  }
  .stat-card::before{
    content:''; position:absolute; left:0; top:0; height:4px; width:100%;
    background:linear-gradient(90deg,var(--primary-color),var(--info-color));
  }
  .stat-icon{ width:56px; height:56px; border-radius:12px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:22px; }
  .stat-number{ font-size:2rem; font-weight:800; color:#1e293b; line-height:1; }

  .user-avatar{
    width:40px; height:40px; border-radius:50%;
    display:flex; align-items:center; justify-content:center; font-weight:700; color:#fff;
    background:linear-gradient(135deg,var(--primary-color),var(--info-color));
  }
  .badge-role{ padding:6px 12px; border-radius:20px; font-size:12px; font-weight:600; }

  #content main>.container-fluid{ margin-top:.25rem; }
</style>

<script>
  // Cari
  document.getElementById('searchInput')?.addEventListener('keyup', function(){
    const q = this.value.toLowerCase();
    document.querySelectorAll('#usersTable tbody tr').forEach(tr=>{
      tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });

  // Filter role
  document.getElementById('roleFilter')?.addEventListener('change', function(){
    const role = this.value;
    document.querySelectorAll('#usersTable tbody tr').forEach(tr=>{
      if(!role){ tr.style.display=''; return; }
      const cell = tr.querySelector('td:nth-child(3)');
      const txt  = cell ? cell.textContent.toLowerCase() : '';
      tr.style.display = txt.includes(role) ? '' : 'none';
    });
  });

  // Edit user → load data + set action update
  async function editUser(id){
    try{
      const res = await fetch(`<?= site_url('admin/users/edit') ?>/${id}`, {headers:{'X-Requested-With':'XMLHttpRequest'}});
      if(!res.ok) throw new Error('Gagal mengambil data');
      const u = await res.json();
      document.getElementById('editNamaLengkap').value = u.nama_lengkap || '';
      document.getElementById('editEmail').value       = u.email || '';
      document.getElementById('editRole').value        = u.role || 'audience';
      document.getElementById('editStatus').value      = u.status || 'aktif';
      document.getElementById('editPassword').value    = '';
      document.getElementById('editUserForm').action   = `<?= site_url('admin/users/update') ?>/${id}`;
      new bootstrap.Modal(document.getElementById('editUserModal')).show();
    }catch(e){
      if (window.Swal) Swal.fire('Error!','Gagal memuat data user','error');
      else alert('Gagal memuat data user');
    }
  }

  // Hapus user (GET → sesuai rute)
  function deleteUser(id, name){
    if (!window.Swal) { if (confirm('Hapus user "'+name+'"?')) location.href = `<?= site_url('admin/users/delete') ?>/${id}`; return; }
    Swal.fire({
      title:'Hapus User?', text:`Apakah Anda yakin ingin menghapus user "${name}"?`,
      icon:'warning', showCancelButton:true, confirmButtonColor:'#ef4444', cancelButtonColor:'#6b7280',
      confirmButtonText:'Ya, Hapus!', cancelButtonText:'Batal'
    }).then((r)=>{ if(r.isConfirmed){ window.location.href = `<?= site_url('admin/users/delete') ?>/${id}`; } });
  }

  // Flash
  document.addEventListener('DOMContentLoaded', ()=>{
    <?php if (session('success')): ?>
      Swal?.fire({icon:'success',title:'Berhasil!',text:'<?= esc(session('success')) ?>',timer:3000,showConfirmButton:false});
    <?php endif; ?>
    <?php if (session('error')): ?>
      Swal?.fire({icon:'error',title:'Error!',text:'<?= esc(session('error')) ?>'});
    <?php endif; ?>
    <?php if (session('errors')): ?>
      Swal?.fire({icon:'error',title:'Validation Error!',html:'<?= esc(implode("<br>", (array)session("errors"))) ?>'});
    <?php endif; ?>
  });
</script>
