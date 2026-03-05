<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/ClienteEquipo.php';

class ClienteEquipoController extends BaseController
{
    private ClienteEquipo $model;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->model = new ClienteEquipo($this->db);
    }

    /**
     * Obtiene todas las relaciones cliente-equipo, por cliente o por equipo
     */
    public function getAll(): void
    {
        try {
            $cliente = $_GET['cliente'] ?? null;
            $equipo = $_GET['equipo'] ?? null;
            
            if ($cliente) {
                $result = $this->model->getByCliente((int)$cliente);
            } elseif ($equipo) {
                $result = $this->model->getByEquipo((int)$equipo);
            } else {
                $result = $this->model->getAll();
            }
            
            $this->respond(200, $result);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener relaciones cliente-equipo');
        }
    }

    /**
     * Obtiene una relación cliente-equipo por ID
     */
    public function getById(): void
    {
        try {
            $id = $_GET['id'] ?? null;
            if (!$id) $this->respond(400, ['message' => 'ID requerido.']);

            $result = $this->model->getById((int)$id);
            $result ? $this->respond(200, $result) : $this->respond(404, ['message' => 'Relación cliente-equipo no encontrada.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener relación cliente-equipo');
        }
    }

    /**
     * Crea una nueva relación cliente-equipo
     */
    public function store(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (empty($data['id_cliente_equipo']) || empty($data['id_cliente']) || empty($data['id_equipo'])) {
                $this->respond(400, ['message' => 'ID, id_cliente e id_equipo requeridos.']);
            }

            if ($this->model->create(
                (int)$data['id_cliente_equipo'],
                (int)$data['id_cliente'],
                (int)$data['id_equipo']
            )) {
                $this->respond(201, ['message' => 'Relación cliente-equipo creada exitosamente.']);
            } else {
                $this->respond(500, ['message' => 'Error al crear relación cliente-equipo.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al crear relación cliente-equipo');
        }
    }

    /**
     * Elimina una relación cliente-equipo
     */
    public function delete(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $id = $data['id_cliente_equipo'] ?? null;

            if (!$id) $this->respond(400, ['message' => 'ID requerido.']);

            if ($this->model->delete((int)$id)) {
                $this->respond(200, ['message' => 'Relación cliente-equipo eliminada exitosamente.']);
            } else {
                $this->respond(500, ['message' => 'Error al eliminar relación cliente-equipo.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al eliminar relación cliente-equipo');
        }
    }
}
