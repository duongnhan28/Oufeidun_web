<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Auth;use App\Core\Csrf;use App\Core\Database;use App\Core\Response;
final class AdminApiController
{
    private function guard():array{$user=Auth::user();if(!$user)Response::json(['error'=>'Phiên đăng nhập đã hết hạn.'],401);$token=$_SERVER['HTTP_X_CSRF_TOKEN']??($_POST['_csrf']??'');if(!Csrf::valid($token))Response::json(['error'=>'Phiên thao tác không hợp lệ.'],403);return$user;}
    private function json():array{$body=json_decode(file_get_contents('php://input'),true);if(!is_array($body))Response::json(['error'=>'Dữ liệu không hợp lệ.'],400);return$body;}
    public function products():never{$this->guard();Response::json(['items'=>(new \App\Repositories\ProductRepository())->all(true)]);}
    public function saveProduct(array $params=[]):never
    {
        $this->guard();$data=$this->json();$id=isset($params['id'])?(int)$params['id']:null;
        foreach(['name','slug','category','description','image']as$field)if(trim((string)($data[$field]??''))==='')Response::json(['error'=>'Thiếu thông tin '.$field.'.'],400);
        if(!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/',(string)$data['slug']))Response::json(['error'=>'Slug không hợp lệ.'],400);
        foreach(['features','specifications','gallery']as$field)if(!is_array($data[$field]??null))$data[$field]=[];
        $values=[trim($data['name']),trim($data['slug']),trim($data['category']),trim($data['description']),trim((string)($data['badge']??'')),trim($data['image']),json_encode($data['features'],JSON_UNESCAPED_UNICODE),json_encode($data['specifications'],JSON_UNESCAPED_UNICODE),json_encode($data['gallery'],JSON_UNESCAPED_UNICODE),(int)!empty($data['published']),(int)!empty($data['featured']),max(0,(int)($data['sortOrder']??0)),(int)!empty($data['showFactory'])];
        try{$pdo=Database::connection();$oldPaths=[];if($id){$old=$pdo->prepare('SELECT image,gallery FROM products WHERE id=?');$old->execute([$id]);$row=$old->fetch();if(!$row)Response::json(['error'=>'Không tìm thấy sản phẩm.'],404);$oldPaths=array_merge([$row['image']],json_decode($row['gallery'],true)?:[]);$sql='UPDATE products SET name=?,slug=?,category=?,description=?,badge=?,image=?,features=?,specifications=?,gallery=?,published=?,featured=?,sort_order=?,show_factory=? WHERE id=?';$values[]=$id;$s=$pdo->prepare($sql);$s->execute($values);}else{$s=$pdo->prepare('INSERT INTO products(name,slug,category,description,badge,image,features,specifications,gallery,published,featured,sort_order,show_factory) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)');$s->execute($values);$id=(int)$pdo->lastInsertId();}self::cleanupImages($pdo,$oldPaths);Response::json(['ok'=>true,'id'=>$id]);}catch(\PDOException $error){Response::json(['error'=>$error->getCode()==='23000'?'Tên đường dẫn đã tồn tại.':'Không thể lưu sản phẩm.'],409);}
    }
    public function deleteProduct(array $params):never{$this->guard();$pdo=Database::connection();$old=$pdo->prepare('SELECT image,gallery FROM products WHERE id=?');$old->execute([(int)$params['id']]);$row=$old->fetch();$paths=$row?array_merge([$row['image']],json_decode($row['gallery'],true)?:[]):[];$s=$pdo->prepare('DELETE FROM products WHERE id=?');$s->execute([(int)$params['id']]);self::cleanupImages($pdo,$paths);Response::json(['ok'=>(bool)$s->rowCount()]);}
    public function upload():never
    {
        $this->guard();$file=$_FILES['image']??null;if(!$file||$file['error']!==UPLOAD_ERR_OK)Response::json(['error'=>'Không nhận được ảnh tải lên.'],400);if($file['size']>5242880)Response::json(['error'=>'Ảnh không được vượt quá 5 MB.'],400);$mime=(new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);if(!in_array($mime,['image/jpeg','image/png','image/webp'],true))Response::json(['error'=>'Chỉ hỗ trợ JPG, PNG hoặc WebP.'],400);if(!getimagesize($file['tmp_name']))Response::json(['error'=>'Tệp ảnh không hợp lệ.'],400);$id=self::uuid();$data=file_get_contents($file['tmp_name']);$s=Database::connection()->prepare('INSERT INTO product_images(id,mime_type,data) VALUES(?,?,?)');$s->bindValue(1,$id);$s->bindValue(2,$mime);$s->bindValue(3,$data,\PDO::PARAM_LOB);$s->execute();Response::json(['ok'=>true,'path'=>'/api/images/'.$id]);
    }
    public function lookup():never
    {
        Auth::requireUser();$pdo=Database::connection();$products=$pdo->query('SELECT id,sku,name,description,active,source,updated_at FROM glass_lookup_products ORDER BY updated_at DESC,sku')->fetchAll();foreach($products as &$p){$s=$pdo->prepare('SELECT display_name AS name,glass_type AS glassType FROM glass_lookup_models WHERE product_id=? ORDER BY sort_order,id');$s->execute([$p['id']]);$p['models']=$s->fetchAll();$s=$pdo->prepare('SELECT image_id FROM glass_lookup_images WHERE product_id=? ORDER BY sort_order,id');$s->execute([$p['id']]);$p['images']=array_map(fn($r)=>'/api/images/'.$r['image_id'],$s->fetchAll());$p['active']=(bool)$p['active'];}Response::json(['items'=>$products]);
    }
    public function saveLookup(array $params=[]):never
    {
        $this->guard();
        $data=$this->json();
        $id=isset($params['id'])?(int)$params['id']:null;
        $sku=trim((string)($data['sku']??''));
        $name=trim((string)($data['name']??''));
        $models=$data['models']??[];
        $images=array_slice(array_values(array_unique($data['images']??[])),0,2);
        if($sku===''||$name===''||!is_array($models)||count($models)<1) Response::json(['error'=>'SKU, tên và ít nhất một dòng máy là bắt buộc.'],400);
        $pdo=Database::connection();
        $pdo->beginTransaction();
        try{
            $oldImagePaths=[];
            if($id){
                $oldImages=$pdo->prepare('SELECT CONCAT("/api/images/",image_id) FROM glass_lookup_images WHERE product_id=?');$oldImages->execute([$id]);$oldImagePaths=$oldImages->fetchAll(\PDO::FETCH_COLUMN);
                $s=$pdo->prepare('UPDATE glass_lookup_products SET sku=?,name=?,description=?,active=? WHERE id=?');
                $s->execute([$sku,$name,trim((string)($data['description']??'')),(int)!empty($data['active']),$id]);
                $productId=$id;
            }else{
                $legacy=self::uuid();
                $slug=strtolower(trim((string)preg_replace('/[^a-zA-Z0-9]+/','-',$sku),'-')).'-'.substr($legacy,0,8);
                $s=$pdo->prepare("INSERT INTO glass_lookup_products(legacy_id,sku,name,slug,description,active,source) VALUES(?,?,?,?,?,?,'admin')");
                $s->execute([$legacy,$sku,$name,$slug,trim((string)($data['description']??'')),(int)!empty($data['active'])]);
                $productId=(int)$pdo->lastInsertId();
            }
            $pdo->prepare('DELETE FROM glass_lookup_models WHERE product_id=?')->execute([$productId]);
            $pdo->prepare('DELETE FROM glass_lookup_images WHERE product_id=?')->execute([$productId]);
            foreach(array_values($models) as $index=>$model){
                $modelName=trim((string)($model['name']??''));
                $type=in_array($model['glassType']??'',['standard','privacy','unknown'],true)?$model['glassType']:'standard';
                if($modelName==='') continue;
                $normalized=self::normalize($modelName);
                $s=$pdo->prepare('INSERT INTO glass_lookup_models(legacy_id,product_id,raw_model_name,display_name,normalized_name,brand,glass_type,sort_order,review_status) VALUES(?,?,?,?,?,?,?,?,?)');
                $s->execute([self::uuid(),$productId,$modelName,$modelName,$normalized,self::brand($normalized),$type,$index,'clean']);
            }
            foreach($images as $index=>$path){
                if(!preg_match('#^/api/images/([a-f0-9-]{36})$#i',$path,$match)) continue;
                $s=$pdo->prepare('INSERT INTO glass_lookup_images(legacy_id,product_id,image_id,source_url,sort_order) VALUES(?,?,?,?,?)');
                $s->execute([self::uuid(),$productId,$match[1],$path,$index]);
            }
            $pdo->commit();
            self::cleanupImages($pdo,$oldImagePaths);
            Response::json(['ok'=>true,'id'=>$productId]);
        }catch(\Throwable $error){
            if($pdo->inTransaction()) $pdo->rollBack();
            $duplicate=$error instanceof \PDOException&&$error->getCode()==='23000';
            Response::json(['error'=>$duplicate?'SKU đã tồn tại.':'Không thể lưu mã kính.'],409);
        }
    }
    public function deleteLookup(array$params):never{$this->guard();$pdo=Database::connection();$images=$pdo->prepare('SELECT CONCAT("/api/images/",image_id) FROM glass_lookup_images WHERE product_id=?');$images->execute([(int)$params['id']]);$paths=$images->fetchAll(\PDO::FETCH_COLUMN);$s=$pdo->prepare('DELETE FROM glass_lookup_products WHERE id=?');$s->execute([(int)$params['id']]);self::cleanupImages($pdo,$paths);Response::json(['ok'=>(bool)$s->rowCount()]);}
    public function resendMessage(array $params):never
    {
        $this->guard();$pdo=Database::connection();$s=$pdo->prepare('SELECT id,name,phone,email,message FROM contact_messages WHERE id=?');$s->execute([(string)($params['id']??'')]);$message=$s->fetch();if(!$message)Response::json(['error'=>'Không tìm thấy tin nhắn.'],404);
        try{$pdo->prepare("UPDATE contact_messages SET mail_status='sending',last_attempt_at=NOW() WHERE id=?")->execute([$message['id']]);$html='<h2>Yêu cầu từ website Oufeidun</h2><p><b>Họ tên:</b> '.htmlspecialchars($message['name']).'</p><p><b>Điện thoại:</b> '.htmlspecialchars($message['phone']).'</p><p><b>Email:</b> '.htmlspecialchars($message['email']).'</p><p><b>Nội dung:</b><br>'.nl2br(htmlspecialchars($message['message'])).'</p>';\App\Core\Mailer::send('Yêu cầu tư vấn - '.$message['name'],$html,$message['email']);$pdo->prepare("UPDATE contact_messages SET mail_status='sent' WHERE id=?")->execute([$message['id']]);Response::json(['ok'=>true,'status'=>'sent']);}catch(\Throwable){$pdo->prepare("UPDATE contact_messages SET mail_status='failed' WHERE id=?")->execute([$message['id']]);Response::json(['error'=>'Gửi mail thất bại. Vui lòng kiểm tra cấu hình SMTP.'],503);}
    }
    private static function cleanupImages(\PDO $pdo,array $paths):void
    {
        foreach(array_unique($paths) as $path){if(!preg_match('#^/api/images/([a-f0-9-]{36})$#i',(string)$path,$match))continue;$used=$pdo->prepare("SELECT COUNT(*) FROM products WHERE image=? OR JSON_SEARCH(gallery,'one',?) IS NOT NULL");$used->execute([$path,$path]);if((int)$used->fetchColumn()>0)continue;$lookup=$pdo->prepare('SELECT COUNT(*) FROM glass_lookup_images WHERE image_id=?');$lookup->execute([$match[1]]);if((int)$lookup->fetchColumn()>0)continue;$pdo->prepare('DELETE FROM product_images WHERE id=?')->execute([$match[1]]);}
    }
    private static function normalize(string$value):string{$value=mb_strtoupper(trim($value),'UTF-8');$value=preg_replace('/[^A-Z0-9]+/u',' ',$value)??$value;$value=preg_replace('/\s+/',' ',$value)??$value;if(preg_match('/^IP(?: |$)/',$value))$value=preg_replace('/^IP/','IPHONE',$value);if(preg_match('/^SAM(?: |$)/',$value))$value=preg_replace('/^SAM/','SAMSUNG',$value);return trim($value);}
    private static function brand(string$value):string{foreach(['IPHONE'=>'apple','SAMSUNG'=>'samsung','OPPO'=>'oppo','VIVO'=>'vivo','REALME'=>'realme','REDMI'=>'xiaomi','XIAOMI'=>'xiaomi','POCO'=>'poco','MOTOROLA'=>'motorola','HUAWEI'=>'huawei','TECNO'=>'tecno','HONOR'=>'honor','ONEPLUS'=>'oneplus','INFINIX'=>'infinix','ZTE'=>'zte','IQOO'=>'iqoo','LG'=>'lg']as$term=>$brand)if(str_contains($value,$term))return$brand;return'other';}
    private static function uuid():string{$d=random_bytes(16);$d[6]=chr((ord($d[6])&15)|64);$d[8]=chr((ord($d[8])&63)|128);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($d),4));}
}
