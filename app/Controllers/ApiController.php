<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Env;
use App\Core\Response;
use PDO;
use App\Core\Mailer;

final class ApiController
{
    public function lookup(): never
    {
        if (!Env::bool('GLASS_LOOKUP_ENABLED', true)) Response::json(['error'=>'Không tìm thấy.'], 404);
        $query = trim((string)($_GET['q'] ?? ''));
        $brand = trim((string)($_GET['brand'] ?? 'all'));
        $type = trim((string)($_GET['type'] ?? 'all'));
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
        if (mb_strlen($query) > 80) Response::json(['error'=>'Bộ lọc tra cứu không hợp lệ.'], 400);
        $normalized = self::normalize($query);
        if ($query !== '' && mb_strlen($normalized) < 2) Response::json(['query'=>$query,'total'=>0,'suggestions'=>[],'items'=>[]]);

        try {
            $pdo = Database::connection();
            $where = ['p.active=1']; $values = [];
            if ($brand !== 'all') { $where[]='m.brand=?'; $values[]=$brand; }
            if ($type !== 'all') { $where[]='m.glass_type=?'; $values[]=$type; }
            if ($normalized !== '') { $where[]='(UPPER(p.sku) LIKE ? OR m.normalized_name LIKE ?)'; $values[]='%'.$normalized.'%';$values[]='%'.$normalized.'%'; }
            $sql = 'SELECT DISTINCT p.id,p.sku,p.name FROM glass_lookup_products p JOIN glass_lookup_models m ON m.product_id=p.id WHERE '.implode(' AND ',$where).' ORDER BY CASE WHEN UPPER(p.sku)=? THEN 0 WHEN UPPER(p.sku) LIKE ? THEN 1 ELSE 2 END,p.sku LIMIT '.$limit;
            $values[]=$normalized;$values[]=$normalized.'%';
            $statement=$pdo->prepare($sql);$statement->execute($values);$products=$statement->fetchAll();
            $items=[];
            foreach($products as $product){
                $models=$pdo->prepare('SELECT display_name,normalized_name,glass_type FROM glass_lookup_models WHERE product_id=?'.($type!=='all'?' AND glass_type=?':'').' ORDER BY sort_order,id');
                $models->execute($type!=='all'?[$product['id'],$type]:[$product['id']]);$modelRows=$models->fetchAll();
                $images=$pdo->prepare('SELECT image_id FROM glass_lookup_images WHERE product_id=? ORDER BY sort_order,id');$images->execute([$product['id']]);
                $compatible=array_values(array_unique(array_column($modelRows,'display_name')));
                $matched=array_values(array_unique(array_column(array_filter($modelRows,fn($model)=>$normalized!==''&&str_contains($model['normalized_name'],$normalized)),'display_name')));
                $items[]=['id'=>(int)$product['id'],'sku'=>$product['sku'],'name'=>$product['name'],'glassTypes'=>array_values(array_unique(array_column($modelRows,'glass_type'))),'images'=>array_map(fn($row)=>'/api/images/'.$row['image_id'], $images->fetchAll()),'matchedModels'=>$matched,'compatibleModels'=>$compatible];
            }
            Response::json(['query'=>$query,'total'=>count($items),'suggestions'=>array_slice(array_values(array_unique(array_merge(...array_map(fn($i)=>$i['matchedModels'],$items)) ?: [])),0,8),'items'=>$items]);
        } catch (\Throwable) {
            Response::json(['error'=>'Database tra cứu chưa được kết nối.'], 503);
        }
    }

    public function image(array $params): never
    {
        $id = $params['id'] ?? '';
        if (!preg_match('/^[a-f0-9-]{36}$/i', $id)) { http_response_code(404); exit; }
        try { $s=Database::connection()->prepare('SELECT mime_type,data FROM product_images WHERE id=?');$s->execute([$id]);$image=$s->fetch(); } catch(\Throwable){$image=false;}
        if(!$image){http_response_code(404);exit;} header('Content-Type: '.$image['mime_type']);header('Cache-Control: public,max-age=31536000,immutable');echo $image['data'];exit;
    }

    public function contact(): never
    {
        $data=json_decode(file_get_contents('php://input'),true);
        if(!is_array($data)) Response::json(['error'=>'Dữ liệu không hợp lệ.'],400);
        $token=$_SERVER['HTTP_X_CSRF_TOKEN']??'';
        if(!Csrf::valid($token)) Response::json(['error'=>'Phiên gửi yêu cầu không hợp lệ.'],403);
        if(!empty($data['website'])) Response::json(['ok'=>true]);
        $name=trim((string)($data['name']??''));$phone=trim((string)($data['phone']??''));$email=trim((string)($data['email']??''));$message=trim((string)($data['message']??''));$source=in_array($data['source']??'', ['contact','oem'],true)?$data['source']:'contact';$id=(string)($data['requestId']??'');
        if(mb_strlen($name)<2||mb_strlen($name)>120||mb_strlen($phone)<8||mb_strlen($phone)>30||mb_strlen($message)<5||mb_strlen($message)>5000||($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL))) Response::json(['error'=>'Vui lòng kiểm tra lại thông tin liên hệ.'],400);
        if(!preg_match('/^[a-f0-9-]{36}$/i',$id)) $id=self::uuid();
        try{$pdo=Database::connection();$ip=self::clientIp();$rate=$pdo->prepare('SELECT COUNT(*) FROM contact_messages WHERE ip=? AND created_at>DATE_SUB(NOW(),INTERVAL 10 MINUTE)');$rate->execute([$ip]);if((int)$rate->fetchColumn()>=3)Response::json(['error'=>'Bạn đã gửi nhiều yêu cầu. Vui lòng thử lại sau 10 phút.'],429,['Retry-After'=>'600']);$insert=$pdo->prepare('INSERT IGNORE INTO contact_messages(id,name,phone,email,message,source,ip) VALUES(?,?,?,?,?,?,?)');$insert->execute([$id,$name,$phone,$email,$message,$source,$ip]);if($insert->rowCount()){try{$pdo->prepare("UPDATE contact_messages SET mail_status='sending',last_attempt_at=NOW() WHERE id=?")->execute([$id]);$html='<h2>Yêu cầu mới từ website Oufeidun</h2><p><b>Họ tên:</b> '.htmlspecialchars($name).'</p><p><b>Điện thoại:</b> '.htmlspecialchars($phone).'</p><p><b>Email:</b> '.htmlspecialchars($email).'</p><p><b>Nội dung:</b><br>'.nl2br(htmlspecialchars($message)).'</p>';Mailer::send('Yêu cầu tư vấn mới - '.$name,$html,$email);$pdo->prepare("UPDATE contact_messages SET mail_status='sent' WHERE id=?")->execute([$id]);}catch(\Throwable){$pdo->prepare("UPDATE contact_messages SET mail_status='failed' WHERE id=?")->execute([$id]);}}}catch(\Throwable){Response::json(['error'=>'Hệ thống liên hệ chưa kết nối database.'],503);}
        Response::json(['ok'=>true,'message'=>'Đã tiếp nhận yêu cầu. Oufeidun sẽ liên hệ lại với bạn.']);
    }

    private static function normalize(string $value): string
    {
        $value=mb_strtoupper(trim($value),'UTF-8');$value=preg_replace('/[^A-Z0-9]+/u',' ',$value)??$value;$value=preg_replace('/\s+/',' ',$value)??$value;
        if(preg_match('/^IP(?: |$)/',$value))$value=preg_replace('/^IP/','IPHONE',$value);if(preg_match('/^SAM(?: |$)/',$value))$value=preg_replace('/^SAM/','SAMSUNG',$value);return trim($value);
    }
    private static function clientIp(): string { return substr((string)($_SERVER['REMOTE_ADDR']??'127.0.0.1'),0,45); }
    private static function uuid(): string { $d=random_bytes(16);$d[6]=chr((ord($d[6])&0x0f)|0x40);$d[8]=chr((ord($d[8])&0x3f)|0x80);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($d),4)); }
}
