<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseController.php';
require_once __DIR__ . '/../../models/torneos/Arbitro.php';

class ArbitroController extends BaseController
{
    private Arbitro $model;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->model = new Arbitro($this->db);
    }

    public function getAll(): void
    {
        try {
            $activos = $_GET['activos'] ?? null;
            $dni = $_GET['dni'] ?? null;

            if ($dni) {
                $result = $this->model->getByDni((string)$dni);
                $this->respond(200, $result ? [$result] : []);
            } elseif ($activos === '1' || $activos === 'true') {
                $this->respond(200, $this->model->getActivos());
            } else {
                $this->respond(200, $this->model->getAll());
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener arbitros');
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
            $result ? $this->respond(200, $result) : $this->respond(404, ['message' => 'Arbitro no encontrado.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener arbitro');
        }
    }

    public function store(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['nombre']) || empty($data['apellido'])) {
                $this->respond(400, ['message' => 'Nombre y apellido requeridos.']);
            }

            $dni = isset($data['dni']) && $data['dni'] !== '' ? trim((string)$data['dni']) : null;
            if ($dni !== null && !preg_match('/^\d{8}$/', $dni)) {
                $this->respond(400, ['message' => 'El DNI debe tener exactamente 8 numeros.']);
            }

            $email = isset($data['email']) && $data['email'] !== '' ? trim((string)$data['email']) : null;
            if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->respond(400, ['message' => 'El email no tiene un formato valido.']);
            }

            $nuevoId = $this->model->create(
                trim((string)$data['nombre']),
                trim((string)$data['apellido']),
                $dni,
                isset($data['telefono']) && $data['telefono'] !== '' ? trim((string)$data['telefono']) : null,
                $email,
                isset($data['activo']) ? (bool)$data['activo'] : true
            );

            if ($nuevoId !== false) {
                $this->respond(201, ['message' => 'Arbitro creado exitosamente.', 'id' => $nuevoId]);
            }

            $this->respond(500, ['message' => 'Error al crear arbitro.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al crear arbitro');
        }
    }

    public function update(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['id']) || empty($data['nombre']) || empty($data['apellido']) || !isset($data['activo'])) {
                $this->respond(400, ['message' => 'ID, nombre, apellido y estado activo requeridos.']);
            }

            $dni = isset($data['dni']) && $data['dni'] !== '' ? trim((string)$data['dni']) : null;
            if ($dni !== null && !preg_match('/^\d{8}$/', $dni)) {
                $this->respond(400, ['message' => 'El DNI debe tener exactamente 8 numeros.']);
            }

            $email = isset($data['email']) && $data['email'] !== '' ? trim((string)$data['email']) : null;
            if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->respond(400, ['message' => 'El email no tiene un formato valido.']);
            }

            if ($this->model->update(
                (int)$data['id'],
                trim((string)$data['nombre']),
                trim((string)$data['apellido']),
                $dni,
                isset($data['telefono']) && $data['telefono'] !== '' ? trim((string)$data['telefono']) : null,
                $email,
                (bool)$data['activo']
            )) {
                $this->respond(200, ['message' => 'Arbitro actualizado exitosamente.']);
            }

            $this->respond(500, ['message' => 'Error al actualizar arbitro.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al actualizar arbitro');
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
                $this->respond(200, ['message' => 'Arbitro eliminado exitosamente.']);
            }

            $this->respond(500, ['message' => 'Error al eliminar arbitro.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al eliminar arbitro');
        }
    }
}