<?php
$heroTitle='Đại lý phân phối';$heroDescription='Danh sách điểm bán Oufeidun theo từng khu vực và thông tin liên hệ hợp tác.';require BASE_PATH.'/app/Views/partials/page-hero.php';
$regions=[
    ['Miền Bắc',[
        'Nhật Lai Châu',
        'Quân Phủ Lý Hà Nam',
        'Hoài Nam Hòa Bình',
        'Hoàng Hà Phú Thọ',
        'Kiên Trang Vĩnh Phúc',
        'Ốp xinh Nam Định',
        'Trang Hải Phòng',
        'Sóc Sơn Hà Nội',
    ]],
    ['Miền Trung',[
        'Thắng Quảng Trị',
        'Thúy Xuân Lâm Hà Tĩnh',
        'Dũng Hương Thanh Hóa',
        'Đại Thành Hà Tĩnh',
        'Mạnh Hùng Nghệ An',
        'Quốc Trường Quảng Nam',
        'Thanh Trọng Quảng Bình',
        'Tiến Đô Lương',
        'Trần Việt Hùng Đông Hà',
        'Thoa Phú Yên',
        'Lượt Gia Lai',
        'Phương Buôn Ma Thuột',
    ]],
    ['Miền Nam',[
        'Minh Vũng Tàu',
        'Doly Cam Ranh',
        'Ly Lâm Đồng',
        'Thanh Vân Bảo Lộc Lâm Đồng',
        'Văn Thanh – Ba Quang Tiền Giang',
        'POPO Bình Dương',
        'Quỳnh Lâm Ea Drang',
    ]],
];
$dealers=[
    ['Quỳnh Lâm','/images/distribution/customer-2026-09/quynh-lam.jpg'],
    ['Thúy Xuân','/images/distribution/customer-2026-09/thuy-xuan.jpg'],
    ['Đại Thành','/images/distribution/customer-2026-09/dai-thanh.jpg'],
    ['Hoài Nam','/images/distribution/customer-2026-09/hoai-nam.jpg'],
    ['Mạnh Hùng','/images/distribution/customer-2026-09/manh-hung.jpg'],
    ['Kiên Trang','/images/distribution/customer-2026-09/kien-trang.jpg'],
];
$commitments=[['Đổi trả hàng lỗi 100%','Hỗ trợ đổi trả 100% đối với sản phẩm được xác nhận lỗi theo chính sách.','from-amber-50 to-white','bg-amber-100 text-amber-700','from-amber-400 to-orange-500'],['Độc quyền theo khu vực','Mỗi khu vực chỉ phát triển một đại lý độc quyền theo thỏa thuận hợp tác.','from-blue-50 to-white','bg-blue-100 text-blue-700','from-blue-500 to-cyan-400'],['Quyền lợi theo doanh số','Đại lý đạt đủ doanh số năm có cơ hội du lịch nước ngoài cùng hệ thống đại lý toàn quốc.','from-emerald-50 to-white','bg-emerald-100 text-emerald-700','from-emerald-500 to-teal-400']];
?>
<section class="section-padding bg-slate-50"><div class="container-custom"><div class="grid overflow-hidden rounded-[2rem] border border-slate-200 bg-[#071a38] shadow-xl lg:grid-cols-[.85fr_1.15fr]"><div class="flex flex-col items-start justify-center px-6 py-9 text-white sm:p-10"><span class="w-fit rounded-full border border-white/25 bg-white/10 px-3 py-1 text-xs font-semibold text-secondary-300">Oufeidun Việt Nam</span><h2 class="mt-4 text-2xl font-bold leading-tight sm:text-4xl">Tìm đúng điểm bán, nhận đúng tư vấn</h2><p class="mt-3 max-w-md text-sm leading-6 text-slate-100 sm:text-base">Kết nối hệ thống điểm bán và tư vấn sản phẩm phù hợp cho khách hàng, đại lý.</p><a href="#danh-sach-dai-ly" class="mt-6 inline-flex items-center gap-2 rounded-full bg-secondary-400 px-5 py-3 text-sm font-bold text-slate-950">Tìm đại lý theo khu vực <?= icon('arrow-right','h-4 w-4') ?></a></div><div class="aspect-video bg-black lg:self-center"><img src="/images/client-2026-09/distribution.webp" alt="Ba dòng kính cường lực Caballo Android, Caballo iPhone và Oufeidun Diamond" class="h-full w-full object-contain"></div></div><div class="mt-8 grid gap-5 md:grid-cols-3"><?php foreach($commitments as $item): ?><article class="group relative overflow-hidden rounded-3xl border border-slate-200 bg-gradient-to-br <?= e($item[2]) ?> p-6 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-lg"><div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r <?= e($item[4]) ?>"></div><div class="flex h-11 w-11 items-center justify-center rounded-2xl <?= e($item[3]) ?> transition-transform duration-200 group-hover:scale-105"><?= icon('shield') ?></div><h2 class="mt-5 text-lg font-bold text-dark"><?= e($item[0]) ?></h2><p class="mt-2 text-sm leading-6 text-slate-600"><?= e($item[1]) ?></p></article><?php endforeach; ?></div></div></section>
<section id="danh-sach-dai-ly" class="section-padding scroll-mt-24 bg-white"><div class="container-custom"><div class="mx-auto mb-6 max-w-2xl text-center"><p class="text-sm font-semibold text-primary-600">Hệ thống phân phối</p><h2 class="heading-2 mt-3 text-dark">Đại lý phân phối theo khu vực</h2><p class="mt-4 leading-7 text-slate-600">Tìm đại lý gần bạn theo khu vực. Các điểm bán dưới đây cung cấp sản phẩm kính cường lực Oufeidun.</p></div><div class="grid gap-6 lg:grid-cols-3"><?php foreach($regions as $region): ?><article class="rounded-3xl border border-slate-200 bg-slate-50 p-6 sm:p-7"><div class="flex items-center gap-2 text-sm font-semibold text-primary-600"><?= icon('map','h-5 w-5') ?><?= e($region[0]) ?></div><h3 class="mt-4 text-xl font-bold text-dark">Hệ thống đại lý <?= e(mb_strtolower($region[0])) ?></h3><ul class="mt-5 space-y-3"><?php foreach($region[1] as $dealer): ?><li class="flex items-start gap-3 rounded-2xl bg-white px-4 py-3 text-sm font-medium text-slate-700 shadow-sm"><?= icon('check','mt-0.5 h-4 w-4 shrink-0 text-primary-600') ?><?= e($dealer) ?></li><?php endforeach; ?></ul></article><?php endforeach; ?></div><div class="mt-8 border-t border-slate-200 pt-8"><div class="mx-auto mb-6 max-w-2xl text-center"><p class="text-sm font-semibold text-primary-600">Chứng nhận đại lý</p><h2 class="heading-3 mt-2 text-dark">Đại lý tiêu biểu</h2></div><div aria-label="Danh sách chứng nhận đại lý tiêu biểu" class="-mx-4 flex snap-x snap-mandatory gap-5 overflow-x-auto px-4 pb-4 [&>article]:min-w-[82vw] [&>article]:snap-center sm:mx-0 sm:grid sm:grid-cols-2 sm:overflow-visible sm:px-0 sm:pb-0 sm:[&>article]:min-w-0 lg:grid-cols-3"><?php foreach($dealers as $dealer): ?><article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-lg"><div class="p-5"><h3 class="text-xl font-bold text-dark"><?= e($dealer[0]) ?></h3><p class="mt-2 text-sm text-slate-600">Chứng nhận đại lý độc quyền thương hiệu Oufeidun.</p></div><button type="button" data-image-preview="<?= e($dealer[1]) ?>" class="mx-5 mb-5 block aspect-[566/800] overflow-hidden rounded-2xl bg-slate-100"><img src="<?= e($dealer[1]) ?>" alt="Chứng nhận đại lý <?= e($dealer[0]) ?>" class="h-full w-full object-contain"></button></article><?php endforeach; ?></div></div></div></section>
<section class="section-padding bg-slate-50"><div class="container-custom grid gap-8 lg:grid-cols-[1.15fr_.85fr]"><div class="rounded-[2rem] bg-[#071a38] p-7 text-white sm:p-10"><p class="text-sm font-semibold text-secondary-300">Hợp tác cùng Oufeidun</p><h2 class="mt-3 text-3xl font-bold leading-tight sm:text-4xl">Mở rộng hệ thống phân phối kính cường lực</h2><p class="mt-5 max-w-2xl leading-7 text-slate-200">Oufeidun chào đón các đối tác cùng định hướng phát triển, cần tư vấn nguồn hàng kính cường lực và chính sách hợp tác.</p><a href="/lien-he" class="mt-8 inline-flex items-center gap-2 rounded-xl bg-primary-500 px-6 py-3 font-semibold text-white transition hover:bg-primary-400">Đăng ký tư vấn hợp tác <?= icon('arrow-right','h-4 w-4') ?></a></div><aside class="rounded-[2rem] border border-slate-200 bg-white p-7 shadow-sm sm:p-10"><h2 class="text-xl font-bold text-dark">Liên hệ Oufeidun</h2><p class="mt-3 text-sm leading-6 text-slate-600">Nhận hỗ trợ về sản phẩm, báo giá và thông tin điểm bán.</p><a href="tel:<?= e($site['company']['phone_raw']) ?>" class="mt-7 flex items-center gap-3 font-semibold text-dark hover:text-primary-600"><?= icon('phone','h-5 w-5 text-primary-600') ?><?= e($site['company']['phone']) ?></a><div class="mt-5 flex items-start gap-3 text-sm leading-6 text-slate-600"><?= icon('map','mt-0.5 h-5 w-5 shrink-0 text-primary-600') ?><?= e($site['company']['address']) ?></div></aside></div></section>
