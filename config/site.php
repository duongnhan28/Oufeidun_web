<?php
declare(strict_types=1);

return [
    'company' => [
        'name' => 'Oufeidun',
        'full_name' => 'CÔNG TY TNHH KÍNH CƯỜNG LỰC ĐIỆN THOẠI OUFEIDUN VIỆT NAM',
        'description' => 'Chuyên sản xuất OEM và phân phối kính cường lực Oufeidun chất lượng cao cho hệ thống đại lý toàn quốc.',
        'phone' => '037 2378 956',
        'phone_raw' => '0372378956',
        'email' => 'Oufeidun@gmail.com',
        'address' => 'Thửa đất số 10, Tờ bản đồ số 81, Lô 23 khu LP3, Khu đô thị Hòa Quý, Phường Ngũ Hành Sơn, Thành phố Đà Nẵng, Việt Nam',
        'facebook' => 'https://www.facebook.com/oufeidun?locale=vi_VN',
        'tiktok' => 'https://www.tiktok.com/@nhamaycuonglucoufeidun',
    ],
    'navigation' => [
        ['label' => 'Trang chủ', 'href' => '/'],
        ['label' => 'Sản phẩm', 'href' => '/san-pham'],
        ['label' => 'Dịch vụ OEM', 'href' => '/dich-vu-oem'],
        ['label' => 'Đại lý phân phối', 'href' => '/dai-ly-phan-phoi'],
        ['label' => 'Về chúng tôi', 'href' => '/gioi-thieu'],
        ['label' => 'Tin tức', 'href' => '/tin-tuc'],
    ],
    'oem_steps' => [
        ['step' => 1, 'title' => 'Tư vấn & Phát triển ý tưởng', 'description' => 'Lắng nghe nhu cầu của đối tác, tư vấn giải pháp tối ưu về sản phẩm, chất liệu và thiết kế phù hợp với thị trường mục tiêu.'],
        ['step' => 2, 'title' => 'Thiết kế bao bì & In logo', 'description' => 'Đội ngũ thiết kế chuyên nghiệp tạo ra mẫu bao bì độc đáo, in logo thương hiệu riêng theo yêu cầu của đối tác.'],
        ['step' => 3, 'title' => 'Sản xuất & Kiểm định chất lượng', 'description' => 'Theo dõi các công đoạn sản xuất và kiểm tra thành phẩm theo yêu cầu của từng đơn hàng.'],
        ['step' => 4, 'title' => 'Đóng gói & Giao hàng', 'description' => 'Hoàn thiện đóng gói và phối hợp phương án giao nhận theo số lượng, địa điểm của đơn hàng.'],
        ['step' => 5, 'title' => 'Hỗ trợ quảng cáo', 'description' => 'Đẩy mạnh thương hiệu và sản phẩm của đối tác trên thị trường.'],
    ],
    'factory_videos' => [
        ['name' => 'day-chuyen-gia-cong', 'title' => 'Dây chuyền gia công', 'description' => 'Cận cảnh thiết bị và các thao tác trên dây chuyền sản xuất kính cường lực.'],
        ['name' => 'van-hanh-thiet-bi', 'title' => 'Vận hành thiết bị', 'description' => 'Góc nhìn thực tế về nhân sự và máy móc tại khu vực gia công.'],
        ['name' => 'khong-gian-nha-xuong', 'title' => 'Không gian nhà xưởng', 'description' => 'Theo dõi dãy thiết bị, khu vực làm việc và bố trí bên trong xưởng.'],
        ['name' => 'nha-may-san-xuat-4', 'title' => 'Hoạt động sản xuất tại nhà máy', 'description' => 'Hình ảnh thực tế về quy trình vận hành và sản xuất tại nhà máy Oufeidun.', 'poster' => ''],
        ['name' => 'nha-may-san-xuat-5', 'title' => 'Thiết bị sản xuất thực tế', 'description' => 'Theo dõi thiết bị và các công đoạn sản xuất kính cường lực tại nhà máy.', 'poster' => ''],
    ],
    'product_videos' => [
        'kinh-cuong-luc-caballo-android' => [
            'src' => '/videos/customer-2026-09/full-border-detail.mp4',
            'type' => 'video/mp4',
            'title' => 'Video kính cường lực full viền Oufeidun',
            'poster' => '/images/products/customer-2026-09/full-border-1.png',
        ],
        'kinh-cuong-luc-oufeidun' => [
            'src' => '/videos/customer-2026-09/privacy-detail.mp4',
            'type' => 'video/mp4',
            'title' => 'Video kính cường lực chống nhìn trộm Oufeidun',
            'poster' => '/images/products/customer-2026-09/privacy-1.png',
        ],
        'kinh-cuong-luc-oufeidun-diamond' => [
            'src' => '/videos/customer-2026-09/borderless-detail.mp4',
            'type' => 'video/mp4',
            'title' => 'Video kính cường lực không viền Oufeidun',
            'poster' => '/images/products/customer-2026-09/borderless-1.png',
        ],
    ],
    'testing_videos' => [
        ['name' => 'gia-cong-day-chuyen', 'title' => 'Gia công trên dây chuyền', 'description' => 'Quan sát kính được đưa qua thiết bị gia công tự động tại nhà xưởng.'],
        ['name' => 'kiem-tra-cong-doan', 'title' => 'Kiểm tra công đoạn sản xuất', 'description' => 'Cận cảnh thiết bị xử lý và kiểm tra kính trong quá trình sản xuất.'],
        ['name' => 'kiem-tra-do-cung-9h', 'title' => 'Kiểm tra độ cứng 9H', 'description' => 'Thao tác kiểm tra khả năng hạn chế trầy xước trực tiếp trên bề mặt kính.'],
    ],
    'news' => [
        ['id'=>1,'title'=>'Giới thiệu kính cường lực Caballo cho điện thoại Android','slug'=>'sap-ra-mat-kinh-cuong-luc-caballo-android','excerpt'=>'Dòng Caballo Android có độ cứng 9H, độ dày 0,33 mm và nhiều form máy để lựa chọn theo nhu cầu.','date'=>'2026-03-03','image'=>'/images/feedback-2026-09/product-overview.webp','category'=>'Sản phẩm'],
        ['id'=>2,'title'=>'Thông số kính cường lực Caballo dành cho iPhone','slug'=>'kinh-cuong-luc-caballo-series-iphone-17','excerpt'=>'Tìm hiểu độ cứng 9H, kính dày 0,25 mm, lớp keo 0,38 mm và các phiên bản dành cho iPhone 6–17.','date'=>'2025-09-18','image'=>'/images/client-2026-09/news-iphone.webp','category'=>'Sản phẩm'],
        ['id'=>3,'title'=>'Các đặc điểm nổi bật của kính cường lực Caballo','slug'=>'kinh-cuong-luc-caballo-giai-phap-bao-ve-man-hinh-toan-dien','excerpt'=>'Khám phá độ cứng 9H, độ trong suốt, cảm ứng và lớp phủ nano qua hình ảnh sản phẩm thực tế.','date'=>'2025-09-03','image'=>'/images/client-2026-09/news-features.webp','category'=>'Sản phẩm'],
        ['id'=>4,'title'=>'Tìm hiểu dòng Diamond Curved Glass của Oufeidun','slug'=>'oufeidun-sap-ra-mat-kinh-cuong-luc-dien-thoai','excerpt'=>'Dòng kính cong 3D có độ cứng 9H, độ dày 0,5 mm và tính năng chống tĩnh điện ESD.','date'=>'2025-07-25','image'=>'/images/feedback-2026-09/diamond-curved-glass.jpg','category'=>'Sản phẩm'],
        ['id'=>5,'title'=>'Cách chọn kính cường lực phù hợp với điện thoại','slug'=>'kinh-cuong-luc-dien-thoai-oufeidun','excerpt'=>'Những thông tin cần kiểm tra khi lựa chọn kính theo dòng máy, kích thước và nhu cầu sử dụng.','date'=>'2025-07-25','image'=>'/images/client-2026-09/news-selection.webp','category'=>'Hướng dẫn'],
    ],
];
