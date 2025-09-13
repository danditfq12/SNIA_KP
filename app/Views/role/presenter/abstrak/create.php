<?php
$title          = $title ?? 'Upload Abstrak';
$eligibleEvents = $eligibleEvents ?? [];
$defaultEvent   = $defaultEvent ?? null;
$kategori       = $kategori ?? [];
$defId          = (int)($defaultEvent['id'] ?? 0);
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- HEADER -->
      <div class="header-section header-blue d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="welcome-text mb-1"><i class="bi bi-upload"></i> Upload Abstrak</h2>
          <div class="text-white-50">Kirimkan abstrak untuk event yang Anda ikuti</div>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-12 col-lg-8">
          <div class="card shadow-sm">
            <div class="card-header bg-light">
              <strong><i class="bi bi-file-earmark-plus"></i> Formulir Upload</strong>
            </div>
            <div class="card-body">
              <form action="/presenter/abstrak/store" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
                <?= csrf_field() ?>

                <!-- Event -->
                <div class="mb-3">
                  <label class="form-label">Event <span class="text-danger">*</span></label>
                  <select name="event_id" class="form-select" required <?= $defId ? 'readonly disabled' : '' ?>>
                    <option value="">-- Pilih Event --</option>
                    <?php foreach ($eligibleEvents as $e): ?>
                      <option value="<?= (int)$e['id'] ?>" <?= $defId===(int)$e['id']?'selected':'' ?>>
                        <?= esc($e['title']) ?> • <?= !empty($e['event_date']) ? date('d M Y', strtotime($e['event_date'])) : '-' ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <?php if ($defId): ?>
                    <input type="hidden" name="event_id" value="<?= $defId ?>">
                    <div class="form-text">Event sudah dipilih dari halaman detail.</div>
                  <?php endif; ?>
                </div>

                <!-- Kategori -->
                <div class="mb-3">
                  <label class="form-label">Kategori Abstrak <span class="text-danger">*</span></label>
                  <select name="id_kategori" class="form-select" required>
                    <option value="">-- Pilih Kategori --</option>
                    <?php foreach ($kategori as $k): ?>
                      <option value="<?= (int)$k['id_kategori'] ?>"><?= esc($k['nama_kategori']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <!-- Judul -->
                <div class="mb-3">
                  <label class="form-label">Judul <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" name="judul" placeholder="Masukkan judul abstrak" required maxlength="255">
                </div>

                <!-- File -->
                <div class="mb-3">
                  <label class="form-label">File Abstrak <span class="text-danger">*</span></label>
                  <input type="file" class="form-control" name="file_abstrak" accept=".pdf,.doc,.docx" required>
                  <div class="form-text">Format: PDF/DOC/DOCX, maks 5MB.</div>
                </div>

                <div class="d-grid d-md-flex gap-2">
                  <button type="submit" class="btn btn-primary">
                    <i class="bi bi-upload"></i> Upload
                  </button>
                  <a href="/presenter/abstrak" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                  </a>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- Tips / Info -->
        <div class="col-12 col-lg-4">
          <div class="card shadow-sm">
            <div class="card-header bg-light"><strong><i class="bi bi-info-circle"></i> Info</strong></div>
            <div class="card-body">
              <ul class="small mb-0">
                <li>Pastikan Anda sudah <strong>terdaftar</strong> pada event tujuan.</li>
                <li>Pengunggahan abstrak hanya tersedia jika <strong>masa unggah</strong> masih aktif.</li>
                <li>Status awal abstrak adalah <strong>MENUNGGU</strong> hingga direview.</li>
                <li>Jika <strong>DITERIMA</strong>, silakan lanjut ke pembayaran dari halaman detail event.</li>
              </ul>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
  :root{ --primary-color:#2563eb; --info-color:#06b6d4; }
  body{ background:#f8fafc; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
  .header-section.header-blue{
    background: linear-gradient(135deg, var(--primary-color), #1e40af);
    color:#fff; padding:22px; border-radius:14px; box-shadow:0 8px 28px rgba(0,0,0,.12);
  }
  .welcome-text{ font-weight:500; font-size:1.35rem; }
  .card{ border-radius:14px; }
  .btn{ border-radius:10px; }
</style>