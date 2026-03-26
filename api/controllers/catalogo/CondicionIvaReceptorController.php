<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseController.php';
require_once __DIR__ . '/../../models/catalogo/CondicionIvaReceptor.php';

class CondicionIvaReceptorController extends BaseController
{
    private CondicionIvaReceptor $model;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->model = new CondicionIvaReceptor($this->db);
    }

    /**
     * Obtiene todas las condiciones de IVA
     */
    public function getAll(): void
    {
        try {
            $result = $this->model->getAll();
            $this->respond(200, $result);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener condiciones de IVA');
        }
    }

    /**
     * Obtiene una condición de IVA por ID
     */
    public function getById(): void
    {
        try {
            $id = $_GET['id'] ?? null;
            if (!$id) $this->respond(400, ['message' => 'ID requerido.']);

            $result = $this->model->getById((int)$id);
            $result ? $this->respond(200, $result) : $this->respond(404, ['message' => 'Condición de IVA no encontrada.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener condición de IVA');
        }
    }

    /**
     * Crea una nueva condición de IVA
     */
    public function store(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (empty($data['id']) || empty($data['descripcion_condicion'])) {
                $this->respond(400, ['message' => 'ID y descripción requeridos.']);
            }

            if ($this->model->create((int)$data['id'], $data['descripcion_condicion'])) {
                $this->respond(201, ['message' => 'Condición de IVA creada exitosamente.']);
            } else {
                $this->respond(500, ['message' => 'Error al crear condición de IVA.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al crear condición de IVA');
        }
    }

    /**
     * Actualiza una condición de IVA existente
     */
    public function update(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (empty($data['id']) || empty($data['descripcion_condicion'])) {
                $this->respond(400, ['message' => 'ID y descripción requeridos.']);
            }

            if ($this->model->update((int)$data['id'], $data['descripcion_condicion'])) {
                $this->respond(200, ['message' => 'Condición de IVA actualizada exitosamente.']);
            } else {
                $this->respond(500, ['message' => 'Error al actualizar condición de IVA.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al actualizar condición de IVA');
        }
    }

    /**
     * Elimina una condición de IVA
     */
    public function delete(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $id = $data['id'] ?? null;

            if (!$id) $this->respond(400, ['message' => 'ID requerido.']);

            if ($this->model->delete((int)$id)) {
                $this->respond(200, ['message' => 'Condición de IVA eliminada exitosamente.']);
            } else {
                $this->respond(500, ['message' => 'Error al eliminar condición de IVA.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al eliminar condición de IVA');
        }
    }
}
