<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseController.php';
require_once __DIR__ . '/../../models/instalaciones/Evento.php';

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
            $this->db->beginTransaction();

            if (!$this->model->update((int)$id, $payload)) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                $this->respond(500, ['message' => 'Error al actualizar evento.']);
            }

            $idGanador = $this->resolveWinnerTeamId($payload);
            $asignacionesAplicadas = 0;
            if ($idGanador !== null) {
                $asignacionesAplicadas = $this->propagarGanadorCruce((int)$id, $idGanador);
            }

            $this->db->commit();

            $this->respond(200, [
                'message' => 'Evento actualizado exitosamente.',
                'ganador_propagado' => $asignacionesAplicadas,
            ]);

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->handleError($e, 'Error al actualizar evento');
        }
    }

    private function resolveWinnerTeamId(array $payload): ?int
    {
        $idLocal = $this->nullableInt($payload['id_equipo_local'] ?? null);
        $idVisitante = $this->nullableInt($payload['id_equipo_visitante'] ?? null);
        $golesLocal = $this->nullableInt($payload['resultado_local'] ?? null);
        $golesVisitante = $this->nullableInt($payload['resultado_visitante'] ?? null);

        if ($idLocal === null || $idVisitante === null || $golesLocal === null || $golesVisitante === null) {
            return null;
        }

        if ($golesLocal > $golesVisitante) {
            return $idLocal;
        }
        if ($golesVisitante > $golesLocal) {
            return $idVisitante;
        }

        $penalesLocal = $this->nullableInt($payload['resultado_penales_local'] ?? null);
        $penalesVisitante = $this->nullableInt($payload['resultado_penales_visitante'] ?? null);

        if ($penalesLocal === null || $penalesVisitante === null) {
            return null;
        }
        if ($penalesLocal > $penalesVisitante) {
            return $idLocal;
        }
        if ($penalesVisitante > $penalesLocal) {
            return $idVisitante;
        }

        return null;
    }

    private function propagarGanadorCruce(int $idEvento, int $idGanador): int
    {
        $sqlCruceActual = "SELECT c.id_fase_torneo, c.orden, ev.numero_fecha
                           FROM cruce_torneo c
                           INNER JOIN evento ev ON ev.id = c.id_evento
                           WHERE c.id_evento = :id_evento
                           LIMIT 1";
        $stmtCruceActual = $this->db->prepare($sqlCruceActual);
        $stmtCruceActual->bindValue(':id_evento', $idEvento, PDO::PARAM_INT);
        $stmtCruceActual->execute();
        $cruceActual = $stmtCruceActual->fetch(PDO::FETCH_ASSOC);

        // Si el evento no pertenece a un cruce de eliminación, no hay propagación.
        if (!$cruceActual) {
            return 0;
        }

        $idFaseTorneo = (int)$cruceActual['id_fase_torneo'];
        $ordenCruce = (int)$cruceActual['orden'];
        $numeroFechaActual = isset($cruceActual['numero_fecha']) ? (int)$cruceActual['numero_fecha'] : 0;
        if ($idFaseTorneo <= 0 || $ordenCruce <= 0 || $numeroFechaActual <= 0) {
            return 0;
        }

        $sqlMinFecha = "SELECT MIN(ev.numero_fecha) AS min_fecha
                        FROM cruce_torneo c
                        INNER JOIN evento ev ON ev.id = c.id_evento
                        WHERE c.id_fase_torneo = :id_fase_torneo";
        $stmtMinFecha = $this->db->prepare($sqlMinFecha);
        $stmtMinFecha->bindValue(':id_fase_torneo', $idFaseTorneo, PDO::PARAM_INT);
        $stmtMinFecha->execute();
        $minFecha = (int)($stmtMinFecha->fetchColumn() ?: 0);
        if ($minFecha <= 0) {
            return 0;
        }

        $ronda = ($numeroFechaActual - $minFecha) + 1;
        if ($ronda <= 0) {
            return 0;
        }

        $tokenGanador = 'R' . $ronda . '-P' . $ordenCruce;

        $sqlCrucesDestino = "SELECT c.id_evento,
                                    c.origen_local_tipo, c.origen_local_valor,
                                    c.origen_visitante_tipo, c.origen_visitante_valor
                             FROM cruce_torneo c
                             WHERE c.id_fase_torneo = :id_fase_torneo
                               AND (
                                    (c.origen_local_tipo = 'GANADOR_CRUCE' AND c.origen_local_valor = :token)
                                 OR (c.origen_visitante_tipo = 'GANADOR_CRUCE' AND c.origen_visitante_valor = :token)
                               )";
        $stmtCrucesDestino = $this->db->prepare($sqlCrucesDestino);
        $stmtCrucesDestino->bindValue(':id_fase_torneo', $idFaseTorneo, PDO::PARAM_INT);
        $stmtCrucesDestino->bindValue(':token', $tokenGanador);
        $stmtCrucesDestino->execute();
        $crucesDestino = $stmtCrucesDestino->fetchAll(PDO::FETCH_ASSOC);

        if (empty($crucesDestino)) {
            return 0;
        }

        $asignaciones = 0;
        foreach ($crucesDestino as $cruceDestino) {
            $idEventoDestino = (int)($cruceDestino['id_evento'] ?? 0);
            if ($idEventoDestino <= 0) {
                continue;
            }

            if (
                (string)($cruceDestino['origen_local_tipo'] ?? '') === 'GANADOR_CRUCE'
                && (string)($cruceDestino['origen_local_valor'] ?? '') === $tokenGanador
            ) {
                $stmtUpdateLocal = $this->db->prepare('UPDATE evento SET id_equipo_local = :id_equipo WHERE id = :id_evento');
                $stmtUpdateLocal->bindValue(':id_equipo', $idGanador, PDO::PARAM_INT);
                $stmtUpdateLocal->bindValue(':id_evento', $idEventoDestino, PDO::PARAM_INT);
                $stmtUpdateLocal->execute();
                $asignaciones += $stmtUpdateLocal->rowCount();
            }

            if (
                (string)($cruceDestino['origen_visitante_tipo'] ?? '') === 'GANADOR_CRUCE'
                && (string)($cruceDestino['origen_visitante_valor'] ?? '') === $tokenGanador
            ) {
                $stmtUpdateVisitante = $this->db->prepare('UPDATE evento SET id_equipo_visitante = :id_equipo WHERE id = :id_evento');
                $stmtUpdateVisitante->bindValue(':id_equipo', $idGanador, PDO::PARAM_INT);
                $stmtUpdateVisitante->bindValue(':id_evento', $idEventoDestino, PDO::PARAM_INT);
                $stmtUpdateVisitante->execute();
                $asignaciones += $stmtUpdateVisitante->rowCount();
            }
        }

        return $asignaciones;
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