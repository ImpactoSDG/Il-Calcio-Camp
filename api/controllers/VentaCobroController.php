<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/VentaCobro.php';

class VentaCobroController extends BaseController
{
    private VentaCobro $model;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->model = new VentaCobro($this->db);
    }

    /**
     * Obtiene todos los registros de venta-cobro, por venta o por cobro
     */
    public function getAll(): void
    {
        try {
            $venta = $_GET['venta'] ?? null;
            $cobro = $_GET['cobro'] ?? null;
            
            if ($venta) {
                $result = $this->model->getByVenta((int)$venta);
            } elseif ($cobro) {
                $result = $this->model->getByCobro((int)$cobro);
            } else {
                $result = $this->model->getAll();
            }
            
            $this->respond(200, $result);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener registros de venta-cobro');
        }
    }

    /**
     * Obtiene un registro de venta-cobro por ID
     */
    public function getById(): void
    {
        try {
            $id = $_GET['id'] ?? null;
            if (!$id) $this->respond(400, ['message' => 'ID requerido.']);

            $result = $this->model->getById((int)$id);
            $result ? $this->respond(200, $result) : $this->respond(404, ['message' => 'Registro de venta-cobro no encontrado.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener registro de venta-cobro');
        }
    }

    /**
     * Crea un nuevo registro de venta-cobro
     */
    public function store(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (empty($data['id_venta_cobro']) || empty($data['id_venta']) || 
                empty($data['id_cobro']) || empty($data['id_medio_pago']) || !isset($data['monto'])) {
                $this->respond(400, ['message' => 'Datos incompletos. Se requieren id_venta_cobro, id_venta, id_cobro, id_medio_pago y monto.']);
            }

            if ($this->model->create(
                (int)$data['id_venta_cobro'],
                (int)$data['id_venta'],
                (int)$data['id_cobro'],
                (int)$data['id_medio_pago'],
                (float)$data['monto']
            )) {
                $this->respond(201, ['message' => 'Registro de venta-cobro creado exitosamente.']);
            } else {
                $this->respond(500, ['message' => 'Error al crear registro de venta-cobro.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al crear registro de venta-cobro');
        }
    }

    /**
     * Actualiza un registro de venta-cobro existente
     */
    public function update(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (empty($data['id_venta_cobro']) || empty($data['id_venta']) || 
                empty($data['id_cobro']) || empty($data['id_medio_pago']) || !isset($data['monto'])) {
                $this->respond(400, ['message' => 'Datos incompletos.']);
            }

            if ($this->model->update(
                (int)$data['id_venta_cobro'],
                (int)$data['id_venta'],
                (int)$data['id_cobro'],
                (int)$data['id_medio_pago'],
                (float)$data['monto']
            )) {
                $this->respond(200, ['message' => 'Registro de venta-cobro actualizado exitosamente.']);
            } else {
                $this->respond(500, ['message' => 'Error al actualizar registro de venta-cobro.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al actualizar registro de venta-cobro');
        }
    }

    /**
     * Elimina un registro de venta-cobro
     */
    public function delete(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $id = $data['id_venta_cobro'] ?? null;

            if (!$id) $this->respond(400, ['message' => 'ID requerido.']);

            if ($this->model->delete((int)$id)) {
                $this->respond(200, ['message' => 'Registro de venta-cobro eliminado exitosamente.']);
            } else {
                $this->respond(500, ['message' => 'Error al eliminar registro de venta-cobro.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al eliminar registro de venta-cobro');
        }
    }
}
