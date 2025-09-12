<?php
$title  = $title ?? 'Kelola Event';
$stats  = $stats ?? [
  'total_events'=>0,'active_events'=>0,'verified_registrations'=>0,'total_revenue'=>0,
];
$events = $events ?? [];
helper(['number','csrf']);
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_admin') ?>
<?= $this->include('partials/alerts') ?>

<meta name="csrf-token" content="<?= csrf_hash() ?>"/>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">
      <div class="header-section header-blue d-flex justify-content-between align-items-center mb-3">
        <div>
          <h3 class="welcome-text mb-1"><i class="bi bi-calendar3 me-2"></i>Kelola Event</h3>
          <div class="text-muted">Manajemen event & aktivitas SNIA Conference</div>
        </div>
        <button class="btn btn-light btn-sm text-primary" data-bs-toggle="modal" data-bs-target="#addEventModal">
          <i class="bi bi-plus-lg me-1"></i>Tambah Event
        </button>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-primary"><i class="bi bi-calendar-event"></i></div>
              <div class="ms-3">
                <div class="stat-number"><?= number_format((int)$stats['total_events']) ?></div>
                <div class="text-muted">Total Event</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-success"><i class="bi bi-check2-circle"></i></div>
              <div class="ms-3">
                <div class="stat-number"><?= number_format((int)$stats['active_events']) ?></div>
                <div class="text-muted">Event Aktif</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-info"><i class="bi bi-people"></i></div>
              <div class="ms-3">
                <div class="stat-number"><?= number_format((int)$stats['verified_registrations']) ?></div>
                <div class="text-muted">Total Pendaftar</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-warning"><i class="bi bi-currency-dollar"></i></div>
              <div class="ms-3">
                <div class="stat-number" style="font-size:1.5rem">Rp <?= number_format((float)$stats['total_revenue'], 0, ',', '.') ?></div>
                <div class="text-muted">Revenue</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- EVENTS -->
      <div class="row">
        <?php if (!empty($events)): foreach ($events as $event):
          $id=(int)$event['id']; $fmt=strtolower($event['format'] ?? 'both');
          $isOn=!empty($event['is_active']); $reg=!empty($event['registration_active']);
        ?>
        <div class="col-lg-6 mb-3">
          <div class="event-card shadow-sm">
            <div class="event-header">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <h5 class="mb-0 text-primary"><?= esc($event['title']) ?></h5>
                <div class="dropdown">
                  <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-three-dots-vertical"></i>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#" onclick="editEvent(<?= $id ?>)"><i class="bi bi-pencil-square me-2"></i>Edit</a></li>
                    <li><a class="dropdown-item" href="#" onclick="toggleStatus(<?= $id ?>)"><i class="bi bi-power me-2"></i><?= $isOn?'Nonaktifkan':'Aktifkan' ?></a></li>
                    <li><a class="dropdown-item" href="#" onclick="toggleRegistration(<?= $id ?>)"><i class="bi bi-person-plus me-2"></i>Reg: <?= $reg?'Tutup':'Buka' ?></a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="#" onclick="deleteEvent(<?= $id ?>)"><i class="bi bi-trash me-2"></i>Hapus</a></li>
                  </ul>
                </div>
              </div>

              <div class="d-flex gap-2 mb-2">
                <span class="format-badge <?= $fmt==='online'?'bg-info text-white':($fmt==='offline'?'bg-primary text-white':'bg-success text-white') ?>">
                  <?= ucfirst($fmt==='both'?'Hybrid':$fmt) ?>
                </span>
                <span class="badge <?= $isOn?'bg-success':'bg-secondary' ?>"><?= $isOn?'Aktif':'Nonaktif' ?></span>
                <span class="badge <?= $reg?'bg-primary':'bg-secondary' ?>"><?= $reg?'Registrasi Buka':'Registrasi Tutup' ?></span>
              </div>

              <div class="text-muted small">
                <i class="bi bi-calendar-event me-1"></i><?= date('d M Y', strtotime($event['event_date'])) ?>
                <i class="bi bi-dot"></i>
                <i class="bi bi-clock me-1"></i><?= date('H:i', strtotime($event['event_time'])) ?>
              </div>
            </div>

            <div class="event-body">
              <div class="price-display">
                <h6 class="mb-2"><i class="bi bi-tag me-2"></i>Harga</h6>
                <div class="row g-2 small">
                  <div class="col-6"><strong>Presenter:</strong><br>Rp <?= number_format((float)$event['presenter_fee_offline'],0,',','.') ?></div>
                  <div class="col-6"><strong>Audience:</strong><br>
                    <?php if ($fmt!=='offline'): ?>Online: Rp <?= number_format((float)($event['audience_fee_online'] ?? 0),0,',','.') ?><br><?php endif; ?>
                    <?php if ($fmt!=='online'): ?>Offline: Rp <?= number_format((float)($event['audience_fee_offline'] ?? 0),0,',','.') ?><?php endif; ?>
                  </div>
                </div>
              </div>

              <div class="row g-2 mb-3">
                <div class="col-3 text-center"><div class="fw-bold text-primary"><?= (int)($event['total_registrations'] ?? 0) ?></div><small class="text-muted">Total</small></div>
                <div class="col-3 text-center"><div class="fw-bold text-success"><?= (int)($event['verified_registrations'] ?? 0) ?></div><small class="text-muted">Verified</small></div>
                <div class="col-3 text-center"><div class="fw-bold text-info"><?= (int)($event['total_abstracts'] ?? 0) ?></div><small class="text-muted">Abstrak</small></div>
                <div class="col-3 text-center"><div class="fw-bold text-warning"><?= (int)($event['present_count'] ?? 0) ?></div><small class="text-muted">Hadir</small></div>
              </div>

              <div class="revenue-display"><i class="bi bi-cash-coin me-2"></i><strong>Revenue: Rp <?= number_format((float)($event['total_revenue'] ?? 0),0,',','.') ?></strong></div>
            </div>
          </div>
        </div>
        <?php endforeach; else: ?>
          <div class="col-12">
            <div class="text-center py-5 text-muted border rounded-3 bg-light-subtle">
              <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
              <h5>Belum ada event</h5>
              <div>Klik "Tambah Event" untuk membuat event baru</div>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>

<!-- ADD MODAL -->
<div class="modal fade" id="addEventModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl"><div class="modal-content">
    <div class="modal-header bg-primary text-white">
      <h5 class="modal-title"><i class="bi bi-plus-lg me-2"></i>Tambah Event Baru</h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <form id="addEventForm">
      <?= csrf_field() ?>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-8 mb-3">
            <label class="form-label">Judul Event <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="title" required>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">Format <span class="text-danger">*</span></label>
            <select class="form-select" name="format" id="eventFormat" required>
              <option value="both">Hybrid</option>
              <option value="online">Online</option>
              <option value="offline">Offline</option>
            </select>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Deskripsi</label>
          <textarea class="form-control" name="description" rows="3"></textarea>
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Tanggal Event <span class="text-danger">*</span></label>
            <input type="date" class="form-control" name="event_date" required>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Waktu Event <span class="text-danger">*</span></label>
            <input type="time" class="form-control" name="event_time" required>
          </div>
        </div>

        <!-- Conditional -->
        <div class="conditional-field" id="locationRow">
          <div class="mb-3">
            <label class="form-label">Lokasi <span class="text-danger" id="locationRequired" style="display:none">*</span></label>
            <input type="text" class="form-control" name="location" id="locationInput">
          </div>
        </div>

        <div class="conditional-field" id="zoomRow">
          <div class="mb-3">
            <label class="form-label">Link Zoom <span class="text-danger" id="zoomRequired" style="display:none">*</span></label>
            <input type="url" class="form-control" name="zoom_link" id="zoomInput" placeholder="https://...">
          </div>
        </div>

        <!-- Pricing -->
        <h6 class="border-bottom pb-2 mb-3">Struktur Harga</h6>
        <div class="row">
          <div class="col-md-4 mb-3" id="presenterPrice">
            <label class="form-label">Presenter (Offline) <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text">Rp</span>
              <input type="text" class="form-control currency" name="presenter_fee_offline" value="0" required>
            </div>
            <small class="text-muted">Presenter hanya bisa offline</small>
          </div>
          <div class="col-md-4 mb-3" id="audienceOnlinePrice">
            <label class="form-label">Audience (Online) <span class="text-danger" id="onlineReqStar">*</span></label>
            <div class="input-group">
              <span class="input-group-text">Rp</span>
              <input type="text" class="form-control currency" name="audience_fee_online" value="0" required>
            </div>
          </div>
          <div class="col-md-4 mb-3" id="audienceOfflinePrice">
            <label class="form-label">Audience (Offline) <span class="text-danger" id="offlineReqStar">*</span></label>
            <div class="input-group">
              <span class="input-group-text">Rp</span>
              <input type="text" class="form-control currency" name="audience_fee_offline" value="0" required>
            </div>
          </div>
        </div>

        <!-- Additional -->
        <h6 class="border-bottom pb-2 mb-3">Pengaturan Tambahan</h6>
        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label">Maksimal Peserta</label>
            <input type="number" class="form-control" name="max_participants" value="0">
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">Batas Pendaftaran</label>
            <input type="datetime-local" class="form-control" name="registration_deadline">
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">Batas Submit Abstrak</label>
            <input type="datetime-local" class="form-control" name="abstract_deadline">
          </div>
        </div>

        <!-- Toggles -->
        <h6 class="border-bottom pb-2 mb-3">Status Event</h6>
        <div class="row">
          <div class="col-md-4">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="is_active" checked>
              <label class="form-check-label">Event Aktif</label>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="registration_active" checked>
              <label class="form-check-label">Pendaftaran Aktif</label>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="abstract_submission_active" checked>
              <label class="form-check-label">Submit Abstrak Aktif</label>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan Event</button>
      </div>
    </form>
  </div></div>
</div>

<!-- EDIT MODAL -->
<div class="modal fade" id="editEventModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl"><div class="modal-content">
    <div class="modal-header bg-primary text-white">
      <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Event</h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <form id="editEventForm" data-event-id="">
      <?= csrf_field() ?>
      <div class="modal-body" id="editFormContent"><!-- loaded dynamically --></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Update Event</button>
      </div>
    </form>
  </div></div>
</div>

<?= $this->include('partials/footer') ?>

<style>
  :root{ --primary-color:#2563eb; --success-color:#10b981; --warning-color:#f59e0b; --danger-color:#ef4444; --info-color:#06b6d4; }
  body{ background:linear-gradient(135deg,#f8fafc 0%,#e2e8f0 100%); }
  .header-section.header-blue{ background:linear-gradient(135deg,var(--primary-color) 0%,#1e40af 100%); color:#fff; padding:28px 24px; border-radius:16px; box-shadow:0 8px 28px rgba(0,0,0,.12); }
  .header-section.header-blue .welcome-text{ color:#fff; font-weight:800; font-size:2rem; }
  .header-section.header-blue .text-muted, .header-section.header-blue strong{ color:rgba(255,255,255,.95)!important; }
  .stat-card{ background:#fff; border-radius:14px; padding:20px; box-shadow:0 8px 28px rgba(0,0,0,.08); border-left:4px solid #e9ecef; position:relative; overflow:hidden; }
  .stat-card:before{ content:''; position:absolute; left:0; top:0; height:4px; width:100%; background:linear-gradient(90deg,var(--primary-color),var(--info-color)); }
  .stat-icon{ width:56px; height:56px; border-radius:12px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:22px; }
  .stat-number{ font-size:2rem; font-weight:800; color:#1e293b; line-height:1; }
  .event-card{ background:#fff; border-radius:12px; border:1px solid #e2e8f0; overflow:hidden; }
  .event-header{ padding:16px 20px; border-bottom:1px solid #f1f5f9; }
  .event-body{ padding:16px 20px; }
  .format-badge{ padding:4px 12px; border-radius:8px; font-size:.75rem; font-weight:600; }
  .price-display{ background:#f8fafc; border-radius:8px; padding:12px; margin:12px 0; }
  .revenue-display{ background:var(--success-color); color:#fff; padding:12px; border-radius:8px; text-align:center; }
  .conditional-field{ display:none; } .conditional-field.show{ display:block; }
  .disabled-field{ opacity:.5; pointer-events:none; }
  #content main>.container-fluid{ margin-top:.25rem; }
</style>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '<?= csrf_hash() ?>';

/* ===== Helper: fetch JSON guard ===== */
async function fetchJSON(url, options = {}) {
  const res = await fetch(url, options);
  const ct = res.headers.get('content-type') || '';
  if (!ct.includes('application/json')) {
    const text = await res.text();
    throw new Error('Server mengirim respons non-JSON. Cek sesi/CSRF.'); // hindari Unexpected token '<'
  }
  return res.json();
}

/* ===== Rupiah formatting while typing ===== */
function formatNumberID(raw){
  const digits = (raw||'').toString().replace(/\D/g,'');
  if(!digits) return '';
  return new Intl.NumberFormat('id-ID').format(parseInt(digits,10));
}
function attachCurrency(el){
  el.value = formatNumberID(el.value || '0');
  el.addEventListener('input', () => {
    const start = el.selectionStart, oldLen = el.value.length;
    el.value = formatNumberID(el.value);
    const diff = el.value.length - oldLen;
    requestAnimationFrame(()=> el.setSelectionRange(start+diff, start+diff));
  });
  el.addEventListener('blur', () => el.value = el.value ? formatNumberID(el.value) : '0');
}
function initCurrencyInputs(root=document){
  root.querySelectorAll('input.currency').forEach(attachCurrency);
}
function normalizeCurrencyFields(fd){
  ['presenter_fee_offline','audience_fee_online','audience_fee_offline'].forEach(name=>{
    if(fd.has(name)) fd.set(name, (fd.get(name)||'').toString().replace(/\D/g,'') || '0');
  });
}

/* ===== UI handlers ===== */
document.addEventListener('DOMContentLoaded', function(){
  initCurrencyInputs(document);
  setupFormHandlers();
  handleFormatChange();
});

function setupFormHandlers(){
  document.getElementById('addEventForm').addEventListener('submit', function(e){
    e.preventDefault();
    submitForm(this, '<?= base_url("admin/event/store") ?>');
  });

  document.getElementById('editEventForm').addEventListener('submit', function(e){
    e.preventDefault();
    const id = this.dataset.eventId;
    submitForm(this, `<?= base_url("admin/event/update") ?>/${id}`);
  });

  document.getElementById('eventFormat').addEventListener('change', handleFormatChange);
}

function handleFormatChange(){
  const format = document.getElementById('eventFormat').value;

  const locationRow = document.getElementById('locationRow');
  const zoomRow     = document.getElementById('zoomRow');
  const locInput    = document.getElementById('locationInput');
  const zoomInput   = document.getElementById('zoomInput');
  const locStar     = document.getElementById('locationRequired');
  const zoomStar    = document.getElementById('zoomRequired');

  const onlineWrap   = document.getElementById('audienceOnlinePrice');
  const offlineWrap  = document.getElementById('audienceOfflinePrice');
  const onlineInput  = onlineWrap.querySelector('input[name="audience_fee_online"]');
  const offlineInput = offlineWrap.querySelector('input[name="audience_fee_offline"]');
  const onlineReqStar = document.getElementById('onlineReqStar');
  const offlineReqStar= document.getElementById('offlineReqStar');

  [locationRow, zoomRow].forEach(s=> s.classList.remove('show'));
  [locInput, zoomInput].forEach(i=> i.removeAttribute('required'));
  [locStar, zoomStar].forEach(s=> s.style.display='none');

  [onlineWrap, offlineWrap].forEach(w=> w.classList.remove('d-none','disabled-field'));
  [onlineInput, offlineInput].forEach(i=> i.removeAttribute('required'));
  onlineReqStar.style.display='none';
  offlineReqStar.style.display='none';

  if (format === 'offline'){
    locationRow.classList.add('show');
    locInput.setAttribute('required','required');
    locStar.style.display='inline';

    onlineWrap.classList.add('d-none');
    onlineInput.value = '0';

    offlineInput.setAttribute('required','required');
    offlineReqStar.style.display='inline';
  } else if (format === 'online'){
    zoomRow.classList.add('show');
    zoomInput.setAttribute('required','required');
    zoomStar.style.display='inline';

    offlineWrap.classList.add('d-none');
    offlineInput.value = '0';

    onlineInput.setAttribute('required','required');
    onlineReqStar.style.display='inline';
  } else { // both
    locationRow.classList.add('show');
    zoomRow.classList.add('show');
    locInput.setAttribute('required','required');
    zoomInput.setAttribute('required','required');
    locStar.style.display='inline';
    zoomStar.style.display='inline';

    onlineInput.setAttribute('required','required');
    offlineInput.setAttribute('required','required');
    onlineReqStar.style.display='inline';
    offlineReqStar.style.display='inline';
  }
}

function submitForm(form, url){
  const fd = new FormData(form);
  const format = fd.get('format');

  if (format==='online')  fd.set('audience_fee_offline','0');
  if (format==='offline') fd.set('audience_fee_online','0');

  normalizeCurrencyFields(fd);

  const btn = form.querySelector('button[type="submit"]');
  const old = btn.innerHTML;
  btn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i>Menyimpan...';
  btn.disabled = true;

  fetchJSON(url, {
    method:'POST',
    body:fd,
    headers:{ 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With':'XMLHttpRequest' }
  })
  .then(data=>{
    if(data.success){
      Swal.fire({icon:'success',title:'Berhasil!',text:data.message,timer:1500,showConfirmButton:false})
        .then(()=>location.reload());
    } else {
      const msg = data.message || (data.errors ? Object.values(data.errors).join('\n') : 'Terjadi kesalahan');
      throw new Error(msg);
    }
  })
  .catch(err=> Swal.fire({icon:'error',title:'Error!',text:err.message}))
  .finally(()=>{ btn.innerHTML = old; btn.disabled = false; });
}

function editEvent(eventId){
  fetchJSON(`<?= base_url("admin/event/edit") ?>/${eventId}`, {
    headers: { 'X-Requested-With':'XMLHttpRequest' }
  })
  .then(data=>{
    if(!data.success) throw new Error(data.message || 'Gagal memuat data event');
    populateEditForm(data.event);
    document.getElementById('editEventForm').dataset.eventId = eventId;
    new bootstrap.Modal(document.getElementById('editEventModal')).show();
  })
  .catch(err=> Swal.fire('Error!', err.message, 'error'));
}

function populateEditForm(event){
  const fmt = v => formatNumberID(String(v ?? 0));

  const audienceOnlineDiv = event.format!=='offline' ? `
    <div class="col-md-4 mb-3">
      <label class="form-label">Audience (Online) *</label>
      <div class="input-group">
        <span class="input-group-text">Rp</span>
        <input type="text" class="form-control currency" name="audience_fee_online" value="${fmt(event.audience_fee_online)}" required>
      </div>
    </div>` : '';

  const audienceOfflineDiv = event.format!=='online' ? `
    <div class="col-md-4 mb-3">
      <label class="form-label">Audience (Offline) *</label>
      <div class="input-group">
        <span class="input-group-text">Rp</span>
        <input type="text" class="form-control currency" name="audience_fee_offline" value="${fmt(event.audience_fee_offline)}" required>
      </div>
    </div>` : '';

  const locationDiv = (event.format==='offline' || event.format==='both') ? `
    <div class="mb-3">
      <label class="form-label">Lokasi ${event.format==='offline' ? '<span class="text-danger">*</span>' : ''}</label>
      <input type="text" class="form-control" name="location" value="${event.location || ''}" ${event.format==='offline'?'required':''}>
    </div>` : '';

  const zoomDiv = (event.format==='online' || event.format==='both') ? `
    <div class="mb-3">
      <label class="form-label">Link Zoom ${event.format==='online' ? '<span class="text-danger">*</span>' : ''}</label>
      <input type="url" class="form-control" name="zoom_link" value="${event.zoom_link || ''}" ${event.format==='online'?'required':''}>
    </div>` : '';

  const html = `
    <div class="row">
      <div class="col-md-8 mb-3">
        <label class="form-label">Judul Event *</label>
        <input type="text" class="form-control" name="title" value="${event.title}" required>
      </div>
      <div class="col-md-4 mb-3">
        <label class="form-label">Format *</label>
        <select class="form-select" name="format" required>
          <option value="both" ${event.format==='both'?'selected':''}>Hybrid</option>
          <option value="online" ${event.format==='online'?'selected':''}>Online</option>
          <option value="offline" ${event.format==='offline'?'selected':''}>Offline</option>
        </select>
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label">Deskripsi</label>
      <textarea class="form-control" name="description" rows="3">${event.description || ''}</textarea>
    </div>
    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label">Tanggal Event *</label>
        <input type="date" class="form-control" name="event_date" value="${event.event_date}" required>
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label">Waktu Event *</label>
        <input type="time" class="form-control" name="event_time" value="${event.event_time}" required>
      </div>
    </div>
    ${locationDiv}
    ${zoomDiv}
    <h6 class="border-bottom pb-2 mb-3">Struktur Harga</h6>
    <div class="row">
      <div class="col-md-4 mb-3">
        <label class="form-label">Presenter (Offline) *</label>
        <div class="input-group">
          <span class="input-group-text">Rp</span>
          <input type="text" class="form-control currency" name="presenter_fee_offline" value="${fmt(event.presenter_fee_offline)}" required>
        </div>
        <small class="text-muted">Presenter hanya bisa offline</small>
      </div>
      ${audienceOnlineDiv}
      ${audienceOfflineDiv}
    </div>
    <h6 class="border-bottom pb-2 mb-3">Pengaturan Tambahan</h6>
    <div class="row">
      <div class="col-md-4 mb-3">
        <label class="form-label">Maksimal Peserta</label>
        <input type="number" class="form-control" name="max_participants" value="${event.max_participants || ''}">
      </div>
      <div class="col-md-4 mb-3">
        <label class="form-label">Batas Pendaftaran</label>
        <input type="datetime-local" class="form-control" name="registration_deadline" value="${event.registration_deadline ? event.registration_deadline.slice(0,16) : ''}">
      </div>
      <div class="col-md-4 mb-3">
        <label class="form-label">Batas Submit Abstrak</label>
        <input type="datetime-local" class="form-control" name="abstract_deadline" value="${event.abstract_deadline ? event.abstract_deadline.slice(0,16) : ''}">
      </div>
    </div>
    <h6 class="border-bottom pb-2 mb-3">Status Event</h6>
    <div class="row">
      <div class="col-md-4"><div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" name="is_active" ${event.is_active?'checked':''}>
        <label class="form-check-label">Event Aktif</label>
      </div></div>
      <div class="col-md-4"><div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" name="registration_active" ${event.registration_active?'checked':''}>
        <label class="form-check-label">Pendaftaran Aktif</label>
      </div></div>
      <div class="col-md-4"><div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" name="abstract_submission_active" ${event.abstract_submission_active?'checked':''}>
        <label class="form-check-label">Submit Abstrak Aktif</label>
      </div></div>
    </div>
    ${event.format==='online'  ? '<input type="hidden" name="audience_fee_offline" value="0">' : ''}
    ${event.format==='offline' ? '<input type="hidden" name="audience_fee_online"  value="0">' : ''}
  `;
  const container = document.getElementById('editFormContent');
  container.innerHTML = html;
  initCurrencyInputs(container);
}

function makeRequest(url, action){
  fetchJSON(url, {
    method:'POST',
    headers:{ 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With':'XMLHttpRequest' }
  })
  .then(data=>{
    if(data.success){
      Swal.fire({icon:'success',title:'Berhasil!',text:data.message,timer:1500,showConfirmButton:false})
        .then(()=>location.reload());
    } else {
      throw new Error(data.message || `Gagal ${action}`);
    }
  })
  .catch(err=> Swal.fire('Error!', err.message, 'error'));
}

function toggleStatus(id){ makeRequest(`<?= base_url("admin/event/toggle-status") ?>/${id}`, 'toggle status event'); }
function toggleRegistration(id){ makeRequest(`<?= base_url("admin/event/toggle-registration") ?>/${id}`, 'toggle pendaftaran'); }
function deleteEvent(id){
  Swal.fire({
    title:'Hapus Event?', text:'Event yang dihapus tidak dapat dikembalikan!',
    icon:'warning', showCancelButton:true, confirmButtonColor:'#d33', cancelButtonColor:'#3085d6',
    confirmButtonText:'Ya, Hapus!', cancelButtonText:'Batal'
  }).then(r=>{ if(r.isConfirmed) makeRequest(`<?= base_url("admin/event/delete") ?>/${id}`, 'hapus event'); });
}
</script>
