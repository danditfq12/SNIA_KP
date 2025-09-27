<?php
$title   = $title ?? 'Data Kontributor';
$event   = $event ?? [];
$eventId = $eventId ?? (int)($event['id'] ?? 0);

$presenterName  = $presenter_name  ?? '';
$presenterEmail = $presenter_email ?? '';
$afiliasi       = $afiliasi        ?? '';
$phone          = $phone           ?? '';

$coAuthors = $coauthors ?? [];
$isUpdate  = !empty($is_update);
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl py-4">

      <!-- Form Card (tanpa headbar/hero) -->
      <div class="card shadow-soft card-glass-plain">
        <div class="card-body">
          <div class="mb-3">
            <h3 class="hero-title mb-1"><i class="bi bi-people me-2"></i>Data Kontributor</h3>
            <div class="text-muted small">
              Event: <strong class="text-blue-900"><?= esc($event['title'] ?? 'Event') ?></strong>
              <?php if (!empty($event['event_date'])): ?>
                • <?= date('d M Y', strtotime($event['event_date'])) ?>
                <?= !empty($event['event_time']) ? ' • '.esc($event['event_time']) : '' ?>
              <?php endif; ?>
            </div>
          </div>

          <form method="post" action="<?= site_url('presenter/kontributor/save/'.$eventId) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="event_id" value="<?= (int)$eventId ?>">

            <div class="row g-3">
              <div class="col-12 col-md-6">
                <label class="form-label">Nama Koresponden (Presenter Utama)</label>
                <input type="text" class="form-control" value="<?= esc($presenterName) ?>" disabled>
                <input type="hidden" name="corresponding_name" value="<?= esc($presenterName) ?>">
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label">Email Koresponden</label>
                <input type="text" class="form-control" value="<?= esc($presenterEmail) ?>" disabled>
                <input type="hidden" name="corresponding_email" value="<?= esc($presenterEmail) ?>">
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label">No. HP (opsional)</label>
                <input type="text" name="phone" class="form-control" value="<?= esc($phone) ?>">
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label">Afiliasi/Institusi <span class="text-danger">*</span></label>
                <input type="text" name="afiliasi" class="form-control" value="<?= esc($afiliasi) ?>" required>
              </div>
            </div>

            <hr class="my-4">

            <div class="d-flex align-items-center justify-content-between mb-2">
              <h6 class="mb-0 text-blue-900">Daftar Co-Author</h6>
              <button type="button" class="btn btn-sm btn-primary" id="addRowBtn">
                <i class="bi bi-plus-lg me-1"></i>Tambah Baris
              </button>
            </div>

            <div class="table-responsive mb-3">
              <table class="table table-sm align-middle mb-0" id="coTable">
                <thead>
                  <tr>
                    <th style="width:35%">Nama</th>
                    <th style="width:30%">Email</th>
                    <th style="width:30%">Afiliasi</th>
                    <th style="width:5%"></th>
                  </tr>
                </thead>
                <tbody>
                <?php if (!empty($coAuthors)): ?>
                  <?php foreach ($coAuthors as $ca): ?>
                    <tr>
                      <td><input type="text"  name="co_name[]"     class="form-control" value="<?= esc($ca['nama'] ?? '') ?>"></td>
                      <td><input type="email" name="co_email[]"    class="form-control" value="<?= esc($ca['email'] ?? '') ?>"></td>
                      <td><input type="text"  name="co_afiliasi[]" class="form-control" value="<?= esc($ca['afiliasi'] ?? '') ?>"></td>
                      <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger delRowBtn"><i class="bi bi-x"></i></button>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr class="empty-row">
                    <td colspan="4" class="text-center text-muted py-4">Belum ada co-author. Klik <b>Tambah Baris</b>.</td>
                  </tr>
                <?php endif; ?>
                </tbody>
              </table>
            </div>

            <div class="d-flex gap-2 justify-content-end">
              <a href="<?= site_url('presenter/events/detail/'.(int)($event['id'] ?? $eventId)) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
              </a>
              <button class="btn btn-primary" name="goto" value="to_abstract">
                <i class="bi bi-save me-1"></i><?= $isUpdate ? 'Update & Lanjut ke Abstrak' : 'Simpan & Lanjut ke Abstrak' ?>
              </button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
/* ===== Kontributor — compact, tanpa headbar, lebar sama ===== */
:root{
  --blue-50:#eff6ff; --blue-100:#dbeafe; --blue-200:#bfdbfe;
  --blue-300:#93c5fd; --blue-400:#60a5fa; --blue-500:#3b82f6;
  --blue-600:#2563eb; --blue-700:#1d4ed8; --blue-800:#1e40af; --blue-900:#1e3a8a;
  --side-pad: 1rem;   /* kiri–kanan minimum */
  --gutter:   1rem;   /* jarak antar kolom */
}

body{ font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size:14.5px; line-height:1.5; }

.page-wrap-blue{
  background:linear-gradient(180deg,var(--blue-50),#fff 40%);
  min-height:100vh;
  padding-top:72px;        /* lebih pendek krn tidak ada headbar */
}

.container-xxl{
  max-width:1400px;
  padding-left:var(--side-pad) !important;
  padding-right:var(--side-pad) !important;
}

.card-glass-plain{
  backdrop-filter:blur(6px);
  background:rgba(255,255,255,.95);
  border-radius:14px;
  border:1px solid rgba(30,64,175,.10);
}
.shadow-soft{ box-shadow:0 10px 24px rgba(30,64,175,.08); }

.hero-title{ font-weight:800; letter-spacing:.25px; font-size:1.35rem; }
.text-blue-900{ color:var(--blue-900)!important; }
.text-muted{ color:#6b7280!important; font-weight:500; font-size:.88rem; }

.card-header{ padding:.9rem 1rem .4rem 1rem !important; }
.card-body{   padding:1rem !important; }

.row.g-3{ --bs-gutter-x: var(--gutter); --bs-gutter-y: var(--gutter); }

.form-label{ font-weight:600; color:#334155; }
.form-control{ border-radius:10px; height:40px; font-size:.95rem; }

/* Table */
.table{ font-size:.95rem; }
.table thead th{
  background-color:var(--blue-50)!important;
  border-bottom:1px solid var(--blue-200);
  font-weight:600; color:var(--blue-900);
  font-size:.85rem; text-transform:uppercase; letter-spacing:.4px;
  padding:.75rem 1rem;
}
.table tbody tr{ border-bottom:1px solid rgba(30,64,175,.08); }
.table tbody td{ padding:.75rem 1rem; vertical-align:middle; border-top:none; }
.table-responsive{ border:1px solid rgba(30,64,175,.08); border-radius:10px; overflow:hidden; }

/* Buttons */
.btn{ font-weight:600; letter-spacing:.25px; border-radius:10px; font-size:.95rem; padding:.55rem 1rem; }
.btn-sm{ padding:.4rem .75rem; font-size:.85rem; }
.btn-primary{ background:var(--blue-600); border-color:var(--blue-600); box-shadow:0 3px 10px rgba(37,99,235,.18); }
.btn-outline-danger{ border-color:#fecaca; color:#b91c1c; }
.btn-outline-danger:hover{ background:#fee2e2; border-color:#fca5a5; color:#7f1d1d; }
.btn-outline-secondary{ color:#334155; border-color:#e2e8f0; }
.btn-outline-secondary:hover{ background:#f8fafc; border-color:#cbd5e1; color:#0f172a; }

/* Responsive */
@media (max-width:575.98px){
  .container-xxl{ padding-left:1rem !important; padding-right:1rem !important; }
  .card-body{ padding:.9rem !important; }
  .table thead th, .table tbody td{ padding:.6rem .75rem; font-size:.85rem; }
}
@media (min-width:576px) and (max-width:767.98px){
  .container-xxl{ padding-left:1.1rem !important; padding-right:1.1rem !important; }
}
@media (min-width:768px) and (max-width:991.98px){
  .container-xxl{ padding-left:1.2rem !important; padding-right:1.2rem !important; }
}
@media (min-width:992px){
  .card-body{ padding:1.05rem !important; }
  .card-header{ padding:1.05rem 1.05rem .45rem 1.05rem !important; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const tbody = document.querySelector('#coTable tbody');
  const addBtn = document.getElementById('addRowBtn');

  function escapeHtml(s){
    return (s ?? '').toString()
      .replace(/&/g,'&amp;').replace(/</g,'&lt;')
      .replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
  }

  function addRow(name = '', email = '', aff = '') {
    const emptyRow = tbody.querySelector('.empty-row');
    if (emptyRow) emptyRow.remove();
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td><input type="text"  name="co_name[]"     class="form-control" value="${escapeHtml(name)}"></td>
      <td><input type="email" name="co_email[]"    class="form-control" value="${escapeHtml(email)}"></td>
      <td><input type="text"  name="co_afiliasi[]" class="form-control" value="${escapeHtml(aff)}"></td>
      <td class="text-center">
        <button type="button" class="btn btn-sm btn-outline-danger delRowBtn"><i class="bi bi-x"></i></button>
      </td>`;
    tbody.appendChild(tr);
  }

  addBtn?.addEventListener('click', () => addRow());

  tbody?.addEventListener('click', function (e) {
    if (e.target.closest('.delRowBtn')) {
      e.target.closest('tr')?.remove();
      if (tbody.querySelectorAll('tr').length === 0) {
        const empty = document.createElement('tr');
        empty.className = 'empty-row';
        empty.innerHTML = `<td colspan="4" class="text-center text-muted py-4">
          Belum ada co-author. Klik <b>Tambah Baris</b>.
        </td>`;
        tbody.appendChild(empty);
      }
    }
  });
});
</script>
