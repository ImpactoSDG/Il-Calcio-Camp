<?php
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/UsuarioController.php';

$auth = new AuthController();
$usuarioController = new UsuarioController();

$uri_parts = explode('/', trim($uri, '/'));
$resource = $uri_parts[0] ?? null;
$id = $uri_parts[1] ?? null;
$subresource = $uri_parts[2] ?? null;

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
    default:
        http_response_code(404);
        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode(["message" => "Ruta no encontrada."]);
        exit;
}
