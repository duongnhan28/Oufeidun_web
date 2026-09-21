<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
use App\Core\Database;
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$path=$argv[1]??BASE_PATH.'/storage/import/legacy-products.snapshot.json';$apply=in_array('--apply',$argv,true);
if(!is_file($path)){fwrite(STDERR,"Snapshot not found: {$path}\n");exit(1);}
$snapshot=json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR);$products=$snapshot['products']??[];
$modelCount=array_sum(array_map(fn($p)=>count($p['models']??[]),$products));$imageCount=array_sum(array_map(fn($p)=>count($p['images']??[]),$products));
echo "Snapshot: ".count($products)." products, {$modelCount} models, {$imageCount} images\n";
if(!$apply){echo "Dry run only. Add --apply after configuring MySQL.\n";exit(0);}
$pdo=Database::connection();$pdo->beginTransaction();
try{
 foreach($products as$product){
  $s=$pdo->prepare("INSERT INTO glass_lookup_products(legacy_id,sku,name,slug,description,is_featured,active,source,legacy_created_at,legacy_updated_at) VALUES(?,?,?,?,?,?,1,'legacy',?,?) ON DUPLICATE KEY UPDATE sku=VALUES(sku),name=VALUES(name),slug=VALUES(slug),description=VALUES(description),is_featured=VALUES(is_featured),legacy_updated_at=VALUES(legacy_updated_at)");
  $s->execute([$product['id'],trim($product['sku']),trim($product['name']),trim($product['slug']),$product['description']??'',(int)!empty($product['isFeatured']),sqlDate($product['createdAt']??null),sqlDate($product['updatedAt']??null)]);
  $s=$pdo->prepare('SELECT id FROM glass_lookup_products WHERE legacy_id=?');$s->execute([$product['id']]);$productId=(int)$s->fetchColumn();
  $pdo->prepare('DELETE FROM glass_lookup_models WHERE product_id=?')->execute([$productId]);
  foreach(array_values($product['models']??[])as$index=>$model){$raw=(string)$model['modelName'];$normalized=normalizeModel($raw);$s=$pdo->prepare('INSERT INTO glass_lookup_models(legacy_id,product_id,raw_model_name,display_name,normalized_name,brand,glass_type,sort_order,review_status) VALUES(?,?,?,?,?,?,?,?,?)');$s->execute([$model['id'],$productId,$raw,$normalized['display'],$normalized['normalized'],$normalized['brand'],$normalized['type'],$index,$normalized['review']]);}
  $pdo->prepare('DELETE FROM glass_lookup_images WHERE product_id=?')->execute([$productId]);
  foreach(array_values($product['images']??[])as$index=>$image){$imageId=$image['id'];$exists=$pdo->prepare('SELECT 1 FROM product_images WHERE id=?');$exists->execute([$imageId]);if(!$exists->fetchColumn()){$binary=downloadImage($image['imageUrl']);$s=$pdo->prepare('INSERT INTO product_images(id,mime_type,data) VALUES(?,?,?)');$s->bindValue(1,$imageId);$s->bindValue(2,$binary['mime']);$s->bindValue(3,$binary['data'],PDO::PARAM_LOB);$s->execute();}$s=$pdo->prepare('INSERT INTO glass_lookup_images(legacy_id,product_id,image_id,source_url,sort_order) VALUES(?,?,?,?,?)');$s->execute([$image['id'],$productId,$imageId,$image['imageUrl'],$index]);}
 }
 $pdo->commit();echo "Import completed.\n";
}catch(Throwable$error){if($pdo->inTransaction())$pdo->rollBack();fwrite(STDERR,"Import failed: {$error->getMessage()}\n");exit(1);}
function sqlDate(?string$value):?string{return$value?date('Y-m-d H:i:s',strtotime($value)):null;}
function lookupText(string$value):string{$ascii=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',trim($value));$ascii=strtoupper($ascii===false?$value:$ascii);return trim((string)preg_replace('/[^A-Z0-9]+/',' ',$ascii));}
function normalizeModel(string $raw): array
{
    $compact = trim((string)preg_replace('/\s+/u', ' ', $raw));
    $privacy = (bool)preg_match('/\(\s*CH[ỐO]NG\s+NH[ÌI]N\s+TR[ỘO]M\s*\)/iu', $compact);
    $display = trim((string)preg_replace('/\s*\(\s*CH[ỐO]NG\s+NH[ÌI]N\s+TR[ỘO]M\s*\)\s*/iu', ' ', $compact));
    $normalized = lookupText($display);
    $brand = 'other';
    foreach (['IP'=>'apple','IPHONE'=>'apple','SAM'=>'samsung','SAMSUNG'=>'samsung','OPPO'=>'oppo','VIVO'=>'vivo','REALME'=>'realme','REDMI'=>'xiaomi','XIAOMI'=>'xiaomi','RM'=>'xiaomi','POCO'=>'poco','MOTOROLA'=>'motorola','HUAWEI'=>'huawei','TECNO'=>'tecno','HONOR'=>'honor','ONEPLUS'=>'oneplus','INFINIX'=>'infinix','ZTE'=>'zte','IQOO'=>'iqoo','LG'=>'lg'] as $prefix => $candidate) {
        if (preg_match('/^'.preg_quote($prefix, '/').'\b/', $normalized)) { $brand = $candidate; break; }
    }
    $normalized = preg_replace('/^IP(?=\s|$)/', 'IPHONE', $normalized) ?? $normalized;
    $normalized = preg_replace('/^SAM(?=\s|$)/', 'SAMSUNG', $normalized) ?? $normalized;
    $normalized = preg_replace('/^RM(?=\s|$)/', 'REDMI', $normalized) ?? $normalized;
    $review = substr_count($compact, '(') !== substr_count($compact, ')') || preg_match('/[\x{3400}-\x{9fff}]/u', $compact) ? 'needs_review' : 'clean';
    return ['display'=>$display,'normalized'=>$normalized,'brand'=>$brand,'type'=>$privacy?'privacy':'standard','review'=>$review];
}
function downloadImage(string$url):array{$context=stream_context_create(['http'=>['timeout'=>30,'follow_location'=>1,'user_agent'=>'OufeidunMigration/1.0']]);$data=file_get_contents($url,false,$context);if($data===false||strlen($data)>5242880)throw new RuntimeException('Invalid image download: '.$url);$mime=(new finfo(FILEINFO_MIME_TYPE))->buffer($data);if(!in_array($mime,['image/jpeg','image/png','image/webp'],true)||!getimagesizefromstring($data))throw new RuntimeException('Invalid image MIME: '.$url);return['mime'=>$mime,'data'=>$data];}
