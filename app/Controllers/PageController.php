<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\ProductRepository;

final class PageController
{
    public function __construct(private readonly ProductRepository $products = new ProductRepository()) {}

    public function home(): void { view('pages/home', ['title'=>'Oufeidun - Kính cường lực điện thoại','products'=>$this->products->all()]); }
    public function products(): void { view('pages/products', ['title'=>'Sản phẩm - Oufeidun','description'=>'Các dòng kính cường lực Caballo chất lượng cao dành cho Android và iPhone.','products'=>$this->products->all()]); }
    public function product(array $params): void
    {
        $product = $this->products->findBySlug(rawurldecode($params['slug']));
        if (!$product) { http_response_code(404); view('pages/404', ['title'=>'Không tìm thấy sản phẩm']); return; }
        view('pages/product-detail', ['title'=>$product['name'].' - Oufeidun','description'=>$product['description'],'product'=>$product]);
    }
    public function oem(): void { view('pages/oem', ['title'=>'Dịch vụ OEM - Oufeidun','description'=>'Phát triển, sản xuất và đóng gói kính cường lực theo yêu cầu thương hiệu.']); }
    public function distribution(): void { view('pages/distribution', ['title'=>'Đại lý phân phối - Oufeidun','description'=>'Tìm hiểu hệ thống đại lý phân phối kính cường lực Oufeidun trên toàn quốc.']); }
    public function businessAreas(): void { view('pages/business-areas', ['title'=>'Lĩnh vực hoạt động - Oufeidun','description'=>'Lĩnh vực phân phối kính cường lực và sản xuất OEM của Oufeidun.']); }
    public function about(): void { view('pages/about', ['title'=>'Giới thiệu - Oufeidun','description'=>'Tìm hiểu hành trình phát triển, tầm nhìn và giá trị cốt lõi của Oufeidun Việt Nam.']); }
    public function news(): void { view('pages/news', ['title'=>'Tin tức - Oufeidun','description'=>'Thông tin sản phẩm và hướng dẫn lựa chọn kính cường lực Oufeidun.']); }
    public function article(array $params): void
    {
        $site = require BASE_PATH . '/config/site.php';
        $article = null;
        foreach ($site['news'] as $item) if ($item['slug'] === rawurldecode($params['slug'])) $article = $item;
        if (!$article) { http_response_code(404); view('pages/404', ['title'=>'Không tìm thấy bài viết']); return; }
        view('pages/article', ['title'=>$article['title'].' - Oufeidun','description'=>$article['excerpt'],'article'=>$article,'articles'=>$site['news']]);
    }
    public function contact(): void { view('pages/contact', ['title'=>'Liên hệ - Oufeidun']); }
    public function login(): void { view('pages/login', ['title'=>'Đăng nhập - Oufeidun'], 'layouts/admin'); }
}
