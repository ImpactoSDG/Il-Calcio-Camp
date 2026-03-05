<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/ArticuloVentaIngresoArticulo.php';

class ArticuloVentaIngresoArticuloController extends BaseController
{
    private ArticuloVentaIngresoArticulo $model;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->model = new ArticuloVentaIngresoArticulo($this->db);
    }

    /**
     * Obtiene todas las relaciones o por artículo de venta
     */
    public function getAll(): void
    {
        try {
            $articuloVenta = $_GET['articulo_venta'] ?? null;
            
            if ($articuloVenta) {
                $result = $this->model->getByArticuloVenta((int)$articuloVenta);
            } else {
                $result = $this->model->getAll();
            }
            
            $this->respond(200, $result);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener relaciones artículo_venta-ingreso_articulo');
        }
    }

    /**
     * Obtiene una relación por ID
     */
    public function getById(): void
    {
        try {
            $id = $_GET['id'] ?? null;
            if (!$id) $this->respond(400, ['message' => 'ID requerido.']);

            $result = $this->model->getById($id);
            $result ? $this->respond(200, $result) : $this->respond(404, ['message' => 'Relación no encontrada.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener relación');
        }
    }

    /**
     * Crea una nueva relación
     */
    public function store(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (empty($data['id_articulo_venta_ingreso_articulo']) || 
                empty($data['articulo_venta_id_articulo_venta']) || 
                empty($data['ingreso_articulo_id']) || 
                empty($data['cantidad'])) {
                $this->respond(400, ['message' => 'Datos incompletos. Se requieren id, articulo_venta_id, ingreso_articulo_id y cantidad.']);
            }

            if ($this->model->create(
                $data['id_articulo_venta_ingreso_articulo'],
                (int)$data['articulo_venta_id_articulo_venta'],
                (int)$data['ingreso_articulo_id'],
                $data['cantidad']
            )) {
                $this->respond(201, ['message' => 'Relación creada exitosamente.']);
            } else {
                $this->respond(500, ['message' => 'Error al crear relación.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al crear relación');
        }
    }

    /**
     * Actualiza una relación existente
     */
    public function update(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (empty($data['id_articulo_venta_ingreso_articulo']) || 
                empty($data['articulo_venta_id_articulo_venta']) || 
                empty($data['ingreso_articulo_id']) || 
                empty($data['cantidad'])) {
                $this->respond(400, ['message' => 'Datos incompletos.']);
            }

            if ($this->model->update(
                $data['id_articulo_venta_ingreso_articulo'],
                (int)$data['articulo_venta_id_articulo_venta'],
                (int)$data['ingreso_articulo_id'],
                $data['cantidad']
            )) {
                $this->respond(200, ['message' => 'Relación actualizada exitosamente.']);
            } else {
                $this->respond(500, ['message' => 'Error al actualizar relación.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al actualizar relación');
        }
    }

    /**
     * Elimina una relación
     */
    public function delete(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $id = $data['id_articulo_venta_ingreso_articulo'] ?? null;

            if (!$id) $this->respond(400, ['message' => 'ID requerido.']);

            if ($this->model->delete($id)) {
                $this->respond(200, ['message' => 'Relación eliminada exitosamente.']);
            } else {
                $this->respond(500, ['message' => 'Error al eliminar relación.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al eliminar relación');
        }
    }
}
