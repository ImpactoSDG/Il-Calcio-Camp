<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseController.php';
require_once __DIR__ . '/../../models/torneos/Jugador.php';

class JugadorController extends BaseController
{
    private Jugador $model;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->model = new Jugador($this->db);
    }

    /**
     * Obtiene todos los jugadores, activos o filtrados por DNI
     */
    public function getAll(): void
    {
        try {
            $activos = $_GET['activos'] ?? null;
            $dni = $_GET['dni'] ?? null;
            $equipo = $_GET['equipo'] ?? null;

            if ($equipo) {
                $this->respond(200, $this->model->getByEquipo((int)$equipo));
            } elseif ($dni) {
                $result = $this->model->getByDni((string)$dni);
                $this->respond(200, $result ? [$result] : []);
            } elseif ($activos === '1' || $activos === 'true') {
                $this->respond(200, $this->model->getActivos());
            } else {
                $this->respond(200, $this->model->getAll());
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener jugadores');
        }
    }

    /**
     * Obtiene un jugador por ID
     */
    public function getById(): void
    {
        try {
            $id = $_GET['id'] ?? null;
            if (!$id) {
                $this->respond(400, ['message' => 'ID requerido.']);
            }

            $result = $this->model->getById((int)$id);
            $result ? $this->respond(200, $result) : $this->respond(404, ['message' => 'Jugador no encontrado.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener jugador');
        }
    }

    /**
     * Crea un nuevo jugador
     */
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

            $fechaAlta = isset($data['fecha_alta']) && $data['fecha_alta'] !== ''
                ? (string)$data['fecha_alta']
                : date('Y-m-d');

            $nuevoId = $this->model->create(
                trim((string)$data['nombre']),
                trim((string)$data['apellido']),
                $dni,
                isset($data['fecha_nac']) && $data['fecha_nac'] !== '' ? (string)$data['fecha_nac'] : null,
                $fechaAlta,
                isset($data['activo']) ? (bool)$data['activo'] : true,
                !empty($data['id_equipo_actual']) ? (int)$data['id_equipo_actual'] : null,
                isset($data['capitan']) ? (bool)$data['capitan'] : false,
                isset($data['arquero']) ? (bool)$data['arquero'] : false
            );

            if ($nuevoId !== false) {
                $this->respond(201, ['message' => 'Jugador creado exitosamente.', 'id' => $nuevoId]);
            }

            $this->respond(500, ['message' => 'Error al crear jugador.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al crear jugador');
        }
    }

    /**
     * Actualiza un jugador existente
     */
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

            if ($this->model->update(
                (int)$data['id'],
                trim((string)$data['nombre']),
                trim((string)$data['apellido']),
                $dni,
                isset($data['fecha_nac']) && $data['fecha_nac'] !== '' ? (string)$data['fecha_nac'] : null,
                isset($data['fecha_alta']) && $data['fecha_alta'] !== '' ? (string)$data['fecha_alta'] : null,
                (bool)$data['activo'],
                !empty($data['id_equipo_actual']) ? (int)$data['id_equipo_actual'] : null,
                isset($data['capitan']) ? (bool)$data['capitan'] : false,
                isset($data['arquero']) ? (bool)$data['arquero'] : false
            )) {
                $this->respond(200, ['message' => 'Jugador actualizado exitosamente.']);
            }

            $this->respond(500, ['message' => 'Error al actualizar jugador.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al actualizar jugador');
        }
    }

    /**
     * Elimina un jugador
     */
    public function delete(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? null;

            if (!$id) {
                $this->respond(400, ['message' => 'ID requerido.']);
            }

            if ($this->model->delete((int)$id)) {
                $this->respond(200, ['message' => 'Jugador eliminado exitosamente.']);
            }

            $this->respond(500, ['message' => 'Error al eliminar jugador.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al eliminar jugador');
        }
    }
}