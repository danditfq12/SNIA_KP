<?php
// ====== DEFAULT VARS ======
$title            = $title ?? 'Kelola Absensi (QR)';
$events           = $events ?? [];
$selectedEventId  = $selectedEventId ?? null;
$currentEvent     = $currentEvent ?? null;
$eventStats       = $eventStats ?? null;
$absensiData      = $absensiData ?? [];
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_admin') ?>
<?= $this->include('partials/alerts') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/absensi_admin.css'); ?>">


<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- HEADER -->
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

      <!-- KPI CARDS -->
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

      <!-- EVENT SELECTOR -->
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

      <!-- QR CODES AREA -->
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
            <div id="eventInfo" class="mb-3"></div>
            <div class="qr-stats" id="qrStats"></div>
            <div class="qr-grid" id="qrGrid"></div>
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
          </div>

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

        <!-- ATTENDANCE TABLE -->
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

<!-- MODALS -->
<!-- QR Detail Modal -->
<div class="modal fade" id="qrDetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
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
    </div>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
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
    </div>
  </div>
</div>

<!-- Bulk Mark Modal -->
<div class="modal fade" id="bulkMarkModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="bi bi-people-fill me-2"></i>Bulk Mark Attendance</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-info">
          <i class="bi bi-info-circle me-2"></i>Pilih peserta yang akan ditandai hadir. Hanya menampilkan peserta dengan pembayaran verified yang belum absen.
          <div class="mt-2"><strong>Total Tersedia: <span id="bulkMarkCount">-</span> peserta</strong></div>
        </div>
        <div id="eligibleUsersList">
          <div class="text-center py-3"><div class="spinner-border text-primary"></div></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i>Batal</button>
        <button type="button" class="btn btn-primary" id="bulkMarkSubmitBtn" onclick="processBulkMark()">
          <i class="bi bi-check-circle me-1"></i>Proses Bulk Mark
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Manual Mark Modal -->
<div class="modal fade" id="manualMarkModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Manual Mark Attendance</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="manualMarkForm">
          <div class="mb-3">
            <label class="form-label">Pilih Peserta <span class="text-danger">*</span></label>
            <select class="form-select" id="manualMarkUserSelect" required>
              <option value="">Pilih peserta...</option>
            </select>
            <small class="text-muted">Hanya peserta dengan pembayaran verified & belum absen</small>
          </div>
          <div class="mb-3">
            <label class="form-label">Catatan (Opsional)</label>
            <textarea class="form-control" id="manualMarkNotes" rows="3" placeholder="Contoh: Datang terlambat, scan QR bermasalah, dll."></textarea>
          </div>
          <div class="alert alert-warning mb-0">
            <i class="bi bi-exclamation-triangle me-2"></i>Presensi ini akan ditandai sebagai "Manual Mark by Admin"
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i>Batal</button>
        <button type="button" class="btn btn-warning" id="manualMarkSubmitBtn" onclick="processManualMark()">
          <i class="bi bi-person-check me-1"></i>Tandai Hadir
        </button>
      </div>
    </div>
  </div>
</div>

<?= $this->include('partials/footer') ?>

<!-- QRCode Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode/1.5.3/qrcode.min.js"></script>
<script>
// ====== Global Variables ======
let currentEventId = <?= $selectedEventId ?? 'null' ?>;
let currentQRCodes = [];
let currentModalQR = null;
let autoRefreshInterval = null;

// ====== Update Clock ======
function updateCurrentTime(){
  const now = new Date();
  const el = document.getElementById('currentTime');
  if(!el) return;
  el.textContent = now.toLocaleString('id-ID',{
    year:'numeric',month:'long',day:'numeric',
    hour:'2-digit',minute:'2-digit',second:'2-digit'
  });
}

// ====== Generate Multiple QR Codes ======
function generateMultipleQRCodes(){
  if(!currentEventId){ showAlert('Silakan pilih event terlebih dahulu','warning'); return; }
  const btn = document.getElementById('generateQRBtn');
  const spinner = btn.querySelector('.loading-spinner');
  btn.disabled = true; 
  spinner.style.display='inline-block';

  fetch('<?= site_url('admin/absensi/generateMultipleQRCodes') ?>',{
    method:'POST',
    headers:{
      'Content-Type':'application/x-www-form-urlencoded',
      'X-Requested-With':'XMLHttpRequest'
    },
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
  .catch(err=>{ 
    console.error(err); 
    showAlert('Error generate QR: '+err.message,'danger'); 
  })
  .finally(()=>{ 
    btn.disabled=false; 
    spinner.style.display='none'; 
  });
}

// ====== Calculate Event Status ======
function calculateEventStatus(eventDate, eventTime){
  const now = new Date();
  const eventDateTime = new Date(eventDate + ' ' + eventTime);
  const hoursDiff = (now.getTime() - eventDateTime.getTime()) / (1000*60*60);
  
  if (hoursDiff < -1) return { 
    status:'Belum Dimulai', class:'status-upcoming', 
    ongoing:false, icon:'bi bi-clock', canScan:false 
  };
  if (hoursDiff < 0)  return { 
    status:'Segera Dimulai', class:'status-starting-soon', 
    ongoing:false, icon:'bi bi-play-circle', canScan:true 
  };
  if (hoursDiff <= 4) return { 
    status:'Sedang Berlangsung', class:'status-ongoing', 
    ongoing:true, icon:'bi bi-broadcast-pin', canScan:true 
  };
  return { 
    status:'Sudah Selesai', class:'status-finished', 
    ongoing:false, icon:'bi bi-check-circle', canScan:false 
  };
}

// ====== Display QR Codes ======
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

// ====== Create QR Card ======
function createQRCard(qr, index){
  const div = document.createElement('div');
  div.className = `qr-card priority-${qr.priority}`;
  div.innerHTML = `
    <div class="position-relative">
      <div class="loading-overlay" id="loading-${index}">
        <div class="spinner-border text-primary" role="status"></div>
      </div>
      <div class="qr-label" style="color:${qr.color}">
        <i class="${qr.icon}"></i>${qr.label}
      </div>
      <div class="qr-description">${qr.description}</div>
      <div class="qr-code-container">
        <canvas id="qr-canvas-${index}" width="200" height="200"></canvas>
      </div>
      <div class="qr-actions">
        <button class="btn btn-primary btn-qr" onclick="showQRDetail(${index})">
          <i class="bi bi-arrows-fullscreen"></i> View
        </button>
        <button class="btn btn-success btn-qr" onclick="copyQRURL(${index})">
          <i class="bi bi-link-45deg"></i> URL
        </button>
        <button class="btn btn-info btn-qr" onclick="copyQRToken(${index})">
          <i class="bi bi-clipboard"></i> Token
        </button>
        <button class="btn btn-warning btn-qr" onclick="printSingleQR(${index})">
          <i class="bi bi-printer"></i> Print
        </button>
      </div>
      <div class="qr-token">${qr.token}</div>
    </div>
  `;
  
  setTimeout(()=>{
    generateQRCode(`qr-canvas-${index}`, qr.url, qr.color);
    const loadingEl = document.getElementById(`loading-${index}`);
    if(loadingEl) loadingEl.classList.remove('show');
  }, index*200);
  
  return div;
}

// ====== Generate QR Code ======
function generateQRCode(canvasId, text, color='#2563eb'){
  const canvas = document.getElementById(canvasId);
  if(!canvas) return;
  
  if (typeof QRCode !== 'undefined' && QRCode.toCanvas) {
    QRCode.toCanvas(canvas, text, { 
      width:200, margin:2, 
      color:{ dark:color, light:'#ffffff' } 
    }, function(err){
      if(err){ 
        console.error(err); 
        generateQRCodeFallback(canvas, text); 
      }
    });
  } else { 
    generateQRCodeFallback(canvas, text); 
  }
}

function generateQRCodeFallback(canvas, text){
  const img = new Image(); 
  img.crossOrigin='anonymous';
  img.onload = function(){ 
    const ctx=canvas.getContext('2d'); 
    ctx.drawImage(img,0,0,200,200); 
  };
  img.onerror = function(){
    const ctx=canvas.getContext('2d'); 
    ctx.fillStyle='#f3f4f6'; 
    ctx.fillRect(0,0,200,200);
    ctx.fillStyle='#6b7280'; 
    ctx.font='14px Arial'; 
    ctx.textAlign='center';
    ctx.fillText('QR Code',100,90); 
    ctx.fillText('Placeholder',100,110);
  };
  img.src = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(text)}`;
}

// ====== Show QR Detail Modal ======
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

// ====== Print QR Codes ======
function printSingleQR(index){
  if(!currentQRCodes[index]) return;
  const qr = currentQRCodes[index];
  const canvas = document.getElementById(`qr-canvas-${index}`);
  createPrintWindow([{ qr, canvas }]);
}

function printQRCode(){
  if(!currentModalQR) return;
  const canvas = document.getElementById('modalQRCanvas');
  createPrintWindow([{ qr: currentModalQR, canvas }]);
}

function printAllQRCodes(){
  const data = currentQRCodes.map((qr, idx)=>({ 
    qr, 
    canvas: document.getElementById(`qr-canvas-${idx}`) 
  }));
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
      .qr-container{ border:3px solid #333; padding:30px; margin:20px auto; 
                      display:inline-block; background:#fff; }
      .qr-title{ font-size:22px; font-weight:800; margin-bottom:8px; }
      .qr-description{ font-size:14px; margin-bottom:16px; color:#666; }
      .qr-token{ font-family:monospace; font-size:12px; margin-top:12px; 
                 word-break:break-all; max-width:300px; margin-left:auto; 
                 margin-right:auto; }
      .instructions{ margin-top:20px; font-size:13px; color:#666; 
                     max-width:420px; margin-left:auto; margin-right:auto; }
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
          <p>1. Tampilkan QR ini<br>
             2. Peserta scan dengan QR reader / Google Lens<br>
             3. Sistem otomatis memvalidasi & mencatat kehadiran<br>
             4. Pastikan peserta login & pembayaran sudah terverifikasi</p>
        </div>
      </div>
    `;
  });
  
  html += '</body></html>';
  w.document.write(html); 
  w.document.close();
  w.onload = function(){ 
    setTimeout(()=>{ w.focus(); w.print(); }, 400); 
  };
}

// ====== Copy Functions ======
function copyQRURL(index=null){
  const qr = index!==null ? currentQRCodes[index] : currentModalQR; 
  if(!qr) return;
  navigator.clipboard.writeText(qr.url)
    .then(()=> showAlert('QR URL disalin ke clipboard!','success'))
    .catch(()=> showAlert('Gagal menyalin URL','danger'));
}

function copyQRToken(index=null){
  const qr = index!==null ? currentQRCodes[index] : currentModalQR; 
  if(!qr) return;
  navigator.clipboard.writeText(qr.token)
    .then(()=> showAlert('Token disalin ke clipboard!','success'))
    .catch(()=> showAlert('Gagal menyalin token','danger'));
}

// ====== Bulk Mark Attendance ======
function showBulkMarkModal(){
  if(!currentEventId){ 
    showAlert('Silakan pilih event terlebih dahulu','warning'); 
    return; 
  }
  loadEligibleUsers();
  new bootstrap.Modal(document.getElementById('bulkMarkModal')).show();
}

function loadEligibleUsers(){
  const list = document.getElementById('eligibleUsersList');
  list.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary"></div><div class="mt-2">Memuat data...</div></div>';
  
  fetch(`<?= site_url('admin/absensi/getEligibleUsers') ?>?event_id=${currentEventId}`)
    .then(r=>r.json())
    .then(data=>{
      if(data.success && data.users.length>0){
        let html = '<div class="mb-2">';
        html += '<button class="btn btn-sm btn-outline-primary" onclick="toggleAllUsers(true)">Pilih Semua</button> ';
        html += '<button class="btn btn-sm btn-outline-secondary" onclick="toggleAllUsers(false)">Batal Semua</button>';
        html += '</div>';
        html += '<div style="max-height:350px; overflow-y:auto;">';
        
        data.users.forEach(user=>{
          html += `
            <div class="form-check mb-2 p-2 border rounded">
              <input class="form-check-input" type="checkbox" value="${user.id_user}" id="user_${user.id_user}">
              <label class="form-check-label w-100" for="user_${user.id_user}">
                <div class="fw-semibold">${user.nama_lengkap}</div>
                <small class="text-muted">${user.email} • ${user.role} • ${user.participation_type || 'offline'}</small>
              </label>
            </div>
          `;
        });
        html += '</div>';
        list.innerHTML = html;
        document.getElementById('bulkMarkCount').textContent = data.users.length;
      }else{
        list.innerHTML = '<div class="alert alert-info mb-0"><i class="bi bi-info-circle me-2"></i>Tidak ada peserta yang memenuhi syarat (pembayaran verified & belum absen)</div>';
      }
    })
    .catch(()=>{ 
      list.innerHTML = '<div class="alert alert-danger mb-0">Gagal memuat data</div>'; 
    });
}

function toggleAllUsers(check){
  document.querySelectorAll('#eligibleUsersList input[type="checkbox"]').forEach(cb=>{ 
    cb.checked=check; 
  });
}

function processBulkMark(){
  const checked = Array.from(
    document.querySelectorAll('#eligibleUsersList input[type="checkbox"]:checked')
  ).map(cb=>cb.value);
  
  if(checked.length===0){ 
    showAlert('Pilih minimal 1 peserta','warning'); 
    return; 
  }
  
  if(!confirm(`Tandai ${checked.length} peserta sebagai hadir?`)) return;
  
  const btn = document.getElementById('bulkMarkSubmitBtn');
  btn.disabled=true; 
  btn.innerHTML='<span class="spinner-border spinner-border-sm me-1"></span>Memproses...';
  
  fetch('<?= site_url('admin/absensi/bulkMarkAttendance') ?>',{
    method:'POST',
    headers:{
      'Content-Type':'application/x-www-form-urlencoded',
      'X-Requested-With':'XMLHttpRequest'
    },
    body:`event_id=${currentEventId}&user_ids=${checked.join(',')}`
  })
  .then(r=>r.json())
  .then(data=>{
    if(data.success){
      showAlert(
        `Berhasil menandai ${data.details.success_count} peserta! ${data.details.error_count>0?'Gagal: '+data.details.error_count:''}`, 
        'success'
      );
      bootstrap.Modal.getInstance(document.getElementById('bulkMarkModal')).hide();
      setTimeout(()=>location.reload(), 1500);
    }else{
      showAlert('Error: '+(data.message||'Unknown error'),'danger');
    }
  })
  .catch(()=>showAlert('Network error','danger'))
  .finally(()=>{ 
    btn.disabled=false; 
    btn.innerHTML='<i class="bi bi-check-circle me-1"></i>Proses Bulk Mark'; 
  });
}

// ====== Manual Mark Attendance ======
function showManualMarkModal(){
  if(!currentEventId){ 
    showAlert('Silakan pilih event terlebih dahulu','warning'); 
    return; 
  }
  document.getElementById('manualMarkForm').reset();
  document.getElementById('manualMarkUserSelect').innerHTML = '<option value="">Pilih peserta...</option>';
  loadManualMarkUsers();
  new bootstrap.Modal(document.getElementById('manualMarkModal')).show();
}

function loadManualMarkUsers(){
  fetch(`<?= site_url('admin/absensi/getEligibleUsers') ?>?event_id=${currentEventId}`)
    .then(r=>r.json())
    .then(data=>{
      const sel = document.getElementById('manualMarkUserSelect');
      if(data.success && data.users.length>0){
        data.users.forEach(user=>{
          const opt = document.createElement('option');
          opt.value = user.id_user;
          opt.textContent = `${user.nama_lengkap} - ${user.email} (${user.role} - ${user.participation_type||'offline'})`;
          sel.appendChild(opt);
        });
      }else{
        sel.innerHTML = '<option value="">Tidak ada peserta tersedia</option>';
      }
    })
    .catch(()=>{ 
      document.getElementById('manualMarkUserSelect').innerHTML = '<option value="">Gagal memuat data</option>'; 
    });
}

function processManualMark(){
  const userId = document.getElementById('manualMarkUserSelect').value;
  const notes = document.getElementById('manualMarkNotes').value;
  
  if(!userId){ 
    showAlert('Pilih peserta terlebih dahulu','warning'); 
    return; 
  }
  
  const btn = document.getElementById('manualMarkSubmitBtn');
  btn.disabled=true; 
  btn.innerHTML='<span class="spinner-border spinner-border-sm me-1"></span>Memproses...';
  
  fetch('<?= site_url('admin/absensi/markAttendance') ?>',{
    method:'POST',
    headers:{
      'Content-Type':'application/x-www-form-urlencoded',
      'X-Requested-With':'XMLHttpRequest'
    },
    body:`user_id=${userId}&event_id=${currentEventId}&notes=${encodeURIComponent(notes)}`
  })
  .then(r=>r.json())
  .then(data=>{
    if(data.success){
      showAlert(data.message,'success');
      bootstrap.Modal.getInstance(document.getElementById('manualMarkModal')).hide();
      setTimeout(()=>location.reload(), 1200);
    }else{
      showAlert('Error: '+(data.message||'Unknown error'),'danger');
    }
  })
  .catch(()=>showAlert('Network error','danger'))
  .finally(()=>{ 
    btn.disabled=false; 
    btn.innerHTML='<i class="bi bi-person-check me-1"></i>Tandai Hadir'; 
  });
}

// ====== Remove Attendance ======
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
  m.hide(); 
  removeAttendance(attendanceId);
}

function removeAttendance(attendanceId){
  if(!attendanceId){ 
    showAlert('ID absensi tidak valid','danger'); 
    return; 
  }
  
  fetch('<?= site_url('admin/absensi/removeAttendance') ?>',{
    method:'POST',
    headers:{
      'Content-Type':'application/x-www-form-urlencoded',
      'X-Requested-With':'XMLHttpRequest'
    },
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

// ====== Live Stats & Status ======
function loadLiveStats(){
  if(!currentEventId) return;
  
  fetch(`<?= site_url('admin/absensi/liveStats') ?>?event_id=${currentEventId}`)
    .then(r=>r.json())
    .then(data=>{
      if(data.success){
        document.getElementById('totalRegistered')?.replaceChildren(
          document.createTextNode(data.stats.total_registered)
        );
        document.getElementById('totalAttended')?.replaceChildren(
          document.createTextNode(data.stats.total_attended)
        );
        document.getElementById('attendanceRate')?.replaceChildren(
          document.createTextNode(data.stats.attendance_rate+'%')
        );
        document.getElementById('kpiTotalRegistered')?.replaceChildren(
          document.createTextNode(data.stats.total_registered)
        );
        document.getElementById('kpiTotalAttended')?.replaceChildren(
          document.createTextNode(data.stats.total_attended)
        );
        document.getElementById('kpiRate')?.replaceChildren(
          document.createTextNode(data.stats.attendance_rate+'%')
        );
        document.getElementById('lastUpdate')?.replaceChildren(
          document.createTextNode(data.stats.last_updated)
        );
      }
    }).catch(()=>{});
}

function updateEventStatus(){
  if(!currentEventId) return;
  
  fetch(`<?= site_url('admin/absensi/getEventStatus') ?>?event_id=${currentEventId}`)
    .then(r=>r.json())
    .then(data=>{
      if(data.success){
        const wrap = document.getElementById('eventStatusDisplay'); 
        if(!wrap) return;
        const badge = wrap.querySelector('.event-status-badge'); 
        if(!badge) return;
        
        const mapped = (s)=>{
          if(s==='Segera Dimulai') return {cls:'status-starting-soon', icon:'bi bi-play-circle'};
          if(s==='Sedang Berlangsung') return {cls:'status-ongoing', icon:'bi bi-broadcast-pin'};
          if(s==='Sudah Selesai') return {cls:'status-finished', icon:'bi bi-check-circle'};
          return {cls:'status-upcoming', icon:'bi bi-clock'};
        }
        
        const m = mapped(data.status);
        badge.className = `event-status-badge ${m.cls}`;
        const i = badge.querySelector('i'); 
        const t = badge.querySelector('span');
        if(i) i.className = m.icon;
        if(t) t.textContent = data.status;
      }
    }).catch(()=>{});
}

// ====== Auto Refresh ======
function toggleAutoRefresh(){
  const cb = document.getElementById('autoRefresh');
  if(cb.checked){
    autoRefreshInterval = setInterval(()=>{
      loadLiveStats(); 
      updateEventStatus();
      if(!document.querySelector('.modal.show')){ 
        refreshAttendanceData(true); 
      }
    }, 120000);
    showAlert('Auto-refresh aktif','info');
  }else{
    if(autoRefreshInterval){ 
      clearInterval(autoRefreshInterval); 
      autoRefreshInterval=null; 
    }
    showAlert('Auto-refresh nonaktif','info');
  }
}

function refreshAttendanceData(silent=false){
  if(!silent) showAlert('Merefresh data...','info');
  loadLiveStats(); 
  updateEventStatus();
  if(document.getElementById('attendanceTable') && !silent){
    setTimeout(()=>{ location.reload(); }, 900);
  }
}

// ====== Search Attendance ======
function searchAttendance(){
  const q = (document.getElementById('searchInput').value || '').toLowerCase();
  const rows = document.querySelectorAll('#attendanceTable tbody tr');
  rows.forEach(row=>{
    const cells = row.querySelectorAll('td'); 
    if(cells.length<=1) return;
    let show=false;
    for(let i=1;i<4;i++){ 
      if(cells[i] && cells[i].textContent.toLowerCase().includes(q)){ 
        show=true; 
        break; 
      } 
    }
    row.style.display = show ? '' : 'none';
  });
}

// ====== Export Attendance ======
function exportAttendance(){
  if(!currentEventId){ 
    showAlert('Silakan pilih event terlebih dahulu','warning'); 
    return; 
  }
  window.open(`<?= site_url('admin/absensi/export') ?>?event_id=${currentEventId}`, '_blank');
}

// ====== Utility Functions ======
function formatDate(s){ 
  return new Date(s).toLocaleDateString('id-ID',{ 
    day:'numeric', month:'long', year:'numeric' 
  }); 
}

function formatTime(s){ 
  return s.substring(0,5)+' WIB'; 
}

function showAlert(message,type='info'){
  const box = document.createElement('div');
  const icons = { 
    success:'bi bi-check-circle', 
    danger:'bi bi-exclamation-octagon', 
    warning:'bi bi-exclamation-triangle', 
    info:'bi bi-info-circle' 
  };
  box.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
  box.style.cssText = 'top:20px; right:20px; z-index:1060; min-width:300px;';
  box.innerHTML = `<i class="${icons[type]||icons.info} me-2"></i>${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
  document.body.appendChild(box);
  setTimeout(()=>{ box.parentNode && box.parentNode.removeChild(box); }, 5000);
}

// ====== Download All QR Codes as ZIP ======
function downloadAllQRCodes() {
  if (!currentQRCodes || currentQRCodes.length === 0) {
    showAlert('Tidak ada QR Code untuk didownload', 'warning');
    return;
  }

  showAlert('Mempersiapkan download...', 'info');

  // Create a temporary container for generating high-quality QR codes
  const tempContainer = document.createElement('div');
  tempContainer.style.position = 'absolute';
  tempContainer.style.left = '-9999px';
  document.body.appendChild(tempContainer);

  const downloadPromises = currentQRCodes.map((qr, index) => {
    return new Promise((resolve) => {
      // Create a temporary canvas for high-quality QR
      const canvas = document.createElement('canvas');
      canvas.width = 512;  // Higher resolution
      canvas.height = 512;
      tempContainer.appendChild(canvas);

      // Generate QR code
      if (typeof QRCode !== 'undefined' && QRCode.toCanvas) {
        QRCode.toCanvas(canvas, qr.url, {
          width: 512,
          margin: 2,
          color: {
            dark: qr.color,
            light: '#ffffff'
          }
        }, function(err) {
          if (err) {
            console.error('QR generation error:', err);
            resolve(null);
          } else {
            // Convert canvas to blob
            canvas.toBlob((blob) => {
              resolve({
                blob: blob,
                filename: `QR_${qr.label.replace(/[^a-zA-Z0-9]/g, '_')}_${index + 1}.png`
              });
            }, 'image/png');
          }
        });
      } else {
        // Fallback: use existing canvas
        const existingCanvas = document.getElementById(`qr-canvas-${index}`);
        if (existingCanvas) {
          existingCanvas.toBlob((blob) => {
            resolve({
              blob: blob,
              filename: `QR_${qr.label.replace(/[^a-zA-Z0-9]/g, '_')}_${index + 1}.png`
            });
          }, 'image/png');
        } else {
          resolve(null);
        }
      }
    });
  });

  // Wait for all QR codes to be generated
  Promise.all(downloadPromises).then((results) => {
    // Remove temporary container
    document.body.removeChild(tempContainer);

    // Filter out null results
    const validResults = results.filter(r => r !== null);

    if (validResults.length === 0) {
      showAlert('Gagal menggenerate QR codes untuk download', 'danger');
      return;
    }

    // Check if JSZip is available
    if (typeof JSZip !== 'undefined') {
      // Create ZIP file
      const zip = new JSZip();
      const folder = zip.folder('QR_Codes');

      validResults.forEach(result => {
        folder.file(result.filename, result.blob);
      });

      // Generate ZIP and download
      zip.generateAsync({ type: 'blob' }).then((content) => {
        const link = document.createElement('a');
        link.href = URL.createObjectURL(content);
        link.download = `QR_Codes_Event_${currentEventId}_${new Date().getTime()}.zip`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(link.href);

        showAlert(`Berhasil download ${validResults.length} QR codes!`, 'success');
      }).catch(err => {
        console.error('ZIP generation error:', err);
        showAlert('Gagal membuat file ZIP', 'danger');
      });
    } else {
      // Fallback: download individually if JSZip not available
      showAlert('Download QR codes satu per satu...', 'info');
      
      validResults.forEach((result, idx) => {
        setTimeout(() => {
          const link = document.createElement('a');
          link.href = URL.createObjectURL(result.blob);
          link.download = result.filename;
          document.body.appendChild(link);
          link.click();
          document.body.removeChild(link);
          URL.revokeObjectURL(link.href);
        }, idx * 300); // Stagger downloads to avoid browser blocking
      });

      setTimeout(() => {
        showAlert(`Download ${validResults.length} QR codes selesai!`, 'success');
      }, validResults.length * 300 + 500);
    }
  }).catch(err => {
    console.error('Download error:', err);
    showAlert('Terjadi kesalahan saat download', 'danger');
    
    // Remove temporary container if error occurs
    if (tempContainer.parentNode) {
      document.body.removeChild(tempContainer);
    }
  });
}

// ====== Alternative: Download Single QR Code ======
function downloadSingleQR(index) {
  if (!currentQRCodes[index]) {
    showAlert('QR Code tidak ditemukan', 'warning');
    return;
  }

  const qr = currentQRCodes[index];
  const canvas = document.getElementById(`qr-canvas-${index}`);
  
  if (!canvas) {
    showAlert('Canvas tidak ditemukan', 'danger');
    return;
  }

  canvas.toBlob((blob) => {
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `QR_${qr.label.replace(/[^a-zA-Z0-9]/g, '_')}.png`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(link.href);
    
    showAlert('QR Code berhasil didownload!', 'success');
  }, 'image/png');
}

// ====== Initialize ======
document.addEventListener('DOMContentLoaded', ()=>{
  setInterval(updateCurrentTime, 1000);
  if(currentEventId){
    loadLiveStats();
    setInterval(updateEventStatus, 30000);
  }
});
</script>