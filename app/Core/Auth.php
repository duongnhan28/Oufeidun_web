<?php
declare(strict_types=1);
namespace App\Core;
final class Auth
{
    private const COOKIE='oufeidun_admin';
    public static function user():?array{ $token=$_COOKIE[self::COOKIE]??'';if(!preg_match('/^[a-f0-9]{64}$/',$token))return null;try{$s=Database::connection()->prepare('SELECT u.id,u.username FROM admin_sessions s JOIN admin_users u ON u.id=s.user_id WHERE s.token_hash=? AND s.expires_at>NOW() AND u.active=1 LIMIT 1');$s->execute([hash('sha256',$token)]);return $s->fetch()?:null;}catch(\Throwable){return null;} }
    public static function requireUser():array{$user=self::user();if(!$user)Response::redirect('/login');return $user;}
    public static function attempt(string $username,string $password,string $ip):array
    {
        $pdo=Database::connection();$pdo->beginTransaction();
        try{$pdo->prepare('INSERT IGNORE INTO login_limits(ip) VALUES(?)')->execute([$ip]);$s=$pdo->prepare('SELECT * FROM login_limits WHERE ip=? FOR UPDATE');$s->execute([$ip]);$limit=$s->fetch();if($limit['blocked_until']&&strtotime($limit['blocked_until'])>time()){$pdo->commit();return['retryAfter'=>strtotime($limit['blocked_until'])-time()];}$s=$pdo->prepare('SELECT id,password_hash FROM admin_users WHERE username=? AND active=1 LIMIT 1');$s->execute([mb_strtolower(trim($username))]);$user=$s->fetch();if(!$user||!password_verify($password,$user['password_hash'])){$count=(int)$limit['failure_count']+1;$first=$limit['first_failure_at']?strtotime($limit['first_failure_at']):0;if(!$first||$first<time()-300){$count=1;$first=time();}$blocked=$count>=5?date('Y-m-d H:i:s',time()+300):null;$pdo->prepare('UPDATE login_limits SET failure_count=?,first_failure_at=?,blocked_until=? WHERE ip=?')->execute([$count,date('Y-m-d H:i:s',$first),$blocked,$ip]);$pdo->commit();return $blocked?['retryAfter'=>300]:['invalid'=>true];}$token=bin2hex(random_bytes(32));$pdo->prepare('INSERT INTO admin_sessions(token_hash,user_id,expires_at) VALUES(?,?,DATE_ADD(NOW(),INTERVAL 8 HOUR))')->execute([hash('sha256',$token),$user['id']]);$pdo->prepare('UPDATE login_limits SET failure_count=0,first_failure_at=NULL,blocked_until=NULL WHERE ip=?')->execute([$ip]);$pdo->exec('DELETE FROM admin_sessions WHERE expires_at<NOW()');$pdo->commit();setcookie(self::COOKIE,$token,['expires'=>time()+28800,'path'=>'/','secure'=>Env::get('APP_ENV','production')==='production','httponly'=>true,'samesite'=>'Lax']);return['ok'=>true];}catch(\Throwable $error){if($pdo->inTransaction())$pdo->rollBack();throw$error;}
    }
    public static function logout():void{$token=$_COOKIE[self::COOKIE]??'';if(preg_match('/^[a-f0-9]{64}$/',$token)){try{$s=Database::connection()->prepare('DELETE FROM admin_sessions WHERE token_hash=?');$s->execute([hash('sha256',$token)]);}catch(\Throwable){}}setcookie(self::COOKIE,'',['expires'=>time()-3600,'path'=>'/','secure'=>Env::get('APP_ENV','production')==='production','httponly'=>true,'samesite'=>'Lax']);}
}

