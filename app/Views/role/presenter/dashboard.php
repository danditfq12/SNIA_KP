<?php
  $title        = $title        ?? 'Presenter Dashboard';
  $upcomingPaid = $upcomingPaid ?? [];   // event verified (>= kemarin)
  $pendingPays  = $pendingPays  ?? [];   // pembayaran pending
  $eventMap     = $eventMap     ?? [];   // peta event_id -> event
  $kpis         = $kpis         ?? ['joined'=>0,'accepted'=>0,'verified'=>0,'loa'=>0];
  $absenToday   = $absenToday   ?? [];   // event verified yang tanggalnya = hari ini (OFFLINE only)

  helper(['number','form']);
  $fmtDate = fn($s)=> $s ? date('d M Y', strtotime($s)) : '-';
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Hai, <?= esc(session('nama_lengkap') ?? session('nama') ?? 'Presenter') ?></h3>
        <div class="d-none d-md-flex gap-2">
          <a href="<?= site_url('presenter/events') ?>" class="btn btn-primary">
            <i class="bi bi-calendar2-event me-1"></i>Event
          </a>
          <a href="<?= site_url('presenter/abstrak') ?>" class="btn btn-outline-primary">
            <i class="bi bi-file-earmark-text me-1"></i>Abstrak
          </a>
          <a href="<?= site_url('presenter/pembayaran') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-wallet2 me-1"></i>Pembayaran
          </a>
          <a href="<?= site_url('presenter/absensi') ?>" class="btn btn-outline-dark">
            <i class="bi bi-qr-code-scan me-1"></i>Absensi
          </a>
          <a href="<?= site_url('presenter/dokumen/loa') ?>" class="btn btn-outline-success">
            <i class="bi bi-award me-1"></i>Dokumen
          </a>
        </div>
      </div>

      <!-- KPI -->
      <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3"><div class="card kpi-card shadow-sm h-100">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="kpi-icon bg-primary-subtle text-primary"><i class="bi bi-people"></i></div>
            <div><div class="text-muted small">Event Diikuti</div><div class="fs-4 fw-semibold"><?= number_format($kpis['joined']) ?></div></div>
          </div></div></div>

        <div class="col-6 col-xl-3"><div class="card kpi-card shadow-sm h-100">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="kpi-icon bg-success-subtle text-success"><i class="bi bi-journal-check"></i></div>
            <div><div class="text-muted small">Abstrak DiACC</div><div class="fs-4 fw-semibold"><?= number_format($kpis['accepted']) ?></div></div>
          </div></div></div>

        <div class="col-6 col-xl-3"><div class="card kpi-card shadow-sm h-100">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="kpi-icon bg-info-subtle text-info"><i class="bi bi-patch-check"></i></div>
            <div><div class="text-muted small">Pembayaran Terverifikasi</div><div class="fs-4 fw-semibold"><?= number_format($kpis['verified']) ?></div></div>
          </div></div></div>

        <div class="col-6 col-xl-3"><div class="card kpi-card shadow-sm h-100">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="kpi-icon bg-warning-subtle text-warning"><i class="bi bi-file-earmark-text"></i></div>
            <div><div class="text-muted small">LOA Didapat</div><div class="fs-4 fw-semibold"><?= number_format($kpis['loa']) ?></div></div>
          </div></div></div>
      </div>

      <!-- Absen Hari Ini (OFFLINE only, tanpa Join Zoom) -->
      <div class="row g-3 mb-3">
        <div class="col-12">
          <div class="card shadow-sm border-0">
            <div class="card-body">
              <div class="d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">Absen Hari Ini</h5>
                <span class="badge bg-warning text-dark"><?= count($absenToday) ?></span>
              </div>
              <hr class="my-3">
              <?php if (!empty($absenToday)): ?>
                <div class="row g-2 g-md-3">
                  <?php foreach ($absenToday as $e): ?>
                    <div class="col-12 col-md-6 col-lg-4">
                      <div class="abs-today-card p-3 border rounded-3 h-100">
                        <div class="d-flex align-items-start justify-content-between">
                          <div class="fw-semibold me-2 text-truncate" title="<?= esc($e['title'] ?? 'Event') ?>">
                            <i class="bi bi-qr-code-scan me-1"></i><?= esc($e['title'] ?? 'Event') ?>
                          </div>
                          <span class="badge bg-warning text-dark">Hari ini</span>
                        </div>
                        <div class="small text-muted mt-1">
                          <?= esc($fmtDate($e['event_date'] ?? null)) ?> · <?= esc($e['event_time'] ?? '-') ?>
                        </div>

                        <div class="mt-2 d-flex flex-wrap gap-2 justify-content-end">
                          <a class="btn btn-sm btn-outline-warning"
                             href="<?= site_url('presenter/absensi') ?>">
                            Absen Sekarang
                          </a>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <div class="p-4 text-center border rounded-3 bg-light-subtle">
                  <div class="mb-2"><i class="bi bi-qr-code fs-3 text-secondary"></i></div>
                  <div class="fw-semibold">Tidak ada event untuk diabsen hari ini</div>
                  <div class="text-muted small">Cek jadwal mendatang di bawah.</div>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- ROW 1: Kiri Aktivitas, Kanan Pending -->
      <div class="row g-3 mb-3">
        <!-- KIRI: Aktivitas Terbaru -->
        <div class="col-12 col-xl-6">
          <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <h5 class="card-title mb-0">Aktivitas Terbaru</h5>
                <span class="text-muted small d-none d-xl-inline">scroll untuk lihat lainnya</span>
              </div>

              <div class="activities-scroll">
                <div id="notifList" class="vstack gap-2">
                  <div class="small text-muted">Memuat...</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- KANAN: Pembayaran Pending -->
        <div class="col-12 col-xl-6">
          <div class="card shadow-sm h-100">
            <div class="card-body">
              <h5 class="card-title mb-3">Pembayaran Pending</h5>
              <?php if (!empty($pendingPays)): ?>
                <div class="table-responsive">
                  <table class="table table-sm align-middle">
                    <thead class="table-light">
                      <tr><th>Event</th><th class="text-end">Jumlah</th><th class="text-nowrap">Tanggal</th><th></th></tr>
                    </thead>
                    <tbody>
                      <?php foreach ($pendingPays as $p): $ev = $eventMap[(int)$p['event_id']] ?? null; ?>
                        <tr>
                          <td>
                            <div class="fw-semibold"><?= esc($ev['title'] ?? 'Event') ?></div>
                            <div class="small text-muted">
                              <?= esc(isset($ev['event_date']) ? date('d M Y', strtotime($ev['event_date'])) : '-') ?> ·
                              <?= esc($ev['event_time'] ?? '-') ?>
                            </div>
                          </td>
                          <td class="text-end">Rp <?= number_format((float)$p['jumlah'], 0, ',', '.') ?></td>
                          <td class="text-nowrap"><?= esc(date('d M Y', strtotime($p['tanggal_bayar'] ?? 'now'))) ?></td>
                          <td class="text-end">
                            <a href="<?= site_url('presenter/pembayaran/detail/'.$p['id_pembayaran']) ?>" class="btn btn-sm btn-outline-primary">Detail</a>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php else: ?>
                <div class="p-4 text-center border rounded-3 bg-light-subtle">
                  <div class="mb-2"><i class="bi bi-wallet2 fs-3 text-secondary"></i></div>
                  <div class="fw-semibold">Tidak ada pembayaran pending</div>
                  <div class="text-muted small">Upload bukti bayar dari menu Pembayaran.</div>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- ROW 2: Jadwal Event (Lunas / Berjalan) — INFO ONLY -->
      <div class="row g-3">
        <div class="col-12">
          <div class="card shadow-sm h-100">
            <div class="card-body">
              <h5 class="card-title mb-3">Jadwal Event (Lunas / Berjalan)</h5>
              <?php if (!empty($upcomingPaid)): ?>
                <div class="list-group list-group-flush">
                  <?php foreach ($upcomingPaid as $u):
                    $isToday = ($u['event_date'] ?? '') === date('Y-m-d');
                    $loc     = $u['location'] ?? '-';
                  ?>
                    <div class="list-group-item px-0">
                      <div class="d-flex justify-content-between align-items-start">
                        <div>
                          <div class="fw-semibold d-flex align-items-center gap-2">
                            <?= esc($u['title'] ?? 'Event') ?>
                            <?php if ($isToday): ?>
                              <span class="badge bg-warning text-dark">Hari ini</span>
                            <?php else: ?>
                              <span class="badge bg-success-subtle text-success">Mendatang</span>
                            <?php endif; ?>
                          </div>
                          <div class="small text-muted">
                            <?= esc($fmtDate($u['event_date'] ?? null)) ?> ·
                            <?= esc($u['event_time'] ?? '-') ?> ·
                            Lokasi: <?= esc($loc) ?>
                          </div>
                        </div>
                        <!-- Info only -->
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <div class="p-4 text-center border rounded-3 bg-light-subtle">
                  <div class="mb-2"><i class="bi bi-calendar-event fs-3 text-secondary"></i></div>
                  <div class="fw-semibold">Belum ada jadwal aktif</div>
                  <div class="text-muted small">Event akan muncul setelah pembayaran diverifikasi.</div>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
/* KPI */
.kpi-card { border:0; border-left:4px solid #e9ecef; }
.kpi-icon{ width:44px; height:44px; border-radius:10px; display:grid; place-items:center; font-size:20px; }

/* Absen Hari Ini */
.abs-today-card{ background:#fff; transition:.16s ease; }
.abs-today-card:hover{ transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0,0,0,.06); }

/* Aktivitas Terbaru */
.activities-scroll{ max-height: 320px; overflow:auto; padding-right:6px; }
.activities-scroll::-webkit-scrollbar{ width:8px; }
.activities-scroll::-webkit-scrollbar-thumb{ background:#ccd6e0; border-radius:8px; }

/* Activity item */
.notice{ border:1px solid #eef2f6; border-radius:12px; padding:12px; background:#fff; position:relative; transition:.15s ease; }
.notice:hover{ box-shadow:0 8px 18px rgba(0,0,0,.06); }
.notice .title{ font-weight:600; }
.notice .meta{ font-size:.85rem; color:#6c757d; }
.notice .stretched-link{ position:absolute; inset:0; }
</style>

<script>
(function(){
  // ==== AKTIVITAS TERBARU (pakai /notif/recent) ====
  const LIST = document.getElementById('notifList');
  const URL  = '<?= site_url('notif/recent') ?>?limit=20';

  const esc = (s)=> (s||'').toString().replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
  const pick = (o, keys)=> keys.map(k=> o && o[k] ? o[k] : null).find(v=> v!=null) || '';

  function normalizeLink(href){
    if(!href) return '#';
    href = String(href).trim();
    href = href.replace(/^(https?:)\/(?!\/)/i, '$1//');
    const m = /^(https?:)\/\/([^/]+)(\/.*)?$/i.exec(href);
    if (m) {
      const host = m[2], path = m[3] || '/';
      if (host === window.location.host) return path;
      return m[1] + '//' + host + path;
    }
    if (href.startsWith('//')) return window.location.protocol + href;
    if (href.startsWith('/'))  return href;
    return '/' + href.replace(/^\.?\//,'');
  }

  async function getJSON(url){
    const res = await fetch(url, {
      headers:{'X-Requested-With':'XMLHttpRequest','Cache-Control':'no-store'},
      credentials: 'same-origin'
    });
    const txt = await res.text();
    try { return JSON.parse(txt); } catch(_){ return {items:[]}; }
  }

  // Render detail baris: Event · Jumlah · Status (fallback: tebak dari link)
  function detailLine(n){
    let eventName = pick(n, ['event_title','event','eventName','context','description']);
    let amount    = pick(n, ['amount','jumlah']);
    let status    = pick(n, ['status','state']) || '';

    // fallback: coba deteksi dari link
    if (!eventName && n.link){
      if (/\/(audience|presenter)\/pembayaran\/detail\/\d+/.test(n.link)) eventName = 'Pembayaran';
      else if (/\/(audience|presenter)\/events\/detail\/\d+/.test(n.link)) eventName = 'Event';
      else if (/\/(audience|presenter)\/abstrak\//.test(n.link))          eventName = 'Abstrak';
    }

    const parts = [];
    if (eventName) parts.push('Event: '+eventName);
    if (amount)    parts.push('Jumlah: '+amount);
    if (status)    parts.push('Status: '+status);
    return parts.length ? parts.join(' · ') : '';
  }

  function iconClass(t){
    switch(t){
      case 'payment':      return 'bi-cash-coin text-success';
      case 'registration': return 'bi-person-check text-primary';
      case 'deadline':     return 'bi-exclamation-triangle text-warning';
      case 'event':        return 'bi-calendar3 text-info';
      default:             return 'bi-info-circle text-primary';
    }
  }

  function render(items){
    LIST.innerHTML = '';
    if (!items || items.length === 0){
      LIST.innerHTML = '<div class="small text-muted">Tidak ada aktivitas.</div>';
      return;
    }
    items.forEach(n=>{
      const wrap = document.createElement('div');
      wrap.className = 'notice';
      const title = esc(n.title || '-');
      const time  = esc(n.time || '');
      const det   = esc(detailLine(n));
      const href  = normalizeLink(n.link || '#');

      wrap.innerHTML = `
        <div class="d-flex align-items-start gap-2">
          <i class="bi ${iconClass(n.type)} mt-1"></i>
          <div class="flex-fill">
            <div class="title text-truncate">${title}</div>
            ${det ? `<div class="meta">${det}</div>` : ''}
            ${time ? `<div class="meta">${time}</div>` : ''}
          </div>
          ${n.link ? `<span class="small text-primary">Buka</span>` : ''}
          ${n.link ? `<a class="stretched-link" href="${esc(href)}" target="_self" rel="noopener"></a>` : ''}
        </div>
      `;
      LIST.appendChild(wrap);
    });
  }

  (async function(){
    LIST.innerHTML = '<div class="small text-muted">Memuat...</div>';
    try { const data = await getJSON(URL); render(data.items || []); }
    catch(e){ LIST.innerHTML = '<div class="text-danger small">Gagal memuat aktivitas.</div>'; }
  })();
})();
</script>
