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

      <!-- Form Card (tanpa hero) -->
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
                <input type="text" class="form-control form-control-soft" value="<?= esc($presenterName) ?>" disabled>
                <input type="hidden" name="corresponding_name" value="<?= esc($presenterName) ?>">
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label">Email Koresponden</label>
                <input type="text" class="form-control form-control-soft" value="<?= esc($presenterEmail) ?>" disabled>
                <input type="hidden" name="corresponding_email" value="<?= esc($presenterEmail) ?>">
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label">No. HP (opsional)</label>
                <input type="text" name="phone" class="form-control form-control-soft" value="<?= esc($phone) ?>">
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label">Afiliasi/Institusi <span class="text-danger">*</span></label>
                <input type="text" name="afiliasi" class="form-control form-control-soft" value="<?= esc($afiliasi) ?>" required>
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
                      <td><input type="text"  name="co_name[]"     class="form-control form-control-soft" value="<?= esc($ca['nama'] ?? '') ?>"></td>
                      <td><input type="email" name="co_email[]"    class="form-control form-control-soft" value="<?= esc($ca['email'] ?? '') ?>"></td>
                      <td><input type="text"  name="co_afiliasi[]" class="form-control form-control-soft" value="<?= esc($ca['afiliasi'] ?? '') ?>"></td>
                      <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger delRowBtn" aria-label="Hapus baris"><i class="bi bi-x"></i></button>
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
/* ===== Kontributor — seragam dengan Abstrak/Full Paper ===== */
:root{
  --blue-50:#eff6ff; --blue-100:#dbeafe; --blue-200:#bfdbfe; --blue-300:#93c5fd;
  --blue-400:#60a5fa; --blue-500:#3b82f6; --blue-600:#2563eb; --blue-700:#1d4ed8; --blue-800:#1e40af; --blue-900:#1e3a8a;
  --ink:#0f172a; --muted:#6b7280;
  --radius:14px;
  --side-pad: clamp(1rem, 2.3vw, 2.2rem); /* kiri–kanan sama seperti halaman lain */
  --gutter:   1rem;
}

body{ font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size:15.5px; line-height:1.6; color:var(--ink); }

.page-wrap-blue{
  min-height:100vh; padding-top:72px;
  background:
    radial-gradient(1000px 380px at 10% -10%, rgba(59,130,246,.16), rgba(59,130,246,0) 60%),
    radial-gradient(1000px 380px at 90% 110%, rgba(59,130,246,.12), rgba(59,130,246,0) 70%),
    linear-gradient(180deg, var(--blue-50), #fff 40%);
}

.container-xxl{
  max-width: min(100%, 1560px);
  padding-left: var(--side-pad) !important;
  padding-right: var(--side-pad) !important;
  margin-inline:auto;
}

/* Card & heading */
.card-glass-plain{
  backdrop-filter: blur(6px);
  background: rgba(255,255,255,.96);
  border-radius: var(--radius);
  border: 1px solid rgba(30,64,175,.10);
}
.shadow-soft{ box-shadow:0 10px 24px rgba(30,64,175,.08); }

.hero-title{ font-weight:800; letter-spacing:.25px; font-size:1.4rem; }
.text-blue-900{ color:var(--blue-900)!important; }

/* Grid spacing */
.row.g-3{ --bs-gutter-x: var(--gutter); --bs-gutter-y: var(--gutter); }

/* Inputs */
.form-label{ font-weight:700; color:#334155; }
.form-control{ border-radius:10px; font-size:.98rem; }
.form-control-soft{
  border:1px solid rgba(2,6,23,.12);
  background:#fff;
  height:44px;
}
.form-control:focus{
  border-color: var(--blue-400);
  box-shadow:0 0 0 .2rem rgba(59,130,246,.12);
}

/* Table */
.table{ font-size:.95rem; margin-bottom:0; }
.table thead th{
  background:#f8fbff!important;
  border-bottom:1px solid rgba(30,64,175,.18);
  font-weight:800; color:#1e3a8a;
  font-size:.84rem; text-transform:uppercase; letter-spacing:.4px;
  padding:.7rem .9rem;
}
.table tbody tr{ border-bottom:1px solid rgba(30,64,175,.08); }
.table tbody td{ padding:.7rem .9rem; vertical-align:middle; border-top:none; }
.table-responsive{
  border:1px solid rgba(30,64,175,.10);
  border-radius:12px; overflow:hidden;
  background:#fff;
}

/* Buttons */
.btn{ font-weight:800; letter-spacing:.2px; border-radius:10px; }
.btn-primary{ background:var(--blue-600); border-color:var(--blue-600); box-shadow:0 4px 12px rgba(37,99,235,.18); }
.btn-outline-danger{ border-color:#fecaca; color:#b91c1c; }
.btn-outline-danger:hover{ background:#fee2e2; border-color:#fca5a5; color:#7f1d1d; }
.btn-outline-secondary{ color:#334155; border-color:#e2e8f0; }
.btn-outline-secondary:hover{ background:#f8fafc; border-color:#cbd5e1; color:#0f172a; }
.btn-sm{ padding:.38rem .7rem; font-size:.84rem; }

/* Responsive */
@media (max-width:575.98px){
  .container-xxl{ padding-left: calc(var(--side-pad) - .25rem) !important; padding-right: calc(var(--side-pad) - .25rem) !important; }
  .table thead th, .table tbody td{ padding:.6rem .75rem; font-size:.86rem; }
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
      <td><input type="text"  name="co_name[]"     class="form-control form-control-soft" value="${escapeHtml(name)}"></td>
      <td><input type="email" name="co_email[]"    class="form-control form-control-soft" value="${escapeHtml(email)}"></td>
      <td><input type="text"  name="co_afiliasi[]" class="form-control form-control-soft" value="${escapeHtml(aff)}"></td>
      <td class="text-center">
        <button type="button" class="btn btn-sm btn-outline-danger delRowBtn" aria-label="Hapus baris"><i class="bi bi-x"></i></button>
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
