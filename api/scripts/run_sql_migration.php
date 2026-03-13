<?php
if ($argc < 2) {
    fwrite(STDERR, "Uso: php api/scripts/run_sql_migration.php <ruta_sql>\n");
    exit(1);
}

$sqlPath = $argv[1];
if (!file_exists($sqlPath)) {
    fwrite(STDERR, "No existe el archivo SQL: {$sqlPath}\n");
    exit(1);
}

require __DIR__ . '/../core/Env.php';
Env::load(__DIR__ . '/../.env');

$dsn = 'mysql:host=' . ($_ENV['DB_HOST'] ?? '127.0.0.1') . ';dbname=' . ($_ENV['DB_NAME'] ?? '') . ';charset=utf8mb4';
$pdo = new PDO($dsn, $_ENV['DB_USER'] ?? '', $_ENV['DB_PASS'] ?? '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sql = file_get_contents($sqlPath);
$lines = preg_split('/\R/', $sql) ?: [];
$cleanLines = [];
foreach ($lines as $line) {
    if (preg_match('/^\s*--/', $line)) {
        continue;
    }
    $cleanLines[] = $line;
}

$sqlClean = implode(PHP_EOL, $cleanLines);
$statements = array_filter(array_map('trim', explode(';', $sqlClean)));

try {
    foreach ($statements as $stmt) {
        if ($stmt === '') {
            continue;
        }
        $pdo->exec($stmt);
    }
    echo "Migracion ejecutada correctamente: {$sqlPath}\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Error ejecutando migracion: " . $e->getMessage() . "\n");
    exit(1);
}
