<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseController.php';
require_once __DIR__ . '/../../models/catalogo/MedioCobro.php';

class MedioCobroController extends BaseController
{
    private MedioCobro $model;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->model = new MedioCobro($this->db);
    }

    /**
     * Obtiene todos los medios de cobro o solo los activos
     */
    public function getAll(): void
    {
        try {
            $activos = $_GET['activos'] ?? null;
            
            if ($activos === '1' || $activos === 'true') {
                $result = $this->model->getActivos();
            } else {
                $result = $this->model->getAll();
            }
            
            $this->respond(200, $result);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener medios de cobro');
        }
    }

    /**
     * Obtiene un medio de cobro por ID
     */
    public function getById(): void
    {
        try {
            $id = $_GET['id'] ?? null;
            if (!$id) $this->respond(400, ['message' => 'ID requerido.']);

            $result = $this->model->getById((int)$id);
            $result ? $this->respond(200, $result) : $this->respond(404, ['message' => 'Medio de cobro no encontrado.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener medio de cobro');
        }
    }

    /**
     * Crea un nuevo medio de cobro
     */
    public function store(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (empty($data['id']) || empty($data['descripcion'])) {
                $this->respond(400, ['message' => 'ID y descripción requeridos.']);
            }

            $activo = $data['activo'] ?? true;

            if ($this->model->create((int)$data['id'], $data['descripcion'], (bool)$activo)) {
                $this->respond(201, ['message' => 'Medio de cobro creado exitosamente.']);
            } else {
                $this->respond(500, ['message' => 'Error al crear medio de cobro.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al crear medio de cobro');
        }
    }

    /**
     * Actualiza un medio de cobro existente
     */
    public function update(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (empty($data['id']) || empty($data['descripcion']) || !isset($data['activo'])) {
                $this->respond(400, ['message' => 'ID, descripción y estado activo requeridos.']);
            }

            if ($this->model->update((int)$data['id'], $data['descripcion'], (bool)$data['activo'])) {
                $this->respond(200, ['message' => 'Medio de cobro actualizado exitosamente.']);
            } else {
                $this->respond(500, ['message' => 'Error al actualizar medio de cobro.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al actualizar medio de cobro');
        }
    }

    /**
     * Elimina un medio de cobro
     */
    public function delete(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $id = $data['id'] ?? null;

            if (!$id) $this->respond(400, ['message' => 'ID requerido.']);

            if ($this->model->delete((int)$id)) {
                $this->respond(200, ['message' => 'Medio de cobro eliminado exitosamente.']);
            } else {
                $this->respond(500, ['message' => 'Error al eliminar medio de cobro.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al eliminar medio de cobro');
        }
    }
}
