<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseController.php';
require_once __DIR__ . '/../../models/comercial/Cobro.php';

class CobroController extends BaseController
{
    private Cobro $model;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->model = new Cobro($this->db);
    }

    /**
     * Obtiene todos los cobros o por cliente
     */
    public function getAll(): void
    {
        try {
            $cliente = $_GET['cliente'] ?? null;
            
            if ($cliente) {
                $result = $this->model->getByCliente((int)$cliente);
            } else {
                $result = $this->model->getAll();
            }
            
            $this->respond(200, $result);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener cobros');
        }
    }

    /**
     * Obtiene un cobro por ID
     */
    public function getById(): void
    {
        try {
            $id = $_GET['id'] ?? null;
            if (!$id) $this->respond(400, ['message' => 'ID requerido.']);

            $result = $this->model->getById((int)$id);
            $result ? $this->respond(200, $result) : $this->respond(404, ['message' => 'Cobro no encontrado.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener cobro');
        }
    }

    /**
     * Obtiene las ventas asociadas a un cobro
     */
    public function getVentas(): void
    {
        try {
            $id = $_GET['id'] ?? null;
            if (!$id) $this->respond(400, ['message' => 'ID de cobro requerido.']);

            $result = $this->model->getVentas((int)$id);
            $this->respond(200, $result);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener ventas del cobro');
        }
    }

    /**
     * Crea un nuevo cobro
     */
    public function store(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (empty($data['id']) || empty($data['cliente_id'])) {
                $this->respond(400, ['message' => 'ID y cliente_id requeridos.']);
            }

            if ($this->model->create((int)$data['id'], (int)$data['cliente_id'])) {
                $this->respond(201, ['message' => 'Cobro creado exitosamente.']);
            } else {
                $this->respond(500, ['message' => 'Error al crear cobro.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al crear cobro');
        }
    }

    /**
     * Actualiza un cobro existente
     */
    public function update(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (empty($data['id']) || empty($data['cliente_id'])) {
                $this->respond(400, ['message' => 'ID y cliente_id requeridos.']);
            }

            if ($this->model->update((int)$data['id'], (int)$data['cliente_id'])) {
                $this->respond(200, ['message' => 'Cobro actualizado exitosamente.']);
            } else {
                $this->respond(500, ['message' => 'Error al actualizar cobro.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al actualizar cobro');
        }
    }

    /**
     * Elimina un cobro
     */
    public function delete(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $id = $data['id'] ?? null;

            if (!$id) $this->respond(400, ['message' => 'ID requerido.']);

            if ($this->model->delete((int)$id)) {
                $this->respond(200, ['message' => 'Cobro eliminado exitosamente.']);
            } else {
                $this->respond(500, ['message' => 'Error al eliminar cobro.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al eliminar cobro');
        }
    }

    /**
     * Obtiene cobros sin cliente asociado, con filtros opcionales.
     */
    public function getSinCliente(): void
    {
        try {
            $filtros = [
                'fecha_desde'  => $_GET['fecha_desde'] ?? null,
                'fecha_hasta'  => $_GET['fecha_hasta'] ?? null,
                'medio_pago_id' => $_GET['medio_pago_id'] ?? null,
            ];
            $result = $this->model->getSinCliente(array_filter($filtros));
            $this->respond(200, $result);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener cobros sin cliente');
        }
    }

    /**
     * Obtiene cobros con cliente asociado, con filtros opcionales.
     */
    public function getConCliente(): void
    {
        try {
            $filtros = [
                'fecha_desde'  => $_GET['fecha_desde'] ?? null,
                'fecha_hasta'  => $_GET['fecha_hasta'] ?? null,
                'medio_pago_id' => $_GET['medio_pago_id'] ?? null,
            ];
            $result = $this->model->getConCliente(array_filter($filtros));
            $this->respond(200, $result);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener cobros con cliente');
        }
    }

    /**
     * Obtiene ventas con saldo pendiente de cobro, con filtros opcionales.
     */
    public function getVentasPendientes(): void
    {
        try {
            $filtros = [
                'fecha_desde'  => $_GET['fecha_desde'] ?? null,
                'fecha_hasta'  => $_GET['fecha_hasta'] ?? null,
                'medio_pago_id' => $_GET['medio_pago_id'] ?? null,
            ];
            $result = $this->model->getVentasPendientes(array_filter($filtros));
            $this->respond(200, $result);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener ventas con cobros pendientes');
        }
    }

    /**
     * Registra un pago para una venta específica.
     */
    public function registrarPagoVenta(int $idUsuario = 0): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            
            if (empty($data['id_venta']) || empty($data['id_medio_pago']) || empty($data['monto'])) {
                $this->respond(400, ['message' => 'Faltan datos requeridos (id_venta, id_medio_pago, monto).']);
                return;
            }

            require_once __DIR__ . '/../../models/comercial/Venta.php';
            $ventaModel = new Venta($this->db);
            
            $fecha = $data['fecha'] ?? date('Y-m-d H:i:s');
            
            // Usar el ID de usuario del body si viene, sino usar el de la sesión (auth)
            $idFinalUsuario = (int)($data['id_usuario'] ?? $idUsuario);

            // esAditivo = true por defecto cuando se llama desde CobroController
            // (que es la vista específica de Cobros donde sumamos al saldo)
            $esAditivo = isset($data['esAditivo']) ? (bool)$data['esAditivo'] : true;

            $success = $ventaModel->registrarPago(
                (int)$data['id_venta'], 
                (int)$data['id_medio_pago'], 
                (float)$data['monto'], 
                $fecha,
                $idFinalUsuario > 0 ? $idFinalUsuario : null,
                $esAditivo
            );

            if ($success) {
                $this->respond(201, ['message' => 'Cobro registrado exitosamente.']);
            } else {
                $this->respond(500, ['message' => 'Error al registrar el cobro.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al registrar pago');
        }
    }

    /**
     * Reporte de cobros de un día específico, agrupado por usuario y medio de cobro.
     */
    public function getReporteDia(): void
    {
        try {
            $fecha = $_GET['fecha'] ?? date('Y-m-d');
            $result = $this->model->getReporteDia($fecha);
            $this->respond(200, $result);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener reporte del día');
        }
    }
}
