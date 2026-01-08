<?php
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/UsuarioController.php';
require_once __DIR__ . '/controllers/PermisoController.php';
require_once __DIR__ . '/controllers/ConfiguracionController.php';

$auth = new AuthController();
$usuarioController = new UsuarioController();
$permisoController = new PermisoController();
$configController = new ConfiguracionController();

$parts = explode('/', trim($uri, '/'));
$resource = $parts[0] ?? null;
$id = $parts[1] ?? null;

switch ($resource) {
    case 'login':
        if ($method === 'POST') {
            $auth->login();
        } else {
            http_response_code(405);
        }
        break;

    case 'register':
        if ($method === 'POST') {
            $auth->register();
        } else {
            http_response_code(405);
        }
        break;

    case 'usuarios':
        switch ($method) {
            case 'GET':
                if ($id) {
                    $_GET['id'] = $id;
                    $usuarioController->getById();
                } else {
                    $usuarioController->getAll();
                }
                break;
            case 'POST':
                if (isset($_GET['action']) && $_GET['action'] === 'password') {
                    $usuarioController->updatePassword();
                } else {
                    $usuarioController->store();
                }
                break;
            case 'PUT':
                $usuarioController->update();
                break;
            case 'DELETE':
                $usuarioController->delete();
                break;
            default:
                http_response_code(405);
                break;
        }
        break;

    case 'gestion-permisos':
        if ($method === 'GET') {
            $usuarioController->getGestionPermisos();
        } else {
            http_response_code(405);
        }
        break;

    case 'toggle-permiso':
        if ($method === 'POST') {
            $permisoController->toggle();
        } else {
            http_response_code(405);
        }
        break;
    case 'configuraciones':
        switch ($method) {
            case 'GET':
                if ($id) {
                    $_GET['id'] = $id;
                    $configController->getById();
                } else {
                    $configController->getAll();
                }
                break;
            case 'POST':
                $configController->store();
                break;
            case 'PUT':
                $configController->update();
                break;
            case 'DELETE':
                $configController->delete();
                break;
            default:
                http_response_code(405);
                break;
        }
        break;

    default:
        http_response_code(404);
        echo json_encode([
            "message" => "Ruta no encontrada.",
            "detalle" => "El recurso '$resource' no existe en este servidor."
        ]);
        break;
}