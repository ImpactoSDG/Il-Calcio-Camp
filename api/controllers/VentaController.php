<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/Venta.php';

class VentaController extends BaseController
{
    private Venta $model;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->model = new Venta($this->db);
    }

    /**
     * Obtiene todas las ventas, por cliente, estado o rango de fechas
     */
    public function getAll(): void
    {
        try {
            $cliente = $_GET['cliente'] ?? null;
            $estado = $_GET['estado'] ?? null;
            $fechaDesde = $_GET['fecha_desde'] ?? null;
            $fechaHasta = $_GET['fecha_hasta'] ?? null;
            
            if ($cliente) {
                $result = $this->model->getByCliente((int)$cliente);
            } elseif ($estado) {
                $result = $this->model->getByEstado((int)$estado);
            } elseif ($fechaDesde && $fechaHasta) {
                $result = $this->model->getByFechas($fechaDesde, $fechaHasta);
            } else {
                $result = $this->model->getAll();
            }
            
            $this->respond(200, $result);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener ventas');
        }
    }

    /**
     * Obtiene una venta por ID
     */
    public function getById(): void
    {
        try {
            $id = $_GET['id'] ?? null;
            if (!$id) $this->respond(400, ['message' => 'ID requerido.']);

            $result = $this->model->getById((int)$id);
            $result ? $this->respond(200, $result) : $this->respond(404, ['message' => 'Venta no encontrada.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener venta');
        }
    }

    /**
     * Obtiene los artículos de una venta
     */
    public function getArticulos(): void
    {
        try {
            $id = $_GET['id'] ?? null;
            if (!$id) $this->respond(400, ['message' => 'ID de venta requerido.']);

            $result = $this->model->getArticulos((int)$id);
            $this->respond(200, $result);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener artículos de la venta');
        }
    }

    /**
     * Crea una nueva venta con sus artículos de manera atómica
     */
    public function store(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (empty($data['fecha']) || empty($data['id_estado_venta']) || empty($data['simbolo'])) {
                $this->respond(400, ['message' => 'Fecha, estado de venta y símbolo requeridos.']);
            }

            if (empty($data['articulos']) || !is_array($data['articulos'])) {
                $this->respond(400, ['message' => 'La venta debe contener artículos.']);
            }

            // Llamamos al nuevo método transaccional
            $result = $this->model->createWithDetails($data, $data['articulos']);

            if ($result['success']) {
                $this->respond(201, ['message' => 'Venta creada exitosamente y stock actualizado.', 'id' => $result['id']]);
            } else {
                $this->respond(400, ['message' => $result['error']]);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al crear venta');
        }
    }

    /**
     * Actualiza una venta existente y sus artículos asociados (Transaccional)
     */
    public function update(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (empty($data['id']) || empty($data['fecha']) || empty($data['id_estado_venta']) || empty($data['simbolo'])) {
                $this->respond(400, ['message' => 'ID, fecha, estado de venta y símbolo requeridos.']);
            }

            if (empty($data['articulos']) || !is_array($data['articulos'])) {
                $this->respond(400, ['message' => 'La venta debe contener artículos.']);
            }

            $result = $this->model->updateWithDetails($data, $data['articulos']);

            if ($result['success']) {
                $this->respond(200, ['message' => 'Venta actualizada exitosamente.']);
            } else {
                $this->respond(400, ['message' => $result['error']]);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al actualizar venta');
        }
    }

    /**
     * Elimina una venta
     */
    public function delete(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $id = $data['id'] ?? null;

            if (!$id) $this->respond(400, ['message' => 'ID requerido.']);

            if ($this->model->delete((int)$id)) {
                $this->respond(200, ['message' => 'Venta eliminada exitosamente.']);
            } else {
                $this->respond(500, ['message' => 'Error al eliminar venta.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al eliminar venta');
        }
    }
}
