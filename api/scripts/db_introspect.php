<?php
require __DIR__ . '/../core/Env.php';
Env::load(__DIR__ . '/../.env');

$dsn = 'mysql:host=' . ($_ENV['DB_HOST'] ?? '127.0.0.1') . ';dbname=' . ($_ENV['DB_NAME'] ?? '') . ';charset=utf8mb4';
$pdo = new PDO($dsn, $_ENV['DB_USER'] ?? '', $_ENV['DB_PASS'] ?? '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo 'DB=' . $pdo->query('SELECT DATABASE()')->fetchColumn() . PHP_EOL;

$tables = [
    'torneo', 'fase_torneo', 'grupo_torneo', 'grupo_torneo_equipo',
    'cruce_torneo', 'evento', 'evento_partido', 'estado_evento_hist',
    'equipo_torneo', 'estado_torneo_hist', 'generacion_fixture'
];

$in = implode(',', array_fill(0, count($tables), '?'));
$sqlCols = "SELECT TABLE_NAME, COLUMN_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME IN ($in)
            ORDER BY TABLE_NAME, ORDINAL_POSITION";
$stmt = $pdo->prepare($sqlCols);
$stmt->execute($tables);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    echo $r['TABLE_NAME'] . '.' . $r['COLUMN_NAME'] . PHP_EOL;
}

echo "-- FKs --" . PHP_EOL;
$sqlFks = "SELECT TABLE_NAME, CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
           FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
           WHERE TABLE_SCHEMA = DATABASE()
             AND REFERENCED_TABLE_NAME IS NOT NULL
             AND TABLE_NAME IN ($in)
           ORDER BY TABLE_NAME, CONSTRAINT_NAME";
$stmt2 = $pdo->prepare($sqlFks);
$stmt2->execute($tables);
foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) as $r) {
    echo $r['TABLE_NAME'] . '.' . $r['COLUMN_NAME'] . ' -> ' . $r['REFERENCED_TABLE_NAME'] . '.' . $r['REFERENCED_COLUMN_NAME'] . ' [' . $r['CONSTRAINT_NAME'] . ']' . PHP_EOL;
}

echo "-- DELETE RULES --" . PHP_EOL;
$sqlRules = "SELECT CONSTRAINT_NAME, TABLE_NAME, REFERENCED_TABLE_NAME, DELETE_RULE
             FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
               AND TABLE_NAME IN ($in)
             ORDER BY TABLE_NAME, CONSTRAINT_NAME";
$stmt3 = $pdo->prepare($sqlRules);
$stmt3->execute($tables);
foreach ($stmt3->fetchAll(PDO::FETCH_ASSOC) as $r) {
    echo $r['TABLE_NAME'] . '.' . $r['CONSTRAINT_NAME'] . ' -> ' . $r['REFERENCED_TABLE_NAME'] . ' DELETE ' . $r['DELETE_RULE'] . PHP_EOL;
}
