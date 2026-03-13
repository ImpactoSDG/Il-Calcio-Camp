<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/PagoProveedor.php';

class PagoProveedorController extends BaseController
{
    private PagoProveedor $model;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->model = new PagoProveedor($this->db);
    }

    public function getAll(): void
    {
        try {
            $idProveedor = $_GET['proveedor'] ?? null;
            $result = $idProveedor
                ? $this->model->getByProveedor((int)$idProveedor)
                : $this->model->getAll();
            $this->respond(200, $result);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener pagos a proveedores');
        }
    }

    public function getById(): void
    {
        try {
            $id = $_GET['id'] ?? null;
            if (!$id) $this->respond(400, ['message' => 'ID requerido.']);

            $result = $this->model->getById((int)$id);
            $result
                ? $this->respond(200, $result)
                : $this->respond(404, ['message' => 'Pago no encontrado.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener pago');
        }
    }

    public function store(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (empty($data['id_proveedor'])) $this->respond(400, ['message' => 'El proveedor es obligatorio.']);
            if (empty($data['monto']) || (float)$data['monto'] <= 0) $this->respond(400, ['message' => 'El monto debe ser mayor a cero.']);
            if (empty($data['id_medio_cobro'])) $this->respond(400, ['message' => 'El medio de cobro es obligatorio.']);

            $id = $this->model->create(
                (int)$data['id_proveedor'],
                (float)$data['monto'],
                (int)$data['id_medio_cobro'],
                $data['observacion'] ?? null
            );

            $id
                ? $this->respond(201, ['message' => 'Pago registrado correctamente.', 'id' => $id])
                : $this->respond(500, ['message' => 'Error al registrar el pago.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al registrar pago');
        }
    }

    public function delete(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $id = $data['id'] ?? null;
            if (!$id) $this->respond(400, ['message' => 'ID requerido.']);

            $result = $this->model->delete((int)$id);
            $result
                ? $this->respond(200, ['message' => 'Pago eliminado correctamente.'])
                : $this->respond(500, ['message' => 'Error al eliminar el pago.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al eliminar pago');
        }
    }
}
