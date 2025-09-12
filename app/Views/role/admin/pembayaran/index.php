<?php
// ===== Vars (fallback agar view aman) =====
$title               = $title               ?? 'Verifikasi Pembayaran';
$pembayarans         = $pembayarans         ?? []; // array assoc
$pembayaran_pending  = (int)($pembayaran_pending  ?? 0);
$pembayaran_verified = (int)($pembayaran_verified ?? 0);
$pembayaran_rejected = (int)($pembayaran_rejected ?? 0);
$total_revenue       = (int)($total_revenue ?? 0);
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
            <i class="bi bi-credit-card me-2"></i><?= esc($title) ?>
          </h3>
          <div class="text-muted">Kelola dan verifikasi pembayaran dari peserta</div>
        </div>
        <div class="text-end d-none d-md-block">
          <div class="btn-group">
            <a href="<?= site_url('admin/pembayaran/export') ?>" class="btn btn-light btn-sm">
              <i class="bi bi-download me-1"></i>Export
            </a>
            <button class="btn btn-outline-light btn-sm" id="btnStatistik" type="button">
              <i class="bi bi-bar-chart-line me-1"></i>Statistik
            </button>
            <button class="btn btn-outline-light btn-sm" id="btnRefresh" type="button">
              <i class="bi bi-arrow-clockwise me-1"></i>Refresh
            </button>
          </div>
          <div class="mt-2 small text-white-50">Terakhir update <strong><?= date('d M Y, H:i') ?></strong></div>
        </div>
      </div>

      <!-- KPI -->
      <section aria-label="Ringkasan Pembayaran" class="mb-3">
        <div class="row g-3">
          <div class="col-6 col-xl-3">
            <div class="stat-card pending shadow-sm h-100">
              <div class="d-flex align-items-center">
                <div class="stat-icon bg-warning"><i class="bi bi-clock"></i></div>
                <div class="ms-3">
                  <div class="stat-number"><?= number_format($pembayaran_pending) ?></div>
                  <div class="text-muted">Pending</div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-6 col-xl-3">
            <div class="stat-card verified shadow-sm h-100">
              <div class="d-flex align-items-center">
                <div class="stat-icon bg-success"><i class="bi bi-check2-circle"></i></div>
                <div class="ms-3">
                  <div class="stat-number"><?= number_format($pembayaran_verified) ?></div>
                  <div class="text-muted">Terverifikasi</div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-6 col-xl-3">
            <div class="stat-card rejected shadow-sm h-100">
              <div class="d-flex align-items-center">
                <div class="stat-icon bg-danger"><i class="bi bi-x-circle"></i></div>
                <div class="ms-3">
                  <div class="stat-number"><?= number_format($pembayaran_rejected) ?></div>
                  <div class="text-muted">Ditolak</div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-6 col-xl-3">
            <div class="stat-card revenue shadow-sm h-100">
              <div class="d-flex align-items-center">
                <div class="stat-icon bg-info"><i class="bi bi-cash-coin"></i></div>
                <div class="ms-3">
                  <div class="stat-number">Rp <?= number_format($total_revenue, 0, ',', '.') ?></div>
                  <div class="text-muted">Total Revenue</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- FILTERS -->
      <section class="card shadow-sm mb-3" aria-label="Filter Pembayaran">
        <div class="card-body">
          <div class="row g-2 align-items-center">
            <div class="col-md-4">
              <div class="position-relative">
                <input type="text" class="form-control ps-5" id="searchInput" placeholder="Cari nama, email, atau metode...">
                <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
              </div>
            </div>
            <div class="col-md-2">
              <select class="form-select" id="statusFilter" aria-label="Filter status">
                <option value="">Semua Status</option>
                <option value="pending">Pending</option>
                <option value="verified">Terverifikasi</option>
                <option value="rejected">Ditolak</option>
              </select>
            </div>
            <div class="col-md-2">
              <select class="form-select" id="roleFilter" aria-label="Filter role">
                <option value="">Semua Role</option>
                <option value="presenter">Presenter</option>
                <option value="audience">Audience</option>
              </select>
            </div>
            <div class="col-md-2">
              <select class="form-select" id="participationFilter" aria-label="Filter partisipasi">
                <option value="">Semua Partisipasi</option>
                <option value="online">Online</option>
                <option value="offline">Offline</option>
              </select>
            </div>
            <div class="col-md-2 text-md-end">
              <button class="btn btn-outline-secondary w-100" id="btnResetFilter">
                <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
              </button>
            </div>
            <div class="col-12">
              <small id="resultCounter" class="text-muted"></small>
            </div>
          </div>
        </div>
      </section>

      <!-- GRID -->
      <section aria-label="Daftar Pembayaran">
        <div class="row g-3" id="paymentContainer">
          <?php if (!empty($pembayarans)): ?>
            <?php foreach ($pembayarans as $p): 
              $status = $p['status'] ?? 'pending';
              $role   = $p['role'] ?? '';
              $part   = $p['participation_type'] ?? '';
              $name   = $p['nama_lengkap'] ?? '-';
              $email  = $p['email'] ?? '-';
              $metode = $p['metode'] ?? '-';
              $amount = (int)($p['jumlah'] ?? 0);
              $evt    = $p['event_title'] ?? null;

              $statusClass = match($status){
                'pending'  => 'bg-warning text-dark',
                'verified' => 'bg-success',
                'rejected' => 'bg-danger',
                default    => 'bg-secondary'
              };
              $statusText = match($status){
                'pending'  => 'Pending',
                'verified' => 'Terverifikasi',
                'rejected' => 'Ditolak',
                default    => ucfirst($status)
              };
              $searchStr = strtolower(($name.' '.$email.' '.$metode));
            ?>
              <div class="col-lg-6 col-xl-4"
                   data-status="<?= esc($status) ?>"
                   data-role="<?= esc($role) ?>"
                   data-participation="<?= esc($part) ?>"
                   data-search="<?= esc($searchStr) ?>">
                <article class="card shadow-sm h-100 payment-card" aria-label="Kartu pembayaran">
                  <div class="payment-header">
                    <div class="d-flex align-items-center">
                      <div class="user-avatar me-3"><?= strtoupper(substr($name,0,1)) ?></div>
                      <div>
                        <div class="fw-semibold"><?= esc($name) ?></div>
                        <small class="text-muted"><?= esc($email) ?></small>
                        <?php if(!empty($evt)): ?>
                          <div class="mt-1">
                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle"><?= esc($evt) ?></span>
                          </div>
                        <?php endif; ?>
                      </div>
                    </div>
                    <span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span>
                  </div>

                  <div class="payment-body">
                    <div class="row">
                      <div class="col-6">
                        <label class="text-muted small">Metode</label>
                        <div class="fw-semibold"><?= esc($metode) ?></div>
                      </div>
                      <div class="col-6">
                        <label class="text-muted small">Jumlah</label>
                        <div class="fw-bold text-success">Rp <?= number_format($amount,0,',','.') ?></div>
                      </div>
                    </div>

                    <div class="row mt-3">
                      <div class="col-6">
                        <label class="text-muted small">Role & Partisipasi</label>
                        <div class="d-flex flex-wrap gap-1">
                          <span class="badge <?= $role==='presenter'?'bg-primary':'bg-secondary' ?>">
                            <?= ucfirst($role ?: '-') ?>
                          </span>
                          <?php if(!empty($part)): ?>
                            <span class="badge bg-light text-dark border"><?= ucfirst($part) ?></span>
                          <?php endif; ?>
                        </div>
                      </div>
                      <div class="col-6">
                        <label class="text-muted small">Tanggal Bayar</label>
                        <div class="fw-semibold">
                          <?= !empty($p['tanggal_bayar']) ? date('d/m/Y H:i', strtotime($p['tanggal_bayar'])) : '-' ?>
                        </div>
                      </div>
                    </div>

                    <?php if(!empty($p['voucher_info'])):
                      $v = $p['voucher_info'];
                      $pot = ($v['tipe'] ?? '')==='percentage'
                          ? ($v['nilai'] ?? 0).'%'
                          : 'Rp '.number_format((int)($v['nilai'] ?? 0),0,',','.');
                    ?>
                      <div class="mt-3 p-2 bg-light rounded border">
                        <small class="text-muted d-block">Voucher digunakan:</small>
                        <div class="fw-semibold text-success"><?= esc($v['kode_voucher'] ?? '-') ?> (<?= $pot ?>)</div>
                      </div>
                    <?php endif; ?>

                    <?php if(!empty($p['verified_at'])): ?>
                      <div class="mt-2">
                        <small class="text-muted">Diverifikasi: <?= date('d/m/Y H:i', strtotime($p['verified_at'])) ?></small>
                      </div>
                    <?php endif; ?>
                  </div>

                  <div class="payment-footer">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                      <div class="btn-group btn-group-sm" role="group" aria-label="Aksi utama">
                        <a class="btn btn-outline-info"
                           href="<?= site_url('admin/pembayaran/detail/'.(int)$p['id_pembayaran']) ?>">
                          <i class="bi bi-eye me-1"></i>Detail
                        </a>
                        <?php if (!empty($p['bukti_bayar'])): ?>
                          <button class="btn btn-outline-secondary btn-view-bukti"
                                  data-bukti-url="<?= site_url('admin/pembayaran/view-bukti/'.(int)$p['id_pembayaran']) ?>">
                            <i class="bi bi-image me-1"></i>Bukti
                          </button>
                        <?php endif; ?>
                      </div>

                      <?php if($status==='pending'): ?>
                        <div class="btn-group btn-group-sm" role="group" aria-label="Verifikasi">
                          <button class="btn btn-success btn-open-verif"
                                  data-id="<?= (int)$p['id_pembayaran'] ?>"
                                  data-status="verified">
                            <i class="bi bi-check2 me-1"></i>Verifikasi
                          </button>
                          <button class="btn btn-danger btn-open-verif"
                                  data-id="<?= (int)$p['id_pembayaran'] ?>"
                                  data-status="rejected">
                            <i class="bi bi-x me-1"></i>Tolak
                          </button>
                        </div>
                      <?php endif; ?>
                    </div>
                  </div>
                </article>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="col-12">
              <div class="p-5 text-center border rounded-3 bg-light-subtle">
                <div class="mb-2"><i class="bi bi-credit-card fs-3 text-secondary"></i></div>
                <div class="fw-semibold">Belum Ada Pembayaran</div>
                <div class="text-muted small">Belum ada pembayaran yang perlu diverifikasi saat ini.</div>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <!-- Load more (opsional) -->
        <?php if (!empty($pembayarans) && count($pembayarans) >= 50): ?>
          <div class="text-center mt-3">
            <button class="btn btn-outline-primary" id="btnLoadMore">
              <i class="bi bi-plus-lg me-1"></i>Tampilkan Lebih Banyak
            </button>
          </div>
        <?php endif; ?>
      </section>

    </div>
  </main>
</div>

<!-- MODALS -->
<div class="modal fade" id="buktiModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header bg-primary text-white">
      <h5 class="modal-title"><i class="bi bi-image me-2"></i>Bukti Pembayaran</h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body text-center">
      <img id="buktiImage" src="" class="img-fluid rounded shadow-sm" alt="Bukti Pembayaran">
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
    </div>
  </div></div>
</div>

<div class="modal fade" id="verifikasiModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header bg-primary text-white">
      <h5 class="modal-title" id="verifikasiTitle"><i class="bi bi-check2-circle me-2"></i>Verifikasi Pembayaran</h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <form id="verifikasiForm" method="POST">
      <?= csrf_field() ?>
      <div class="modal-body">
        <div class="alert alert-info">
          <i class="bi bi-info-circle me-2"></i>Pastikan bukti pembayaran sudah sesuai sebelum melakukan verifikasi.
        </div>
        <div class="mb-3">
          <label class="form-label">Keterangan Verifikasi</label>
          <textarea class="form-control" name="keterangan" rows="3" placeholder="Tambahkan keterangan (opsional)..."></textarea>
        </div>
        <input type="hidden" name="status" id="verifikasiStatus">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary" id="verifikasiSubmit">
          <i class="bi bi-save me-1"></i>Proses
        </button>
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

body{
  background:linear-gradient(135deg,#f8fafc 0%,#e2e8f0 100%);
  font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;
}

/* Header (seragam dengan dashboard/reviewer) */
.header-section.header-blue{
  background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%);
  color:#fff; padding:28px 24px; border-radius:16px; box-shadow:0 8px 28px rgba(0,0,0,.12);
}
.header-section.header-blue .welcome-text{ color:#fff; font-weight:800; font-size:2rem; }
.header-section.header-blue .text-muted, 
.header-section.header-blue strong{ color:rgba(255,255,255,.9)!important; }

.welcome-text{ color:var(--primary-color); font-weight:700; }

/* Stat cards (kpi) */
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

/* Avatar (inisial) */
.user-avatar{
  width:48px; height:48px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; color:#fff;
  background:linear-gradient(135deg,var(--primary-color),var(--info-color));
}

/* Payment card */
.payment-card{
  background:#fff; border-radius:12px; box-shadow:0 4px 20px rgba(0,0,0,.08); border:1px solid #e2e8f0; overflow:hidden;
}
.payment-header{
  padding:16px 20px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;
}
.payment-body{ padding:20px; }
.payment-footer{ padding:16px 20px; background:#f8fafc; border-top:1px solid #e2e8f0; }

.status-badge{
  padding:6px 12px; border-radius:20px; font-size:12px; font-weight:600;
}
.amount-display{ font-size:1.1rem; font-weight:700; color:var(--success-color); }

/* Event tag (selaras style badge lembut) */
.badge.bg-info-subtle{
  background: #e0f2fe!important;
  color:#0369a1!important;
  border-color:#bae6fd!important;
}

/* Util */
#content main>.container-fluid{ margin-top:.25rem; }
</style>

<!-- Hook JS modular (disarankan) -->
<script>
// Catatan: idealnya logic dipindah ke public/js/admin/payments.js dan di-enqueue di partial footer.
// Di bawah ini hanya binding ringan supaya view langsung jalan.

(function(){
  const $ = (sel, ctx=document) => ctx.querySelector(sel);
  const $$= (sel, ctx=document) => Array.from(ctx.querySelectorAll(sel));

  // Filters
  const searchInput = $('#searchInput');
  const statusSel   = $('#statusFilter');
  const roleSel     = $('#roleFilter');
  const partSel     = $('#participationFilter');
  const resetBtn    = $('#btnResetFilter');
  const counterEl   = $('#resultCounter');

  function applyFilter(){
    const q    = (searchInput?.value || '').toLowerCase();
    const s    = statusSel?.value || '';
    const r    = roleSel?.value || '';
    const p    = partSel?.value || '';
    const cards= $$('#paymentContainer > div');

    let visible = 0;
    cards.forEach(col=>{
      const ds  = (col.getAttribute('data-search')||'').toLowerCase();
      const st  = col.getAttribute('data-status')||'';
      const rl  = col.getAttribute('data-role')||'';
      const prt = col.getAttribute('data-participation')||'';

      const show = (!q || ds.includes(q))
                && (!s || st===s)
                && (!r || rl===r)
                && (!p || prt===p);
      col.style.display = show ? '' : 'none';
      if (show) visible++;
    });
    if(counterEl){ counterEl.textContent = `Menampilkan ${visible} dari ${cards.length} pembayaran`; }
  }

  searchInput?.addEventListener('input', applyFilter);
  statusSel?.addEventListener('change', applyFilter);
  roleSel?.addEventListener('change', applyFilter);
  partSel?.addEventListener('change', applyFilter);
  resetBtn?.addEventListener('click', ()=>{
    if(searchInput) searchInput.value='';
    if(statusSel)   statusSel.value='';
    if(roleSel)     roleSel.value='';
    if(partSel)     partSel.value='';
    applyFilter();
  });

  // Bukti modal
  const buktiModal = new bootstrap.Modal($('#buktiModal'));
  $$('.btn-view-bukti').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const url = btn.getAttribute('data-bukti-url');
      $('#buktiImage').src = url;
      buktiModal.show();
    });
  });

  // Verifikasi modal
  const verifModal = new bootstrap.Modal($('#verifikasiModal'));
  $$('.btn-open-verif').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const id = btn.getAttribute('data-id');
      const st = btn.getAttribute('data-status');
      const isVerif = st==='verified';
      $('#verifikasiTitle').innerHTML = (isVerif
        ? '<i class="bi bi-check2-circle me-2"></i>Verifikasi Pembayaran'
        : '<i class="bi bi-x-circle me-2"></i>Tolak Pembayaran');
      $('#verifikasiStatus').value = st;
      const form = $('#verifikasiForm');
      form.action = '<?= site_url('admin/pembayaran/verifikasi') ?>/'+id;
      const submit = $('#verifikasiSubmit');
      submit.className = 'btn ' + (isVerif ? 'btn-success' : 'btn-danger');
      submit.innerHTML = '<i class="bi bi-save me-1"></i>' + (isVerif ? 'Verifikasi' : 'Tolak');
      verifModal.show();
    });
  });

  // Header buttons
  $('#btnRefresh')?.addEventListener('click', ()=>location.reload());
  $('#btnStatistik')?.addEventListener('click', ()=>{
    // arahkan ke route statistik (bisa modal/chart di halaman lain)
    window.location.href = '<?= site_url('admin/pembayaran/statistik') ?>';
  });

  // Auto refresh ringan jika ada pending
  setInterval(()=>{
    const hasPending = document.querySelector('[data-status="pending"]');
    if(!hasPending) return;
    fetch(window.location.href, {headers: {'X-Requested-With':'XMLHttpRequest'}})
      .then(r=>r.text())
      .then(html=>{
        const doc = new DOMParser().parseFromString(html,'text/html');
        const newList = doc.querySelector('#paymentContainer');
        if(newList){
          $('#paymentContainer').innerHTML = newList.innerHTML;
          // re-bind minimal setelah refresh:
          applyFilter();
        }
      })
      .catch(()=>{});
  }, 30000);

  // first render
  applyFilter();
})();
</script>
