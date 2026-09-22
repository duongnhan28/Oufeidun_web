<?php $site = require BASE_PATH . '/config/site.php'; $description = $description ?? $site['company']['description']; $baseUrl=rtrim(\App\Core\Env::get('APP_URL','http://localhost'),'/'); $currentUrl=$baseUrl.($_SERVER['REQUEST_URI']??'/'); ?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e($title ?? 'Oufeidun') ?></title>
  <meta name="description" content="<?= e($description) ?>">
  <meta name="keywords" content="kính cường lực điện thoại, kính cường lực Caballo, Oufeidun, OEM kính cường lực, đại lý kính cường lực">
  <link rel="canonical" href="<?= e($currentUrl) ?>">
  <meta property="og:type" content="website"><meta property="og:locale" content="vi_VN"><meta property="og:site_name" content="Oufeidun"><meta property="og:title" content="<?= e($title ?? 'Oufeidun') ?>"><meta property="og:description" content="<?= e($description) ?>"><meta property="og:url" content="<?= e($currentUrl) ?>"><meta property="og:image" content="<?= e($baseUrl.'/images/source/oufeidun-cover.jpg') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/app.css?v=<?= filemtime(PUBLIC_PATH . '/assets/css/app.css') ?>">
  <link rel="stylesheet" href="/assets/css/php-overrides.css?v=<?= filemtime(PUBLIC_PATH . '/assets/css/php-overrides.css') ?>">
  <script>window.OUFEIDUN={csrf:<?= json_encode(csrf_token()) ?>};</script>
  <script src="/assets/js/app.js?v=<?= filemtime(PUBLIC_PATH . '/assets/js/app.js') ?>" defer></script>
  <script type="application/ld+json"><?= json_encode(['@context'=>'https://schema.org','@type'=>'Organization','name'=>$site['company']['name'],'legalName'=>$site['company']['full_name'],'url'=>$baseUrl,'logo'=>$baseUrl.'/images/source/oufeidun-cover.jpg','email'=>$site['company']['email'],'telephone'=>$site['company']['phone'],'address'=>['@type'=>'PostalAddress','streetAddress'=>$site['company']['address'],'addressCountry'=>'VN'],'sameAs'=>[$site['company']['facebook'],$site['company']['tiktok']]],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?></script>
</head>
<body class="bg-light text-dark antialiased">
  <?php require BASE_PATH . '/app/Views/partials/navbar.php'; ?>
  <main><?= $content ?></main>
  <?php require BASE_PATH . '/app/Views/partials/footer.php'; ?>
  <?php require BASE_PATH . '/app/Views/partials/floating.php'; ?>
  <div id="image-dialog" class="image-dialog" hidden><button type="button" data-dialog-close aria-label="Đóng ảnh"><?= icon('x') ?></button><img src="" alt="Ảnh sản phẩm phóng lớn"></div>
</body>
</html>
