<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Database;use App\Core\Env;use App\Core\PosAuth;use App\Core\Response;
use App\Services\PosService;
final class PosController
{
    private function json():array{$length=(int)($_SERVER['CONTENT_LENGTH']??0);if($length>1048576)Response::json(['error'=>'PAYLOAD_TOO_LARGE'],413);$raw=file_get_contents('php://input');if(strlen($raw)>1048576)Response::json(['error'=>'PAYLOAD_TOO_LARGE'],413);$body=json_decode($raw,true);if(!is_array($body))Response::json(['error'=>'VALIDATION_ERROR'],400);return$body;}
    private function fail(\Throwable$error):never{$code=$error->getMessage();$status=match($code){'UNAUTHENTICATED','INVALID_CREDENTIALS','SESSION_REQUIRED'=>401,'SESSION_ALREADY_ACTIVE'=>409,'FORBIDDEN'=>403,'RATE_LIMIT'=>429,default=>400};Response::json(['error'=>$code],$status);}
    public function login():never{$d=$this->json();try{$result=PosAuth::login((string)($d['username']??''),(string)($d['password']??''),(string)($d['device_id']??''),$this->clientIp());Response::json($result);}catch(\Throwable$error){$this->fail($error);}}
    public function touch():never{try{PosAuth::actor();Response::json(['ok'=>true]);}catch(\Throwable$error){$this->fail($error);}}
    public function logout():never{PosAuth::logout();Response::json(['ok'=>true]);}
    public function workspace():never
    {
        try{$actor=PosAuth::actor();$pdo=Database::appConnection();$brands=$pdo->query('SELECT id,name FROM pos_glass_brands ORDER BY name')->fetchAll();$products=$pdo->query("SELECT p.id,p.sku,p.name,p.glass_type,p.models,p.brand,p.glass_brand_id,p.image_path,CAST(p.sale_price AS CHAR) sale_price,p.active,p.version,p.price_version,b.on_hand,b.version stock_version,".($actor['role']==='admin'?"CAST(c.fixed_cost AS CHAR)":"NULL")." fixed_cost FROM pos_products p JOIN pos_inventory_balances b ON b.product_id=p.id JOIN pos_product_costs c ON c.product_id=p.id WHERE p.deleted_at IS NULL ORDER BY p.sku LIMIT 5000")->fetchAll();foreach($products as&$p){$p['models']=json_decode($p['models'],true)?:[];$p['active']=(bool)$p['active'];$p['on_hand']=(int)$p['on_hand'];$p['version']=(int)$p['version'];$p['price_version']=(int)$p['price_version'];$p['stock_version']=(int)$p['stock_version'];if($actor['role']!=='admin')unset($p['fixed_cost']);}$orders=$this->orders($pdo,$actor);$users=$actor['role']==='admin'?$pdo->query("SELECT id,display_name,role,active FROM pos_users WHERE deleted_at IS NULL ORDER BY FIELD(role,'admin','staff'),created_at")->fetchAll():[];foreach($users as&$u)$u['active']=(bool)$u['active'];$logs=$actor['role']==='admin'?$pdo->query('SELECT l.id,l.action,u.display_name actor_name,l.created_at,l.detail FROM pos_audit_logs l JOIN pos_users u ON u.id=l.actor_id ORDER BY l.created_at DESC LIMIT 200')->fetchAll():[];Response::json(['me'=>PosAuth::publicUser($actor),'glass_brands'=>$brands,'products'=>$products,'orders'=>$orders,'users'=>$users,'logs'=>$logs]);}catch(\Throwable$error){$this->fail($error);}
    }
    public function query():never
    {
        try{$actor=PosAuth::actor();$d=$this->json();if(($d['action']??'')!=='history')throw new \RuntimeException('VALIDATION_ERROR');$productId=(string)($d['product_id']??'');if(!preg_match('/^[a-f0-9-]{36}$/i',$productId))throw new \RuntimeException('VALIDATION_ERROR');$pdo=Database::appConnection();$exists=$pdo->prepare('SELECT 1 FROM pos_products WHERE id=? AND deleted_at IS NULL');$exists->execute([$productId]);if(!$exists->fetchColumn())throw new \RuntimeException('PRODUCT_NOT_FOUND');$s=$pdo->prepare('SELECT movement_type,quantity_delta,balance_after,occurred_at FROM pos_inventory_movements WHERE product_id=? ORDER BY occurred_at DESC,id DESC LIMIT 500');$s->execute([$productId]);Response::json($s->fetchAll());}catch(\Throwable$error){$this->fail($error);}
    }
    public function mutate():never{try{$actor=PosAuth::actor();$result=(new PosService(Database::appConnection()))->mutate($actor,$this->json());Response::json($result);}catch(\Throwable$error){$this->fail($error);}}
    public function orderFeatures():never
    {
        try {
            $actor = PosAuth::actor();
            $payload = $this->json();
            $action = (string)($payload['action'] ?? '');
            $orderId = (string)($payload['order_id'] ?? '');
            if (!preg_match('/^[a-f0-9-]{36}$/i', $orderId)) throw new \RuntimeException('VALIDATION_ERROR');
            $pdo = Database::appConnection();
            $pdo->beginTransaction();
            try {
                $statement = $pdo->prepare('SELECT * FROM pos_sales_orders WHERE id=? FOR UPDATE');
                $statement->execute([$orderId]);
                $order = $statement->fetch();
                if (!$order || ($actor['role'] !== 'admin' && $order['created_by'] !== $actor['id'])) throw new \RuntimeException('FORBIDDEN');

                if ($action === 'save') {
                    $items = $payload['items'] ?? null;
                    $address = trim((string)($payload['customer_address'] ?? ''));
                    $customerType = (string)($payload['customer_type'] ?? 'retail');
                    if ($order['status'] !== 'draft') throw new \RuntimeException('INVALID_ORDER_STATE');
                    if (!is_array($items) || count($items) < 1 || count($items) > 100 || mb_strlen($address) > 500 || !in_array($customerType, ['retail','wholesale'], true)) throw new \RuntimeException('VALIDATION_ERROR');
                    $seen = [];
                    foreach ($items as $item) {
                        $productId = (string)($item['product_id'] ?? '');
                        $kind = (string)($item['item_kind'] ?? 'sale');
                        $price = (string)($item['unit_sale_price'] ?? '');
                        $note = trim((string)($item['item_note'] ?? ''));
                        if (!preg_match('/^[a-f0-9-]{36}$/i', $productId) || isset($seen[$productId]) || !in_array($kind, ['sale','gift','sample'], true) || !preg_match('/^[0-9]{1,15}$/', $price) || mb_strlen($note) > 500) throw new \RuntimeException('VALIDATION_ERROR');
                        $seen[$productId] = true;
                        $line = $pdo->prepare('SELECT id FROM pos_sales_order_items WHERE order_id=? AND product_id=? FOR UPDATE');
                        $line->execute([$orderId, $productId]);
                        $lineId = $line->fetchColumn();
                        if (!$lineId) throw new \RuntimeException('VALIDATION_ERROR');
                        $effectivePrice = in_array($kind, ['gift','sample'], true) ? '0' : $price;
                        $update = $pdo->prepare('UPDATE pos_sales_order_items SET custom_unit_price=?,unit_sale_price=?,item_kind=?,item_note=? WHERE id=?');
                        $update->execute([$effectivePrice, $effectivePrice, $kind, $note, $lineId]);
                    }
                    $pdo->prepare('UPDATE pos_sales_orders SET customer_address=?,customer_type=?,total_amount=(SELECT COALESCE(SUM(quantity*unit_sale_price),0) FROM pos_sales_order_items WHERE order_id=?),version=version+1 WHERE id=?')->execute([$address, $customerType, $orderId, $orderId]);
                    $this->audit($pdo, $actor['id'], 'order.features.update', $orderId);
                    $pdo->commit();
                    Response::json(['id'=>$orderId]);
                }

                if ($action === 'payment') {
                    $method = (string)($payload['method'] ?? '');
                    $amount = (string)($payload['amount'] ?? '');
                    $note = trim((string)($payload['note'] ?? ''));
                    if (!in_array($order['status'], ['confirmed','partially_returned','returned'], true) || !in_array($method, ['cash','transfer','cod','cod_transfer','cash_transfer'], true) || !preg_match('/^[0-9]{1,15}$/', $amount) || mb_strlen($note) > 500) throw new \RuntimeException('VALIDATION_ERROR');
                    $paid = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM pos_sales_payments WHERE order_id=?');
                    $paid->execute([$orderId]);
                    $paidAmount = (int)$paid->fetchColumn();
                    if ((int)$amount <= 0 || (int)$amount + $paidAmount > (int)$order['total_amount']) throw new \RuntimeException('PAYMENT_EXCEEDS_DUE');
                    $paymentId = PosService::uuid();
                    $pdo->prepare('INSERT INTO pos_sales_payments(id,order_id,amount,method,note,created_by) VALUES(?,?,?,?,?,?)')->execute([$paymentId,$orderId,$amount,$method,$note,$actor['id']]);
                    $pdo->prepare('UPDATE pos_sales_orders SET version=version+1 WHERE id=?')->execute([$orderId]);
                    $this->audit($pdo, $actor['id'], 'order.payment', $orderId.' / '.$amount);
                    $pdo->commit();
                    Response::json(['id'=>$paymentId]);
                }

                throw new \RuntimeException('VALIDATION_ERROR');
            } catch (\Throwable $error) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                throw $error;
            }
        } catch (\Throwable $error) {
            $this->fail($error);
        }
    }
    public function deleteProduct():never{try{$actor=PosAuth::actor();$result=(new PosService(Database::appConnection()))->deleteProduct($actor,$this->json());Response::json($result);}catch(\Throwable$error){$this->fail($error);}}
    public function report():never
    {
        try{$actor=PosAuth::actor();if($actor['role']!=='admin')throw new \RuntimeException('FORBIDDEN');$d=$this->json();$from=(string)($d['from']??'');$to=(string)($d['to']??'');$brand=trim((string)($d['brand']??'all'));$start=\DateTimeImmutable::createFromFormat('!Y-m-d',$from);$end=\DateTimeImmutable::createFromFormat('!Y-m-d',$to);if(!$start||!$end||$end<$start||$start->diff($end)->days>366||mb_strlen($brand)>100)throw new \RuntimeException('VALIDATION_ERROR');$toExclusive=$end->modify('+1 day')->format('Y-m-d');$pdo=Database::appConnection();$filter=$brand==='all'?'':' AND p.brand=?';$args=[$from,$toExclusive,...($brand==='all'?[]:[$brand])];$s=$pdo->prepare("SELECT COALESCE(SUM(i.quantity*i.unit_sale_price),0) revenue,COALESCE(SUM(i.quantity*c.unit_cost_snapshot),0) cost,COALESCE(SUM(i.quantity),0) sold FROM pos_sales_orders o JOIN pos_sales_order_items i ON i.order_id=o.id JOIN pos_sales_item_costs c ON c.order_item_id=i.id JOIN pos_products p ON p.id=i.product_id WHERE o.confirmed_at>=? AND o.confirmed_at<?{$filter}");$s->execute($args);$sales=$s->fetch();$s=$pdo->prepare("SELECT COALESCE(SUM(i.quantity*i.unit_sale_price),0) refunds,COALESCE(SUM(i.quantity*c.unit_cost_snapshot),0) reversed_cost FROM pos_sales_orders o JOIN pos_sales_order_items i ON i.order_id=o.id JOIN pos_sales_item_costs c ON c.order_item_id=i.id JOIN pos_products p ON p.id=i.product_id WHERE o.cancelled_at>=? AND o.cancelled_at<?{$filter}");$s->execute($args);$cancel=$s->fetch();$s=$pdo->prepare("SELECT COALESCE(SUM(ri.refund_amount),0) refunds,COALESCE(SUM(ri.quantity*c.unit_cost_snapshot),0) reversed_cost,COALESCE(SUM((ri.quantity-ri.restock_quantity)*c.unit_cost_snapshot),0) damage FROM pos_sales_returns r JOIN pos_sales_return_items ri ON ri.return_id=r.id JOIN pos_sales_order_items i ON i.id=ri.order_item_id JOIN pos_sales_item_costs c ON c.order_item_id=i.id JOIN pos_products p ON p.id=i.product_id WHERE r.posted_at>=? AND r.posted_at<?{$filter}");$s->execute($args);$returns=$s->fetch();$s=$pdo->prepare("SELECT COALESCE(SUM(-i.quantity_delta*i.fixed_cost_snapshot),0) damage FROM pos_inventory_documents d JOIN pos_inventory_document_items i ON i.document_id=d.id JOIN pos_products p ON p.id=i.product_id WHERE d.type='damage' AND d.posted_at>=? AND d.posted_at<?{$filter}");$s->execute($args);$damage=$s->fetchColumn();$stockArgs=$brand==='all'?[]:[$brand];$stockSql=$brand==='all'?'':' WHERE p.brand=?';$s=$pdo->prepare("SELECT COALESCE(SUM(b.on_hand),0) stock,COALESCE(SUM(b.on_hand*c.fixed_cost),0) stock_value FROM pos_inventory_balances b JOIN pos_product_costs c ON c.product_id=b.product_id JOIN pos_products p ON p.id=b.product_id{$stockSql}");$s->execute($stockArgs);$stock=$s->fetch();$invArgs=[$from,$toExclusive,...($brand==='all'?[]:[$brand])];$s=$pdo->prepare("SELECT p.sku,p.name,COALESCE(SUM(CASE WHEN m.occurred_at<? THEN m.quantity_delta ELSE 0 END),0) opening,COALESCE(SUM(CASE WHEN m.occurred_at>=? AND m.occurred_at<? AND m.quantity_delta>0 THEN m.quantity_delta ELSE 0 END),0) incoming,COALESCE(-SUM(CASE WHEN m.occurred_at>=? AND m.occurred_at<? AND m.quantity_delta<0 THEN m.quantity_delta ELSE 0 END),0) outgoing,COALESCE(SUM(CASE WHEN m.occurred_at<? THEN m.quantity_delta ELSE 0 END),0) closing FROM pos_products p LEFT JOIN pos_inventory_movements m ON m.product_id=p.id".($brand==='all'?'':' WHERE p.brand=?')." GROUP BY p.id,p.sku,p.name ORDER BY p.sku LIMIT 5000");$invParams=[$from,$from,$toExclusive,$from,$toExclusive,$toExclusive,...($brand==='all'?[]:[$brand])];$s->execute($invParams);$inventory=$s->fetchAll();$revenue=(int)$sales['revenue']-(int)$cancel['refunds']-(int)$returns['refunds'];$cost=(int)$sales['cost']-(int)$cancel['reversed_cost']-(int)$returns['reversed_cost'];Response::json(['revenue'=>(string)$revenue,'refunds'=>(string)((int)$cancel['refunds']+(int)$returns['refunds']),'cost'=>(string)$cost,'profit'=>(string)($revenue-$cost),'damage'=>(string)((int)$returns['damage']+(int)$damage),'sold'=>(int)$sales['sold'],'stock'=>(int)$stock['stock'],'stock_value'=>(string)$stock['stock_value'],'inventory'=>$inventory]);}catch(\Throwable$error){$this->fail($error);}
    }
    public function staff():never
    {
        try{$actor=PosAuth::actor();if($actor['role']!=='admin')throw new \RuntimeException('FORBIDDEN');$d=$this->json();$action=(string)($d['action']??'');$pdo=Database::appConnection();if($action==='create'){$name=trim((string)($d['display_name']??''));$login=trim((string)($d['login_name']??''));$password=(string)($d['password']??'');if($name===''||mb_strlen($name)>100||mb_strlen($login)<3||mb_strlen($login)>50||strlen($password)<12||strlen($password)>128)throw new \RuntimeException('VALIDATION_ERROR');$id=PosService::uuid();$s=$pdo->prepare("INSERT INTO pos_users(id,login_name,password_hash,display_name,role) VALUES(?,?,?,?,'staff')");$s->execute([$id,$login,password_hash($password,PASSWORD_ARGON2ID),$name]);$this->audit($pdo,$actor['id'],'staff.create',$id);Response::json(['id'=>$id]);}if($action==='profile'){$name=trim((string)($d['display_name']??''));if($name===''||mb_strlen($name)>100)throw new \RuntimeException('VALIDATION_ERROR');$pdo->prepare("UPDATE pos_users SET display_name=? WHERE id=? AND role='admin'")->execute([$name,$actor['id']]);$this->audit($pdo,$actor['id'],'admin.profile',$name);Response::json(['id'=>$actor['id']]);}$id=(string)($d['id']??'');$target=$pdo->prepare('SELECT * FROM pos_users WHERE id=? AND deleted_at IS NULL');$target->execute([$id]);$user=$target->fetch();if(!$user)throw new \RuntimeException('ACCOUNT_NOT_FOUND');if($action==='reset'){$password=(string)($d['password']??'');if(strlen($password)<12||strlen($password)>128||($user['role']!=='staff'&&$id!==$actor['id']))throw new \RuntimeException('FORBIDDEN');$pdo->prepare('UPDATE pos_users SET password_hash=? WHERE id=?')->execute([password_hash($password,PASSWORD_ARGON2ID),$id]);$pdo->prepare('DELETE FROM pos_sessions WHERE user_id=?')->execute([$id]);$this->audit($pdo,$actor['id'],$id===$actor['id']?'admin.password.reset':'staff.password.reset',$id);Response::json(['id'=>$id,'self'=>$id===$actor['id']]);}if($action==='delete'){if($user['role']!=='staff'||$id===$actor['id'])throw new \RuntimeException('FORBIDDEN');$pdo->prepare('DELETE FROM pos_sessions WHERE user_id=?')->execute([$id]);$pdo->prepare('UPDATE pos_users SET active=0,deleted_at=NOW(),password_hash=? WHERE id=?')->execute([password_hash(bin2hex(random_bytes(32)),PASSWORD_ARGON2ID),$id]);$this->audit($pdo,$actor['id'],'staff.delete',$user['display_name']);Response::json(['id'=>$id]);}throw new \RuntimeException('VALIDATION_ERROR');}catch(\PDOException$error){if($error->getCode()==='23000')$this->fail(new \RuntimeException('ACCOUNT_EXISTS'));$this->fail($error);}catch(\Throwable$error){$this->fail($error);}
    }
    public function uploadImage():never{try{$actor=PosAuth::actor();if($actor['role']!=='admin')throw new \RuntimeException('FORBIDDEN');$file=$_FILES['image']??null;if(!$file||$file['error']!==UPLOAD_ERR_OK||$file['size']>5242880)throw new \RuntimeException('VALIDATION_ERROR');$mime=(new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);if(!in_array($mime,['image/jpeg','image/png','image/webp'],true)||!getimagesize($file['tmp_name']))throw new \RuntimeException('VALIDATION_ERROR');$extension=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime];$path=PosService::uuid().'.'.$extension;$data=file_get_contents($file['tmp_name']);$s=Database::appConnection()->prepare('INSERT INTO pos_product_images(path,mime_type,data,created_by) VALUES(?,?,?,?)');$s->bindValue(1,$path);$s->bindValue(2,$mime);$s->bindValue(3,$data,\PDO::PARAM_LOB);$s->bindValue(4,$actor['id']);$s->execute();Response::json(['path'=>$path]);}catch(\Throwable$error){$this->fail($error);}}
    public function image(array$params):never{try{PosAuth::actor(false);$s=Database::appConnection()->prepare('SELECT mime_type,data FROM pos_product_images WHERE path=?');$s->execute([$params['path']??'']);$image=$s->fetch();if(!$image){http_response_code(404);exit;}header('Content-Type: '.$image['mime_type']);header('Cache-Control: private,max-age=1800');echo$image['data'];exit;}catch(\Throwable$error){$this->fail($error);}}
    public function deleteImage(array$params):never{try{$actor=PosAuth::actor();if($actor['role']!=='admin')throw new \RuntimeException('FORBIDDEN');$path=$params['path']??'';$s=Database::appConnection()->prepare('SELECT 1 FROM pos_products WHERE image_path=? AND deleted_at IS NULL');$s->execute([$path]);if(!$s->fetchColumn())Database::appConnection()->prepare('DELETE FROM pos_product_images WHERE path=?')->execute([$path]);Response::json(['ok'=>true]);}catch(\Throwable$error){$this->fail($error);}}
    private function audit(\PDO$pdo,string$actor,string$action,string$detail):void{$pdo->prepare('INSERT INTO pos_audit_logs(id,actor_id,action,detail) VALUES(?,?,?,?)')->execute([PosService::uuid(),$actor,$action,$detail]);}
    private function clientIp():string{$ip=(string)($_SERVER['REMOTE_ADDR']??'127.0.0.1');$header=trim((string)(Env::get('TRUSTED_PROXY_IP_HEADER','')??''));if($header!==''){$serverKey='HTTP_'.strtoupper(str_replace('-','_',$header));$forwarded=trim(explode(',',(string)($_SERVER[$serverKey]??''))[0]);if(filter_var($forwarded,FILTER_VALIDATE_IP))$ip=$forwarded;}return substr($ip,0,45);}
    private function orders(\PDO$pdo,array$actor):array
    {
        $sql="SELECT o.*,CONCAT('DH-',LPAD(o.order_no,5,'0')) order_label,u.display_name creator_name FROM pos_sales_orders o JOIN pos_users u ON u.id=o.created_by WHERE o.status<>'deleted'".($actor['role']==='admin'?'':' AND o.created_by=?')." ORDER BY o.updated_at DESC LIMIT 500";
        $statement=$pdo->prepare($sql);$statement->execute($actor['role']==='admin'?[]:[$actor['id']]);$orders=$statement->fetchAll();
        foreach($orders as&$o){
            $o['order_no']=$o['order_label'];unset($o['order_label']);$o['version']=(int)$o['version'];
            $s=$pdo->prepare('SELECT i.*,COALESCE((SELECT SUM(ri.quantity) FROM pos_sales_return_items ri WHERE ri.order_item_id=i.id),0) returned_quantity FROM pos_sales_order_items i WHERE i.order_id=? ORDER BY i.id');
            $s->execute([$o['id']]);$o['items']=$s->fetchAll();
            foreach($o['items']as&$i){$i['quantity']=(int)$i['quantity'];$i['returned_quantity']=(int)$i['returned_quantity'];}
            $s=$pdo->prepare('SELECT p.id,CAST(p.amount AS CHAR) amount,p.method,p.note,p.paid_at,u.display_name actor_name FROM pos_sales_payments p JOIN pos_users u ON u.id=p.created_by WHERE p.order_id=? ORDER BY p.paid_at,p.id');
            $s->execute([$o['id']]);$o['payments']=$s->fetchAll();
            $paidAmount=0;foreach($o['payments']as$payment)$paidAmount+=(int)$payment['amount'];
            $o['paid_amount']=(string)$paidAmount;$o['outstanding_amount']=(string)max(0,(int)$o['total_amount']-$paidAmount);
            $o['payment_status']=$paidAmount===0?'unpaid':($paidAmount<(int)$o['total_amount']?'partial':'paid');
            $s=$pdo->prepare("SELECT r.*,CONCAT('TH-',LPAD(r.return_no,5,'0')) return_label FROM pos_sales_returns r WHERE r.order_id=? ORDER BY r.posted_at");
            $s->execute([$o['id']]);$o['returns']=$s->fetchAll();
            foreach($o['returns']as&$r){$r['return_no']=$r['return_label'];unset($r['return_label']);$s=$pdo->prepare('SELECT order_item_id,quantity,restock_quantity FROM pos_sales_return_items WHERE return_id=?');$s->execute([$r['id']]);$r['items']=$s->fetchAll();}
        }
        return$orders;
    }
}
