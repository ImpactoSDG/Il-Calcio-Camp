<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/Provincia.php';

class ProvinciaController extends BaseController
{
    private Provincia $model;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->model = new Provincia($this->db);
    }

    /**
     * Obtiene todas las provincias
     */
    public function getAll(): void
    {
        try {
            $result = $this->model->getAll();
            $this->respond(200, $result);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener provincias');
        }
    }

    /**
     * Obtiene una provincia por ID
     */
    public function getById(): void
    {
        try {
            $id = $_GET['id'] ?? null;
            if (!$id) $this->respond(400, ['message' => 'ID requerido.']);

            $result = $this->model->getById((int)$id);
            $result ? $this->respond(200, $result) : $this->respond(404, ['message' => 'Provincia no encontrada.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener provincia');
        }
    }

    /**
     * Crea una nueva provincia
     */
    public function store(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (empty($data['id']) || empty($data['provincia'])) {
                $this->respond(400, ['message' => 'ID y nombre de provincia requeridos.']);
            }

            if ($this->model->create((int)$data['id'], $data['provincia'])) {
                $this->respond(201, ['message' => 'Provincia creada exitosamente.']);
            } else {
                $this->respond(500, ['message' => 'Error al crear provincia.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al crear provincia');
        }
    }

    /**
     * Actualiza una provincia existente
     */
    public function update(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (empty($data['id']) || empty($data['provincia'])) {
                $this->respond(400, ['message' => 'ID y nombre de provincia requeridos.']);
            }

            if ($this->model->update((int)$data['id'], $data['provincia'])) {
                $this->respond(200, ['message' => 'Provincia actualizada exitosamente.']);
            } else {
                $this->respond(500, ['message' => 'Error al actualizar provincia.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al actualizar provincia');
        }
    }

    /**
     * Elimina una provincia
     */
    public function delete(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $id = $data['id'] ?? null;

            if (!$id) $this->respond(400, ['message' => 'ID requerido.']);

            if ($this->model->delete((int)$id)) {
                $this->respond(200, ['message' => 'Provincia eliminada exitosamente.']);
            } else {
                $this->respond(500, ['message' => 'Error al eliminar provincia.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al eliminar provincia');
        }
    }
}
