<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class ProductRepository
{
    public function all(bool $admin = false): array
    {
        try {
            $sql = 'SELECT id,name,slug,category,description,badge,image,features,specifications,gallery,published,featured,sort_order AS sortOrder,show_factory AS showFactory FROM products';
            if (!$admin) $sql .= ' WHERE published = 1';
            $sql .= ' ORDER BY sort_order,id';
            $rows = Database::connection()->query($sql)->fetchAll();
            return array_map([$this, 'hydrate'], $rows);
        } catch (\Throwable) {
            return $this->fallback();
        }
    }

    public function findBySlug(string $slug): ?array
    {
        try {
            $statement = Database::connection()->prepare('SELECT id,name,slug,category,description,badge,image,features,specifications,gallery,published,featured,sort_order AS sortOrder,show_factory AS showFactory FROM products WHERE slug=? AND published=1 LIMIT 1');
            $statement->execute([$slug]);
            $row = $statement->fetch();
            return $row ? $this->hydrate($row) : null;
        } catch (\Throwable) {
            foreach ($this->fallback() as $product) if ($product['slug'] === $slug) return $product;
            return null;
        }
    }

    private function hydrate(array $row): array
    {
        foreach (['features', 'specifications', 'gallery'] as $field) {
            if (is_string($row[$field] ?? null)) $row[$field] = json_decode($row[$field], true) ?: [];
        }
        return $row;
    }

    private function fallback(): array
    {
        return [
            ['id'=>1,'name'=>'Kính cường lực full viền Oufeidun','slug'=>'kinh-cuong-luc-caballo-android','category'=>'Kính cường lực','description'=>'Kính cường lực Oufeidun gồm 02 dòng chính: Android và iPhone, độ dày 0,33 mm, bề mặt trong suốt và cảm ứng mượt mà. Thiết kế bao bì ngựa xanh cho dòng Android và ngựa đỏ cho iPhone; liên hệ để chọn từng form máy.','badge'=>'Mới','image'=>'/images/products/customer-2026-09/full-border-1.png','features'=>['Độ cứng 9H, hỗ trợ hạn chế trầy xước bề mặt','Bề mặt trong suốt, cảm ứng mượt mà','Độ dày kính 0,33 mm','Bao bì ngựa xanh cho Android và ngựa đỏ cho iPhone'],'specifications'=>[['label'=>'Thương hiệu','value'=>'Oufeidun'],['label'=>'Dòng sản phẩm','value'=>'Kính cường lực full viền'],['label'=>'Độ cứng','value'=>'9H'],['label'=>'Độ dày kính','value'=>'0,33 mm'],['label'=>'Chức năng','value'=>'Bảo vệ màn hình điện thoại'],['label'=>'Tương thích','value'=>'Android và iPhone — liên hệ để chọn đúng form máy']],'gallery'=>['/images/products/customer-2026-09/full-border-1.png','/images/products/customer-2026-09/full-border-3.png','/images/products/customer-2026-09/full-border-2.png','/images/products/customer-2026-09/full-border-4.png','/images/products/customer-2026-09/full-border-5.png'],'published'=>true,'featured'=>true,'sortOrder'=>1,'showFactory'=>true],
            ['id'=>2,'name'=>'Kính cường lực chống nhìn trộm Oufeidun','slug'=>'kinh-cuong-luc-oufeidun','category'=>'Kính cường lực','description'=>'Kính cường lực chống nhìn trộm 180 độ, 2 chiều, bảo vệ màn hình riêng tư tối ưu. Thiết kế ngựa lửa nổi bật, lớp phủ nano hạn chế bám vân tay, kết hợp độ trong suốt cao và trải nghiệm cảm ứng mượt mà.','badge'=>'Hot','image'=>'/images/products/customer-2026-09/privacy-1.png','features'=>['Chống nhìn trộm 180 độ, bảo vệ hai chiều','Độ cứng 9H','Lớp phủ nano hạn chế bám vân tay','Hình ngựa lửa nổi bật, viền cong 3D'],'specifications'=>[['label'=>'Thương hiệu','value'=>'Oufeidun'],['label'=>'Dòng sản phẩm','value'=>'Kính cường lực chống nhìn trộm'],['label'=>'Chất liệu','value'=>'Kính cường lực cao cấp'],['label'=>'Độ cứng','value'=>'9H'],['label'=>'Độ dày kính','value'=>'0,33 mm'],['label'=>'Thiết kế','value'=>'Hình ngựa lửa, viền cong 3D'],['label'=>'Tương thích','value'=>'iPhone 6–17 — chọn kính theo đúng model']],'gallery'=>['/images/products/customer-2026-09/privacy-1.png','/images/products/customer-2026-09/privacy-2.png','/images/products/customer-2026-09/privacy-3.png','/images/products/customer-2026-09/privacy-4.png'],'published'=>true,'featured'=>true,'sortOrder'=>2,'showFactory'=>true],
            ['id'=>3,'name'=>'Kính cường lực không viền cao cấp Oufeidun','slug'=>'kinh-cuong-luc-oufeidun-diamond','category'=>'Kính cường lực','description'=>'Oufeidun – Cường lực không viền mang thiết kế tinh tế, ôm sát màn hình, tối ưu trải nghiệm hiển thị và cảm ứng tự nhiên. Với vật liệu cao cấp, keo AB 380 và lớp phủ AF chống bám vân tay, sản phẩm giúp màn hình luôn trong sạch, mượt mà và hạn chế trầy xước trong quá trình sử dụng.','badge'=>'Diamond','image'=>'/images/products/customer-2026-09/borderless-1.png','features'=>['Độ cong viền 300cc bo sát viền điện thoại, hình ảnh trải nghiệm cao','Vật liệu cao cấp dày 0.4mm','Keo AB 380','Tích điện mặt AF chống bám vân tay bám bẩn, tăng độ mượt mà','Có lưới nhôm chống bụi ở loa'],'specifications'=>[['label'=>'Thương hiệu','value'=>'Oufeidun'],['label'=>'Dòng sản phẩm','value'=>'Cường lực không viền cao cấp'],['label'=>'Độ cứng','value'=>'9H'],['label'=>'Độ dày kính','value'=>'0,4 mm'],['label'=>'Thiết kế','value'=>'3D Curved Edge - không viền']],'gallery'=>['/images/products/customer-2026-09/borderless-1.png','/images/products/customer-2026-09/borderless-2.png','/images/products/customer-2026-09/borderless-3.png','/images/products/customer-2026-09/borderless-4.png'],'published'=>true,'featured'=>true,'sortOrder'=>3,'showFactory'=>false],
        ];
    }
}
