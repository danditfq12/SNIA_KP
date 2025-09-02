<?php
// props: $label, $value (int|float|string), $icon (opsional class bootstrap icon), $accent (opsional)
// contoh: echo view('components/stat_card', ['label'=>'Lunas','value'=>12,'icon'=>'bi-check2-circle']);
$icon   = $icon   ?? 'bi-dot';
$accent = $accent ?? 'primary';
?>
<div class="card p-3 shadow-sm h-100 border-0">
  <div class="d-flex align-items-center justify-content-between">
    <small class="text-muted"><?= esc($label ?? '-') ?></small>
    <i class="bi <?= esc($icon) ?> text-<?= esc($accent) ?>"></i>
  </div>
  <div class="h4 mb-0 mt-1"><?= esc($value ?? '0') ?></div>
</div>
