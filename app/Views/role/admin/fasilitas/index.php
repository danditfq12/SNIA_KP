<?php
$title = $title ?? 'Kelola Fasilitas & Benefit';
$fasilitas = $fasilitas ?? [];
$events = $events ?? [];
$stats = $stats ?? [];
$total = (int)($stats['total_events_with_facilities'] ?? 0);

// Build type counts from stats
$typeLabels = [
    'presenter_online' => ['label' => 'Presenter Online', 'icon' => 'bi-laptop', 'color' => 'success'],
    'presenter_offline' => ['label' => 'Presenter Offline', 'icon' => 'bi-person-video3', 'color' => 'info'],
    'audience_online' => ['label' => 'Audience Online', 'icon' => 'bi-people', 'color' => 'warning'],
    'audience_offline' => ['label' => 'Audience Offline', 'icon' => 'bi-people-fill', 'color' => 'danger'],
];

$byType = [];
foreach ($stats['by_type'] ?? [] as $item) {
    $byType[$item['participant_type']] = $item['total'];
}
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_admin') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl px-3 px-md-4 py-4">

      <!-- ===== HERO ===== -->
      <div class="card-hero mb-4">
        <div class="hero-body">
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
              <h3 class="hero-title mb-1"><i class="bi bi-star-fill me-2"></i><?= esc($title) ?></h3>
              <div class="text-white-70 small">Atur fasilitas dan benefit untuk setiap tipe partisipan di event.</div>
            </div>

            <div class="hero-tools flex-grow-1" style="max-width:620px;">
              <div class="input-group input-group-lg hero-search">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input id="searchInput" type="text" class="form-control" placeholder="Cari event atau tipe partisipan…">
                <button id="clearSearch" type="button" class="btn btn-light d-none"><i class="bi bi-x-circle"></i></button>
              </div>
            </div>
          </div>

          <div class="d-flex flex-wrap mt-3 gap-2 align-items-center">
            <span class="chip glass-chip">
              <i class="bi bi-calendar-event me-1"></i>Event
              <strong class="ms-2"><?= number_format($total) ?></strong>
            </span>

            <?php foreach (array_slice($typeLabels, 0, 2) as $type => $info): ?>
            <span class="chip glass-chip">
              <i class="<?= $info['icon'] ?> me-1"></i><?= $info['label'] ?>
              <strong class="ms-2"><?= $byType[$type] ?? 0 ?></strong>
            </span>
            <?php endforeach; ?>

            <div class="ms-auto d-flex flex-wrap gap-2">
              <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambahFasilitas">
                <i class="bi bi-plus-circle me-1"></i>Tambah
              </button>
            </div>
          </div>
        </div>

        <!-- Tabs filter -->
        <div class="hero-tabs">
          <ul class="nav nav-pills" id="filterTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" data-filter="all" type="button" role="tab">
                Semua <span class="badge bg-light text-primary ms-1" id="countAll"><?= count($fasilitas) ?></span>
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" data-filter="presenter_online" type="button" role="tab">
                Presenter Online <span class="badge bg-light text-success ms-1" id="countPresOnline">0</span>
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" data-filter="presenter_offline" type="button" role="tab">
                Presenter Offline <span class="badge bg-light text-info ms-1" id="countPresOffline">0</span>
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" data-filter="audience_online" type="button" role="tab">
                Audience Online <span class="badge bg-light text-warning ms-1" id="countAudOnline">0</span>
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" data-filter="audience_offline" type="button" role="tab">
                Audience Offline <span class="badge bg-light text-danger ms-1" id="countAudOffline">0</span>
              </button>
            </li>
          </ul>
        </div>
      </div>

      <!-- ===== GRID KARTU ===== -->
      <div id="gridWrap" class="row g-3 row-cols-1 row-cols-md-2 row-cols-xl-3">
        <?php if (empty($fasilitas)): ?>
          <div class="col">
            <div class="empty-hint"><i class="bi bi-inbox me-1"></i>Belum ada fasilitas. Tambahkan dulu ya!</div>
          </div>
        <?php else: foreach ($fasilitas as $item): ?>
          <?php
            $id = (int)($item['id'] ?? 0);
            $eventTitle = (string)($item['event_title'] ?? '-');
            $eventDate = (string)($item['event_date'] ?? '');
            $participantType = (string)($item['participant_type'] ?? '');
            $fasilitasArray = is_array($item['fasilitas']) ? $item['fasilitas'] : [];
            $isActive = (bool)($item['is_active'] ?? true);
            
            $searchable = strtolower($eventTitle.' '.$participantType);
            $pillClass = $isActive ? 'pill-success' : 'pill-muted';
            $pillText = $isActive ? 'Aktif' : 'Nonaktif';
            
            // Type badge dengan warna yang lebih terlihat
            $typeBadges = [
                'presenter_online' => '<span class="type-badge badge-pres-online"><i class="bi bi-laptop me-1"></i>Presenter Online</span>',
                'presenter_offline' => '<span class="type-badge badge-pres-offline"><i class="bi bi-person-video3 me-1"></i>Presenter Offline</span>',
                'audience_online' => '<span class="type-badge badge-aud-online"><i class="bi bi-people me-1"></i>Audience Online</span>',
                'audience_offline' => '<span class="type-badge badge-aud-offline"><i class="bi bi-people-fill me-1"></i>Audience Offline</span>',
            ];
            $typeBadge = $typeBadges[$participantType] ?? '<span class="type-badge">'.$participantType.'</span>';
          ?>
          <div class="col">
            <div class="fp-card js-card" data-id="<?= $id ?>" data-search="<?= esc($searchable) ?>" data-type="<?= esc($participantType) ?>">
              
              <div class="fp-head">
                <h6 class="mb-0 fp-title" title="<?= esc($eventTitle) ?>"><?= esc($eventTitle) ?></h6>
                <span class="status-pill <?= $pillClass ?> small pill-state"><?= $pillText ?></span>
              </div>

              <ul class="meta-list">
                <li>
                  <span>Tanggal</span>
                  <strong><?= $eventDate ? date('d M Y', strtotime($eventDate)) : '—' ?></strong>
                </li>
                <li>
                  <span>Tipe</span>
                  <div><?= $typeBadge ?></div>
                </li>
                <li>
                  <span>Jumlah</span>
                  <strong class="text-primary"><?= count($fasilitasArray) ?> fasilitas</strong>
                </li>
              </ul>

              <div class="facilities-preview mb-2">
                <small class="text-muted d-block mb-1 fw-semibold">Fasilitas:</small>
                <ul class="small mb-0 ps-3">
                  <?php 
                  $shown = array_slice($fasilitasArray, 0, 2);
                  foreach ($shown as $f): ?>
                    <li><?= esc($f) ?></li>
                  <?php endforeach; ?>
                  <?php if (count($fasilitasArray) > 2): ?>
                    <li class="text-muted fst-italic">+<?= count($fasilitasArray) - 2 ?> lainnya</li>
                  <?php endif; ?>
                </ul>
              </div>

              <div class="fp-foot">
                <button class="btn btn-primary flex-fill btn-sm btn-view" data-id="<?= $id ?>">
                  <i class="bi bi-eye me-1"></i>Lihat
                </button>
                <button class="btn btn-outline-warning flex-fill btn-sm btn-edit" data-id="<?= $id ?>">
                  <i class="bi bi-pencil me-1"></i>Edit
                </button>
                <button class="btn btn-outline-danger btn-sm btn-delete" data-id="<?= $id ?>">
                  <i class="bi bi-trash"></i>
                </button>
              </div>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>

    </div>
  </main>
</div>

<!-- ===== MODAL TAMBAH ===== -->
<div class="modal fade" id="modalTambahFasilitas" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content modal-paper">
      <div class="modal-header modal-paper__header glass-header">
        <h5 class="modal-title text-white"><i class="bi bi-plus-circle me-2"></i>Tambah Fasilitas</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form id="formTambahFasilitas" novalidate>
        <?= csrf_field() ?>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Event <span class="text-danger">*</span></label>
            <select name="event_id" class="form-select" required>
              <option value="">-- Pilih Event --</option>
              <?php foreach ($events as $event): ?>
              <option value="<?= $event['id'] ?>">
                <?= esc($event['title']) ?> 
                (<?= date('d M Y', strtotime($event['event_date'])) ?>)
              </option>
              <?php endforeach; ?>
            </select>
            <div class="invalid-feedback"></div>
          </div>

          <div class="mb-3">
            <label class="form-label">Tipe Partisipan <span class="text-danger">*</span></label>
            <select name="participant_type" class="form-select" required>
              <option value="">-- Pilih Tipe --</option>
              <option value="presenter_online">🖥️ Presenter Online</option>
              <option value="presenter_offline">👨‍🏫 Presenter Offline</option>
              <option value="audience_online">👥 Audience Online</option>
              <option value="audience_offline">👥 Audience Offline</option>
            </select>
            <div class="invalid-feedback"></div>
          </div>

          <div class="mb-3">
            <label class="form-label">Fasilitas <span class="text-danger">*</span></label>
            <small class="text-muted d-block mb-2">Masukkan satu fasilitas per baris</small>
            <div id="fasilitasInputs">
              <div class="input-group mb-2">
                <span class="input-group-text"><i class="bi bi-check-circle"></i></span>
                <input type="text" class="form-control fasilitas-item" placeholder="Contoh: E-Certificate">
                <button type="button" class="btn btn-outline-success btn-add-item">
                  <i class="bi bi-plus"></i>
                </button>
              </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary mt-1" id="addMoreBtn">
              <i class="bi bi-plus-circle me-1"></i>Tambah Item
            </button>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">
            <span class="spinner-border spinner-border-sm me-1 d-none" id="loadingTambah"></span>
            <i class="bi bi-save me-1"></i>Simpan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ===== MODAL VIEW ===== -->
<div class="modal fade" id="modalView" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content modal-paper">
      <div class="modal-header modal-paper__header glass-header">
        <h5 class="modal-title text-white"><i class="bi bi-eye me-2"></i>Detail Fasilitas</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="viewContent">
        <!-- Content will be loaded here -->
      </div>
    </div>
  </div>
</div>

<!-- ===== MODAL EDIT ===== -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content modal-paper">
      <div class="modal-header modal-paper__header glass-header">
        <h5 class="modal-title text-white"><i class="bi bi-pencil me-2"></i>Edit Fasilitas</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form id="formEdit" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="editId">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Event</label>
            <input type="text" class="form-control" id="editEvent" readonly>
          </div>

          <div class="mb-3">
            <label class="form-label">Tipe Partisipan</label>
            <input type="text" class="form-control" id="editType" readonly>
          </div>

          <div class="mb-3">
            <label class="form-label">Fasilitas <span class="text-danger">*</span></label>
            <div id="editFasilitasInputs">
              <!-- Will be populated -->
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="editAddMoreBtn">
              <i class="bi bi-plus-circle me-1"></i>Tambah Item
            </button>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-warning">
            <span class="spinner-border spinner-border-sm me-1 d-none" id="loadingEdit"></span>
            <i class="bi bi-save me-1"></i>Update
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?= $this->include('partials/footer') ?>

<!-- ===== JAVASCRIPT ===== -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.1/sweetalert2.min.js"></script>
<script>
(function(){
  const BASE_URL = '<?= site_url('admin/fasilitas') ?>';
  const input = document.getElementById('searchInput');
  const clearBtn = document.getElementById('clearSearch');
  const grid = document.getElementById('gridWrap');
  const tabs = [...document.querySelectorAll('#filterTabs .nav-link')];

  function allCards(){ return [...grid.querySelectorAll('.js-card')]; }

  function updateCounts(){
    const cards = allCards();
    let all=cards.length, presOn=0, presOff=0, audOn=0, audOff=0;
    cards.forEach(c => {
      const t = c.dataset.type;
      if(t==='presenter_online') presOn++;
      else if(t==='presenter_offline') presOff++;
      else if(t==='audience_online') audOn++;
      else if(t==='audience_offline') audOff++;
    });
    document.getElementById('countAll').textContent = all;
    document.getElementById('countPresOnline').textContent = presOn;
    document.getElementById('countPresOffline').textContent = presOff;
    document.getElementById('countAudOnline').textContent = audOn;
    document.getElementById('countAudOffline').textContent = audOff;
  }

  function applyFilter(){
    const activeTab = document.querySelector('#filterTabs .nav-link.active');
    const filter = activeTab?.dataset.filter || 'all';
    const query = (input?.value || '').trim().toLowerCase();

    allCards().forEach(card=>{
      const type = card.dataset.type;
      const hay = (card.dataset.search || card.textContent).toLowerCase();
      const matchSearch = (!query || hay.includes(query));
      const matchFilter = (filter==='all') || (type === filter);
      card.closest('.col').style.display = (matchSearch && matchFilter) ? '' : 'none';
    });
    clearBtn?.classList.toggle('d-none', !query);
    updateCounts();
  }

  input?.addEventListener('input', applyFilter);
  clearBtn?.addEventListener('click', ()=>{ input.value=''; applyFilter(); input.focus(); });
  tabs.forEach(btn=>{
    btn.addEventListener('click', ()=>{
      tabs.forEach(b=>b.classList.remove('active'));
      btn.classList.add('active');
      applyFilter();
    });
  });

  // Add More Item
  const fasilitasInputsContainer = document.getElementById('fasilitasInputs');
  const addMoreBtnElement = document.getElementById('addMoreBtn');
  
  if (addMoreBtnElement) {
    addMoreBtnElement.addEventListener('click', function() {
      const html = `
        <div class="input-group mb-2">
          <span class="input-group-text"><i class="bi bi-check-circle"></i></span>
          <input type="text" class="form-control fasilitas-item" placeholder="Masukkan fasilitas">
          <button type="button" class="btn btn-outline-danger btn-remove-item">
            <i class="bi bi-trash"></i>
          </button>
        </div>
      `;
      if (fasilitasInputsContainer) {
        fasilitasInputsContainer.insertAdjacentHTML('beforeend', html);
      }
    });
  }

  if (fasilitasInputsContainer) {
    fasilitasInputsContainer.addEventListener('click', function(e) {
      if (e.target.closest('.btn-add-item')) {
        const html = `
          <div class="input-group mb-2">
            <span class="input-group-text"><i class="bi bi-check-circle"></i></span>
            <input type="text" class="form-control fasilitas-item" placeholder="Masukkan fasilitas">
            <button type="button" class="btn btn-outline-danger btn-remove-item">
              <i class="bi bi-trash"></i>
            </button>
          </div>
        `;
        fasilitasInputsContainer.insertAdjacentHTML('beforeend', html);
      }
      
      if (e.target.closest('.btn-remove-item')) {
        const inputGroup = e.target.closest('.input-group');
        if (inputGroup) inputGroup.remove();
      }
    });
  }

  // Submit Add
  $('#formTambahFasilitas').on('submit', function(e) {
    e.preventDefault();

    const fasilitasItems = [];
    $('.fasilitas-item').each(function() {
      const val = $(this).val().trim();
      if (val) fasilitasItems.push(val);
    });

    if (fasilitasItems.length === 0) {
      showAlert('error', 'Minimal 1 fasilitas harus diisi');
      return;
    }

    const form = $(this), btn = form.find('button[type="submit"]'), spin = $('#loadingTambah');
    form.find('.is-invalid').removeClass('is-invalid');
    spin.removeClass('d-none'); btn.prop('disabled', true);

    const data = {
      event_id: $('[name="event_id"]').val(),
      participant_type: $('[name="participant_type"]').val(),
      fasilitas: JSON.stringify(fasilitasItems),
      '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
    };

    $.ajax({
      url: BASE_URL + '/store',
      method: 'POST',
      data: data,
      dataType: 'json',
      headers: {'X-Requested-With': 'XMLHttpRequest'},
      success: function(res) {
        if (res.success) {
          $('#modalTambahFasilitas').modal('hide');
          showAlert('success', res.message || 'Fasilitas berhasil ditambahkan');
          setTimeout(() => location.reload(), 800);
        } else {
          if(res.errors) handleFormErrors(form, res.errors);
          else showAlert('error', res.message || 'Terjadi kesalahan');
        }
      },
      error: function() { showAlert('error', 'Gagal mengirim data'); },
      complete: function() { spin.addClass('d-none'); btn.prop('disabled', false); }
    });
  });

  // View Detail
  $(document).on('click', '.btn-view', function() {
    const id = $(this).data('id');

    $.ajax({
      url: BASE_URL + '/detail/' + id,
      method: 'GET',
      dataType: 'json',
      headers: {'X-Requested-With': 'XMLHttpRequest'},
      success: function(res) {
        if (res.success) {
          const item = res.data;
          const typeLabels = {
            'presenter_online': '🖥️ Presenter Online',
            'presenter_offline': '👨‍🏫 Presenter Offline',
            'audience_online': '👥 Audience Online',
            'audience_offline': '👥 Audience Offline'
          };
          
          let html = `
            <div class="row g-3">
              <div class="col-md-6">
                <strong class="d-block text-muted mb-1">Event:</strong>
                <div>${item.event_title}</div>
              </div>
              <div class="col-md-6">
                <strong class="d-block text-muted mb-1">Tipe Partisipan:</strong>
                <div>${typeLabels[item.participant_type] || item.participant_type}</div>
              </div>
            </div>
            <hr>
            <div class="mb-3">
              <strong class="d-block text-muted mb-2">Fasilitas & Benefit:</strong>
              <ul class="list-group">
          `;
          
          item.fasilitas.forEach(f => {
            html += `<li class="list-group-item"><i class="bi bi-check-circle-fill text-success me-2"></i>${f}</li>`;
          });
          
          html += `</ul></div>`;
          
          $('#viewContent').html(html);
          $('#modalView').modal('show');
        } else {
          showAlert('error', res.message || 'Data tidak ditemukan');
        }
      },
      error: function() { showAlert('error', 'Gagal memuat data'); }
    });
  });

  // Edit
  $(document).on('click', '.btn-edit', function() {
    const id = $(this).data('id');

    $.ajax({
      url: BASE_URL + '/detail/' + id,
      method: 'GET',
      dataType: 'json',
      headers: {'X-Requested-With': 'XMLHttpRequest'},
      success: function(res) {
        if (res.success) {
          const item = res.data;
          const typeLabels = {
            'presenter_online': '🖥️ Presenter Online',
            'presenter_offline': '👨‍🏫 Presenter Offline',
            'audience_online': '👥 Audience Online',
            'audience_offline': '👥 Audience Offline'
          };
          
          $('#editId').val(item.id);
          $('#editEvent').val(item.event_title);
          $('#editType').val(typeLabels[item.participant_type] || item.participant_type);

          let inputsHtml = '';
          item.fasilitas.forEach((f, i) => {
            inputsHtml += `
              <div class="input-group mb-2">
                <span class="input-group-text"><i class="bi bi-check-circle"></i></span>
                <input type="text" class="form-control edit-fasilitas-item" value="${f}">
                <button type="button" class="btn btn-outline-danger btn-remove-edit-item">
                  <i class="bi bi-trash"></i>
                </button>
              </div>
            `;
          });

          $('#editFasilitasInputs').html(inputsHtml);
          $('#modalEdit').modal('show');
        } else {
          showAlert('error', res.message || 'Data tidak ditemukan');
        }
      },
      error: function() { showAlert('error', 'Gagal memuat data'); }
    });
  });

  $('#editAddMoreBtn').on('click', function() {
    const html = `
      <div class="input-group mb-2">
        <span class="input-group-text"><i class="bi bi-check-circle"></i></span>
        <input type="text" class="form-control edit-fasilitas-item" placeholder="Masukkan fasilitas">
        <button type="button" class="btn btn-outline-danger btn-remove-edit-item">
          <i class="bi bi-trash"></i>
        </button>
      </div>
    `;
    $('#editFasilitasInputs').append(html);
  });

  $(document).on('click', '.btn-remove-edit-item', function() {
    $(this).closest('.input-group').remove();
  });

  // Submit Edit
  $('#formEdit').on('submit', function(e) {
    e.preventDefault();

    const fasilitasItems = [];
    $('.edit-fasilitas-item').each(function() {
      const val = $(this).val().trim();
      if (val) fasilitasItems.push(val);
    });

    if (fasilitasItems.length === 0) {
      showAlert('error', 'Minimal 1 fasilitas harus diisi');
      return;
    }

    const form = $(this), id = $('#editId').val();
    const btn = form.find('button[type="submit"]'), spin = $('#loadingEdit');
    form.find('.is-invalid').removeClass('is-invalid');
    spin.removeClass('d-none'); btn.prop('disabled', true);

    const data = {
      fasilitas: JSON.stringify(fasilitasItems),
      '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
    };

    $.ajax({
      url: BASE_URL + '/update/' + id,
      method: 'POST',
      data: data,
      dataType: 'json',
      headers: {'X-Requested-With': 'XMLHttpRequest'},
      success: function(res) {
        if (res.success) {
          $('#modalEdit').modal('hide');
          showAlert('success', res.message || 'Fasilitas berhasil diupdate');
          setTimeout(() => location.reload(), 800);
        } else {
          showAlert('error', res.message || 'Terjadi kesalahan');
        }
      },
      error: function() { showAlert('error', 'Gagal mengirim data'); },
      complete: function() { spin.addClass('d-none'); btn.prop('disabled', false); }
    });
  });

  // Delete
  $(document).on('click', '.btn-delete', function() {
    const id = $(this).data('id');

    Swal.fire({
      title: 'Hapus Fasilitas?',
      text: 'Data yang dihapus tidak dapat dikembalikan',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Ya, Hapus!',
      cancelButtonText: 'Batal',
      reverseButtons: true
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: BASE_URL + '/delete/' + id,
          method: 'POST',
          dataType: 'json',
          headers: {'X-Requested-With': 'XMLHttpRequest'},
          data: {'<?= csrf_token() ?>': '<?= csrf_hash() ?>'},
          success: function(res) {
            if (res.success) {
              showAlert('success', res.message || 'Fasilitas berhasil dihapus');
              setTimeout(() => location.reload(), 700);
            } else {
              showAlert('error', res.message || 'Gagal menghapus');
            }
          },
          error: function() { showAlert('error', 'Gagal menghapus data'); }
        });
      }
    });
  });

  // Helpers
  function handleFormErrors(form, errors) {
    if (typeof errors === 'object') {
      Object.keys(errors).forEach((f) => {
        const input = form.find(`[name="${f}"]`);
        if (input.length) {
          input.addClass('is-invalid');
          const fb = input.siblings('.invalid-feedback');
          if (fb.length) fb.text(errors[f]);
        }
      });
    }
  }

  function showAlert(type, message) {
    if (!message) return;
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const icon = type === 'success' ? 'check-circle' : 'exclamation-triangle';
    const html = `
      <div class="alert ${alertClass} alert-dismissible fade show shadow-sm" role="alert">
        <i class="bi bi-${icon} me-2"></i>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>`;
    const c = document.querySelector('#content .container-xxl');
    if (c) {
      const old = c.querySelector('.alert');
      if (old) old.remove();
      c.insertAdjacentHTML('afterbegin', html);
    }
    setTimeout(() => {
      const a = document.querySelector('#content .container-xxl .alert');
      if (a) a.remove();
    }, 5000);
  }

  // Modal events
  const modalTambah = document.getElementById('modalTambahFasilitas');
  const modalEdit = document.getElementById('modalEdit');
  
  if (modalTambah) {
    modalTambah.addEventListener('shown.bs.modal', () => {
      const eventSelect = document.querySelector('#formTambahFasilitas [name="event_id"]');
      if (eventSelect) eventSelect.focus();
    });
  }
  
  if (modalEdit) {
    modalEdit.addEventListener('shown.bs.modal', () => {
      const firstInput = document.querySelector('#editFasilitasInputs .edit-fasilitas-item');
      if (firstInput) firstInput.focus();
    });
  }

  // Initial render
  applyFilter();
})();
</script>

<!-- ===== CSS ===== -->
<style>
:root{
  --blue-50:#eff6ff; --blue-100:#dbeafe; --blue-200:#bfdbfe; --blue-300:#93c5fd;
  --blue-400:#60a5fa; --blue-500:#3b82f6; --blue-600:#2563eb; --blue-700:#1d4ed8; --blue-800:#1e40af; --blue-900:#1e3a8a;
  --muted:#6b7280; --ink:#0f172a; --radius:16px;
  --card-min-h: 280px;
  --side-pad: clamp(1rem, 2.3vw, 2.2rem);
}
.container-xxl{ max-width:min(100%, 1560px); padding-left:var(--side-pad)!important; padding-right:var(--side-pad)!important; margin-inline:auto; }
body{ font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size:15.5px; line-height:1.6; color:var(--ink); }
.page-wrap-blue{
  min-height:100vh; padding-top:72px;
  background:
    radial-gradient(1000px 380px at 10% -10%, rgba(59,130,246,.16), rgba(59,130,246,0) 60%),
    radial-gradient(1000px 380px at 90% 110%, rgba(59,130,246,.12), rgba(59,130,246,0) 70%),
    linear-gradient(180deg, var(--blue-50), #fff 40%);
}

/* HERO */
.card-hero{ border:0; border-radius:var(--radius); overflow:hidden; box-shadow:0 12px 28px rgba(30,64,175,.18); }
.card-hero .hero-body{ background:linear-gradient(135deg,var(--blue-700),var(--blue-800)); color:#fff; padding:1.8rem 1.2rem; min-height:176px; }
.hero-title{ font-weight:800; }
.text-white-70{ color:rgba(255,255,255,.85)!important; }
.hero-tools .input-group .input-group-text{ background:#fff; border:0; }
.hero-tools .form-control{ border:0; }
.hero-tools .btn{ border:0; }
.hero-search{ border-radius:12px; overflow:hidden; }
.hero-tabs{ background:#fff; padding:.6rem .8rem; border:1px solid rgba(30,64,175,.18); border-top:0; }
.hero-tabs .nav-link{ font-weight:700; border-radius:999px; padding:.45rem .9rem; font-size:.88rem; }
.hero-tabs .nav-link.active{ background:var(--blue-600); color:#fff; }

/* glass chip */
.chip{ display:inline-flex; align-items:center; gap:.35rem; padding:.35rem .6rem; border-radius:999px; font-size:.78rem; font-weight:700; }
.glass-chip{ color:#fff; border:1px solid rgba(255,255,255,.28); background: rgba(255,255,255,.16); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); }

/* CARD */
.fp-card{
  position:relative;
  border:1px solid rgba(30,64,175,.12); border-radius:14px; background:#fff;
  box-shadow:0 10px 22px rgba(30,64,175,.10);
  padding:0.9rem; display:flex; flex-direction:column; min-height:var(--card-min-h);
  transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease;
}
.fp-card:hover{ transform: translateY(-2px); box-shadow:0 16px 28px rgba(30,64,175,.16); border-color: rgba(30,64,175,.22); }

.fp-head{ display:flex; align-items:center; justify-content:space-between; gap:.75rem; margin-bottom:.5rem; }
.fp-title{ line-height:1.35; max-width:65%; color:var(--blue-900); font-size:.98rem; }

.status-pill{ font-weight:800; font-size:.76rem; padding:.2rem .55rem; border-radius:999px; border:1px solid rgba(0,0,0,.06); white-space:nowrap; }
.pill-success{ background:#d1fae5; color:#065f46; }
.pill-muted{ background:#f3f4f6; color:#374151; }

.meta-list{ list-style:none; padding-left:0; margin:.4rem 0; }
.meta-list li{ display:flex; align-items:center; justify-content:space-between; gap:.75rem; padding:.3rem 0; font-size:.9rem; }
.meta-list li span{ color:var(--muted); }

.facilities-preview{ background:#f8fafc; border:1px dashed rgba(30,64,175,.12); border-radius:8px; padding:.6rem; }
.facilities-preview ul{ margin-bottom:0; }

/* Type Badges - Blue-White Theme Only */
.type-badge{ 
  display: inline-flex; 
  align-items: center; 
  gap: .3rem; 
  padding: .35rem .65rem; 
  border-radius: 8px; 
  font-size: .82rem; 
  font-weight: 700; 
  white-space: nowrap;
  border: 1.5px solid;
}

.badge-pres-online{ 
  background: #2563eb; 
  border-color: #1d4ed8;
  color: #fff !important;
  box-shadow: 0 2px 8px rgba(37, 99, 235, .3);
}

.badge-pres-offline{ 
  background: #3b82f6; 
  border-color: #2563eb;
  color: #fff !important;
  box-shadow: 0 2px 8px rgba(59, 130, 246, .3);
}

.badge-aud-online{ 
  background: #60a5fa; 
  border-color: #3b82f6;
  color: #fff !important;
  box-shadow: 0 2px 8px rgba(96, 165, 250, .3);
}

.badge-aud-offline{ 
  background: #1e40af; 
  border-color: #1e3a8a;
  color: #fff !important;
  box-shadow: 0 2px 8px rgba(30, 64, 175, .3);
}

.type-badge i{ font-size: .9rem; color: #fff !important; }

.fp-foot{ display:flex; gap:.6rem; margin-top:auto; padding-top:.6rem; border-top:1px dashed rgba(30,64,175,.16); }
.btn{ font-weight:700; border-radius:10px; font-size:.88rem; padding:.55rem .9rem; }
.btn-primary{ background:var(--blue-600); border-color:var(--blue-600); box-shadow:0 4px 12px rgba(37,99,235,.2); }

.empty-hint{ color:#3a2a6a; background:#f6f9ff; border:1px dashed rgba(30,64,175,.18); border-radius:12px; padding:.9rem 1rem; font-weight:700; }

/* Modal */
.modal-paper{ overflow:hidden; border:0; box-shadow:0 14px 40px rgba(0,0,0,.18); border-radius:14px; }
.modal-paper__header{ background:linear-gradient(135deg, var(--blue-700) 0%, var(--blue-800) 100%); color:#fff; border:0; }
.glass-header{ background: linear-gradient(135deg, rgba(29,78,216,.85), rgba(30,64,175,.85)); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); border-bottom: 1px solid rgba(255,255,255,.18); }

@media (max-width:767.98px){
  .hero-tools{ width:100%; max-width:none; }
  .card-hero .hero-body{ padding:1.4rem 1rem; min-height:165px; }
  .hero-tabs .nav-link{ padding:.4rem .7rem; font-size:.82rem; }
}
@media (max-width:575.98px){
  .container-xxl{ padding-left: calc(var(--side-pad) - .25rem) !important; padding-right: calc(var(--side-pad) - .25rem) !important; }
  .fp-foot{ flex-direction:column; }
}
</style>