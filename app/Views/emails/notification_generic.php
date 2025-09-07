<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title><?= esc($title) ?></title>
</head>
<body style="font-family: Arial, sans-serif; background-color:#f4f4f4; padding:20px;">
  <div style="max-width:600px; margin:auto; background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 2px 4px rgba(0,0,0,.1);">
    
    <!-- Header -->
    <div style="background:#2563eb; color:#ffffff; padding:16px; text-align:center;">
      <h2 style="margin:0;"><?= esc($title) ?></h2>
    </div>

    <!-- Body -->
    <div style="padding:20px; color:#333333; line-height:1.5;">
      <?php if (!empty($message)): ?>
        <p><?= nl2br(esc($message)) ?></p>
      <?php endif; ?>

      <?php if (!empty($link)): ?>
        <p style="margin-top:20px; text-align:center;">
          <a href="<?= esc($link) ?>" 
             style="display:inline-block; padding:12px 20px; background:#2563eb; color:#ffffff; border-radius:4px; text-decoration:none;">
            Lihat Detail
          </a>
        </p>
      <?php endif; ?>
    </div>

    <!-- Footer -->
    <div style="background:#f9f9f9; padding:12px; text-align:center; font-size:12px; color:#777;">
      Email ini dikirim otomatis oleh sistem <strong>SNIA</strong>. 
      Mohon jangan balas langsung ke email ini.
    </div>
  </div>
</body>
</html>
