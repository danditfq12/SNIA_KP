<?php
// ============ DEFAULT VARS ============
$event = $event ?? [
  'id' => 0,'title'=>'Event','format'=>'both','is_active'=>0,
  'event_date'=>date('Y-m-d'),'event_time'=>date('H:i'),
  'description'=>'','location'=>null,'zoom_link'=>null,
  'registration_active'=>0,'abstract_submission_active'=>0,
  'max_participants'=>0
];
$title = $title ?? ('Detail Event - '.($event['title'] ?? 'Event'));

$stats = $stats ?? [
  'total_registrations'=>0,'verified_registrations'=>0,
  'total_abstracts'=>0,'total_revenue'=>0,
  'presenter_registrations'=>0,'audience_online_registrations'=>0,'audience_offline_registrations'=>0,
  'presenter_revenue'=>0,'audience_online_revenue'=>0,'audience_offline_revenue'=>0,
  'online_revenue'=>0,'offline_revenue'=>0,
];

$pricing_matrix = $pricing_matrix ?? [
  'presenter'=>['offline'=>0],
  'audience'=>['online'=>0,'offline'=>0],
];

$recent_registrations = $recent_registrations ?? []; // nama_lengkap,email,role,participation_type,status,tanggal_bayar,jumlah
$registration_open    = isset($registration_open) ? (bool)$registration_open : !empty($event['registration_active']);
$abstract_open        = isset($abstract_open) ? (bool)$abstract_open : !empty($event['abstract_submission_active']);

$fmt = strtolower($event['format'] ?? 'both'); // online|offline|both
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_admin') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- HEADER BIRU -->
      <div class="header-section header-blue mb-3">
        <div class="row align-items-center">
          <div class="col-md-8">
            <div class="d-flex align-items-center gap-2 gap-md-3">
              <a href="<?= site_url('admin/event') ?>" class="btn btn-light btn-sm text-primary">
                <i class="bi bi-arrow-left"></i>
              </a>
              <div>
                <h3 class="welcome-text mb-1"><?= esc($event['title']) ?></h3>
                <div class="text-muted">
                  <i class="bi bi-calendar-event me-1"></i><?= date('d F Y', strtotime($event['event_date'])) ?>
                  <i class="bi bi-dot"></i>
                  <i class="bi bi-clock me-1"></i><?= date('H:i', strtotime($event['event_time'])) ?>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-4 text-start text-md-end mt-3 mt-md-0">
            <div class="d-inline-flex flex-wrap gap-2">
              <span class="badge bg-light text-dark fs-6">
                <i class="bi <?= $fmt==='online'?'bi-camera-video':($fmt==='offline'?'bi-geo-alt':'bi-globe2') ?> me-1"></i>
                <?= ucfirst($fmt==='both' ? 'Hybrid' : $fmt) ?>
              </span>
              <span class="badge <?= !empty($event['is_active'])?'bg-success':'bg-secondary' ?> fs-6">
                <?= !empty($event['is_active'])?'Aktif':'Nonaktif' ?>
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- KPI -->
      <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
          <div class="stat-card h-100">
            <div class="stat-icon bg-primary text-white"><i class="bi bi-people"></i></div>
            <div class="stat-number text-primary"><?= (int)$stats['total_registrations'] ?></div>
            <div class="text-muted">Total Registrasi</div>
          </div>
        </div>
        <div class="col-6 col-xl-3">
          <div class="stat-card h-100">
            <div class="stat-icon bg-success text-white"><i class="bi bi-check2-circle"></i></div>
            <div class="stat-number text-success"><?= (int)$stats['verified_registrations'] ?></div>
            <div class="text-muted">Terverifikasi</div>
          </div>
        </div>
        <div class="col-6 col-xl-3">
          <div class="stat-card h-100">
            <div class="stat-icon bg-warning text-white"><i class="bi bi-file-earmark-text"></i></div>
            <div class="stat-number text-warning"><?= (int)$stats['total_abstracts'] ?></div>
            <div class="text-muted">Total Abstrak</div>
          </div>
        </div>
        <div class="col-6 col-xl-3">
          <div class="stat-card h-100">
            <div class="stat-icon bg-info text-white"><i class="bi bi-cash-coin"></i></div>
            <div class="stat-number text-info">Rp <?= number_format((float)$stats['total_revenue'],0,',','.') ?></div>
            <div class="text-muted">Total Revenue</div>
          </div>
        </div>
      </div>

      <div class="row">
        <!-- LEFT -->
        <div class="col-lg-8">
          <!-- Participant Breakdown -->
          <div class="participant-breakdown">
            <h5 class="mb-4"><i class="bi bi-pie-chart me-2 text-primary"></i>Breakdown Peserta</h5>

            <!-- Presenter -->
            <div class="participant-type-card presenter">
              <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                  <div class="me-3">
                    <div class="stat-icon bg-primary bg-opacity-25 text-primary" style="width:50px;height:50px;font-size:20px;">
                      <i class="bi bi-mic"></i>
                    </div>
                  </div>
                  <div>
                    <h6 class="mb-1">Presenter (Offline Only)</h6>
                    <div class="text-muted small">Presentasi tatap muka</div>
                  </div>
                </div>
                <div class="text-end">
                  <div class="h3 mb-0 text-primary"><?= (int)$stats['presenter_registrations'] ?></div>
                  <small class="text-muted">peserta</small>
                </div>
              </div>
            </div>

            <!-- Audience Online -->
            <?php if ($fmt!=='offline'): ?>
            <div class="participant-type-card audience-online">
              <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                  <div class="me-3">
                    <div class="stat-icon bg-info bg-opacity-25 text-info" style="width:50px;height:50px;font-size:20px;">
                      <i class="bi bi-camera-video"></i>
                    </div>
                  </div>
                  <div>
                    <h6 class="mb-1">Audience Online</h6>
                    <div class="text-muted small">Partisipasi virtual</div>
                  </div>
                </div>
                <div class="text-end">
                  <div class="h3 mb-0 text-info"><?= (int)$stats['audience_online_registrations'] ?></div>
                  <small class="text-muted">peserta</small>
                </div>
              </div>
            </div>
            <?php endif; ?>

            <!-- Audience Offline -->
            <?php if ($fmt!=='online'): ?>
            <div class="participant-type-card audience-offline">
              <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                  <div class="me-3">
                    <div class="stat-icon bg-success bg-opacity-25 text-success" style="width:50px;height:50px;font-size:20px;">
                      <i class="bi bi-geo-alt"></i>
                    </div>
                  </div>
                  <div>
                    <h6 class="mb-1">Audience Offline</h6>
                    <div class="text-muted small">Partisipasi tatap muka</div>
                  </div>
                </div>
                <div class="text-end">
                  <div class="h3 mb-0 text-success"><?= (int)$stats['audience_offline_registrations'] ?></div>
                  <small class="text-muted">peserta</small>
                </div>
              </div>
            </div>
            <?php endif; ?>
          </div>

          <!-- Revenue Breakdown -->
          <?php if ((float)$stats['total_revenue'] > 0): ?>
          <div class="revenue-card">
            <div class="row align-items-center">
              <div class="col-md-8">
                <h5 class="mb-3"><i class="bi bi-cash-coin me-2"></i>Revenue Breakdown</h5>
                <div class="row">
                  <div class="col-6 mb-2">
                    <small class="opacity-75">Presenter Revenue</small>
                    <div class="h5 mb-0">Rp <?= number_format((float)$stats['presenter_revenue'],0,',','.') ?></div>
                  </div>
                  <div class="col-6 mb-2">
                    <small class="opacity-75">Audience Revenue</small>
                    <div class="h5 mb-0">
                      Rp <?= number_format((float)(($stats['audience_online_revenue'] ?? 0)+($stats['audience_offline_revenue'] ?? 0)),0,',','.') ?>
                    </div>
                  </div>
                </div>
                <?php if (!empty($stats['online_revenue']) && !empty($stats['offline_revenue'])): ?>
                <div class="row mt-3 pt-3 border-top border-light">
                  <div class="col-6">
                    <small class="opacity-75">Online Revenue</small>
                    <div class="h6 mb-0">Rp <?= number_format((float)$stats['online_revenue'],0,',','.') ?></div>
                  </div>
                  <div class="col-6">
                    <small class="opacity-75">Offline Revenue</small>
                    <div class="h6 mb-0">Rp <?= number_format((float)$stats['offline_revenue'],0,',','.') ?></div>
                  </div>
                </div>
                <?php endif; ?>
              </div>
              <div class="col-md-4 text-center">
                <div class="h2 mb-1">Rp <?= number_format((float)$stats['total_revenue'],0,',','.') ?></div>
                <div class="opacity-75">Total Revenue</div>
              </div>
            </div>
          </div>
          <?php endif; ?>

          <!-- Recent Registrations -->
          <div class="table-wrap">
            <h5 class="mb-3"><i class="bi bi-clock-history me-2 text-primary"></i>Registrasi Terbaru</h5>
            <div class="table-responsive">
              <table class="table table-hover align-middle">
                <thead class="table-light">
                  <tr>
                    <th>Nama</th>
                    <th>Role</th>
                    <th>Tipe</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                    <th>Jumlah</th>
                  </tr>
                </thead>
                <tbody>
                <?php if (!empty($recent_registrations)): ?>
                  <?php foreach (array_slice($recent_registrations,0,10) as $reg): ?>
                  <tr>
                    <td>
                      <div class="fw-semibold"><?= esc($reg['nama_lengkap'] ?? '-') ?></div>
                      <small class="text-muted"><?= esc($reg['email'] ?? '') ?></small>
                    </td>
                    <td>
                      <span class="badge bg-<?= ($reg['role']??'')==='presenter'?'primary':'info' ?>">
                        <?= ucfirst($reg['role'] ?? '-') ?>
                      </span>
                    </td>
                    <td>
                      <?php $ptype = $reg['participation_type'] ?? '-'; ?>
                      <span class="badge <?= $ptype==='online'?'bg-info text-white':'bg-success text-white' ?>">
                        <i class="bi <?= $ptype==='online'?'bi-camera-video':'bi-geo-alt' ?> me-1"></i><?= ucfirst($ptype) ?>
                      </span>
                    </td>
                    <td>
                      <?php
                        $st = $reg['status'] ?? 'pending';
                        $cls = $st==='verified'?'success':($st==='pending'?'warning':'danger');
                      ?>
                      <span class="badge bg-<?= $cls ?>"><?= ucfirst($st) ?></span>
                    </td>
                    <td><small><?= !empty($reg['tanggal_bayar']) ? date('d/m/Y H:i', strtotime($reg['tanggal_bayar'])) : '-' ?></small></td>
                    <td><span class="fw-semibold">Rp <?= number_format((float)($reg['jumlah'] ?? 0),0,',','.') ?></span></td>
                  </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                      <i class="bi bi-inbox fs-3 d-block mb-2"></i>Belum ada registrasi
                    </td>
                  </tr>
                <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- RIGHT -->
        <div class="col-lg-4">
          <!-- Informasi Event -->
          <div class="stat-card mb-3">
            <h6 class="mb-3"><i class="bi bi-info-circle me-2 text-primary"></i>Informasi Event</h6>
            <div class="mb-3">
              <small class="text-muted d-block">Deskripsi</small>
              <div><?= esc($event['description'] ?: 'Tidak ada deskripsi') ?></div>
            </div>

            <?php if (!empty($event['location'])): ?>
            <div class="mb-3">
              <small class="text-muted d-block">Lokasi</small>
              <div><i class="bi bi-geo-alt text-success me-1"></i><?= esc($event['location']) ?></div>
            </div>
            <?php endif; ?>

            <?php if (!empty($event['zoom_link'])): ?>
            <div class="mb-3">
              <small class="text-muted d-block">Link Online</small>
              <div>
                <i class="bi bi-camera-video text-info me-1"></i>
                <a href="<?= esc($event['zoom_link']) ?>" target="_blank" class="text-decoration-none">Zoom Meeting</a>
              </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($event['max_participants'])): ?>
            <div class="mb-3">
              <small class="text-muted d-block">Kapasitas Maksimal</small>
              <div><i class="bi bi-people text-warning me-1"></i><?= number_format((int)$event['max_participants']) ?> peserta</div>
            </div>
            <?php endif; ?>

            <div class="mb-2">
              <small class="text-muted d-block">Status Pendaftaran</small>
              <span class="badge <?= $registration_open ? 'bg-success':'bg-danger' ?>"><?= $registration_open ? 'Buka':'Tutup' ?></span>
            </div>
            <div>
              <small class="text-muted d-block">Submit Abstrak</small>
              <span class="badge <?= $abstract_open ? 'bg-success':'bg-danger' ?>"><?= $abstract_open ? 'Buka':'Tutup' ?></span>
            </div>
          </div>

          <!-- Harga -->
          <div class="stat-card mb-3">
            <h6 class="mb-3"><i class="bi bi-cash-coin me-2 text-success"></i>Harga Registrasi</h6>
            <div class="mb-3 p-3 rounded bg-primary bg-opacity-10 d-flex justify-content-between align-items-center">
              <div>
                <div class="fw-semibold text-primary">Presenter</div>
                <small class="text-muted">Offline Only</small>
              </div>
              <div class="h5 mb-0">Rp <?= number_format((float)($pricing_matrix['presenter']['offline'] ?? 0),0,',','.') ?></div>
            </div>

            <div class="p-3 rounded bg-info bg-opacity-10">
              <div class="fw-semibold text-info mb-2">Audience</div>
              <?php if ($fmt!=='offline'): ?>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <small class="text-muted">Online</small>
                <span class="fw-semibold">Rp <?= number_format((float)($pricing_matrix['audience']['online'] ?? 0),0,',','.') ?></span>
              </div>
              <?php endif; ?>
              <?php if ($fmt!=='online'): ?>
              <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">Offline</small>
                <span class="fw-semibold">Rp <?= number_format((float)($pricing_matrix['audience']['offline'] ?? 0),0,',','.') ?></span>
              </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Aksi Cepat -->
          <div class="stat-card">
            <h6 class="mb-3"><i class="bi bi-lightning-charge me-2 text-warning"></i>Aksi Cepat</h6>
            <div class="d-grid gap-2">
              <a href="<?= site_url('admin/event/sessions/'.$event['id']) ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-sliders me-1"></i>Kelola Sesi
              </a>
              <button class="btn btn-outline-<?= !empty($event['registration_active'])?'warning':'success' ?> btn-sm"
                      onclick="toggleRegistration(<?= (int)$event['id'] ?>)">
                <i class="bi <?= !empty($event['registration_active'])?'bi-pause':'bi-play' ?> me-1"></i>
                <?= !empty($event['registration_active'])?'Tutup':'Buka' ?> Pendaftaran
              </button>
              <button class="btn btn-outline-<?= !empty($event['abstract_submission_active'])?'warning':'success' ?> btn-sm"
                      onclick="toggleAbstract(<?= (int)$event['id'] ?>)">
                <i class="bi <?= !empty($event['abstract_submission_active'])?'bi-pause':'bi-play' ?> me-1"></i>
                <?= !empty($event['abstract_submission_active'])?'Tutup':'Buka' ?> Abstrak
              </button>
              <a href="<?= site_url('admin/pembayaran?event_id='.$event['id']) ?>" class="btn btn-outline-info btn-sm">
                <i class="bi bi-credit-card me-1"></i>Lihat Pembayaran
              </a>
              <a href="<?= site_url('admin/abstrak?event_id='.$event['id']) ?>" class="btn btn-outline-warning btn-sm">
                <i class="bi bi-file-earmark-text me-1"></i>Lihat Abstrak
              </a>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
  :root{
    --primary-color:#2563eb; --secondary-color:#64748b; --success-color:#10b981; --warning-color:#f59e0b; --danger-color:#ef4444; --info-color:#06b6d4;
  }
  body{ background:linear-gradient(135deg,#f8fafc 0%,#e2e8f0 100%); }

  /* Header biru seragam */
  .header-section.header-blue{
    background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%);
    color:#fff; padding:28px 24px; border-radius:16px; box-shadow:0 8px 28px rgba(0,0,0,.12);
  }
  .header-section.header-blue .welcome-text{ color:#fff; font-weight:800; font-size:2rem; }
  .header-section.header-blue .text-muted{ color:rgba(255,255,255,.95)!important; }

  .stat-card{
    background:#fff; border-radius:14px; padding:20px; box-shadow:0 8px 28px rgba(0,0,0,.08);
    border:1px solid #eef2f6; position:relative; overflow:hidden;
  }
  .stat-card .stat-icon{
    width:56px;height:56px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;margin-bottom:6px;
  }
  .stat-number{ font-size:2rem;font-weight:800;line-height:1; }

  .participant-breakdown{ background:#fff;border-radius:16px;padding:24px;box-shadow:0 8px 32px rgba(0,0,0,.08);margin-bottom:1rem; }
  .participant-type-card{ background:#f8fafc;border-radius:12px;padding:20px;margin-bottom:12px;border-left:4px solid var(--primary-color); }
  .participant-type-card.audience-online{ border-left-color:var(--info-color); }
  .participant-type-card.audience-offline{ border-left-color:var(--success-color); }

  .revenue-card{ background:linear-gradient(135deg, var(--success-color), #059669); color:#fff; border-radius:16px; padding:24px; margin-bottom:1rem; }

  .table-wrap{ background:#fff;border-radius:16px;box-shadow:0 8px 32px rgba(0,0,0,.08);padding:16px; }
  #content main>.container-fluid{ margin-top:.25rem; }
</style>

<script>
  // ===== CSRF (CI4) =====
  const csrfName = '<?= csrf_token() ?>';
  let   csrfHash = '<?= csrf_hash() ?>';

  function makeRequest(url, label){
    const body = new FormData();
    body.append(csrfName, csrfHash);

    fetch(url, { method:'POST', body })
      .then(r=>r.json())
      .then(res=>{
        if(res && res[csrfName]) csrfHash = res[csrfName];
        if(res?.success){
          Swal.fire({icon:'success', title:'Berhasil!', text: res.message || ('Berhasil '+label), timer:1200, showConfirmButton:false})
              .then(()=> location.reload());
        }else{
          throw new Error(res?.message || ('Gagal '+label));
        }
      })
      .catch(err=> Swal.fire('Error!', err.message, 'error'));
  }

  function toggleRegistration(eventId){
    Swal.fire({
      title:'Ubah Status Pendaftaran?', icon:'question',
      showCancelButton:true, confirmButtonText:'Ya, ubah', cancelButtonText:'Batal'
    }).then(r=>{ if(r.isConfirmed){ makeRequest('<?= site_url("admin/event/toggle-registration") ?>/'+eventId, 'ubah pendaftaran'); }});
  }

  function toggleAbstract(eventId){
    Swal.fire({
      title:'Ubah Status Submit Abstrak?', icon:'question',
      showCancelButton:true, confirmButtonText:'Ya, ubah', cancelButtonText:'Batal'
    }).then(r=>{ if(r.isConfirmed){ makeRequest('<?= site_url("admin/event/toggle-abstract") ?>/'+eventId, 'ubah submit abstrak'); }});
  }

  // Flash (opsional, jika belum dari partial alerts)
  <?php if (session('success')): ?>
    window.Swal?.fire({icon:'success', title:'Berhasil!', text:'<?= esc(session('success')) ?>', timer:2000, showConfirmButton:false});
  <?php endif; ?>
  <?php if (session('error')): ?>
    window.Swal?.fire({icon:'error', title:'Error!', text:'<?= esc(session('error')) ?>'});
  <?php endif; ?>
</script>
