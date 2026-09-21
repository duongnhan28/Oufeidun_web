<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
use App\Core\Database;
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$username=mb_strtolower(trim($argv[1]??''));$password=$argv[2]??'';
if($username===''||strlen($password)<12){fwrite(STDERR,"Usage: php scripts/create-admin.php <username> <password-min-12-chars>\n");exit(1);}
$hash=password_hash($password,PASSWORD_ARGON2ID);$s=Database::connection()->prepare('INSERT INTO admin_users(username,password_hash,active) VALUES(?,?,1) ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash),active=1');$s->execute([$username,$hash]);echo "Admin account saved.\n";

