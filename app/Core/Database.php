<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $host = Env::get('DB_HOST');
        $database = Env::get('DB_DATABASE');
        $username = Env::get('DB_USERNAME');
        if (!$host || !$database || !$username) {
            throw new RuntimeException('Database chưa được cấu hình. Hãy cập nhật file .env.');
        }

        $port = Env::get('DB_PORT', '3306');
        $charset = Env::get('DB_CHARSET', 'utf8mb4');
        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";
        self::$connection = new PDO($dsn, $username, Env::get('DB_PASSWORD', ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ]);
        self::$connection->exec("SET time_zone = '+07:00'");
        return self::$connection;
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
