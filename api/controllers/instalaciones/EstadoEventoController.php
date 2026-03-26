<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseController.php';
require_once __DIR__ . '/../../models/instalaciones/EstadoEvento.php';

class EstadoEventoController extends BaseController
{
    private EstadoEvento $model;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->model = new EstadoEvento($this->db);
    }

    public function getAll(): void
    {
        try {
            $this->respond(200, $this->model->getAll());
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener estados de evento');
        }
    }

    public function getById(): void
    {
        try {
            $id = $_GET['id'] ?? null;
            if (!$id) {
                $this->respond(400, ['message' => 'ID requerido.']);
            }
            $result = $this->model->getById((int)$id);
            $result ? $this->respond(200, $result) : $this->respond(404, ['message' => 'Estado de evento no encontrado.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener estado de evento');
        }
    }
}