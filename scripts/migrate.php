<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Core\Env;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$webDatabase = Env::get('DB_WEB_DATABASE', Env::get('DB_DATABASE'));
$appDatabase = Env::get('DB_APP_DATABASE', Env::get('DB_DATABASE'));
if (!$webDatabase || !$appDatabase) {
    fwrite(STDERR, "DB_WEB_DATABASE và DB_APP_DATABASE chưa được cấu hình.\n");
    exit(1);
}
foreach ([$webDatabase, $appDatabase] as $database) {
    if (!preg_match('/^[A-Za-z0-9_]+$/', $database)) {
        fwrite(STDERR, "Tên database không hợp lệ: {$database}\n");
        exit(1);
    }
}

$host = Env::get('DB_HOST', '127.0.0.1');
$port = Env::get('DB_PORT', '3306');
$charset = Env::get('DB_CHARSET', 'utf8mb4');
$collation = Env::get('DB_COLLATION', 'utf8mb4_unicode_ci');
if (!preg_match('/^[A-Za-z0-9_]+$/', $collation)) {
    fwrite(STDERR, "Collation không hợp lệ.\n");
    exit(1);
}
$server = new PDO(
    "mysql:host={$host};port={$port};charset={$charset}",
    Env::get('DB_USERNAME', ''),
    Env::get('DB_PASSWORD', ''),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$server->exec("CREATE DATABASE IF NOT EXISTS `{$webDatabase}` CHARACTER SET utf8mb4 COLLATE {$collation}");
$server->exec("CREATE DATABASE IF NOT EXISTS `{$appDatabase}` CHARACTER SET utf8mb4 COLLATE {$collation}");

applyMigration(Database::connection(), BASE_PATH . '/database/migrations/001_website_schema.sql');
applyMigration(Database::connection(), BASE_PATH . '/database/migrations/003_web_seed_products.sql');
applyMigration(Database::appConnection(), BASE_PATH . '/database/migrations/002_pos_schema.sql');

function applyMigration(PDO $pdo, string $file): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS app_migrations (name VARCHAR(190) PRIMARY KEY, applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $name = basename($file);
    $check = $pdo->prepare('SELECT 1 FROM app_migrations WHERE name=?');
    $check->execute([$name]);
    if ($check->fetchColumn()) {
        echo "Skipped {$name}\n";
        return;
    }

    try {
        $sql = file_get_contents($file);
        $statements = preg_split('/;\s*(?:\r?\n|$)/', $sql ?: '') ?: [];
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if ($statement !== '') $pdo->exec($statement);
        }
        $statement = $pdo->prepare('INSERT INTO app_migrations(name) VALUES(?)');
        $statement->execute([$name]);
        echo "Applied {$name}\n";
    } catch (Throwable $error) {
        fwrite(STDERR, "Failed {$name}: {$error->getMessage()}\n");
        exit(1);
    }
}
