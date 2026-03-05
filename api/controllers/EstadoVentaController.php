<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/EstadoVenta.php';

class EstadoVentaController extends BaseController
{
    private EstadoVenta $model;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->model = new EstadoVenta($this->db);
    }

    /**
     * Obtiene todos los estados de venta
     */
    public function getAll(): void
    {
        try {
            $result = $this->model->getAll();
            $this->respond(200, $result);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener estados de venta');
        }
    }

    /**
     * Obtiene un estado de venta por ID
     */
    public function getById(): void
    {
        try {
            $id = $_GET['id'] ?? null;
            if (!$id) $this->respond(400, ['message' => 'ID requerido.']);

            $result = $this->model->getById((int)$id);
            $result ? $this->respond(200, $result) : $this->respond(404, ['message' => 'Estado de venta no encontrado.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener estado de venta');
        }
    }

    /**
     * Crea un nuevo estado de venta
     */
    public function store(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (empty($data['descripcion'])) {
                $this->respond(400, ['message' => 'Descripción requerida.']);
            }

            $id = $this->model->create($data['descripcion']);

            if ($id) {
                $this->respond(201, ['message' => 'Estado de venta creado exitosamente.', 'id' => $id]);
            } else {
                $this->respond(500, ['message' => 'Error al crear estado de venta.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al crear estado de venta');
        }
    }

    /**
     * Actualiza un estado de venta existente
     */
    public function update(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (empty($data['id']) || empty($data['descripcion'])) {
                $this->respond(400, ['message' => 'ID y descripción requeridos.']);
            }

            if ($this->model->update((int)$data['id'], $data['descripcion'])) {
                $this->respond(200, ['message' => 'Estado de venta actualizado exitosamente.']);
            } else {
                $this->respond(500, ['message' => 'Error al actualizar estado de venta.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al actualizar estado de venta');
        }
    }

    /**
     * Elimina un estado de venta
     */
    public function delete(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $id = $data['id'] ?? null;

            if (!$id) $this->respond(400, ['message' => 'ID requerido.']);

            if ($this->model->delete((int)$id)) {
                $this->respond(200, ['message' => 'Estado de venta eliminado exitosamente.']);
            } else {
                $this->respond(500, ['message' => 'Error al eliminar estado de venta.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al eliminar estado de venta');
        }
    }
}
