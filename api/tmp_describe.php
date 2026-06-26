<?php
require_once __DIR__ . '/core/Env.php';
Env::load(__DIR__ . '/../.env');
require_once __DIR__ . '/core/Database.php';
$db = (new Database())->connect();

foreach (['equipo_torneo', 'equipo'] as $table) {
    echo "<h3>$table</h3><pre>";
    $stmt = $db->query("DESCRIBE $table");
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $r['Field'] . ' | ' . $r['Type'] . ' | NULL=' . $r['Null'] . ' | Default=' . var_export($r['Default'], true) . "\n";
    }
    echo "</pre>";
}
