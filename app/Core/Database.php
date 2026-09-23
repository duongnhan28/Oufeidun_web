<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

final class Database
{
    private static ?PDO $connection = null;
    private static ?PDO $appConnection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        self::$connection = self::connect('DB_WEB_DATABASE', 'DB_WEB_USERNAME', 'DB_WEB_PASSWORD');
        return self::$connection;
    }

    public static function appConnection(): PDO
    {
        if (self::$appConnection instanceof PDO) {
            return self::$appConnection;
        }

        self::$appConnection = self::connect('DB_APP_DATABASE', 'DB_APP_USERNAME', 'DB_APP_PASSWORD');
        return self::$appConnection;
    }

    private static function connect(string $databaseKey, string $usernameKey, string $passwordKey): PDO
    {

        $host = Env::get('DB_HOST');
        $database = Env::get($databaseKey, Env::get('DB_DATABASE'));
        $username = Env::get($usernameKey) ?: Env::get('DB_USERNAME');
        if (!$host || !$database || !$username) {
            throw new RuntimeException('Database chưa được cấu hình. Hãy cập nhật file .env.');
        }

        $port = Env::get('DB_PORT', '3306');
        $charset = Env::get('DB_CHARSET', 'utf8mb4');
        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";
        $password = Env::get($passwordKey);
        if ($password === null || $password === '') $password = Env::get('DB_PASSWORD', '');
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ]);
        $pdo->exec("SET time_zone = '+07:00'");
        return $pdo;
    }

    public static function available(): bool
    {
        try {
            self::connection()->query('SELECT 1');
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
