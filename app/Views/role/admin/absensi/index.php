<?php
// ====== DEFAULT VARS (aman kalau controller belum isi) ======
$title            = $title ?? 'Kelola Absensi (QR)';
$events           = $events ?? [];            // id, title, event_date, event_time
$selectedEventId  = $selectedEventId ?? null;
$currentEvent     = $currentEvent ?? null;    // ['title','event_date','event_time', ...]
$eventStats       = $eventStats ?? null;      // ['total_registered','total_attended','attendance_rate','event_status']
$absensiData      = $absensiData ?? [];       // daftar attendance
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_admin') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- HEADER (seragam dengan contoh) -->
      <div class="header-section header-blue d-flex justify-content-between align-items-center mb-3">
        <div>
          <h3 class="welcome-text mb-1">
            <i class="bi bi-qr-code-scan me-2"></i>Enhanced QR Attendance Management
          </h3>
          <div class="text-muted">Generate & kelola QR untuk presensi, lengkap dengan status real-time</div>
        </div>
        <div class="text-end d-none d-md-block">
          <small class="text-muted d-block">Waktu Sekarang</small>
          <strong id="currentTime"><?= date('d M Y, H:i:s') ?></strong>
          <div class="mt-2">
            <a href="<?= site_url('qr/scanner') ?>" class="btn btn-light btn-sm">
              <i class="bi bi-camera-video me-1"></i>Buka QR Scanner
            </a>
          </div>
        </div>
      </div>

      <!-- KPI (seragam style stat-card) -->
      <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-primary"><i class="bi bi-people"></i></div>
              <div class="ms-3">
                <div class="stat-number" id="kpiTotalRegistered"><?= !empty($eventStats) ? number_format($eventStats['total_registered']) : '-' ?></div>
                <div class="text-muted">Terdaftar</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-success"><i class="bi bi-person-check"></i></div>
              <div class="ms-3">
                <div class="stat-number" id="kpiTotalAttended"><?= !empty($eventStats) ? number_format($eventStats['total_attended']) : '-' ?></div>
                <div class="text-muted">Hadir</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-info"><i class="bi bi-graph-up"></i></div>
              <div class="ms-3">
                <div class="stat-number" id="kpiRate"><?= !empty($eventStats) ? $eventStats['attendance_rate'].'%' : '-%' ?></div>
                <div class="text-muted">Attendance Rate</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-warning"><i class="bi bi-broadcast"></i></div>
              <div class="ms-3">
                <?php
                  $badgeClass = 'status-upcoming';
                  $badgeIcon  = 'bi bi-clock';
                  $badgeText  = 'Belum Dimulai';
                  if (!empty($eventStats)) {
                    $badgeText = $eventStats['event_status'];
                    if ($badgeText === 'Segera Dimulai') { $badgeClass='status-starting-soon'; $badgeIcon='bi bi-play-circle'; }
                    elseif ($badgeText === 'Sedang Berlangsung') { $badgeClass='status-ongoing'; $badgeIcon='bi bi-broadcast-pin'; }
                    elseif ($badgeText === 'Sudah Selesai') { $badgeClass='status-finished'; $badgeIcon='bi bi-check-circle'; }
                  }
                ?>
                <div class="event-status-badge <?= $badgeClass ?>">
                  <i class="<?= $badgeIcon ?>"></i> <span><?= $badgeText ?></span>
                </div>
                <div class="text-muted small mt-1">Status Event</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- FILTER / PILIH EVENT -->
      <div class="card shadow-sm mb-3">
        <div class="card-header bg-primary text-white">
          <h6 class="mb-0"><i class="bi bi-calendar-check me-2"></i>Pilih Event untuk Generate QR</h6>
        </div>
        <div class="card-body">
          <form method="GET" action="<?= site_url('admin/absensi') ?>" class="mb-3">
            <div class="row g-2">
              <div class="col-md-8">
                <select name="event_id" id="eventSelect" class="form-select" onchange="this.form.submit()">
                  <option value="">-- Pilih Event --</option>
                  <?php if (!empty($events)): ?>
                    <?php foreach ($events as $event): ?>
                      <option value="<?= $event['id'] ?>" <?= ($selectedEventId == $event['id']) ? 'selected' : '' ?>>
                        <?= esc($event['title']) ?> - <?= date('d M Y', strtotime($event['event_date'])) ?>
                      </option>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </select>
              </div>
              <div class="col-md-4">
                <button type="button" class="btn btn-info btn-custom w-100" onclick="generateMultipleQRCodes()" id="generateQRBtn" <?= !$selectedEventId ? 'disabled' : '' ?>>
                  <i class="bi bi-qr-code me-1"></i>Generate Semua QR
                  <span class="loading-spinner ms-1" style="display:none;">
                    <i class="bi bi-arrow-repeat"></i>
                  </span>
                </button>
              </div>
            </div>
          </form>

          <!-- Ringkas status event terpilih -->
          <?php if ($currentEvent): ?>
            <div class="alert alert-info mb-0">
              <div class="row align-items-center">
                <div class="col-md-8">
                  <h6 class="mb-1">Event Terpilih: <?= esc($currentEvent['title']) ?></h6>
                  <small>
                    <i class="bi bi-calendar3 me-1"></i><?= date('d F Y', strtotime($currentEvent['event_date'])) ?>
                    <i class="bi bi-clock ms-3 me-1"></i><?= date('H:i', strtotime($currentEvent['event_time'])) ?> WIB
                  </small>
                </div>
                <div class="col-md-4 text-end">
                  <div id="eventStatusDisplay">
                    <?php 
                      $statusClass = 'status-upcoming';
                      $statusText  = 'Belum Dimulai';
                      $statusIcon  = 'bi bi-clock';
                      if (!empty($eventStats)) {
                        $statusText = $eventStats['event_status'];
                        if ($statusText==='Segera Dimulai'){ $statusClass='status-starting-soon'; $statusIcon='bi bi-play-circle'; }
                        elseif ($statusText==='Sedang Berlangsung'){ $statusClass='status-ongoing'; $statusIcon='bi bi-broadcast-pin'; }
                        elseif ($statusText==='Sudah Selesai'){ $statusClass='status-finished'; $statusIcon='bi bi-check-circle'; }
                      }
                    ?>
                    <div class="event-status-badge <?= $statusClass ?>">
                      <i class="<?= $statusIcon ?>"></i>
                      <span><?= $statusText ?></span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- AREA HASIL GENERATE QR -->
      <div id="qrCodesArea" style="display:none;">
        <div class="card shadow-sm mb-3">
          <div class="card-header bg-info text-white">
            <div class="d-flex justify-content-between align-items-center">
              <h6 class="mb-0"><i class="bi bi-qr-code me-2"></i>Generated QR Codes</h6>
              <div class="d-flex gap-2">
                <button class="btn btn-light btn-sm" onclick="printAllQRCodes()">
                  <i class="bi bi-printer me-1"></i>Print All
                </button>
                <button class="btn btn-outline-light btn-sm" onclick="downloadAllQRCodes()">
                  <i class="bi bi-download me-1"></i>Download
                </button>
              </div>
            </div>
          </div>
          <div class="card-body">
            <div id="eventInfo" class="mb-3"><!-- diisi JS --></div>

            <div class="qr-stats" id="qrStats">
              <!-- diisi JS -->
            </div>

            <div class="qr-grid" id="qrGrid">
              <!-- diisi JS -->
            </div>
          </div>
        </div>
      </div>

      <?php if ($currentEvent): ?>
        <!-- TOOLS ABSENSI -->
        <div class="row g-3 mb-3">
          <div class="col-lg-8">
            <div class="card shadow-sm h-100">
              <div class="card-header bg-warning text-white">
                <h6 class="mb-0"><i class="bi bi-people me-2"></i>Attendance Management</h6>
              </div>
              <div class="card-body">
                <div class="row g-2">
                  <div class="col-md-4">
                    <button class="btn btn-outline-primary w-100" onclick="showBulkMarkModal()">
                      <i class="bi bi-people-fill me-1"></i>Bulk Mark
                    </button>
                  </div>
                  <div class="col-md-4">
                    <button class="btn btn-outline-success w-100" onclick="exportAttendance()">
                      <i class="bi bi-file-earmark-excel me-1"></i>Export Excel
                    </button>
                  </div>
                  <div class="col-md-4">
                    <button class="btn btn-outline-warning w-100" onclick="showManualMarkModal()">
                      <i class="bi bi-person-plus me-1"></i>Manual Mark
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div><!-- /col -->

          <div class="col-lg-4">
            <div class="card shadow-sm h-100">
              <div class="card-header bg-secondary text-white">
                <h6 class="mb-0"><i class="bi bi-arrow-repeat me-2"></i>Real-time Updates</h6>
              </div>
              <div class="card-body">
                <button class="btn btn-primary w-100 mb-2" onclick="refreshAttendanceData()">
                  <i class="bi bi-arrow-clockwise me-1"></i>Refresh Data
                </button>
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="autoRefresh" onchange="toggleAutoRefresh()">
                  <label class="form-check-label" for="autoRefresh">Auto-refresh (2 menit)</label>
                </div>
                <small class="text-muted">Last update: <span id="lastUpdate"><?= date('H:i:s') ?></span></small>
              </div>
            </div>
          </div>
        </div>

        <!-- TABEL ABSENSI -->
        <div class="card shadow-sm">
          <div class="card-header bg-secondary text-white">
            <div class="row align-items-center g-2">
              <div class="col">
                <h6 class="mb-0"><i class="bi bi-list-check me-2"></i>Attendance Records</h6>
              </div>
              <div class="col-auto">
                <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="Cari peserta..." onkeyup="searchAttendance()">
              </div>
            </div>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover mb-0" id="attendanceTable">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Partisipasi</th>
                    <th>Waktu Scan</th>
                    <th>Status</th>
                    <th>Ditandai Oleh</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!empty($absensiData)): ?>
                    <?php foreach ($absensiData as $index => $attendance): ?>
                      <tr>
                        <td><?= $index + 1 ?></td>
                        <td>
                          <div class="fw-semibold"><?= esc($attendance['nama_lengkap']) ?></div>
                          <?php if (!empty($attendance['institusi'])): ?>
                            <small class="text-muted"><?= esc($attendance['institusi']) ?></small>
                          <?php endif; ?>
                        </td>
                        <td><?= esc($attendance['email']) ?></td>
                        <td>
                          <span class="badge bg-<?= $attendance['role'] == 'presenter' ? 'primary' : 'info' ?>">
                            <?= ucfirst($attendance['role']) ?>
                          </span>
                        </td>
                        <td>
                          <span class="badge bg-<?= ($attendance['participation_type'] ?? 'offline') == 'online' ? 'info' : 'success' ?>">
                            <?= ucfirst($attendance['participation_type'] ?? 'offline') ?>
                          </span>
                        </td>
                        <td>
                          <div><?= date('d M Y', strtotime($attendance['waktu_scan'])) ?></div>
                          <small class="text-muted"><?= date('H:i:s', strtotime($attendance['waktu_scan'])) ?></small>
                        </td>
                        <td>
                          <span class="badge bg-<?= $attendance['status'] == 'hadir' ? 'success' : 'danger' ?>">
                            <i class="bi bi-<?= $attendance['status'] == 'hadir' ? 'check' : 'x' ?>-circle me-1"></i>
                            <?= ucfirst($attendance['status']) ?>
                          </span>
                        </td>
                        <td>
                          <?php if (!empty($attendance['marked_by_admin'])): ?>
                            <span class="badge bg-warning text-dark">
                              <i class="bi bi-person-gear me-1"></i>Admin
                            </span>
                          <?php else: ?>
                            <span class="badge bg-secondary">
                              <i class="bi bi-qr-code me-1"></i>QR Scan
                            </span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <button class="btn btn-sm btn-outline-danger"
                                  onclick="removeAttendanceWithModal(<?= $attendance['id_absensi'] ?>, '<?= esc($attendance['nama_lengkap']) ?>')"
                                  title="Hapus presensi">
                            <i class="bi bi-trash"></i>
                          </button>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="9" class="text-center py-4">
                        <i class="bi bi-people-x fs-3 text-muted d-block mb-2"></i>
                        <div class="text-muted">Belum ada data absensi</div>
                      </td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      <?php else: ?>
        <div class="p-4 text-center border rounded-3 bg-light-subtle">
          <div class="mb-2"><i class="bi bi-calendar-plus fs-3 text-secondary"></i></div>
          <div class="fw-semibold">Pilih Event</div>
          <div class="text-muted small">Pilih event pada bagian atas untuk mengelola QR & absensi.</div>
        </div>
      <?php endif; ?>

    </div>
  </main>
</div>

<!-- MODALS (pakai fungsi/ID sama) -->
<div class="modal fade" id="qrDetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title"><i class="bi bi-qr-code me-2"></i><span id="modalQRTitle">QR Code Details</span></h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body text-center">
      <div id="modalQRDisplay" class="modal-qr-display"></div>
      <div class="mt-3"><div class="qr-token" id="modalQRToken"></div></div>
      <div class="mt-3">
        <div class="alert alert-info text-start">
          <h6 class="mb-2"><i class="bi bi-info-circle me-2"></i>Cara Pakai</h6>
          <ol class="mb-0">
            <li>Tampilkan QR ini ke peserta</li>
            <li>Peserta scan dengan QR scanner / Google Lens</li>
            <li>Peserta akan diarahkan ke halaman presensi</li>
            <li>Sistem akan memvalidasi & mencatat kehadiran otomatis</li>
          </ol>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      <button type="button" class="btn btn-info" onclick="copyQRToken()"><i class="bi bi-clipboard me-1"></i>Copy Token</button>
      <button type="button" class="btn btn-success" onclick="copyQRURL()"><i class="bi bi-link-45deg me-1"></i>Copy URL</button>
      <button type="button" class="btn btn-primary" onclick="printQRCode()"><i class="bi bi-printer me-1"></i>Print</button>
    </div>
  </div></div>
</div>

<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header bg-danger text-white">
      <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Konfirmasi Hapus</h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body text-center">
      <i class="bi bi-person-x fs-1 text-danger d-block mb-2"></i>
      <h6>Hapus Data Presensi?</h6>
      <p class="text-muted" id="deleteMessage"></p>
      <div class="alert alert-warning mb-0"><i class="bi bi-exclamation-circle me-2"></i>Tindakan ini tidak bisa dibatalkan!</div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i>Batal</button>
      <button type="button" class="btn btn-danger" id="confirmDeleteBtn"><i class="bi bi-trash me-1"></i>Ya, Hapus</button>
    </div>
  </div></div>
</div>

<?= $this->include('partials/footer') ?>

<!-- QRCode lib (tidak mengubah fungsi existing) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode/1.5.3/qrcode.min.js"></script>

<style>
  /* ====== THEME ALIAS dari contoh ====== */
  :root{
    --primary-color:#2563eb; --info-color:#06b6d4; --success-color:#10b981; --warning-color:#f59e0b; --danger-color:#ef4444;
  }
  body{ background:linear-gradient(135deg,#f8fafc 0%,#e2e8f0 100%); font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; }

  .header-section.header-blue{
    background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%);
    color:#fff; padding:28px 24px; border-radius:16px; box-shadow:0 8px 28px rgba(0,0,0,.12);
  }
  .header-section.header-blue .welcome-text{ color:#fff; font-weight:800; font-size:2rem; }
  .header-section.header-blue .text-muted, .header-section.header-blue strong{ color:rgba(255,255,255,.92)!important; }

  .welcome-text{ color:var(--primary-color); font-weight:700; }

  .stat-card{
    background:#fff; border-radius:14px; padding:18px; box-shadow:0 8px 28px rgba(0,0,0,.08);
    border-left:4px solid #e9ecef; position:relative; overflow:hidden;
  }
  .stat-card:before{
    content:''; position:absolute; left:0; top:0; height:4px; width:100%;
    background:linear-gradient(90deg,var(--primary-color),var(--info-color));
  }
  .stat-icon{ width:52px; height:52px; border-radius:12px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:20px; }
  .stat-number{ font-size:1.8rem; font-weight:800; color:#1e293b; line-height:1; }

  /* ====== QR AREA styling (sesuai komponen) ====== */
  .qr-stats{ display:grid; grid-template-columns:repeat(auto-fit,minmax(120px,1fr)); gap:15px; margin-bottom:10px; }
  .qr-grid{ display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:20px; }

  .qr-card{
    background:#fff; border-radius:16px; padding:20px; border:1px solid rgba(226,232,240,.9);
    box-shadow:0 8px 32px rgba(0,0,0,.08); transition:.25s; border-left:4px solid var(--primary-color);
  }
  .qr-card:hover{ transform:translateY(-2px); box-shadow:0 12px 40px rgba(0,0,0,.15); }
  .qr-card.priority-1{ border-left-color:#6366f1; }
  .qr-card.priority-2{ border-left-color:#8b5cf6; }
  .qr-card.priority-3{ border-left-color:var(--info-color); }
  .qr-card.priority-4{ border-left-color:var(--success-color); }

  .qr-label{ font-weight:700; font-size:1rem; margin-bottom:6px; display:flex; align-items:center; gap:8px; }
  .qr-description{ font-size:.85rem; color:#6b7280; margin-bottom:10px; }
  .qr-code-container{ text-align:center; padding:15px; background:#f8fafc; border-radius:10px; margin:15px 0; }
  .qr-actions{ display:flex; gap:8px; flex-wrap:wrap; margin-top:12px; }
  .btn-qr{ flex:1; min-width:80px; border-radius:10px; font-size:.8rem; padding:6px 10px; }
  .qr-token{ font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono","Courier New", monospace; font-size:.75rem; background:#f3f4f6; padding:4px 8px; border-radius:6px; word-break:break-all; margin-top:8px; }

  .modal-qr-display{ max-width:90vw; max-height:90vh; }
  .loading-overlay{ position:absolute; inset:0; background:rgba(255,255,255,.85); display:none; align-items:center; justify-content:center; border-radius:16px; }
  .loading-overlay.show{ display:flex; }

  /* Badge status event (match tone) */
  .event-status-badge{ font-weight:700; padding:8px 14px; border-radius:999px; display:inline-flex; align-items:center; gap:8px; transition:.2s; }
  .event-status-badge.status-upcoming{ background:#f3f4f6; color:#6b7280; }
  .event-status-badge.status-starting-soon{ background:#fef3c7; color:#92400e; }
  .event-status-badge.status-ongoing{ background:#d1fae5; color:#065f46; animation:pulse 2s infinite; }
  .event-status-badge.status-finished{ background:#fee2e2; color:#991b1b; }
  @keyframes pulse{ 0%,100%{opacity:1;} 50%{opacity:.75;} }
</style>

<script>
  // ====== Global vars (dipertahankan) ======
  let currentEventId = <?= $selectedEventId ?? 'null' ?>;
  let currentQRCodes = [];
  let currentModalQR = null;
  let autoRefreshInterval = null;

  // ====== Jam hidup ======
  function updateCurrentTime(){
    const now = new Date();
    const el = document.getElementById('currentTime');
    if(!el) return;
    el.textContent = now.toLocaleString('id-ID',{year:'numeric',month:'long',day:'numeric',hour:'2-digit',minute:'2-digit',second:'2-digit'});
  }

  // ====== Generate Multiple QR (fungsi original, tidak diubah) ======
  function generateMultipleQRCodes(){
    if(!currentEventId){ showAlert('Silakan pilih event terlebih dahulu','warning'); return; }
    const btn = document.getElementById('generateQRBtn');
    const spinner = btn.querySelector('.loading-spinner');
    btn.disabled = true; spinner.style.display='inline-block';

    fetch('<?= site_url('admin/absensi/generateMultipleQRCodes') ?>',{
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},
      body:'event_id='+currentEventId
    })
    .then(r=>r.json())
    .then(data=>{
      if(data.success){
        currentQRCodes = data.qr_codes;
        displayQRCodes(data);
        showAlert('QR Codes berhasil digenerate!','success');
      }else{
        showAlert('Error: '+(data.message||'Unknown error'),'danger');
      }
    })
    .catch(err=>{ console.error(err); showAlert('Error generate QR: '+err.message,'danger'); })
    .finally(()=>{ btn.disabled=false; spinner.style.display='none'; });
  }

  // ====== Hitung status event (frontend) ======
  function calculateEventStatus(eventDate, eventTime){
    const now = new Date();
    const eventDateTime = new Date(eventDate + ' ' + eventTime);
    const hoursDiff = (now.getTime() - eventDateTime.getTime()) / (1000*60*60);
    if (hoursDiff < -1) return { status:'Belum Dimulai', class:'status-upcoming', ongoing:false, icon:'bi bi-clock', canScan:false };
    if (hoursDiff < 0)  return { status:'Segera Dimulai', class:'status-starting-soon', ongoing:false, icon:'bi bi-play-circle', canScan:true };
    if (hoursDiff <= 4) return { status:'Sedang Berlangsung', class:'status-ongoing', ongoing:true, icon:'bi bi-broadcast-pin', canScan:true };
    return { status:'Sudah Selesai', class:'status-finished', ongoing:false, icon:'bi bi-check-circle', canScan:false };
  }

  // ====== Render hasil QR ======
  function displayQRCodes(data){
    const area = document.getElementById('qrCodesArea');
    const eventInfo = document.getElementById('eventInfo');
    const qrStats = document.getElementById('qrStats');
    const qrGrid = document.getElementById('qrGrid');

    const est = calculateEventStatus(data.event_date, data.event_time);

    eventInfo.innerHTML = `
      <div class="row align-items-center">
        <div class="col-md-8">
          <h6 class="mb-1">${data.event_title}</h6>
          <p class="mb-0 text-muted">
            <i class="bi bi-calendar3 me-1"></i>${formatDate(data.event_date)}
            <i class="bi bi-clock ms-3 me-1"></i>${formatTime(data.event_time)}
          </p>
        </div>
        <div class="col-md-4 text-end">
          <div class="event-status-badge ${est.class}">
            <i class="${est.icon}"></i><span>${est.status}</span>
          </div>
          <div class="mt-2">
            <a href="${data.scanner_url}" target="_blank" class="btn btn-outline-primary btn-sm">
              <i class="bi bi-camera-video me-1"></i>Buka Scanner
            </a>
          </div>
        </div>
      </div>
    `;

    window.isEventOngoing = est.ongoing;
    window.canScanQR = est.canScan;

    qrStats.innerHTML = `
      <div class="stat-card">
        <div class="stat-number">${data.qr_codes.length}</div>
        <div class="text-muted small">QR Codes</div>
      </div>
      <div class="stat-card">
        <div class="stat-number" id="totalRegistered">-</div>
        <div class="text-muted small">Terdaftar</div>
      </div>
      <div class="stat-card">
        <div class="stat-number" id="totalAttended">-</div>
        <div class="text-muted small">Hadir</div>
      </div>
      <div class="stat-card">
        <div class="stat-number" id="attendanceRate">-%</div>
        <div class="text-muted small">Rate</div>
      </div>
    `;

    qrGrid.innerHTML = '';
    data.qr_codes.forEach((qr, idx)=>{
      const card = createQRCard(qr, idx);
      qrGrid.appendChild(card);
    });

    area.style.display = 'block';
    loadLiveStats();
  }

  // ====== Kartu QR ======
  function createQRCard(qr, index){
    const div = document.createElement('div');
    div.className = `qr-card priority-${qr.priority}`;
    div.innerHTML = `
      <div class="position-relative">
        <div class="loading-overlay" id="loading-${index}">
          <div class="spinner-border text-primary" role="status"></div>
        </div>
        <div class="qr-label" style="color:${qr.color}"><i class="${qr.icon}"></i>${qr.label}</div>
        <div class="qr-description">${qr.description}</div>
        <div class="qr-code-container">
          <canvas id="qr-canvas-${index}" width="200" height="200"></canvas>
        </div>
        <div class="qr-actions">
          <button class="btn btn-primary btn-qr" onclick="showQRDetail(${index})"><i class="bi bi-arrows-fullscreen"></i> View</button>
          <button class="btn btn-success btn-qr" onclick="copyQRURL(${index})"><i class="bi bi-link-45deg"></i> URL</button>
          <button class="btn btn-info btn-qr" onclick="copyQRToken(${index})"><i class="bi bi-clipboard"></i> Token</button>
          <button class="btn btn-warning btn-qr" onclick="printSingleQR(${index})"><i class="bi bi-printer"></i> Print</button>
        </div>
        <div class="qr-token">${qr.token}</div>
      </div>
    `;
    setTimeout(()=>{
      generateQRCode(`qr-canvas-${index}`, qr.url, qr.color);
      document.getElementById(`loading-${index}`).classList.remove('show');
    }, index*200);
    return div;
  }

  // ====== QR generator (fungsi original) ======
  function generateQRCode(canvasId, text, color='#2563eb'){
    const canvas = document.getElementById(canvasId);
    if(!canvas) return;
    if (typeof QRCode !== 'undefined' && QRCode.toCanvas) {
      QRCode.toCanvas(canvas, text, { width:200, margin:2, color:{ dark:color, light:'#ffffff' } }, function(err){
        if(err){ console.error(err); generateQRCodeFallback(canvas, text); }
      });
    } else { generateQRCodeFallback(canvas, text); }
  }
  function generateQRCodeFallback(canvas, text){
    const img = new Image(); img.crossOrigin='anonymous';
    img.onload = function(){ const ctx=canvas.getContext('2d'); ctx.drawImage(img,0,0,200,200); };
    img.onerror = function(){
      const ctx=canvas.getContext('2d'); ctx.fillStyle='#f3f4f6'; ctx.fillRect(0,0,200,200);
      ctx.fillStyle='#6b7280'; ctx.font='14px Arial'; ctx.textAlign='center';
      ctx.fillText('QR Code',100,90); ctx.fillText('Placeholder',100,110);
    };
    img.src = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(text)}`;
  }

  // ====== Modal detail QR ======
  function showQRDetail(index){
    if(!currentQRCodes[index]) return;
    currentModalQR = currentQRCodes[index];
    document.getElementById('modalQRTitle').textContent = currentModalQR.label + ' - QR Code';
    document.getElementById('modalQRToken').textContent = currentModalQR.token;
    const display = document.getElementById('modalQRDisplay');
    display.innerHTML = '<canvas id="modalQRCanvas" width="300" height="300"></canvas>';
    generateQRCode('modalQRCanvas', currentModalQR.url, currentModalQR.color);
    new bootstrap.Modal(document.getElementById('qrDetailModal')).show();
  }

  // ====== Copy URL/Token (original) ======
  function copyQRURL(index=null){
    const qr = index!==null ? currentQRCodes[index] : currentModalQR; if(!qr) return;
    navigator.clipboard.writeText(qr.url).then(()=> showAlert('QR URL disalin ke clipboard!','success')).catch(()=> showAlert('Gagal menyalin URL','danger'));
  }
  function copyQRToken(index=null){
    const qr = index!==null ? currentQRCodes[index] : currentModalQR; if(!qr) return;
    navigator.clipboard.writeText(qr.token).then(()=> showAlert('Token disalin ke clipboard!','success')).catch(()=> showAlert('Gagal menyalin token','danger'));
  }

  // ====== Print ======
  function printSingleQR(index){
    if(!currentQRCodes[index]) return;
    const qr = currentQRCodes[index];
    const canvas = document.getElementById(`qr-canvas-${index}`);
    createPrintWindow([{ qr, canvas }]);
  }
  function printAllQRCodes(){
    const data = currentQRCodes.map((qr, idx)=>({ qr, canvas: document.getElementById(`qr-canvas-${idx}`) }));
    createPrintWindow(data);
  }
  function createPrintWindow(qrData){
    const w = window.open('', '_blank');
    let html = `
      <!DOCTYPE html><html><head><title>QR Codes - Attendance</title>
      <style>
        body{ font-family:Arial, sans-serif; margin:0; padding:20px; }
        .print-page{ page-break-after:always; text-align:center; padding:30px; }
        .print-page:last-child{ page-break-after:auto; }
        .qr-container{ border:3px solid #333; padding:30px; margin:20px auto; display:inline-block; background:#fff; }
        .qr-title{ font-size:22px; font-weight:800; margin-bottom:8px; }
        .qr-description{ font-size:14px; margin-bottom:16px; color:#666; }
        .qr-token{ font-family:monospace; font-size:12px; margin-top:12px; word-break:break-all; max-width:300px; margin-left:auto; margin-right:auto; }
        .instructions{ margin-top:20px; font-size:13px; color:#666; max-width:420px; margin-left:auto; margin-right:auto; }
        @media print{ body{margin:0;} }
      </style></head><body>
    `;
    qrData.forEach(item=>{
      html += `
        <div class="print-page">
          <div class="qr-title">${item.qr.label}</div>
          <div class="qr-description">${item.qr.description}</div>
          <div class="qr-container">
            <img src="${item.canvas.toDataURL()}" alt="QR Code" />
            <div class="qr-token">${item.qr.token}</div>
          </div>
          <div class="instructions">
            <p><strong>Instruksi:</strong></p>
            <p>1. Tampilkan QR ini<br>2. Peserta scan dengan QR reader / Google Lens<br>3. Sistem otomatis memvalidasi & mencatat kehadiran<br>4. Pastikan peserta login & pembayaran sudah terverifikasi</p>
          </div>
        </div>
      `;
    });
    html += '</body></html>';
    w.document.write(html); w.document.close();
    w.onload = function(){ setTimeout(()=>{ w.focus(); w.print(); }, 400); };
  }

  // ====== Live Stats & Status ======
  function loadLiveStats(){
    if(!currentEventId) return;
    fetch(`<?= site_url('admin/absensi/liveStats') ?>?event_id=${currentEventId}`)
      .then(r=>r.json())
      .then(data=>{
        if(data.success){
          document.getElementById('totalRegistered')?.replaceChildren(document.createTextNode(data.stats.total_registered));
          document.getElementById('totalAttended')?.replaceChildren(document.createTextNode(data.stats.total_attended));
          document.getElementById('attendanceRate')?.replaceChildren(document.createTextNode(data.stats.attendance_rate+'%'));
          document.getElementById('kpiTotalRegistered')?.replaceChildren(document.createTextNode(data.stats.total_registered));
          document.getElementById('kpiTotalAttended')?.replaceChildren(document.createTextNode(data.stats.total_attended));
          document.getElementById('kpiRate')?.replaceChildren(document.createTextNode(data.stats.attendance_rate+'%'));
          document.getElementById('lastUpdate')?.replaceChildren(document.createTextNode(data.stats.last_updated));
        }
      }).catch(()=>{});
  }

  function updateEventStatus(){
    if(!currentEventId) return;
    fetch(`<?= site_url('admin/absensi/getEventStatus') ?>?event_id=${currentEventId}`)
      .then(r=>r.json())
      .then(data=>{
        if(data.success){
          const wrap = document.getElementById('eventStatusDisplay'); if(!wrap) return;
          const badge = wrap.querySelector('.event-status-badge'); if(!badge) return;
          const mapped = (s)=>{
            if(s==='Segera Dimulai') return {cls:'status-starting-soon', icon:'bi bi-play-circle'};
            if(s==='Sedang Berlangsung') return {cls:'status-ongoing', icon:'bi bi-broadcast-pin'};
            if(s==='Sudah Selesai') return {cls:'status-finished', icon:'bi bi-check-circle'};
            return {cls:'status-upcoming', icon:'bi bi-clock'};
          }
          const m = mapped(data.status);
          badge.className = `event-status-badge ${m.cls}`;
          const i = badge.querySelector('i'); const t = badge.querySelector('span');
          if(i) i.className = m.icon;
          if(t) t.textContent = data.status;
        }
      }).catch(()=>{});
  }

  // ====== Hapus Presensi (dengan modal konfirmasi) ======
  function removeAttendanceWithModal(attendanceId, participantName){
    document.getElementById('deleteMessage').innerHTML = `
      Apakah Anda yakin ingin menghapus presensi <strong>${participantName}</strong>?
    `;
    const btn = document.getElementById('confirmDeleteBtn');
    btn.onclick = function(){ confirmRemoveAttendance(attendanceId); }
    new bootstrap.Modal(document.getElementById('deleteConfirmModal')).show();
  }
  function confirmRemoveAttendance(attendanceId){
    const m = bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal'));
    m.hide(); removeAttendance(attendanceId);
  }
  function removeAttendance(attendanceId){
    if(!attendanceId){ showAlert('ID absensi tidak valid','danger'); return; }
    fetch('<?= site_url('admin/absensi/removeAttendance') ?>',{
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},
      body:'attendance_id='+attendanceId
    })
    .then(r=>r.json())
    .then(data=>{
      if(data.success){
        showAlert('Data presensi berhasil dihapus!','success');
        const btn = document.querySelector(`[onclick*="removeAttendanceWithModal(${attendanceId}"]`);
        const row = btn ? btn.closest('tr') : null;
        if(row) row.remove();
        loadLiveStats();
      }else{
        showAlert('Error: '+(data.message||'Gagal menghapus presensi'),'danger');
      }
    })
    .catch(()=>{ showAlert('Network error: gagal menghapus presensi','danger'); });
  }

  // ====== Auto Refresh ======
  function toggleAutoRefresh(){
    const cb = document.getElementById('autoRefresh');
    if(cb.checked){
      autoRefreshInterval = setInterval(()=>{
        loadLiveStats(); updateEventStatus();
        if(!document.querySelector('.modal.show')){ refreshAttendanceData(true); }
      }, 120000);
      showAlert('Auto-refresh aktif','info');
    }else{
      if(autoRefreshInterval){ clearInterval(autoRefreshInterval); autoRefreshInterval=null; }
      showAlert('Auto-refresh nonaktif','info');
    }
  }

  // ====== Manual Refresh ======
  function refreshAttendanceData(silent=false){
    if(!silent) showAlert('Merefresh data...','info');
    loadLiveStats(); updateEventStatus();
    if(document.getElementById('attendanceTable') && !silent){
      setTimeout(()=>{ location.reload(); }, 900);
    }
  }

  // ====== Pencarian tabel ======
  function searchAttendance(){
    const q = (document.getElementById('searchInput').value || '').toLowerCase();
    const rows = document.querySelectorAll('#attendanceTable tbody tr');
    rows.forEach(row=>{
      const cells = row.querySelectorAll('td'); if(cells.length<=1) return;
      let show=false;
      for(let i=1;i<4;i++){ if(cells[i] && cells[i].textContent.toLowerCase().includes(q)){ show=true; break; } }
      row.style.display = show ? '' : 'none';
    });
  }

  // ====== Utils & placeholder ======
  function formatDate(s){ return new Date(s).toLocaleDateString('id-ID',{ day:'numeric', month:'long', year:'numeric' }); }
  function formatTime(s){ return s.substring(0,5)+' WIB'; }

  function showAlert(message,type='info'){
    const box = document.createElement('div');
    const icons = { success:'bi bi-check-circle', danger:'bi bi-exclamation-octagon', warning:'bi bi-exclamation-triangle', info:'bi bi-info-circle' };
    box.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    box.style.cssText = 'top:20px; right:20px; z-index:1060; min-width:300px;';
    box.innerHTML = `<i class="${icons[type]||icons.info} me-2"></i>${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
    document.body.appendChild(box);
    setTimeout(()=>{ box.parentNode && box.parentNode.removeChild(box); }, 5000);
  }

  function exportAttendance(){
    if(!currentEventId){ showAlert('Silakan pilih event terlebih dahulu','warning'); return; }
    window.open(`<?= site_url('admin/absensi/export') ?>?event_id=${currentEventId}`, '_blank');
  }
  function showBulkMarkModal(){ showAlert('Fitur bulk mark akan segera diimplementasikan','info'); }
  function showManualMarkModal(){ showAlert('Fitur manual mark akan segera diimplementasikan','info'); }
  function downloadAllQRCodes(){ showAlert('Fitur download akan segera diimplementasikan','info'); }

  // ====== Init ======
  document.addEventListener('DOMContentLoaded', ()=>{
    setInterval(updateCurrentTime, 1000);
    if(currentEventId){
      loadLiveStats();
      setInterval(updateEventStatus, 30000);
    }
  });
</script>