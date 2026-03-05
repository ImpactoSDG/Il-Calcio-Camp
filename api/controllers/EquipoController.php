<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/Equipo.php';

class EquipoController extends BaseController
{
    private Equipo $model;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->model = new Equipo($this->db);
    }

    /**
     * Obtiene todos los equipos o solo los activos, o filtra por disciplina
     */
    public function getAll(): void
    {
        try {
            $activos = $_GET['activos'] ?? null;
            $disciplina = $_GET['disciplina'] ?? null;
            
            if ($disciplina) {
                $result = $this->model->getByDisciplina($disciplina);
            } elseif ($activos === '1' || $activos === 'true') {
                $result = $this->model->getActivos();
            } else {
                $result = $this->model->getAll();
            }
            
            $this->respond(200, $result);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener equipos');
        }
    }

    /**
     * Obtiene un equipo por ID
     */
    public function getById(): void
    {
        try {
            $id = $_GET['id'] ?? null;
            if (!$id) $this->respond(400, ['message' => 'ID requerido.']);

            $result = $this->model->getById((int)$id);
            $result ? $this->respond(200, $result) : $this->respond(404, ['message' => 'Equipo no encontrado.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener equipo');
        }
    }

    /**
     * Crea un nuevo equipo
     */
    public function store(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (empty($data['id']) || empty($data['nombre']) || empty($data['disciplina'])) {
                $this->respond(400, ['message' => 'ID, nombre y disciplina requeridos.']);
            }

            $activo = $data['activo'] ?? true;

            if ($this->model->create((int)$data['id'], $data['nombre'], $data['disciplina'], (bool)$activo)) {
                $this->respond(201, ['message' => 'Equipo creado exitosamente.']);
            } else {
                $this->respond(500, ['message' => 'Error al crear equipo.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al crear equipo');
        }
    }

    /**
     * Actualiza un equipo existente
     */
    public function update(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (empty($data['id']) || empty($data['nombre']) || empty($data['disciplina']) || !isset($data['activo'])) {
                $this->respond(400, ['message' => 'ID, nombre, disciplina y estado activo requeridos.']);
            }

            if ($this->model->update((int)$data['id'], $data['nombre'], $data['disciplina'], (bool)$data['activo'])) {
                $this->respond(200, ['message' => 'Equipo actualizado exitosamente.']);
            } else {
                $this->respond(500, ['message' => 'Error al actualizar equipo.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al actualizar equipo');
        }
    }

    /**
     * Elimina un equipo
     */
    public function delete(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $id = $data['id'] ?? null;

            if (!$id) $this->respond(400, ['message' => 'ID requerido.']);

            if ($this->model->delete((int)$id)) {
                $this->respond(200, ['message' => 'Equipo eliminado exitosamente.']);
            } else {
                $this->respond(500, ['message' => 'Error al eliminar equipo.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al eliminar equipo');
        }
    }
}
