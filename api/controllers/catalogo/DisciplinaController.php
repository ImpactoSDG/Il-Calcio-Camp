<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseController.php';
require_once __DIR__ . '/../../models/catalogo/Disciplina.php';

class DisciplinaController extends BaseController
{
    private Disciplina $model;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->model = new Disciplina($this->db);
    }

    public function getAll(): void
    {
        try {
            $this->respond(200, $this->model->getAll());
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener disciplinas');
        }
    }
}
