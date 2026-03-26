<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseController.php';
require_once __DIR__ . '/../../models/torneos/TipoEventoPartido.php';

class TipoEventoPartidoController extends BaseController
{
    private TipoEventoPartido $model;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->model = new TipoEventoPartido($this->db);
    }

    public function getAll(): void
    {
        try {
            $this->respond(200, $this->model->getAll());
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener tipos de incidencia de partido');
        }
    }
}