<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseController.php';
require_once __DIR__ . '/../../models/torneos/EventoPartido.php';

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
            $idTorneo = isset($_GET['id_torneo']) && $_GET['id_torneo'] !== ''
                ? (int)$_GET['id_torneo']
                : null;

            $this->respond(200, $this->model->getAll($idEvento, $idTorneo));
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

    public function reportarResultado(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $payload = $this->validateAndNormalizeReporte($data);
            $evento = $this->getEventoForReport($payload['id_evento']);

            if (!$evento) {
                $this->respond(404, ['message' => 'Partido no encontrado.']);
            }

            if ((string)($evento['tipo_evento'] ?? '') !== 'partido') {
                $this->respond(400, ['message' => 'Solo se pueden reportar resultados de eventos tipo partido.']);
            }

            $this->validateEquiposIncidencias($payload['incidencias_crear'], $evento);
            $this->validateGolesVsMarcador($payload, $evento);

            $this->db->beginTransaction();

            $this->updateResultadoEvento($payload);

            foreach ($payload['incidencias_eliminar_ids'] as $idIncidencia) {
                $this->deleteIncidenciaEvento($idIncidencia, $payload['id_evento']);
            }

            foreach ($payload['incidencias_crear'] as $incidencia) {
                $this->model->create(array_merge($incidencia, [
                    'id_evento' => $payload['id_evento'],
                ]));
            }

            $idGanador = $this->resolveWinnerTeamId($evento, $payload);
            $asignacionesAplicadas = 0;
            if ($idGanador !== null) {
                $asignacionesAplicadas = $this->propagarGanadorCruce($payload['id_evento'], $idGanador);
            }

            $this->db->commit();

            $this->respond(200, [
                'message' => 'Resultado reportado exitosamente.',
                'incidencias_creadas' => count($payload['incidencias_crear']),
                'incidencias_eliminadas' => count($payload['incidencias_eliminar_ids']),
                'ganador_propagado' => $asignacionesAplicadas,
            ]);
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->handleError($e, 'Error al reportar resultado del partido');
        }
    }

    private function validateAndNormalizeReporte(?array $data): array
    {
        if (!is_array($data)) {
            $this->respond(400, ['message' => 'Datos invalidos.']);
        }

        $idEvento = $this->nullableInt($data['id_evento'] ?? null);
        $idEstadoEvento = $this->nullableInt($data['id_estado_evento'] ?? null);
        $resultadoLocal = $this->nullableInt($data['resultado_local'] ?? null);
        $resultadoVisitante = $this->nullableInt($data['resultado_visitante'] ?? null);
        $penalesLocal = $this->nullableInt($data['resultado_penales_local'] ?? null);
        $penalesVisitante = $this->nullableInt($data['resultado_penales_visitante'] ?? null);

        if (!$idEvento || !$idEstadoEvento || $resultadoLocal === null || $resultadoVisitante === null) {
            $this->respond(400, ['message' => 'id_evento, estado y resultado local/visitante son requeridos.']);
        }

        foreach ([$resultadoLocal, $resultadoVisitante, $penalesLocal, $penalesVisitante] as $valor) {
            if ($valor !== null && $valor < 0) {
                $this->respond(400, ['message' => 'Los resultados no pueden ser negativos.']);
            }
        }

        $incidenciasCrear = $data['incidencias_crear'] ?? [];
        if (!is_array($incidenciasCrear)) {
            $this->respond(400, ['message' => 'incidencias_crear debe ser una lista.']);
        }

        $incidenciasNormalizadas = array_map(function ($item) {
            if (!is_array($item)) {
                $this->respond(400, ['message' => 'Cada incidencia debe ser un objeto.']);
            }

            $idTipo = $this->nullableInt($item['id_tipo_evento_partido'] ?? null);
            $idEquipo = $this->nullableInt($item['id_equipo'] ?? null);
            $minuto = $this->nullableInt($item['minuto'] ?? null);

            if (!$idTipo || !$idEquipo) {
                $this->respond(400, ['message' => 'Cada incidencia debe tener tipo y equipo.']);
            }
            if ($minuto !== null && $minuto < 0) {
                $this->respond(400, ['message' => 'El minuto de incidencia no puede ser negativo.']);
            }

            return [
                'id_tipo_evento_partido' => $idTipo,
                'id_jugador' => $this->nullableInt($item['id_jugador'] ?? null),
                'id_equipo' => $idEquipo,
                'minuto' => $minuto,
                'observacion' => isset($item['observacion']) && trim((string)$item['observacion']) !== ''
                    ? trim((string)$item['observacion'])
                    : null,
            ];
        }, $incidenciasCrear);

        $idsEliminar = $data['incidencias_eliminar_ids'] ?? [];
        if (!is_array($idsEliminar)) {
            $this->respond(400, ['message' => 'incidencias_eliminar_ids debe ser una lista.']);
        }

        $idsEliminar = array_values(array_unique(array_filter(array_map(
            fn ($id) => $this->nullableInt($id),
            $idsEliminar
        ), fn ($id) => $id !== null && $id > 0)));

        return [
            'id_evento' => $idEvento,
            'id_estado_evento' => $idEstadoEvento,
            'resultado_local' => $resultadoLocal,
            'resultado_visitante' => $resultadoVisitante,
            'resultado_penales_local' => $penalesLocal,
            'resultado_penales_visitante' => $penalesVisitante,
            'incidencias_crear' => $incidenciasNormalizadas,
            'incidencias_eliminar_ids' => $idsEliminar,
        ];
    }

    private function getEventoForReport(int $idEvento): ?array
    {
        $sql = "SELECT id, tipo_evento, id_equipo_local, id_equipo_visitante
                FROM evento
                WHERE id = :id
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $idEvento, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    private function validateEquiposIncidencias(array $incidencias, array $evento): void
    {
        $idLocal = (int)($evento['id_equipo_local'] ?? 0);
        $idVisitante = (int)($evento['id_equipo_visitante'] ?? 0);

        if ($idLocal <= 0 || $idVisitante <= 0) {
            $this->respond(400, ['message' => 'El partido debe tener equipo local y visitante para reportar resultado.']);
        }

        foreach ($incidencias as $incidencia) {
            $idEquipo = (int)($incidencia['id_equipo'] ?? 0);
            if ($idEquipo !== $idLocal && $idEquipo !== $idVisitante) {
                $this->respond(400, ['message' => 'Las incidencias solo pueden pertenecer al equipo local o visitante del partido.']);
            }
        }
    }

    private function validateGolesVsMarcador(array $payload, array $evento): void
    {
        $idLocal = (int)($evento['id_equipo_local'] ?? 0);
        $idVisitante = (int)($evento['id_equipo_visitante'] ?? 0);
        $eliminadas = array_flip($payload['incidencias_eliminar_ids']);
        $golesLocal = 0;
        $golesVisitante = 0;

        $sql = "SELECT ep.id, ep.id_equipo, tep.descripcion
                FROM evento_partido ep
                INNER JOIN tipo_evento_partido tep ON ep.id_tipo_evento_partido = tep.id
                WHERE ep.id_evento = :id_evento";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_evento', $payload['id_evento'], PDO::PARAM_INT);
        $stmt->execute();

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $incidencia) {
            if (isset($eliminadas[(int)$incidencia['id']])) {
                continue;
            }
            if (!$this->isTipoGolDescripcion((string)($incidencia['descripcion'] ?? ''))) {
                continue;
            }
            if ((int)$incidencia['id_equipo'] === $idLocal) {
                $golesLocal++;
            } elseif ((int)$incidencia['id_equipo'] === $idVisitante) {
                $golesVisitante++;
            }
        }

        foreach ($payload['incidencias_crear'] as $incidencia) {
            if (!$this->isTipoGolId((int)$incidencia['id_tipo_evento_partido'])) {
                continue;
            }
            if ((int)$incidencia['id_equipo'] === $idLocal) {
                $golesLocal++;
            } elseif ((int)$incidencia['id_equipo'] === $idVisitante) {
                $golesVisitante++;
            }
        }

        if ($golesLocal !== $payload['resultado_local'] || $golesVisitante !== $payload['resultado_visitante']) {
            $this->respond(400, [
                'message' => sprintf(
                    'El marcador no coincide con las incidencias de gol. Marcador: %d-%d | Goles cargados: %d-%d.',
                    $payload['resultado_local'],
                    $payload['resultado_visitante'],
                    $golesLocal,
                    $golesVisitante
                ),
            ]);
        }
    }

    private function updateResultadoEvento(array $payload): void
    {
        $sql = "UPDATE evento
                SET id_estado_evento = :id_estado_evento,
                    resultado_local = :resultado_local,
                    resultado_visitante = :resultado_visitante,
                    resultado_penales_local = :resultado_penales_local,
                    resultado_penales_visitante = :resultado_penales_visitante
                WHERE id = :id_evento";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_estado_evento', $payload['id_estado_evento'], PDO::PARAM_INT);
        $stmt->bindValue(':resultado_local', $payload['resultado_local'], PDO::PARAM_INT);
        $stmt->bindValue(':resultado_visitante', $payload['resultado_visitante'], PDO::PARAM_INT);
        $stmt->bindValue(':resultado_penales_local', $payload['resultado_penales_local'], $payload['resultado_penales_local'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':resultado_penales_visitante', $payload['resultado_penales_visitante'], $payload['resultado_penales_visitante'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':id_evento', $payload['id_evento'], PDO::PARAM_INT);
        $stmt->execute();
    }

    private function deleteIncidenciaEvento(int $idIncidencia, int $idEvento): void
    {
        $stmt = $this->db->prepare('DELETE FROM evento_partido WHERE id = :id AND id_evento = :id_evento');
        $stmt->bindValue(':id', $idIncidencia, PDO::PARAM_INT);
        $stmt->bindValue(':id_evento', $idEvento, PDO::PARAM_INT);
        $stmt->execute();
    }

    private function isTipoGolId(int $idTipoEventoPartido): bool
    {
        $stmt = $this->db->prepare('SELECT descripcion FROM tipo_evento_partido WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $idTipoEventoPartido, PDO::PARAM_INT);
        $stmt->execute();
        return $this->isTipoGolDescripcion((string)($stmt->fetchColumn() ?: ''));
    }

    private function isTipoGolDescripcion(string $descripcion): bool
    {
        return stripos($descripcion, 'gol') !== false;
    }

    private function resolveWinnerTeamId(array $evento, array $payload): ?int
    {
        $idLocal = $this->nullableInt($evento['id_equipo_local'] ?? null);
        $idVisitante = $this->nullableInt($evento['id_equipo_visitante'] ?? null);
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
