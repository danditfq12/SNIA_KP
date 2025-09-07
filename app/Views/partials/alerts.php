<?php
/**
 * Partial: SweetAlert2 flash alerts (universal)
 * Cara pakai di view/layout:  <?= $this->include('partials/alerts') ?>
 *
 * Key flashdata yang didukung:
 * - success, error, warning, info  (string)
 * - swal (array)                   (advanced/custom)
 *   contoh:
 *   session()->setFlashdata('swal', [
 *     'icon'  => 'success',
 *     'title' => 'Berhasil',
 *     'text'  => 'Pembayaran terverifikasi',
 *     'timer' => 1800,       // optional
 *     'toast' => true,       // optional (akan jadi toast kanan-atas)
 *   ]);
 */
$success = session()->getFlashdata('success');
$error   = session()->getFlashdata('error');
$warning = session()->getFlashdata('warning');
$info    = session()->getFlashdata('info');
$custom  = session()->getFlashdata('swal'); // array
?>

<!-- SweetAlert2 (CDN) -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
(function () {
  // helper untuk toast
  function fireToast(icon, title, timer=2200) {
    Swal.fire({
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: timer,
      icon: icon,
      title: title
    });
  }

  <?php if ($success): ?>
    fireToast('success', <?= json_encode($success) ?>);
  <?php endif; ?>

  <?php if ($error): ?>
    Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text: <?= json_encode($error) ?>,
      confirmButtonText: 'OK'
    });
  <?php endif; ?>

  <?php if ($warning): ?>
    Swal.fire({
      icon: 'warning',
      title: 'Perhatian',
      text: <?= json_encode($warning) ?>,
      confirmButtonText: 'Mengerti'
    });
  <?php endif; ?>

  <?php if ($info): ?>
    fireToast('info', <?= json_encode($info) ?>, 2600);
  <?php endif; ?>

  <?php if (is_array($custom) && !empty($custom)): ?>
    Swal.fire(<?= json_encode($custom, JSON_UNESCAPED_UNICODE) ?>);
  <?php endif; ?>
})();
</script>
