<?php
use CodeIgniter\I18n\Time;

$title  = $title ?? 'Kelola Event';
$stats  = $stats ?? ['total_events'=>0,'active_events'=>0,'verified_registrations'=>0,'total_revenue'=>0];
$events = $events ?? [];
helper(['number','csrf']);

$tz           = 'Asia/Jakarta';
$minEventDate = Time::now($tz)->addDays(1)->toDateString();
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_admin') ?>
<?= $this->include('partials/alerts') ?>

<meta name="csrf-token" content="<?= csrf_hash() ?>"/>

<div id="content">
  <main class="flex-fill" style="padding-top:70px; padding-bottom: 40px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- Header -->
      <div class="header-section mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
          <div>
            <h3 class="mb-1"><i class="bi bi-calendar3 me-2"></i>Kelola Event</h3>
            <small class="text-muted">
              <i class="bi bi-star-fill text-warning me-1"></i>
              Gel.1 Offline = Early Bird | Gel.2-3 = Harga Naik | Presenter Hanya Offline
            </small>
          </div>
          <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEventModal">
            <i class="bi bi-plus-lg me-2"></i>Tambah Event
          </button>
        </div>
      </div>

      <!-- Stats -->
      <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
          <div class="stat-card">
            <i class="bi bi-calendar-event stat-icon text-primary"></i>
            <div>
              <h4 class="mb-0"><?= number_format((int)$stats['total_events']) ?></h4>
              <small class="text-muted">Total Event</small>
            </div>
          </div>
        </div>
        
        <div class="col-6 col-lg-3">
          <div class="stat-card">
            <i class="bi bi-check2-circle stat-icon text-success"></i>
            <div>
              <h4 class="mb-0"><?= number_format((int)$stats['active_events']) ?></h4>
              <small class="text-muted">Event Aktif</small>
            </div>
          </div>
        </div>
        
        <div class="col-6 col-lg-3">
          <div class="stat-card">
            <i class="bi bi-people stat-icon text-info"></i>
            <div>
              <h4 class="mb-0"><?= number_format((int)$stats['verified_registrations']) ?></h4>
              <small class="text-muted">Total Pendaftar</small>
            </div>
          </div>
        </div>
        
        <div class="col-6 col-lg-3">
          <div class="stat-card">
            <i class="bi bi-currency-dollar stat-icon text-warning"></i>
            <div>
              <div class="revenue-text">Rp <?= number_format((float)$stats['total_revenue'], 0, ',', '.') ?></div>
              <small class="text-muted">Total Revenue</small>
            </div>
          </div>
        </div>
      </div>

      <!-- Events List -->
      <div class="row">
        <?php if (!empty($events)): 
          foreach ($events as $event):
            $id = (int)$event['id']; 
            $fmt = strtolower($event['format'] ?? 'both');
            $isOn = !empty($event['is_active']); 
            
            // Safely decode registration_waves with multiple fallbacks
            $waves = [];
            if (isset($event['registration_waves'])) {
                if (is_string($event['registration_waves'])) {
                    $decoded = json_decode($event['registration_waves'], true);
                    $waves = is_array($decoded) ? $decoded : [];
                } elseif (is_array($event['registration_waves'])) {
                    $waves = $event['registration_waves'];
                }
            }
            
            $now = time();
            $activeWaveNum = null;
            
            foreach ($waves as $idx => $w) {
              $start = strtotime($w['registration_start'] ?? '');
              $end = strtotime($w['registration_deadline'] ?? '');
              if ($start && $end && $now >= $start && $now <= $end) {
                $activeWaveNum = $idx + 1;
                break;
              }
            }
            
            // Check if multi-day event
            $isMultiDay = !empty($event['event_end_date']) && $event['event_end_date'] !== $event['event_date'];
            $duration = 1;
            if ($isMultiDay) {
              $start = strtotime($event['event_date']);
              $end = strtotime($event['event_end_date']);
              $duration = round(($end - $start) / 86400) + 1;
            }
            
            $totalPresenters = (int)($event['total_presenters'] ?? 0);
            $presentersOnline = (int)($event['presenters_online'] ?? 0);
            $presentersOffline = (int)($event['presenters_offline'] ?? 0);
            $totalAudience = (int)($event['total_audience'] ?? 0);
            $audienceOnline = (int)($event['audience_online'] ?? 0);
            $audienceOffline = (int)($event['audience_offline'] ?? 0);
        ?>
        <div class="col-12 col-lg-6 mb-4">
          <div class="event-card">
            <div class="event-card-header">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <h5 class="event-title mb-0"><?= esc($event['title']) ?></h5>
                
                <div class="dropdown">
                  <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                    <i class="bi bi-three-dots-vertical"></i>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                      <a class="dropdown-item" href="#" onclick="editEvent(<?= $id ?>)">
                        <i class="bi bi-pencil me-2"></i>Edit Event
                      </a>
                    </li>
                    <li>
                      <a class="dropdown-item" href="#" onclick="editWaves(<?= $id ?>)">
                        <i class="bi bi-cash-stack me-2"></i>Atur Gelombang
                      </a>
                    </li>
                    <li>
                      <a class="dropdown-item" href="#" onclick="toggleStatus(<?= $id ?>)">
                        <i class="bi bi-power me-2"></i><?= $isOn ? 'Nonaktifkan' : 'Aktifkan' ?>
                      </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                      <a class="dropdown-item text-danger" href="#" onclick="forceDeleteEvent(<?= $id ?>)">
                        <i class="bi bi-trash me-2"></i>Hapus
                      </a>
                    </li>
                  </ul>
                </div>
              </div>

              <div class="event-meta">
                <div>
                  <i class="bi bi-calendar me-1"></i>
                  <?php if ($isMultiDay): ?>
                    <?= date('d M Y', strtotime($event['event_date'])) ?> - <?= date('d M Y', strtotime($event['event_end_date'])) ?>
                  <?php else: ?>
                    <?= date('d M Y, H:i', strtotime($event['event_date'].' '.$event['event_time'])) ?>
                  <?php endif; ?>
                </div>
                <?php if ($isMultiDay): ?>
                <div>
                  <i class="bi bi-clock me-1"></i>
                  <?= date('H:i', strtotime($event['event_time'])) ?> - <?= date('H:i', strtotime($event['event_end_time'])) ?> WIB
                </div>
                <?php endif; ?>
              </div>

              <div class="d-flex gap-2 flex-wrap mt-2">
                <span class="badge bg-<?= $fmt === 'online' ? 'info' : ($fmt === 'offline' ? 'warning' : 'success') ?>">
                  <?= ucfirst($fmt === 'both' ? 'Hybrid' : $fmt) ?>
                </span>
                
                <span class="badge bg-<?= $isOn ? 'success' : 'secondary' ?>">
                  <?= $isOn ? 'Aktif' : 'Nonaktif' ?>
                </span>
                
                <?php if ($isMultiDay): ?>
                  <span class="badge bg-info">
                    <i class="bi bi-calendar-range me-1"></i><?= $duration ?> Hari
                  </span>
                <?php endif; ?>
                
                <?php if ($activeWaveNum): ?>
                  <span class="badge bg-primary">
                    Gel. <?= $activeWaveNum ?> <?= $activeWaveNum === 1 ? '⭐' : '' ?>
                  </span>
                <?php endif; ?>
              </div>
            </div>

            <div class="event-card-body">
              <!-- Participant Stats -->
              <div class="participant-stats">
                <div class="row g-2 text-center">
                  <div class="col-6">
                    <div class="stat-box">
                      <i class="bi bi-person-video3"></i>
                      <div class="stat-number"><?= $totalPresenters ?></div>
                      <div class="stat-label">Presenter</div>
                      <small class="text-muted">
                        <?php if ($fmt !== 'offline'): ?>
                          On: <?= $presentersOnline ?> | 
                        <?php endif; ?>
                        Off: <?= $presentersOffline ?>
                      </small>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="stat-box">
                      <i class="bi bi-people"></i>
                      <div class="stat-number"><?= $totalAudience ?></div>
                      <div class="stat-label">Audience</div>
                      <small class="text-muted">On: <?= $audienceOnline ?> | Off: <?= $audienceOffline ?></small>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Waves -->
              <?php if (!empty($waves)): ?>
              <div class="waves-section">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <strong><i class="bi bi-calendar-range me-1"></i>Gelombang Pendaftaran</strong>
                  <button class="btn btn-sm btn-outline-primary" onclick="editWaves(<?= $id ?>)">
                    <i class="bi bi-gear"></i>
                  </button>
                </div>
                
                <?php foreach ($waves as $idx => $wave): 
                  $waveNum = $idx + 1;
                  $regStart = strtotime($wave['registration_start'] ?? '');
                  $regEnd = strtotime($wave['registration_deadline'] ?? '');
                  $isActive = ($regStart && $regEnd && $now >= $regStart && $now <= $regEnd);
                  $isClosed = ($regEnd && $now > $regEnd);
                  $isPending = ($regStart && $now < $regStart);
                ?>
                <div class="wave-item <?= $isActive ? 'active' : ($isClosed ? 'closed' : 'pending') ?>">
                  <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                      <div class="d-flex align-items-center gap-2 mb-1">
                        <strong>Gel. <?= $waveNum ?></strong>
                        <?php if ($waveNum === 1): ?>
                          <span class="badge bg-warning text-dark">Early Bird Offline</span>
                        <?php endif; ?>
                        <?php if ($isActive): ?>
                          <span class="badge bg-success">AKTIF</span>
                        <?php elseif ($isClosed): ?>
                          <span class="badge bg-secondary">TUTUP</span>
                        <?php elseif ($isPending): ?>
                          <span class="badge bg-info">SEGERA</span>
                        <?php endif; ?>
                      </div>
                      <div class="small text-muted">
                        <i class="bi bi-calendar-check me-1"></i>
                        <?= $regStart ? date('d/m H:i', $regStart) : '-' ?> - <?= $regEnd ? date('d/m H:i', $regEnd) : '-' ?>
                      </div>
                    </div>
                    
                    <?php if (!$isClosed): ?>
                    <div class="text-end">
                      <div class="price-display">
                        <?php if ($fmt !== 'offline'): ?>
                          <div class="price-row">
                            <span class="price-label"><i class="bi bi-camera-video text-info"></i> Pres On:</span>
                            <span class="price-value">Rp <?= number_format((float)($wave['presenter_fee_online'] ?? 0), 0, ',', '.') ?></span>
                          </div>
                        <?php endif; ?>
                        
                        <?php if ($fmt !== 'online'): ?>
                          <div class="price-row <?= $waveNum === 1 ? 'early-bird' : '' ?>">
                            <span class="price-label">
                              <i class="bi bi-geo-alt text-warning"></i> Pres Off:
                              <?php if ($waveNum === 1): ?><i class="bi bi-star-fill text-warning"></i><?php endif; ?>
                            </span>
                            <span class="price-value">Rp <?= number_format((float)($wave['presenter_fee_offline'] ?? 0), 0, ',', '.') ?></span>
                          </div>
                        <?php endif; ?>
                        
                        <?php if ($fmt !== 'offline'): ?>
                          <div class="price-row">
                            <span class="price-label"><i class="bi bi-camera-video text-info"></i> Aud On:</span>
                            <span class="price-value">Rp <?= number_format((float)($wave['audience_fee_online'] ?? 0), 0, ',', '.') ?></span>
                          </div>
                        <?php endif; ?>
                        
                        <?php if ($fmt !== 'online'): ?>
                          <div class="price-row <?= $waveNum === 1 ? 'early-bird' : '' ?>">
                            <span class="price-label">
                              <i class="bi bi-geo-alt text-warning"></i> Aud Off:
                              <?php if ($waveNum === 1): ?><i class="bi bi-star-fill text-warning"></i><?php endif; ?>
                            </span>
                            <span class="price-value">Rp <?= number_format((float)($wave['audience_fee_offline'] ?? 0), 0, ',', '.') ?></span>
                          </div>
                        <?php endif; ?>
                      </div>
                    </div>
                    <?php endif; ?>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
              <?php else: ?>
              <div class="text-center py-3">
                <i class="bi bi-calendar-x text-muted" style="font-size:2rem"></i>
                <p class="text-muted small mb-2">Belum ada gelombang</p>
                <button class="btn btn-sm btn-primary" onclick="editWaves(<?= $id ?>)">
                  <i class="bi bi-plus"></i> Atur Gelombang
                </button>
              </div>
              <?php endif; ?>

              <!-- Quick Stats -->
              <div class="quick-stats">
                <div class="row g-2 text-center">
                  <div class="col-3">
                    <div class="quick-stat-item">
                      <div class="stat-value text-primary"><?= (int)($event['total_registrations'] ?? 0) ?></div>
                      <small>Total</small>
                    </div>
                  </div>
                  <div class="col-3">
                    <div class="quick-stat-item">
                      <div class="stat-value text-success"><?= (int)($event['verified_registrations'] ?? 0) ?></div>
                      <small>Verified</small>
                    </div>
                  </div>
                  <div class="col-3">
                    <div class="quick-stat-item">
                      <div class="stat-value text-info"><?= (int)($event['total_abstracts'] ?? 0) ?></div>
                      <small>Abstrak</small>
                    </div>
                  </div>
                  <div class="col-3">
                    <div class="quick-stat-item">
                      <div class="stat-value text-warning"><?= (int)($event['present_count'] ?? 0) ?></div>
                      <small>Hadir</small>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Revenue -->
              <div class="revenue-badge">
                <i class="bi bi-cash-coin me-2"></i>
                <strong>Rp <?= number_format((float)($event['total_revenue'] ?? 0), 0, ',', '.') ?></strong>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; 
        else: ?>
          <div class="col-12">
            <div class="empty-state">
              <i class="bi bi-calendar-x"></i>
              <h5>Belum Ada Event</h5>
              <p class="text-muted">Mulai buat event pertama Anda</p>
              <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#addEventModal">
                <i class="bi bi-plus-lg me-2"></i>Buat Event
              </button>
            </div>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </main>
</div>

<!-- ADD EVENT MODAL -->
<div class="modal fade" id="addEventModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-calendar-plus me-2"></i>Tambah Event Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="addEventForm">
        <?= csrf_field() ?>
        <div class="modal-body">
          
          <div class="mb-3">
            <label class="form-label">Judul Event <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="title" required placeholder="Contoh: Seminar Nasional 2025">
          </div>

          <div class="mb-3">
            <label class="form-label">Deskripsi</label>
            <textarea class="form-control" name="description" rows="3" placeholder="Deskripsi singkat tentang event..."></textarea>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Format <span class="text-danger">*</span></label>
              <select class="form-select" name="format" id="eventFormat" required>
                <option value="both">Hybrid (Online + Offline)</option>
                <option value="online">Online Saja</option>
                <option value="offline">Offline Saja</option>
              </select>
              <small class="text-muted">⚠️ Presenter hanya bisa offline</small>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Status</label>
              <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" name="is_active" id="isActive" checked>
                <label class="form-check-label" for="isActive">Event Aktif</label>
              </div>
            </div>
          </div>

          <hr class="my-4">
          <h6 class="mb-3"><i class="bi bi-calendar-event me-2"></i>Tanggal & Waktu Event</h6>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
              <input type="date" class="form-control" name="event_date" id="eventStartDate" required min="<?= esc($minEventDate) ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Waktu Mulai <span class="text-danger">*</span></label>
              <input type="time" class="form-control" name="event_time" required value="09:00">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Tanggal Selesai <span class="text-danger">*</span></label>
              <input type="date" class="form-control" name="event_end_date" id="eventEndDate" required>
              <small class="text-muted">💡 Sama dengan tanggal mulai untuk event 1 hari</small>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Waktu Selesai <span class="text-danger">*</span></label>
              <input type="time" class="form-control" name="event_end_time" required value="17:00">
            </div>
          </div>

          <hr class="my-4">
          <h6 class="mb-3"><i class="bi bi-geo-alt me-2"></i>Lokasi & Link</h6>

          <div class="conditional-field" id="locationRow">
            <div class="mb-3">
              <label class="form-label">Lokasi Offline <span class="text-danger" id="locationRequired" style="display:none">*</span></label>
              <input type="text" class="form-control" name="location" id="locationInput" placeholder="Contoh: Hotel Grand Indonesia, Jakarta">
            </div>
          </div>

          <div class="conditional-field" id="zoomRow">
            <div class="mb-3">
              <label class="form-label">Link Zoom <span class="text-danger" id="zoomRequired" style="display:none">*</span></label>
              <input type="url" class="form-control" name="zoom_link" id="zoomInput" placeholder="https://zoom.us/j/...">
            </div>
          </div>

          <hr class="my-4">
          <h6 class="mb-3"><i class="bi bi-file-earmark-text me-2"></i>Deadline Pengumpulan</h6>

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Deadline Abstrak</label>
              <input type="datetime-local" class="form-control" name="abstract_deadline">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Deadline Revisi</label>
              <input type="datetime-local" class="form-control" name="abstract_revision_deadline">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Deadline Full Paper</label>
              <input type="datetime-local" class="form-control" name="full_paper_deadline">
            </div>
          </div>

          <div class="alert alert-info small mb-0">
            <i class="bi bi-info-circle me-1"></i>
            <strong>Langkah selanjutnya:</strong> Setelah event dibuat, atur 3 gelombang pendaftaran dengan harga via menu "Atur Gelombang"
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg me-2"></i>Simpan Event
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- EDIT WAVES MODAL -->
<div class="modal fade" id="editWavesModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-fullscreen-md-down">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="bi bi-calendar-range me-2"></i>Atur 3 Gelombang Pendaftaran</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form id="editWavesForm" data-event-id="">
        <?= csrf_field() ?>
        <div class="modal-body">
          <div class="alert alert-warning">
            <strong><i class="bi bi-exclamation-triangle me-2"></i>Aturan Early Bird:</strong>
            <ul class="mb-0 mt-2">
              <li><strong>Gelombang 1 Offline</strong> = Early Bird (harga termurah atau sama)</li>
              <li><strong>Gelombang 2-3</strong> = Harga normal/naik</li>
              <li><strong>Presenter</strong> = Hanya offline (online diisi 0)</li>
              <li><strong>Online</strong> = Tidak ada Early Bird</li>
            </ul>
          </div>
          <div id="wavesFormContent"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg me-2"></i>Simpan Semua Gelombang
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- EDIT EVENT MODAL -->
<div class="modal fade" id="editEventModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Event</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="editEventForm" data-event-id="">
        <?= csrf_field() ?>
        <div class="modal-body" id="editFormContent"></div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg me-2"></i>Update Event
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?= $this->include('partials/footer') ?>

<style>
/* ============================================
   MODERN BLUE EVENT MANAGER - ELEGANT & CLEAN
   ============================================ */

:root {
  --primary: #2563eb;
  --primary-dark: #1e40af;
  --primary-light: #3b82f6;
  --accent: #60a5fa;
  --bg-main: #f8fafc;
  --bg-card: #ffffff;
  --text-main: #1e293b;
  --text-muted: #64748b;
  --border: #e2e8f0;
  --success: #10b981;
  --warning: #f59e0b;
  --info: #06b6d4;
  --danger: #ef4444;
  --shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
  --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

body {
  background: var(--bg-main);
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  color: var(--text-main);
}

/* ============================================
   HEADER SECTION
   ============================================ */

.header-section {
  background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
  padding: 2rem;
  border-radius: 16px;
  box-shadow: var(--shadow-md);
}

.header-section h3 {
  color: white;
  font-weight: 600;
  font-size: 1.75rem;
  margin: 0;
}

.header-section small {
  color: rgba(255, 255, 255, 0.95);
  font-size: 0.875rem;
}

.header-section .btn-primary {
  background: white;
  color: var(--primary);
  border: none;
  padding: 0.625rem 1.5rem;
  border-radius: 10px;
  font-weight: 500;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  transition: all 0.3s ease;
}

.header-section .btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  background: #f8fafc;
  color: var(--primary-dark);
}

/* ============================================
   STAT CARDS
   ============================================ */

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--border);
  transition: all 0.3s ease;
  height: 100%;
}

.stat-card:hover {
  box-shadow: var(--shadow-md);
  transform: translateY(-2px);
}

.stat-card .stat-icon {
  font-size: 2.5rem;
  opacity: 0.9;
  flex-shrink: 0;
}

.stat-card h4 {
  font-size: 1.75rem;
  font-weight: 700;
  margin: 0;
  color: var(--text-main);
}

.stat-card small {
  font-size: 0.813rem;
  color: var(--text-muted);
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.revenue-text {
  font-size: 1rem;
  font-weight: 700;
  color: var(--text-main);
}

/* ============================================
   EVENT CARD
   ============================================ */

.event-card {
  background: white;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--border);
  transition: all 0.3s ease;
  height: 100%;
  display: flex;
  flex-direction: column;
}

.event-card:hover {
  box-shadow: var(--shadow-lg);
  transform: translateY(-4px);
  border-color: var(--accent);
}

/* Event Card Header */
.event-card-header {
  padding: 1.5rem;
  border-bottom: 1px solid var(--border);
  background: linear-gradient(to bottom, #ffffff, #f8fafc);
}

.event-title {
  color: var(--primary);
  font-weight: 600;
  font-size: 1.125rem;
  line-height: 1.4;
  margin: 0;
}

.event-meta {
  font-size: 0.813rem;
  color: var(--text-muted);
  margin-top: 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.event-meta i {
  color: var(--primary);
  font-size: 0.875rem;
}

/* Badges */
.badge {
  padding: 0.375rem 0.75rem;
  border-radius: 6px;
  font-weight: 500;
  font-size: 0.75rem;
  letter-spacing: 0.3px;
}

/* Dropdown Button */
.event-card-header .btn-light {
  background: white;
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: 0.375rem 0.625rem;
  transition: all 0.2s;
}

.event-card-header .btn-light:hover {
  background: var(--bg-main);
  border-color: var(--primary);
}

/* Event Card Body */
.event-card-body {
  padding: 1.5rem;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

/* ============================================
   PARTICIPANT STATS
   ============================================ */

.participant-stats {
  padding-bottom: 1.25rem;
  border-bottom: 2px solid var(--bg-main);
}

.stat-box {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  padding: 1rem;
  border-radius: 10px;
  border: 1px solid var(--border);
  text-align: center;
  transition: all 0.2s;
}

.stat-box:hover {
  background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
  border-color: var(--accent);
}

.stat-box i {
  font-size: 1.75rem;
  color: var(--primary);
  margin-bottom: 0.5rem;
  display: block;
}

.stat-number {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--primary);
  line-height: 1;
  margin: 0.25rem 0;
}

.stat-label {
  font-size: 0.813rem;
  color: var(--text-muted);
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stat-box small {
  font-size: 0.688rem;
  color: var(--text-muted);
}

/* ============================================
   WAVES SECTION
   ============================================ */

.waves-section {
  padding-bottom: 1.25rem;
  border-bottom: 2px solid var(--bg-main);
}

.waves-section strong {
  font-size: 0.875rem;
  color: var(--text-main);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.waves-section .btn-outline-primary {
  border-color: var(--primary);
  color: var(--primary);
  padding: 0.25rem 0.75rem;
  border-radius: 6px;
  font-size: 0.875rem;
}

.waves-section .btn-outline-primary:hover {
  background: var(--primary);
  color: white;
}

.wave-item {
  background: white;
  padding: 1rem;
  border-radius: 10px;
  margin-bottom: 0.75rem;
  border: 2px solid var(--border);
  border-left: 4px solid var(--text-muted);
  transition: all 0.3s ease;
}

.wave-item:hover {
  border-left-color: var(--primary);
  box-shadow: var(--shadow-sm);
}

.wave-item.active {
  background: linear-gradient(to right, #dbeafe, #eff6ff);
  border-color: var(--primary);
  border-left-color: var(--primary);
  border-left-width: 4px;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.wave-item.closed {
  opacity: 0.6;
  background: #f8fafc;
}

.wave-item.pending {
  background: #fef3c7;
  border-left-color: var(--warning);
}

.wave-item strong {
  color: var(--primary);
  font-size: 0.938rem;
}

/* Price Display */
.price-display {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.price-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.5rem;
  padding: 0.25rem 0.5rem;
  background: #f8fafc;
  border-radius: 4px;
  font-size: 0.75rem;
}

.price-row.early-bird {
  background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
  border: 1px solid var(--warning);
  font-weight: 600;
}

.price-label {
  color: var(--text-muted);
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.price-row.early-bird .price-label {
  color: #92400e;
}

.price-value {
  font-weight: 600;
  color: var(--text-main);
}

/* ============================================
   QUICK STATS
   ============================================ */

.quick-stats {
  padding: 1rem;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border-radius: 10px;
  border: 1px solid var(--border);
}

.quick-stat-item {
  text-align: center;
}

.quick-stat-item .stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  line-height: 1;
  margin-bottom: 0.25rem;
}

.quick-stat-item small {
  font-size: 0.688rem;
  color: var(--text-muted);
  text-transform: uppercase;
  font-weight: 600;
  letter-spacing: 0.5px;
}

/* ============================================
   REVENUE BADGE
   ============================================ */

.revenue-badge {
  background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
  color: white;
  padding: 1rem;
  border-radius: 10px;
  text-align: center;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.revenue-badge i {
  font-size: 1.25rem;
}

.revenue-badge strong {
  font-size: 1.125rem;
  font-weight: 700;
}

/* ============================================
   EMPTY STATE
   ============================================ */

.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  background: white;
  border-radius: 16px;
  border: 2px dashed var(--border);
}

.empty-state i {
  font-size: 4rem;
  color: var(--accent);
  opacity: 0.5;
  margin-bottom: 1rem;
}

.empty-state h5 {
  color: var(--text-muted);
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.empty-state p {
  color: var(--text-muted);
  margin-bottom: 1rem;
}

.empty-state .btn-primary {
  background: var(--primary);
  border: none;
  padding: 0.75rem 2rem;
  border-radius: 10px;
  font-weight: 500;
  box-shadow: var(--shadow-md);
}

.empty-state .btn-primary:hover {
  background: var(--primary-dark);
  transform: translateY(-2px);
  box-shadow: var(--shadow-lg);
}

/* ============================================
   MODALS
   ============================================ */

.modal-header {
  background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
  color: white;
  border: none;
  padding: 1.5rem;
}

.modal-header .modal-title {
  font-weight: 600;
  font-size: 1.25rem;
}

.modal-header .btn-close,
.modal-header .btn-close-white {
  opacity: 1;
  filter: brightness(0) invert(1);
}

.modal-body {
  padding: 1.5rem;
  max-height: 70vh;
  overflow-y: auto;
}

.modal-footer {
  border-top: 1px solid var(--border);
  padding: 1rem 1.5rem;
  background: var(--bg-main);
}

/* Form Elements */
.form-label {
  font-weight: 600;
  color: var(--text-main);
  margin-bottom: 0.5rem;
  font-size: 0.875rem;
}

.form-control,
.form-select {
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: 0.625rem 0.875rem;
  font-size: 0.938rem;
  transition: all 0.2s;
}

.form-control:focus,
.form-select:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.alert {
  border-radius: 10px;
  border: none;
  padding: 1rem;
}

.alert-info {
  background: #dbeafe;
  color: #1e40af;
}

.alert-warning {
  background: #fef3c7;
  color: #92400e;
}

/* Wave Form Card */
.wave-form-card {
  background: white;
  border: 2px solid var(--border);
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 1.25rem;
  transition: all 0.3s;
}

.wave-form-card:hover {
  border-color: var(--primary);
  box-shadow: var(--shadow-md);
}

.wave-form-card h6 {
  color: var(--primary);
  font-weight: 600;
  margin-bottom: 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 2px solid var(--bg-main);
  font-size: 1rem;
}

.wave-form-card .badge {
  vertical-align: middle;
}

.wave-form-card .border-success {
  border-color: var(--success) !important;
  border-width: 2px !important;
}

/* ============================================
   BUTTONS
   ============================================ */

.btn {
  border-radius: 8px;
  padding: 0.625rem 1.25rem;
  font-weight: 500;
  transition: all 0.3s ease;
  border: none;
}

.btn-primary {
  background: var(--primary);
  box-shadow: 0 2px 4px rgba(37, 99, 235, 0.3);
}

.btn-primary:hover {
  background: var(--primary-dark);
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(37, 99, 235, 0.4);
}

.btn-secondary {
  background: #e2e8f0;
  color: var(--text-main);
}

.btn-secondary:hover {
  background: #cbd5e1;
}

.btn-sm {
  padding: 0.375rem 0.875rem;
  font-size: 0.875rem;
}

/* ============================================
   DROPDOWN
   ============================================ */

.dropdown-menu {
  border: 1px solid var(--border);
  border-radius: 10px;
  box-shadow: var(--shadow-lg);
  padding: 0.5rem;
}

.dropdown-item {
  border-radius: 6px;
  padding: 0.625rem 1rem;
  font-size: 0.875rem;
  transition: all 0.2s;
}

.dropdown-item:hover {
  background: var(--bg-main);
  color: var(--primary);
}

.dropdown-item.text-danger:hover {
  background: #fee2e2;
  color: var(--danger);
}

.dropdown-divider {
  margin: 0.5rem 0;
  border-color: var(--border);
}

/* ============================================
   CONDITIONAL FIELDS
   ============================================ */

.conditional-field {
  display: none;
  animation: fadeIn 0.3s ease;
}

.conditional-field.show {
  display: block;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* ============================================
   RESPONSIVE
   ============================================ */

@media (max-width: 768px) {
  .header-section {
    padding: 1.5rem;
    border-radius: 12px;
  }

  .header-section h3 {
    font-size: 1.5rem;
  }

  .stat-card {
    padding: 1.25rem;
  }

  .stat-card h4 {
    font-size: 1.5rem;
  }

  .stat-card .stat-icon {
    font-size: 2rem;
  }

  .event-card {
    margin-bottom: 1rem;
  }

  .event-title {
    font-size: 1rem;
  }

  .stat-number {
    font-size: 1.5rem;
  }

  .revenue-text {
    font-size: 0.875rem;
  }

  .price-display {
    width: 100%;
  }

  .wave-item {
    flex-direction: column;
  }
}

/* ============================================
   UTILITIES
   ============================================ */

.text-primary { color: var(--primary) !important; }
.text-muted { color: var(--text-muted) !important; }
.bg-primary { background: var(--primary) !important; }
.border-primary { border-color: var(--primary) !important; }

/* Smooth Transitions */
* {
  transition: background-color 0.2s ease, border-color 0.2s ease;
}
</style>

<script>
const csrfToken = '<?= csrf_hash() ?>';

document.addEventListener('DOMContentLoaded', function() {
  try {
    setupFormHandlers();
    setupDateValidation();
  } catch (error) {
    console.error('Error initializing event page:', error);
  }
});

async function fetchJSON(url, options = {}) {
  const res = await fetch(url, options);
  if (!res.ok) throw new Error('Network error');
  return res.json();
}

function setupFormHandlers() {
  const addForm = document.getElementById('addEventForm');
  const editForm = document.getElementById('editEventForm');
  const wavesForm = document.getElementById('editWavesForm');
  const formatSelect = document.getElementById('eventFormat');

  if (addForm) {
    addForm.addEventListener('submit', function(e) {
      e.preventDefault();
      if (validateEventDates()) {
        submitForm(this, '<?= base_url("admin/event/create") ?>');
      }
    });
  }

  if (editForm) {
    editForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const eventId = this.dataset.eventId;
      if (eventId && validateEventDates('edit')) {
        submitForm(this, '<?= base_url("admin/event/update") ?>/' + eventId);
      }
    });
  }

  if (wavesForm) {
    wavesForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const eventId = this.dataset.eventId;
      if (eventId) {
        submitWaves(this, eventId);
      }
    });
  }

  if (formatSelect) {
    formatSelect.addEventListener('change', handleFormatChange);
    // Trigger initial setup
    handleFormatChange();
  }
}

function setupDateValidation() {
  const startDate = document.getElementById('eventStartDate');
  const endDate = document.getElementById('eventEndDate');

  if (startDate && endDate) {
    startDate.addEventListener('change', function() {
      endDate.min = this.value;
      if (endDate.value && endDate.value < this.value) {
        endDate.value = this.value;
      }
    });
  }
}

function validateEventDates(formType = 'add') {
  const startDate = document.querySelector('[name="event_date"]')?.value;
  const endDate = document.querySelector('[name="event_end_date"]')?.value;

  if (!startDate || !endDate) {
    Swal.fire('Error!', 'Tanggal mulai dan selesai harus diisi', 'error');
    return false;
  }

  if (new Date(endDate) < new Date(startDate)) {
    Swal.fire('Error!', 'Tanggal selesai harus setelah atau sama dengan tanggal mulai', 'error');
    return false;
  }

  return true;
}

function handleFormatChange() {
  const formatSelect = document.getElementById('eventFormat');
  if (!formatSelect) return;
  
  const format = formatSelect.value;
  if (!format) return;

  const locationRow = document.getElementById('locationRow');
  const zoomRow = document.getElementById('zoomRow');
  const locInput = document.getElementById('locationInput');
  const zoomInput = document.getElementById('zoomInput');
  const locStar = document.getElementById('locationRequired');
  const zoomStar = document.getElementById('zoomRequired');

  // Remove 'show' class and required attributes
  if (locationRow) locationRow.classList.remove('show');
  if (zoomRow) zoomRow.classList.remove('show');
  if (locInput) locInput.removeAttribute('required');
  if (zoomInput) zoomInput.removeAttribute('required');
  if (locStar) locStar.style.display = 'none';
  if (zoomStar) zoomStar.style.display = 'none';

  // Apply based on format
  if (format === 'offline') {
    if (locationRow) locationRow.classList.add('show');
    if (locInput) locInput.setAttribute('required', 'required');
    if (locStar) locStar.style.display = 'inline';
  } else if (format === 'online') {
    if (zoomRow) zoomRow.classList.add('show');
    if (zoomInput) zoomInput.setAttribute('required', 'required');
    if (zoomStar) zoomStar.style.display = 'inline';
  } else if (format === 'both') {
    if (locationRow) locationRow.classList.add('show');
    if (zoomRow) zoomRow.classList.add('show');
    if (locInput) locInput.setAttribute('required', 'required');
    if (zoomInput) zoomInput.setAttribute('required', 'required');
    if (locStar) locStar.style.display = 'inline';
    if (zoomStar) zoomStar.style.display = 'inline';
  }
}

function submitForm(form, url) {
  const btn = form.querySelector('button[type="submit"]');
  if (!btn) return;
  
  const originalText = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';

  fetchJSON(url, {
    method: 'POST',
    body: new FormData(form),
    headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
  })
  .then(data => {
    if (data.success) {
      Swal.fire({ 
        icon: 'success', 
        title: 'Berhasil!', 
        text: data.message, 
        timer: 1500,
        showConfirmButton: false
      }).then(() => location.reload());
    } else {
      throw new Error(data.message || 'Error');
    }
  })
  .catch(err => Swal.fire({ icon: 'error', title: 'Error!', text: err.message }))
  .finally(() => {
    btn.disabled = false;
    btn.innerHTML = originalText;
  });
}

function editWaves(eventId) {
  fetchJSON('<?= base_url("admin/event/get") ?>/' + eventId, {
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
  .then(data => {
    if (!data.success) throw new Error(data.message);
    populateWavesForm(data.event);
    const form = document.getElementById('editWavesForm');
    if (form) {
      form.dataset.eventId = eventId;
      const modal = new bootstrap.Modal(document.getElementById('editWavesModal'));
      modal.show();
    }
  })
  .catch(err => Swal.fire('Error!', err.message, 'error'));
}

function populateWavesForm(event) {
  const format = event.format || 'both';
  let waves = [];
  
  if (typeof event.registration_waves === 'string') {
    try {
      waves = JSON.parse(event.registration_waves);
    } catch(e) {
      waves = [];
    }
  } else if (Array.isArray(event.registration_waves)) {
    waves = event.registration_waves;
  }

  while (waves.length < 3) waves.push({});

  let html = '';

  for (let i = 0; i < 3; i++) {
    const wave = waves[i] || {};
    const waveNum = i + 1;
    
    html += `
      <div class="wave-form-card">
        <h6>
          Gelombang ${waveNum} 
          ${waveNum === 1 ? '<span class="badge bg-warning text-dark ms-2"><i class="bi bi-star-fill me-1"></i>Early Bird Offline</span>' : ''}
          ${waveNum === 2 ? '<span class="badge bg-info ms-2">Normal</span>' : ''}
          ${waveNum === 3 ? '<span class="badge bg-primary ms-2">Last Call</span>' : ''}
        </h6>
        
        <div class="row mb-3">
          <div class="col-md-6 mb-2">
            <label class="form-label small">Dibuka <span class="text-danger">*</span></label>
            <input type="datetime-local" class="form-control" 
                   name="waves[${i}][registration_start]" 
                   value="${wave.registration_start ? wave.registration_start.slice(0,16) : ''}" required>
          </div>
          <div class="col-md-6 mb-2">
            <label class="form-label small">Ditutup <span class="text-danger">*</span></label>
            <input type="datetime-local" class="form-control" 
                   name="waves[${i}][registration_deadline]" 
                   value="${wave.registration_deadline ? wave.registration_deadline.slice(0,16) : ''}" required>
          </div>
        </div>
        
        <div class="alert alert-info small mb-3">
          <i class="bi bi-info-circle me-1"></i>
          ${waveNum === 1 ? '<strong>Early Bird:</strong> Harga offline harus termurah atau sama dengan gelombang berikutnya' : ''}
          ${waveNum === 2 ? '<strong>Normal:</strong> Harga bisa naik dari Early Bird' : ''}
          ${waveNum === 3 ? '<strong>Last Call:</strong> Harga tertinggi sebelum event' : ''}
        </div>
        
        <div class="row">
          ${format !== 'offline' ? `
          <div class="col-md-6 mb-2">
            <label class="form-label small">
              <i class="bi bi-camera-video text-info"></i> Presenter Online 
              <span class="text-danger">*</span>
            </label>
            <div class="input-group">
              <span class="input-group-text">Rp</span>
              <input type="number" class="form-control" 
                     name="waves[${i}][presenter_fee_online]" 
                     value="${wave.presenter_fee_online || 0}" min="0" step="1000" required 
                     placeholder="0">
            </div>
            <small class="text-muted">⚠️ Biasanya 0 (presenter offline saja)</small>
          </div>` : '<input type="hidden" name="waves['+i+'][presenter_fee_online]" value="0">'}
          
          ${format !== 'online' ? `
          <div class="col-md-6 mb-2">
            <label class="form-label small">
              <i class="bi bi-geo-alt text-warning"></i> Presenter Offline 
              ${waveNum === 1 ? '<i class="bi bi-star-fill text-warning"></i>' : ''}
              <span class="text-danger">*</span>
            </label>
            <div class="input-group">
              <span class="input-group-text">Rp</span>
              <input type="number" class="form-control ${waveNum === 1 ? 'border-success border-2' : ''}" 
                     name="waves[${i}][presenter_fee_offline]" 
                     value="${wave.presenter_fee_offline || ''}" min="0" step="1000" required 
                     placeholder="${waveNum === 1 ? '150000 (termurah)' : (waveNum === 2 ? '200000' : '250000')}">
            </div>
          </div>` : '<input type="hidden" name="waves['+i+'][presenter_fee_offline]" value="0">'}
          
          ${format !== 'offline' ? `
          <div class="col-md-6 mb-2">
            <label class="form-label small">
              <i class="bi bi-camera-video text-info"></i> Audience Online 
              <span class="text-danger">*</span>
            </label>
            <div class="input-group">
              <span class="input-group-text">Rp</span>
              <input type="number" class="form-control" 
                     name="waves[${i}][audience_fee_online]" 
                     value="${wave.audience_fee_online || ''}" min="0" step="1000" required 
                     placeholder="${waveNum === 1 ? '75000' : (waveNum === 2 ? '100000' : '125000')}">
            </div>
          </div>` : '<input type="hidden" name="waves['+i+'][audience_fee_online]" value="0">'}
          
          ${format !== 'online' ? `
          <div class="col-md-6 mb-2">
            <label class="form-label small">
              <i class="bi bi-geo-alt text-warning"></i> Audience Offline 
              ${waveNum === 1 ? '<i class="bi bi-star-fill text-warning"></i>' : ''}
              <span class="text-danger">*</span>
            </label>
            <div class="input-group">
              <span class="input-group-text">Rp</span>
              <input type="number" class="form-control ${waveNum === 1 ? 'border-success border-2' : ''}" 
                     name="waves[${i}][audience_fee_offline]" 
                     value="${wave.audience_fee_offline || ''}" min="0" step="1000" required 
                     placeholder="${waveNum === 1 ? '100000 (termurah)' : (waveNum === 2 ? '150000' : '175000')}">
            </div>
          </div>` : '<input type="hidden" name="waves['+i+'][audience_fee_offline]" value="0">'}
        </div>
      </div>`;
  }

  const content = document.getElementById('wavesFormContent');
  if (content) content.innerHTML = html;
}

function submitWaves(form, eventId) {
  const fd = new FormData(form);
  const waves = [];
  
  for (let i = 0; i < 3; i++) {
    const wave = {
      registration_start: fd.get(`waves[${i}][registration_start]`),
      registration_deadline: fd.get(`waves[${i}][registration_deadline]`),
      presenter_fee_online: parseFloat(fd.get(`waves[${i}][presenter_fee_online]`)) || 0,
      presenter_fee_offline: parseFloat(fd.get(`waves[${i}][presenter_fee_offline]`)) || 0,
      audience_fee_online: parseFloat(fd.get(`waves[${i}][audience_fee_online]`)) || 0,
      audience_fee_offline: parseFloat(fd.get(`waves[${i}][audience_fee_offline]`)) || 0
    };
    
    if (!wave.registration_start || !wave.registration_deadline) {
      return Swal.fire('Error!', `Gelombang ${i+1}: Tanggal harus diisi`, 'error');
    }
    
    if (new Date(wave.registration_start) >= new Date(wave.registration_deadline)) {
      return Swal.fire('Error!', `Gelombang ${i+1}: Tanggal mulai harus lebih awal dari tanggal selesai`, 'error');
    }
    
    waves.push(wave);
  }
  
  // Validate no overlap
  for (let i = 0; i < 2; i++) {
    if (new Date(waves[i].registration_deadline) >= new Date(waves[i+1].registration_start)) {
      return Swal.fire('Error!', `Gelombang ${i+2} harus dimulai setelah gelombang ${i+1} selesai`, 'error');
    }
  }

  // Validate Early Bird rule (Wave 1 offline <= Wave 2 & 3)
  if (waves[0].presenter_fee_offline > 0) {
    for (let i = 1; i < 3; i++) {
      if (waves[0].presenter_fee_offline > waves[i].presenter_fee_offline && waves[i].presenter_fee_offline > 0) {
        return Swal.fire('Error!', 
          `Early Bird: Gelombang 1 presenter offline (Rp ${waves[0].presenter_fee_offline.toLocaleString()}) harus ≤ gelombang ${i+1} (Rp ${waves[i].presenter_fee_offline.toLocaleString()})`, 
          'error');
      }
      if (waves[0].audience_fee_offline > waves[i].audience_fee_offline && waves[i].audience_fee_offline > 0) {
        return Swal.fire('Error!', 
          `Early Bird: Gelombang 1 audience offline (Rp ${waves[0].audience_fee_offline.toLocaleString()}) harus ≤ gelombang ${i+1} (Rp ${waves[i].audience_fee_offline.toLocaleString()})`, 
          'error');
      }
    }
  }

  const btn = form.querySelector('button[type="submit"]');
  if (btn) {
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';

    fetchJSON('<?= base_url("admin/event/update-waves") ?>/' + eventId, {
      method: 'POST',
      body: JSON.stringify({ waves: waves }),
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    .then(data => {
      if (data.success) {
        Swal.fire({ 
          icon: 'success', 
          title: 'Berhasil!', 
          text: 'Gelombang berhasil disimpan',
          timer: 1500,
          showConfirmButton: false
        }).then(() => location.reload());
      } else {
        throw new Error(data.message);
      }
    })
    .catch(err => Swal.fire({ icon: 'error', title: 'Error!', text: err.message }))
    .finally(() => { 
      if(btn) {
        btn.disabled = false;
        btn.innerHTML = originalText;
      }
    });
  }
}

function editEvent(eventId) {
  fetchJSON('<?= base_url("admin/event/get") ?>/' + eventId, {
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
  .then(data => {
    if (!data.success) throw new Error(data.message);
    populateEditForm(data.event);
    const form = document.getElementById('editEventForm');
    if (form) {
      form.dataset.eventId = eventId;
      const modal = new bootstrap.Modal(document.getElementById('editEventModal'));
      modal.show();
    }
  })
  .catch(err => Swal.fire('Error!', err.message, 'error'));
}

function populateEditForm(event) {
  const format = event.format || 'both';
  const html = `
    <div class="mb-3">
      <label class="form-label">Judul <span class="text-danger">*</span></label>
      <input type="text" class="form-control" name="title" value="${event.title || ''}" required>
    </div>
    
    <div class="mb-3">
      <label class="form-label">Deskripsi</label>
      <textarea class="form-control" name="description" rows="2">${event.description || ''}</textarea>
    </div>
    
    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label">Format <span class="text-danger">*</span></label>
        <select class="form-select" name="format" id="editFormat" required>
          <option value="both" ${format === 'both' ? 'selected' : ''}>Hybrid</option>
          <option value="online" ${format === 'online' ? 'selected' : ''}>Online</option>
          <option value="offline" ${format === 'offline' ? 'selected' : ''}>Offline</option>
        </select>
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label">Status</label>
        <div class="form-check form-switch mt-2">
          <input class="form-check-input" type="checkbox" name="is_active" ${event.is_active ? 'checked' : ''}>
          <label class="form-check-label">Aktif</label>
        </div>
      </div>
    </div>
    
    <hr class="my-3">
    <h6 class="mb-3"><i class="bi bi-calendar-event me-2"></i>Tanggal & Waktu</h6>
    
    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
        <input type="date" class="form-control" name="event_date" value="${event.event_date || ''}" required>
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label">Waktu Mulai <span class="text-danger">*</span></label>
        <input type="time" class="form-control" name="event_time" value="${(event.event_time || '').substring(0,5)}" required>
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label">Tanggal Selesai <span class="text-danger">*</span></label>
        <input type="date" class="form-control" name="event_end_date" value="${event.event_end_date || event.event_date || ''}" required>
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label">Waktu Selesai <span class="text-danger">*</span></label>
        <input type="time" class="form-control" name="event_end_time" value="${(event.event_end_time || event.event_time || '').substring(0,5)}" required>
      </div>
    </div>
    
    ${format !== 'online' ? `
    <div class="mb-3">
      <label class="form-label">Lokasi ${format === 'offline' ? '<span class="text-danger">*</span>' : ''}</label>
      <input type="text" class="form-control" name="location" value="${event.location || ''}" ${format === 'offline' ? 'required' : ''}>
    </div>` : ''}
    
    ${format !== 'offline' ? `
    <div class="mb-3">
      <label class="form-label">Link Zoom ${format === 'online' ? '<span class="text-danger">*</span>' : ''}</label>
      <input type="url" class="form-control" name="zoom_link" value="${event.zoom_link || ''}" ${format === 'online' ? 'required' : ''}>
    </div>` : ''}
  `;
  const content = document.getElementById('editFormContent');
  if (content) content.innerHTML = html;
}

function toggleStatus(id) {
  fetchJSON('<?= base_url("admin/event/toggle-status") ?>/' + id, {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
  })
  .then(d => {
    if (!d.success) throw new Error(d.message);
    Swal.fire({ 
      icon: 'success', 
      title: 'Sukses', 
      text: d.message,
      timer: 1000,
      showConfirmButton: false
    }).then(() => location.reload());
  })
  .catch(err => Swal.fire('Error!', err.message, 'error'));
}

function forceDeleteEvent(id) {
  Swal.fire({
    title: 'Hapus Event?',
    html: '<p class="mb-2">Semua data akan dihapus permanen!</p><p class="text-danger small mb-0">⚠️ Event dengan peserta verified tidak bisa dihapus</p>',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, Hapus!',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b'
  }).then(res => {
    if (!res.isConfirmed) return;
    
    fetchJSON('<?= base_url("admin/event/delete") ?>/' + id, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(d => {
      if (!d.success) throw new Error(d.message);
      Swal.fire({ 
        icon: 'success', 
        title: 'Terhapus', 
        text: 'Event berhasil dihapus',
        timer: 1000,
        showConfirmButton: false
      }).then(() => location.reload());
    })
    .catch(err => Swal.fire('Error!', err.message, 'error'));
  });
}
</script>