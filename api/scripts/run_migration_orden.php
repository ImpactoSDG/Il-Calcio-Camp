<?php
require_once __DIR__ . '/../core/Env.php';
Env::load(__DIR__ . '/../.env');

try {
    $pdo = new PDO(
        'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_NAME'] . ';charset=utf8mb4',
        $_ENV['DB_USER'],
        $_ENV['DB_PASS']
    );
    // Verificar si la columna ya existe antes de agregarla
    $stmt = $pdo->query("SHOW COLUMNS FROM usuario_modulo LIKE 'orden_usuario'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec('ALTER TABLE usuario_modulo ADD COLUMN orden_usuario INT NULL DEFAULT NULL');
        echo "Columna orden_usuario agregada correctamente" . PHP_EOL;
    } else {
        echo "La columna orden_usuario ya existe" . PHP_EOL;
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
