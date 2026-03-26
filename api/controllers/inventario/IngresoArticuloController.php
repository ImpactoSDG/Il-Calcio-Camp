<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseController.php';
require_once __DIR__ . '/../../models/inventario/IngresoArticulo.php';

class IngresoArticuloController extends BaseController
{
    private IngresoArticulo $model;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->model = new IngresoArticulo($this->db);
    }

    /**
     * Obtiene todos los ingresos, por artículo o por rango de fechas
     */
    public function getAll(): void
    {
        try {
            $articulo = $_GET['articulo'] ?? null;
            $fechaDesde = $_GET['fecha_desde'] ?? null;
            $fechaHasta = $_GET['fecha_hasta'] ?? null;
            
            if ($articulo) {
                $result = $this->model->getByArticulo((int)$articulo);
            } elseif ($fechaDesde && $fechaHasta) {
                $result = $this->model->getByFechas($fechaDesde, $fechaHasta);
            } else {
                $result = $this->model->getAll();
            }
            
            $this->respond(200, $result);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener ingresos de artículos');
        }
    }

    /**
     * Obtiene un ingreso de artículo por ID
     */
    public function getById(): void
    {
        try {
            $id = $_GET['id'] ?? null;
            if (!$id) $this->respond(400, ['message' => 'ID requerido.']);

            $result = $this->model->getById((int)$id);
            $result ? $this->respond(200, $result) : $this->respond(404, ['message' => 'Ingreso de artículo no encontrado.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener ingreso de artículo');
        }
    }

    /**
     * Crea un nuevo ingreso de artículo
     */
    public function store(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (empty($data['fecha_ingreso']) || empty($data['cantidad']) || empty($data['id_articulo']) || 
                empty($data['precio_unitario']) || empty($data['total'])) {
                $this->respond(400, ['message' => 'Datos incompletos. Se requieren fecha_ingreso, cantidad, id_articulo, precio_unitario y total.']);
            }

            $vencimiento = $data['vencimiento'] ?? null;
            $esAjuste = $data['es_ajuste'] ?? false;
            $esPerecedero = $data['es_perecedero'] ?? false;

            $id = $this->model->create(
                $data['fecha_ingreso'],
                $vencimiento,
                (bool)$esAjuste,
                (float)$data['cantidad'],
                (int)$data['id_articulo'],
                (float)$data['precio_unitario'],
                (float)$data['total'],
                (bool)$esPerecedero
            );

            if ($id) {
                $this->respond(201, ['message' => 'Ingreso de artículo creado exitosamente.', 'id' => $id]);
            } else {
                $this->respond(500, ['message' => 'Error al crear ingreso de artículo.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al crear ingreso de artículo');
        }
    }

    /**
     * Actualiza un ingreso de artículo existente
     */
    public function update(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (empty($data['id']) || empty($data['fecha_ingreso']) || empty($data['cantidad']) || 
                empty($data['id_articulo']) || empty($data['precio_unitario']) || empty($data['total'])) {
                $this->respond(400, ['message' => 'Datos incompletos.']);
            }

            $vencimiento = $data['vencimiento'] ?? null;
            $esAjuste = $data['es_ajuste'] ?? false;
            $esPerecedero = $data['es_perecedero'] ?? false;

            if ($this->model->update(
                (int)$data['id'],
                $data['fecha_ingreso'],
                $vencimiento,
                (bool)$esAjuste,
                (float)$data['cantidad'],
                (int)$data['id_articulo'],
                (float)$data['precio_unitario'],
                (float)$data['total'],
                (bool)$esPerecedero
            )) {
                $this->respond(200, ['message' => 'Ingreso de artículo actualizado exitosamente.']);
            } else {
                $this->respond(500, ['message' => 'Error al actualizar ingreso de artículo.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al actualizar ingreso de artículo');
        }
    }

    /**
     * Elimina un ingreso de artículo
     */
    public function delete(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $id = $data['id'] ?? null;

            if (!$id) $this->respond(400, ['message' => 'ID requerido.']);

            if ($this->model->delete((int)$id)) {
                $this->respond(200, ['message' => 'Ingreso de artículo eliminado exitosamente.']);
            } else {
                $this->respond(500, ['message' => 'Error al eliminar ingreso de artículo.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al eliminar ingreso de artículo');
        }
    }
}
