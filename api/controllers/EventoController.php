<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/Evento.php';

class EventoController extends BaseController
{
    private Evento $model;
    private array $tiposPermitidos = ['partido', 'festejo', 'reunion', 'otro'];

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->model = new Evento($this->db);
    }

    public function getAll(): void
    {
        try {
            $tipo = $_GET['tipo_evento'] ?? null;

            if ($tipo) {
                $this->respond(200, $this->model->getByTipo((string)$tipo));
            }

            $this->respond(200, $this->model->getAll());
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener eventos');
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
            $result ? $this->respond(200, $result) : $this->respond(404, ['message' => 'Evento no encontrado.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener evento');
        }
    }

    public function store(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $payload = $this->validateAndNormalize($data, false);
            $nuevoId = $this->model->create($payload);

            if ($nuevoId !== false) {
                $this->respond(201, ['message' => 'Evento creado exitosamente.', 'id' => $nuevoId]);
            }

            $this->respond(500, ['message' => 'Error al crear evento.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al crear evento');
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

            $payload = $this->validateAndNormalize($data, true);
            if ($this->model->update((int)$id, $payload)) {
                $this->respond(200, ['message' => 'Evento actualizado exitosamente.']);
            }

            $this->respond(500, ['message' => 'Error al actualizar evento.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al actualizar evento');
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
                $this->respond(200, ['message' => 'Evento eliminado exitosamente.']);
            }

            $this->respond(500, ['message' => 'Error al eliminar evento.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al eliminar evento');
        }
    }

    private function validateAndNormalize(?array $data, bool $isUpdate): array
    {
        if (!is_array($data)) {
            $this->respond(400, ['message' => 'Datos inválidos.']);
        }

        $idEstadoEvento = $this->nullableInt($data['id_estado_evento'] ?? null);
        $tipoEvento = isset($data['tipo_evento']) ? trim((string)$data['tipo_evento']) : '';
        $titulo = isset($data['titulo']) ? trim((string)$data['titulo']) : '';
        $fechaHoraInicio = isset($data['fecha_hora_inicio']) ? trim((string)$data['fecha_hora_inicio']) : '';
        $fechaHoraFin = isset($data['fecha_hora_fin']) ? trim((string)$data['fecha_hora_fin']) : '';

        if (!$idEstadoEvento || $tipoEvento === '' || $titulo === '' || $fechaHoraInicio === '') {
            $this->respond(400, ['message' => 'Estado, tipo de evento, titulo y fecha/hora de inicio son requeridos.']);
        }

        if (!in_array($tipoEvento, $this->tiposPermitidos, true)) {
            $this->respond(400, ['message' => 'Tipo de evento inválido.']);
        }

        $inicio = $this->normalizeDateTime($fechaHoraInicio);
        $fin = $fechaHoraFin !== '' ? $this->normalizeDateTime($fechaHoraFin) : null;

        if ($fin !== null && strtotime($fin) < strtotime($inicio)) {
            $this->respond(400, ['message' => 'La fecha/hora de fin no puede ser anterior al inicio.']);
        }

        $idEquipoLocal = $this->nullableInt($data['id_equipo_local'] ?? null);
        $idEquipoVisitante = $this->nullableInt($data['id_equipo_visitante'] ?? null);
        if ($idEquipoLocal !== null && $idEquipoVisitante !== null && $idEquipoLocal === $idEquipoVisitante) {
            $this->respond(400, ['message' => 'El equipo local y visitante no pueden ser el mismo.']);
        }

        return [
            'id_torneo' => $this->nullableInt($data['id_torneo'] ?? null),
            'id_estado_evento' => $idEstadoEvento,
            'tipo_evento' => $tipoEvento,
            'titulo' => $titulo,
            'descripcion' => isset($data['descripcion']) && trim((string)$data['descripcion']) !== '' ? trim((string)$data['descripcion']) : null,
            'numero_fecha' => $this->nullableInt($data['numero_fecha'] ?? null),
            'fecha_hora_inicio' => $inicio,
            'fecha_hora_fin' => $fin,
            'id_cancha' => $this->nullableInt($data['id_cancha'] ?? null),
            'id_arbitro' => $this->nullableInt($data['id_arbitro'] ?? null),
            'id_equipo_local' => $idEquipoLocal,
            'id_equipo_visitante' => $idEquipoVisitante,
            'resultado_local' => $this->nullableInt($data['resultado_local'] ?? null),
            'resultado_visitante' => $this->nullableInt($data['resultado_visitante'] ?? null),
            'resultado_penales_local' => $this->nullableInt($data['resultado_penales_local'] ?? null),
            'resultado_penales_visitante' => $this->nullableInt($data['resultado_penales_visitante'] ?? null),
        ];
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (int)$value;
    }

    private function normalizeDateTime(string $value): string
    {
        return str_replace('T', ' ', $value) . (strlen($value) === 16 ? ':00' : '');
    }
}