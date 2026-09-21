<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
use App\Core\Database;use App\Services\PosService;
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$login=trim($argv[1]??'admin');$display=trim($argv[2]??'Chủ cửa hàng');$password=$argv[3]??'';
if(strlen($login)<3||$display===''||strlen($password)<12){fwrite(STDERR,"Usage: php scripts/create-pos-admin.php <login> <display-name> <password-min-12>\n");exit(1);}
$pdo=Database::appConnection();$s=$pdo->prepare('SELECT id FROM pos_users WHERE LOWER(login_name)=LOWER(?) AND deleted_at IS NULL');$s->execute([$login]);$id=$s->fetchColumn()?:PosService::uuid();$pdo->prepare("INSERT INTO pos_users(id,login_name,password_hash,display_name,role,active) VALUES(?,?,?,?, 'admin',1) ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash),display_name=VALUES(display_name),active=1,deleted_at=NULL")->execute([$id,$login,password_hash($password,PASSWORD_ARGON2ID),$display]);echo "POS admin account saved.\n";
