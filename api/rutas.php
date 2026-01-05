<?php
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/UsuarioController.php';
require_once __DIR__ . '/controllers/PermisoController.php'; // <--- 1. Agregar el nuevo controller

$auth = new AuthController();
$usuarioController = new UsuarioController();
$permisoController = new PermisoController(); // <--- 2. Instanciar

$uri_parts = explode('/', trim($uri, '/'));
$resource = $uri_parts[0] ?? null;

switch ($resource) {
    case 'login':
        if ($method == 'POST') {
            $auth->login();
        }
        break;

    case 'register':
        if ($method == 'POST') {
            $auth->register();
        }
        break;

    case 'usuarios':
        if ($method == 'GET') {
            $usuarioController->getAll();
        }
        break;

    // --- NUEVAS RUTAS PARA EL GESTIONADOR ---

    case 'gestion-permisos': // Ruta para cargar la matriz (GET)
        if ($method == 'GET') {
            $usuarioController->getGestionPermisos();
        }
        break;

    case 'toggle-permiso': // Ruta para el switch (POST o PUT)
        if ($method == 'POST') {
            $permisoController->toggle();
        }
        break;

    // ----------------------------------------

    default:
        http_response_code(404);
        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode(["message" => "Ruta no encontrada."]);
        exit;
}