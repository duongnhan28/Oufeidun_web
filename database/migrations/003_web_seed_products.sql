INSERT INTO products (
    name, slug, category, description, badge, image,
    features, specifications, gallery, published, featured, sort_order, show_factory
) VALUES
(
    'Kính cường lực Caballo Android',
    'kinh-cuong-luc-caballo-android',
    'Kính cường lực',
    'Dòng kính cường lực Caballo dành cho điện thoại Android, chuẩn form máy, độ cứng cao và cảm ứng mượt mà.',
    'Mới',
    '/images/client-2026-09/caballo-android.webp',
    '["Độ cứng 9H","Độ dày kính 0,33 mm","Nhiều form máy Android","Cảm ứng ổn định"]',
    '[{"label":"Độ cứng","value":"9H"},{"label":"Độ dày","value":"0,33 mm"},{"label":"Thiết kế","value":"Chuẩn form điện thoại Android"}]',
    '["/images/client-2026-09/caballo-android.webp","/images/products/2026-09/caballo-android-lifestyle.webp"]',
    1, 1, 1, 1
),
(
    'Kính cường lực Caballo iPhone',
    'kinh-cuong-luc-oufeidun',
    'Kính cường lực',
    'Kính cường lực Caballo cho iPhone với độ cứng 9H, hạn chế trầy xước và va đập, đảm bảo độ trong suốt cùng trải nghiệm cảm ứng ổn định.',
    'Hot',
    '/images/client-2026-09/caballo-iphone.webp',
    '["Độ cứng 9H","Kính dày 0,25 mm","Lớp keo 0,38 mm","Dành cho iPhone 6–17"]',
    '[{"label":"Độ cứng","value":"9H"},{"label":"Độ dày kính","value":"0,25 mm"},{"label":"Lớp keo","value":"0,38 mm"}]',
    '["/images/client-2026-09/caballo-iphone.webp"]',
    1, 1, 2, 1
),
(
    'Kính cường lực Oufeidun Diamond Curved Glass',
    'kinh-cuong-luc-oufeidun-diamond',
    'Kính cường lực',
    'Dòng kính cong 3D, độ cứng 9H và thiết kế cao cấp dành cho các model phù hợp.',
    'Diamond',
    '/images/client-2026-09/diamond.webp',
    '["Thiết kế cạnh cong 3D","Độ cứng 9H","Chống tĩnh điện ESD","Độ dày 0,5 mm"]',
    '[{"label":"Độ cứng","value":"9H"},{"label":"Độ dày","value":"0,5 mm"},{"label":"Thiết kế","value":"Cạnh cong 3D"}]',
    '["/images/client-2026-09/diamond.webp"]',
    1, 1, 3, 0
)
ON DUPLICATE KEY UPDATE
    name=VALUES(name), category=VALUES(category), description=VALUES(description),
    badge=VALUES(badge), image=VALUES(image), features=VALUES(features),
    specifications=VALUES(specifications), gallery=VALUES(gallery),
    published=VALUES(published), featured=VALUES(featured),
    sort_order=VALUES(sort_order), show_factory=VALUES(show_factory);
