<?php
// helper kecil untuk waktu relatif (aman dari redeclare)
if (!function_exists('timeAgo')) {
  function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    if ($time < 60) return 'Baru saja';
    if ($time < 3600) return floor($time/60) . ' menit yang lalu';
    if ($time < 86400) return floor($time/3600) . ' jam yang lalu';
    if ($time < 2592000) return floor($time/86400) . ' hari yang lalu';
    return date('d M Y', strtotime($datetime));
  }
}
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
            <i class="fas fa-chart-line me-2"></i>Laporan & Analitik
          </h3>
          <div class="text-muted">Pantau kinerja & statistik SNIA secara komprehensif</div>
        </div>
        <div class="text-end d-none d-md-block">
          <small class="text-muted d-block">Terakhir update</small>
          <strong><?= date('d M Y, H:i') ?></strong>
        </div>
      </div>

      <!-- KPI -->
      <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <div class="stat-number"><?= number_format($total_users ?? 0) ?></div>
                <div class="text-muted">Total Users</div>
              </div>
              <i class="fas fa-users fa-lg text-primary"></i>
            </div>
          </div>
        </div>
        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <div class="stat-number"><?= number_format($total_abstrak ?? 0) ?></div>
                <div class="text-muted">Total Abstrak</div>
              </div>
              <i class="fas fa-file-alt fa-lg text-success"></i>
            </div>
          </div>
        </div>
        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <div class="stat-number"><?= number_format($total_pembayaran ?? 0) ?></div>
                <div class="text-muted">Total Pembayaran</div>
              </div>
              <i class="fas fa-credit-card fa-lg text-warning"></i>
            </div>
          </div>
        </div>
        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <div class="stat-number">Rp <?= number_format($total_revenue ?? 0,0,',','.') ?></div>
                <div class="text-muted">Total Revenue</div>
              </div>
              <i class="fas fa-money-bill-wave fa-lg text-danger"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- CHARTS 1 -->
      <div class="row g-3 mb-3">
        <div class="col-lg-6">
          <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
              <div class="fw-semibold"><i class="fas fa-user-plus me-2 text-primary"></i>Pendaftaran Bulanan</div>
              <button class="btn btn-sm btn-outline-primary" onclick="refreshChart('users')">
                <i class="fas fa-sync-alt"></i>
              </button>
            </div>
            <div class="card-body" style="height:320px;">
              <canvas id="monthlyUsersChart"></canvas>
            </div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
              <div class="fw-semibold"><i class="fas fa-chart-area me-2 text-success"></i>Revenue Bulanan</div>
              <button class="btn btn-sm btn-outline-success" onclick="refreshChart('revenue')">
                <i class="fas fa-sync-alt"></i>
              </button>
            </div>
            <div class="card-body" style="height:320px;">
              <canvas id="monthlyRevenueChart"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- CHARTS 2 -->
      <div class="row g-3 mb-3">
        <div class="col-lg-6">
          <div class="card shadow-sm">
            <div class="card-header bg-white">
              <div class="fw-semibold"><i class="fas fa-chart-pie me-2 text-info"></i>Status Abstrak</div>
            </div>
            <div class="card-body">
              <div class="row g-2 mb-3">
                <div class="col-6 col-md-3"><div class="summary-info warning"><small class="text-muted">Menunggu</small><div class="fw-bold text-warning"><?= $abstrak_by_status['menunggu'] ?? 0 ?></div></div></div>
                <div class="col-6 col-md-3"><div class="summary-info info"><small class="text-muted">Sedang Review</small><div class="fw-bold text-info"><?= $abstrak_by_status['sedang_direview'] ?? 0 ?></div></div></div>
                <div class="col-6 col-md-3"><div class="summary-info success"><small class="text-muted">Diterima</small><div class="fw-bold text-success"><?= $abstrak_by_status['diterima'] ?? 0 ?></div></div></div>
                <div class="col-6 col-md-3"><div class="summary-info danger"><small class="text-muted">Ditolak</small><div class="fw-bold text-danger"><?= $abstrak_by_status['ditolak'] ?? 0 ?></div></div></div>
              </div>
              <div style="height:300px;"><canvas id="abstrakStatusChart"></canvas></div>
            </div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="card shadow-sm">
            <div class="card-header bg-white">
              <div class="fw-semibold"><i class="fas fa-users-cog me-2 text-secondary"></i>Distribusi Role User</div>
            </div>
            <div class="card-body">
              <div class="row g-2 mb-3">
                <div class="col-6 col-md-3"><div class="summary-info danger"><small class="text-muted">Admin</small><div class="fw-bold text-danger"><?= $user_by_role['admin'] ?? 0 ?></div></div></div>
                <div class="col-6 col-md-3"><div class="summary-info"><small class="text-muted">Presenter</small><div class="fw-bold text-primary"><?= $user_by_role['presenter'] ?? 0 ?></div></div></div>
                <div class="col-6 col-md-3"><div class="summary-info"><small class="text-muted">Audience</small><div class="fw-bold text-secondary"><?= $user_by_role['audience'] ?? 0 ?></div></div></div>
                <div class="col-6 col-md-3"><div class="summary-info success"><small class="text-muted">Reviewer</small><div class="fw-bold text-success"><?= $user_by_role['reviewer'] ?? 0 ?></div></div></div>
              </div>
              <div style="height:300px;"><canvas id="userRolesChart"></canvas></div>
            </div>
          </div>
        </div>
      </div>

      <!-- AKTIVITAS + TREN -->
      <div class="row g-3 mb-3">
        <div class="col-lg-4">
          <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold"><i class="fas fa-clock me-2 text-primary"></i>Aktivitas Terbaru</div>
            <div class="card-body">
              <?php if (!empty($recent_registrations)): ?>
                <?php foreach (array_slice($recent_registrations, 0, 5) as $u): ?>
                  <div class="d-flex align-items-center py-2 border-bottom">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:40px;height:40px;background:linear-gradient(135deg,#2563eb,#06b6d4);color:#fff;">
                      <i class="fas fa-user-plus"></i>
                    </div>
                    <div>
                      <div class="fw-semibold"><?= esc($u['nama_lengkap']) ?></div>
                      <small class="text-muted">Mendaftar sebagai <?= ucfirst($u['role']) ?> · <?= timeAgo($u['created_at']) ?></small>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="text-center text-muted py-4">
                  <i class="fas fa-inbox fa-2x mb-2"></i>
                  <div>Belum ada aktivitas</div>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <div class="col-lg-8">
          <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold"><i class="fas fa-chart-line me-2 text-info"></i>Tren 6 Bulan Terakhir</div>
            <div class="card-body" style="height:320px;">
              <canvas id="monthlyTrendsChart"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- EXPORT -->
      <div class="card shadow-sm">
        <div class="card-header bg-white fw-semibold"><i class="fas fa-download me-2 text-success"></i>Export Laporan</div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-3">
              <button class="export-card w-100 btn btn-light text-start" onclick="exportReport('users')">
                <i class="fas fa-users me-2 text-primary"></i> Data Users
                <div class="small text-muted">Export semua data users</div>
              </button>
            </div>
            <div class="col-md-3">
              <button class="export-card w-100 btn btn-light text-start" onclick="exportReport('abstrak')">
                <i class="fas fa-file-alt me-2 text-success"></i> Data Abstrak
                <div class="small text-muted">Export data abstrak & review</div>
              </button>
            </div>
            <div class="col-md-3">
              <button class="export-card w-100 btn btn-light text-start" onclick="exportReport('pembayaran')">
                <i class="fas fa-credit-card me-2 text-warning"></i> Data Pembayaran
                <div class="small text-muted">Export transaksi pembayaran</div>
              </button>
            </div>
            <div class="col-md-3">
              <button class="export-card w-100 btn btn-light text-start" onclick="exportReport('comprehensive')">
                <i class="fas fa-chart-line me-2 text-info"></i> Laporan Komprehensif
                <div class="small text-muted">Export laporan lengkap</div>
              </button>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<!-- libs -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
  :root{
    --primary-color:#2563eb; --info-color:#06b6d4; --success-color:#10b981; --warning-color:#f59e0b; --danger-color:#ef4444;
  }
  .summary-info{background:#f8fafc;border-radius:8px;padding:10px 12px;border-left:4px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center}
  .summary-info.warning{border-left-color:var(--warning-color)}
  .summary-info.info{border-left-color:var(--info-color)}
  .summary-info.success{border-left-color:var(--success-color)}
  .summary-info.danger{border-left-color:var(--danger-color)}
</style>

<script>
  // data dari server
  const labelsMonths   = <?= json_encode(array_column($monthly_stats ?? [], 'month')) ?>;
  const dataUsers      = <?= json_encode(array_map('intval', array_column($monthly_stats ?? [], 'users'))) ?>;
  const dataRevenue    = <?= json_encode(array_map('intval', array_column($monthly_stats ?? [], 'revenue'))) ?>;
  const dataAbstraks   = <?= json_encode(array_map('intval', array_column($monthly_stats ?? [], 'abstraks'))) ?>;

  let monthlyUsersChart, monthlyRevenueChart, abstrakStatusChart, userRolesChart, monthlyTrendsChart;

  document.addEventListener('DOMContentLoaded', () => {
    initCharts();
    animateNumbers();
  });

  function initCharts(){
    // users
    monthlyUsersChart = new Chart(document.getElementById('monthlyUsersChart').getContext('2d'), {
      type:'line',
      data:{ labels:labelsMonths, datasets:[{ label:'Pendaftaran User', data:dataUsers, borderColor:'#2563eb', backgroundColor:'rgba(37,99,235,.12)', borderWidth:3, fill:true, tension:.35, pointRadius:3 }] },
      options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{ y:{beginAtZero:true, grid:{color:'rgba(0,0,0,.05)'}}, x:{grid:{display:false}} } }
    });

    // revenue
    monthlyRevenueChart = new Chart(document.getElementById('monthlyRevenueChart').getContext('2d'), {
      type:'bar',
      data:{ labels:labelsMonths, datasets:[{ label:'Revenue', data:dataRevenue, backgroundColor:'rgba(16,185,129,.85)', borderColor:'#10b981', borderWidth:2, borderRadius:8, borderSkipped:false }] },
      options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{ y:{beginAtZero:true, ticks:{ callback:(v)=>'Rp '+Number(v).toLocaleString('id-ID') } } } }
    });

    // abstrak status
    abstrakStatusChart = new Chart(document.getElementById('abstrakStatusChart').getContext('2d'), {
      type:'doughnut',
      data:{
        labels:['Menunggu','Sedang Review','Diterima','Ditolak','Revisi'],
        datasets:[{ data:[
          <?= (int)($abstrak_by_status['menunggu'] ?? 0) ?>,
          <?= (int)($abstrak_by_status['sedang_direview'] ?? 0) ?>,
          <?= (int)($abstrak_by_status['diterima'] ?? 0) ?>,
          <?= (int)($abstrak_by_status['ditolak'] ?? 0) ?>,
          <?= (int)($abstrak_by_status['revisi'] ?? 0) ?>
        ], backgroundColor:['#f59e0b','#06b6d4','#10b981','#ef4444','#8b5cf6'], borderColor:'#fff', borderWidth:3 }]
      },
      options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ position:'bottom', labels:{ usePointStyle:true, padding:16 } } } }
    });

    // roles
    userRolesChart = new Chart(document.getElementById('userRolesChart').getContext('2d'), {
      type:'polarArea',
      data:{
        labels:['Admin','Presenter','Audience','Reviewer'],
        datasets:[{ data:[
          <?= (int)($user_by_role['admin'] ?? 0) ?>,
          <?= (int)($user_by_role['presenter'] ?? 0) ?>,
          <?= (int)($user_by_role['audience'] ?? 0) ?>,
          <?= (int)($user_by_role['reviewer'] ?? 0) ?>
        ], backgroundColor:['rgba(239,68,68,.75)','rgba(37,99,235,.75)','rgba(100,116,139,.75)','rgba(16,185,129,.75)'], borderColor:['#ef4444','#2563eb','#64748b','#10b981'], borderWidth:2 }]
      },
      options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ position:'bottom', labels:{ usePointStyle:true, padding:16 } } } }
    });

    // trends
    monthlyTrendsChart = new Chart(document.getElementById('monthlyTrendsChart').getContext('2d'), {
      type:'line',
      data:{ labels:labelsMonths, datasets:[
        { label:'Users', data:dataUsers, borderColor:'#2563eb', backgroundColor:'rgba(37,99,235,.10)', tension:.35, yAxisID:'y' },
        { label:'Abstrak', data:dataAbstraks, borderColor:'#10b981', backgroundColor:'rgba(16,185,129,.10)', tension:.35, yAxisID:'y' }
      ]},
      options:{ responsive:true, maintainAspectRatio:false, interaction:{mode:'index', intersect:false}, plugins:{legend:{position:'top'}}, scales:{ y:{ beginAtZero:true } } }
    });
  }

  function animateNumbers(){
    document.querySelectorAll('.stat-number').forEach(el=>{
      const isRp = el.textContent.includes('Rp');
      const final = parseInt(el.textContent.replace(/[^\d]/g,''))||0;
      let now = 0, step = Math.max(1, Math.floor(final/60));
      const tick = ()=>{ now += step; if(now>=final){ now = final; } el.textContent = isRp ? 'Rp '+now.toLocaleString('id-ID') : now.toLocaleString('id-ID'); if(now<final) requestAnimationFrame(tick); };
      tick();
    });
  }

  function refreshChart(type){
    Swal.fire({title:'Memuat data...', html:'<div class="loading-spinner"></div>', showConfirmButton:false, allowOutsideClick:false});
    // (opsional) panggil endpoint jika sudah tersedia:
    // fetch('<?= site_url('admin/laporan/chart-data') ?>?type='+type).then(r=>r.json()).then(json=>{ ...update dataset... })
    setTimeout(()=>{ Swal.close(); Swal.fire({icon:'success', title:'Data diperbarui!', timer:1200, showConfirmButton:false}); }, 800);
  }

  function exportReport(type){
    Swal.fire({
      title:'Export Laporan',
      text:'Unduh laporan '+type+' sekarang?',
      icon:'question',
      showCancelButton:true,
      confirmButtonColor:'#10b981', cancelButtonColor:'#6b7280',
      confirmButtonText:'Ya, Download', cancelButtonText:'Batal'
    }).then(res=>{
      if(res.isConfirmed){ window.location.href = '<?= site_url('admin/laporan/export') ?>?type='+encodeURIComponent(type)+'&format=csv'; }
    });
  }
</script>
