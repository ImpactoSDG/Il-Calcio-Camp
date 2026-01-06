<?php
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/UsuarioController.php';
require_once __DIR__ . '/controllers/PermisoController.php';

$auth = new AuthController();
$usuarioController = new UsuarioController();
$permisoController = new PermisoController();

$method = $_SERVER['REQUEST_METHOD'];

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$uri = str_replace('/proyecto-base/api', '', $uri);

$uri_parts = explode('/', trim($uri, '/'));
$resource = $uri_parts[0] ?? null;

header("Content-Type: application/json; charset=UTF-8");

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
        switch ($method) {
            case 'GET':
                if (isset($_GET['id'])) {
                    $usuarioController->getById();
                } else {
                    $usuarioController->getAll();
                }
                break;
            case 'PUT':
                $usuarioController->update();
                break;
            case 'DELETE':
                $usuarioController->delete();
                break;
            case 'POST':
                if (isset($_GET['action']) && $_GET['action'] == 'password') {
                    $usuarioController->updatePassword();
                } else {
                    $usuarioController->store();
                }
                break;
        }
        break;

    case 'gestion-permisos':
        if ($method == 'GET') {
            $usuarioController->getGestionPermisos();
        }
        break;

    case 'toggle-permiso':
        if ($method == 'POST') {
            $permisoController->toggle();
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(["message" => "Ruta no encontrada."]);
        break;
}