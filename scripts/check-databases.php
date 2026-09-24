<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$web = Database::connection();
$app = Database::appConnection();
$webTables = $web->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$appTables = $app->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

$results = [
    'WEB_DB' => $web->query('SELECT DATABASE()')->fetchColumn(),
    'WEB_TABLES' => count($webTables),
    'WEB_PRODUCTS' => $web->query('SELECT COUNT(*) FROM glass_lookup_products')->fetchColumn(),
    'WEB_MODELS' => $web->query('SELECT COUNT(*) FROM glass_lookup_models')->fetchColumn(),
    'WEB_IMAGES' => $web->query('SELECT COUNT(*) FROM product_images')->fetchColumn(),
    'WEB_MARKETING_PRODUCTS' => $web->query('SELECT COUNT(*) FROM products')->fetchColumn(),
    'WEB_ADMINS' => $web->query('SELECT COUNT(*) FROM admin_users')->fetchColumn(),
    'WEB_CONTACTS' => $web->query('SELECT COUNT(*) FROM contact_messages')->fetchColumn(),
    'APP_DB' => $app->query('SELECT DATABASE()')->fetchColumn(),
    'APP_TABLES' => count($appTables),
    'APP_USERS' => $app->query('SELECT COUNT(*) FROM pos_users')->fetchColumn(),
    'APP_HAS_PAYMENTS' => in_array('pos_sales_payments', $appTables, true) ? 1 : 0,
    'APP_HAS_ORDER_ADDRESS' => (int) $app->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='pos_sales_orders' AND COLUMN_NAME='customer_address'")->fetchColumn(),
    'APP_HAS_ITEM_KIND' => (int) $app->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='pos_sales_order_items' AND COLUMN_NAME='item_kind'")->fetchColumn(),
    'WEB_HAS_POS' => in_array('pos_users', $webTables, true) ? 1 : 0,
    'APP_HAS_WEB' => in_array('glass_lookup_products', $appTables, true) ? 1 : 0,
];

foreach ($results as $key => $value) echo $key . '=' . $value . PHP_EOL;

if ($results['WEB_HAS_POS'] || $results['APP_HAS_WEB'] || !$results['APP_HAS_PAYMENTS'] || !$results['APP_HAS_ORDER_ADDRESS'] || !$results['APP_HAS_ITEM_KIND']) {
    fwrite(STDERR, "Database readiness check failed.\n");
    exit(1);
}
