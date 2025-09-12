<?php
// ===== Fallback vars supaya view aman =====
$title        = $title ?? 'Detail Pembayaran';
$pembayaran   = $pembayaran ?? [];
$voucher      = $voucher   ?? null;
$verified_by  = $verified_by ?? null;

$amount   = (int)($pembayaran['jumlah'] ?? 0);
$status   = $pembayaran['status'] ?? 'pending';
$statusClass = match($status){
  'pending'  => 'bg-warning text-dark',
  'verified' => 'bg-success',
  'rejected' => 'bg-danger',
  default    => 'bg-secondary'
};
$statusText  = match($status){
  'pending'  => 'Pending',
  'verified' => 'Terverifikasi',
  'rejected' => 'Ditolak',
  default    => 'Unknown'
};
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
          <h3 class="welcome-text mb-1"><i class="bi bi-credit-card me-2"></i><?= esc($title) ?></h3>
          <div class="text-muted">Informasi lengkap pembayaran & proses verifikasi</div>
        </div>
        <div class="text-end">
          <a href="<?= site_url('admin/pembayaran') ?>" class="btn btn-outline-light btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Kembali
          </a>
        </div>
      </div>

      <div class="row g-3">

        <!-- Informasi Pengguna -->
        <div class="col-lg-6">
          <div class="card shadow-sm detail-card">
            <div class="card-header bg-light">
              <strong><i class="bi bi-person me-2"></i>Informasi Pengguna</strong>
            </div>
            <div class="card-body">
              <div class="d-flex align-items-center mb-3">
                <div class="user-avatar me-3">
                  <?= strtoupper(substr(($pembayaran['nama_lengkap'] ?? 'U'),0,1)) ?>
                </div>
                <div>
                  <h5 class="mb-1"><?= esc($pembayaran['nama_lengkap'] ?? 'N/A') ?></h5>
                  <small class="text-muted d-block"><?= esc($pembayaran['email'] ?? 'N/A') ?></small>
                  <div class="mt-1 d-flex flex-wrap gap-1">
                    <span class="badge <?= ($pembayaran['role'] ?? '')==='presenter' ? 'bg-primary':'bg-secondary' ?>">
                      <?= ucfirst($pembayaran['role'] ?? 'audience') ?>
                    </span>
                    <?php if (!empty($pembayaran['participation_type'])): ?>
                      <span class="badge bg-light text-dark border"><?= ucfirst($pembayaran['participation_type']) ?></span>
                    <?php endif; ?>
                    <span class="badge <?= ($pembayaran['status_user'] ?? 'aktif')==='aktif'?'bg-success':'bg-secondary' ?>">
                      <?= ucfirst($pembayaran['status_user'] ?? 'aktif') ?>
                    </span>
                  </div>
                </div>
              </div>

              <div class="row g-2">
                <div class="col-6">
                  <div class="small text-muted">Tanggal Registrasi</div>
                  <div class="fw-semibold">
                    <?php
                      $reg = $pembayaran['created_at'] ?? $pembayaran['tanggal_bayar'] ?? null;
                      echo $reg ? date('d/m/Y', strtotime($reg)) : '-';
                    ?>
                  </div>
                </div>
                <div class="col-6">
                  <div class="small text-muted">ID User</div>
                  <div class="fw-semibold"><?= esc($pembayaran['id_user'] ?? 'N/A') ?></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Informasi Pembayaran -->
        <div class="col-lg-6">
          <div class="card shadow-sm detail-card">
            <div class="card-header bg-light">
              <strong><i class="bi bi-cash-coin me-2"></i>Informasi Pembayaran</strong>
            </div>
            <div class="card-body">
              <div class="text-center mb-3">
                <div class="stat-number mb-1">Rp <?= number_format($amount,0,',','.') ?></div>
                <span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span>
              </div>

              <div class="row g-2">
                <div class="col-6">
                  <div class="small text-muted">ID Pembayaran</div>
                  <div class="fw-semibold">#PAY<?= str_pad((int)($pembayaran['id_pembayaran'] ?? 0),4,'0',STR_PAD_LEFT) ?></div>
                </div>
                <div class="col-6">
                  <div class="small text-muted">Metode</div>
                  <div class="fw-semibold"><?= esc($pembayaran['metode'] ?? '-') ?></div>
                </div>
                <div class="col-6">
                  <div class="small text-muted">Tanggal Bayar</div>
                  <div class="fw-semibold">
                    <?= !empty($pembayaran['tanggal_bayar']) ? date('d/m/Y H:i', strtotime($pembayaran['tanggal_bayar'])) : '-' ?>
                  </div>
                </div>
                <?php if(!empty($pembayaran['verified_at'])): ?>
                <div class="col-6">
                  <div class="small text-muted">Tanggal Verifikasi</div>
                  <div class="fw-semibold"><?= date('d/m/Y H:i', strtotime($pembayaran['verified_at'])) ?></div>
                </div>
                <?php endif; ?>

                <?php if (isset($pembayaran['original_amount'], $pembayaran['jumlah']) && (int)$pembayaran['original_amount']!=(int)$pembayaran['jumlah']): ?>
                  <div class="col-6">
                    <div class="small text-muted">Harga Asli</div>
                    <div class="text-muted"><del>Rp <?= number_format((int)$pembayaran['original_amount'],0,',','.') ?></del></div>
                  </div>
                  <div class="col-6">
                    <div class="small text-muted">Diskon</div>
                    <div class="fw-semibold text-success">-Rp <?= number_format((int)($pembayaran['discount_amount'] ?? 0),0,',','.') ?></div>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Event info -->
        <?php if(!empty($pembayaran['event_title'])): ?>
        <div class="col-12">
          <div class="card shadow-sm">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center">
              <div class="d-flex align-items-center gap-3">
                <i class="bi bi-calendar-event fs-4 text-primary"></i>
                <div>
                  <div class="fw-semibold"><?= esc($pembayaran['event_title']) ?></div>
                  <?php if(!empty($pembayaran['event_date'])): ?>
                    <small class="text-muted"><?= date('d M Y', strtotime($pembayaran['event_date'])) ?></small>
                  <?php endif; ?>
                </div>
              </div>
              <?php if(!empty($pembayaran['event_id'])): ?>
                <a class="btn btn-outline-primary btn-sm" href="<?= site_url('admin/event/detail/'.$pembayaran['event_id']) ?>">
                  <i class="bi bi-box-arrow-up-right me-1"></i>Lihat Event
                </a>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <!-- Voucher -->
        <?php if($voucher): ?>
        <div class="col-12">
          <div class="card shadow-sm border-success-subtle">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
              <div class="d-flex align-items-center gap-2">
                <i class="bi bi-ticket-perforated text-success"></i>
                <div>
                  <div class="fw-semibold">Voucher: <?= esc($voucher['kode_voucher']) ?></div>
                  <small class="text-muted">
                    <?= ($voucher['tipe']??'')==='percentage' ? ($voucher['nilai']??0).'%' : 'Rp '.number_format((int)($voucher['nilai']??0),0,',','.') ?>
                  </small>
                </div>
              </div>
              <div class="text-end small">
                Diskon: <strong>Rp <?= number_format((int)($pembayaran['discount_amount'] ?? 0),0,',','.') ?></strong>
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <!-- Fitur untuk presenter (jika verified) -->
        <?php if(($pembayaran['role'] ?? '')==='presenter' && $status==='verified'): ?>
        <div class="col-12">
          <div class="card shadow-sm">
            <div class="card-header bg-light">
              <strong><i class="bi bi-unlock me-2"></i>Fitur yang Dibuka</strong>
            </div>
            <div class="card-body">
              <div class="row g-2">
                <div class="col-sm-6"><div class="alert alert-light border d-flex align-items-center gap-2 mb-0"><i class="bi bi-qr-code-scan text-success"></i><span>QR Attendance</span></div></div>
                <div class="col-sm-6"><div class="alert alert-light border d-flex align-items-center gap-2 mb-0"><i class="bi bi-download text-info"></i><span>Download LoA</span></div></div>
                <div class="col-sm-6"><div class="alert alert-light border d-flex align-items-center gap-2 mb-0"><i class="bi bi-speedometer2 text-primary"></i><span>Dashboard Presenter</span></div></div>
                <div class="col-sm-6"><div class="alert alert-light border d-flex align-items-center gap-2 mb-0"><i class="bi bi-award text-warning"></i><span>Generate Sertifikat</span></div></div>
              </div>
              <?php if(!empty($pembayaran['features_unlocked_at'])): ?>
                <small class="text-muted d-block mt-2">Dibuka: <?= date('d/m/Y H:i', strtotime($pembayaran['features_unlocked_at'])) ?></small>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <!-- Bukti Pembayaran -->
        <div class="col-lg-8">
          <div class="card shadow-sm">
            <div class="card-header bg-light">
              <strong><i class="bi bi-image me-2"></i>Bukti Pembayaran</strong>
            </div>
            <div class="card-body">
              <?php if(!empty($pembayaran['bukti_bayar'])): ?>
                <div class="text-center">
                  <img id="buktiImage" src="<?= site_url('admin/pembayaran/view-bukti/'.(int)$pembayaran['id_pembayaran']) ?>"
                       class="img-fluid rounded shadow-sm" style="max-height:60vh; cursor:zoom-in"
                       alt="Bukti Pembayaran" onclick="previewImage(this.src)">
                  <div class="mt-3 d-flex justify-content-center gap-2">
                    <button class="btn btn-outline-primary btn-sm" onclick="previewImage(document.getElementById('buktiImage').src)">
                      <i class="bi bi-search me-1"></i>Perbesar
                    </button>
                    <a class="btn btn-outline-success btn-sm" href="<?= site_url('admin/pembayaran/download-bukti/'.(int)$pembayaran['id_pembayaran']) ?>">
                      <i class="bi bi-download me-1"></i>Download
                    </a>
                  </div>
                  <small class="text-muted d-block mt-2">
                    Diupload: <?= !empty($pembayaran['tanggal_bayar']) ? date('d M Y', strtotime($pembayaran['tanggal_bayar'])) : '-' ?>
                  </small>
                </div>
              <?php else: ?>
                <div class="p-5 text-center border rounded-3 bg-light-subtle">
                  <div class="mb-2"><i class="bi bi-image fs-3 text-secondary"></i></div>
                  <div class="fw-semibold">Bukti Pembayaran Tidak Tersedia</div>
                  <div class="text-muted small">Belum ada bukti pembayaran yang diupload.</div>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Timeline & Aksi -->
        <div class="col-lg-4">
          <div class="card shadow-sm">
            <div class="card-header bg-light">
              <strong><i class="bi bi-clock-history me-2"></i>Timeline</strong>
            </div>
            <div class="card-body">
              <div class="mb-3">
                <small class="text-muted d-block"><?= !empty($pembayaran['tanggal_bayar']) ? date('d M Y, H:i', strtotime($pembayaran['tanggal_bayar'])) : 'N/A' ?></small>
                <div class="fw-semibold">Pembayaran dibuat</div>
              </div>

              <?php if(!empty($pembayaran['bukti_bayar'])): ?>
              <div class="mb-3">
                <small class="text-muted d-block"><?= !empty($pembayaran['tanggal_bayar']) ? date('d M Y, H:i', strtotime($pembayaran['tanggal_bayar'])) : 'N/A' ?></small>
                <div class="fw-semibold">Bukti pembayaran diupload</div>
              </div>
              <?php endif; ?>

              <?php if($status==='verified' && !empty($pembayaran['verified_at'])): ?>
              <div class="mb-3">
                <small class="text-muted d-block"><?= date('d M Y, H:i', strtotime($pembayaran['verified_at'])) ?></small>
                <div class="fw-semibold text-success">Pembayaran diverifikasi</div>
                <?php if($verified_by): ?>
                  <small class="text-muted">oleh <?= esc($verified_by['nama_lengkap']) ?></small>
                <?php endif; ?>
              </div>
              <?php elseif($status==='rejected' && !empty($pembayaran['verified_at'])): ?>
              <div class="mb-3">
                <small class="text-muted d-block"><?= date('d M Y, H:i', strtotime($pembayaran['verified_at'])) ?></small>
                <div class="fw-semibold text-danger">Pembayaran ditolak</div>
                <?php if($verified_by): ?>
                  <small class="text-muted">oleh <?= esc($verified_by['nama_lengkap']) ?></small>
                <?php endif; ?>
              </div>
              <?php else: ?>
              <div class="mb-3">
                <small class="text-muted d-block">Menunggu</small>
                <div class="fw-semibold text-warning">Menunggu verifikasi admin</div>
              </div>
              <?php endif; ?>
            </div>
          </div>

          <?php if($status==='pending'): ?>
          <div class="card shadow-sm mt-3">
            <div class="card-header bg-light">
              <strong><i class="bi bi-sliders me-2"></i>Aksi Verifikasi</strong>
            </div>
            <div class="card-body">
              <div class="d-grid gap-2">
                <button class="btn btn-success" data-open-verif data-id="<?= (int)($pembayaran['id_pembayaran'] ?? 0) ?>" data-status="verified">
                  <i class="bi bi-check2 me-1"></i>Verifikasi Pembayaran
                </button>
                <button class="btn btn-danger" data-open-verif data-id="<?= (int)($pembayaran['id_pembayaran'] ?? 0) ?>" data-status="rejected">
                  <i class="bi bi-x me-1"></i>Tolak Pembayaran
                </button>
                <hr class="my-2">
                <button class="btn btn-outline-info" id="btnNotify"><i class="bi bi-envelope me-1"></i>Kirim Notifikasi</button>
                <button class="btn btn-outline-secondary" id="btnAddNote"><i class="bi bi-sticky me-1"></i>Tambah Catatan</button>
              </div>
            </div>
          </div>
          <?php endif; ?>
        </div>

        <!-- Informasi Tambahan -->
        <div class="col-12">
          <div class="card shadow-sm">
            <div class="card-header bg-light">
              <strong><i class="bi bi-info-circle me-2"></i>Informasi Tambahan</strong>
            </div>
            <div class="card-body">
              <div class="row g-2">
                <div class="col-md-6">
                  <div class="d-flex justify-content-between border-bottom py-2">
                    <span class="text-muted">Event ID</span>
                    <span class="fw-semibold"><?= esc($pembayaran['event_id'] ?? '-') ?></span>
                  </div>
                  <div class="d-flex justify-content-between border-bottom py-2">
                    <span class="text-muted">Created At</span>
                    <span class="fw-semibold">
                      <?= !empty($pembayaran['tanggal_bayar']) ? date('d/m/Y H:i:s', strtotime($pembayaran['tanggal_bayar'])) : 'N/A' ?>
                    </span>
                  </div>
                  <?php if(!empty($pembayaran['payment_reference'])): ?>
                  <div class="d-flex justify-content-between border-bottom py-2">
                    <span class="text-muted">Referensi</span>
                    <span class="fw-semibold"><?= esc($pembayaran['payment_reference']) ?></span>
                  </div>
                  <?php endif; ?>
                </div>
                <div class="col-md-6">
                  <?php if($verified_by): ?>
                  <div class="d-flex justify-content-between border-bottom py-2">
                    <span class="text-muted">Diverifikasi oleh</span>
                    <span class="fw-semibold"><?= esc($verified_by['nama_lengkap']) ?></span>
                  </div>
                  <?php endif; ?>
                  <?php if(!empty($pembayaran['id_voucher'])): ?>
                  <div class="d-flex justify-content-between border-bottom py-2">
                    <span class="text-muted">Voucher ID</span>
                    <span class="fw-semibold"><?= esc($pembayaran['id_voucher']) ?></span>
                  </div>
                  <?php endif; ?>
                  <div class="d-flex justify-content-between border-bottom py-2">
                    <span class="text-muted">Status Terakhir</span>
                    <span class="fw-semibold"><span class="badge <?= $statusClass ?>"><?= $statusText ?></span></span>
                  </div>
                  <?php if(!empty($pembayaran['auto_verified'])): ?>
                  <div class="d-flex justify-content-between border-bottom py-2">
                    <span class="text-muted">Auto Verified</span>
                    <span class="fw-semibold"><span class="badge bg-info">Ya</span></span>
                  </div>
                  <?php endif; ?>
                </div>
              </div>

              <?php if(!empty($pembayaran['keterangan'])): ?>
              <div class="mt-3">
                <label class="form-label">Keterangan:</label>
                <div class="alert alert-light border mb-0"><?= nl2br(esc($pembayaran['keterangan'])) ?></div>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

      </div><!-- row -->

    </div>
  </main>
</div>

<!-- MODALS -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl"><div class="modal-content">
    <div class="modal-header bg-primary text-white">
      <h5 class="modal-title"><i class="bi bi-image me-2"></i>Preview Bukti Pembayaran</h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body text-center">
      <img id="fullImage" src="" class="img-fluid" style="max-height:70vh" alt="Bukti Pembayaran">
    </div>
    <div class="modal-footer">
      <a class="btn btn-outline-success" href="<?= site_url('admin/pembayaran/download-bukti/'.(int)($pembayaran['id_pembayaran'] ?? 0)) ?>">
        <i class="bi bi-download me-1"></i>Download
      </a>
      <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
    </div>
  </div></div>
</div>

<div class="modal fade" id="verifikasiModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header bg-primary text-white">
      <h5 class="modal-title" id="verifikasiTitle"><i class="bi bi-check2-circle me-2"></i>Verifikasi Pembayaran</h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <form id="verifikasiForm" method="POST" action="#">
      <?= csrf_field() ?>
      <div class="modal-body">
        <div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>Pastikan bukti pembayaran sudah sesuai sebelum verifikasi.</div>
        <div class="mb-3">
          <label class="form-label">Keterangan</label>
          <textarea class="form-control" name="keterangan" rows="3" placeholder="Opsional..."></textarea>
        </div>
        <input type="hidden" name="status" id="verifikasiStatus">
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-primary" id="verifikasiSubmit" type="submit"><i class="bi bi-save me-1"></i>Proses</button>
      </div>
    </form>
  </div></div>
</div>

<?= $this->include('partials/footer') ?>

<style>
    :root{
  --primary-color:#2563eb;
  --info-color:#06b6d4;
  --success-color:#10b981;
  --warning-color:#f59e0b;
  --danger-color:#ef4444;
}

/* Background & font sudah sama via global */

/* Header seragam */
.header-section.header-blue{
  background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%);
  color:#fff; padding:28px 24px; border-radius:16px; box-shadow:0 8px 28px rgba(0,0,0,.12);
}
.header-section.header-blue .welcome-text{ color:#fff; font-weight:800; font-size:2rem; }
.header-section.header-blue .text-muted,
.header-section.header-blue strong{ color:rgba(255,255,255,.9)!important; }

/* Avatar inisial */
.user-avatar{
  width:56px; height:56px; border-radius:50%;
  display:flex; align-items:center; justify-content:center;
  font-weight:700; color:#fff; font-size:20px;
  background:linear-gradient(135deg,var(--primary-color),var(--info-color));
}

/* Badge status & elemen kecil */
.status-badge{
  padding:6px 12px; border-radius:20px; font-size:12px; font-weight:600;
}
.badge.bg-info-subtle{
  background:#e0f2fe!important; color:#0369a1!important; border:1px solid #bae6fd!important;
}

/* Kartu detail mengikuti gaya card global */
.detail-card{ border-radius:14px; overflow:hidden; }
.detail-card .card-header{
  background:#f8fafc; border-bottom:1px solid #e9eef5;
  padding:.85rem 1rem;
}

/* Angka besar (reuse dari KPI) */
.stat-number{ font-size:2rem; font-weight:800; color:#1e293b; line-height:1; }

/* Section event & voucher */
.card.border-success-subtle{ border-color:#d1fae5!important; }
.card.border-success-subtle .card-body{ background:linear-gradient(180deg,#f0fdf4 0%,#ecfdf5 100%); }
.card .bi-calendar-event{ color:var(--primary-color); }

/* Gambar bukti */
#buktiImage{ max-height:60vh; object-fit:contain; cursor:zoom-in; }
#fullImage{ object-fit:contain; }

/* Utility */
#content main>.container-fluid{ margin-top:.25rem; }

/* (Opsional) Timeline sederhana kalau dipakai di halaman lain */
.timeline-dot{
  width:10px; height:10px; border-radius:50%; background:var(--primary-color);
  display:inline-block; margin-right:.5rem;
}
</style>

<script>
(function(){
  const $ = (s, c=document)=>c.querySelector(s);
  const $$= (s, c=document)=>Array.from(c.querySelectorAll(s));

  // Preview bukti
  const imgModal = new bootstrap.Modal($('#imageModal'));
  window.previewImage = (src)=>{
    if(!src) return;
    $('#fullImage').src = src;
    imgModal.show();
  };

  // Verifikasi modal openers
  const verifModal = new bootstrap.Modal($('#verifikasiModal'));
  $$('.btn[data-open-verif]').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const id = btn.getAttribute('data-id');
      const st = btn.getAttribute('data-status');
      const isVer = st==='verified';
      $('#verifikasiTitle').innerHTML = (isVer
        ? '<i class="bi bi-check2-circle me-2"></i>Verifikasi Pembayaran'
        : '<i class="bi bi-x-circle me-2"></i>Tolak Pembayaran');
      $('#verifikasiStatus').value = st;
      const form = $('#verifikasiForm');
      form.action = '<?= site_url('admin/pembayaran/verifikasi') ?>/'+id;
      const submit = $('#verifikasiSubmit');
      submit.className = 'btn ' + (isVer ? 'btn-success' : 'btn-danger');
      submit.innerHTML = '<i class="bi bi-save me-1"></i>' + (isVer ? 'Verifikasi' : 'Tolak');
      verifModal.show();
    });
  });

  // Notifikasi & Catatan (placeholder)
  $('#btnNotify')?.addEventListener('click', ()=>{
    Swal.fire({
      title:'Kirim Notifikasi',
      text:'Kirim email/pesan ke user terkait status pembayaran?',
      icon:'question', showCancelButton:true, confirmButtonText:'Kirim', cancelButtonText:'Batal'
    }).then(r=>{
      if(r.isConfirmed){
        // TODO: ajax kirim notifikasi
        Swal.fire({icon:'success',title:'Berhasil',text:'Notifikasi terkirim',timer:1800,showConfirmButton:false});
      }
    });
  });

  $('#btnAddNote')?.addEventListener('click', async ()=>{
    const {value:note} = await Swal.fire({title:'Tambah Catatan',input:'textarea',inputPlaceholder:'Tulis catatan...',showCancelButton:true,confirmButtonText:'Simpan',cancelButtonText:'Batal'});
    if(note){
      // TODO: ajax simpan catatan
      Swal.fire({icon:'success',title:'Tersimpan',timer:1500,showConfirmButton:false});
    }
  });
})();
</script>
