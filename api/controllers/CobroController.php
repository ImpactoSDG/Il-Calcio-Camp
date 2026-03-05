<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/Cobro.php';

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
}
