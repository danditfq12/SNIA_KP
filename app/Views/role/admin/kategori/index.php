<?php
$title    = $title ?? 'Manajemen Kategori Abstrak';
$kategori = $kategori ?? []; // id_kategori, nama_kategori, deskripsi, is_active
$total    = (int)($stats['total_kategori'] ?? count($kategori));
$tab      = $tab ?? ($stats['tab'] ?? 'semua');
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
              <h3 class="hero-title mb-1"><i class="bi bi-tags me-2"></i><?= esc($title) ?></h3>
              <div class="text-white-70 small">Kelola daftar kategori untuk klasifikasi abstrak.</div>
            </div>

            <div class="hero-tools flex-grow-1" style="max-width:620px;">
              <div class="input-group input-group-lg hero-search">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input id="searchInput" type="text" class="form-control" placeholder="Cari kategori atau deskripsi…">
                <button id="clearSearch" type="button" class="btn btn-light d-none"><i class="bi bi-x-circle"></i></button>
              </div>
            </div>
          </div>

          <div class="d-flex flex-wrap mt-3 gap-2 align-items-center">
            <span class="chip glass-chip">
              <i class="bi bi-hash me-1"></i>Total
              <strong class="ms-2"><?= number_format($total) ?></strong>
            </span>

            <div class="ms-auto d-flex flex-wrap gap-2">
              <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambahKategori">
                <i class="bi bi-plus-circle me-1"></i>Tambah
              </button>
              <button id="btnToggleSelect" class="btn btn-outline-light btn-sm">
                <i class="bi bi-check2-square me-1"></i> Pilih
              </button>
            </div>
          </div>
        </div>

        <!-- Tabs filter: initial tab ambil dari server -->
        <div class="hero-tabs">
          <ul class="nav nav-pills" id="filterTabs" role="tablist" data-init-tab="<?= esc($tab) ?>">
            <li class="nav-item" role="presentation">
              <button class="nav-link" data-filter="all" type="button" role="tab">
                Semua <span class="badge bg-light text-primary ms-1" id="countAll"><?= count($kategori) ?></span>
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" data-filter="active" type="button" role="tab">
                Aktif <span class="badge bg-light text-primary ms-1" id="countActive">0</span>
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" data-filter="inactive" type="button" role="tab">
                Nonaktif <span class="badge bg-light text-secondary ms-1" id="countInactive">0</span>
              </button>
            </li>
          </ul>
        </div>
      </div>

      <!-- ===== BULK ACTION BAR ===== -->
      <div id="bulkBar" class="bulk-bar d-none">
        <div class="left d-flex align-items-center gap-3 flex-wrap">
          <div class="form-check form-check-inline m-0">
            <input class="form-check-input" type="checkbox" id="masterCheck">
            <label class="form-check-label fw-semibold" for="masterCheck">Pilih semua yang terlihat</label>
          </div>
          <span class="chip chip-count"><i class="bi bi-check2-square me-1"></i><span id="selCount">0</span> terpilih</span>
          <button class="btn btn-link btn-sm text-decoration-none px-2" id="btnClearSelection">Kosongkan</button>
          <button class="btn btn-link btn-sm text-decoration-none px-2" id="btnSelectAllGlobal">
            Pilih semua (<span id="totalCountAll"><?= number_format($total) ?></span>)
          </button>
        </div>
        <div class="right d-flex gap-2 align-items-center">
          <div class="btn-group">
            <button id="btnBulkPrimary" class="btn btn-primary btn-sm" disabled>
              <i class="bi bi-toggle2-off me-1"></i><span id="bulkPrimaryLabel">Nonaktifkan</span>
            </button>
            <button id="btnBulkMore" type="button" class="btn btn-primary btn-sm dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false" disabled>
              <span class="visually-hidden">Toggle Dropdown</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="#" data-alt="activate"><i class="bi bi-toggle2-on me-2"></i>Aktifkan</a></li>
              <li><a class="dropdown-item" href="#" data-alt="deactivate"><i class="bi bi-toggle2-off me-2"></i>Nonaktifkan</a></li>
            </ul>
          </div>
          <button id="btnExitSelect" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-x-lg me-1"></i>Keluar mode pilih
          </button>
        </div>
      </div>

      <!-- ===== GRID KARTU ===== -->
      <div id="gridWrap" class="row g-3 row-cols-1 row-cols-md-2 row-cols-xl-3 row-cols-xxl-4">
        <?php if (empty($kategori)): ?>
          <div class="col">
            <div class="empty-hint"><i class="bi bi-inbox me-1"></i>Belum ada kategori. Tambahkan dulu ya!</div>
          </div>
        <?php else: foreach ($kategori as $k): ?>
          <?php
            $id   = (int)($k['id_kategori'] ?? 0);
            $name = (string)($k['nama_kategori'] ?? '-');
            $desc = trim((string)($k['deskripsi'] ?? ''));

            // Normalisasi boolean (true/false, 1/0, '1'/'0', 't'/'f', 'true'/'false')
            $rawActive  = $k['is_active'] ?? true;
            $activeBool = ($rawActive === true) || ($rawActive === 1) || ($rawActive === '1') || ($rawActive === 't') || ($rawActive === 'true') || ($rawActive === 'on');
            $active     = $activeBool ? 1 : 0;

            $searchable = strtolower($name.' '.$desc);
            $pillClass  = $active ? 'pill-success' : 'pill-muted';
            $pillText   = $active ? 'Aktif' : 'Nonaktif';
          ?>
          <div class="col">
            <div class="fp-card js-card" data-id="<?= $id ?>" data-search="<?= esc($searchable) ?>" data-active="<?= $active ? '1':'0' ?>">
              <label class="pick">
                <input type="checkbox" class="pick-input" data-id="<?= $id ?>">
                <span class="pick-box"></span>
              </label>

              <div class="fp-head">
                <h6 class="mb-0 fp-title" title="<?= esc($name) ?>"><?= esc($name) ?></h6>
                <span class="status-pill <?= $pillClass ?> small pill-state"><?= $pillText ?></span>
              </div>

              <ul class="meta-list">
                <li>
                  <span>Deskripsi</span>
                  <strong class="text-truncate" style="max-width:60%; text-align:right;">
                    <?= $desc !== '' ? esc($desc) : '—' ?>
                  </strong>
                </li>
              </ul>

              <div class="fp-foot">
                <button class="btn btn-primary flex-fill btn-sm" onclick="editKategori(<?= $id ?>)">
                  <i class="bi bi-pencil me-1"></i>Edit
                </button>
                <button class="btn btn-outline-secondary flex-fill btn-sm" onclick="confirmDelete(<?= $id ?>,'<?= esc($name,'attr') ?>')">
                  <i class="bi bi-trash me-1"></i>Hapus
                </button>
              </div>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>

    </div>
  </main>
</div>

<!-- ===== MODALS ===== -->
<div class="modal fade" id="modalTambahKategori" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content modal-paper">
      <div class="modal-header modal-paper__header glass-header">
        <h5 class="modal-title text-white"><i class="bi bi-plus-circle me-2"></i>Tambah Kategori</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form id="formTambahKategori" novalidate>
        <?= csrf_field() ?>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nama Kategori <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="nama_kategori" placeholder="Contoh: Artificial Intelligence" required maxlength="150" autocomplete="off">
            <div class="invalid-feedback"></div>
          </div>
          <div class="mb-0">
            <label class="form-label">Deskripsi</label>
            <textarea class="form-control" name="deskripsi" rows="3" maxlength="500" placeholder="Deskripsi singkat…"></textarea>
            <div class="form-text">Opsional • maks 500 karakter</div>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-light" type="button" data-bs-dismiss="modal">Batal</button>
          <button class="btn btn-primary" type="submit">
            <span class="spinner-border spinner-border-sm me-1 d-none" id="loadingTambah"></span>Simpan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalEditKategori" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content modal-paper">
      <div class="modal-header modal-paper__header glass-header">
        <h5 class="modal-title text-white"><i class="bi bi-pencil me-2"></i>Edit Kategori</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form id="formEditKategori" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="id_kategori" id="edit_id_kategori">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nama Kategori <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="nama_kategori" id="edit_nama_kategori" required maxlength="150" autocomplete="off">
            <div class="invalid-feedback"></div>
          </div>
          <div class="mb-3">
            <label class="form-label">Deskripsi</label>
            <textarea class="form-control" name="deskripsi" id="edit_deskripsi" rows="3" maxlength="500"></textarea>
            <div class="form-text">Opsional • maks 500 karakter</div>
            <div class="invalid-feedback"></div>
          </div>
          <div class="mb-0">
            <label class="form-label">Status</label>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" role="switch" id="edit_is_active" name="is_active" value="1">
              <label class="form-check-label" for="edit_is_active">Aktifkan kategori</label>
            </div>
            <div class="form-text">Jika dimatikan, kategori masuk tab “Nonaktif”.</div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-light" type="button" data-bs-dismiss="modal">Batal</button>
          <button class="btn btn-primary" type="submit">
            <span class="spinner-border spinner-border-sm me-1 d-none" id="loadingEdit"></span>Perbarui
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?= $this->include('partials/footer') ?>

<!-- ===== JS ===== -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.1/sweetalert2.min.js"></script>
<script>
(function(){
  const input = document.getElementById('searchInput');
  const clearBtn = document.getElementById('clearSearch');
  const grid = document.getElementById('gridWrap');
  const tabs = [...document.querySelectorAll('#filterTabs .nav-link')];

  const bulkBar = document.getElementById('bulkBar');
  const masterCheck = document.getElementById('masterCheck');
  const selCountEl = document.getElementById('selCount');
  const btnBulkPrimary = document.getElementById('btnBulkPrimary');
  const btnBulkMore = document.getElementById('btnBulkMore');
  const btnClearSelection = document.getElementById('btnClearSelection');
  const btnExitSelect = document.getElementById('btnExitSelect');
  const btnToggleSelect = document.getElementById('btnToggleSelect');
  const btnSelectAllGlobal = document.getElementById('btnSelectAllGlobal');
  const totalCountAllEl = document.getElementById('totalCountAll');
  const bulkPrimaryLabel = document.getElementById('bulkPrimaryLabel');

  let selected = new Set();
  let selectMode = false;

  function allCards(){ return [...grid.querySelectorAll('.js-card')]; }
  function visibleCards(){ return allCards().filter(c => c.closest('.col').style.display !== 'none'); }
  function visibleIds(){ return visibleCards().map(c => String(c.dataset.id)); }

  function updateCounts(){
    const cards = allCards();
    let all=cards.length, act=0, inact=0;
    cards.forEach(c => (c.dataset.active === '1') ? act++ : inact++);
    document.getElementById('countAll').textContent = all;
    document.getElementById('countActive').textContent = act;
    document.getElementById('countInactive').textContent = inact;
    if (totalCountAllEl) totalCountAllEl.textContent = all.toLocaleString();
  }

  function setSelectMode(on){
    selectMode = !!on;
    document.body.classList.toggle('select-mode', selectMode);
    if(!selectMode){ selected.clear(); }
    refreshUI();
  }

  function updateMasterVisual(){
    if (!masterCheck) return;
    const vids = visibleIds();
    if (vids.length === 0){
      masterCheck.checked = false;
      masterCheck.indeterminate = false;
      masterCheck.disabled = true;
      return;
    }
    masterCheck.disabled = false;
    let countSelVisible = vids.reduce((acc,id)=> acc + (selected.has(id)?1:0), 0);
    if (countSelVisible === 0){
      masterCheck.checked = false;
      masterCheck.indeterminate = false;
    } else if (countSelVisible === vids.length){
      masterCheck.checked = true;
      masterCheck.indeterminate = false;
    } else {
      masterCheck.checked = false;
      masterCheck.indeterminate = true;
    }
  }

  function refreshUI(){
    allCards().forEach(card=>{
      const id = String(card.dataset.id);
      const checked = selected.has(id);
      const inp = card.querySelector('.pick-input');
      if (inp) inp.checked = checked;
      card.classList.toggle('selected', checked);
    });

    const n = selected.size;
    const anyVisible = visibleCards().length > 0;
    selCountEl.textContent = n;
    bulkBar.classList.toggle('d-none', !selectMode || (!n && !anyVisible));

    let allActive = true, allInactive = true;
    selected.forEach(id=>{
      const c = grid.querySelector(`.js-card[data-id="${id}"]`);
      if(!c) return;
      if (c.dataset.active === '1') allInactive = false; else allActive = false;
    });

    let action = 'deactivate', label = 'Nonaktifkan', icon = 'bi-toggle2-off';
    if (selected.size > 0) {
      if (allInactive) { action = 'activate'; label = 'Aktifkan'; icon = 'bi-toggle2-on'; }
      else if (allActive) { action = 'deactivate'; label = 'Nonaktifkan'; icon = 'bi-toggle2-off'; }
      else { action = 'deactivate'; label = 'Nonaktifkan'; icon = 'bi-toggle2-off'; }
    }
    btnBulkPrimary.dataset.action = action;
    bulkPrimaryLabel.textContent = label;
    btnBulkPrimary.innerHTML = `<i class="bi ${icon} me-1"></i><span id="bulkPrimaryLabel">${label}</span>`;
    btnBulkPrimary.disabled = (n===0);
    btnBulkMore.disabled = (n===0);

    btnToggleSelect.innerHTML = selectMode
      ? `<i class="bi bi-x-square me-1"></i>Batal Pilih (${n})`
      : `<i class="bi bi-check2-square me-1"></i> Pilih`;

    updateMasterVisual();
  }

  function applyFilter(){
    const activeTab = document.querySelector('#filterTabs .nav-link.active');
    const filter = activeTab?.dataset.filter || 'all';
    const query = (input?.value || '').trim().toLowerCase();

    allCards().forEach(card=>{
      const isActive = (card.dataset.active === '1');
      const hay = (card.dataset.search || card.textContent).toLowerCase();
      const matchSearch = (!query || hay.includes(query));
      const matchFilter = (filter==='all') || (filter==='active' && isActive) || (filter==='inactive' && !isActive);
      card.closest('.col').style.display = (matchSearch && matchFilter) ? '' : 'none';
    });
    clearBtn?.classList.toggle('d-none', !query);
    updateCounts();
    refreshUI();
  }

  // Initial tab sesuai server (?tab=)
  const initTab = document.querySelector('#filterTabs')?.dataset.initTab || 'semua';
  const initialFilter = (initTab === 'aktif') ? 'active' : (initTab === 'nonaktif' ? 'inactive' : 'all');
  const initBtn = tabs.find(b => (b.dataset.filter === initialFilter));
  if (initBtn) {
    tabs.forEach(b => b.classList.remove('active'));
    initBtn.classList.add('active');
  }

  input?.addEventListener('input', applyFilter);
  clearBtn?.addEventListener('click', ()=>{ input.value=''; applyFilter(); input.focus(); });
  tabs.forEach(btn=>{
    btn.addEventListener('click', ()=>{
      tabs.forEach(b=>b.classList.remove('active'));
      btn.classList.add('active');
      applyFilter();

      // Optional: update ?tab= di URL agar shareable
      const map = { all:'semua', active:'aktif', inactive:'nonaktif' };
      const next = map[btn.dataset.filter] || 'semua';
      const url = new URL(window.location.href);
      url.searchParams.set('tab', next);
      window.history.replaceState({}, '', url);
    });
  });

  // Select mode
  btnToggleSelect.addEventListener('click', ()=> setSelectMode(!selectMode));
  btnExitSelect.addEventListener('click', ()=> setSelectMode(false));
  btnClearSelection.addEventListener('click', ()=>{ selected.clear(); refreshUI(); });

  grid.addEventListener('change', (e)=>{
    if(!e.target.classList.contains('pick-input')) return;
    if(!selectMode){ e.target.checked=false; return; }
    const id = String(e.target.dataset.id);
    if(e.target.checked) selected.add(id); else selected.delete(id);
    refreshUI();
  });

  masterCheck?.addEventListener('change', ()=>{
    if(!selectMode) { masterCheck.checked = false; return; }
    const vids = visibleIds();
    if (masterCheck.checked){ vids.forEach(id => selected.add(String(id))); }
    else { vids.forEach(id => selected.delete(String(id))); }
    refreshUI();
  });

  btnSelectAllGlobal?.addEventListener('click', (e)=>{
    e.preventDefault();
    if(!selectMode) setSelectMode(true);
    allCards().forEach(c => selected.add(String(c.dataset.id)));
    refreshUI();
  });

  function bulkAction(kind){
    if(selected.size===0) return;
    const ids = Array.from(selected);
    $.ajax({
      url: '<?= site_url('admin/kategori/bulk') ?>',
      type: 'POST',
      dataType: 'json',
      headers: {'X-Requested-With':'XMLHttpRequest'},
      data: {
        action: kind,
        ids: ids,
        '<?= csrf_token() ?>':'<?= csrf_hash() ?>'
      },
      success: function(res){
        if(res.success){
          showAlert('success', res.message || (kind==='activate' ? 'Berhasil diaktifkan.' : 'Berhasil dinonaktifkan.'));
          ids.forEach(id=>{
            const card = grid.querySelector(`.js-card[data-id="${id}"]`);
            if(!card) return;
            const pill = card.querySelector('.pill-state');
            if(kind==='activate'){
              card.dataset.active = '1';
              pill.classList.remove('pill-muted'); pill.classList.add('pill-success'); pill.textContent='Aktif';
            }else{
              card.dataset.active = '0';
              pill.classList.remove('pill-success'); pill.classList.add('pill-muted'); pill.textContent='Nonaktif';
            }
          });
          selected.clear();
          applyFilter();
          refreshUI();
        }else{
          showAlert('error', res.message || 'Aksi massal gagal.');
        }
      },
      error: function(){ showAlert('error','Gagal mengirim permintaan.'); }
    });
  }
  btnBulkPrimary.addEventListener('click', ()=> bulkAction(btnBulkPrimary.dataset.action || 'deactivate'));
  document.querySelectorAll('.dropdown-menu [data-alt]').forEach(a=>{
    a.addEventListener('click', (e)=>{ e.preventDefault(); bulkAction(a.dataset.alt); });
  });

  // Shortcuts
  document.addEventListener('keydown', (e)=>{
    if (e.key === 'Escape' && selectMode){ setSelectMode(false); }
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase()==='a'){
      e.preventDefault();
      if(!selectMode) setSelectMode(true);
      visibleIds().forEach(id => selected.add(String(id)));
      refreshUI();
    }
    if (e.shiftKey && e.key.toLowerCase()==='a'){
      e.preventDefault();
      if(!selectMode) setSelectMode(true);
      allCards().forEach(c => selected.add(String(c.dataset.id)));
      refreshUI();
    }
  });

  // AJAX forms
  $('#formTambahKategori').on('submit', function(e){
    e.preventDefault();
    const form=$(this), btn=form.find('button[type="submit"]'), spin=$('#loadingTambah');
    form.find('.is-invalid').removeClass('is-invalid');
    spin.removeClass('d-none'); btn.prop('disabled', true);
    $.ajax({
      url:'<?= site_url('admin/kategori/store') ?>', type:'POST', data: form.serialize(), dataType:'json',
      headers:{'X-Requested-With':'XMLHttpRequest'},
      success:function(res){
        if(res.success){ $('#modalTambahKategori').modal('hide'); showAlert('success',res.message||'Kategori ditambahkan.'); setTimeout(()=>location.reload(),800); }
        else{ if(res.errors){ handleFormErrors(form,res.errors); } else { showAlert('error',res.message||'Terjadi kesalahan.'); } }
      },
      error:function(){ showAlert('error','Terjadi kesalahan sistem'); },
      complete:function(){ spin.addClass('d-none'); btn.prop('disabled', false); }
    });
  });

  $('#formEditKategori').on('submit', function(e){
    e.preventDefault();
    const form=$(this), id=$('#edit_id_kategori').val();
    if(!id) return showAlert('error','ID kategori tidak ditemukan');
    const btn=form.find('button[type="submit"]'), spin=$('#loadingEdit');
    form.find('.is-invalid').removeClass('is-invalid');
    spin.removeClass('d-none'); btn.prop('disabled', true);
    $.ajax({
      url:`<?= site_url('admin/kategori/update') ?>/${id}`, type:'POST', data: form.serialize(), dataType:'json',
      headers:{'X-Requested-With':'XMLHttpRequest'},
      success:function(res){
        if(res.success){ $('#modalEditKategori').modal('hide'); showAlert('success',res.message||'Kategori diperbarui.'); setTimeout(()=>location.reload(),800); }
        else{ if(res.errors){ handleFormErrors(form,res.errors); } else { showAlert('error',res.message||'Terjadi kesalahan.'); } }
      },
      error:function(){ showAlert('error','Terjadi kesalahan sistem'); },
      complete:function(){ spin.addClass('d-none'); btn.prop('disabled', false); }
    });
  });

  document.getElementById('modalTambahKategori')?.addEventListener('shown.bs.modal', ()=>{ document.querySelector('#formTambahKategori [name="nama_kategori"]')?.focus(); });
  document.getElementById('modalEditKategori')?.addEventListener('shown.bs.modal', ()=>{ document.getElementById('edit_nama_kategori')?.focus(); });

  // first render
  applyFilter();
})();

function editKategori(id){
  if(!id) return showAlert('error','ID kategori tidak valid');
  $.ajax({
    url:`<?= site_url('admin/kategori/show') ?>/${id}`, type:'GET', dataType:'json',
    headers:{'X-Requested-With':'XMLHttpRequest'},
    success:function(res){
      if(res.success && res.data){
        const d=res.data;
        $('#edit_id_kategori').val(d.id_kategori);
        $('#edit_nama_kategori').val(d.nama_kategori||'');
        $('#edit_deskripsi').val(d.deskripsi||'');
        const active=('is_active' in d)?(String(d.is_active)==='1'||d.is_active===true||String(d.is_active)==='t'||String(d.is_active)==='true') : true;
        $('#edit_is_active').prop('checked', active);
        $('#modalEditKategori').modal('show');
      }else{ showAlert('error', res.message||'Data tidak ditemukan'); }
    },
    error:function(){ showAlert('error','Gagal memuat data kategori'); }
  });
}
function confirmDelete(id, nama){
  if(!id) return showAlert('error','Data tidak valid');
  Swal.fire({
    title:'Hapus Kategori?', html:`Yakin ingin menghapus <strong>"${nama}"</strong>?`,
    icon:'warning', showCancelButton:true, confirmButtonColor:'#dc3545', cancelButtonColor:'#6c757d',
    confirmButtonText:'Ya, Hapus', cancelButtonText:'Batal', reverseButtons:true
  }).then((r)=>{ if(r.isConfirmed) deleteKategori(id); });
}
function deleteKategori(id){
  $.ajax({
    url:`<?= site_url('admin/kategori/delete') ?>/${id}`, type:'POST',
    data:{'_method':'DELETE','<?= csrf_token() ?>':'<?= csrf_hash() ?>'},
    dataType:'json', headers:{'X-HTTP-Method-Override':'DELETE','X-Requested-With':'XMLHttpRequest'},
    success:function(res){
      if(res.success){ showAlert('success', res.message||'Kategori dihapus.'); setTimeout(()=>location.reload(),700); }
      else{ showAlert('error', res.message||'Gagal menghapus kategori'); }
    },
    error:function(){ showAlert('error','Gagal menghapus kategori'); }
  });
}
// helpers
function handleFormErrors(form, errors){
  if(typeof errors==='object'){
    Object.keys(errors).forEach((f)=>{
      const input=form.find(`[name="${f}"]`);
      if(input.length){
        input.addClass('is-invalid');
        const fb=input.siblings('.invalid-feedback'); if(fb.length) fb.text(errors[f]);
      }
    });
  }
}
function showAlert(type, message){
  if(!message) return;
  const alertClass = type==='success'?'alert-success':'alert-danger';
  const icon = type==='success'?'check-circle':'exclamation-triangle';
  const html = `
    <div class="alert ${alertClass} alert-dismissible fade show shadow-sm" role="alert">
      <i class="bi bi-${icon} me-2"></i>${message}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>`;
  const c=document.querySelector('#content .container-xxl');
  if(c){ const old=c.querySelector('.alert'); if(old) old.remove(); c.insertAdjacentHTML('afterbegin', html); }
  setTimeout(()=>{ const a=document.querySelector('#content .container-xxl .alert'); if(a) a.remove(); }, 5000);
}
</script>

<!-- ===== CSS ===== -->
<style>
:root{
  --blue-50:#eff6ff; --blue-100:#dbeafe; --blue-200:#bfdbfe; --blue-300:#93c5fd;
  --blue-400:#60a5fa; --blue-500:#3b82f6; --blue-600:#2563eb; --blue-700:#1d4ed8; --blue-800:#1e40af; --blue-900:#1e3a8a;
  --muted:#6b7280; --ink:#0f172a; --radius:16px;
  --card-min-h: 220px;
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
.hero-tabs .nav-link{ font-weight:700; border-radius:999px; padding:.45rem 1rem; }
.hero-tabs .nav-link.active{ background:var(--blue-600); color:#fff; }

/* glass chip */
.chip{ display:inline-flex; align-items:center; gap:.35rem; padding:.35rem .6rem; border-radius:999px; font-size:.78rem; font-weight:700; }
.glass-chip{ color:#fff; border:1px solid rgba(255,255,255,.28); background: rgba(255,255,255,.16); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); }

/* BULK BAR */
.bulk-bar{
  display:flex; align-items:center; justify-content:space-between;
  gap:.75rem; padding:.6rem .8rem; border-radius:12px;
  background:#ffffff; border:1px solid rgba(30,64,175,.18); box-shadow:0 8px 18px rgba(30,64,175,.08);
  margin-bottom: .75rem;
}
.chip-count{ background:#eef3ff; color:#1e3a8a; border:1px solid rgba(30,64,175,.15); }

/* CARD */
.fp-card{
  position:relative;
  border:1px solid rgba(30,64,175,.12); border-radius:14px; background:#fff;
  box-shadow:0 10px 22px rgba(30,64,175,.10);
  padding:0.9rem; display:flex; flex-direction:column; min-height:var(--card-min-h);
  transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease, background .18s ease;
}
.fp-card:hover{ transform: translateY(-2px); box-shadow:0 16px 28px rgba(30,64,175,.16); border-color: rgba(30,64,175,.22); }

/* checkbox hanya tampil saat select-mode */
.pick{ position:absolute; top:.55rem; left:.6rem; display:none; align-items:center; gap:.4rem; cursor:pointer; z-index:2; }
.select-mode .pick{ display:inline-flex; }
.pick-input{ position:absolute; opacity:0; width:0; height:0; }
.pick-box{
  width:18px; height:18px; border-radius:4px; border:1.8px solid #cbd5e1; background:#fff; display:inline-block;
  box-shadow: inset 0 0 0 1px rgba(0,0,0,.02);
}
.pick-input:checked + .pick-box{ background:#2563eb; border-color:#2563eb; box-shadow: inset 0 0 0 2px #fff; }
.fp-card.selected{ background:#f0f7ff; border-color:#93c5fd; box-shadow:0 12px 24px rgba(59,130,246,.16); }

.fp-head{ display:flex; align-items:center; justify-content:space-between; gap:.75rem; margin-bottom:.25rem; }
.fp-title{ line-height:1.35; max-width:72%; color:var(--blue-900); }

.status-pill{ font-weight:800; font-size:.78rem; padding:.2rem .55rem; border-radius:999px; border:1px solid rgba(0,0,0,.06); white-space:nowrap; }
.pill-success{ background:#d1fae5; color:#065f46; }
.pill-muted{ background:#f3f4f6; color:#374151; }

.meta-list{ list-style:none; padding-left:0; margin:.4rem 0 .2rem 0; }
.meta-list li{ display:flex; align-items:center; justify-content:space-between; gap:.75rem; padding:.36rem 0; }
.meta-list li span{ color:var(--muted); }

.fp-foot{ display:flex; gap:.6rem; margin-top:auto; padding-top:.6rem; border-top:1px dashed rgba(30,64,175,.16); }
.btn{ font-weight:800; border-radius:10px; font-size:.98rem; padding:.6rem 1.05rem; }
.btn-primary{ background:var(--blue-600); border-color:var(--blue-600); box-shadow:0 4px 12px rgba(37,99,235,.2); }

.empty-hint{ color:#3a2a6a; background:#f6f9ff; border:1px dashed rgba(30,64,175,.18); border-radius:12px; padding:.9rem 1rem; font-weight:700; }

/* Modal */
.modal-paper{ overflow:hidden; border:0; box-shadow:0 14px 40px rgba(0,0,0,.18); }
.modal-paper__header{ background:linear-gradient(135deg, var(--blue-700) 0%, var(--blue-800) 100%); color:#fff; border:0; }
.glass-header{ background: linear-gradient(135deg, rgba(29,78,216,.85), rgba(30,64,175,.85)); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); border-bottom: 1px solid rgba(255,255,255,.18); }

/* Bulk bar form control */
#bulkBar .form-check-input{ width: 1.05rem; height: 1.05rem; cursor: pointer; }
#bulkBar .form-check-label{ cursor: pointer; }

@media (max-width:767.98px){
  .hero-tools{ width:100%; max-width:none; }
  .card-hero .hero-body{ padding:1.4rem 1rem; min-height:165px; }
}
@media (max-width:575.98px){
  .container-xxl{ padding-left: calc(var(--side-pad) - .25rem) !important; padding-right: calc(var(--side-pad) - .25rem) !important; }
}
</style>