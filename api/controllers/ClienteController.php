<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/Cliente.php';

class ClienteController extends BaseController
{
    private Cliente $model;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->model = new Cliente($this->db);
    }

    /**
     * Obtiene todos los clientes o por provincia
     */
    public function getAll(): void
    {
        try {
            $provincia = $_GET['provincia'] ?? null;
            
            if ($provincia) {
                $result = $this->model->getByProvincia((int)$provincia);
            } else {
                $result = $this->model->getAll();
            }
            
            $this->respond(200, $result);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener clientes');
        }
    }

    /**
     * Obtiene un cliente por ID
     */
    public function getById(): void
    {
        try {
            $id = $_GET['id'] ?? null;
            if (!$id) $this->respond(400, ['message' => 'ID requerido.']);

            $result = $this->model->getById((int)$id);
            $result ? $this->respond(200, $result) : $this->respond(404, ['message' => 'Cliente no encontrado.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener cliente');
        }
    }

    /**
     * Obtiene los equipos asociados a un cliente
     */
    public function getEquipos(): void
    {
        try {
            $id = $_GET['id'] ?? null;
            if (!$id) $this->respond(400, ['message' => 'ID de cliente requerido.']);

            $result = $this->model->getEquipos((int)$id);
            $this->respond(200, $result);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener equipos del cliente');
        }
    }

    /**
     * Obtiene los movimientos (ventas y cobros) de un cliente
     */
    public function getMovimientos(): void
    {
        try {
            $id = $_GET['id'] ?? null;
            if (!$id) $this->respond(400, ['message' => 'ID de cliente requerido.']);

            $result = $this->model->getMovimientos((int)$id);
            $this->respond(200, $result);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener movimientos del cliente');
        }
    }

    /**
     * Crea un nuevo cliente
     */
    public function store(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (empty($data['id']) || empty($data['nombre_cliente'])) {
                $this->respond(400, ['message' => 'ID y nombre de cliente requeridos.']);
            }

            $condicionIva = $data['condicion_iva'] ?? null;
            $idCondicionIvaReceptor = $data['id_condicion_iva_receptor'] ?? null;
            $direccion = $data['direccion'] ?? null;
            $idProvincia = $data['id_provinica'] ?? null;

            if ($this->model->create(
                (int)$data['id'],
                $data['nombre_cliente'],
                $condicionIva,
                $idCondicionIvaReceptor,
                $direccion,
                $idProvincia
            )) {
                $this->respond(201, ['message' => 'Cliente creado exitosamente.']);
            } else {
                $this->respond(500, ['message' => 'Error al crear cliente.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al crear cliente');
        }
    }

    /**
     * Actualiza un cliente existente
     */
    public function update(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (empty($data['id']) || empty($data['nombre_cliente'])) {
                $this->respond(400, ['message' => 'ID y nombre de cliente requeridos.']);
            }

            $condicionIva = $data['condicion_iva'] ?? null;
            $idCondicionIvaReceptor = $data['id_condicion_iva_receptor'] ?? null;
            $direccion = $data['direccion'] ?? null;
            $idProvincia = $data['id_provinica'] ?? null;

            if ($this->model->update(
                (int)$data['id'],
                $data['nombre_cliente'],
                $condicionIva,
                $idCondicionIvaReceptor,
                $direccion,
                $idProvincia
            )) {
                $this->respond(200, ['message' => 'Cliente actualizado exitosamente.']);
            } else {
                $this->respond(500, ['message' => 'Error al actualizar cliente.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al actualizar cliente');
        }
    }

    /**
     * Elimina un cliente
     */
    public function delete(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $id = $data['id'] ?? null;

            if (!$id) $this->respond(400, ['message' => 'ID requerido.']);

            if ($this->model->delete((int)$id)) {
                $this->respond(200, ['message' => 'Cliente eliminado exitosamente.']);
            } else {
                $this->respond(500, ['message' => 'Error al eliminar cliente.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al eliminar cliente');
        }
    }
}
