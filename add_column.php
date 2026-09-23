<?php
$envPath = __DIR__ . '/.env';
$env = parse_ini_file($envPath);

$host = $env['DB_HOST'] ?? 'localhost';
$database = $env['DB_DATABASE'];
$username = $env['DB_USERNAME'];
$password = $env['DB_PASSWORD'];
$port = $env['DB_PORT'] ?? 3306;

try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$database}", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SHOW COLUMNS FROM wallets LIKE 'balance_type'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec('ALTER TABLE wallets ADD COLUMN balance_type ENUM("full", "installment") DEFAULT "full" AFTER balance');
        echo "Column added!\n";
    } else {
        echo "Column exists!\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
