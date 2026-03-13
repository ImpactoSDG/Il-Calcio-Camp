<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/Proveedor.php';

class ProveedorController extends BaseController
{
    private Proveedor $model;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->model = new Proveedor($this->db);
    }

    public function getAll(): void
    {
        try {
            $soloActivos = isset($_GET['activos']) && $_GET['activos'] === '1';
            $result = $soloActivos ? $this->model->getActivos() : $this->model->getAll();
            $this->respond(200, $result);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener proveedores');
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
                : $this->respond(404, ['message' => 'Proveedor no encontrado.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener proveedor');
        }
    }

    public function store(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (empty($data['nombre'])) {
                $this->respond(400, ['message' => 'El nombre del proveedor es obligatorio.']);
            }

            $id = $this->model->create(
                trim($data['nombre']),
                isset($data['apellido']) ? trim($data['apellido']) : null,
                isset($data['nombre_fantasia']) ? trim($data['nombre_fantasia']) : null,
                isset($data['telefono']) ? trim($data['telefono']) : null,
                isset($data['direccion']) ? trim($data['direccion']) : null
            );

            $id
                ? $this->respond(201, ['message' => 'Proveedor creado exitosamente.', 'id' => $id])
                : $this->respond(500, ['message' => 'Error al crear el proveedor.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al crear proveedor');
        }
    }

    public function update(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (empty($data['id_proveedor'])) $this->respond(400, ['message' => 'ID del proveedor requerido.']);
            if (empty($data['nombre'])) $this->respond(400, ['message' => 'El nombre es obligatorio.']);

            $result = $this->model->update(
                (int)$data['id_proveedor'],
                trim($data['nombre']),
                isset($data['apellido']) ? trim($data['apellido']) : null,
                isset($data['nombre_fantasia']) ? trim($data['nombre_fantasia']) : null,
                isset($data['telefono']) ? trim($data['telefono']) : null,
                isset($data['direccion']) ? trim($data['direccion']) : null,
                (bool)($data['activo'] ?? true)
            );

            $result
                ? $this->respond(200, ['message' => 'Proveedor actualizado correctamente.'])
                : $this->respond(500, ['message' => 'Error al actualizar el proveedor.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al actualizar proveedor');
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
                ? $this->respond(200, ['message' => 'Proveedor desactivado correctamente.'])
                : $this->respond(500, ['message' => 'Error al desactivar el proveedor.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al eliminar proveedor');
        }
    }
}
