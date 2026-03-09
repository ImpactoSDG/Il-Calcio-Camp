<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/ImpresoraTiquetera.php';

class ImpresoraTiqueteraController
{
    private ImpresoraTiquetera $model;

    public function __construct(PDO $db)
    {
        $this->model = new ImpresoraTiquetera($db);
    }

    private function respond(int $code, array $data): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data);
        exit;
    }

    public function getAll(): void
    {
        $this->respond(200, $this->model->getAll());
    }

    public function getById(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            $this->respond(400, ['message' => 'ID requerido.']);
        }

        $result = $this->model->getById($id);
        $result
            ? $this->respond(200, $result)
            : $this->respond(404, ['message' => 'Impresora no encontrada.']);
    }

    public function getDefault(): void
    {
        $result = $this->model->getDefault();
        $result
            ? $this->respond(200, $result)
            : $this->respond(404, ['message' => 'No hay impresora predeterminada configurada.']);
    }

    public function store(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['nombre']) || empty($data['comando_corte'])) {
            $this->respond(400, ['message' => 'Los campos nombre y comando_corte son requeridos.']);
        }

        $this->model->create($data)
            ? $this->respond(201, ['message' => 'Impresora registrada con éxito.'])
            : $this->respond(500, ['message' => 'Error al registrar la impresora.']);
    }

    public function update(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $id   = (int)($data['id'] ?? 0);

        if (!$id || empty($data['nombre']) || empty($data['comando_corte'])) {
            $this->respond(400, ['message' => 'Los campos id, nombre y comando_corte son requeridos.']);
        }

        $this->model->update($id, $data)
            ? $this->respond(200, ['message' => 'Impresora actualizada con éxito.'])
            : $this->respond(500, ['message' => 'Error al actualizar la impresora.']);
    }

    public function delete(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $id   = (int)($data['id'] ?? 0);

        if (!$id) {
            $this->respond(400, ['message' => 'ID requerido para eliminar.']);
        }

        $this->model->delete($id)
            ? $this->respond(200, ['message' => 'Impresora eliminada correctamente.'])
            : $this->respond(500, ['message' => 'Error al eliminar la impresora.']);
    }
}
