<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseController.php';
require_once __DIR__ . '/../../models/torneos/EstadoTorneo.php';

class EstadoTorneoController extends BaseController
{
    private EstadoTorneo $model;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->model = new EstadoTorneo($this->db);
    }

    public function getAll(): void
    {
        try {
            $this->respond(200, $this->model->getAll());
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener estados de torneo');
        }
    }
}
