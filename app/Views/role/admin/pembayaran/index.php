<?php
// ===== Variable Initialization =====
$title               = $title               ?? 'Verifikasi Pembayaran';
$pembayarans         = $pembayarans         ?? [];
$pembayaran_pending  = (int)($pembayaran_pending  ?? 0);
$pembayaran_verified = (int)($pembayaran_verified ?? 0);
$pembayaran_rejected = (int)($pembayaran_rejected ?? 0);
$total_revenue       = (int)($total_revenue ?? 0);

if (!function_exists('money_id')) {
  function money_id($n){ return 'Rp '.number_format((int)$n, 0, ',', '.'); }
}
// Fallback mb_substr jika ekstensi mbstring tidak aktif
if (!function_exists('mb_substr')) {
  function mb_substr($s, $start, $len = null, $enc = null) { return substr($s, $start, $len ?? 1); }
}
// Util untuk inisial avatar (2 huruf, mis. “Budi Santoso” => “BS”)
if (!function_exists('name_initials')) {
  function name_initials(string $name): string {
    $name = trim(preg_replace('/\s+/u',' ',$name));
    if ($name === '') return 'U';
    $parts = explode(' ', $name, 3);
    $a = strtoupper(mb_substr($parts[0] ?? '', 0, 1, 'UTF-8'));
    $b = strtoupper(mb_substr($parts[1] ?? $parts[0] ?? '', 0, 1, 'UTF-8'));
    return $a.$b;
  }
}
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_admin') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill page-wrap-blue" style="padding-top: 70px;">
    <div class="container-xxl px-3 px-md-4 py-4">

      <!-- HERO: Title + Search (pencarian dipindah ke sini) -->
      <div class="card-hero mb-4">
        <div class="hero-body">
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
              <h3 class="hero-title mb-1">
                <i class="bi bi-credit-card me-2"></i><?= esc($title) ?>
              </h3>
              <div class="text-white-70 small">Kelola & verifikasi pembayaran peserta.</div>
            </div>
            <div class="hero-meta text-end">
              <small class="text-white-70 d-block">Terakhir update</small>
              <div class="fw-bold"><?= date('d M Y, H:i') ?></div>
            </div>
          </div>

          <div class="hero-tools flex-grow-1 mt-3" style="max-width:820px;">
            <div class="input-group input-group-lg hero-search">
              <span class="input-group-text"><i class="bi bi-search"></i></span>
              <input id="searchInput" type="text" class="form-control" placeholder="Cari nama, email, event, metode, status…">
              <button id="clearSearch" type="button" class="btn btn-light d-none"><i class="bi bi-x-circle"></i></button>
            </div>
            <div class="mt-2 small text-white-70" id="resultCounter" aria-live="polite"></div>
          </div>
        </div>
      </div>

      <!-- KPI Ringkas -->
      <section aria-label="Ringkasan Pembayaran" class="mb-3">
        <div class="row g-3">
          <div class="col-6 col-lg-3">
            <div class="stat-card">
              <div class="stat-content">
                <div class="stat-icon bg-warning"><i class="bi bi-clock"></i></div>
                <div class="stat-info min-w-0">
                  <div class="stat-number" data-countup="<?= $pembayaran_pending ?>"><?= number_format($pembayaran_pending) ?></div>
                  <div class="stat-label">Pending</div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-6 col-lg-3">
            <div class="stat-card">
              <div class="stat-content">
                <div class="stat-icon bg-success"><i class="bi bi-check2-circle"></i></div>
                <div class="stat-info min-w-0">
                  <div class="stat-number" data-countup="<?= $pembayaran_verified ?>"><?= number_format($pembayaran_verified) ?></div>
                  <div class="stat-label">Terverifikasi</div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-6 col-lg-3">
            <div class="stat-card">
              <div class="stat-content">
                <div class="stat-icon bg-danger"><i class="bi bi-x-circle"></i></div>
                <div class="stat-info min-w-0">
                  <div class="stat-number" data-countup="<?= $pembayaran_rejected ?>"><?= number_format($pembayaran_rejected) ?></div>
                  <div class="stat-label">Ditolak</div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-6 col-lg-3">
            <div class="stat-card">
              <div class="stat-content">
                <div class="stat-icon bg-info"><i class="bi bi-cash-coin"></i></div>
                <div class="stat-info min-w-0">
                  <div class="stat-number small-text"><?= money_id($total_revenue) ?></div>
                  <div class="stat-label">Total Revenue</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Grid Kartu Pembayaran -->
      <section aria-label="Daftar Pembayaran">
        <div class="section-header mb-2">
          <h4 class="section-title">Daftar Pembayaran</h4>
        </div>

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
              $evt    = $p['event_title'] ?? '';
              $paidAt = !empty($p['tanggal_bayar']) ? date('d/m/Y H:i', strtotime($p['tanggal_bayar'])) : '-';

              $statusClass = match($status){
                'pending'  => 'status-pending',
                'verified' => 'status-verified',
                'rejected' => 'status-rejected',
                default    => 'status-default'
              };
              $statusText = match($status){
                'pending'  => 'Pending',
                'verified' => 'Terverifikasi',
                'rejected' => 'Ditolak',
                default    => ucfirst($status)
              };

              // string pencarian gabungan
              $searchStr = strtolower(preg_replace('/\s+/', ' ', trim("$name $email $evt $metode $status $role $part")));
            ?>
              <div class="col-12 col-md-6 col-xl-4"
                   data-status="<?= esc($status) ?>"
                   data-role="<?= esc($role) ?>"
                   data-participation="<?= esc($part) ?>"
                   data-search="<?= esc($searchStr) ?>">
                <article class="payment-card" aria-label="Kartu pembayaran">
                  <!-- Payment Header -->
                  <div class="payment-header">
                    <div class="user-info min-w-0">
                      <div class="user-avatar" aria-hidden="true"><?= name_initials($name) ?></div>
                      <div class="user-details min-w-0">
                        <div class="user-name text-wrap break-anywhere"><?= esc($name) ?></div>
                        <div class="user-email text-wrap break-anywhere"><?= esc($email) ?></div>
                        <?php if ($evt): ?>
                          <div class="event-badge-wrapper">
                            <span class="event-badge text-wrap break-anywhere"><?= esc($evt) ?></span>
                          </div>
                        <?php endif; ?>
                      </div>
                    </div>
                    <div class="status-badge <?= $statusClass ?> text-center" role="status" aria-label="Status pembayaran: <?= $statusText ?>">
                      <?= $statusText ?>
                    </div>
                  </div>

                  <!-- Payment Body -->
                  <div class="payment-body">
                    <div class="payment-details">
                      <div class="detail-row">
                        <div class="detail-item">
                          <label class="detail-label">Metode</label>
                          <div class="detail-value text-wrap break-anywhere"><?= esc($metode) ?></div>
                        </div>
                        <div class="detail-item">
                          <label class="detail-label">Jumlah</label>
                          <div class="detail-value amount"><?= money_id($amount) ?></div>
                        </div>
                      </div>
                      <div class="detail-row">
                        <div class="detail-item">
                          <label class="detail-label">Role & Partisipasi</label>
                          <div class="badge-group">
                            <span class="role-badge <?= $role==='presenter' ? 'role-presenter' : 'role-audience' ?>">
                              <?= $role ? ucfirst($role) : '-' ?>
                            </span>
                            <?php if (!empty($part)): ?>
                              <span class="participation-badge"><?= ucfirst($part) ?></span>
                            <?php endif; ?>
                          </div>
                        </div>
                        <div class="detail-item">
                          <label class="detail-label">Tanggal Bayar</label>
                          <div class="detail-value"><?= $paidAt ?></div>
                        </div>
                      </div>
                    </div>

                    <?php if (!empty($p['voucher_info'])):
                      $v = $p['voucher_info'];
                      $pot = ($v['tipe'] ?? '') === 'percentage'
                        ? (int)($v['nilai'] ?? 0).'%'
                        : money_id($v['nilai'] ?? 0);
                    ?>
                      <div class="voucher-info">
                        <div class="voucher-label">Voucher digunakan:</div>
                        <div class="voucher-details text-wrap break-anywhere">
                          <?= esc($v['kode_voucher'] ?? '-') ?> (<?= $pot ?>)
                        </div>
                      </div>
                    <?php endif; ?>

                    <?php if (!empty($p['verified_at'])): ?>
                      <div class="verification-info">
                        <small class="verification-text">Diverifikasi: <?= date('d/m/Y H:i', strtotime($p['verified_at'])) ?></small>
                      </div>
                    <?php endif; ?>
                  </div>

                  <!-- Payment Footer -->
                  <div class="payment-footer">
                    <div class="action-buttons">
                      <div class="primary-actions">
                        <a class="btn btn-outline-info btn-sm"
                           href="<?= site_url('admin/pembayaran/detail/'.(int)$p['id_pembayaran']) ?>">
                          <i class="bi bi-eye me-1"></i>Detail
                        </a>
                        <?php if (!empty($p['bukti_bayar'])): ?>
                          <button class="btn btn-outline-secondary btn-sm btn-view-bukti"
                                  data-bukti-url="<?= site_url('admin/pembayaran/view-bukti/'.(int)$p['id_pembayaran']) ?>">
                            <i class="bi bi-image me-1"></i>Bukti
                          </button>
                        <?php endif; ?>
                      </div>

                      <?php if ($status === 'pending'): ?>
                        <div class="verification-actions">
                          <button class="btn btn-success btn-sm btn-open-verif"
                                  data-id="<?= (int)$p['id_pembayaran'] ?>"
                                  data-status="verified">
                            <i class="bi bi-check2 me-1"></i>Verifikasi
                          </button>
                          <button class="btn btn-danger btn-sm btn-open-verif"
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
              <div class="empty-state">
                <div class="empty-icon"><i class="bi bi-credit-card"></i></div>
                <h5 class="empty-title">Belum Ada Pembayaran</h5>
                <p class="empty-text">Belum ada pembayaran yang perlu diverifikasi saat ini.</p>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <?php if (!empty($pembayarans) && count($pembayarans) >= 50): ?>
          <div class="load-more-section">
            <button class="btn btn-outline-primary btn-lg" id="btnLoadMore">
              <i class="bi bi-plus-lg me-2"></i>Tampilkan Lebih Banyak
            </button>
          </div>
        <?php endif; ?>
      </section>

    </div>
  </main>
</div>

<!-- Bukti Modal -->
<div class="modal fade" id="buktiModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title"><i class="bi bi-image me-2"></i>Bukti Pembayaran</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
    </div>
    <div class="modal-body text-center">
      <img id="buktiImage" src="" class="img-fluid rounded shadow-sm" alt="Bukti Pembayaran">
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
    </div>
  </div></div>
</div>

<!-- Verifikasi Modal -->
<div class="modal fade" id="verifikasiModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title" id="verifikasiTitle"><i class="bi bi-check2-circle me-2"></i>Verifikasi Pembayaran</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
    </div>
    <form id="verifikasiForm" method="POST">
      <?= csrf_field() ?>
      <div class="modal-body">
        <div class="alert alert-info">
          <i class="bi bi-info-circle me-2"></i>Pastikan bukti pembayaran sudah sesuai sebelum verifikasi.
        </div>
        <div class="form-group">
          <label class="form-label">Keterangan Verifikasi</label>
          <textarea class="form-control" name="keterangan" rows="3" placeholder="Tambahkan keterangan (opsional)…"></textarea>
        </div>
        <input type="hidden" name="status" id="verifikasiStatus">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary" id="verifikasiSubmit"><i class="bi bi-save me-2"></i>Proses</button>
      </div>
    </form>
  </div></div>
</div>

<?= $this->include('partials/footer') ?>

<style>
/* ====== Anti horizontal scroll & wrapping ====== */
html, body { overflow-x: hidden; }
* { word-wrap: break-word; }
.break-anywhere { overflow-wrap:anywhere; word-break:break-word; }
.text-wrap { white-space: normal !important; }

/* ====== Theme ====== */
:root{
  --primary-color:#2563eb; --blue-800:#1e40af;
  --success-color:#10b981; --warning-color:#f59e0b; --danger-color:#ef4444; --info-color:#06b6d4;
  --ink:#0f172a; --muted:#64748b;
  --light-bg:#f8fafc; --border:#e2e8f0;
  --radius:14px;
  --shadow-sm:0 2px 8px rgba(0,0,0,.08);
  --shadow-md:0 6px 18px rgba(0,0,0,.12);
  --shadow-lg:0 12px 32px rgba(0,0,0,.16);
}
.container-xxl{ max-width: min(100%,1560px); margin-inline:auto; padding-inline:clamp(1rem,2.3vw,2rem)!important; }
.page-wrap-blue{
  min-height:100vh;
  background:
    radial-gradient(1000px 380px at 10% -10%, rgba(59,130,246,.16), rgba(59,130,246,0) 60%),
    radial-gradient(1000px 380px at 90% 110%, rgba(59,130,246,.12), rgba(59,130,246,0) 70%),
    linear-gradient(180deg, var(--light-bg), #fff 42%);
}

/* ====== HERO ====== */
.card-hero{ border:0; border-radius:18px; overflow:hidden; box-shadow:var(--shadow-lg); }
.card-hero .hero-body{ background:linear-gradient(135deg,var(--primary-color),var(--blue-800)); color:#fff; padding:1.6rem 1.2rem; }
.hero-title{ font-weight:900; letter-spacing:.2px; margin-bottom:.2rem; font-size:1.35rem; }
.text-white-70{ color:rgba(255,255,255,.9)!important; }
.hero-tools .input-group .input-group-text{ background:#fff; border:0; }
.hero-tools .form-control{ border:0; }
.hero-tools .btn{ border:0; }
.hero-search{ border-radius:12px; overflow:hidden; }

/* ====== KPI ====== */
.stat-card{ background:#fff; border:1px solid var(--border); border-radius:var(--radius); padding:14px; box-shadow:var(--shadow-sm); height:100%; }
.stat-content{ display:flex; align-items:center; gap:12px; }
.stat-icon{ width:48px; height:48px; border-radius:12px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:20px; }
.stat-number{ font-weight:900; font-size:1.6rem; line-height:1.1; color:var(--ink); }
.stat-number.small-text{ font-size:1.1rem; }
.stat-label{ color:var(--muted); font-weight:700; font-size:.84rem; }

/* ====== Section header ====== */
.section-title{ font-weight:800; color:var(--ink); }

/* ====== Payment card ====== */
.payment-card{ background:#fff; border:1px solid var(--border); border-radius:var(--radius); box-shadow:var(--shadow-sm); display:flex; flex-direction:column; height:100%; }
.payment-card:hover{ transform:translateY(-2px); box-shadow:var(--shadow-md); transition:.2s ease; }
.payment-header{ padding:14px; border-bottom:1px solid var(--border); background:linear-gradient(145deg,#f8fafc,#f1f5f9);
  display:flex; align-items:flex-start; justify-content:space-between; gap:10px; }
.user-info{ display:flex; align-items:center; gap:12px; }
.user-avatar{
  width:44px; height:44px; aspect-ratio:1/1; border-radius:50%;
  background:linear-gradient(135deg,var(--primary-color),var(--info-color));
  display:flex; align-items:center; justify-content:center; color:#fff; font-weight:900; font-size:.95rem; flex-shrink:0;
}
.user-name{ font-weight:900; color:var(--ink); font-size:1rem; }
.user-email{ color:var(--muted); font-size:.86rem; }
.event-badge{ background:rgba(6,182,212,.12); color:#0369a1; border:1px solid rgba(6,182,212,.25); padding:.22rem .5rem; border-radius:6px; font-size:.75rem; font-weight:600; }

.status-badge{
  padding:.42rem .8rem; border-radius:999px; font-size:.72rem; font-weight:800; letter-spacing:.02em; text-transform:uppercase;
  white-space:normal; min-width:110px; text-align:center;
}
.status-pending{ background:#fef3c7; color:#92400e; }
.status-verified{ background:#d1fae5; color:#065f46; }
.status-rejected{ background:#fee2e2; color:#991b1b; }
.status-default{ background:#f3f4f6; color:#374151; }

.payment-body{ padding:14px; flex:1; }
.detail-row{ display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:10px; }
.detail-item{ display:flex; flex-direction:column; }
.detail-label{ color:var(--muted); font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.03em; }
.detail-value{ color:var(--ink); font-weight:800; }
.detail-value.amount{ color:var(--success-color); font-size:1rem; }

.badge-group{ display:flex; flex-wrap:wrap; gap:6px; }
.role-badge{ padding:.22rem .48rem; border-radius:6px; font-size:.72rem; font-weight:800; text-transform:uppercase; }
.role-presenter{ background:#dbeafe; color:#1e40af; }
.role-audience{ background:#f3f4f6; color:#374151; }
.participation-badge{ background:#f9fafb; color:var(--muted); border:1px solid var(--border); padding:.22rem .48rem; border-radius:6px; font-size:.72rem; font-weight:700; }

.voucher-info{ background:linear-gradient(145deg,#f0fdf4,#ecfdf5); border:1px solid #bbf7d0; border-radius:8px; padding:.6rem .7rem; margin-bottom:10px; }
.voucher-label{ color:var(--muted); font-size:.75rem; }
.voucher-details{ color:var(--success-color); font-weight:800; }

.verification-info{ margin-top:6px; padding-top:6px; border-top:1px solid var(--border); }
.verification-text{ color:var(--muted); font-size:.76rem; }

.payment-footer{ padding:12px 14px; background:#f8fafc; border-top:1px solid var(--border); }
.action-buttons{ display:flex; align-items:center; justify-content:space-between; gap:8px; flex-wrap:wrap; }
.primary-actions,.verification-actions{ display:flex; gap:8px; }

/* ====== Empty ====== */
.empty-state{ text-align:center; padding:2.4rem 1.2rem; background:#fff; border:2px dashed var(--border); border-radius:12px; }
.empty-icon{ font-size:3rem; color:var(--muted); }
.empty-title{ font-weight:800; color:var(--ink); }
.empty-text{ color:var(--muted); }

/* ====== Modal ====== */
.modal-header{ background:linear-gradient(135deg,var(--primary-color),var(--blue-800)); color:#fff; border-bottom:0; }
.modal-title{ font-weight:800; }

/* ====== Buttons ====== */
.btn{ font-weight:700; border-radius:8px; }
.btn:hover{ transform:translateY(-1px); box-shadow:var(--shadow-sm); transition:.15s ease; }

/* ====== Responsive ====== */
@media (max-width: 768px){
  .card-hero .hero-body{ padding:1.2rem 1rem; }
  .detail-row{ grid-template-columns:1fr; }
  .action-buttons{ flex-direction:column; align-items:stretch; }
  .primary-actions,.verification-actions{ width:100%; justify-content:center; flex-wrap:wrap; }
  .payment-header{ flex-direction:column; align-items:stretch; }
  .status-badge{ align-self:flex-start; }
}
</style>

<script>
(() => {
  'use strict';

  // Helpers
  const $ = (s, c=document) => c.querySelector(s);
  const $$ = (s, c=document) => Array.from(c.querySelectorAll(s));

  // Count-up KPI (respect reduced motion)
  const prefersReduced = matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (!prefersReduced) {
    $$('.stat-number[data-countup]').forEach(el=>{
      const target = parseInt(el.dataset.countup||'0',10);
      let now=0, step=Math.max(1, Math.ceil(target/40));
      const tick=()=>{ now=Math.min(now+step,target); el.textContent=new Intl.NumberFormat('id-ID').format(now); if(now<target) requestAnimationFrame(tick); };
      requestAnimationFrame(tick);
    });
  }

  // ===== Pencarian di HERO =====
  const input = $('#searchInput');
  const clearBtn = $('#clearSearch');
  const counter = $('#resultCounter');
  const cards = () => $$('#paymentContainer > div');

  function applySearch(q){
    const query = (q||'').trim().toLowerCase();
    let shown = 0;
    cards().forEach(col=>{
      const hay = (col.getAttribute('data-search') || '').toLowerCase();
      const show = !query || hay.includes(query);
      col.classList.toggle('d-none', !show);
      if (show) shown++;
    });
    clearBtn?.classList.toggle('d-none', !query);
    if (counter){
      counter.textContent = `Menampilkan ${new Intl.NumberFormat('id-ID').format(shown)} dari ${cards().length} pembayaran`;
    }
    sessionStorage.setItem('paySearch', query);
  }

  // restore last query
  const last = sessionStorage.getItem('paySearch') || '';
  if (input){ input.value = last; }
  applySearch(last);

  input?.addEventListener('input', e => applySearch(e.target.value));
  clearBtn?.addEventListener('click', ()=>{
    input.value = '';
    applySearch('');
    input.focus();
  });

  // ===== Modals =====
  const buktiModal = new bootstrap.Modal($('#buktiModal'));
  const verifikasiModal = new bootstrap.Modal($('#verifikasiModal'));

  function bindButtons(root=document){
    $$('.btn-view-bukti', root).forEach(btn=>{
      btn.addEventListener('click', ()=>{
        const url = btn.getAttribute('data-bukti-url');
        const img = $('#buktiImage');
        if (url && img){
          img.onerror = () => {
            img.alt = 'Bukti tidak dapat dimuat';
            img.src = 'data:image/svg+xml;charset=UTF-8,' +
              encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="800" height="400"><rect width="100%" height="100%" fill="#f3f4f6"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#6b7280" font-family="Arial" font-size="20">Bukti tidak tersedia</text></svg>');
          };
          img.src = url;
          buktiModal.show();
        }
      });
    });

    $$('.btn-open-verif', root).forEach(btn=>{
      btn.addEventListener('click', ()=>{
        const id = btn.getAttribute('data-id');
        const status = btn.getAttribute('data-status');
        const isOK = status==='verified';

        $('#verifikasiTitle').innerHTML = isOK
          ? '<i class="bi bi-check2-circle me-2"></i>Verifikasi Pembayaran'
          : '<i class="bi bi-x-circle me-2"></i>Tolak Pembayaran';
        $('#verifikasiStatus').value = status;
        $('#verifikasiForm').action = `<?= site_url('admin/pembayaran/verifikasi') ?>/${id}`;

        const submit = $('#verifikasiSubmit');
        submit.className = `btn btn-${isOK?'success':'danger'}`;
        submit.innerHTML = `<i class="bi bi-save me-2"></i>${isOK?'Verifikasi':'Tolak'}`;

        verifikasiModal.show();
      });
    });
  }
  bindButtons();

  // ===== Auto refresh pending (opsional) =====
  function autoRefreshPending(){
    if (!document.querySelector('[data-status="pending"]')) return;
    fetch(window.location.href, { headers:{'X-Requested-With':'XMLHttpRequest'} })
      .then(r=>r.text()).then(html=>{
        const doc = new DOMParser().parseFromString(html,'text/html');
        const fresh = doc.querySelector('#paymentContainer');
        if (!fresh) return;
        const cur = $('#paymentContainer');
        cur.innerHTML = fresh.innerHTML;
        bindButtons(cur);
        applySearch(sessionStorage.getItem('paySearch')||'');
      }).catch(()=>{ /* silent */ });
  }
  setInterval(autoRefreshPending, 30000);

  // Load more (placeholder)
  $('#btnLoadMore')?.addEventListener('click', ()=> console.log('Load more payments…'));

})();
</script>
