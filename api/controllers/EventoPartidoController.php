<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/EventoPartido.php';

class EventoPartidoController extends BaseController
{
    private EventoPartido $model;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->model = new EventoPartido($this->db);
    }

    public function getAll(): void
    {
        try {
            $idEvento = isset($_GET['id_evento']) && $_GET['id_evento'] !== ''
                ? (int)$_GET['id_evento']
                : null;

            $this->respond(200, $this->model->getAll($idEvento));
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener incidencias del partido');
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
            $result ? $this->respond(200, $result) : $this->respond(404, ['message' => 'Incidencia no encontrada.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener incidencia del partido');
        }
    }

    public function store(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $payload = $this->validateAndNormalize($data);
            $id = $this->model->create($payload);

            if ($id !== false) {
                $this->respond(201, ['message' => 'Incidencia creada exitosamente.', 'id' => $id]);
            }

            $this->respond(500, ['message' => 'Error al crear incidencia.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al crear incidencia del partido');
        }
    }

    public function update(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? null;

            if (!$id) {
                $this->respond(400, ['message' => 'ID requerido.']);
            }

            $payload = $this->validateAndNormalize($data);
            if ($this->model->update((int)$id, $payload)) {
                $this->respond(200, ['message' => 'Incidencia actualizada exitosamente.']);
            }

            $this->respond(500, ['message' => 'Error al actualizar incidencia.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al actualizar incidencia del partido');
        }
    }

    public function delete(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? null;
            if (!$id) {
                $this->respond(400, ['message' => 'ID requerido.']);
            }

            if ($this->model->delete((int)$id)) {
                $this->respond(200, ['message' => 'Incidencia eliminada exitosamente.']);
            }

            $this->respond(500, ['message' => 'Error al eliminar incidencia.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al eliminar incidencia del partido');
        }
    }

    private function validateAndNormalize(?array $data): array
    {
        if (!is_array($data)) {
            $this->respond(400, ['message' => 'Datos inválidos.']);
        }

        $idEvento = $this->nullableInt($data['id_evento'] ?? null);
        $idTipoEventoPartido = $this->nullableInt($data['id_tipo_evento_partido'] ?? null);

        if (!$idEvento || !$idTipoEventoPartido) {
            $this->respond(400, ['message' => 'id_evento e id_tipo_evento_partido son requeridos.']);
        }

        return [
            'id_evento' => $idEvento,
            'id_tipo_evento_partido' => $idTipoEventoPartido,
            'id_jugador' => $this->nullableInt($data['id_jugador'] ?? null),
            'id_equipo' => $this->nullableInt($data['id_equipo'] ?? null),
            'minuto' => $this->nullableInt($data['minuto'] ?? null),
            'observacion' => isset($data['observacion']) && trim((string)$data['observacion']) !== ''
                ? trim((string)$data['observacion'])
                : null,
        ];
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (int)$value;
    }
}