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
            ['id'=>1,'name'=>'Kính cường lực Caballo Android','slug'=>'kinh-cuong-luc-caballo-android','category'=>'Kính cường lực','description'=>'Dòng kính cường lực Caballo dành cho điện thoại Android, chuẩn form máy, độ cứng cao và cảm ứng mượt mà.','badge'=>'Mới','image'=>'/images/client-2026-09/caballo-android.webp','features'=>['Độ cứng 9H','Độ dày kính 0,33 mm','Nhiều form máy Android','Cảm ứng ổn định'],'specifications'=>[['label'=>'Độ cứng','value'=>'9H'],['label'=>'Độ dày','value'=>'0,33 mm'],['label'=>'Thiết kế','value'=>'Chuẩn form điện thoại Android']],'gallery'=>['/images/client-2026-09/caballo-android.webp','/images/products/2026-09/caballo-android-lifestyle.webp'],'published'=>true,'featured'=>true,'sortOrder'=>1,'showFactory'=>true],
            ['id'=>2,'name'=>'Kính cường lực Caballo iPhone','slug'=>'kinh-cuong-luc-oufeidun','category'=>'Kính cường lực','description'=>'Kính cường lực Caballo cho iPhone với độ cứng 9H, hạn chế trầy xước và va đập, đảm bảo độ trong suốt cùng trải nghiệm cảm ứng ổn định.','badge'=>'Hot','image'=>'/images/client-2026-09/caballo-iphone.webp','features'=>['Độ cứng 9H','Kính dày 0,25 mm','Lớp keo 0,38 mm','Dành cho iPhone 6–17'],'specifications'=>[['label'=>'Độ cứng','value'=>'9H'],['label'=>'Độ dày kính','value'=>'0,25 mm'],['label'=>'Lớp keo','value'=>'0,38 mm']],'gallery'=>['/images/client-2026-09/caballo-iphone.webp'],'published'=>true,'featured'=>true,'sortOrder'=>2,'showFactory'=>true],
            ['id'=>3,'name'=>'Kính cường lực Oufeidun Diamond Curved Glass','slug'=>'kinh-cuong-luc-oufeidun-diamond','category'=>'Kính cường lực','description'=>'Dòng kính cong 3D, độ cứng 9H và thiết kế cao cấp dành cho các model phù hợp.','badge'=>'Diamond','image'=>'/images/client-2026-09/diamond.webp','features'=>['Thiết kế cạnh cong 3D','Độ cứng 9H','Chống tĩnh điện ESD','Độ dày 0,5 mm'],'specifications'=>[['label'=>'Độ cứng','value'=>'9H'],['label'=>'Độ dày','value'=>'0,5 mm'],['label'=>'Thiết kế','value'=>'Cạnh cong 3D']],'gallery'=>['/images/client-2026-09/diamond.webp'],'published'=>true,'featured'=>true,'sortOrder'=>3,'showFactory'=>false],
        ];
    }
}

