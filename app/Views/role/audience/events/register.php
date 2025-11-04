<?php
  $title   = 'Pilih Mode Kehadiran';
  $event   = $event ?? [];
  $options = $options ?? [];    // contoh: ['online','offline']
  $pricing = $pricing ?? [];    // ['audience'=>['online'=>..., 'offline'=>...]]
  $rupiah  = function($n){ return ($n===null||$n==='') ? '—' : 'Rp '.number_format((float)$n,0,',','.'); };
  $priceOnline  = $pricing['audience']['online']  ?? null;
  $priceOffline = $pricing['audience']['offline'] ?? null;
  
  $eventFormat = strtolower($event['format'] ?? '');
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- Back Button -->
      <div class="mb-3">
        <a href="<?= site_url('audience/events/detail/'.($event['id'] ?? 0)) ?>" class="btn-back">
          <i class="bi bi-arrow-left"></i>
          <span>Kembali ke Detail Event</span>
        </a>
      </div>

      <!-- Event Summary Card -->
      <div class="event-summary-card mb-4">
        <div class="summary-header">
          <i class="bi bi-info-circle-fill"></i>
          <h5>Ringkasan Event</h5>
        </div>
        <div class="summary-body">
          <div class="event-title">
            <h4><?= esc($event['title'] ?? 'Event') ?></h4>
          </div>
          
          <div class="event-meta">
            <div class="meta-item">
              <i class="bi bi-calendar-event"></i>
              <span><?= esc(isset($event['event_date']) ? date('d M Y', strtotime($event['event_date'])) : '-') ?></span>
            </div>
            <div class="meta-item">
              <i class="bi bi-clock-fill"></i>
              <span><?= esc($event['event_time'] ?? '-') ?></span>
            </div>
            <div class="meta-item meta-format">
              <?php if($eventFormat === 'online'): ?>
                <i class="bi bi-wifi"></i>
                <span>Online</span>
              <?php elseif($eventFormat === 'offline'): ?>
                <i class="bi bi-geo-alt-fill"></i>
                <span>Offline</span>
              <?php else: ?>
                <i class="bi bi-arrow-left-right"></i>
                <span>Hybrid</span>
              <?php endif; ?>
            </div>
            <?php if (!empty($event['location'])): ?>
              <div class="meta-item">
                <i class="bi bi-pin-map-fill"></i>
                <span><?= esc($event['location']) ?></span>
              </div>
            <?php endif; ?>
          </div>

          <div class="price-summary">
            <div class="price-label">Harga Tiket Audience</div>
            <div class="price-badges">
              <?php if($priceOnline !== null && $priceOnline !== '' && (float)$priceOnline > 0): ?>
              <div class="price-badge badge-online">
                <i class="bi bi-wifi"></i>
                <span class="badge-label">Online:</span>
                <span class="badge-price">
                  <strong><?= $rupiah($priceOnline) ?></strong>
                </span>
              </div>
              <?php endif; ?>
              
              <?php if($priceOffline !== null && $priceOffline !== '' && (float)$priceOffline > 0): ?>
              <div class="price-badge badge-offline">
                <i class="bi bi-people-fill"></i>
                <span class="badge-label">Offline:</span>
                <span class="badge-price">
                  <strong><?= $rupiah($priceOffline) ?></strong>
                </span>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Mode Selection Card -->
      <div class="mode-selection-card">
        <div class="selection-header">
          <i class="bi bi-check-square-fill"></i>
          <h5>Pilih Mode Kehadiran</h5>
        </div>
        <div class="selection-body">
          <form id="regForm" action="<?= site_url('audience/events/register/'.($event['id'] ?? 0)) ?>" method="post">
            <?= csrf_field() ?>

            <?php if (empty($options)): ?>
              <div class="alert-empty">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div>
                  <strong>Mode Tidak Tersedia</strong>
                  <p>Mode kehadiran tidak tersedia untuk event ini.</p>
                </div>
              </div>
            <?php else: ?>

              <div class="mode-options">
                <?php foreach ($options as $opt): 
                  $price = $pricing['audience'][$opt] ?? null;
                  
                  // Skip jika harga tidak tersedia (null, empty, atau 0)
                  if ($price === null || $price === '' || (float)$price == 0) continue;
                  
                  $icon  = $opt === 'online' ? 'bi-wifi' : 'bi-people-fill';
                  $colorClass = $opt === 'online' ? 'option-online' : 'option-offline';
                ?>
                <label class="mode-option-card <?= $colorClass ?>" for="mode_<?= esc($opt) ?>">
                  <div class="option-radio">
                    <input class="form-check-input" type="radio" name="mode_kehadiran"
                           id="mode_<?= esc($opt) ?>" value="<?= esc($opt) ?>" required>
                  </div>
                  
                  <div class="option-icon">
                    <i class="bi <?= $icon ?>"></i>
                  </div>
                  
                  <div class="option-content">
                    <div class="option-title"><?= esc(ucfirst($opt)) ?></div>
                    <div class="option-price">
                      <span class="price-amount"><?= $rupiah($price) ?></span>
                    </div>
                    <div class="option-description">
                      <?php if($opt === 'online'): ?>
                        Ikuti event secara virtual dari mana saja
                      <?php else: ?>
                        Hadir langsung di lokasi event
                      <?php endif; ?>
                    </div>
                  </div>
                  
                  <div class="option-check">
                    <i class="bi bi-check-circle-fill"></i>
                  </div>
                </label>
                <?php endforeach; ?>
              </div>

              <div class="form-actions">
                <button type="button" id="btnSubmit" class="btn-submit">
                  <i class="bi bi-arrow-right-circle-fill"></i>
                  <span>Lanjut ke Pembayaran</span>
                </button>
                <a href="<?= site_url('audience/events/detail/'.($event['id'] ?? 0)) ?>" class="btn-cancel">
                  <i class="bi bi-x-circle"></i>
                  <span>Batal</span>
                </a>
              </div>
            <?php endif; ?>
          </form>
        </div>
      </div>

    </div>
  </main>
</div>

<style>
  :root {
    --primary-blue: #2563eb;
    --primary-blue-dark: #1e40af;
    --blue-50: #eff6ff;
    --blue-100: #dbeafe;
    --blue-200: #bfdbfe;
    --success: #10b981;
    --warning: #f59e0b;
    --gray-50: #f9fafb;
    --gray-100: #f3f4f6;
    --gray-200: #e5e7eb;
    --gray-600: #4b5563;
    --gray-700: #374151;
    --gray-900: #111827;
  }

  body { 
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 50%, #f0f9ff 100%); 
    min-height: 100vh; 
  }

  /* BACK BUTTON */
  .btn-back {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: white;
    color: var(--primary-blue);
    border: 2px solid var(--blue-200);
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.1);
  }
  .btn-back:hover {
    background: var(--blue-50);
    border-color: var(--primary-blue);
    transform: translateX(-4px);
    color: var(--primary-blue-dark);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
  }

  /* EVENT SUMMARY CARD */
  .event-summary-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    border: 1px solid rgba(37, 99, 235, 0.08);
  }
  .summary-header {
    background: linear-gradient(135deg, var(--primary-blue), var(--primary-blue-dark));
    color: white;
    padding: 1.25rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }
  .summary-header i {
    font-size: 1.4rem;
  }
  .summary-header h5 {
    margin: 0;
    font-weight: 700;
    font-size: 1.1rem;
  }
  .summary-body {
    padding: 1.5rem;
  }
  .event-title h4 {
    color: var(--gray-900);
    font-weight: 800;
    font-size: 1.5rem;
    margin-bottom: 1rem;
  }
  .event-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 2px dashed var(--gray-200);
  }
  .meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: var(--gray-50);
    border-radius: 10px;
    font-size: 0.875rem;
    color: var(--gray-700);
  }
  .meta-item i {
    color: var(--primary-blue);
    font-size: 1rem;
  }
  .meta-format {
    background: linear-gradient(135deg, var(--blue-50), var(--blue-100));
    font-weight: 600;
  }
  .price-summary {
    background: linear-gradient(135deg, var(--gray-50), white);
    padding: 1.25rem;
    border-radius: 12px;
    border: 2px solid var(--gray-200);
  }
  .price-label {
    font-size: 0.85rem;
    color: var(--gray-600);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.75rem;
  }
  .price-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
  }
  .price-badge {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    border-radius: 10px;
    font-size: 0.875rem;
    flex: 1;
    min-width: 200px;
  }
  .badge-online {
    background: linear-gradient(135deg, var(--blue-50), var(--blue-100));
    border: 2px solid var(--blue-200);
  }
  .badge-offline {
    background: linear-gradient(135deg, #fffbeb, #fef3c7);
    border: 2px solid #fde68a;
  }
  .price-badge i {
    font-size: 1.2rem;
  }
  .badge-online i {
    color: var(--primary-blue);
  }
  .badge-offline i {
    color: #d97706;
  }
  .badge-label {
    color: var(--gray-600);
    font-weight: 600;
  }
  .badge-price {
    margin-left: auto;
  }
  .badge-price strong {
    color: var(--gray-900);
    font-size: 1rem;
  }

  /* MODE SELECTION CARD */
  .mode-selection-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    border: 1px solid rgba(37, 99, 235, 0.08);
  }
  .selection-header {
    background: linear-gradient(135deg, var(--primary-blue), var(--primary-blue-dark));
    color: white;
    padding: 1.25rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }
  .selection-header i {
    font-size: 1.4rem;
  }
  .selection-header h5 {
    margin: 0;
    font-weight: 700;
    font-size: 1.1rem;
  }
  .selection-body {
    padding: 1.5rem;
  }

  /* MODE OPTIONS */
  .mode-options {
    display: grid;
    gap: 1rem;
    margin-bottom: 1.5rem;
  }
  .mode-option-card {
    position: relative;
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    border: 3px solid;
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
  }
  .mode-option-card.option-online {
    border-color: var(--blue-200);
  }
  .mode-option-card.option-offline {
    border-color: #fde68a;
  }
  .mode-option-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1);
  }
  .mode-option-card.option-online:hover {
    border-color: var(--primary-blue);
    background: var(--blue-50);
    box-shadow: 0 12px 30px rgba(37, 99, 235, 0.2);
  }
  .mode-option-card.option-offline:hover {
    border-color: #fbbf24;
    background: #fffbeb;
    box-shadow: 0 12px 30px rgba(251, 191, 36, 0.2);
  }
  
  /* Checked state */
  .mode-option-card:has(input:checked) {
    transform: translateY(-4px);
  }
  .mode-option-card.option-online:has(input:checked) {
    border-color: var(--primary-blue);
    background: var(--blue-50);
    box-shadow: 0 12px 30px rgba(37, 99, 235, 0.25);
  }
  .mode-option-card.option-offline:has(input:checked) {
    border-color: #fbbf24;
    background: #fffbeb;
    box-shadow: 0 12px 30px rgba(251, 191, 36, 0.25);
  }
  .mode-option-card:has(input:checked) .option-check {
    opacity: 1;
    transform: scale(1);
  }
  .mode-option-card:has(input:checked) .option-icon {
    transform: scale(1.1);
  }

  .option-radio {
    flex-shrink: 0;
  }
  .option-radio input {
    width: 22px;
    height: 22px;
    cursor: pointer;
    border: 2px solid var(--gray-300);
  }
  .option-radio input:checked {
    background-color: var(--primary-blue);
    border-color: var(--primary-blue);
  }
  .option-icon {
    width: 60px;
    height: 60px;
    border-radius: 14px;
    display: grid;
    place-items: center;
    font-size: 1.75rem;
    flex-shrink: 0;
    transition: all 0.3s ease;
  }
  .option-online .option-icon {
    background: linear-gradient(135deg, var(--blue-100), #bfdbfe);
    color: var(--primary-blue);
  }
  .option-offline .option-icon {
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    color: #d97706;
  }
  .option-content {
    flex: 1;
  }
  .option-title {
    font-size: 1.1rem;
    font-weight: 800;
    color: var(--gray-900);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.35rem;
  }
  .option-price {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: 0.35rem;
  }
  .price-amount {
    color: var(--gray-900);
  }
  .option-description {
    font-size: 0.85rem;
    color: var(--gray-600);
  }
  .option-check {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--success);
    color: white;
    display: grid;
    place-items: center;
    font-size: 1.2rem;
    flex-shrink: 0;
    opacity: 0;
    transform: scale(0.8);
    transition: all 0.3s ease;
  }

  /* ALERT EMPTY */
  .alert-empty {
    display: flex;
    align-items: start;
    gap: 1rem;
    padding: 1.5rem;
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    border: 2px solid #fbbf24;
    border-radius: 12px;
    color: #92400e;
  }
  .alert-empty i {
    font-size: 1.5rem;
    color: #d97706;
    flex-shrink: 0;
  }
  .alert-empty strong {
    display: block;
    margin-bottom: 0.25rem;
    font-size: 1rem;
  }
  .alert-empty p {
    margin: 0;
    font-size: 0.9rem;
  }

  /* FORM ACTIONS */
  .form-actions {
    display: flex;
    gap: 1rem;
  }
  .btn-submit,
  .btn-cancel {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.95rem 1.75rem;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1rem;
    text-decoration: none;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
  }
  .btn-submit {
    flex: 1;
    background: linear-gradient(135deg, var(--primary-blue), var(--primary-blue-dark));
    color: white;
    box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
  }
  .btn-submit:hover {
    background: linear-gradient(135deg, var(--primary-blue-dark), #1e3a8a);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(37, 99, 235, 0.4);
  }
  .btn-cancel {
    background: white;
    color: var(--gray-700);
    border: 2px solid var(--gray-200);
  }
  .btn-cancel:hover {
    background: var(--gray-50);
    border-color: var(--gray-300);
    color: var(--gray-900);
  }

  /* RESPONSIVE */
  @media (max-width: 768px) {
    .event-title h4 {
      font-size: 1.25rem;
    }
    .event-meta {
      gap: 0.5rem;
    }
    .meta-item {
      font-size: 0.8rem;
      padding: 0.4rem 0.75rem;
    }
    .price-badges {
      flex-direction: column;
    }
    .price-badge {
      min-width: 100%;
    }
    .mode-option-card {
      padding: 1.25rem;
    }
    .option-icon {
      width: 50px;
      height: 50px;
      font-size: 1.5rem;
    }
    .option-title {
      font-size: 1rem;
    }
    .option-price {
      font-size: 1.1rem;
    }
    .form-actions {
      flex-direction: column;
    }
    .btn-submit,
    .btn-cancel {
      width: 100%;
    }
  }

  @media (max-width: 576px) {
    .mode-option-card {
      gap: 0.75rem;
    }
    .option-content {
      min-width: 0;
    }
    .option-check {
      width: 28px;
      height: 28px;
      font-size: 1rem;
    }
  }
</style>

<script>
document.getElementById('btnSubmit')?.addEventListener('click', function(){
  const f = document.getElementById('regForm');
  const selectedMode = f.querySelector('input[name="mode_kehadiran"]:checked');
  
  if (!selectedMode) {
    if (window.Swal) {
      Swal.fire({
        title: 'Pilih Mode Kehadiran',
        text: 'Silakan pilih mode kehadiran terlebih dahulu (Online atau Offline)',
        icon: 'warning',
        confirmButtonText: 'OK',
        confirmButtonColor: '#2563eb'
      });
    } else {
      alert('Silakan pilih mode kehadiran terlebih dahulu');
    }
    return;
  }

  const go = ()=> f.submit();
  const modeName = selectedMode.value.toUpperCase();
  
  if (window.Swal) {
    Swal.fire({
      title: 'Konfirmasi Pendaftaran',
      html: `<div style="margin:1rem 0;line-height:1.6;color:#6b7280;">
        <p>Anda memilih mode kehadiran: <strong style="color:#2563eb;">${modeName}</strong></p>
        <p>Lanjut ke pembayaran untuk menyelesaikan registrasi.</p>
      </div>`,
      icon: 'question',
      iconColor: '#2563eb',
      showCancelButton: true,
      confirmButtonText: '<i class="bi bi-check-circle me-2"></i>Ya, Lanjutkan',
      cancelButtonText: '<i class="bi bi-x-circle me-2"></i>Batal',
      confirmButtonColor: '#2563eb',
      cancelButtonColor: '#6b7280'
    }).then(r=>{ if(r.isConfirmed) go(); });
  } else {
    if (confirm(`Anda memilih ${modeName}. Lanjut ke pembayaran?`)) go();
  }
});
</script>

<?= $this->include('partials/footer') ?>