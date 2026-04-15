<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseController.php';
require_once __DIR__ . '/../../models/inventario/ArticuloResumenVenta.php';

class ArticuloResumenVentaController extends BaseController
{
    private ArticuloResumenVenta $model;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->model = new ArticuloResumenVenta($this->db);
    }

    /**
     * GET /articulos-vendidos?fecha_desde=YYYY-MM-DD&fecha_hasta=YYYY-MM-DD
     *
     * Retorna la cantidad vendida en el período y el stock acumulado
     * hasta la fecha final, para cada artículo activo.
     */
    public function getAll(): void
    {
        try {
            $fechaDesde = trim($_GET['fecha_desde'] ?? '');
            $fechaHasta = trim($_GET['fecha_hasta'] ?? '');

            if ($fechaDesde === '' || $fechaHasta === '') {
                $this->respond(400, ['message' => 'Los parámetros fecha_desde y fecha_hasta son requeridos.']);
                return;
            }

            $regexFecha = '/^\d{4}-\d{2}-\d{2}$/';
            if (!preg_match($regexFecha, $fechaDesde) || !preg_match($regexFecha, $fechaHasta)) {
                $this->respond(400, ['message' => 'Formato de fecha inválido. Se esperan fechas en formato YYYY-MM-DD.']);
                return;
            }

            if ($fechaDesde > $fechaHasta) {
                $this->respond(400, ['message' => 'La fecha desde no puede ser mayor a la fecha hasta.']);
                return;
            }

            $result = $this->model->getResumenPorPeriodo($fechaDesde, $fechaHasta);
            $this->respond(200, $result);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener artículos vendidos');
        }
    }
}
