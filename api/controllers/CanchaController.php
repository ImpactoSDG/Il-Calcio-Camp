<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/Cancha.php';

class CanchaController extends BaseController
{
    private Cancha $model;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->model = new Cancha($this->db);
    }

    public function getAll(): void
    {
        try {
            $this->respond(200, $this->model->getAll());
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener canchas');
        }
    }

    public function getById(): void
    {
        try {
            $id = $_GET['id'] ?? null;
            if (!$id) {
                $this->respond(400, ['message' => 'ID requerido.']);
            }
            $result = $this->model->getById((int)$id);
            $result ? $this->respond(200, $result) : $this->respond(404, ['message' => 'Cancha no encontrada.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener cancha');
        }
    }

    public function store(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['nombre'])) {
                $this->respond(400, ['message' => 'Nombre requerido.']);
            }

            $nuevoId = $this->model->create(
                trim((string)$data['nombre']),
                isset($data['descripcion']) && $data['descripcion'] !== '' ? trim((string)$data['descripcion']) : null,
                isset($data['activo']) ? (bool)$data['activo'] : true
            );

            if ($nuevoId !== false) {
                $this->respond(201, ['message' => 'Cancha creada exitosamente.', 'id' => $nuevoId]);
            }

            $this->respond(500, ['message' => 'Error al crear cancha.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al crear cancha');
        }
    }

    public function update(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['id']) || empty($data['nombre']) || !isset($data['activo'])) {
                $this->respond(400, ['message' => 'ID, nombre y estado activo requeridos.']);
            }

            if ($this->model->update(
                (int)$data['id'],
                trim((string)$data['nombre']),
                isset($data['descripcion']) && $data['descripcion'] !== '' ? trim((string)$data['descripcion']) : null,
                (bool)$data['activo']
            )) {
                $this->respond(200, ['message' => 'Cancha actualizada exitosamente.']);
            }

            $this->respond(500, ['message' => 'Error al actualizar cancha.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al actualizar cancha');
        }
    }

    public function delete(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? null;

            if (!$id) {
                $this->respond(400, ['message' => 'ID requerido.']);
            }

            if ($this->model->delete((int)$id)) {
                $this->respond(200, ['message' => 'Cancha eliminada exitosamente.']);
            }

            $this->respond(500, ['message' => 'Error al eliminar cancha.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al eliminar cancha');
        }
    }
}