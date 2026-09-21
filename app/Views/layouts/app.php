<?php $site = require BASE_PATH . '/config/site.php'; $description = $description ?? $site['company']['description']; ?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e($title ?? 'Oufeidun') ?></title>
  <meta name="description" content="<?= e($description) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/app.css">
  <link rel="stylesheet" href="/assets/css/php-overrides.css">
  <script>window.OUFEIDUN={csrf:<?= json_encode(csrf_token()) ?>};</script>
  <script src="/assets/js/app.js" defer></script>
</head>
<body class="bg-light text-dark antialiased">
  <?php require BASE_PATH . '/app/Views/partials/navbar.php'; ?>
  <main><?= $content ?></main>
  <?php require BASE_PATH . '/app/Views/partials/footer.php'; ?>
  <?php require BASE_PATH . '/app/Views/partials/floating.php'; ?>
  <div id="image-dialog" class="image-dialog" hidden><button type="button" data-dialog-close aria-label="Đóng ảnh"><?= icon('x') ?></button><img src="" alt="Ảnh sản phẩm phóng lớn"></div>
</body>
</html>

