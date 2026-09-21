<?php
declare(strict_types=1);
namespace App\Core;

final class PosAuth
{
    public static function login(string $username, string $password, string $deviceId, string $ip): array
    {
        if (!preg_match('/^[a-f0-9-]{36}$/i', $deviceId)) throw new \RuntimeException('VALIDATION_ERROR');
        $pdo = Database::appConnection();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('INSERT IGNORE INTO pos_login_limits(ip) VALUES(?)')->execute([$ip]);
            $s = $pdo->prepare('SELECT * FROM pos_login_limits WHERE ip=? FOR UPDATE');
            $s->execute([$ip]);
            $limit = $s->fetch();
            if ($limit['blocked_until'] && strtotime($limit['blocked_until']) > time()) throw new \RuntimeException('RATE_LIMIT');
            $s = $pdo->prepare('SELECT * FROM pos_users WHERE LOWER(login_name)=LOWER(?) AND active=1 AND deleted_at IS NULL LIMIT 1 FOR UPDATE');
            $s->execute([trim($username)]);
            $user = $s->fetch();
            if (!$user || !password_verify($password, $user['password_hash'])) {
                $count = (int)$limit['failure_count'] + 1;
                $first = $limit['first_failure_at'] ? strtotime($limit['first_failure_at']) : 0;
                if (!$first || $first < time() - 300) { $count = 1; $first = time(); }
                $blocked = $count >= 5 ? date('Y-m-d H:i:s', time() + 300) : null;
                $pdo->prepare('UPDATE pos_login_limits SET failure_count=?,first_failure_at=?,blocked_until=? WHERE ip=?')->execute([$count, date('Y-m-d H:i:s', $first), $blocked, $ip]);
                $pdo->commit();
                throw new \RuntimeException($blocked ? 'RATE_LIMIT' : 'INVALID_CREDENTIALS');
            }
            $s = $pdo->prepare('SELECT device_id,last_seen_at FROM pos_sessions WHERE user_id=? FOR UPDATE');
            $s->execute([$user['id']]);
            $active = $s->fetch();
            if ($active && $active['device_id'] !== $deviceId && strtotime($active['last_seen_at']) > time() - 90) {
                $pdo->rollBack();
                throw new \RuntimeException('SESSION_ALREADY_ACTIVE');
            }
            $token = bin2hex(random_bytes(32));
            $pdo->prepare('DELETE FROM pos_sessions WHERE user_id=?')->execute([$user['id']]);
            $pdo->prepare('INSERT INTO pos_sessions(token_hash,user_id,device_id,last_seen_at,expires_at) VALUES(?,?,?,NOW(),DATE_ADD(NOW(),INTERVAL 8 HOUR))')->execute([hash('sha256', $token), $user['id'], $deviceId]);
            $pdo->prepare('UPDATE pos_login_limits SET failure_count=0,first_failure_at=NULL,blocked_until=NULL WHERE ip=?')->execute([$ip]);
            $pdo->commit();
            return ['access_token'=>$token, 'expires_in'=>28800, 'user'=>self::publicUser($user)];
        } catch (\Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $error;
        }
    }

    public static function actor(bool $touch = true): array
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = str_starts_with($header, 'Bearer ') ? substr($header, 7) : '';
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) throw new \RuntimeException('UNAUTHENTICATED');
        $pdo = Database::appConnection();
        $s = $pdo->prepare('SELECT u.*,s.token_hash,s.last_seen_at,s.expires_at FROM pos_sessions s JOIN pos_users u ON u.id=s.user_id WHERE s.token_hash=? AND s.expires_at>NOW() AND u.active=1 AND u.deleted_at IS NULL LIMIT 1');
        $s->execute([hash('sha256', $token)]);
        $user = $s->fetch();
        if (!$user) throw new \RuntimeException('SESSION_REQUIRED');
        if ($touch) $pdo->prepare('UPDATE pos_sessions SET last_seen_at=NOW() WHERE token_hash=?')->execute([$user['token_hash']]);
        return $user;
    }

    public static function logout(): void
    {
        try {
            $user = self::actor(false);
            Database::appConnection()->prepare('DELETE FROM pos_sessions WHERE token_hash=?')->execute([$user['token_hash']]);
        } catch (\Throwable) {}
    }

    public static function publicUser(array $user): array
    {
        return ['id'=>$user['id'], 'display_name'=>$user['display_name'], 'role'=>$user['role'], 'active'=>(bool)$user['active']];
    }
}
