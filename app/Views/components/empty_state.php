<?php
// props: $title, $desc, $icon (opsional), $actionLabel, $actionHref
$icon = $icon ?? 'bi-inbox';
?>
<div class="p-4 text-center border rounded-3 bg-light-subtle">
  <div class="mb-2"><i class="bi <?= esc($icon) ?> fs-2 text-secondary"></i></div>
  <div class="fw-semibold mb-1"><?= esc($title ?? 'Belum ada data') ?></div>
  <div class="text-muted small mb-3"><?= esc($desc ?? '') ?></div>
  <?php if (!empty($actionLabel) && !empty($actionHref)): ?>
    <a href="<?= esc($actionHref) ?>" class="btn btn-sm btn-primary"><?= esc($actionLabel) ?></a>
  <?php endif; ?>
</div>
