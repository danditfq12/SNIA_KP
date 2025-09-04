<?php
  // $payments = list pembayaran user
  $title = 'Pembayaran Saya';
  $badge = ['pending'=>'warning','verified'=>'success','rejected'=>'danger','canceled'=>'secondary'];
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>

<div id="content">
  <main class="flex-fill">
    <div class="container-fluid p-3 p-md-4">

      <h4 class="mb-3">Pembayaran</h4>

      <?php if (session('message')): ?>
        <div class="alert alert-success"><?= esc(session('message')) ?></div>
      <?php endif; ?>
      <?php if (session('error')): ?>
        <div class="alert alert-danger"><?= esc(session('error')) ?></div>
      <?php endif; ?>

      <?php if (empty($payments)): ?>
        <div class="p-4 text-center border rounded-3 bg-light-subtle">
          <div class="mb-2"><i class="bi bi-wallet2 fs-3 text-secondary"></i></div>
          <div class="fw-semibold">Belum ada pembayaran</div>
          <div class="text-muted small">Pembayaranmu akan muncul di sini setelah mengunggah bukti.</div>
        </div>
      <?php else: ?>

        <!-- DESKTOP: Tabel -->
        <div class="d-none d-md-block">
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th style="width:56px">No</th>
                  <th>Event</th>
                  <th style="width:170px">Jumlah</th>
                  <th style="width:130px">Status</th>
                  <th style="width:180px">Tanggal</th>
                  <th style="width:100px"></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($payments as $i => $row): ?>
                  <tr>
                    <td><?= $i+1 ?></td>
                    <td class="text-truncate" style="max-width:420px;">
                      <?= esc($row['event_title'] ?? '-') ?>
                    </td>
                    <td>Rp <?= number_format((float)($row['jumlah'] ?? 0), 0, ',', '.') ?></td>
                    <td>
                      <span class="badge bg-<?= $badge[$row['status']] ?? 'secondary' ?>">
                        <?= ucfirst(esc($row['status'])) ?>
                      </span>
                    </td>
                    <td><?= esc(isset($row['tanggal_bayar']) ? date('d/m/Y H:i', strtotime($row['tanggal_bayar'])) : '-') ?></td>
                    <td>
                      <a href="<?= site_url('audience/pembayaran/detail/'.(int)$row['id_pembayaran']) ?>"
                         class="btn btn-sm btn-outline-primary">Detail</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- MOBILE: Kartu -->
        <div class="d-block d-md-none">
          <div class="vstack gap-2">
            <?php foreach ($payments as $row): ?>
              <div class="card shadow-sm border-0">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-start mb-1">
                    <div class="me-2">
                      <div class="fw-semibold text-truncate" style="max-width: 220px;">
                        <?= esc($row['event_title'] ?? '-') ?>
                      </div>
                      <small class="text-muted">
                        <?= esc(isset($row['tanggal_bayar']) ? date('d M Y, H:i', strtotime($row['tanggal_bayar'])) : '-') ?>
                      </small>
                    </div>
                    <span class="badge bg-<?= $badge[$row['status']] ?? 'secondary' ?>">
                      <?= ucfirst(esc($row['status'])) ?>
                    </span>
                  </div>

                  <div class="d-flex justify-content-between align-items-center mt-2">
                    <div class="small text-muted">Jumlah</div>
                    <div class="fw-semibold">Rp <?= number_format((float)($row['jumlah'] ?? 0), 0, ',', '.') ?></div>
                  </div>

                  <div class="mt-3 d-grid">
                    <a href="<?= site_url('audience/pembayaran/detail/'.(int)$row['id_pembayaran']) ?>"
                       class="btn btn-primary btn-sm">Detail</a>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

      <?php endif; ?>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>
