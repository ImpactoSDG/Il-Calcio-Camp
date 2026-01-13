<?php
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/UsuarioController.php';
require_once __DIR__ . '/controllers/PermisoController.php';
require_once __DIR__ . '/controllers/ConfiguracionController.php';
require_once __DIR__ . '/controllers/ModuloController.php';

$auth = new AuthController();
$usuarioController = new UsuarioController();
$permisoController = new PermisoController();
$configController = new ConfiguracionController();
$moduloController = new ModuloController();

$parts = explode('/', trim($uri, '/'));
$resource = $parts[0] ?? null;
$id = $parts[1] ?? null;

switch ($resource) {
    case 'version': // obtener la última fecha de modificación del proyecto
        if ($method === 'GET') {
            date_default_timezone_set('America/Argentina/Cordoba');
            clearstatcache();

            $rootPath = realpath(__DIR__ . '/../');
            $latest_mtime = 0;

            $directory = new RecursiveDirectoryIterator($rootPath);
            $iterator = new RecursiveIteratorIterator($directory);

            foreach ($iterator as $fileinfo) {
                if (str_contains($fileinfo->getPathname(), 'node_modules')) {
                    continue;
                }

                if ($fileinfo->isFile()) {
                    $mtime = $fileinfo->getMTime();
                    if ($mtime > $latest_mtime) {
                        $latest_mtime = $mtime;
                    }
                }
            }

            header('Content-Type: application/json');
            echo json_encode([
                "timestamp" => date("d/m/Y, H:i:s", $latest_mtime)
            ]);
        } else {
            http_response_code(405);
        }
        break;
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
    case 'roles':
        if ($method === 'GET') {
            $auth->getRoles();
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
    case 'refresh-modulos':
        if ($method === 'GET') {
            $usuarioController->refreshModulos();
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
    case 'modulos':
        if ($method === 'PUT') {
            $moduloController->update();
        } else {
            http_response_code(405);
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