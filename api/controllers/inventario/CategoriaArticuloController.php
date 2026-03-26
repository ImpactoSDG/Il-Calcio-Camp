<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseController.php';
require_once __DIR__ . '/../../models/inventario/CategoriaArticulo.php';

class CategoriaArticuloController extends BaseController
{
    private CategoriaArticulo $model;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->model = new CategoriaArticulo($this->db);
    }

    /**
     * Obtiene todas las categorías de artículos
     */
    public function getAll(): void
    {
        try {
            $result = $this->model->getAll();
            $this->respond(200, $result);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener categorías de artículos');
        }
    }

    /**
     * Obtiene una categoría por ID
     */
    public function getById(): void
    {
        try {
            $id = $_GET['id'] ?? null;
            if (!$id) $this->respond(400, ['message' => 'ID requerido.']);

            $result = $this->model->getById((int)$id);
            $result ? $this->respond(200, $result) : $this->respond(404, ['message' => 'Categoría no encontrada.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener categoría');
        }
    }

    /**
     * Crea una nueva categoría de artículo
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
                $this->respond(201, ['message' => 'Categoría creada exitosamente.', 'id' => $id]);
            } else {
                $this->respond(500, ['message' => 'Error al crear categoría.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al crear categoría');
        }
    }

    /**
     * Actualiza una categoría existente
     */
    public function update(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (empty($data['id']) || empty($data['descripcion'])) {
                $this->respond(400, ['message' => 'ID y descripción requeridos.']);
            }

            if ($this->model->update((int)$data['id'], $data['descripcion'])) {
                $this->respond(200, ['message' => 'Categoría actualizada exitosamente.']);
            } else {
                $this->respond(500, ['message' => 'Error al actualizar categoría.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al actualizar categoría');
        }
    }

    /**
     * Elimina una categoría
     */
    public function delete(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $id = $data['id'] ?? null;

            if (!$id) $this->respond(400, ['message' => 'ID requerido.']);

            if ($this->model->delete((int)$id)) {
                $this->respond(200, ['message' => 'Categoría eliminada exitosamente.']);
            } else {
                $this->respond(500, ['message' => 'Error al eliminar categoría.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al eliminar categoría');
        }
    }
}
