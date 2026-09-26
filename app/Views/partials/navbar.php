<?php $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'; ?>
<header id="site-header" class="fixed left-0 right-0 top-0 z-50 py-4 transition-all duration-300">
  <div class="container-custom flex items-center justify-between">
    <a href="/" class="flex items-center gap-2" aria-label="Oufeidun - Trang chủ"><span class="inline-flex items-center" data-logo><img src="/images/brand/oufeidun-logo-transparent.png" alt="Oufeidun" width="120" height="40" class="h-8 w-auto object-contain sm:h-10"></span></a>
    <nav class="hidden items-center gap-1 xl:flex" aria-label="Điều hướng chính">
      <?php foreach ($site['navigation'] as $link): $active = $link['href']==='/' ? $path==='/' : str_starts_with($path, $link['href']); ?>
        <a href="<?= e($link['href']) ?>" class="relative whitespace-nowrap rounded-full px-3 py-2 text-sm font-medium transition <?= $active ? 'bg-primary-50 text-primary-500' : 'text-white/90 hover:text-white' ?>" data-nav-link><?= e($link['label']) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="flex items-center gap-2 sm:gap-3">
      <a href="/login" class="inline-flex shrink-0 items-center gap-2 whitespace-nowrap rounded-full border border-white/40 bg-white/10 px-3 py-2 text-sm font-semibold text-white sm:px-4" data-header-action><?= icon('login','h-4 w-4') ?> Đăng nhập</a>
      <a href="/lien-he" class="hidden items-center gap-2 rounded-full bg-white px-5 py-2.5 text-sm font-semibold text-primary-500 transition hover:scale-105 md:flex" data-header-cta>Đăng ký đại lý <?= icon('external','h-4 w-4') ?></a>
      <button id="menu-toggle" type="button" class="rounded-lg p-2 text-white xl:hidden" aria-label="Mở menu" aria-expanded="false"><?= icon('menu','h-6 w-6') ?></button>
    </div>
  </div>
  <nav id="mobile-navigation" class="container-custom hidden flex-col gap-1 bg-white py-4 shadow-xl xl:hidden" aria-label="Điều hướng di động">
    <?php foreach ($site['navigation'] as $link): ?><a href="<?= e($link['href']) ?>" class="block rounded-xl px-4 py-3 text-base font-medium text-gray-700 hover:bg-gray-50"><?= e($link['label']) ?></a><?php endforeach; ?>
    <a href="/lien-he" class="mt-2 flex items-center justify-center gap-2 rounded-xl bg-primary-500 px-5 py-3 font-semibold text-white">Đăng ký đại lý <?= icon('external','h-4 w-4') ?></a>
  </nav>
</header>
