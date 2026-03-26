<?php
// --- Auth ---
require_once __DIR__ . '/controllers/auth/RegistroPublicoController.php';
require_once __DIR__ . '/controllers/auth/AuthController.php';

// --- Usuarios y Configuraciones ---
require_once __DIR__ . '/controllers/usuarios/UsuarioController.php';
require_once __DIR__ . '/controllers/usuarios/PermisoController.php';
require_once __DIR__ . '/controllers/usuarios/ConfiguracionController.php';
require_once __DIR__ . '/controllers/usuarios/ModuloController.php';

// --- Catálogo (datos de referencia) ---
require_once __DIR__ . '/controllers/catalogo/EstadoVentaController.php';
require_once __DIR__ . '/controllers/catalogo/CondicionIvaReceptorController.php';
require_once __DIR__ . '/controllers/catalogo/ProvinciaController.php';
require_once __DIR__ . '/controllers/catalogo/DisciplinaController.php';
require_once __DIR__ . '/controllers/catalogo/MedioCobroController.php';

// --- Torneos (Gestión Deportiva) ---
require_once __DIR__ . '/controllers/torneos/ArbitroController.php';
require_once __DIR__ . '/controllers/torneos/EquipoController.php';
require_once __DIR__ . '/controllers/torneos/JugadorController.php';
require_once __DIR__ . '/controllers/torneos/TorneoController.php';
require_once __DIR__ . '/controllers/torneos/PlanTorneoController.php';
require_once __DIR__ . '/controllers/torneos/ClienteEquipoController.php';
require_once __DIR__ . '/controllers/torneos/TipoEventoPartidoController.php';
require_once __DIR__ . '/controllers/torneos/EventoPartidoController.php';

// --- Instalaciones (Canchas y Eventos) ---
require_once __DIR__ . '/controllers/instalaciones/CanchaController.php';
require_once __DIR__ . '/controllers/instalaciones/EventoController.php';
require_once __DIR__ . '/controllers/instalaciones/EstadoEventoController.php';

// --- Inventario (Stock y Proveedores) ---
require_once __DIR__ . '/controllers/inventario/CategoriaArticuloController.php';
require_once __DIR__ . '/controllers/inventario/ArticuloController.php';
require_once __DIR__ . '/controllers/inventario/IngresoArticuloController.php';
require_once __DIR__ . '/controllers/inventario/ArticuloVentaIngresoArticuloController.php';
require_once __DIR__ . '/controllers/inventario/ProveedorController.php';
require_once __DIR__ . '/controllers/inventario/PedidoProveedorController.php';
require_once __DIR__ . '/controllers/inventario/PagoProveedorController.php';

// --- Comercial (Ventas y Clientes) ---
require_once __DIR__ . '/controllers/comercial/SimboloDiaController.php';
require_once __DIR__ . '/controllers/comercial/ClienteController.php';
require_once __DIR__ . '/controllers/comercial/VentaController.php';
require_once __DIR__ . '/controllers/comercial/ArticuloVentaController.php';
require_once __DIR__ . '/controllers/comercial/CobroController.php';
require_once __DIR__ . '/controllers/comercial/VentaCobroController.php';
require_once __DIR__ . '/controllers/comercial/TicketVentaController.php';

// --- Hardware ---
require_once __DIR__ . '/controllers/hardware/ImpresoraTiqueteraController.php';
require_once __DIR__ . '/controllers/hardware/QzCertificadoController.php';

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
$disciplinaController = new DisciplinaController($db);
$clienteController = new ClienteController($db);
$ventaController = new VentaController($db);
$articuloVentaController = new ArticuloVentaController($db);
$medioCobroController = new MedioCobroController($db);
$cobroController = new CobroController($db);
$ventaCobroController = new VentaCobroController($db);
$equipoController = new EquipoController($db);
$jugadorController = new JugadorController($db);
$arbitroController = new ArbitroController($db);
$eventoController = new EventoController($db);
$estadoEventoController = new EstadoEventoController($db);
$canchaController = new CanchaController($db);
$torneoController = new TorneoController($db);
$planTorneoController = new PlanTorneoController($db);
$clienteEquipoController = new ClienteEquipoController($db);
$articuloVentaIngresoArticuloController = new ArticuloVentaIngresoArticuloController($db);
$tipoEventoPartidoController = new TipoEventoPartidoController($db);
$eventoPartidoController = new EventoPartidoController($db);
$ticketVentaController = new TicketVentaController($db);
$impresoraTiqueteraController  = new ImpresoraTiqueteraController($db);
$qzCertificadoController       = new QzCertificadoController($db);
$proveedorController           = new ProveedorController($db);
$pedidoProveedorController = new PedidoProveedorController($db);
$pagoProveedorController = new PagoProveedorController($db);
$simboloDiaController = new SimboloDiaController();
$registroPublicoController = new RegistroPublicoController($db);

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

    // Registro público de equipos (sin autenticación)
    case 'registro-equipo':
        if ($method === 'POST') {
            $registroPublicoController->registrar();
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
    case 'toggle-favorito':
        verifyAuth();
        if ($method === 'POST') {
            $usuarioController->toggleFavorito();
        } else {
            http_response_code(405);
        }
        break;
    case 'reorder-modulos':
        verifyAuth();
        if ($method === 'POST') {
            $usuarioController->reorderModulos();
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

    case 'disciplinas':
        verifyAuth();
        if ($method === 'GET') {
            $disciplinaController->getAll();
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
        if ($method === 'POST' && $id === 'subir-escudo') {
            $equipoController->subirEscudo();
            break;
        }
        if ($method === 'POST' && $id === 'baja-logica') {
            $equipoController->bajaLogica();
            break;
        }
        if ($method === 'POST' && $id === 'confirmar') {
            $equipoController->confirmarEquipo();
            break;
        }
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

    case 'jugadores':
        verifyAuth();
        switch ($method) {
            case 'GET':
                if ($id) {
                    $_GET['id'] = $id;
                    $jugadorController->getById();
                } else {
                    $jugadorController->getAll();
                }
                break;
            case 'POST':
                $jugadorController->store();
                break;
            case 'PUT':
                $jugadorController->update();
                break;
            case 'DELETE':
                $jugadorController->delete();
                break;
            default:
                http_response_code(405);
                break;
        }
        break;

    case 'arbitros':
        verifyAuth();
        switch ($method) {
            case 'GET':
                if ($id) {
                    $_GET['id'] = $id;
                    $arbitroController->getById();
                } else {
                    $arbitroController->getAll();
                }
                break;
            case 'POST':
                $arbitroController->store();
                break;
            case 'PUT':
                $arbitroController->update();
                break;
            case 'DELETE':
                $arbitroController->delete();
                break;
            default:
                http_response_code(405);
                break;
        }
        break;

    case 'estados-evento':
        verifyAuth();
        if ($method === 'GET') {
            if ($id) {
                $_GET['id'] = $id;
                $estadoEventoController->getById();
            } else {
                $estadoEventoController->getAll();
            }
        } else {
            http_response_code(405);
        }
        break;

    case 'canchas':
        verifyAuth();
        switch ($method) {
            case 'GET':
                if ($id) {
                    $_GET['id'] = $id;
                    $canchaController->getById();
                } else {
                    $canchaController->getAll();
                }
                break;
            case 'POST':
                $canchaController->store();
                break;
            case 'PUT':
                $canchaController->update();
                break;
            case 'DELETE':
                $canchaController->delete();
                break;
            default:
                http_response_code(405);
                break;
        }
        break;

    case 'torneos':
        verifyAuth();
        if ($method === 'GET') {
            if ($id) {
                $_GET['id'] = $id;
                $torneoController->getById();
            } else {
                $torneoController->getAll();
            }
        } elseif ($method === 'DELETE') {
            if ($id) {
                $_GET['id'] = $id;
            }
            $torneoController->delete();
        } else {
            http_response_code(405);
        }
        break;

    case 'dashboard-torneo':
        verifyAuth();
        if ($method === 'GET') {
            $torneoController->getDashboard();
        } else {
            http_response_code(405);
        }
        break;

    case 'planificacion-torneo':
        verifyAuth();
        if ($method === 'POST' && $id === 'confirmar') {
            $planTorneoController->confirmar();
        } elseif ($method === 'POST' && $id === 'subir-comprobante') {
            $planTorneoController->subirComprobantePago();
        } elseif ($method === 'GET' && $id === 'confirmada') {
            $planTorneoController->getConfirmadaVigente();
        } elseif ($method === 'GET' && $id === 'detalle') {
            $planTorneoController->getDetalleGestion();
        } elseif ($method === 'GET' && $id === 'programacion-data') {
            $planTorneoController->getProgramacionData();
        } elseif ($method === 'GET' && $id === 'equipos-disponibles') {
            $planTorneoController->getEquiposDisponibles();
        } elseif ($method === 'POST' && $id === 'inscribir-equipos') {
            $planTorneoController->inscribirEquipos();
        } elseif ($method === 'POST' && $id === 'eliminar-inscripcion') {
            $planTorneoController->eliminarInscripcion();
        } elseif ($method === 'POST' && $id === 'auto-programar') {
            $planTorneoController->autoProgramarEventos();
        } elseif ($method === 'POST' && $id === 'actualizar-programacion-evento') {
            $planTorneoController->actualizarProgramacionEvento();
        } elseif ($method === 'POST' && $id === 'deshacer-programacion') {
            $planTorneoController->deshacerProgramacionEventos();
        } elseif ($method === 'POST' && $id === 'asignar-equipos') {
            $planTorneoController->asignarEquipos();
        } elseif ($method === 'POST' && $id === 'asignar-cruces') {
            $planTorneoController->asignarEquiposCruces();
        } elseif ($method === 'POST' && $id === 'actualizar-asignacion-cruce') {
            $planTorneoController->actualizarAsignacionCruce();
        } elseif ($method === 'POST' && $id === 'actualizar-pago-evento-equipo') {
            $planTorneoController->actualizarPagoEventoEquipo();
        } elseif ($method === 'POST' && $id === 'eliminar-asignaciones') {
            $planTorneoController->eliminarAsignaciones();
        } elseif ($method === 'POST') {
            $planTorneoController->simular();
        } else {
            http_response_code(405);
        }
        break;

    case 'eventos':
        verifyAuth();
        switch ($method) {
            case 'GET':
                if ($id) {
                    $_GET['id'] = $id;
                    $eventoController->getById();
                } else {
                    $eventoController->getAll();
                }
                break;
            case 'POST':
                $eventoController->store();
                break;
            case 'PUT':
                $eventoController->update();
                break;
            case 'DELETE':
                $eventoController->delete();
                break;
            default:
                http_response_code(405);
                break;
        }
        break;

    case 'tipos-evento-partido':
        verifyAuth();
        if ($method === 'GET') {
            $tipoEventoPartidoController->getAll();
        } else {
            http_response_code(405);
        }
        break;

    case 'eventos-partido':
        verifyAuth();
        switch ($method) {
            case 'GET':
                if ($id) {
                    $_GET['id'] = $id;
                    $eventoPartidoController->getById();
                } else {
                    $eventoPartidoController->getAll();
                }
                break;
            case 'POST':
                $eventoPartidoController->store();
                break;
            case 'PUT':
                $eventoPartidoController->update();
                break;
            case 'DELETE':
                $eventoPartidoController->delete();
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
        $ventasAuth = verifyAuth();
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
                $ventaController->store((int)($ventasAuth['id'] ?? 0));
                break;
            case 'PUT':
                $ventaController->update((int)($ventasAuth['id'] ?? 0));
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
        $cobrosAuth = verifyAuth();
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
                    } elseif ($action === 'reporte-dia') {
                        $cobroController->getReporteDia();
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
                    $cobroController->registrarPagoVenta((int)($cobrosAuth['id'] ?? 0));
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

    case 'simbolo-dia':
        verifyAuth();
        if ($method === 'GET') {
            $simboloDiaController->obtener();
        } else {
            http_response_code(405);
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

    case 'qz-certificados':
        verifyAuth();
        switch ($method) {
            case 'GET':
                $qzCertificadoController->get();
                break;
            case 'POST':
                $qzCertificadoController->upload();
                break;
            case 'DELETE':
                $qzCertificadoController->delete();
                break;
            default:
                http_response_code(405);
                break;
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

    // ============ MÓDULO DE COMPRAS ============

    case 'proveedores':
        verifyAuth();
        switch ($method) {
            case 'GET':
                if ($id) { $_GET['id'] = $id; $proveedorController->getById(); }
                else { $proveedorController->getAll(); }
                break;
            case 'POST':
                $proveedorController->store();
                break;
            case 'PUT':
                $proveedorController->update();
                break;
            case 'DELETE':
                $proveedorController->delete();
                break;
            default:
                http_response_code(405);
                break;
        }
        break;

    case 'pedidos-proveedor':
        verifyAuth();
        switch ($method) {
            case 'GET':
                if ($id) { $_GET['id'] = $id; $pedidoProveedorController->getById(); }
                else { $pedidoProveedorController->getAll(); }
                break;
            case 'POST':
                if ($id === 'cambiar-estado') {
                    $pedidoProveedorController->cambiarEstado();
                } else {
                    $pedidoProveedorController->store();
                }
                break;
            case 'PUT':
                $pedidoProveedorController->update();
                break;
            case 'DELETE':
                $pedidoProveedorController->delete();
                break;
            default:
                http_response_code(405);
                break;
        }
        break;

    case 'pagos-proveedor':
        verifyAuth();
        switch ($method) {
            case 'GET':
                if ($id) { $_GET['id'] = $id; $pagoProveedorController->getById(); }
                else { $pagoProveedorController->getAll(); }
                break;
            case 'POST':
                $pagoProveedorController->store();
                break;
            case 'DELETE':
                $pagoProveedorController->delete();
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