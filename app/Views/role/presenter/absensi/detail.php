<?php
$title    = $title ?? 'Detail Absensi';
$event    = $event ?? [];
$payment  = $payment ?? [];
$window   = $window ?? ['is_open'=>false,'start_ts'=>null,'end_ts'=>null,'reason'=>''];
$attended = $attended ?? false;
$tokens   = $tokens ?? []; // array token string untuk dropdown

// REAL mode
$anytime   = false;
$canAttend = (!$attended) && (!empty($window['is_open']));

// WIB helpers
date_default_timezone_set('Asia/Jakarta');
$fmt = function($ts,$f='d M Y H:i'){
  if(!$ts) return '-';
  $d = new DateTime('@'.$ts); $d->setTimezone(new DateTimeZone('Asia/Jakarta'));
  return $d->format($f);
};
$startText = $fmt($window['start_ts']);
$endText   = $fmt($window['end_ts']);
$nowWIB    = (new DateTime('now', new DateTimeZone('Asia/Jakarta')))->format('Y-m-d H:i:s');
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl px-3 px-md-4 py-3 py-md-4">

      <!-- HERO (compact, no “dapur”) -->
      <div class="hero-blue mb-2 p-3 p-md-4 d-flex align-items-start justify-content-between gap-2">
        <div class="flex-grow-1">
          <h4 class="hero-title mb-1"><i class="bi bi-qr-code me-2"></i>Absensi Presenter</h4>
          <div class="text-white-75 small"><span class="fw-semibold"><?= esc($event['title'] ?? '-') ?></span></div>
          <div class="text-white-75 small">WIB: <span id="now-wib"><?= $nowWIB ?></span></div>
        </div>
        <div class="text-end d-none d-md-block">
          <div class="text-white-75 small">Window (WIB)</div>
          <div class="fw-semibold text-white"><?= $startText ?> – <?= $endText ?></div>
        </div>
      </div>

      <div class="row g-3">
        <!-- PANEL ABSENSI -->
        <div class="col-12 col-lg-5 order-1 order-lg-2">
          <div class="card card-glass-plain shadow-soft h-100">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-blue-soft"><i class="bi bi-person-check"></i></span>
                <h6 class="mb-0 fw-semibold text-blue-900">Panel Absensi</h6>
              </div>
              <span class="badge <?= $attended ? 'bg-success-subtle text-success' : ($canAttend ? 'bg-info-subtle text-info' : 'bg-secondary-subtle text-secondary') ?>">
                <?= $attended ? 'Sudah Absen' : ($canAttend ? 'Window Buka' : 'Tertutup') ?>
              </span>
            </div>
            <div class="card-body">
              <div class="d-flex align-items-start gap-2 mb-2">
                <div class="status-dot <?= $attended ? 'bg-success' : ($canAttend ? 'bg-primary' : 'bg-secondary') ?>"></div>
                <div class="small text-muted">
                  <span class="d-inline-block me-1">Lokasi: <span class="fw-semibold"><?= esc($event['location'] ?? '-') ?></span></span>
                  <?php if (!empty($event['format'])): ?>
                    <span class="d-inline-block me-1">• Format: <span class="fw-semibold"><?= strtoupper(esc($event['format'])) ?></span></span>
                  <?php endif; ?>
                  • <span class="badge bg-purple text-white">Presenter</span>
                </div>
              </div>

              <!-- Form token: dropdown → fallback input -->
              <form action="<?= site_url('presenter/absensi/scan') ?>" method="POST" id="tokenForm" class="mb-3">
                <?= csrf_field() ?>
                <input type="hidden" name="event_id" value="<?= (int)($event['id'] ?? 0) ?>">
                <label class="form-label fw-semibold mb-1">Token Kehadiran</label>

                <?php if (!empty($tokens)): ?>
                  <div class="input-group input-group-lg mb-2">
                    <span class="input-group-text"><i class="bi bi-key"></i></span>
                    <select name="token" class="form-select" <?= $canAttend ? 'required' : 'disabled' ?>>
                      <option value="" selected disabled>Pilih token</option>
                      <?php foreach($tokens as $t): ?>
                        <option value="<?= esc($t) ?>"><?= esc($t) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                <?php else: ?>
                  <div class="input-group input-group-lg mb-2">
                    <span class="input-group-text"><i class="bi bi-input-cursor-text"></i></span>
                    <input type="text" class="form-control" name="token" placeholder="Tempel token / URL QR" <?= $canAttend ? 'required' : 'disabled' ?>>
                  </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between align-items-center small text-muted mb-2">
                  <div><strong>WIB:</strong> <span id="wib-inline"><?= $nowWIB ?></span> • Window: <?= $startText ?> – <?= $endText ?></div>
                  <button class="btn btn-primary btn-md" type="submit" <?= $canAttend ? '' : 'disabled' ?>>
                    <i class="bi bi-check2-circle"></i> Absen
                  </button>
                </div>
              </form>

              <!-- Scanner -->
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="fw-semibold text-blue-900"><i class="bi bi-qr-code-scan me-1"></i>Scan QR Code</div>
                <button class="btn btn-light btn-sm d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#scannerWrap" aria-expanded="true">
                  Tampilkan/Sembunyikan
                </button>
              </div>

              <div id="scannerWrap" class="collapse show">
                <div id="scanner-controls" class="d-flex flex-wrap gap-2 mb-2">
                  <button id="startScanBtn" class="btn btn-success btn-md flex-fill" <?= $canAttend ? '' : 'disabled' ?>>
                    <i class="bi bi-play-circle"></i> Mulai Scan
                  </button>
                  <button id="stopScanBtn" class="btn btn-danger btn-md flex-fill" style="display:none;">
                    <i class="bi bi-stop-circle"></i> Stop
                  </button>
                  <button id="switchCameraBtn" class="btn btn-info btn-md flex-fill">
                    <i class="bi bi-arrow-repeat"></i> Ganti Kamera
                  </button>
                </div>

                <div class="qr-frame mb-2">
                  <div id="reader" class="qr-canvas"></div>
                </div>
                <div id="scanner-message" class="text-muted small">Klik “Mulai Scan” untuk mengaktifkan kamera.</div>

                <!-- hasil & error -->
                <div id="scan-result" class="alert alert-info mt-3" style="display:none;">
                  <strong>QR Terdeteksi:</strong>
                  <div id="scanned-content" class="mt-2 font-monospace small"></div>
                  <div id="qr-role-info" class="mt-2"></div>
                  <div id="processing-status" class="mt-2"></div>
                </div>
                <div id="success-result" class="alert alert-success mt-3" style="display:none;">
                  <i class="bi bi-check-circle-fill fs-6 text-success me-1"></i><strong>Absensi Berhasil!</strong>
                  <div id="success-details" class="small mt-1"></div>
                </div>
                <div id="camera-permission" class="alert alert-warning mt-2" style="display:none;">
                  <i class="bi bi-camera-fill me-2"></i><strong>Izin Kamera Diperlukan</strong><br><small>Izinkan akses kamera untuk menggunakan scanner</small>
                </div>
                <div id="scanner-error" class="alert alert-danger mt-2" style="display:none;">
                  <i class="bi bi-exclamation-triangle me-2"></i><strong id="error-message">Terjadi kesalahan</strong>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- INFO EVENT + PAYMENT (singkat, tanpa “dapur”) -->
        <div class="col-12 col-lg-7 order-2 order-lg-1">
          <div class="card card-glass-plain shadow-soft mb-3">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-info-circle"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Informasi Event</h6>
            </div>
            <div class="card-body">
              <h5 class="mb-2 text-blue-900"><?= esc($event['title'] ?? '-') ?></h5>
              <div class="text-muted small mb-3"><?= nl2br(esc($event['description'] ?? '-')) ?></div>
              <div class="row gy-2">
                <div class="col-12 col-sm-6">
                  <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-calendar-event text-primary"></i>
                    <div>
                      <div class="small text-muted">Tanggal & Waktu (WIB)</div>
                      <div class="fw-semibold">
                        <?php if (!empty($event['event_date'])):
                          $dt = new DateTime(($event['event_date'] ?? '').' '.($event['event_time'] ?? '00:00:00'), new DateTimeZone('Asia/Jakarta')); ?>
                          <?= $dt->format('d M Y') ?> • <?= $dt->format('H:i') ?> WIB
                        <?php else: ?>-<?php endif; ?>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-12 col-sm-6">
                  <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-geo-alt text-primary"></i>
                    <div>
                      <div class="small text-muted">Lokasi</div>
                      <div class="fw-semibold"><?= esc($event['location'] ?? '-') ?></div>
                    </div>
                  </div>
                </div>
                <?php if (!empty($event['zoom_link'])): ?>
                <div class="col-12">
                  <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-camera-video text-primary"></i>
                    <div>
                      <div class="small text-muted">Link Online</div>
                      <a href="<?= esc($event['zoom_link']) ?>" target="_blank" rel="noopener" class="fw-semibold">Buka Tautan</a>
                    </div>
                  </div>
                </div>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <div class="card card-glass-plain shadow-soft">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-receipt"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Status Pembayaran</h6>
            </div>
            <div class="card-body">
              <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge bg-success-subtle text-success">Verified</span>
                <div class="small text-muted">Anda berhak melakukan absensi sebagai Presenter.</div>
              </div>
              <div class="small text-muted">Metode</div>
              <div class="fw-semibold mb-2"><?= esc($payment['metode'] ?? '-') ?></div>
              <div class="small text-muted">Jumlah</div>
              <div class="fw-semibold">Rp <?= number_format((int)($payment['jumlah'] ?? 0), 0, ',', '.') ?></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Sticky action bar (mobile) -->
      <div class="sticky-bar d-lg-none">
        <div class="container-xxl px-3">
          <div class="bar-inner">
            <button id="stickyStart" class="btn btn-success btn-lg flex-fill" <?= $canAttend ? '' : 'disabled' ?>>
              <i class="bi bi-qr-code-scan me-1"></i> Mulai Scan
            </button>
            <button class="btn btn-primary btn-lg flex-fill" data-scroll="#tokenForm" <?= $canAttend ? '' : 'disabled' ?>>
              <i class="bi bi-check2-circle me-1"></i> Pilih Token
            </button>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<!-- JS (scanner) -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
let html5QrCode=null,isScanning=false,isProcessing=false,scannedResult=null,cameras=[],currentCameraIndex=0,scanSuccessful=false;

// --- Jam WIB live ---
function nowWIB(){ const now=new Date(); const wib=new Date(now.getTime()+(7*60*60*1000)-(now.getTimezoneOffset()*60*1000)); return wib.toISOString().slice(0,19).replace('T',' '); }
function tickWIB(){ ['now-wib','wib-inline'].forEach(id=>{ const el=document.getElementById(id); if(el) el.textContent=nowWIB(); }); }
setInterval(tickWIB,1000);

// --- Deteksi role QR sederhana (untuk UX saja) ---
function detectQRRole(qr){
  const p={
    presenter:/EVENT_\d+_presenter_/i,
    universal:/EVENT_\d+_all_/i,
    audience:/EVENT_\d+_audience_/i,
    reviewer:/EVENT_\d+_reviewer_/i,
    simple:/EVENT_\d+_\d{8}$/i,
    numeric:/^\d+$/,
    admin:/^(ADMIN|MANUAL|BULK)_\d+/i
  };
  if(p.presenter.test(qr)) return {type:'presenter',allowed:true,badge:'bg-purple text-white',text:'Presenter QR - Valid'};
  if(p.universal.test(qr) || p.simple.test(qr) || p.numeric.test(qr) || p.admin.test(qr)) return {type:'universal',allowed:true,badge:'bg-primary-subtle text-primary',text:'Universal QR - Valid'};
  if(p.audience.test(qr)) return {type:'audience',allowed:false,badge:'bg-warning-subtle text-warning',text:'Audience QR - Ditolak'};
  if(p.reviewer.test(qr)) return {type:'reviewer',allowed:false,badge:'bg-info-subtle text-dark',text:'Reviewer QR - Ditolak'};
  return {type:'unknown',allowed:false,badge:'bg-secondary-subtle text-secondary',text:'Format QR tidak dikenali'};
}

async function initCams(){
  try{ cameras=await Html5Qrcode.getCameras(); return cameras.length>0; }
  catch(e){ console.error(e); showError('Tidak dapat mengakses daftar kamera: '+(e.message||e)); return false; }
}
async function initScanner(){
  try{
    if(html5QrCode && html5QrCode.getState()===Html5QrcodeScannerState.SCANNING){ try{ await html5QrCode.stop(); }catch(_){} }
    html5QrCode=new Html5Qrcode("reader");
    const ok=await initCams(); if(!ok){ showError('Tidak ada kamera yang tersedia'); return false; }
    return true;
  }catch(e){ console.error(e); showError('Gagal inisialisasi scanner: '+(e.message||e)); return false; }
}
async function startScanner(){
  const startBtn=document.getElementById('startScanBtn');
  const stopBtn=document.getElementById('stopScanBtn');
  const msg=document.getElementById('scanner-message');
  if(isScanning) return;
  msg.innerHTML='<span class="text-info"><i class="bi bi-camera-fill"></i> Memulai kamera...</span>';
  try{
    const cameraCfg=cameras.length?cameras[currentCameraIndex].id:{facingMode:"environment"};
    const size=Math.min(360, Math.floor(window.innerWidth*0.9));
    const cfg={fps:12,qrbox:{width:size,height:size},aspectRatio:1.0};
    await html5QrCode.start(cameraCfg,cfg,(decoded)=>onScanSuccess(decoded),()=>{});
    isScanning=true; startBtn.style.display='none'; stopBtn.style.display='inline-block';
    msg.innerHTML='<span class="text-success"><i class="bi bi-camera-video"></i> Scanner aktif</span>';
  }catch(e){
    console.error(e); isScanning=false;
    let t='Gagal memulai scanner';
    const s=e.toString();
    if(s.includes('Permission')){ t='Akses kamera ditolak. Izinkan kamera di browser.'; document.getElementById('camera-permission').style.display='block'; }
    else if(s.includes('NotFound')) t='Kamera tidak ditemukan.';
    else if(s.includes('NotReadable')) t='Kamera sedang dipakai aplikasi lain.';
    else t='Gagal memulai scanner: '+(e.message||e);
    showError(t);
    startBtn.style.display='inline-block'; stopBtn.style.display='none';
  }
}
async function stopScanner(){
  const startBtn=document.getElementById('startScanBtn');
  const stopBtn=document.getElementById('stopScanBtn');
  const msg=document.getElementById('scanner-message');
  if(!isScanning) return;
  try{ if(html5QrCode && html5QrCode.getState()===Html5QrcodeScannerState.SCANNING){ await html5QrCode.stop(); } }
  catch(e){ console.error(e); }
  isScanning=false; startBtn.style.display='inline-block'; stopBtn.style.display='none';
  msg.innerHTML='<span class="text-muted"><i class="bi bi-pause-circle"></i> Scanner dihentikan</span>';
}
async function switchCamera(){
  if(cameras.length<=1){ Swal.fire({icon:'info',title:'Tidak bisa ganti kamera',text:'Hanya satu kamera tersedia'}); return; }
  const was=isScanning; if(was) await stopScanner();
  currentCameraIndex=(currentCameraIndex+1)%cameras.length;
  if(was) setTimeout(()=>startScanner(),600);
}

async function onScanSuccess(decodedText){
  if(isProcessing||scanSuccessful) return;
  isProcessing=true;
  const resBox=document.getElementById('scan-result');
  const content=document.getElementById('scanned-content');
  const roleBox=document.getElementById('qr-role-info');
  const proc=document.getElementById('processing-status');
  const msg=document.getElementById('scanner-message');

  content.textContent=decodedText;
  const role=detectQRRole(decodedText);
  roleBox.innerHTML=`<span class="badge ${role.badge}">${role.text}</span>`;
  resBox.style.display='block';

  if(!role.allowed){
    proc.innerHTML='<span class="badge bg-danger">QR Ditolak</span>';
    msg.innerHTML='<span class="text-danger"><i class="bi bi-exclamation-triangle"></i> QR tidak bisa untuk Presenter</span>';
    setTimeout(async()=>{ await stopScanner(); isProcessing=false; },1200);
    Swal.fire({icon:'warning',title:'QR Tidak Sesuai',html:`Jenis: <b>${role.type.toUpperCase()}</b>. Gunakan QR <b>Presenter</b> atau <b>Universal</b>.`});
    return;
  }

  const now = nowWIB();
  proc.innerHTML=`<span class="badge bg-info"><i class="bi bi-hourglass-split"></i> Memproses... (WIB: ${now})</span>`;
  msg.innerHTML='<span class="text-info"><i class="bi bi-cloud-upload"></i> Menyimpan absensi...</span>';
  await stopScanner();

  try{
    const res=await fetch('<?= site_url('presenter/absensi/scan') ?>',{
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},
      body:new URLSearchParams({
        'event_id':'<?= (int)($event['id'] ?? 0) ?>',
        'token':decodedText,
        '<?= csrf_token() ?>':'<?= csrf_hash() ?>'
      })
    });
    const json=await res.json();

    if(json.success){
      scanSuccessful=true; resBox.style.display='none';
      const succ=document.getElementById('success-result');
      const det=document.getElementById('success-details');
      const t=json.data.attendance_datetime_wib||now;
      det.innerHTML=`
        <div class="row g-2 text-start">
          <div class="col-6"><strong>Nama:</strong><br>${json.data.participant_name}</div>
          <div class="col-6"><strong>Role:</strong><br><span class="badge bg-purple text-white">${json.data.participant_role}</span></div>
          <div class="col-6"><strong>Event:</strong><br>${json.data.event_title}</div>
          <div class="col-6"><strong>Waktu WIB:</strong><br>${t}</div>
        </div>`;
      succ.style.display='block';
      msg.innerHTML='<span class="text-success"><i class="bi bi-check-circle"></i> Tersimpan!</span>';
      Swal.fire({icon:'success',title:'Absensi Berhasil!',html:`<small>${json.data.participant_name}</small><br><small>${t} WIB</small>`,timer:3000,showConfirmButton:false});
      setTimeout(()=>location.reload(),3000);
    }else{
      proc.innerHTML='<span class="badge bg-danger">Gagal</span>';
      msg.innerHTML=`<span class="text-danger"><i class="bi bi-exclamation-triangle"></i> ${json.message}</span>`;
      Swal.fire({icon:'error',title:'Gagal Simpan',text:json.message});
    }
  }catch(e){
    console.error(e);
    proc.innerHTML='<span class="badge bg-danger">Error Koneksi</span>';
    msg.innerHTML='<span class="text-danger"><i class="bi bi-wifi-off"></i> Gagal terhubung ke server</span>';
    Swal.fire({icon:'error',title:'Error Koneksi',text:'Periksa koneksi internet Anda.'});
  }finally{
    isProcessing=false;
  }
}

function showError(message){
  const err=document.getElementById('scanner-error');
  document.getElementById('error-message').textContent=message;
  err.style.display='block';
  document.getElementById('scanner-message').innerHTML=`<span class="text-danger"><i class="bi bi-exclamation-triangle"></i> ${message}</span>`;
}

function smoothScrollTo(sel){
  const el=document.querySelector(sel); if(!el) return;
  window.scrollTo({top: el.getBoundingClientRect().top + window.scrollY - 80, behavior:'smooth'});
}

// --- Auto alert: sudah/belum waktunya absen ---
function showWindowAlerts(){
  const attended = <?= $attended ? 'true' : 'false' ?>;
  const isOpen   = <?= !empty($window['is_open']) ? 'true' : 'false' ?>;
  const anytime  = <?= isset($anytime) && $anytime ? 'true' : 'false' ?>;

  const startText = <?= json_encode($startText ?? '-') ?>;
  const endText   = <?= json_encode($endText ?? '-') ?>;
  const reason    = <?= json_encode($window['reason'] ?? '') ?>;

  if (attended) return;          // sudah absen → diam
  if (anytime) return;           // real mode: anytime=false (kalau true, diam/atau bisa info)

  if (isOpen) {
    Swal.fire({
      icon: 'success',
      title: 'Waktu Absen Dibuka',
      html: `Silakan lakukan absen sekarang.<br><small>Window: ${startText} – ${endText} WIB</small>`,
      timer: 3200,
      showConfirmButton: false
    });
  } else {
    Swal.fire({
      icon: 'warning',
      title: 'Belum Waktunya Absen',
      html: `${reason ? reason : 'Window absensi belum dibuka atau sudah ditutup.'}<br><small>Window: ${startText} – ${endText} WIB</small>`
    });
  }
}

document.addEventListener('DOMContentLoaded', async ()=>{
  tickWIB();
  await initScanner();

  document.getElementById('startScanBtn').addEventListener('click', startScanner);
  document.getElementById('stopScanBtn').addEventListener('click', stopScanner);
  document.getElementById('switchCameraBtn').addEventListener('click', switchCamera);

  document.getElementById('stickyStart').addEventListener('click', ()=>{ smoothScrollTo('#reader'); startScanner(); });
  document.querySelector('[data-scroll="#tokenForm"]')?.addEventListener('click',(e)=>{ e.preventDefault(); smoothScrollTo('#tokenForm'); });

  document.getElementById('tokenForm')?.addEventListener('submit', function(e){
    e.preventDefault();
    Swal.fire({
      title:'Konfirmasi Absensi Presenter',
      html:`Kirim token?<br><small>Waktu: ${nowWIB()} WIB</small>`,
      icon:'question', showCancelButton:true, confirmButtonText:'Ya, Kirim', cancelButtonText:'Batal'
    }).then(r=>{ if(r.isConfirmed) this.submit(); });
  });

  // tampilkan alert window waktu
  showWindowAlerts();
});
</script>

<style>
/* ===== Presenter Blue UI (seragam, sama dengan index) ===== */
:root{
  --blue-50:#eff6ff; --blue-100:#dbeafe; --blue-200:#bfdbfe;
  --blue-300:#93c5fd; --blue-400:#60a5fa; --blue-500:#3b82f6;
  --blue-600:#2563eb; --blue-700:#1d4ed8; --blue-800:#1e40af; --blue-900:#1e3a8a;
  --side-pad:1rem; --gutter:1rem;
}
body{ font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size:14.6px; line-height:1.5; }

/* Layout */
.page-wrap-blue{ background:linear-gradient(180deg,var(--blue-50),#fff 40%); min-height:100vh; padding-top:72px; }
.container-xxl{ max-width:1400px; padding-left:var(--side-pad)!important; padding-right:var(--side-pad)!important; }
.row.g-3{ --bs-gutter-x: var(--gutter); --bs-gutter-y: var(--gutter); }

/* Hero (sama dengan index) */
.hero-blue{
  background:radial-gradient(1200px 400px at 10% -20%,var(--blue-600) 0,var(--blue-700) 40%,var(--blue-800) 100%) !important;
  color:#fff !important; border-radius:16px;
  border:1px solid rgba(255,255,255,.15);
  box-shadow:0 12px 28px rgba(30,64,175,.10);
}
.hero-blue.card-glass,.hero-blue.card-glass-plain{ backdrop-filter:none !important; }
.hero-title{ font-weight:800; letter-spacing:.25px; font-size:1.45rem; }
.text-white-75{ color:rgba(255,255,255,.85)!important; }

/* Cards (sama dengan index) */
.card-glass-plain{
  backdrop-filter:blur(6px);
  background:#fff;
  border-radius:14px;
  border:1px solid rgba(30,64,175,.10);
}
.shadow-soft{ box-shadow:0 10px 24px rgba(30,64,175,.08); }
.card-header{ padding:1rem 1rem .45rem 1rem !important; }
.card-body{   padding:1.05rem !important; }

/* Badges subtle (sama dengan index) */
.bg-blue-soft{ background:var(--blue-200); color:var(--blue-800); border-radius:12px; padding:.38rem .6rem; font-weight:600; font-size:.85rem; }
.bg-success-subtle{   background:#d1fae5!important; color:#065f46!important; }
.bg-warning-subtle{   background:#fef3c7!important; color:#92400e!important; }
.bg-secondary-subtle{ background:#f1f5f9!important; color:#475569!important; }
.bg-info-subtle{      background:#e0f2fe!important; color:#0c4a6e!important; }
.text-blue-900{ color:var(--blue-900)!important; }
.bg-purple{ background:#8b5cf6!important; }

/* Status dot */
.status-dot{ width:12px; height:12px; border-radius:50%; margin-top:4px; }
.status-dot.bg-success{ background:#16a34a; }
.status-dot.bg-primary{ background:#2563eb; }
.status-dot.bg-secondary{ background:#94a3b8; }

/* QR viewport */
.qr-frame{
  width:100%;
  border:2px solid #e2e8f0;
  border-radius:14px;
  padding:.6rem;
  background:#f8fafc;
  box-shadow:0 10px 22px rgba(30,64,175,.08);
}
.qr-canvas{ width:100%; max-width:540px; margin:0 auto; aspect-ratio:1/1; }
#reader{ border-radius:10px; overflow:hidden; }
#reader__dashboard_section, #reader__header_message{ display:none!important; }
.font-monospace{ font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; word-break:break-all; }

/* Buttons */
.btn{ font-weight:600; letter-spacing:.25px; border-radius:10px; font-size:.95rem; padding:.55rem 1rem; }
.btn-primary{ background:var(--blue-600); border-color:var(--blue-600); box-shadow:0 4px 12px rgba(37,99,235,.2); }
.btn-success{ background:#059669; border-color:#059669; box-shadow:0 4px 12px rgba(5,150,105,.2); }
.btn-info{ background:#0ea5e9; border-color:#0ea5e9; }
.btn-outline-secondary{ border-color:#cbd5e1; }

/* Sticky bar (mobile) */
.sticky-bar{
  position:sticky; bottom:0; left:0; right:0; z-index:1030;
  background:linear-gradient(180deg, rgba(255,255,255,.0), #ffffff 28%), #fff;
  border-top:1px solid #e5e7eb;
  padding:.5rem 0 .75rem;
}
.sticky-bar .bar-inner{ display:flex; gap:.6rem; }

/* Responsive – match index */
@media (max-width:575.98px){
  .container-xxl{ padding-left:1rem!important; padding-right:1rem!important; }
  .hero-blue{ border-radius:16px; padding:1.25rem!important; margin-bottom:1rem!important; }
  .hero-title{ font-size:1.25rem; }
  .qr-frame{ padding:.5rem; }
}
</style>