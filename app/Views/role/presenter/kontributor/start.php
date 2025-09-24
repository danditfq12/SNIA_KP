<?php
$title  = $title ?? 'Data Kontributor';
$event  = $event ?? [];
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
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <div class="header-section header-blue d-flex justify-content-between align-items-center mb-3">
        <div>
          <h3 class="welcome-text mb-1"><i class="bi bi-people me-2"></i>Data Kontributor</h3>
          <div class="text-white-50">Event: <strong><?= esc($event['title'] ?? 'Event') ?></strong></div>
        </div>
        <div class="text-end d-none d-md-block">
          <small class="text-white-50 d-block">Tanggal event</small>
          <strong class="text-white">
            <?= isset($event['event_date']) ? date('d M Y', strtotime($event['event_date'])) : '-' ?>
            <?= !empty($event['event_time']) ? ' • '.esc($event['event_time']) : '' ?>
          </strong>
        </div>
      </div>

      <div class="card shadow-sm border-0">
        <div class="card-body">
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
              <h6 class="mb-0">Daftar Co-Author</h6>
              <button type="button" class="btn btn-sm btn-primary" id="addRowBtn">
                <i class="bi bi-plus-lg"></i> Tambah Baris
              </button>
            </div>

            <div class="table-responsive mb-3">
              <table class="table table-bordered align-middle" id="coTable">
                <thead class="table-light">
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
                      <td><input type="text" name="co_name[]" class="form-control"  value="<?= esc($ca['nama'] ?? '') ?>"></td>
                      <td><input type="email" name="co_email[]" class="form-control" value="<?= esc($ca['email'] ?? '') ?>"></td>
                      <td><input type="text" name="co_afiliasi[]" class="form-control" value="<?= esc($ca['afiliasi'] ?? '') ?>"></td>
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
                <i class="bi bi-arrow-left"></i> Kembali
              </a>
              <button class="btn btn-primary" name="goto" value="to_abstract">
                <i class="bi bi-save"></i> <?= $isUpdate ? 'Update & Lanjut ke Abstrak' : 'Simpan & Lanjut ke Abstrak' ?>
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
  :root{ --primary-color:#2563eb; --info-color:#06b6d4; }
  .header-section.header-blue{
    background:linear-gradient(135deg,var(--primary-color),#1e40af);
    color:#fff; padding:24px; border-radius:16px; box-shadow:0 8px 28px rgba(0,0,0,.12);
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
      if (tbody.children.length === 0) {
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