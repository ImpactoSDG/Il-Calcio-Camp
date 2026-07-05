<?php

declare(strict_types=1);

/**
 * Proveedor de la conexión PDO para los tests de integración.
 *
 * La conexión es lazy (se abre en el primer pdo()) para que la suite "unit"
 * pueda correr sin base de datos. Incluye una salvaguarda para no ejecutar los
 * tests contra una base que no sea de test.
 */
final class TestDatabase
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
        $nombre = $_ENV['DB_NAME'] ?? '';
        $usuario = $_ENV['DB_USER'] ?? 'root';
        $password = $_ENV['DB_PASS'] ?? '';

        // SALVAGUARDA: nunca correr contra una base que no sea de test.
        // Evita destruir datos reales si .env.testing quedó mal configurado.
        if (stripos($nombre, 'test') === false) {
            throw new RuntimeException(
                "SEGURIDAD: DB_NAME ('{$nombre}') debe contener 'test'. " .
                "Configurá api/.env.testing con una base de datos de test dedicada."
            );
        }

        $pdo = new PDO(
            "mysql:host={$host};dbname={$nombre};charset=utf8mb4",
            $usuario,
            $password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        // Forzar modo estricto para reproducir fielmente el bug de tipos (cuit_dni = '').
        // Sin esto, según la configuración del servidor, un string vacío podría
        // insertarse silenciosamente como 0 y el test no detectaría la regresión.
        $pdo->exec("SET SESSION sql_mode='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");

        self::$pdo = $pdo;
        return self::$pdo;
    }
}
