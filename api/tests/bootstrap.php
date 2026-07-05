<?php

declare(strict_types=1);

/**
 * Bootstrap de PHPUnit.
 *
 * - Carga el autoloader de Composer (para PHPUnit).
 * - Carga las variables de entorno de test desde api/.env.testing (si existe).
 * - NO abre la conexión a la base todavía: la conexión es lazy (ver TestDatabase),
 *   así los tests puros (suite "unit") corren sin necesidad de una base de datos.
 */

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/TestDatabase.php';
require_once __DIR__ . '/DatabaseTestCase.php';

// Cargar .env.testing hacia $_ENV (sin pisar variables ya definidas en el entorno).
$rutaEnvTest = __DIR__ . '/../.env.testing';
if (file_exists($rutaEnvTest)) {
    $lineas = file($rutaEnvTest, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lineas as $linea) {
        if (str_starts_with(trim($linea), '#')) {
            continue;
        }
        if (!str_contains($linea, '=')) {
            continue;
        }
        [$clave, $valor] = array_map('trim', explode('=', $linea, 2));
        if ($clave !== '' && !array_key_exists($clave, $_ENV)) {
            $_ENV[$clave] = $valor;
        }
    }
}
