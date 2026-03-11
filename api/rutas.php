<?php
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/UsuarioController.php';
require_once __DIR__ . '/controllers/PermisoController.php';
require_once __DIR__ . '/controllers/ConfiguracionController.php';
require_once __DIR__ . '/controllers/ModuloController.php';

// Nuevos controladores
require_once __DIR__ . '/controllers/CategoriaArticuloController.php';
require_once __DIR__ . '/controllers/ArticuloController.php';
require_once __DIR__ . '/controllers/IngresoArticuloController.php';
require_once __DIR__ . '/controllers/EstadoVentaController.php';
require_once __DIR__ . '/controllers/CondicionIvaReceptorController.php';
require_once __DIR__ . '/controllers/ProvinciaController.php';
require_once __DIR__ . '/controllers/ClienteController.php';
require_once __DIR__ . '/controllers/VentaController.php';
require_once __DIR__ . '/controllers/ArticuloVentaController.php';
require_once __DIR__ . '/controllers/MedioCobroController.php';
require_once __DIR__ . '/controllers/CobroController.php';
require_once __DIR__ . '/controllers/VentaCobroController.php';
require_once __DIR__ . '/controllers/EquipoController.php';
require_once __DIR__ . '/controllers/ClienteEquipoController.php';
require_once __DIR__ . '/controllers/ArticuloVentaIngresoArticuloController.php';
require_once __DIR__ . '/controllers/TicketVentaController.php';
require_once __DIR__ . '/controllers/ImpresoraTiqueteraController.php';

require_once __DIR__ . '/core/JwtHandler.php';
require_once __DIR__ . '/core/BaseController.php';
require_once __DIR__ . '/core/Database.php';

// Conexión única compartida
$database = new Database();
$db = $database->connect();

$auth = new AuthController($db);
$usuarioController = new UsuarioController($db);
$permisoController = new PermisoController($db);
$configController = new ConfiguracionController($db);
$moduloController = new ModuloController($db);

// Nuevos controladores
$categoriaArticuloController = new CategoriaArticuloController($db);
$articuloController = new ArticuloController($db);
$ingresoArticuloController = new IngresoArticuloController($db);
$estadoVentaController = new EstadoVentaController($db);
$condicionIvaReceptorController = new CondicionIvaReceptorController($db);
$provinciaController = new ProvinciaController($db);
$clienteController = new ClienteController($db);
$ventaController = new VentaController($db);
$articuloVentaController = new ArticuloVentaController($db);
$medioCobroController = new MedioCobroController($db);
$cobroController = new CobroController($db);
$ventaCobroController = new VentaCobroController($db);
$equipoController = new EquipoController($db);
$clienteEquipoController = new ClienteEquipoController($db);
$articuloVentaIngresoArticuloController = new ArticuloVentaIngresoArticuloController($db);
$ticketVentaController = new TicketVentaController($db);
$impresoraTiqueteraController = new ImpresoraTiqueteraController($db);

function verifyAuth(): array {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';

    if (!str_starts_with($authHeader, 'Bearer ')) {
        http_response_code(401);
        echo json_encode(['message' => 'No autorizado: Cabecera Authorization no encontrada.']);
        exit;
    }

    $token = substr($authHeader, 7);
    $payload = JwtHandler::decode($token);

    if (!$payload) {
        http_response_code(401);
        echo json_encode(['message' => 'No autorizado: Token inválido o expirado.']);
        exit;
    }

    return $payload;
}

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
        verifyAuth();
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
        verifyAuth();
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
        verifyAuth();
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

    // ============ NUEVAS RUTAS - ENTIDADES DE CATÁLOGO ============
    
    case 'categorias-articulo':
        verifyAuth();
        switch ($method) {
            case 'GET':
                if ($id) {
                    $_GET['id'] = $id;
                    $categoriaArticuloController->getById();
                } else {
                    $categoriaArticuloController->getAll();
                }
                break;
            case 'POST':
                $categoriaArticuloController->store();
                break;
            case 'PUT':
                $categoriaArticuloController->update();
                break;
            case 'DELETE':
                $categoriaArticuloController->delete();
                break;
            default:
                http_response_code(405);
                break;
        }
        break;

    case 'estados-venta':
        verifyAuth();
        if ($method === 'GET') {
            if ($id) { $_GET['id'] = $id; $estadoVentaController->getById(); }
            else { $estadoVentaController->getAll(); }
        } else {
            http_response_code(405);
        }
        break;

    case 'condiciones-iva':
        verifyAuth();
        if ($method === 'GET') {
            if ($id) { $_GET['id'] = $id; $condicionIvaReceptorController->getById(); }
            else { $condicionIvaReceptorController->getAll(); }
        } else {
            http_response_code(405);
        }
        break;

    case 'provincias':
        verifyAuth();
        if ($method === 'GET') {
            if ($id) { $_GET['id'] = $id; $provinciaController->getById(); }
            else { $provinciaController->getAll(); }
        } else {
            http_response_code(405);
        }
        break;

    case 'medios-cobro':
        verifyAuth();
        if ($method === 'GET') {
            if ($id) { $_GET['id'] = $id; $medioCobroController->getById(); }
            else { $medioCobroController->getAll(); }
        } else {
            http_response_code(405);
        }
        break;

    case 'equipos':
        verifyAuth();
        switch ($method) {
            case 'GET':
                if ($id) {
                    $_GET['id'] = $id;
                    $equipoController->getById();
                } else {
                    $equipoController->getAll();
                }
                break;
            case 'POST':
                $equipoController->store();
                break;
            case 'PUT':
                $equipoController->update();
                break;
            case 'DELETE':
                $equipoController->delete();
                break;
            default:
                http_response_code(405);
                break;
        }
        break;

    // ============ ENTIDADES DE NEGOCIO ============

    case 'articulos':
        verifyAuth();
        switch ($method) {
            case 'GET':
                if ($id) {
                    $_GET['id'] = $id;
                    $articuloController->getById();
                } else {
                    $articuloController->getAll();
                }
                break;
            case 'POST':
                if ($id === 'upload-image') {
                    $articuloController->uploadImage();
                } else {
                    $articuloController->store();
                }
                break;
            case 'PUT':
                $articuloController->update();
                break;
            case 'PATCH':
                $articuloController->bulkUpdate();
                break;
            case 'DELETE':
                $articuloController->delete();
                break;
            default:
                http_response_code(405);
                break;
        }
        break;

    case 'clientes':
        verifyAuth();
        switch ($method) {
            case 'GET':
                if ($id) {
                    if (isset($_GET['action'])) {
                        $_GET['id'] = $id;
                        if ($_GET['action'] === 'equipos') {
                            $clienteController->getEquipos();
                        } elseif ($_GET['action'] === 'movimientos') {
                            $clienteController->getMovimientos();
                        }
                    } else {
                        $_GET['id'] = $id;
                        $clienteController->getById();
                    }
                } else {
                    $clienteController->getAll();
                }
                break;
            case 'POST':
                $clienteController->store();
                break;
            case 'PUT':
                $clienteController->update();
                break;
            case 'DELETE':
                $clienteController->delete();
                break;
            default:
                http_response_code(405);
                break;
        }
        break;

    case 'ingresos-articulo':
        verifyAuth();
        switch ($method) {
            case 'GET':
                if ($id) {
                    $_GET['id'] = $id;
                    $ingresoArticuloController->getById();
                } else {
                    $ingresoArticuloController->getAll();
                }
                break;
            case 'POST':
                $ingresoArticuloController->store();
                break;
            case 'PUT':
                $ingresoArticuloController->update();
                break;
            case 'DELETE':
                $ingresoArticuloController->delete();
                break;
            default:
                http_response_code(405);
                break;
        }
        break;

    case 'ventas':
        verifyAuth();
        switch ($method) {
            case 'GET':
                if ($id) {
                    $_GET['id'] = $id; // Asegurar que el ID de la URL sea capturado en $_GET
                    if (isset($_GET['action']) && $_GET['action'] === 'articulos') {
                        $ventaController->getArticulos();
                    } else {
                        $ventaController->getById();
                    }
                } else {
                    $ventaController->getAll();
                }
                break;
            case 'POST':
                $ventaController->store();
                break;
            case 'PUT':
                $ventaController->update();
                break;
            case 'DELETE':
                $ventaController->delete();
                break;
            default:
                http_response_code(405);
                break;
        }
        break;

    case 'cobros':
        verifyAuth();
        switch ($method) {
            case 'GET':
                if ($id) {
                    if (isset($_GET['action']) && $_GET['action'] === 'ventas') {
                        $cobroController->getVentas();
                    } else {
                        $_GET['id'] = $id;
                        $cobroController->getById();
                    }
                } else {
                    $action = $_GET['action'] ?? null;
                    if ($action === 'sin-cliente') {
                        $cobroController->getSinCliente();
                    } elseif ($action === 'con-cliente') {
                        $cobroController->getConCliente();
                    } elseif ($action === 'ventas-pendientes') {
                        $cobroController->getVentasPendientes();
                    } else {
                        $cobroController->getAll();
                    }
                }
                break;
            case 'POST':
                // Primero revisamos si el action viene en el body (JSON)
                $postData = json_decode(file_get_contents("php://input"), true);
                $action = $_GET['action'] ?? ($postData['action'] ?? null);

                if ($action === 'registrar-pago') {
                    $cobroController->registrarPagoVenta();
                } else {
                    $cobroController->store();
                }
                break;
            case 'PUT':
                $cobroController->update();
                break;
            case 'DELETE':
                $cobroController->delete();
                break;
            default:
                http_response_code(405);
                break;
        }
        break;

    // ============ ENTIDADES RELACIONALES ============

    case 'articulos-venta':
        verifyAuth();
        switch ($method) {
            case 'GET':
                if ($id) {
                    $_GET['id'] = $id;
                    $articuloVentaController->getById();
                } else {
                    $articuloVentaController->getAll();
                }
                break;
            case 'POST':
                $articuloVentaController->store();
                break;
            case 'PUT':
                $articuloVentaController->update();
                break;
            case 'DELETE':
                $articuloVentaController->delete();
                break;
            default:
                http_response_code(405);
                break;
        }
        break;

    case 'ventas-cobro':
        verifyAuth();
        switch ($method) {
            case 'GET':
                if ($id) {
                    $_GET['id'] = $id;
                    $ventaCobroController->getById();
                } else {
                    $ventaCobroController->getAll();
                }
                break;
            case 'POST':
                $ventaCobroController->store();
                break;
            case 'PUT':
                $ventaCobroController->update();
                break;
            case 'DELETE':
                $ventaCobroController->delete();
                break;
            default:
                http_response_code(405);
                break;
        }
        break;

    case 'clientes-equipos':
        verifyAuth();
        switch ($method) {
            case 'GET':
                if ($id) {
                    $_GET['id'] = $id;
                    $clienteEquipoController->getById();
                } else {
                    $clienteEquipoController->getAll();
                }
                break;
            case 'POST':
                $clienteEquipoController->store();
                break;
            case 'DELETE':
                $clienteEquipoController->delete();
                break;
            default:
                http_response_code(405);
                break;
        }
        break;

    case 'articulos-venta-ingresos':
        verifyAuth();
        switch ($method) {
            case 'GET':
                if ($id) {
                    $_GET['id'] = $id;
                    $articuloVentaIngresoArticuloController->getById();
                } else {
                    $articuloVentaIngresoArticuloController->getAll();
                }
                break;
            case 'POST':
                $articuloVentaIngresoArticuloController->store();
                break;
            case 'PUT':
                $articuloVentaIngresoArticuloController->update();
                break;
            case 'DELETE':
                $articuloVentaIngresoArticuloController->delete();
                break;
            default:
                http_response_code(405);
                break;
        }
        break;

    case 'ticket-venta':
        verifyAuth();
        if ($method === 'GET') {
            $ticketVentaController->generar();
        } else {
            http_response_code(405);
        }
        break;

    case 'impresoras-tiquetera':
        verifyAuth();
        switch ($method) {
            case 'GET':
                if (isset($_GET['default'])) {
                    $impresoraTiqueteraController->getDefault();
                } elseif ($id) {
                    $_GET['id'] = $id;
                    $impresoraTiqueteraController->getById();
                } else {
                    $impresoraTiqueteraController->getAll();
                }
                break;
            case 'POST':
                $impresoraTiqueteraController->store();
                break;
            case 'PUT':
                $impresoraTiqueteraController->update();
                break;
            case 'DELETE':
                $impresoraTiqueteraController->delete();
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