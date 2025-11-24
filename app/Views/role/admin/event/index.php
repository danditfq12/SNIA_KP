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
<link rel="stylesheet" href="<?= base_url('assets/css/event_admin.css'); ?>">

<meta name="csrf-token" content="<?= csrf_hash() ?>"/>

<style>
/* Warning Styles */
.price-warning {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 600;
  margin-left: 8px;
  animation: pulse 1s infinite;
}

.price-warning.same-price {
  background: #fef3c7;
  color: #92400e;
  border: 1px solid #fbbf24;
}

.price-warning.price-drop {
  background: #fee2e2;
  color: #991b1b;
  border: 1px solid #ef4444;
}

.price-warning.price-increase {
  background: #d1fae5;
  color: #065f46;
  border: 1px solid #10b981;
}

.price-warning.zero-price {
  background: #fecaca;
  color: #7f1d1d;
  border: 1px solid #dc2626;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.8; }
}

.wave-form-card.has-error {
  border-color: #ef4444 !important;
  background: linear-gradient(to bottom, #fef2f2 0%, #ffffff 100%);
}

.input-group-text {
  background: #f3f4f6;
  border-right: none;
}

.input-group .form-control {
  border-left: none;
}

.input-group .form-control:focus {
  border-left: none;
  box-shadow: none;
}

.date-disabled-hint {
  font-size: 0.75rem;
  color: #6b7280;
  margin-top: 4px;
}

/* ✅ Form Validation Styles */
.form-control.is-invalid,
.form-select.is-invalid {
  border-color: #dc3545;
  padding-right: calc(1.5em + 0.75rem);
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
  background-repeat: no-repeat;
  background-position: right calc(0.375em + 0.1875rem) center;
  background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

.form-control.is-invalid:focus,
.form-select.is-invalid:focus {
  border-color: #dc3545;
  box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
}

.invalid-feedback {
  display: none;
  width: 100%;
  margin-top: 0.25rem;
  font-size: 0.875em;
  color: #dc3545;
}

.is-invalid ~ .invalid-feedback {
  display: block;
}

#addEventWarning .alert {
  border-radius: 8px;
  font-size: 0.9rem;
  animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>

<div id="content">
  <main class="flex-fill" style="padding-top:70px; padding-bottom: 40px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- Header -->
      <div class="header-section mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
          <div>
            <h3><i class="bi bi-calendar3 me-2"></i>Kelola Event</h3>
            <small>
              <i class="bi bi-star-fill text-warning me-1"></i>
              ✅ Presenter & Audience bisa Online/Offline | Gelombang bisa ditambah/dikurangi
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
              <h4><?= number_format((int)$stats['total_events']) ?></h4>
              <small>Total Event</small>
            </div>
          </div>
        </div>
        
        <div class="col-6 col-lg-3">
          <div class="stat-card">
            <i class="bi bi-check2-circle stat-icon text-success"></i>
            <div>
              <h4><?= number_format((int)$stats['active_events']) ?></h4>
              <small>Event Aktif</small>
            </div>
          </div>
        </div>
        
        <div class="col-6 col-lg-3">
          <div class="stat-card">
            <i class="bi bi-people stat-icon text-info"></i>
            <div>
              <h4><?= number_format((int)$stats['verified_registrations']) ?></h4>
              <small>Total Pendaftar</small>
            </div>
          </div>
        </div>
        
        <div class="col-6 col-lg-3">
          <div class="stat-card">
            <i class="bi bi-currency-dollar stat-icon text-warning"></i>
            <div>
              <div class="revenue-text">Rp <?= number_format((float)$stats['total_revenue'], 0, ',', '.') ?></div>
              <small>Total Revenue</small>
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
            
            // Safely decode registration_waves
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
                <h5 class="event-title"><?= esc($event['title']) ?></h5>
                
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
                  <i class="bi bi-calendar"></i>
                  <?php if ($isMultiDay): ?>
                    <?= date('d M Y', strtotime($event['event_date'])) ?> - <?= date('d M Y', strtotime($event['event_end_date'])) ?>
                  <?php else: ?>
                    <?= date('d M Y, H:i', strtotime($event['event_date'].' '.$event['event_time'])) ?>
                  <?php endif; ?>
                </div>
                <?php if ($isMultiDay): ?>
                <div>
                  <i class="bi bi-clock"></i>
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
                    <i class="bi bi-calendar2-range me-1"></i><?= $duration ?> Hari
                  </span>
                <?php endif; ?>
                
                <?php if ($activeWaveNum): ?>
                  <span class="badge bg-primary">
                    <i class="bi bi-bullseye me-1"></i>Gelombang <?= $activeWaveNum ?> Aktif
                  </span>
                <?php elseif (count($waves) > 0): ?>
                  <span class="badge bg-secondary">
                    <i class="bi bi-calendar-x me-1"></i>Tidak ada gelombang aktif
                  </span>
                <?php else: ?>
                  <span class="badge bg-danger">
                    <i class="bi bi-exclamation-triangle me-1"></i>Gelombang belum diatur
                  </span>
                <?php endif; ?>
              </div>
            </div>

            <div class="event-card-body">
              <!-- Participants Stats -->
              <div class="participant-stats">
                <div class="row g-2">
                  <div class="col-6">
                    <div class="stat-box">
                      <i class="bi bi-person-video3"></i>
                      <div class="stat-number"><?= $totalPresenters ?></div>
                      <div class="stat-label">Presenter</div>
                      <small><?= $presentersOnline ?> online, <?= $presentersOffline ?> offline</small>
                    </div>
                  </div>
                  
                  <div class="col-6">
                    <div class="stat-box">
                      <i class="bi bi-people"></i>
                      <div class="stat-number"><?= $totalAudience ?></div>
                      <div class="stat-label">Audience</div>
                      <small><?= $audienceOnline ?> online, <?= $audienceOffline ?> offline</small>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Wave Info -->
              <?php if (count($waves) > 0): ?>
              <div class="waves-section">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <strong><i class="bi bi-calendar-range me-1"></i>Gelombang (<?= count($waves) ?>)</strong>
                </div>
                <div>
                  <?php foreach ($waves as $idx => $w): 
                    $wNum = $idx + 1;
                    $wStart = strtotime($w['registration_start'] ?? '');
                    $wEnd = strtotime($w['registration_deadline'] ?? '');
                    $isActive = $wStart && $wEnd && $now >= $wStart && $now <= $wEnd;
                    $isPast = $wEnd && $now > $wEnd;
                    $isPending = $wStart && $now < $wStart;
                  ?>
                  <div class="wave-item <?= $isActive ? 'active' : ($isPast ? 'closed' : ($isPending ? 'pending' : '')) ?>">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                      <div>
                        <strong>Gelombang <?= $wNum ?></strong>
                        <?php if ($wNum === 1): ?>
                          <span class="badge bg-warning">
                            <i class="bi bi-star-fill me-1"></i>Early Bird
                          </span>
                        <?php endif; ?>
                        <?php if ($isActive): ?>
                          <span class="badge bg-primary">Aktif</span>
                        <?php elseif ($isPast): ?>
                          <span class="badge bg-secondary">Selesai</span>
                        <?php elseif ($isPending): ?>
                          <span class="badge bg-info">Segera</span>
                        <?php endif; ?>
                      </div>
                      <small class="text-muted">
                        <?= date('d M', $wStart) ?> - <?= date('d M Y', $wEnd) ?>
                      </small>
                    </div>
                    <div class="price-display">
                      <div class="price-row <?= $wNum === 1 ? 'early-bird' : '' ?>">
                        <span class="price-label">
                          <i class="bi bi-person-video3"></i> Presenter Online
                        </span>
                        <span class="price-value">Rp <?= number_format($w['presenter_fee_online'] ?? 0, 0, ',', '.') ?></span>
                      </div>
                      <div class="price-row <?= $wNum === 1 ? 'early-bird' : '' ?>">
                        <span class="price-label">
                          <i class="bi bi-person-badge"></i> Presenter Offline
                        </span>
                        <span class="price-value">Rp <?= number_format($w['presenter_fee_offline'] ?? 0, 0, ',', '.') ?></span>
                      </div>
                      <div class="price-row">
                        <span class="price-label">
                          <i class="bi bi-laptop"></i> Audience Online
                        </span>
                        <span class="price-value">Rp <?= number_format($w['audience_fee_online'] ?? 0, 0, ',', '.') ?></span>
                      </div>
                      <div class="price-row">
                        <span class="price-label">
                          <i class="bi bi-people"></i> Audience Offline
                        </span>
                        <span class="price-value">Rp <?= number_format($w['audience_fee_offline'] ?? 0, 0, ',', '.') ?></span>
                      </div>
                    </div>
                  </div>
                  <?php endforeach; ?>
                </div>
              </div>
              <?php else: ?>
              <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle me-1"></i>
                Gelombang belum diatur. Klik "Atur Gelombang" untuk mengatur.
              </div>
              <?php endif; ?>

              <!-- Revenue Badge -->
              <div class="revenue-badge">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <i class="bi bi-currency-dollar me-1"></i>
                    <small>Total Revenue</small>
                  </div>
                  <strong>Rp <?= number_format((float)($event['total_revenue'] ?? 0), 0, ',', '.') ?></strong>
                </div>
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
            <p>Mulai dengan membuat event baru untuk sistem SNIA Anda</p>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEventModal">
              <i class="bi bi-plus-lg me-2"></i>Tambah Event Pertama
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
        <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Tambah Event Baru</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form id="addEventForm">
        <?= csrf_field() ?>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Judul Event <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="title" required 
                   placeholder="Contoh: SNIA 2025 - Seminar Nasional Informatika">
            <div class="invalid-feedback">Judul event minimal 5 karakter</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Deskripsi</label>
            <textarea class="form-control" name="description" rows="3" 
                      placeholder="Deskripsi singkat tentang event..."></textarea>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Format <span class="text-danger">*</span></label>
              <select class="form-select" name="format" id="eventFormat" required>
                <option value="both">Hybrid (Online + Offline)</option>
                <option value="online">Online Saja</option>
                <option value="offline">Offline Saja</option>
              </select>
              <small class="text-muted">
                ✅ Presenter & Audience bisa pilih online/offline
              </small>
              <div class="invalid-feedback">Format event harus dipilih</div>
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
              <input type="date" class="form-control" name="event_date" id="eventStartDate" 
                     required min="<?= esc($minEventDate) ?>">
              <div class="invalid-feedback">Tanggal mulai harus diisi</div>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Waktu Mulai <span class="text-danger">*</span></label>
              <input type="time" class="form-control" name="event_time" required value="09:00">
              <div class="invalid-feedback">Waktu mulai harus diisi</div>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Tanggal Selesai <span class="text-danger">*</span></label>
              <input type="date" class="form-control" name="event_end_date" id="eventEndDate" required>
              <small class="text-muted">💡 Sama dengan tanggal mulai untuk event 1 hari</small>
              <div class="invalid-feedback">Tanggal selesai harus diisi dan setelah tanggal mulai</div>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Waktu Selesai <span class="text-danger">*</span></label>
              <input type="time" class="form-control" name="event_end_time" required value="17:00">
              <div class="invalid-feedback">Waktu selesai harus diisi</div>
            </div>
          </div>

          <hr class="my-4">
          <h6 class="mb-3"><i class="bi bi-geo-alt me-2"></i>Lokasi & Link</h6>

          <div class="conditional-field show" id="locationRow">
            <div class="mb-3">
              <label class="form-label">Lokasi Offline <span class="text-danger" id="locationRequired" style="display:none">*</span></label>
              <input type="text" class="form-control" name="location" id="locationInput" 
                     placeholder="Contoh: Hotel Grand Indonesia, Jakarta">
              <div class="invalid-feedback">Lokasi offline harus diisi sesuai format event</div>
            </div>
          </div>

          <div class="conditional-field show" id="zoomRow">
            <div class="mb-3">
              <label class="form-label">Link Zoom <span class="text-danger" id="zoomRequired" style="display:none">*</span></label>
              <input type="url" class="form-control" name="zoom_link" id="zoomInput" 
                     placeholder="https://zoom.us/j/...">
              <div class="invalid-feedback">Link Zoom harus berupa URL yang valid (https://...)</div>
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

          <div class="alert alert-info">
            <i class="bi bi-info-circle me-1"></i>
            <strong>Langkah selanjutnya:</strong> Setelah event dibuat, atur gelombang pendaftaran dengan harga via menu "Atur Gelombang"
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
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-calendar-range me-2"></i>Atur Gelombang Pendaftaran</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form id="editWavesForm" data-event-id="" data-event-date="" data-event-end-date="">
        <?= csrf_field() ?>
        <div class="modal-body">
          <div class="alert alert-info">
            <strong><i class="bi bi-info-circle me-2"></i>Aturan:</strong>
            <ul class="mb-0 mt-2">
              <li><strong>Harga TIDAK BOLEH 0</strong> = Semua harga harus diisi</li>
              <li><strong>Gelombang 1 Offline</strong> = Early Bird (harga termurah)</li>
              <li><strong>Gelombang 2+</strong> = Harga HARUS NAIK dari gelombang sebelumnya</li>
              <li><strong>Tanggal:</strong> Tidak boleh overlap antar gelombang & harus sebelum event dimulai</li>
              <li><strong>Presenter & Audience</strong> = Bisa pilih online/offline</li>
              <li><strong>Format harga:</strong> Otomatis diformat dengan titik (contoh: 150.000)</li>
            </ul>
          </div>
          
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">Daftar Gelombang</h6>
            <button type="button" class="btn btn-success btn-sm" onclick="addWaveRow()">
              <i class="bi bi-plus-lg me-1"></i>Tambah Gelombang
            </button>
          </div>
          
          <div id="wavesContainer"></div>
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
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
let currentEventId = null;
let currentEventDate = null;
let currentEventEndDate = null;

// ===== HELPER FUNCTIONS =====
function getCsrfToken() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.content : '';
}

async function fetchJSON(url, options = {}) {
  try {
    const response = await fetch(url, options);
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
    return await response.json();
  } catch (error) {
    console.error('Fetch error:', error);
    throw error;
  }
}

// ✅ Format number dengan titik sebagai pemisah ribuan
function formatNumber(num) {
  if (typeof num === 'string') {
    num = num.replace(/\./g, '');
  }
  num = parseFloat(num) || 0;
  return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

// ✅ Parse formatted number
function parseFormattedNumber(str) {
  if (!str) return 0;
  const cleaned = str.toString().replace(/\./g, '');
  return parseFloat(cleaned) || 0;
}

// ✅ Validate if input is valid number (TIDAK BOLEH 0)
function isValidNumber(str) {
  if (!str) return false;
  const cleaned = str.toString().replace(/\./g, '');
  const num = parseFloat(cleaned);
  return !isNaN(num) && cleaned.trim() !== '' && num > 0; // 
}

// ✅ Validate URL
function isValidURL(string) {
  try {
    new URL(string);
    return true;
  } catch (_) {
    return false;
  }
}

// ✅ Auto-format input
function formatCurrencyInput(input) {
  const cursorPos = input.selectionStart;
  const oldValue = input.value;
  let value = input.value.replace(/[^\d]/g, '');
  
  if (value) {
    const formatted = formatNumber(value);
    input.value = formatted;
    const dotsAdded = formatted.length - oldValue.replace(/\./g, '').length;
    const newCursorPos = cursorPos + dotsAdded;
    input.setSelectionRange(newCursorPos, newCursorPos);
  }
  
  input.classList.remove('is-invalid');
}

// ✅ Validate price input (HARUS > 0, TIDAK BOLEH 0)
function validatePriceInput(input) {
  const value = input.value.trim();
  
  // ✅ Validasi: Harus diisi
  if (!value || !isValidNumber(value)) {
    input.classList.add('is-invalid');
    const feedback = input.parentElement.parentElement.querySelector('.invalid-feedback');
    if (feedback) {
      feedback.textContent = 'Harga harus diisi dan tidak boleh 0';
    }
    return false;
  }
  
  const num = parseFormattedNumber(value);
  
  // ✅ Validasi: Tidak boleh 0 atau negatif
  if (num <= 0) {
    input.classList.add('is-invalid');
    const feedback = input.parentElement.parentElement.querySelector('.invalid-feedback');
    if (feedback) {
      feedback.textContent = 'Harga tidak boleh 0 atau negatif (minimal Rp 1.000)';
    }
    return false;
  }
  
  // ✅ Validasi: Minimal Rp 1.000
  if (num < 1000) {
    input.classList.add('is-invalid');
    const feedback = input.parentElement.parentElement.querySelector('.invalid-feedback');
    if (feedback) {
      feedback.textContent = 'Harus naik dari gelombang sebelumnnya';
    }
    return false;
  }
  
  input.classList.remove('is-invalid');
  return true;
}

// ✅ Real-time validation untuk add event form
function validateAddEventForm() {
  const form = document.getElementById('addEventForm');
  if (!form) return true;
  
  let isValid = true;
  let errors = [];
  
  // Clear previous warnings
  clearAddEventWarning();
  
  // 1. Title validation
  const title = form.querySelector('input[name="title"]');
  if (title) {
    if (!title.value.trim()) {
      title.classList.add('is-invalid');
      errors.push('Judul event harus diisi');
      isValid = false;
    } else if (title.value.trim().length < 5) {
      title.classList.add('is-invalid');
      errors.push('Judul event minimal 5 karakter');
      isValid = false;
    } else {
      title.classList.remove('is-invalid');
    }
  }
  
  // 2. Format validation
  const format = form.querySelector('select[name="format"]');
  if (format && !format.value) {
    format.classList.add('is-invalid');
    errors.push('Format event harus dipilih');
    isValid = false;
  } else if (format) {
    format.classList.remove('is-invalid');
  }
  
  // 3. Event dates validation
  const startDate = form.querySelector('input[name="event_date"]');
  const startTime = form.querySelector('input[name="event_time"]');
  const endDate = form.querySelector('input[name="event_end_date"]');
  const endTime = form.querySelector('input[name="event_end_time"]');
  
  if (startDate && !startDate.value) {
    startDate.classList.add('is-invalid');
    errors.push('Tanggal mulai event harus diisi');
    isValid = false;
  } else if (startDate) {
    startDate.classList.remove('is-invalid');
  }
  
  if (startTime && !startTime.value) {
    startTime.classList.add('is-invalid');
    errors.push('Waktu mulai event harus diisi');
    isValid = false;
  } else if (startTime) {
    startTime.classList.remove('is-invalid');
  }
  
  if (endDate && !endDate.value) {
    endDate.classList.add('is-invalid');
    errors.push('Tanggal selesai event harus diisi');
    isValid = false;
  } else if (endDate) {
    endDate.classList.remove('is-invalid');
  }
  
  if (endTime && !endTime.value) {
    endTime.classList.add('is-invalid');
    errors.push('Waktu selesai event harus diisi');
    isValid = false;
  } else if (endTime) {
    endTime.classList.remove('is-invalid');
  }
  
  // Validate datetime logic
  if (startDate && startTime && endDate && endTime && 
      startDate.value && startTime.value && endDate.value && endTime.value) {
    const start = new Date(`${startDate.value}T${startTime.value}`);
    const end = new Date(`${endDate.value}T${endTime.value}`);
    
    if (end <= start) {
      endDate.classList.add('is-invalid');
      endTime.classList.add('is-invalid');
      errors.push('Waktu selesai harus setelah waktu mulai');
      isValid = false;
      showAddEventWarning('danger', ' Waktu selesai event harus SETELAH waktu mulai!');
    } else {
      endDate.classList.remove('is-invalid');
      endTime.classList.remove('is-invalid');
      
      // Calculate and show duration
      const durationMs = end - start;
      const durationHours = Math.floor(durationMs / (1000 * 60 * 60));
      const durationDays = Math.floor(durationHours / 24);
      const remainingHours = durationHours % 24;
      
      if (durationDays > 0) {
        showAddEventWarning('info', ` Durasi event: ${durationDays} hari ${remainingHours} jam`);
      } else {
        showAddEventWarning('info', `Durasi event: ${durationHours} jam`);
      }
    }
  }
  
  // 4. Location/Zoom validation based on format
  if (format && format.value) {
    const location = form.querySelector('input[name="location"]');
    const zoomLink = form.querySelector('input[name="zoom_link"]');
    
    if (format.value === 'offline') {
      // Offline: location required
      if (location && !location.value.trim()) {
        location.classList.add('is-invalid');
        errors.push('Lokasi offline harus diisi untuk format Offline');
        isValid = false;
      } else if (location) {
        location.classList.remove('is-invalid');
      }
    } else if (format.value === 'online') {
      // Online: zoom link required
      if (zoomLink && !zoomLink.value.trim()) {
        zoomLink.classList.add('is-invalid');
        errors.push('Link Zoom harus diisi untuk format Online');
        isValid = false;
      } else if (zoomLink && zoomLink.value.trim()) {
        if (!isValidURL(zoomLink.value)) {
          zoomLink.classList.add('is-invalid');
          errors.push('Link Zoom tidak valid (harus URL lengkap https://...)');
          isValid = false;
        } else {
          zoomLink.classList.remove('is-invalid');
        }
      }
    } else if (format.value === 'both') {
      // Hybrid: both should be filled (recommended)
      if (location && !location.value.trim()) {
        location.classList.add('is-invalid');
        errors.push('Lokasi offline direkomendasikan untuk format Hybrid');
        isValid = false;
      } else if (location) {
        location.classList.remove('is-invalid');
      }
      
      if (zoomLink && !zoomLink.value.trim()) {
        zoomLink.classList.add('is-invalid');
        errors.push('Link Zoom direkomendasikan untuk format Hybrid');
        isValid = false;
      } else if (zoomLink && zoomLink.value.trim()) {
        if (!isValidURL(zoomLink.value)) {
          zoomLink.classList.add('is-invalid');
          errors.push('Link Zoom tidak valid (harus URL lengkap https://...)');
          isValid = false;
        } else {
          zoomLink.classList.remove('is-invalid');
        }
      }
    }
  }
  
  return isValid;
}

// Show warning in add event modal
function showAddEventWarning(type, message) {
  let warningContainer = document.getElementById('addEventWarning');
  
  if (!warningContainer) {
    warningContainer = document.createElement('div');
    warningContainer.id = 'addEventWarning';
    warningContainer.className = 'mb-3';
    
    const modalBody = document.querySelector('#addEventModal .modal-body');
    if (modalBody) {
      modalBody.insertBefore(warningContainer, modalBody.firstChild);
    }
  }
  
  const alertClass = {
    'danger': 'alert-danger',
    'warning': 'alert-warning',
    'info': 'alert-info',
    'success': 'alert-success'
  }[type] || 'alert-info';
  
  const icon = {
    'danger': 'bi-exclamation-triangle-fill',
    'warning': 'bi-exclamation-circle-fill',
    'info': 'bi-info-circle-fill',
    'success': 'bi-check-circle-fill'
  }[type] || 'bi-info-circle-fill';
  
  warningContainer.innerHTML = `
    <div class="alert ${alertClass} d-flex align-items-center mb-0 py-2" role="alert">
      <i class="bi ${icon} me-2"></i>
      <div class="flex-grow-1">${message}</div>
    </div>
  `;
  warningContainer.style.display = 'block';
}

// ✅ Clear warning
function clearAddEventWarning() {
  const warningContainer = document.getElementById('addEventWarning');
  if (warningContainer) {
    warningContainer.style.display = 'none';
    warningContainer.innerHTML = '';
  }
}

// Attach real-time validation to add event form inputs
document.addEventListener('DOMContentLoaded', function() {
  const addEventForm = document.getElementById('addEventForm');
  if (addEventForm) {
    // Title validation
    const titleInput = addEventForm.querySelector('input[name="title"]');
    if (titleInput) {
      titleInput.addEventListener('blur', function() {
        validateAddEventForm();
      });
      titleInput.addEventListener('input', function() {
        if (this.value.trim().length >= 5) {
          this.classList.remove('is-invalid');
        }
      });
    }
    
    // Format validation
    const formatSelect = addEventForm.querySelector('select[name="format"]');
    if (formatSelect) {
      formatSelect.addEventListener('change', function() {
        validateAddEventForm();
      });
    }
    
    // Date/time validations
    ['event_date', 'event_time', 'event_end_date', 'event_end_time'].forEach(fieldName => {
      const input = addEventForm.querySelector(`input[name="${fieldName}"]`);
      if (input) {
        input.addEventListener('change', function() {
          validateAddEventForm();
        });
        input.addEventListener('blur', function() {
          if (!this.value) {
            this.classList.add('is-invalid');
          }
        });
        input.addEventListener('focus', function() {
          this.classList.remove('is-invalid');
        });
      }
    });
    
    // Location validation
    const locationInput = addEventForm.querySelector('input[name="location"]');
    if (locationInput) {
      locationInput.addEventListener('blur', function() {
        validateAddEventForm();
      });
      locationInput.addEventListener('focus', function() {
        this.classList.remove('is-invalid');
      });
    }
    
    // Zoom link validation
    const zoomInput = addEventForm.querySelector('input[name="zoom_link"]');
    if (zoomInput) {
      zoomInput.addEventListener('blur', function() {
        validateAddEventForm();
      });
      zoomInput.addEventListener('input', function() {
        if (isValidURL(this.value)) {
          this.classList.remove('is-invalid');
        }
      });
      zoomInput.addEventListener('focus', function() {
        this.classList.remove('is-invalid');
      });
    }
  }
});

// Validasi dan tampilkan warning untuk harga 
function validateAndShowPriceWarning(input) {
  const waveCard = input.closest('.wave-form-card');
  if (!waveCard) return;
  
  const waveNum = parseInt(waveCard.id.split('-')[1]);
  const fieldName = input.name.match(/\[([^\]]+)\]$/)?.[1];
  
  // Clear previous warning
  const existingWarning = input.parentElement.querySelector('.price-warning');
  if (existingWarning) {
    existingWarning.remove();
  }
  
  const currentPrice = parseFormattedNumber(input.value);
  
  // CEK HARGA 0 DULU!
  if (currentPrice === 0) {
    const warningHTML = `<span class="price-warning zero-price">
      <i class="bi bi-x-circle-fill"></i> 
      Harga TIDAK BOLEH 0!
    </span>`;
    input.classList.add('is-invalid');
    waveCard.classList.add('has-error');
    input.parentElement.insertAdjacentHTML('afterend', warningHTML);
    return;
  }
  
  if (currentPrice < 1000) {
    const warningHTML = `<span class="price-warning zero-price">
      <i class="bi bi-exclamation-triangle-fill"></i> 
      Harus naik dari gelombang sebelumnnya!
    </span>`;
    input.classList.add('is-invalid');
    waveCard.classList.add('has-error');
    input.parentElement.insertAdjacentHTML('afterend', warningHTML);
    return;
  }
  
  // Jika gelombang 1, skip comparison (tapi tetap cek Early Bird)
  if (waveNum === 1) {
    validateEarlyBird();
    return;
  }
  
  // Get previous wave price
  const prevWave = document.getElementById(`wave-${waveNum - 1}`);
  if (!prevWave) return;
  
  const prevInput = prevWave.querySelector(`input[name*="[${fieldName}]"]`);
  if (!prevInput) return;
  
  const prevPrice = parseFormattedNumber(prevInput.value);
  
  //  CEK HARGA GELOMBANG SEBELUMNYA JUGA HARUS > 0
  if (prevPrice === 0) {
    const warningHTML = `<span class="price-warning zero-price">
      <i class="bi bi-exclamation-triangle-fill"></i> 
      Gelombang ${waveNum-1} masih 0!
    </span>`;
    input.classList.add('is-invalid');
    waveCard.classList.add('has-error');
    input.parentElement.insertAdjacentHTML('afterend', warningHTML);
    return;
  }
  
  let warningHTML = '';
  
  if (currentPrice < prevPrice) {
    // HARGA TURUN - ERROR!
    warningHTML = `<span class="price-warning price-drop">
      <i class="bi bi-exclamation-triangle-fill"></i> 
      Harga TURUN! Gel. ${waveNum-1}: Rp ${formatNumber(prevPrice)}
    </span>`;
    input.classList.add('is-invalid');
    waveCard.classList.add('has-error');
  } else if (currentPrice === prevPrice) {
    // HARGA SAMA - WARNING!
    warningHTML = `<span class="price-warning same-price">
      <i class="bi bi-exclamation-circle-fill"></i> 
      Harga SAMA dengan Gelombang ${waveNum-1}!
    </span>`;
    input.classList.add('is-invalid');
    waveCard.classList.add('has-error');
  } else {
    // HARGA NAIK - OK!
    const diff = currentPrice - prevPrice;
    const diffPercent = ((diff / prevPrice) * 100).toFixed(1);
    warningHTML = `<span class="price-warning price-increase">
      <i class="bi bi-check-circle-fill"></i> 
      +Rp ${formatNumber(diff)} (+${diffPercent}%)
    </span>`;
    input.classList.remove('is-invalid');
  }
  
  input.parentElement.insertAdjacentHTML('afterend', warningHTML);
  checkWaveHasErrors(waveNum);
}

// Check if wave has errors
function checkWaveHasErrors(waveNum) {
  const waveCard = document.getElementById(`wave-${waveNum}`);
  if (!waveCard) return;
  
  const hasInvalid = waveCard.querySelector('.is-invalid');
  if (!hasInvalid) {
    waveCard.classList.remove('has-error');
  }
}

// Validasi Early Bird (DENGAN CEK HARGA 0)
function validateEarlyBird() {
  const wave1 = document.getElementById('wave-1');
  if (!wave1) return;
  
  const allWaves = document.querySelectorAll('.wave-form-card');
  if (allWaves.length <= 1) return;
  
  // Clear previous warnings untuk wave 1
  wave1.querySelectorAll('.price-warning').forEach(w => w.remove());
  
  const fields = ['presenter_fee_offline', 'audience_fee_offline'];
  
  fields.forEach(fieldName => {
    const wave1Input = wave1.querySelector(`input[name*="[${fieldName}]"]`);
    if (!wave1Input) return;
    
    const wave1Price = parseFormattedNumber(wave1Input.value);
    
    //  CEK HARGA 0
    if (wave1Price === 0) {
      wave1Input.classList.add('is-invalid');
      wave1Input.parentElement.insertAdjacentHTML('afterend', `
        <span class="price-warning zero-price">
          <i class="bi bi-x-circle-fill"></i> 
          Harga TIDAK BOLEH 0!
        </span>
      `);
      wave1.classList.add('has-error');
      return;
    }
    
    if (wave1Price < 1000) {
      wave1Input.classList.add('is-invalid');
      wave1Input.parentElement.insertAdjacentHTML('afterend', `
        <span class="price-warning zero-price">
          <i class="bi bi-exclamation-triangle-fill"></i> 
          Minimal Rp 50.000!
        </span>
      `);
      wave1.classList.add('has-error');
      return;
    }
    
    // Check against all other waves
    let isEarlyBird = true;
    for (let i = 2; i <= allWaves.length; i++) {
      const compareWave = document.getElementById(`wave-${i}`);
      if (!compareWave) continue;
      
      const compareInput = compareWave.querySelector(`input[name*="[${fieldName}]"]`);
      if (!compareInput) continue;
      
      const comparePrice = parseFormattedNumber(compareInput.value);
      if (comparePrice === 0) continue;
      
      if (wave1Price >= comparePrice) {
        isEarlyBird = false;
        break;
      }
    }
    
    if (!isEarlyBird) {
      wave1Input.classList.add('is-invalid');
      wave1Input.parentElement.insertAdjacentHTML('afterend', `
        <span class="price-warning price-drop">
          <i class="bi bi-exclamation-triangle-fill"></i> 
          EARLY BIRD gagal! Harus termurah
        </span>
      `);
      wave1.classList.add('has-error');
    } else {
      wave1Input.classList.remove('is-invalid');
      wave1Input.parentElement.insertAdjacentHTML('afterend', `
        <span class="price-warning price-increase">
          <i class="bi bi-star-fill"></i> Early Bird OK!
        </span>
      `);
    }
  });
  
  checkWaveHasErrors(1);
}

// Update date restrictions
function updateDateRestrictions() {
  const allWaves = document.querySelectorAll('.wave-form-card');
  
  allWaves.forEach((wave, index) => {
    const waveNum = index + 1;
    const startInput = wave.querySelector('.wave-start-date');
    const endInput = wave.querySelector('.wave-end-date');
    
    if (!startInput || !endInput) return;
    
    // Set maximum date (harus sebelum event DIMULAI - bukan selesai)
    if (currentEventDate) {
      const eventStartDate = new Date(currentEventDate);
      eventStartDate.setDate(eventStartDate.getDate() - 1); // 1 hari sebelum event dimulai
      const maxDate = eventStartDate.toISOString().split('T')[0];
      endInput.max = maxDate;
      
      // Format event date untuk display
      const eventDisplayDate = new Date(currentEventDate).toLocaleDateString('id-ID', { 
        day: 'numeric', 
        month: 'short', 
        year: 'numeric' 
      });
      
      // Add hint dengan info event multi-hari
      let hint = endInput.parentElement.querySelector('.date-disabled-hint');
      if (!hint) {
        hint = document.createElement('div');
        hint.className = 'date-disabled-hint';
        endInput.parentElement.appendChild(hint);
      }
      
      // Check if multi-day event
      const isMultiDay = currentEventDate !== currentEventEndDate;
      if (isMultiDay) {
        const eventEndDisplayDate = new Date(currentEventEndDate).toLocaleDateString('id-ID', { 
          day: 'numeric', 
          month: 'short', 
          year: 'numeric' 
        });
        hint.innerHTML = `<i class="bi bi-info-circle"></i> Maksimal ${maxDate} | Event: ${eventDisplayDate} - ${eventEndDisplayDate}`;
      } else {
        hint.innerHTML = `<i class="bi bi-info-circle"></i> Maksimal ${maxDate} | Event: ${eventDisplayDate}`;
      }
    }
    
    // Set minimum start date
    if (waveNum > 1) {
      const prevWave = document.getElementById(`wave-${waveNum - 1}`);
      if (prevWave) {
        const prevEndInput = prevWave.querySelector('.wave-end-date');
        if (prevEndInput && prevEndInput.value) {
          const prevEndDate = new Date(prevEndInput.value);
          prevEndDate.setDate(prevEndDate.getDate() + 1);
          const minDate = prevEndDate.toISOString().split('T')[0];
          startInput.min = minDate;
          
          let hint = startInput.parentElement.querySelector('.date-disabled-hint');
          if (!hint) {
            hint = document.createElement('div');
            hint.className = 'date-disabled-hint';
            startInput.parentElement.appendChild(hint);
          }
          hint.innerHTML = `<i class="bi bi-info-circle"></i> Minimal ${minDate} (setelah Gel. ${waveNum-1} selesai)`;
        }
      }
    } else {
      const today = new Date().toISOString().split('T')[0];
      startInput.min = today;
      
      let hint = startInput.parentElement.querySelector('.date-disabled-hint');
      if (!hint) {
        hint = document.createElement('div');
        hint.className = 'date-disabled-hint';
        startInput.parentElement.appendChild(hint);
      }
      hint.innerHTML = `<i class="bi bi-info-circle"></i> Minimal hari ini`;
    }
    
    if (startInput.value) {
      endInput.min = startInput.value;
      if (endInput.value && endInput.value < startInput.value) {
        endInput.classList.add('is-invalid');
      } else {
        endInput.classList.remove('is-invalid');
      }
    }
  });
}

// ===== EVENT FORMAT HANDLER =====
document.getElementById('eventFormat')?.addEventListener('change', function() {
  const format = this.value;
  const locationRow = document.getElementById('locationRow');
  const zoomRow = document.getElementById('zoomRow');
  const locationInput = document.getElementById('locationInput');
  const zoomInput = document.getElementById('zoomInput');
  const locationRequired = document.getElementById('locationRequired');
  const zoomRequired = document.getElementById('zoomRequired');

  if (!locationRow || !zoomRow || !locationInput || !zoomInput) return;

  if (format === 'online') {
    locationRow.classList.remove('show');
    zoomRow.classList.add('show');
    locationInput.removeAttribute('required');
    zoomInput.setAttribute('required', 'required');
    if (locationRequired) locationRequired.style.display = 'none';
    if (zoomRequired) zoomRequired.style.display = 'inline';
  } else if (format === 'offline') {
    locationRow.classList.add('show');
    zoomRow.classList.remove('show');
    locationInput.setAttribute('required', 'required');
    zoomInput.removeAttribute('required');
    if (locationRequired) locationRequired.style.display = 'inline';
    if (zoomRequired) zoomRequired.style.display = 'none';
  } else {
    locationRow.classList.add('show');
    zoomRow.classList.add('show');
    locationInput.removeAttribute('required');
    zoomInput.removeAttribute('required');
    if (locationRequired) locationRequired.style.display = 'none';
    if (zoomRequired) zoomRequired.style.display = 'none';
  }
});

// Auto-fill end date
document.getElementById('eventStartDate')?.addEventListener('change', function() {
  const endDateInput = document.getElementById('eventEndDate');
  endDateInput.min = this.value;
  if (!endDateInput.value || endDateInput.value < this.value) {
    endDateInput.value = this.value;
  }
  validateAddEventForm();
});

// ===== ADD EVENT FORM SUBMIT =====
document.getElementById('addEventForm')?.addEventListener('submit', async function(e) {
  e.preventDefault();
  
  clearAddEventWarning();
  
  if (!validateAddEventForm()) {
    const invalidFields = this.querySelectorAll('.is-invalid');
    const fieldNames = [];
    
    invalidFields.forEach(field => {
      const label = field.closest('.mb-3')?.querySelector('label');
      if (label) {
        const labelText = label.textContent.replace('*', '').trim();
        if (!fieldNames.includes(labelText)) {
          fieldNames.push(labelText);
        }
      }
    });
    
    Swal.fire({
      icon: 'error',
      title: 'Form Tidak Lengkap!',
      html: `<p class="mb-2"><strong>Mohon lengkapi field berikut:</strong></p>
             <ul class="text-start mb-0">
               ${fieldNames.map(name => `<li>${name}</li>`).join('')}
             </ul>`,
      confirmButtonText: 'OK, Saya Lengkapi',
      width: '500px'
    });
    return;
  }
  
  const formData = new FormData(this);
  const btn = this.querySelector('button[type="submit"]');
  const originalText = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';

  try {
    const data = await fetchJSON('<?= base_url("admin/event/create") ?>', {
      method: 'POST',
      body: formData,
      headers: {'X-Requested-With': 'XMLHttpRequest','X-CSRF-TOKEN': getCsrfToken()}
    });

    if (data.success) {
      Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: data.message,
        timer: 1500,
        showConfirmButton: false
      }).then(() => location.reload());
    } else {
      throw new Error(data.message || 'Gagal menyimpan event');
    }
  } catch (error) {
    Swal.fire('Error!', error.message, 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = originalText;
  }
});

// ===== EDIT WAVES =====
function editWaves(eventId) {
  fetchJSON('<?= base_url("admin/event/get") ?>/' + eventId, {
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
  .then(data => {
    if (!data.success) throw new Error(data.message);
    
    currentEventId = eventId;
    currentEventDate = data.event.event_date;
    currentEventEndDate = data.event.event_end_date || data.event.event_date;
    
    let waves = data.event.registration_waves || [];
    if (waves.length === 0) {
      waves.push({
        registration_start: '',
        registration_deadline: '',
        presenter_fee_online: 0,
        presenter_fee_offline: 0,
        audience_fee_online: 0,
        audience_fee_offline: 0
      });
    }
    renderAllWaves(waves);
    
    const form = document.getElementById('editWavesForm');
    if (form) {
      form.dataset.eventId = eventId;
      form.dataset.eventDate = currentEventDate;
      form.dataset.eventEndDate = currentEventEndDate;
      const modal = new bootstrap.Modal(document.getElementById('editWavesModal'));
      modal.show();
    }
  })
  .catch(err => Swal.fire('Error!', err.message, 'error'));
}

function renderAllWaves(waves) {
  const container = document.getElementById('wavesContainer');
  let html = '';
  waves.forEach((wave, index) => {
    html += generateWaveForm(index + 1, wave);
  });
  container.innerHTML = html;
  
  setTimeout(() => {
    document.querySelectorAll('.price-input').forEach(input => {
      input.addEventListener('input', function() {
        formatCurrencyInput(this);
      });
      
      input.addEventListener('blur', function() {
        if (validatePriceInput(this)) {
          validateAndShowPriceWarning(this);
        }
      });
      
      input.addEventListener('focus', function() {
        this.classList.remove('is-invalid');
      });
    });
    
    document.querySelectorAll('.wave-start-date, .wave-end-date').forEach(input => {
      input.addEventListener('change', function() {
        updateDateRestrictions();
      });
    });
    
    updateDateRestrictions();
  }, 100);
}

function addWaveRow() {
  const container = document.getElementById('wavesContainer');
  const waveCount = container.querySelectorAll('.wave-form-card').length + 1;
  const newWaveHTML = generateWaveForm(waveCount, {
    registration_start: '',
    registration_deadline: '',
    presenter_fee_online: 0,
    presenter_fee_offline: 0,
    audience_fee_online: 0,
    audience_fee_offline: 0
  });
  container.insertAdjacentHTML('beforeend', newWaveHTML);
  
  const newWave = container.querySelector('#wave-' + waveCount);
  newWave.querySelectorAll('.price-input').forEach(input => {
    input.addEventListener('input', function() {
      formatCurrencyInput(this);
    });
    input.addEventListener('blur', function() {
      if (validatePriceInput(this)) {
        validateAndShowPriceWarning(this);
      }
    });
    input.addEventListener('focus', function() {
      this.classList.remove('is-invalid');
    });
  });
  
  newWave.querySelectorAll('.wave-start-date, .wave-end-date').forEach(input => {
    input.addEventListener('change', function() {
      updateDateRestrictions();
    });
  });
  
  updateDateRestrictions();
  
  Swal.fire({icon: 'success',title: 'Gelombang ditambahkan!',text: 'Gelombang ' + waveCount + ' berhasil ditambahkan',timer: 1000,showConfirmButton: false});
}

function removeWave(waveNumber) {
  const container = document.getElementById('wavesContainer');
  const waveCount = container.querySelectorAll('.wave-form-card').length;
  if (waveCount <= 1) {
    return Swal.fire('Error!', 'Minimal harus ada 1 gelombang', 'error');
  }
  Swal.fire({
    title: 'Hapus Gelombang ' + waveNumber + '?',
    text: 'Data gelombang ini akan dihapus',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, Hapus',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#ef4444'
  }).then((result) => {
    if (result.isConfirmed) {
      document.getElementById('wave-' + waveNumber).remove();
      renumberWaves();
      updateDateRestrictions();
      Swal.fire({icon: 'success',title: 'Terhapus!',text: 'Gelombang berhasil dihapus',timer: 1000,showConfirmButton: false});
    }
  });
}

function renumberWaves() {
  const waveSections = document.querySelectorAll('.wave-form-card');
  waveSections.forEach((section, index) => {
    const newNumber = index + 1;
    section.id = 'wave-' + newNumber;
    section.querySelector('h6').innerHTML = `<i class="bi bi-calendar-week me-2"></i>Gelombang ${newNumber} ${newNumber === 1 ? '<span class="badge bg-warning ms-2"><i class="bi bi-star-fill"></i> Early Bird</span>' : ''}`;
    section.querySelectorAll('input').forEach(input => {
      const name = input.name.replace(/\[\d+\]/, '[' + index + ']');
      input.name = name;
    });
    const removeBtn = section.querySelector('[onclick^="removeWave"]');
    if (removeBtn) {
      removeBtn.setAttribute('onclick', 'removeWave(' + newNumber + ')');
    }
  });
}

function generateWaveForm(waveNum, data = {}) {
  const startDate = data.registration_start ? data.registration_start.split(' ')[0] : '';
  const endDate = data.registration_deadline ? data.registration_deadline.split(' ')[0] : '';
  
  const today = new Date().toISOString().split('T')[0];
  
  const presOnline = formatNumber(data.presenter_fee_online || 0);
  const presOffline = formatNumber(data.presenter_fee_offline || 0);
  const audOnline = formatNumber(data.audience_fee_online || 0);
  const audOffline = formatNumber(data.audience_fee_offline || 0);
  
  return `
  <div class="wave-form-card ${waveNum === 1 ? 'border-success' : ''}" id="wave-${waveNum}">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h6 class="mb-0">
        <i class="bi bi-calendar-week me-2"></i>Gelombang ${waveNum} 
        ${waveNum === 1 ? '<span class="badge bg-warning ms-2"><i class="bi bi-star-fill"></i> Early Bird</span>' : ''}
      </h6>
      ${waveNum > 1 ? `<button type="button" class="btn btn-sm btn-danger" onclick="removeWave(${waveNum})"><i class="bi bi-trash me-1"></i>Hapus</button>` : '<span class="badge bg-primary">Gelombang Utama</span>'}
    </div>
    <div class="row mb-3">
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-calendar-check"></i> Tanggal Mulai <span class="text-danger">*</span></label>
        <input type="date" class="form-control wave-start-date" 
               name="waves[${waveNum-1}][registration_start]" 
               value="${startDate}" 
               min="${today}" 
               required>
      </div>
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-calendar-x"></i> Tanggal Deadline <span class="text-danger">*</span></label>
        <input type="date" class="form-control wave-end-date" 
               name="waves[${waveNum-1}][registration_deadline]" 
               value="${endDate}" 
               min="${startDate || today}" 
               required>
      </div>
    </div>
    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label"><i class="bi bi-person-video3 me-1 text-info"></i>Presenter Online (Rp) <span class="text-danger">*</span></label>
        <div class="input-group">
          <span class="input-group-text">Rp</span>
          <input type="text" 
                 class="form-control price-input" 
                 name="waves[${waveNum-1}][presenter_fee_online]" 
                 value="${presOnline}" 
                 placeholder="0" 
                 inputmode="numeric"
                 required>
        </div>
        <div class="invalid-feedback">Harga harus diisi dan tidak boleh 0 </div>
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label">
          <i class="bi bi-person-badge me-1 text-warning"></i>Presenter Offline (Rp) <span class="text-danger">*</span>
          ${waveNum === 1 ? '<span class="badge bg-warning ms-1"><i class="bi bi-star-fill"></i> Early Bird</span>' : ''}
        </label>
        <div class="input-group">
          <span class="input-group-text">Rp</span>
          <input type="text" 
                 class="form-control price-input" 
                 name="waves[${waveNum-1}][presenter_fee_offline]" 
                 value="${presOffline}" 
                 placeholder="0" 
                 inputmode="numeric"
                 required>
        </div>
        <div class="invalid-feedback">Harga harus diisi dan tidak boleh 0 </div>
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label"><i class="bi bi-laptop me-1 text-primary"></i>Audience Online (Rp) <span class="text-danger">*</span></label>
        <div class="input-group">
          <span class="input-group-text">Rp</span>
          <input type="text" 
                 class="form-control price-input" 
                 name="waves[${waveNum-1}][audience_fee_online]" 
                 value="${audOnline}" 
                 placeholder="0" 
                 inputmode="numeric"
                 required>
        </div>
        <div class="invalid-feedback">Harga harus diisi dan tidak boleh 0 </div>
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label">
          <i class="bi bi-people me-1 text-success"></i>Audience Offline (Rp) <span class="text-danger">*</span>
          ${waveNum === 1 ? '<span class="badge bg-warning ms-1"><i class="bi bi-star-fill"></i> Early Bird</span>' : ''}
        </label>
        <div class="input-group">
          <span class="input-group-text">Rp</span>
          <input type="text" 
                 class="form-control price-input" 
                 name="waves[${waveNum-1}][audience_fee_offline]" 
                 value="${audOffline}" 
                 placeholder="0" 
                 inputmode="numeric"
                 required>
        </div>
        <div class="invalid-feedback">Harga harus diisi dan tidak boleh 0 </div>
      </div>
    </div>
  </div>`;
}

// ===== SAVE WAVES (DENGAN VALIDASI HARGA TIDAK BOLEH 0) =====
document.getElementById('editWavesForm')?.addEventListener('submit', function(e) {
  e.preventDefault();
  saveWaves();
});

function saveWaves() {
  const form = document.getElementById('editWavesForm');
  const eventId = form.dataset.eventId;
  const waveSections = document.querySelectorAll('.wave-form-card');
  
  if (waveSections.length === 0) {
    return Swal.fire('Error!', 'Minimal harus ada 1 gelombang', 'error');
  }

  const hasErrorWaves = document.querySelectorAll('.wave-form-card.has-error');
  if (hasErrorWaves.length > 0) {
    return Swal.fire({
      icon: 'error',
      title: 'Validasi Gagal!',
      html: 'Ada gelombang dengan harga yang tidak valid.<br>Pastikan:<br>• <strong>Harga TIDAK BOLEH 0 </strong><br>• Harga gelombang 2+ HARUS LEBIH TINGGI dari gelombang sebelumnya<br>• Gelombang 1 offline harus termurah (Early Bird)',
      confirmButtonText: 'Perbaiki'
    });
  }

  let hasError = false;
  let hasZeroPrice = false;
  
  document.querySelectorAll('.price-input').forEach(input => {
    if (!validatePriceInput(input)) {
      hasError = true;
      const num = parseFormattedNumber(input.value);
      if (num === 0) {
        hasZeroPrice = true;
      }
    }
  });

  if (hasError) {
    if (hasZeroPrice) {
      return Swal.fire({
        icon: 'error',
        title: 'Harga Tidak Boleh 0!',
        html: '<p class="mb-2">Ada harga yang bernilai 0 atau tidak diisi.</p><p class="text-danger mb-0"><strong>Semua Harga HARUS Diisi Naik Dari Gelombang Sebelumnnya</strong></p>',
        confirmButtonText: 'Perbaiki'
      });
    } else {
      return Swal.fire('Error!', 'Ada input harga yang tidak valid. Pastikan semua harga diisi dengan angka yang benar', 'error');
    }
  }

  const waves = [];
  waveSections.forEach((section, index) => {
    const inputs = section.querySelectorAll('input');
    const wave = {
      registration_start: '',
      registration_deadline: '',
      presenter_fee_online: 0,
      presenter_fee_offline: 0,
      audience_fee_online: 0,
      audience_fee_offline: 0
    };
    
    inputs.forEach(input => {
      const name = input.name.match(/\[([^\]]+)\]$/)?.[1];
      if (name) {
        if (name.includes('fee')) {
          wave[name] = parseFormattedNumber(input.value);
        } else {
          wave[name] = input.value;
        }
      }
    });
    
    if (wave.registration_start) wave.registration_start = wave.registration_start + ' 00:00:00';
    if (wave.registration_deadline) wave.registration_deadline = wave.registration_deadline + ' 23:59:59';
    waves.push(wave);
  });

  //  VALIDASI: CEK SEMUA HARGA TIDAK BOLEH 0
  for (let i = 0; i < waves.length; i++) {
    const wave = waves[i];
    const waveNum = i + 1;
    
    if (!wave.registration_start || !wave.registration_deadline) {
      return Swal.fire('Error!', `Gelombang ${waveNum}: Tanggal harus diisi`, 'error');
    }
    
    const start = new Date(wave.registration_start).getTime();
    const end = new Date(wave.registration_deadline).getTime();
    if (end <= start) {
      return Swal.fire('Error!', `Gelombang ${waveNum}: Deadline harus setelah tanggal mulai`, 'error');
    }
    
    //  CEK HARGA TIDAK BOLEH 0
    const fields = ['presenter_fee_online', 'presenter_fee_offline', 'audience_fee_online', 'audience_fee_offline'];
    for (const field of fields) {
      if (wave[field] === 0 || wave[field] < 1000) {
        const fieldLabel = field.replace(/_/g, ' ').replace('fee', '').trim();
        return Swal.fire({
          icon: 'error',
          title: 'Harga Tidak Boleh 0!',
          html: `<p class="mb-2">Gelombang ${waveNum}: <strong>${fieldLabel}</strong> tidak boleh 0!</p><p class="text-danger mb-0">Semua harga HARUS diisi dengan nilai minimal Rp 1.000</p>`,
          confirmButtonText: 'Perbaiki'
        });
      }
    }
    
    if (i > 0) {
      const prevEnd = new Date(waves[i-1].registration_deadline).getTime();
      if (start <= prevEnd) {
        return Swal.fire('Error!', `Gelombang ${waveNum} harus dimulai setelah gelombang ${i} selesai`, 'error');
      }
      
      for (const field of fields) {
        if (wave[field] <= waves[i-1][field]) {
          const fieldLabel = field.replace(/_/g, ' ').replace('fee', '').trim();
          return Swal.fire('Error!', `Gelombang ${waveNum}: ${fieldLabel} harus LEBIH TINGGI dari gelombang ${i}`, 'error');
        }
      }
    }
    
    if (waveNum === 1 && waves.length > 1) {
      const wave1PresOff = waves[0].presenter_fee_offline;
      const wave1AudOff = waves[0].audience_fee_offline;
      
      for (let j = 1; j < waves.length; j++) {
        const comparePresOff = waves[j].presenter_fee_offline;
        const compareAudOff = waves[j].audience_fee_offline;
        
        if (wave1PresOff >= comparePresOff) {
          return Swal.fire('Error!', `Early Bird: Gelombang 1 presenter offline harus < gelombang ${j+1}`, 'error');
        }
        if (wave1AudOff >= compareAudOff) {
          return Swal.fire('Error!', `Early Bird: Gelombang 1 audience offline harus < gelombang ${j+1}`, 'error');
        }
      }
    }
  }

  const btn = form.querySelector('button[type="submit"]');
  const originalText = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';

  fetchJSON('<?= base_url("admin/event/update-waves") ?>/' + eventId, {
    method: 'POST',
    body: JSON.stringify({ waves: waves }),
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': getCsrfToken(),
      'X-Requested-With': 'XMLHttpRequest'
    }
  })
  .then(data => {
    if (data.success) {
      Swal.fire({ icon: 'success', title: 'Berhasil!', text: data.message, timer: 1500, showConfirmButton: false}).then(() => location.reload());
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

// ===== EDIT EVENT =====
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
        <select class="form-select" name="format" required>
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
    ${format !== 'online' ? `<div class="mb-3"><label class="form-label">Lokasi ${format === 'offline' ? '<span class="text-danger">*</span>' : ''}</label><input type="text" class="form-control" name="location" value="${event.location || ''}" ${format === 'offline' ? 'required' : ''}></div>` : ''}
    ${format !== 'offline' ? `<div class="mb-3"><label class="form-label">Link Zoom ${format === 'online' ? '<span class="text-danger">*</span>' : ''}</label><input type="url" class="form-control" name="zoom_link" value="${event.zoom_link || ''}" ${format === 'online' ? 'required' : ''}></div>` : ''}
  `;
  document.getElementById('editFormContent').innerHTML = html;
}

document.getElementById('editEventForm')?.addEventListener('submit', async function(e) {
  e.preventDefault();
  const eventId = this.dataset.eventId;
  const formData = new FormData(this);
  const btn = this.querySelector('button[type="submit"]');
  const originalText = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';

  try {
    const data = await fetchJSON('<?= base_url("admin/event/update") ?>/' + eventId, {
      method: 'POST',
      body: formData,
      headers: {'X-Requested-With': 'XMLHttpRequest','X-CSRF-TOKEN': getCsrfToken()}
    });
    if (data.success) {
      Swal.fire({icon: 'success',title: 'Berhasil!',text: data.message,timer: 1500,showConfirmButton: false}).then(() => location.reload());
    } else {
      throw new Error(data.message || 'Gagal update event');
    }
  } catch (error) {
    Swal.fire('Error!', error.message, 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = originalText;
  }
});

// ===== TOGGLE STATUS & DELETE =====
function toggleStatus(id) {
  fetchJSON('<?= base_url("admin/event/toggle-status") ?>/' + id, {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': getCsrfToken(), 'X-Requested-With': 'XMLHttpRequest' }
  })
  .then(d => {
    if (!d.success) throw new Error(d.message);
    Swal.fire({ icon: 'success', title: 'Sukses', text: d.message, timer: 1000, showConfirmButton: false}).then(() => location.reload());
  })
  .catch(err => Swal.fire('Error!', err.message, 'error'));
}

function forceDeleteEvent(id) {
  Swal.fire({
    title: 'Hapus Events?',
    html: '<p class="mb-2"><strong>⚠️ PERHATIAN:</strong> Apakah anda yakin!</p><p class="text-danger small mb-0">Termasuk seluruh peserta (verified & pending) dan data pembayaran</p>',
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
      headers: { 'X-CSRF-TOKEN': getCsrfToken(), 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(d => {
      if (!d.success) throw new Error(d.message);
      Swal.fire({ icon: 'success', title: 'Terhapus', text: d.message, timer: 1500, showConfirmButton: false}).then(() => location.reload());
    })
    .catch(err => Swal.fire('Error!', err.message, 'error'));
  });
}
</script>