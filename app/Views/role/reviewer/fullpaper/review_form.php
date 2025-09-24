<?= view('partials/header') ?>
<?= view('partials/sidebar_reviewer') ?>

<div class="container py-3">
  <?= view('partials/alerts') ?>

  <h3 class="mb-3">Review Full Paper</h3>

  <div class="mb-3">
    <div class="card">
      <div class="card-body">
        <p class="mb-1"><b>Judul:</b> <?= esc($submission['title'] ?? '-') ?></p>
        <a class="btn btn-outline-secondary btn-sm" href="<?= site_url('reviewer/fullpaper/download/'.$submission['id']) ?>">Download Naskah</a>
      </div>
    </div>
  </div>

  <form method="post" action="<?= site_url('reviewer/fullpaper/review/'.$submission['id']) ?>">
    <?= csrf_field() ?>
    <div class="mb-3">
      <label class="form-label">Keputusan</label>
      <select name="keputusan" class="form-select" required>
        <?php
          $opts = ['ACCEPTED'=>'Accept','REVISION'=>'Revision','REJECTED'=>'Reject'];
          $val  = $existingReview['keputusan'] ?? '';
          foreach ($opts as $k=>$v):
        ?>
          <option value="<?= $k ?>" <?= $val===$k?'selected':'' ?>><?= $v ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Komentar untuk Author</label>
      <textarea name="komentar" rows="6" class="form-control" required><?= esc($existingReview['komentar'] ?? '') ?></textarea>
    </div>
    <input type="hidden" name="review_type" value="FULL">
    <button class="btn btn-primary">Simpan Review</button>
  </form>
</div>

<?= view('partials/footer') ?>