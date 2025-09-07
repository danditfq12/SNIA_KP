<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title><?= esc($title) ?></title>
</head>
<body style="font-family: Arial, sans-serif; background: #f7f7f7; padding: 20px;">
  <div style="max-width: 600px; margin: auto; background: #fff; padding: 20px; border-radius: 8px;">
    <h2 style="color: #333;"><?= esc($title) ?></h2>
    <p><?= nl2br(esc($message)) ?></p>

    <?php if (!empty($link)): ?>
      <p>
        <a href="<?= esc($link) ?>" style="display: inline-block; padding: 10px 15px; background: #2563eb; color: #fff; border-radius: 4px; text-decoration: none;">
          Lihat Detail
        </a>
      </p>
    <?php endif; ?>

    <hr>
    <small style="color: #999;">Email ini dikirim otomatis oleh sistem SNIA.</small>
  </div>
</body>
</html>
