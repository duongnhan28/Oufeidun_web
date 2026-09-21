<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\ProductRepository;

final class PageController
{
    public function __construct(private readonly ProductRepository $products = new ProductRepository()) {}

    public function home(): void { view('pages/home', ['title'=>'Oufeidun - Kính cường lực điện thoại','products'=>$this->products->all()]); }
    public function products(): void { view('pages/products', ['title'=>'Sản phẩm - Oufeidun','products'=>$this->products->all()]); }
    public function product(array $params): void
    {
        $product = $this->products->findBySlug(rawurldecode($params['slug']));
        if (!$product) { http_response_code(404); view('pages/404', ['title'=>'Không tìm thấy sản phẩm']); return; }
        view('pages/product-detail', ['title'=>$product['name'].' - Oufeidun','product'=>$product]);
    }
    public function oem(): void { view('pages/oem', ['title'=>'Dịch vụ OEM - Oufeidun']); }
    public function distribution(): void { view('pages/distribution', ['title'=>'Đại lý phân phối - Oufeidun']); }
    public function about(): void { view('pages/about', ['title'=>'Giới thiệu - Oufeidun']); }
    public function news(): void { view('pages/news', ['title'=>'Tin tức - Oufeidun']); }
    public function article(array $params): void
    {
        $site = require BASE_PATH . '/config/site.php';
        $article = null;
        foreach ($site['news'] as $item) if ($item['slug'] === rawurldecode($params['slug'])) $article = $item;
        if (!$article) { http_response_code(404); view('pages/404', ['title'=>'Không tìm thấy bài viết']); return; }
        view('pages/article', ['title'=>$article['title'].' - Oufeidun','article'=>$article,'articles'=>$site['news']]);
    }
    public function contact(): void { view('pages/contact', ['title'=>'Liên hệ - Oufeidun']); }
    public function login(): void { view('pages/login', ['title'=>'Đăng nhập - Oufeidun'], 'layouts/admin'); }
}

