<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;

$pdo = Database::connection();
$pdo->exec('CREATE TABLE IF NOT EXISTS app_migrations (name VARCHAR(190) PRIMARY KEY, applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
$applied = $pdo->query('SELECT name FROM app_migrations')->fetchAll(PDO::FETCH_COLUMN);
foreach (glob(BASE_PATH . '/database/migrations/*.sql') ?: [] as $file) {
    $name = basename($file);
    if (in_array($name, $applied, true)) continue;
    try {
        // MySQL implicitly commits DDL, so execute idempotent statements one by one.
        $sql = file_get_contents($file);
        $statements = preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [];
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
