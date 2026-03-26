<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseController.php';
require_once __DIR__ . '/../../models/inventario/ArticuloVenta.php';

class ArticuloVentaController extends BaseController
{
    private ArticuloVenta $model;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->model = new ArticuloVenta($this->db);
    }

    /**
     * Obtiene todos los artículos de ventas o por venta
     */
    public function getAll(): void
    {
        try {
            $venta = $_GET['venta'] ?? null;
            
            if ($venta) {
                $result = $this->model->getByVenta((int)$venta);
            } else {
                $result = $this->model->getAll();
            }
            
            $this->respond(200, $result);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener artículos de venta');
        }
    }

    /**
     * Obtiene un artículo de venta por ID
     */
    public function getById(): void
    {
        try {
            $id = $_GET['id'] ?? null;
            if (!$id) $this->respond(400, ['message' => 'ID requerido.']);

            $result = $this->model->getById((int)$id);
            $result ? $this->respond(200, $result) : $this->respond(404, ['message' => 'Artículo de venta no encontrado.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener artículo de venta');
        }
    }

    /**
     * Crea un nuevo artículo de venta
     */
    public function store(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (empty($data['id_articulo']) || empty($data['id_venta']) || 
                !isset($data['cantidad']) || !isset($data['precio_unitario']) || !isset($data['total'])) {
                $this->respond(400, ['message' => 'Datos incompletos. Se requieren id_articulo, id_venta, cantidad, precio_unitario y total.']);
            }

            $id = $this->model->create(
                (int)$data['id_articulo'],
                (int)$data['id_venta'],
                (float)$data['cantidad'],
                (float)$data['precio_unitario'],
                (float)$data['total']
            );

            if ($id) {
                $this->respond(201, ['message' => 'Artículo de venta creado exitosamente.', 'id' => $id]);
            } else {
                $this->respond(500, ['message' => 'Error al crear artículo de venta.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al crear artículo de venta');
        }
    }

    /**
     * Actualiza un artículo de venta existente
     */
    public function update(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (empty($data['id']) || empty($data['id_articulo']) || empty($data['id_venta']) || 
                !isset($data['cantidad']) || !isset($data['precio_unitario']) || !isset($data['total'])) {
                $this->respond(400, ['message' => 'Datos incompletos.']);
            }

            if ($this->model->update(
                (int)$data['id'],
                (int)$data['id_articulo'],
                (int)$data['id_venta'],
                (float)$data['cantidad'],
                (float)$data['precio_unitario'],
                (float)$data['total']
            )) {
                $this->respond(200, ['message' => 'Artículo de venta actualizado exitosamente.']);
            } else {
                $this->respond(500, ['message' => 'Error al actualizar artículo de venta.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al actualizar artículo de venta');
        }
    }

    /**
     * Elimina un artículo de venta
     */
    public function delete(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $id = $data['id'] ?? null;

            if (!$id) $this->respond(400, ['message' => 'ID requerido.']);

            if ($this->model->delete((int)$id)) {
                $this->respond(200, ['message' => 'Artículo de venta eliminado exitosamente.']);
            } else {
                $this->respond(500, ['message' => 'Error al eliminar artículo de venta.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al eliminar artículo de venta');
        }
    }
}
