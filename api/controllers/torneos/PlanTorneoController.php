<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseController.php';

class PlanTorneoController extends BaseController
{
    private const ESTADO_EVENTO_PROGRAMACION_PENDIENTE_ID = 1;
    private const ESTADO_EVENTO_PROGRAMADO_ID = 2;
    private const ESTADO_TORNEO_EN_CURSO_ID = 4;
    private ?array $eventoPagoColumns = null;

    public function simular(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!is_array($data)) {
                $this->respond(400, ['message' => 'Datos inválidos para planificar torneo.']);
            }

            $payload = $this->normalizePayload($data);
            $resultado = $this->buildSimulation($payload);

            $this->respond(200, $resultado);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al simular planificación del torneo');
        }
    }

    public function confirmar(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!is_array($data)) {
                $this->respond(400, ['message' => 'Datos inválidos para confirmar planificación.']);
            }

            $parametrosRaw = $data['parametros'] ?? $data;
            if (!is_array($parametrosRaw)) {
                $this->respond(400, ['message' => 'parametros debe ser un objeto válido.']);
            }

            $payload = $this->normalizePayload($parametrosRaw);
            $resultado = $this->buildSimulation($payload);

            $idTorneo = $this->nullableInt($data['id_torneo'] ?? null);
            $torneoNuevo = $data['torneo_nuevo'] ?? null;

            if ($idTorneo === null && !is_array($torneoNuevo)) {
                $this->respond(400, ['message' => 'Debes enviar id_torneo existente o torneo_nuevo para crear uno.']);
            }

            if ($idTorneo !== null) {
                if (!$this->torneoExiste($idTorneo)) {
                    $this->respond(404, ['message' => 'El torneo indicado no existe.']);
                }

                if ($this->existeConfirmadaVigente($idTorneo)) {
                    $this->respond(409, ['message' => 'El torneo ya tiene una planificación confirmada vigente.']);
                }
            }

            $motor = trim((string)($data['motor_generacion'] ?? 'php'));
            $version = trim((string)($data['version_algoritmo'] ?? 'v1.0.0'));
            $observacion = isset($data['observacion']) ? trim((string)$data['observacion']) : null;
            if ($observacion === '') {
                $observacion = null;
            }

            $this->db->beginTransaction();

            if ($idTorneo === null && is_array($torneoNuevo)) {
                $idTorneo = $this->crearTorneoDesdePlanificacion($torneoNuevo, $payload);
            }

            $sql = "INSERT INTO generacion_fixture (
                        id_torneo,
                        fecha_generacion,
                        motor_generacion,
                        version_algoritmo,
                        parametros_json,
                        resultado_json,
                        estado,
                        observacion
                    ) VALUES (
                        :id_torneo,
                        NOW(),
                        :motor_generacion,
                        :version_algoritmo,
                        :parametros_json,
                        :resultado_json,
                        'CONFIRMADA',
                        :observacion
                    )";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
            $stmt->bindValue(':motor_generacion', $motor);
            $stmt->bindValue(':version_algoritmo', $version);
            $stmt->bindValue(':parametros_json', json_encode($payload));
            $stmt->bindValue(':resultado_json', json_encode($resultado));
            $stmt->bindValue(':observacion', $observacion);
            $stmt->execute();

            $idGeneracion = (int)$this->db->lastInsertId();

            $estadoEventoPendienteProgramacionId = $this->getEstadoEventoProgramacionPendienteId();
            $idsFases = [];
            $idsGrupos = [];
            $idsCruces = [];
            $idsEventos = [];

            $idFaseGrupos = null;
            $maxFechaZonas = 0;
            if ($payload['usa_zonas'] && !empty($resultado['zonas'])) {
                $esLigaFase = $payload['formato'] === 'LIGA';
                $idFaseGrupos = $this->insertFase(
                    $idTorneo,
                    $esLigaFase ? 'Liga' : 'Fase de grupos',
                    $esLigaFase ? 'LIGA' : 'FASE_DE_GRUPOS',
                    1,
                    [
                        'cantidad_zonas' => count($resultado['zonas']),
                        'clasificados_por_zona' => $payload['clasificados_por_zona'],
                        'ida_vuelta' => $payload['ida_vuelta_zonas'],
                    ]
                );
                $idsFases[] = $idFaseGrupos;

                $esLiga = $payload['formato'] === 'LIGA';
                foreach ($resultado['zonas'] as $index => $zona) {
                    $idGrupo = $this->insertGrupo(
                        $idFaseGrupos,
                        $esLiga ? 'Liga' : 'Zona ' . (string)$zona['zona'],
                        $index + 1,
                        (int)$zona['equipos'],
                        'MANUAL_POST_CONFIRMACION'
                    );
                    $idsGrupos[] = $idGrupo;

                    [$idsEventosZona, $maxFechaZona] = $this->insertEventosZona(
                        $idTorneo,
                        $estadoEventoPendienteProgramacionId,
                        (string)$zona['zona'],
                        (int)$zona['equipos'],
                        (bool)$payload['ida_vuelta_zonas'],
                        $esLiga
                    );

                    $idsEventos = array_merge($idsEventos, $idsEventosZona);
                    $maxFechaZonas = max($maxFechaZonas, $maxFechaZona);
                }
            }

            // LIGA: sin fase eliminatoria ni cruces
            if ($payload['formato'] === 'LIGA') {
                $this->db->commit();
                $this->respond(200, [
                    'message' => 'Planificación de liga confirmada exitosamente.',
                    'id_generacion' => $idGeneracion,
                    'id_torneo' => $idTorneo,
                    'fases' => $idsFases,
                    'eventos' => $idsEventos,
                ]);
            }

            $ordenFaseElim = $payload['usa_zonas'] ? 2 : 1;
            $idFaseEliminacion = $this->insertFase(
                $idTorneo,
                $payload['formato'] === 'GRUPOS_CON_CONSUELO' ? 'Zona Ganadores' : 'Fase eliminatoria',
                'ELIMINACION_DIRECTA',
                $ordenFaseElim,
                [
                    'llave_equipos' => $resultado['llave']['equipos'] ?? null,
                    'tercer_puesto' => $payload['tercer_puesto'],
                ]
            );
            $idsFases[] = $idFaseEliminacion;

            $rondas = $resultado['llave']['rondas'] ?? [];
            foreach ($rondas as $indiceRonda => $ronda) {
                $numeroFecha = $maxFechaZonas + $indiceRonda + 1;
                $offsetDias = $indiceRonda * 7;

                foreach (($ronda['partidos'] ?? []) as $indicePartido => $partido) {
                    $tituloEvento = ($ronda['nombre'] ?? 'Cruce') . ' - Partido ' . ($indicePartido + 1);
                    $fechaHoraInicio = date('Y-m-d H:i:s', strtotime('+' . $offsetDias . ' days 20:00:00'));

                    $idEvento = $this->insertEventoPlanificado(
                        $idTorneo,
                        $estadoEventoPendienteProgramacionId,
                        $tituloEvento,
                        'Evento generado automaticamente desde planificación confirmada.',
                        $numeroFecha,
                        $fechaHoraInicio
                    );
                    $idsEventos[] = $idEvento;

                    $origenLocal = $this->parseOrigen((string)($partido['local'] ?? 'TBD'));
                    $origenVisitante = $this->parseOrigen((string)($partido['visitante'] ?? 'TBD'));

                    $idCruce = $this->insertCruce(
                        $idFaseEliminacion,
                        $idEvento,
                        ($ronda['nombre'] ?? 'Cruce') . ' P' . ($indicePartido + 1),
                        $indicePartido + 1,
                        $origenLocal,
                        $origenVisitante
                    );
                    $idsCruces[] = $idCruce;
                }
            }

            // Fase Rueda Consuelo (solo para formato GRUPOS_CON_CONSUELO)
            if ($payload['formato'] === 'GRUPOS_CON_CONSUELO' && !empty($resultado['consuelo'])) {
                $idFaseConsuelo = $this->insertFase(
                    $idTorneo,
                    'Rueda Consuelo',
                    'RUEDA_CONSUELO',
                    $ordenFaseElim + 1,
                    [
                        'llave_equipos' => $resultado['consuelo']['equipos'] ?? null,
                        'clasificados_consuelo' => $payload['clasificados_consuelo'],
                    ]
                );
                $idsFases[] = $idFaseConsuelo;

                $rondasConsuelo = $resultado['consuelo']['rondas'] ?? [];
                foreach ($rondasConsuelo as $indiceRonda => $ronda) {
                    $numeroFecha = $maxFechaZonas + $indiceRonda + 1;
                    $offsetDias = $indiceRonda * 7;

                    foreach (($ronda['partidos'] ?? []) as $indicePartido => $partido) {
                        $tituloEvento = 'Consuelo - ' . ($ronda['nombre'] ?? 'Cruce') . ' - Partido ' . ($indicePartido + 1);
                        $fechaHoraInicio = date('Y-m-d H:i:s', strtotime('+' . $offsetDias . ' days 18:00:00'));

                        $idEvento = $this->insertEventoPlanificado(
                            $idTorneo,
                            $estadoEventoPendienteProgramacionId,
                            $tituloEvento,
                            'Partido de Rueda Consuelo.',
                            $numeroFecha,
                            $fechaHoraInicio
                        );
                        $idsEventos[] = $idEvento;

                        $origenLocal = $this->parseOrigen((string)($partido['local'] ?? 'TBD'));
                        $origenVisitante = $this->parseOrigen((string)($partido['visitante'] ?? 'TBD'));

                        $idCruce = $this->insertCruce(
                            $idFaseConsuelo,
                            $idEvento,
                            'Consuelo - ' . ($ronda['nombre'] ?? 'Cruce') . ' P' . ($indicePartido + 1),
                            $indicePartido + 1,
                            $origenLocal,
                            $origenVisitante
                        );
                        $idsCruces[] = $idCruce;
                    }
                }
            }

            $this->db->commit();

            $this->respond(201, [
                'message' => 'Planificación confirmada correctamente.',
                'id_generacion_fixture' => $idGeneracion,
                'id_torneo' => $idTorneo,
                'estado' => 'CONFIRMADA',
                'ids_fases' => $idsFases,
                'ids_grupos' => $idsGrupos,
                'ids_cruces' => $idsCruces,
                'ids_eventos' => $idsEventos,
            ]);
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->handleError($e, 'Error al confirmar planificación del torneo');
        }
    }

    public function getConfirmadaVigente(): void
    {
        try {
            $idTorneo = $this->nullableInt($_GET['id_torneo'] ?? null);
            if ($idTorneo === null) {
                $this->respond(400, ['message' => 'id_torneo es obligatorio.']);
            }

            $sql = "SELECT id, id_torneo, fecha_generacion, motor_generacion, version_algoritmo,
                           parametros_json, resultado_json, estado, observacion
                    FROM generacion_fixture
                    WHERE id_torneo = :id_torneo
                      AND estado = 'CONFIRMADA'
                    ORDER BY id DESC
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $this->respond(404, ['message' => 'No existe una planificación confirmada para ese torneo.']);
            }

            $this->respond(200, [
                'id' => (int)$row['id'],
                'id_torneo' => (int)$row['id_torneo'],
                'fecha_generacion' => $row['fecha_generacion'],
                'motor_generacion' => $row['motor_generacion'],
                'version_algoritmo' => $row['version_algoritmo'],
                'estado' => $row['estado'],
                'observacion' => $row['observacion'],
                'parametros' => json_decode((string)$row['parametros_json'], true),
                'resultado' => json_decode((string)$row['resultado_json'], true),
            ]);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener planificación confirmada');
        }
    }

    public function getDetalleGestion(): void
    {
        try {
            $idTorneo = $this->nullableInt($_GET['id_torneo'] ?? null);
            if ($idTorneo === null) {
                $this->respond(400, ['message' => 'id_torneo es obligatorio.']);
            }

            $this->actualizarTorneoEnCursoSiCorresponde($idTorneo);

            $sqlTorneo = "SELECT t.id, t.nombre, t.descripcion, t.id_disciplina, t.id_estado_torneo,
                                 t.fecha_inicio, t.fecha_fin, t.cupo_equipos, t.valor_inscripcion,
                                 t.formato_manual, d.nombre AS disciplina_nombre, et.descripcion AS estado_nombre
                          FROM torneo t
                          LEFT JOIN disciplina d ON d.id = t.id_disciplina
                          LEFT JOIN estado_torneo et ON et.id = t.id_estado_torneo
                          WHERE t.id = :id_torneo
                                                        AND COALESCE(t.activo, 1) = 1
                          LIMIT 1";
            $stmt = $this->db->prepare($sqlTorneo);
            $stmt->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
            $stmt->execute();
            $torneo = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$torneo) {
                $this->respond(404, ['message' => 'Torneo no encontrado.']);
            }

            $confirmada = $this->getConfirmadaData($idTorneo);

            $sqlGrupos = "SELECT g.id, g.nombre, g.orden, g.cantidad_equipos_objetivo,
                                  COUNT(gte.id) AS asignados
                           FROM grupo_torneo g
                           INNER JOIN fase_torneo f ON f.id = g.id_fase_torneo
                           LEFT JOIN grupo_torneo_equipo gte ON gte.id_grupo_torneo = g.id
                           WHERE f.id_torneo = :id_torneo
                             AND UPPER(f.tipo_fase) IN ('FASE_DE_GRUPOS', 'LIGA')
                           GROUP BY g.id, g.nombre, g.orden, g.cantidad_equipos_objetivo
                           ORDER BY g.orden ASC, g.id ASC";
            $stmt = $this->db->prepare($sqlGrupos);
            $stmt->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
            $stmt->execute();
            $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $sqlAsignaciones = "SELECT g.id AS id_grupo_torneo, g.nombre AS grupo_nombre,
                                       gte.posicion_inicial,
                                       et.id_equipo, e.nombre AS equipo_nombre, e.escudo
                                FROM grupo_torneo_equipo gte
                                INNER JOIN grupo_torneo g ON g.id = gte.id_grupo_torneo
                                INNER JOIN fase_torneo f ON f.id = g.id_fase_torneo
                                INNER JOIN equipo_torneo et ON et.id = gte.id_equipo_torneo
                                INNER JOIN equipo e ON e.id = et.id_equipo
                                WHERE f.id_torneo = :id_torneo
                                  AND UPPER(f.tipo_fase) IN ('FASE_DE_GRUPOS', 'LIGA')
                                ORDER BY g.orden ASC, gte.posicion_inicial ASC";
            $stmt = $this->db->prepare($sqlAsignaciones);
            $stmt->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
            $stmt->execute();
            $asignaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        $sqlInscriptos = "SELECT et.id, et.id_equipo, e.nombre AS equipo_nombre, e.escudo,
                                                                         et.id_estado_inscripcion, ei.descripcion AS estado_inscripcion,
                                                                         et.fecha_inscripcion,
                                     (SELECT ie.id FROM inscripcion_equipo ie
                                       WHERE ie.id_torneo = et.id_torneo AND ie.id_estado = 8
                                         AND UPPER(TRIM(ie.nombre_equipo)) = UPPER(TRIM(e.nombre))
                                       ORDER BY ie.fecha_actualizacion_estado DESC LIMIT 1) AS id_inscripcion_equipo,
                                     (SELECT ie.comprobante_pago FROM inscripcion_equipo ie
                                       WHERE ie.id_torneo = et.id_torneo AND ie.id_estado = 8
                                         AND UPPER(TRIM(ie.nombre_equipo)) = UPPER(TRIM(e.nombre))
                                       ORDER BY ie.fecha_actualizacion_estado DESC LIMIT 1) AS comprobante_pago,
                                     (SELECT ie.fecha_actualizacion_comprobante_pago FROM inscripcion_equipo ie
                                       WHERE ie.id_torneo = et.id_torneo AND ie.id_estado = 8
                                         AND UPPER(TRIM(ie.nombre_equipo)) = UPPER(TRIM(e.nombre))
                                       ORDER BY ie.fecha_actualizacion_estado DESC LIMIT 1) AS fecha_actualizacion_comprobante_pago,
                                     MAX(CASE WHEN gte.id IS NULL THEN 0 ELSE 1 END) AS asignado
                              FROM equipo_torneo et
                              INNER JOIN equipo e ON e.id = et.id_equipo
                              LEFT JOIN estado_inscripcion ei ON ei.id = et.id_estado_inscripcion
                              LEFT JOIN grupo_torneo_equipo gte ON gte.id_equipo_torneo = et.id
                              WHERE et.id_torneo = :id_torneo
                              GROUP BY et.id, et.id_equipo, e.nombre, e.escudo,
                                       et.id_estado_inscripcion, ei.descripcion,
                                                                             et.fecha_inscripcion
                              ORDER BY e.nombre ASC";
            $stmt = $this->db->prepare($sqlInscriptos);
            $stmt->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
            $stmt->execute();
            $inscriptos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $estadosInscripcion = $this->getEstadosInscripcionCatalogo();

            $inscripcionesTotal = count($inscriptos);
            $inscripcionesAsignadas = 0;
            foreach ($inscriptos as $item) {
                if ((int)($item['asignado'] ?? 0) === 1) {
                    $inscripcionesAsignadas++;
                }
            }

            $stmtSolicitudes = $this->db->prepare(
                "SELECT COUNT(*) FROM inscripcion_equipo
                 WHERE id_torneo = :id_torneo AND id_estado IN (1, 2, 3, 7)"
            );
            $stmtSolicitudes->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
            $stmtSolicitudes->execute();
            $solicitudesActivas = (int)$stmtSolicitudes->fetchColumn();

            $sqlEventos = "SELECT
                               SUM(CASE WHEN ev.titulo LIKE 'Zona % - Fecha % - Partido %' THEN 1 ELSE 0 END) AS eventos_zona,
                               SUM(CASE WHEN ev.titulo NOT LIKE 'Zona % - Fecha % - Partido %' THEN 1 ELSE 0 END) AS eventos_eliminacion,
                               SUM(CASE WHEN ev.id_estado_evento = 2 THEN 1 ELSE 0 END) AS eventos_programados,
                               SUM(CASE WHEN ev.id_estado_evento = 4 THEN 1 ELSE 0 END) AS eventos_finalizados,
                               SUM(CASE WHEN ev.id_estado_evento = 1 OR ev.fecha_hora_inicio IS NULL OR ev.id_cancha IS NULL OR ev.id_arbitro IS NULL THEN 1 ELSE 0 END) AS eventos_a_programar,
                               COUNT(*) AS total
                           FROM evento ev
                           WHERE ev.id_torneo = :id_torneo";
            $stmt = $this->db->prepare($sqlEventos);
            $stmt->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
            $stmt->execute();
            $eventos = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['eventos_zona' => 0, 'eventos_eliminacion' => 0, 'total' => 0];
            $eventosPartido = $this->getEventosPartido($idTorneo, false, 'todas');

            $this->respond(200, [
                'torneo' => $torneo,
                'confirmada' => $confirmada,
                'grupos' => $grupos,
                'asignaciones' => $asignaciones,
                'inscriptos' => $inscriptos,
                'eventos_partido' => $eventosPartido,
                'estados_inscripcion' => $estadosInscripcion,
                'inscripciones' => [
                    'total' => $inscripcionesTotal,
                    'asignadas' => $inscripcionesAsignadas,
                    'solicitudes_activas' => $solicitudesActivas,
                ],
                'eventos' => [
                    'zona' => (int)($eventos['eventos_zona'] ?? 0),
                    'eliminacion' => (int)($eventos['eventos_eliminacion'] ?? 0),
                    'programados' => (int)($eventos['eventos_programados'] ?? 0),
                    'finalizados' => (int)($eventos['eventos_finalizados'] ?? 0),
                    'a_programar' => (int)($eventos['eventos_a_programar'] ?? 0),
                    'total' => (int)($eventos['total'] ?? 0),
                ],
            ]);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener detalle de gestion de torneo');
        }
    }

    public function getEquiposDisponibles(): void
    {
        try {
            $idTorneo = $this->nullableInt($_GET['id_torneo'] ?? null);
            if ($idTorneo === null) {
                $this->respond(400, ['message' => 'id_torneo es obligatorio.']);
            }

            $sql = "SELECT e.id, e.nombre, e.escudo
                    FROM equipo e
                    WHERE e.activo = 1
                      AND e.confirmar = 1
                      AND e.id NOT IN (
                          SELECT et.id_equipo
                          FROM equipo_torneo et
                          WHERE et.id_torneo = :id_torneo
                      )
                    ORDER BY e.nombre ASC, e.id ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
            $stmt->execute();

            $this->respond(200, $stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener equipos disponibles');
        }
    }

    public function asignarEquipos(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!is_array($data)) {
                $this->respond(400, ['message' => 'Datos invalidos para asignar equipos.']);
            }

            $idTorneo = $this->nullableInt($data['id_torneo'] ?? null);
            if ($idTorneo === null) {
                $this->respond(400, ['message' => 'id_torneo es obligatorio.']);
            }

            if (!$this->torneoExiste($idTorneo)) {
                $this->respond(404, ['message' => 'Torneo no encontrado.']);
            }

            if (!$this->existeConfirmadaVigente($idTorneo)) {
                $this->respond(409, ['message' => 'El torneo no tiene una planificación confirmada vigente.']);
            }

            $asignaciones = $data['asignaciones'] ?? [];
            if (!is_array($asignaciones) || count($asignaciones) === 0) {
                $this->respond(400, ['message' => 'Debes enviar al menos una asignacion.']);
            }

            $grupos = $this->getGruposFaseZonas($idTorneo);
            if (empty($grupos)) {
                $this->respond(409, ['message' => 'El torneo no tiene grupos configurados para asignar equipos.']);
            }

            $grupoMap = [];
            foreach ($grupos as $g) {
                $grupoMap[(int)$g['id']] = $g;
            }

            $equiposIds = [];
            $conteoPorGrupo = [];

            foreach ($asignaciones as $row) {
                $idGrupo = (int)($row['id_grupo_torneo'] ?? 0);
                $idEquipo = (int)($row['id_equipo'] ?? 0);
                $posicion = (int)($row['posicion_inicial'] ?? 0);

                if ($idGrupo <= 0 || $idEquipo <= 0 || $posicion <= 0) {
                    $this->respond(400, ['message' => 'Cada asignacion requiere id_grupo_torneo, id_equipo y posicion_inicial validos.']);
                }

                if (!isset($grupoMap[$idGrupo])) {
                    $this->respond(400, ['message' => 'Se detecto un grupo que no pertenece al torneo.']);
                }

                if (in_array($idEquipo, $equiposIds, true)) {
                    $this->respond(422, ['message' => 'No se puede asignar el mismo equipo mas de una vez.']);
                }

                $equiposIds[] = $idEquipo;
                $conteoPorGrupo[$idGrupo] = ($conteoPorGrupo[$idGrupo] ?? 0) + 1;
            }

            foreach ($conteoPorGrupo as $idGrupo => $cantidad) {
                $objetivo = (int)($grupoMap[$idGrupo]['cantidad_equipos_objetivo'] ?? 0);
                if ($objetivo > 0 && $cantidad > $objetivo) {
                    $this->respond(422, ['message' => 'Un grupo supera su capacidad de equipos objetivo.']);
                }
            }

            $equiposActivos = $this->getEquiposActivosMap($equiposIds);
            if (count($equiposActivos) !== count($equiposIds)) {
                $this->respond(422, ['message' => 'Hay equipos inexistentes o inactivos en la asignacion.']);
            }

            $this->db->beginTransaction();

            // Reemplazo completo de asignaciones para mantener consistencia.
            $this->deleteAsignacionesActuales($idTorneo);

            $mapEquipoTorneo = $this->getEquipoTorneoMapByEquipos($idTorneo, $equiposIds);
            if (count($mapEquipoTorneo) !== count($equiposIds)) {
                $this->respond(422, ['message' => 'Hay equipos no inscriptos en el torneo. Cargá inscripciones antes de asignar.']);
            }

            $insertadas = 0;
            foreach ($asignaciones as $row) {
                $idGrupo = (int)$row['id_grupo_torneo'];
                $idEquipo = (int)$row['id_equipo'];
                $posicion = (int)$row['posicion_inicial'];
                $this->insertGrupoTorneoEquipo($idGrupo, $mapEquipoTorneo[$idEquipo], $posicion);
                $insertadas++;
            }

            $parametrosConfirmados = $this->getParametrosConfirmados($idTorneo);
            $idaVuelta = (bool)($parametrosConfirmados['ida_vuelta_zonas'] ?? false);

            $eventosZonaActualizados = $this->actualizarEventosZonaConAsignacion($idTorneo, $idaVuelta);
            $eventosCruceActualizados = $this->actualizarEventosCruceConAsignacion($idTorneo);

            $this->db->commit();

            $this->respond(200, [
                'message' => 'Equipos asignados correctamente.',
                'asignaciones_insertadas' => $insertadas,
                'eventos_zona_actualizados' => $eventosZonaActualizados,
                'eventos_cruce_actualizados' => $eventosCruceActualizados,
            ]);
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->handleError($e, 'Error al asignar equipos del torneo');
        }
    }

    public function eliminarAsignaciones(): void
    {
        try {
            $body = json_decode(file_get_contents('php://input'), true);
            if (!is_array($body)) {
                $this->respond(400, ['message' => 'Datos inválidos para eliminar asignaciones.']);
            }
            $idTorneo = $this->nullableInt($body['id_torneo'] ?? null);
            if ($idTorneo === null) {
                $this->respond(400, ['message' => 'id_torneo es obligatorio.']);
            }

            // Verificar que no haya partidos finalizados para el torneo.
            $sqlCheck = "SELECT COUNT(*) AS finalizados
                         FROM evento
                         WHERE id_torneo = :id_torneo
                           AND id_estado_evento IN (4, 7)";
            $stmtCheck = $this->db->prepare($sqlCheck);
            $stmtCheck->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
            $stmtCheck->execute();
            $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            if ((int)($row['finalizados'] ?? 0) > 0) {
                $this->respond(422, ['message' => 'No se pueden eliminar las asignaciones porque ya hay partidos finalizados en este torneo.']);
            }

            $this->deleteAsignacionesActuales($idTorneo);

            $this->respond(200, ['message' => 'Asignaciones eliminadas correctamente.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al eliminar asignaciones del torneo');
        }
    }

    public function asignarEquiposCruces(): void
    {
        try {
            $body = json_decode(file_get_contents('php://input'), true);
            if (!is_array($body)) {
                $this->respond(400, ['message' => 'Datos inválidos para asignar cruces.']);
            }
            $idTorneo = $this->nullableInt($body['id_torneo'] ?? null);
            if ($idTorneo === null) {
                $this->respond(400, ['message' => 'id_torneo es obligatorio.']);
            }

            if (!$this->torneoExiste($idTorneo)) {
                $this->respond(404, ['message' => 'Torneo no encontrado.']);
            }

            if (!$this->existeConfirmadaVigente($idTorneo)) {
                $this->respond(409, ['message' => 'El torneo no tiene una planificación confirmada vigente.']);
            }

            $hayFaseDeZonas = !empty($this->getGruposFaseZonas($idTorneo));
            if ($hayFaseDeZonas && !$this->isFaseZonasCerrada($idTorneo)) {
                $this->respond(422, ['message' => 'No se pueden asignar cruces hasta finalizar todos los partidos de zonas.']);
            }

            $this->db->beginTransaction();

            if ($hayFaseDeZonas) {
                $map = $this->buildStandingsMap($idTorneo);
                if (empty($map)) {
                    $this->respond(422, ['message' => 'No hay tabla de posiciones disponible para asignar cruces por zonas.']);
                }
                $seedList = $this->buildSeedList($map);
                $actualizados = $this->actualizarCrucesConContexto($idTorneo, $map, $seedList, true);
                $modo = 'zonas_por_puntaje';
            } else {
                $seedList = $this->buildRandomSeedListFromInscriptos($idTorneo);
                if (empty($seedList)) {
                    $this->respond(422, ['message' => 'No hay equipos inscriptos para asignar cruces.']);
                }
                $actualizados = $this->actualizarCrucesConContexto($idTorneo, [], $seedList, true);
                $modo = 'aleatorio';
            }

            $this->db->commit();

            $this->respond(200, [
                'message' => 'Cruces asignados correctamente.',
                'modo' => $modo,
                'eventos_cruce_actualizados' => $actualizados,
            ]);
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->handleError($e, 'Error al asignar equipos en cruces');
        }
    }

    public function actualizarAsignacionCruce(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!is_array($data)) {
                $this->respond(400, ['message' => 'Datos inválidos para actualizar asignación de cruce.']);
            }

            $idTorneo = $this->nullableInt($data['id_torneo'] ?? null);
            $idEvento = $this->nullableInt($data['id_evento'] ?? null);
            $idEquipoLocal = $this->nullableInt($data['id_equipo_local'] ?? null);
            $idEquipoVisitante = $this->nullableInt($data['id_equipo_visitante'] ?? null);

            if ($idTorneo === null || $idEvento === null) {
                $this->respond(400, ['message' => 'id_torneo e id_evento son obligatorios.']);
            }

            if (!$this->torneoExiste($idTorneo)) {
                $this->respond(404, ['message' => 'Torneo no encontrado.']);
            }

            $stmtEventoCruce = $this->db->prepare(
                "SELECT ev.id
                 FROM evento ev
                 INNER JOIN cruce_torneo c ON c.id_evento = ev.id
                 INNER JOIN fase_torneo f ON f.id = c.id_fase_torneo
                 WHERE ev.id = :id_evento
                   AND ev.id_torneo = :id_torneo
                   AND f.id_torneo = :id_torneo
                 LIMIT 1"
            );
            $stmtEventoCruce->bindValue(':id_evento', $idEvento, PDO::PARAM_INT);
            $stmtEventoCruce->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
            $stmtEventoCruce->execute();
            if (!$stmtEventoCruce->fetch(PDO::FETCH_ASSOC)) {
                $this->respond(404, ['message' => 'El partido indicado no pertenece a un cruce del torneo.']);
            }

            if ($idEquipoLocal !== null && $idEquipoVisitante !== null && $idEquipoLocal === $idEquipoVisitante) {
                $this->respond(422, ['message' => 'El equipo local y visitante no pueden ser el mismo.']);
            }

            $idsValidar = [];
            if ($idEquipoLocal !== null) {
                $idsValidar[] = $idEquipoLocal;
            }
            if ($idEquipoVisitante !== null) {
                $idsValidar[] = $idEquipoVisitante;
            }

            if (!empty($idsValidar)) {
                $idsValidar = array_values(array_unique(array_map('intval', $idsValidar)));
                $placeholders = implode(',', array_fill(0, count($idsValidar), '?'));
                $sql = "SELECT id_equipo
                        FROM equipo_torneo
                        WHERE id_torneo = ?
                          AND id_equipo IN ($placeholders)";
                $stmt = $this->db->prepare($sql);
                $stmt->bindValue(1, $idTorneo, PDO::PARAM_INT);
                foreach ($idsValidar as $index => $idEquipo) {
                    $stmt->bindValue($index + 2, $idEquipo, PDO::PARAM_INT);
                }
                $stmt->execute();
                $idsEncontrados = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
                if (count($idsEncontrados) !== count($idsValidar)) {
                    $this->respond(422, ['message' => 'Solo se pueden asignar equipos inscriptos en el torneo.']);
                }
            }

            $stmtUpdate = $this->db->prepare(
                'UPDATE evento
                 SET id_equipo_local = :id_equipo_local,
                     id_equipo_visitante = :id_equipo_visitante
                 WHERE id = :id_evento
                   AND id_torneo = :id_torneo'
            );
            $stmtUpdate->bindValue(':id_equipo_local', $idEquipoLocal, $idEquipoLocal === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmtUpdate->bindValue(':id_equipo_visitante', $idEquipoVisitante, $idEquipoVisitante === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmtUpdate->bindValue(':id_evento', $idEvento, PDO::PARAM_INT);
            $stmtUpdate->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
            $stmtUpdate->execute();

            $this->respond(200, [
                'message' => 'Asignación manual del cruce actualizada correctamente.',
                'id_evento' => $idEvento,
            ]);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al actualizar asignación manual de cruce');
        }
    }

    public function subirComprobantePago(): void
    {
        try {
            if (!isset($_FILES['comprobante'])) {
                $this->respond(400, ['message' => 'Debes adjuntar un archivo en el campo comprobante.']);
            }

            $file = $_FILES['comprobante'];
            if (!is_array($file) || !isset($file['error'])) {
                $this->respond(400, ['message' => 'Archivo de comprobante inválido.']);
            }

            if ((int)$file['error'] !== UPLOAD_ERR_OK) {
                $this->respond(400, ['message' => 'Error al subir el comprobante. Código: ' . (string)$file['error']]);
            }

            $maxBytes = 10 * 1024 * 1024; // 10 MB
            $size = (int)($file['size'] ?? 0);
            if ($size <= 0 || $size > $maxBytes) {
                $this->respond(422, ['message' => 'El comprobante debe pesar entre 1 byte y 10 MB.']);
            }

            $tmpPath = (string)($file['tmp_name'] ?? '');
            if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
                $this->respond(400, ['message' => 'No se encontró el archivo temporal del comprobante.']);
            }

            $allowedMimeToExt = [
                'application/pdf' => 'pdf',
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
            ];

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = $finfo ? (string)finfo_file($finfo, $tmpPath) : '';
            if ($finfo) {
                /** @noinspection PhpDeprecatedFunctionUsageInspection */
                finfo_close($finfo);
            }

            if (!isset($allowedMimeToExt[$mime])) {
                $this->respond(422, ['message' => 'Formato no permitido. Usa PDF, JPG, PNG o WEBP.']);
            }

            $uploadsDir = $this->resolveUploadsDirectory();
            $originalName = (string)($file['name'] ?? 'comprobante');
            $safeBase = $this->sanitizeFileBaseName(pathinfo($originalName, PATHINFO_FILENAME));
            $ext = $allowedMimeToExt[$mime];
            $fileName = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '_' . $safeBase . '.' . $ext;
            $fullPath = rtrim($uploadsDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;

            if (!move_uploaded_file($tmpPath, $fullPath)) {
                $this->respond(500, ['message' => 'No se pudo guardar el comprobante en el servidor.']);
            }

            $webPath = $this->buildUploadsPublicPath($fileName);
            $this->respond(201, [
                'message' => 'Comprobante subido correctamente.',
                'comprobante_pago' => $webPath,
                'file_name' => $fileName,
                'mime_type' => $mime,
                'size_bytes' => $size,
            ]);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al subir comprobante de pago');
        }
    }

    public function inscribirEquipos(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!is_array($data)) {
                $this->respond(400, ['message' => 'Datos invalidos para inscribir equipos.']);
            }

            $idTorneo = $this->nullableInt($data['id_torneo'] ?? null);
            if ($idTorneo === null) {
                $this->respond(400, ['message' => 'id_torneo es obligatorio.']);
            }

            if (!$this->torneoExiste($idTorneo)) {
                $this->respond(404, ['message' => 'Torneo no encontrado.']);
            }

            $inscripciones = $data['inscripciones'] ?? [];
            if (!is_array($inscripciones) || count($inscripciones) === 0) {
                $this->respond(400, ['message' => 'Debes enviar inscripciones para registrar.']);
            }

            $idsEquipos = [];
            foreach ($inscripciones as $item) {
                $idEquipo = (int)($item['id_equipo'] ?? 0);
                if ($idEquipo <= 0) {
                    $this->respond(400, ['message' => 'Cada inscripción debe incluir id_equipo válido.']);
                }
                if (in_array($idEquipo, $idsEquipos, true)) {
                    $this->respond(422, ['message' => 'No se puede repetir equipo en la misma carga de inscripciones.']);
                }

                $idsEquipos[] = $idEquipo;
            }

            $equiposActivos = $this->getEquiposActivosMap($idsEquipos);
            if (count($equiposActivos) !== count($idsEquipos)) {
                $this->respond(422, ['message' => 'Hay equipos inexistentes o inactivos en la carga de inscripciones.']);
            }

            $yaInscriptos = $this->getEquipoTorneoMapByEquipos($idTorneo, $idsEquipos);

            $cupoObjetivo = $this->getCupoObjetivoTorneo($idTorneo);
            if ($cupoObjetivo > 0) {
                $inscriptosActuales = $this->getTotalInscriptosTorneo($idTorneo);
                $nuevasInscripciones = 0;
                foreach ($idsEquipos as $idEquipo) {
                    if (!isset($yaInscriptos[$idEquipo])) {
                        $nuevasInscripciones++;
                    }
                }

                if (($inscriptosActuales + $nuevasInscripciones) > $cupoObjetivo) {
                    $disponibles = max(0, $cupoObjetivo - $inscriptosActuales);
                    $this->respond(422, [
                        'message' => 'No se pueden registrar más inscripciones: el cupo del torneo está completo.',
                        'cupo_equipos' => $cupoObjetivo,
                        'inscriptos_actuales' => $inscriptosActuales,
                        'vacantes_disponibles' => $disponibles,
                    ]);
                }
            }

            $idEstadoInscripta = $this->resolveEstadoInscripcionByDescripcion(['INSCRIPTA', 'PENDIENTE', 'PENDIENTE_PAGO']);

            $this->db->beginTransaction();
            $creadas = 0;
            $actualizadas = 0;

            foreach ($idsEquipos as $idEquipo) {
                if (isset($yaInscriptos[$idEquipo])) {
                    $sql = "UPDATE equipo_torneo
                            SET id_estado_inscripcion = :id_estado_inscripcion,
                                fecha_pago = :fecha_pago,
                                comprobante_pago = :comprobante_pago,
                                observacion = :observacion
                            WHERE id = :id";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bindValue(':id_estado_inscripcion', $idEstadoInscripta, PDO::PARAM_INT);
                    $stmt->bindValue(':fecha_pago', null, PDO::PARAM_NULL);
                    $stmt->bindValue(':comprobante_pago', null, PDO::PARAM_NULL);
                    $stmt->bindValue(':observacion', null, PDO::PARAM_NULL);
                    $stmt->bindValue(':id', (int)$yaInscriptos[$idEquipo], PDO::PARAM_INT);
                    $stmt->execute();
                    $actualizadas++;
                } else {
                    $this->insertEquipoTorneo($idTorneo, $idEquipo, $idEstadoInscripta, null, null, null);
                    $creadas++;
                }
            }

            $this->db->commit();

            $this->respond(200, [
                'message' => 'Inscripciones registradas correctamente.',
                'creadas' => $creadas,
                'actualizadas' => $actualizadas,
            ]);
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->handleError($e, 'Error al cargar inscripciones del torneo');
        }
    }

    public function eliminarInscripcion(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!is_array($data)) {
                $this->respond(400, ['message' => 'Datos inválidos para eliminar inscripción.']);
            }

            $idTorneo = $this->nullableInt($data['id_torneo'] ?? null);
            $idEquipoTorneo = $this->nullableInt($data['id_equipo_torneo'] ?? null);

            if ($idTorneo === null || $idEquipoTorneo === null) {
                $this->respond(400, ['message' => 'id_torneo e id_equipo_torneo son obligatorios.']);
            }

            $stmt = $this->db->prepare('SELECT id FROM equipo_torneo WHERE id = :id AND id_torneo = :id_torneo LIMIT 1');
            $stmt->bindValue(':id', $idEquipoTorneo, PDO::PARAM_INT);
            $stmt->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
            $stmt->execute();
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->respond(404, ['message' => 'La inscripción indicada no existe para el torneo.']);
            }

            $this->db->beginTransaction();

            $stmt = $this->db->prepare('DELETE FROM grupo_torneo_equipo WHERE id_equipo_torneo = :id_equipo_torneo');
            $stmt->bindValue(':id_equipo_torneo', $idEquipoTorneo, PDO::PARAM_INT);
            $stmt->execute();
            $asignacionesEliminadas = $stmt->rowCount();

            $stmt = $this->db->prepare('DELETE FROM equipo_torneo WHERE id = :id AND id_torneo = :id_torneo');
            $stmt->bindValue(':id', $idEquipoTorneo, PDO::PARAM_INT);
            $stmt->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
            $stmt->execute();

            $parametrosConfirmados = $this->getParametrosConfirmados($idTorneo);
            $idaVuelta = (bool)($parametrosConfirmados['ida_vuelta_zonas'] ?? false);

            $eventosZonaActualizados = $this->actualizarEventosZonaConAsignacion($idTorneo, $idaVuelta);
            $eventosCruceActualizados = $this->actualizarEventosCruceConAsignacion($idTorneo);

            $this->db->commit();

            $this->respond(200, [
                'message' => 'Inscripción eliminada correctamente.',
                'asignaciones_eliminadas' => $asignacionesEliminadas,
                'eventos_zona_actualizados' => $eventosZonaActualizados,
                'eventos_cruce_actualizados' => $eventosCruceActualizados,
            ]);
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->handleError($e, 'Error al eliminar inscripción del torneo');
        }
    }

    public function getProgramacionData(): void
    {
        try {
            $idTorneo = $this->nullableInt($_GET['id_torneo'] ?? null);
            if ($idTorneo === null) {
                $this->respond(400, ['message' => 'id_torneo es obligatorio.']);
            }
            $faseProgramar = $this->normalizeFaseProgramar($_GET['fase_programar'] ?? 'todas');

            if (!$this->torneoExiste($idTorneo)) {
                $this->respond(404, ['message' => 'Torneo no encontrado.']);
            }

            $canchas = $this->getCanchasActivasMap();
            $arbitros = $this->getArbitrosActivosMap();
            // La grilla debe mostrar siempre todos los partidos del torneo.
            $eventos = $this->getEventosPartido($idTorneo, false, 'todas');
            // El filtro de fase define el universo a programar, no lo visible en la grilla.
            $eventosObjetivoProgramacion = $this->getEventosPartido($idTorneo, true, $faseProgramar);

            $pendientes = 0;
            foreach ($eventos as $ev) {
                if (empty($ev['fecha_hora_inicio']) || empty($ev['id_cancha']) || empty($ev['id_arbitro'])) {
                    $pendientes++;
                }
            }

            $this->respond(200, [
                'canchas' => array_values($canchas),
                'arbitros' => array_values($arbitros),
                'eventos' => $eventos,
                'resumen' => [
                    'total_eventos' => count($eventos),
                    'pendientes_programar' => $pendientes,
                    'ya_programados' => max(0, count($eventos) - $pendientes),
                ],
                'resumen_objetivo_programacion' => [
                    'fase_programar' => $faseProgramar,
                    'pendientes_en_fase' => count($eventosObjetivoProgramacion),
                ],
                'fase_programar' => $faseProgramar,
            ]);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener datos de programación del torneo');
        }
    }

    public function autoProgramarEventos(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!is_array($data)) {
                $this->respond(400, ['message' => 'Datos inválidos para programación automática.']);
            }

            $idTorneo = $this->nullableInt($data['id_torneo'] ?? null);
            if ($idTorneo === null) {
                $this->respond(400, ['message' => 'id_torneo es obligatorio.']);
            }
            if (!$this->torneoExiste($idTorneo)) {
                $this->respond(404, ['message' => 'Torneo no encontrado.']);
            }

            $fechaInicioRaw = trim((string)($data['fecha_inicio'] ?? date('Y-m-d')));
            $fechaInicio = DateTime::createFromFormat('Y-m-d', $fechaInicioRaw);
            if (!$fechaInicio) {
                $this->respond(400, ['message' => 'fecha_inicio debe tener formato YYYY-MM-DD.']);
            }

            $duracionMinutos = (int)($data['duracion_minutos'] ?? 70);
            if ($duracionMinutos < 20 || $duracionMinutos > 240) {
                $this->respond(422, ['message' => 'duracion_minutos debe estar entre 20 y 240.']);
            }

            $fechaHastaRaw = isset($data['fecha_hasta']) ? trim((string)$data['fecha_hasta']) : '';
            if ($fechaHastaRaw !== '') {
                $fechaHasta = DateTime::createFromFormat('Y-m-d', $fechaHastaRaw);
                if (!$fechaHasta) {
                    $this->respond(400, ['message' => 'fecha_hasta debe tener formato YYYY-MM-DD.']);
                }
                if ($fechaHasta < $fechaInicio) {
                    $this->respond(422, ['message' => 'fecha_hasta no puede ser anterior a fecha_inicio.']);
                }
                $maxDiasBusqueda = (int)$fechaInicio->diff($fechaHasta)->days + 1;
            } else {
                $maxDiasBusqueda = (int)($data['max_dias_busqueda'] ?? 120);
                if ($maxDiasBusqueda < 7) {
                    $maxDiasBusqueda = 7;
                }
                if ($maxDiasBusqueda > 365) {
                    $maxDiasBusqueda = 365;
                }
            }

            $franjas = $this->normalizeFranjas((array)($data['franjas'] ?? []));
            if (empty($franjas)) {
                $this->respond(422, ['message' => 'Debes configurar al menos una franja horaria para programar.']);
            }

            $idsCanchas = $this->normalizeIdList($data['id_canchas'] ?? []);
            $idsArbitros = $this->normalizeIdList($data['id_arbitros'] ?? []);
            $forceReprogramar = (bool)($data['force_reprogramar'] ?? false);
            $faseProgramar = $this->normalizeFaseProgramar($data['fase_programar'] ?? 'todas');
            $idsEventosSeleccionados = $this->normalizeIdList($data['id_eventos'] ?? []);

            $canchas = $this->getCanchasActivasMap($idsCanchas);
            $arbitros = $this->getArbitrosActivosMap($idsArbitros);

            if (empty($canchas)) {
                $this->respond(422, ['message' => 'No hay canchas activas seleccionadas para programar.']);
            }
            if (empty($arbitros)) {
                $this->respond(422, ['message' => 'No hay árbitros activos seleccionados para programar.']);
            }

            $eventos = $this->getEventosPartido($idTorneo, !$forceReprogramar, $faseProgramar);
            if ($faseProgramar === 'seleccionados') {
                if (empty($idsEventosSeleccionados)) {
                    $this->respond(422, ['message' => 'Debes seleccionar al menos un partido pendiente para programar.']);
                }
                $eventos = $this->filterEventosByIds($eventos, $idsEventosSeleccionados);
            }
            if (empty($eventos)) {
                $this->respond(200, [
                    'message' => 'No hay partidos pendientes para programar con la configuración actual.',
                    'programados' => 0,
                    'sin_programar' => 0,
                ]);
            }

            $idsEventosAProgramar = array_map(static fn(array $ev): int => (int)$ev['id'], $eventos);
            $ocupacion = $this->getResourceOccupancy($idTorneo, $idsEventosAProgramar);

            $slots = $this->buildSchedulingSlots(
                clone $fechaInicio,
                $franjas,
                $duracionMinutos,
                array_map('intval', array_keys($canchas)),
                array_map('intval', array_keys($arbitros)),
                $maxDiasBusqueda,
                $ocupacion,
            );

            if (empty($slots)) {
                $this->respond(422, ['message' => 'No se generaron slots válidos con las franjas y recursos seleccionados.']);
            }

            $totalEventos = count($eventos);
            $totalSlots = count($slots);
            $aProgramar = min($totalEventos, $totalSlots);
            $idEstadoProgramado = $this->getEstadoEventoProgramadoId();

            $this->db->beginTransaction();
            for ($i = 0; $i < $aProgramar; $i++) {
                $ev = $eventos[$i];
                $slot = $slots[$i];

                $stmt = $this->db->prepare("UPDATE evento
                                           SET fecha_hora_inicio = :inicio,
                                               fecha_hora_fin = :fin,
                                               id_cancha = :id_cancha,
                                               id_arbitro = :id_arbitro,
                                               id_estado_evento = :id_estado_evento
                                           WHERE id = :id_evento");
                $stmt->bindValue(':inicio', $slot['inicio']);
                $stmt->bindValue(':fin', $slot['fin']);
                $stmt->bindValue(':id_cancha', (int)$slot['id_cancha'], PDO::PARAM_INT);
                $stmt->bindValue(':id_arbitro', (int)$slot['id_arbitro'], PDO::PARAM_INT);
                $stmt->bindValue(':id_estado_evento', $idEstadoProgramado, PDO::PARAM_INT);
                $stmt->bindValue(':id_evento', (int)$ev['id'], PDO::PARAM_INT);
                $stmt->execute();
            }
            $this->db->commit();

            $this->respond(200, [
                'message' => 'Programación automática aplicada correctamente.',
                'programados' => $aProgramar,
                'sin_programar' => max(0, $totalEventos - $aProgramar),
                'slots_generados' => $totalSlots,
                'eventos_pendientes' => $totalEventos,
                'fase_programar' => $faseProgramar,
            ]);
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->handleError($e, 'Error al programar automáticamente los partidos');
        }
    }

    public function deshacerProgramacionEventos(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!is_array($data)) {
                $this->respond(400, ['message' => 'Datos inválidos para deshacer programación.']);
            }

            $idTorneo = $this->nullableInt($data['id_torneo'] ?? null);
            if ($idTorneo === null) {
                $this->respond(400, ['message' => 'id_torneo es obligatorio.']);
            }
            if (!$this->torneoExiste($idTorneo)) {
                $this->respond(404, ['message' => 'Torneo no encontrado.']);
            }

            $faseProgramar = $this->normalizeFaseProgramar($data['fase_programar'] ?? 'todas');
            $idsEventosSeleccionados = $this->normalizeIdList($data['id_eventos'] ?? []);
            $idEstadoProgramado = $this->getEstadoEventoProgramadoId();
            $idEstadoPendiente = $this->getEstadoEventoProgramacionPendienteId();

            $whereFase = '';
            if ($faseProgramar === 'zonas') {
                $whereFase = " AND titulo LIKE 'Zona % - Fecha % - Partido %'";
            } elseif ($faseProgramar === 'eliminacion') {
                $whereFase = " AND titulo NOT LIKE 'Zona % - Fecha % - Partido %'";
            }

            $whereSeleccionados = '';
            $selectedParamMap = [];
            if ($faseProgramar === 'seleccionados') {
                if (empty($idsEventosSeleccionados)) {
                    $this->respond(422, ['message' => 'Debes seleccionar al menos un partido programado para deshacer.']);
                }
                $named = [];
                foreach ($idsEventosSeleccionados as $idx => $idEvento) {
                    $param = ':id_evento_sel_' . $idx;
                    $named[] = $param;
                    $selectedParamMap[$param] = (int)$idEvento;
                }
                $whereSeleccionados = ' AND id IN (' . implode(',', $named) . ')';
            }

            $sqlCount = "SELECT COUNT(*)
                         FROM evento
                         WHERE id_torneo = :id_torneo
                           AND LOWER(tipo_evento) = 'partido'
                           AND id_estado_evento = :id_estado_programado" . $whereFase . $whereSeleccionados;
            $stmtCount = $this->db->prepare($sqlCount);
            $stmtCount->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
            $stmtCount->bindValue(':id_estado_programado', $idEstadoProgramado, PDO::PARAM_INT);
            if ($faseProgramar === 'seleccionados') {
                foreach ($selectedParamMap as $param => $idEvento) {
                    $stmtCount->bindValue($param, $idEvento, PDO::PARAM_INT);
                }
            }
            $stmtCount->execute();
            $totalARevertir = (int)$stmtCount->fetchColumn();

            if ($totalARevertir <= 0) {
                $this->respond(200, [
                    'message' => 'No hay partidos en estado Programado para deshacer con el filtro actual.',
                    'revertidos' => 0,
                    'fase_programar' => $faseProgramar,
                ]);
            }

            $sqlUpdate = "UPDATE evento
                          SET fecha_hora_inicio = NULL,
                              fecha_hora_fin = NULL,
                              id_cancha = NULL,
                              id_arbitro = NULL,
                              id_estado_evento = :id_estado_pendiente
                          WHERE id_torneo = :id_torneo
                            AND LOWER(tipo_evento) = 'partido'
                            AND id_estado_evento = :id_estado_programado" . $whereFase . $whereSeleccionados;
            $stmtUpdate = $this->db->prepare($sqlUpdate);
            $stmtUpdate->bindValue(':id_estado_pendiente', $idEstadoPendiente, PDO::PARAM_INT);
            $stmtUpdate->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
            $stmtUpdate->bindValue(':id_estado_programado', $idEstadoProgramado, PDO::PARAM_INT);
            if ($faseProgramar === 'seleccionados') {
                foreach ($selectedParamMap as $param => $idEvento) {
                    $stmtUpdate->bindValue($param, $idEvento, PDO::PARAM_INT);
                }
            }
            $stmtUpdate->execute();

            $this->respond(200, [
                'message' => 'Programación deshecha correctamente.',
                'revertidos' => (int)$stmtUpdate->rowCount(),
                'fase_programar' => $faseProgramar,
                'eventos_cruce_actualizados' => 0,
            ]);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al deshacer la programación de partidos');
        }
    }

    public function actualizarPagoEventoEquipo(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!is_array($data)) {
                $this->respond(400, ['message' => 'Datos inválidos para actualizar pago por partido.']);
            }

            $idTorneo = $this->nullableInt($data['id_torneo'] ?? null);
            $idEvento = $this->nullableInt($data['id_evento'] ?? null);
            $lado = strtolower(trim((string)($data['lado'] ?? '')));
            $pagoRealizado = (bool)($data['pago_realizado'] ?? false);
            $urlComprobantePago = isset($data['url_comprobante_pago'])
                ? trim((string)$data['url_comprobante_pago'])
                : null;

            if ($idTorneo === null || $idEvento === null || ($lado !== 'local' && $lado !== 'visitante')) {
                $this->respond(400, ['message' => 'id_torneo, id_evento y lado (local/visitante) son obligatorios.']);
            }

            if (!$this->torneoExiste($idTorneo)) {
                $this->respond(404, ['message' => 'Torneo no encontrado.']);
            }

            $evento = $this->getEventoPartidoById($idTorneo, $idEvento);
            if (!$evento) {
                $this->respond(404, ['message' => 'Partido no encontrado para el torneo indicado.']);
            }

            $columns = $this->getEventoPagoColumnMap();
            if (!$columns['all_present']) {
                $this->respond(422, ['message' => 'Faltan columnas de pago por partido en evento. Ejecuta la migración correspondiente.']);
            }

            $colPago = $lado === 'local' ? 'pago_local_realizado' : 'pago_visitante_realizado';
            $colComprobante = $lado === 'local' ? 'url_comprobante_pago_local' : 'url_comprobante_pago_visitante';

            if (!$pagoRealizado) {
                $urlComprobantePago = null;
            } elseif ($urlComprobantePago !== null && $urlComprobantePago !== '' && strlen($urlComprobantePago) > 255) {
                $this->respond(422, ['message' => 'La URL del comprobante no puede superar 255 caracteres.']);
            }

            $sql = "UPDATE evento
                    SET {$colPago} = :pago_realizado,
                        {$colComprobante} = :url_comprobante_pago
                    WHERE id = :id_evento
                      AND id_torneo = :id_torneo
                      AND LOWER(tipo_evento) = 'partido'";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':pago_realizado', $pagoRealizado ? 1 : 0, PDO::PARAM_INT);
            if ($urlComprobantePago !== null && $urlComprobantePago !== '') {
                $stmt->bindValue(':url_comprobante_pago', $urlComprobantePago);
            } else {
                $stmt->bindValue(':url_comprobante_pago', null, PDO::PARAM_NULL);
            }
            $stmt->bindValue(':id_evento', $idEvento, PDO::PARAM_INT);
            $stmt->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
            $stmt->execute();

            $this->respond(200, [
                'message' => 'Pago por partido actualizado correctamente.',
                'id_evento' => $idEvento,
                'lado' => $lado,
                'pago_realizado' => $pagoRealizado ? 1 : 0,
                'url_comprobante_pago' => $urlComprobantePago,
            ]);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al actualizar pago por equipo en partido');
        }
    }

    public function actualizarProgramacionEvento(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!is_array($data)) {
                $this->respond(400, ['message' => 'Datos inválidos para actualizar programación del partido.']);
            }

            $idTorneo = $this->nullableInt($data['id_torneo'] ?? null);
            $idEvento = $this->nullableInt($data['id_evento'] ?? null);
            $idCancha = $this->nullableInt($data['id_cancha'] ?? null);
            $idArbitro = $this->nullableInt($data['id_arbitro'] ?? null);
            $fechaHoraInicio = trim((string)($data['fecha_hora_inicio'] ?? ''));

            if ($idTorneo === null || $idEvento === null || $idCancha === null || $idArbitro === null || $fechaHoraInicio === '') {
                $this->respond(400, ['message' => 'id_torneo, id_evento, fecha_hora_inicio, id_cancha e id_arbitro son obligatorios.']);
            }

            if (!$this->torneoExiste($idTorneo)) {
                $this->respond(404, ['message' => 'Torneo no encontrado.']);
            }

            $evento = $this->getEventoPartidoById($idTorneo, $idEvento);
            if (!$evento) {
                $this->respond(404, ['message' => 'Partido no encontrado para el torneo indicado.']);
            }

            $canchas = $this->getCanchasActivasMap([$idCancha]);
            if (!isset($canchas[$idCancha])) {
                $this->respond(422, ['message' => 'La cancha seleccionada no está activa o no existe.']);
            }

            $arbitros = $this->getArbitrosActivosMap([$idArbitro]);
            if (!isset($arbitros[$idArbitro])) {
                $this->respond(422, ['message' => 'El árbitro seleccionado no está activo o no existe.']);
            }

            $duracionMinutos = (int)($data['duracion_minutos'] ?? 70);
            if ($duracionMinutos < 20 || $duracionMinutos > 240) {
                $this->respond(422, ['message' => 'duracion_minutos debe estar entre 20 y 240.']);
            }

            $inicioNormalizado = str_replace('T', ' ', $fechaHoraInicio);
            if (strlen($inicioNormalizado) === 16) {
                $inicioNormalizado .= ':00';
            }

            $inicio = DateTime::createFromFormat('Y-m-d H:i:s', $inicioNormalizado);
            if (!$inicio) {
                $this->respond(400, ['message' => 'fecha_hora_inicio debe tener formato YYYY-MM-DD HH:MM o YYYY-MM-DDTHH:MM.']);
            }

            $fin = clone $inicio;
            $fin->modify('+' . $duracionMinutos . ' minutes');

            $ocupacion = $this->getResourceOccupancy($idTorneo, [$idEvento]);

            if ($this->hasOverlap((array)($ocupacion['canchas'][$idCancha] ?? []), $inicio, $fin)) {
                $this->respond(422, ['message' => 'La cancha seleccionada no está disponible en el horario indicado.']);
            }

            if ($this->hasOverlap((array)($ocupacion['arbitros'][$idArbitro] ?? []), $inicio, $fin)) {
                $this->respond(422, ['message' => 'El árbitro seleccionado no está disponible en el horario indicado.']);
            }

            $idEstadoProgramado = $this->getEstadoEventoProgramadoId();

            $sql = "UPDATE evento
                    SET fecha_hora_inicio = :inicio,
                        fecha_hora_fin = :fin,
                        id_cancha = :id_cancha,
                        id_arbitro = :id_arbitro,
                        id_estado_evento = :id_estado_evento
                    WHERE id = :id_evento
                      AND id_torneo = :id_torneo
                      AND LOWER(tipo_evento) = 'partido'";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':inicio', $inicio->format('Y-m-d H:i:s'));
            $stmt->bindValue(':fin', $fin->format('Y-m-d H:i:s'));
            $stmt->bindValue(':id_cancha', $idCancha, PDO::PARAM_INT);
            $stmt->bindValue(':id_arbitro', $idArbitro, PDO::PARAM_INT);
            $stmt->bindValue(':id_estado_evento', $idEstadoProgramado, PDO::PARAM_INT);
            $stmt->bindValue(':id_evento', $idEvento, PDO::PARAM_INT);
            $stmt->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
            $stmt->execute();

            $this->respond(200, [
                'message' => 'Partido actualizado correctamente.',
                'id_evento' => $idEvento,
                'fecha_hora_inicio' => $inicio->format('Y-m-d H:i:s'),
                'fecha_hora_fin' => $fin->format('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al actualizar manualmente la programación del partido');
        }
    }

    private function buildSimulation(array $payload): array
    {
        $warnings = [];
        $zonas = [];
        $partidosFaseZonas = 0;
        $minimoPartidosPorEquipo = 1;

        if ($payload['usa_zonas']) {
            [$zonas, $partidosFaseZonas, $minimoPartidosPorEquipo, $warnings] = $this->calcularFaseZonas($payload, $warnings);
        }

        // LIGA: sin llave eliminatoria
        if ($payload['formato'] === 'LIGA') {
            $n = $payload['cantidad_equipos'];
            $idaVuelta = $payload['ida_vuelta_zonas'];
            $factor = $idaVuelta ? 2 : 1;
            $jornadas = (($n % 2 === 0) ? ($n - 1) : $n) * $factor;

            return [
                'message' => 'Planificación simulada correctamente.',
                'input_normalizado' => $payload,
                'resumen' => [
                    'total_partidos' => $partidosFaseZonas,
                    'partidos_fase_zonas' => $partidosFaseZonas,
                    'partidos_eliminacion' => 0,
                    'partidos_consuelo' => 0,
                    'cantidad_zonas' => 1,
                    'llave_equipos' => null,
                    'minimo_partidos_por_equipo' => ($n - 1) * $factor,
                    'jornadas' => $jornadas,
                ],
                'zonas' => $zonas,
                'llave' => null,
                'consuelo' => null,
                'observaciones' => $warnings,
            ];
        }

        [$llave, $partidosEliminacion, $warnings] = $this->calcularLlave($payload, $zonas, $warnings);

        $consuelo = null;
        $partidosConsuelo = 0;
        if ($payload['formato'] === 'GRUPOS_CON_CONSUELO' && $payload['clasificados_consuelo'] > 0) {
            [$consuelo, $partidosConsuelo, $warnings] = $this->calcularLlaveConsuelo($payload, $zonas, $warnings);
        }

        return [
            'message' => 'Planificación simulada correctamente.',
            'input_normalizado' => $payload,
            'resumen' => [
                'total_partidos' => $partidosFaseZonas + $partidosEliminacion + $partidosConsuelo,
                'partidos_fase_zonas' => $partidosFaseZonas,
                'partidos_eliminacion' => $partidosEliminacion,
                'partidos_consuelo' => $partidosConsuelo,
                'cantidad_zonas' => count($zonas),
                'llave_equipos' => $llave['equipos'],
                'minimo_partidos_por_equipo' => $minimoPartidosPorEquipo,
            ],
            'zonas' => $zonas,
            'llave' => $llave,
            'consuelo' => $consuelo,
            'observaciones' => $warnings,
        ];
    }

    private function getConfirmadaData(int $idTorneo): ?array
    {
        $sql = "SELECT id, fecha_generacion, motor_generacion, version_algoritmo,
                       parametros_json, resultado_json, estado
                FROM generacion_fixture
                WHERE id_torneo = :id_torneo
                  AND estado = 'CONFIRMADA'
                ORDER BY id DESC
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return [
            'id' => (int)$row['id'],
            'fecha_generacion' => $row['fecha_generacion'],
            'motor_generacion' => $row['motor_generacion'],
            'version_algoritmo' => $row['version_algoritmo'],
            'estado' => $row['estado'],
            'parametros' => json_decode((string)$row['parametros_json'], true),
            'resultado' => json_decode((string)$row['resultado_json'], true),
        ];
    }

    private function getGruposFaseZonas(int $idTorneo): array
    {
        $sql = "SELECT g.id, g.nombre, g.orden, g.cantidad_equipos_objetivo
                FROM grupo_torneo g
                INNER JOIN fase_torneo f ON f.id = g.id_fase_torneo
                WHERE f.id_torneo = :id_torneo
                  AND UPPER(f.tipo_fase) IN ('FASE_DE_GRUPOS', 'LIGA')
                ORDER BY g.orden ASC, g.id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getEquiposActivosMap(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT id FROM equipo WHERE activo = 1 AND confirmar = 1 AND id IN ($placeholders)";
        $stmt = $this->db->prepare($sql);
        foreach ($ids as $i => $id) {
            $stmt->bindValue($i + 1, $id, PDO::PARAM_INT);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $map = [];
        foreach ($rows as $id) {
            $map[(int)$id] = true;
        }
        return $map;
    }

    private function deleteAsignacionesActuales(int $idTorneo): void
    {
        $sql = "DELETE gte
                FROM grupo_torneo_equipo gte
                INNER JOIN grupo_torneo g ON g.id = gte.id_grupo_torneo
                INNER JOIN fase_torneo f ON f.id = g.id_fase_torneo
                WHERE f.id_torneo = :id_torneo";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
        $stmt->execute();
    }

    private function getCanchasActivasMap(array $idsFiltrados = []): array
    {
        $sql = "SELECT id, nombre, descripcion
                FROM cancha
                WHERE activo = 1";
        if (!empty($idsFiltrados)) {
            $placeholders = implode(',', array_fill(0, count($idsFiltrados), '?'));
            $sql .= " AND id IN ($placeholders)";
        }
        $sql .= " ORDER BY nombre ASC, id ASC";

        $stmt = $this->db->prepare($sql);
        foreach ($idsFiltrados as $i => $id) {
            $stmt->bindValue($i + 1, $id, PDO::PARAM_INT);
        }
        $stmt->execute();

        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $map[(int)$row['id']] = $row;
        }
        return $map;
    }

    private function getArbitrosActivosMap(array $idsFiltrados = []): array
    {
        $sql = "SELECT id, nombre, apellido
                FROM arbitro
                WHERE activo = 1";
        if (!empty($idsFiltrados)) {
            $placeholders = implode(',', array_fill(0, count($idsFiltrados), '?'));
            $sql .= " AND id IN ($placeholders)";
        }
        $sql .= " ORDER BY apellido ASC, nombre ASC, id ASC";

        $stmt = $this->db->prepare($sql);
        foreach ($idsFiltrados as $i => $id) {
            $stmt->bindValue($i + 1, $id, PDO::PARAM_INT);
        }
        $stmt->execute();

        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $row['nombre_completo'] = trim((string)$row['apellido'] . ' ' . (string)$row['nombre']);
            $map[(int)$row['id']] = $row;
        }
        return $map;
    }

    private function getEventosPartido(int $idTorneo, bool $soloPendientes = false, string $faseProgramar = 'todas'): array
    {
        $eventoPagoColumnMap = $this->getEventoPagoColumnMap();
        $pagoLocalSelect = $eventoPagoColumnMap['pago_local']
            ? 'COALESCE(ev.pago_local_realizado, 0) AS pago_local_realizado'
            : '0 AS pago_local_realizado';
        $urlLocalSelect = $eventoPagoColumnMap['url_local']
            ? 'ev.url_comprobante_pago_local AS url_comprobante_pago_local'
            : 'NULL AS url_comprobante_pago_local';
        $pagoVisitanteSelect = $eventoPagoColumnMap['pago_visitante']
            ? 'COALESCE(ev.pago_visitante_realizado, 0) AS pago_visitante_realizado'
            : '0 AS pago_visitante_realizado';
        $urlVisitanteSelect = $eventoPagoColumnMap['url_visitante']
            ? 'ev.url_comprobante_pago_visitante AS url_comprobante_pago_visitante'
            : 'NULL AS url_comprobante_pago_visitante';

        $sql = "SELECT ev.id, ev.titulo, ev.numero_fecha, ev.fecha_hora_inicio, ev.fecha_hora_fin,
                   ev.id_estado_evento,
                       ev.id_cancha, ev.id_arbitro,
                       ev.id_equipo_local, ev.id_equipo_visitante,
                       ev.resultado_local, ev.resultado_visitante,
                       {$pagoLocalSelect},
                       {$urlLocalSelect},
                       {$pagoVisitanteSelect},
                       {$urlVisitanteSelect},
                       el.nombre AS equipo_local_nombre,
                       el.escudo AS equipo_local_escudo,
                       evt.nombre AS equipo_visitante_nombre,
                       evt.escudo AS equipo_visitante_escudo,
                   ee.descripcion AS estado_evento_descripcion,
                       c.nombre AS cancha_nombre,
                       CONCAT(COALESCE(a.apellido, ''), ' ', COALESCE(a.nombre, '')) AS arbitro_nombre_completo
                FROM evento ev
            LEFT JOIN estado_evento ee ON ee.id = ev.id_estado_evento
                LEFT JOIN cancha c ON c.id = ev.id_cancha
                LEFT JOIN arbitro a ON a.id = ev.id_arbitro
                LEFT JOIN equipo el ON el.id = ev.id_equipo_local
                LEFT JOIN equipo evt ON evt.id = ev.id_equipo_visitante
                WHERE ev.id_torneo = :id_torneo
                  AND LOWER(tipo_evento) = 'partido'";

        if ($faseProgramar === 'zonas') {
            $sql .= " AND ev.titulo LIKE 'Zona % - Fecha % - Partido %'";
        } elseif ($faseProgramar === 'eliminacion') {
            $sql .= " AND ev.titulo NOT LIKE 'Zona % - Fecha % - Partido %'";
        }

        if ($soloPendientes) {
            $sql .= " AND (fecha_hora_inicio IS NULL OR id_cancha IS NULL OR id_arbitro IS NULL)";
        }

        $sql .= " ORDER BY COALESCE(numero_fecha, 9999) ASC, id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getEventoPagoColumnMap(): array
    {
        if ($this->eventoPagoColumns !== null) {
            return $this->eventoPagoColumns;
        }

        $map = [
            'pago_local' => $this->tableHasColumn('evento', 'pago_local_realizado'),
            'url_local' => $this->tableHasColumn('evento', 'url_comprobante_pago_local'),
            'pago_visitante' => $this->tableHasColumn('evento', 'pago_visitante_realizado'),
            'url_visitante' => $this->tableHasColumn('evento', 'url_comprobante_pago_visitante'),
        ];
        $map['all_present'] = $map['pago_local'] && $map['url_local'] && $map['pago_visitante'] && $map['url_visitante'];

        $this->eventoPagoColumns = $map;
        return $this->eventoPagoColumns;
    }

    private function tableHasColumn(string $table, string $column): bool
    {
        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM {$table} LIKE '{$column}'");
            return $stmt !== false && (bool)$stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable) {
            return false;
        }
    }

    private function normalizeFaseProgramar(mixed $value): string
    {
        $raw = strtolower(trim((string)$value));
        if ($raw === 'zonas' || $raw === 'eliminacion' || $raw === 'seleccionados') {
            return $raw;
        }
        return 'todas';
    }

    private function filterEventosByIds(array $eventos, array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        $set = [];
        foreach ($ids as $id) {
            $set[(int)$id] = true;
        }

        $out = [];
        foreach ($eventos as $ev) {
            $idEvento = (int)($ev['id'] ?? 0);
            if ($idEvento > 0 && isset($set[$idEvento])) {
                $out[] = $ev;
            }
        }
        return $out;
    }

    private function getEventoPartidoById(int $idTorneo, int $idEvento): ?array
    {
        $sql = "SELECT id, id_torneo, tipo_evento
                FROM evento
                WHERE id = :id_evento
                  AND id_torneo = :id_torneo
                  AND LOWER(tipo_evento) = 'partido'
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_evento', $idEvento, PDO::PARAM_INT);
        $stmt->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function normalizeFranjas(array $franjas): array
    {
        $out = [];
        foreach ($franjas as $item) {
            if (!is_array($item)) {
                continue;
            }
            $dia = (int)($item['dia_semana'] ?? 0);
            $inicio = trim((string)($item['hora_inicio'] ?? ''));
            $fin = trim((string)($item['hora_fin'] ?? ''));
            if ($dia < 1 || $dia > 7 || !$this->isHoraValida($inicio) || !$this->isHoraValida($fin)) {
                continue;
            }
            if ($inicio >= $fin) {
                continue;
            }
            $out[] = [
                'dia_semana' => $dia,
                'hora_inicio' => $inicio,
                'hora_fin' => $fin,
            ];
        }
        return $out;
    }

    private function isHoraValida(string $value): bool
    {
        return (bool)preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value);
    }

    private function normalizeIdList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }
        $ids = [];
        foreach ($value as $v) {
            $id = (int)$v;
            if ($id > 0) {
                $ids[$id] = true;
            }
        }
        return array_map('intval', array_keys($ids));
    }

    private function buildSchedulingSlots(
        DateTime $fechaInicio,
        array $franjas,
        int $duracionMinutos,
        array $idCanchas,
        array $idArbitros,
        int $maxDiasBusqueda,
        array $ocupacion,
    ): array {
        $franjasPorDia = [];
        foreach ($franjas as $f) {
            $franjasPorDia[(int)$f['dia_semana']][] = $f;
        }

        $slots = [];
        $arbCursor = 0;

        for ($d = 0; $d < $maxDiasBusqueda; $d++) {
            $fecha = clone $fechaInicio;
            if ($d > 0) {
                $fecha->modify('+' . $d . ' day');
            }
            $diaSemana = (int)$fecha->format('N');
            $franjasDia = $franjasPorDia[$diaSemana] ?? [];
            if (empty($franjasDia)) {
                continue;
            }

            $fechaStr = $fecha->format('Y-m-d');
            foreach ($franjasDia as $franja) {
                $slotInicio = DateTime::createFromFormat('Y-m-d H:i', $fechaStr . ' ' . $franja['hora_inicio']);
                $slotFin = DateTime::createFromFormat('Y-m-d H:i', $fechaStr . ' ' . $franja['hora_fin']);
                if (!$slotInicio || !$slotFin || $slotInicio >= $slotFin) {
                    continue;
                }

                $cursor = clone $slotInicio;
                while ($cursor < $slotFin) {
                    $inicio = clone $cursor;
                    $fin = clone $cursor;
                    $fin->modify('+' . $duracionMinutos . ' minutes');
                    if ($fin > $slotFin) {
                        break;
                    }

                    $arbitrosUsadosEnHorario = [];
                    foreach ($idCanchas as $idCancha) {
                        if ($this->hasOverlap((array)($ocupacion['canchas'][$idCancha] ?? []), $inicio, $fin)) {
                            continue;
                        }

                        $idArbitro = $this->pickNextArbitro(
                            $idArbitros,
                            $arbitrosUsadosEnHorario,
                            (array)($ocupacion['arbitros'] ?? []),
                            $inicio,
                            $fin,
                            $arbCursor,
                        );
                        if ($idArbitro === null) {
                            break;
                        }

                        $slots[] = [
                            'inicio' => $inicio->format('Y-m-d H:i:s'),
                            'fin' => $fin->format('Y-m-d H:i:s'),
                            'id_cancha' => $idCancha,
                            'id_arbitro' => $idArbitro,
                        ];

                        $ocupacion['canchas'][$idCancha][] = ['inicio' => clone $inicio, 'fin' => clone $fin];
                        $ocupacion['arbitros'][$idArbitro][] = ['inicio' => clone $inicio, 'fin' => clone $fin];
                        $arbitrosUsadosEnHorario[$idArbitro] = true;
                    }

                    $cursor->modify('+' . $duracionMinutos . ' minutes');
                }
            }
        }

        usort($slots, static function (array $a, array $b): int {
            if ($a['inicio'] === $b['inicio']) {
                return $a['id_cancha'] <=> $b['id_cancha'];
            }
            return strcmp((string)$a['inicio'], (string)$b['inicio']);
        });

        return $slots;
    }

    private function pickNextArbitro(
        array $idArbitros,
        array $arbitrosUsadosEnHorario,
        array $ocupacionArbitros,
        DateTime $inicio,
        DateTime $fin,
        int &$cursor,
    ): ?int
    {
        $total = count($idArbitros);
        if ($total === 0) {
            return null;
        }

        for ($i = 0; $i < $total; $i++) {
            $idx = ($cursor + $i) % $total;
            $id = (int)$idArbitros[$idx];
            if (isset($arbitrosUsadosEnHorario[$id])) {
                continue;
            }
            if ($this->hasOverlap((array)($ocupacionArbitros[$id] ?? []), $inicio, $fin)) {
                continue;
            }
            if (!isset($arbitrosUsadosEnHorario[$id])) {
                $cursor = $idx + 1;
                return $id;
            }
        }

        return null;
    }

    private function hasOverlap(array $intervalos, DateTime $inicio, DateTime $fin): bool
    {
        $iniTs = $inicio->getTimestamp();
        $finTs = $fin->getTimestamp();
        foreach ($intervalos as $r) {
            if (!isset($r['inicio'], $r['fin']) || !($r['inicio'] instanceof DateTime) || !($r['fin'] instanceof DateTime)) {
                continue;
            }
            $rIni = $r['inicio']->getTimestamp();
            $rFin = $r['fin']->getTimestamp();
            if ($iniTs < $rFin && $finTs > $rIni) {
                return true;
            }
        }
        return false;
    }

    private function getResourceOccupancy(int $idTorneo, array $excludeEventoIds = []): array
    {
        $sql = "SELECT id, fecha_hora_inicio, fecha_hora_fin, id_cancha, id_arbitro
                FROM evento
                WHERE LOWER(tipo_evento) = 'partido'
                  AND fecha_hora_inicio IS NOT NULL
                  AND fecha_hora_fin IS NOT NULL";

        if (!empty($excludeEventoIds)) {
            $sql .= ' AND id NOT IN (' . implode(',', array_fill(0, count($excludeEventoIds), '?')) . ')';
        }

        $stmt = $this->db->prepare($sql);
        foreach ($excludeEventoIds as $i => $idEv) {
            $stmt->bindValue($i + 1, (int)$idEv, PDO::PARAM_INT);
        }
        $stmt->execute();

        $ocupacion = ['canchas' => [], 'arbitros' => []];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $inicio = DateTime::createFromFormat('Y-m-d H:i:s', (string)$row['fecha_hora_inicio']);
            $fin = DateTime::createFromFormat('Y-m-d H:i:s', (string)$row['fecha_hora_fin']);
            if (!$inicio || !$fin || $inicio >= $fin) {
                continue;
            }

            $idCancha = (int)($row['id_cancha'] ?? 0);
            $idArbitro = (int)($row['id_arbitro'] ?? 0);
            if ($idCancha > 0) {
                $ocupacion['canchas'][$idCancha][] = ['inicio' => clone $inicio, 'fin' => clone $fin];
            }
            if ($idArbitro > 0) {
                $ocupacion['arbitros'][$idArbitro][] = ['inicio' => clone $inicio, 'fin' => clone $fin];
            }
        }

        return $ocupacion;
    }

    private function resolveEstadoInscripcionByDescripcion(array $descripciones): int
    {
        foreach ($descripciones as $desc) {
            $stmt = $this->db->prepare('SELECT id FROM estado_inscripcion WHERE UPPER(descripcion) = :desc LIMIT 1');
            $stmt->bindValue(':desc', strtoupper($desc));
            $stmt->execute();
            $id = $stmt->fetchColumn();
            if ($id) {
                return (int)$id;
            }
        }

        $stmt = $this->db->prepare('SELECT id FROM estado_inscripcion ORDER BY id ASC LIMIT 1');
        $stmt->execute();
        $id = $stmt->fetchColumn();
        if ($id) {
            return (int)$id;
        }

        $this->respond(400, ['message' => 'No existe estado_inscripcion configurado para asignar equipos.']);
        return 0;
    }

    private function insertEquipoTorneo(
        int $idTorneo,
        int $idEquipo,
        int $idEstadoInscripcion,
        ?string $fechaPago = null,
        ?string $comprobantePago = null,
        ?string $observacion = null
    ): int
    {
        $sql = "INSERT INTO equipo_torneo (
                    id_equipo,
                    id_torneo,
                    id_estado_inscripcion,
                    fecha_inscripcion,
                    fecha_pago,
                    comprobante_pago,
                    observacion
                ) VALUES (
                    :id_equipo,
                    :id_torneo,
                    :id_estado_inscripcion,
                    CURDATE(),
                    :fecha_pago,
                    :comprobante_pago,
                    :observacion
                )";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_equipo', $idEquipo, PDO::PARAM_INT);
        $stmt->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
        $stmt->bindValue(':id_estado_inscripcion', $idEstadoInscripcion, PDO::PARAM_INT);
        if ($fechaPago) {
            $stmt->bindValue(':fecha_pago', $fechaPago);
        } else {
            $stmt->bindValue(':fecha_pago', null, PDO::PARAM_NULL);
        }
        if (!empty($comprobantePago)) {
            $stmt->bindValue(':comprobante_pago', $comprobantePago);
        } else {
            $stmt->bindValue(':comprobante_pago', null, PDO::PARAM_NULL);
        }
        if (!empty($observacion)) {
            $stmt->bindValue(':observacion', $observacion);
        } else {
            $stmt->bindValue(':observacion', null, PDO::PARAM_NULL);
        }
        $stmt->execute();
        return (int)$this->db->lastInsertId();
    }

    private function getEstadosInscripcionCatalogo(): array
    {
        $stmt = $this->db->prepare('SELECT id, descripcion FROM estado_inscripcion ORDER BY id ASC');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function resolveUploadsDirectory(): string
    {
        $configured = trim((string)($_ENV['UPLOADS_DIR'] ?? getenv('UPLOADS_DIR') ?? ''));
        $candidates = [];
        if ($configured !== '') {
            $candidates[] = $configured;
        }
        // Default: always use uploads under current project root.
        $candidates[] = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'uploads';

        foreach ($candidates as $candidate) {
            $dir = rtrim($candidate, "\\/");
            if ($dir === '') {
                continue;
            }
            if (is_dir($dir) || @mkdir($dir, 0775, true)) {
                return $dir;
            }
        }

        $this->respond(500, ['message' => 'No se pudo resolver el directorio de uploads en el proyecto. Configura UPLOADS_DIR.']);
        return '';
    }

    private function sanitizeFileBaseName(string $baseName): string
    {
        $sanitized = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $baseName);
        $sanitized = trim((string)$sanitized, '_');
        if ($sanitized === '') {
            $sanitized = 'comprobante';
        }
        return substr($sanitized, 0, 60);
    }

    private function buildUploadsPublicPath(string $fileName): string
    {
        $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
        $apiDir = rtrim((string)dirname($scriptName), '/');
        $baseDir = preg_replace('#/api$#', '', $apiDir) ?? '';
        $baseDir = rtrim($baseDir, '/');

        if ($baseDir === '' || $baseDir === '.') {
            return '/uploads/' . $fileName;
        }

        return $baseDir . '/uploads/' . $fileName;
    }

    private function getEquipoTorneoMapByEquipos(int $idTorneo, array $idsEquipos): array
    {
        if (empty($idsEquipos)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($idsEquipos), '?'));
        $sql = "SELECT id, id_equipo
                FROM equipo_torneo
                WHERE id_torneo = ?
                  AND id_equipo IN ($placeholders)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $idTorneo, PDO::PARAM_INT);
        foreach ($idsEquipos as $i => $idEquipo) {
            $stmt->bindValue($i + 2, $idEquipo, PDO::PARAM_INT);
        }
        $stmt->execute();

        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $map[(int)$row['id_equipo']] = (int)$row['id'];
        }
        return $map;
    }

    private function getTotalInscriptosTorneo(int $idTorneo): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM equipo_torneo WHERE id_torneo = :id_torneo');
        $stmt->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    private function getCupoObjetivoTorneo(int $idTorneo): int
    {
        $stmt = $this->db->prepare('SELECT cupo_equipos FROM torneo WHERE id = :id_torneo LIMIT 1');
        $stmt->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
        $stmt->execute();
        $cupo = (int)($stmt->fetchColumn() ?: 0);
        if ($cupo > 0) {
            return $cupo;
        }

        $stmt = $this->db->prepare("SELECT COALESCE(SUM(g.cantidad_equipos_objetivo), 0)
                                   FROM grupo_torneo g
                                   INNER JOIN fase_torneo f ON f.id = g.id_fase_torneo
                                   WHERE f.id_torneo = :id_torneo
                                     AND UPPER(f.tipo_fase) = 'FASE_DE_GRUPOS'");
        $stmt->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
        $stmt->execute();

        return (int)($stmt->fetchColumn() ?: 0);
    }

    private function insertGrupoTorneoEquipo(int $idGrupo, int $idEquipoTorneo, int $posicion): void
    {
        $sql = "INSERT INTO grupo_torneo_equipo (id_grupo_torneo, id_equipo_torneo, posicion_inicial)
                VALUES (:id_grupo_torneo, :id_equipo_torneo, :posicion_inicial)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_grupo_torneo', $idGrupo, PDO::PARAM_INT);
        $stmt->bindValue(':id_equipo_torneo', $idEquipoTorneo, PDO::PARAM_INT);
        $stmt->bindValue(':posicion_inicial', $posicion, PDO::PARAM_INT);
        $stmt->execute();
    }

    private function getParametrosConfirmados(int $idTorneo): array
    {
        $stmt = $this->db->prepare("SELECT parametros_json
                                   FROM generacion_fixture
                                   WHERE id_torneo = :id_torneo AND estado = 'CONFIRMADA'
                                   ORDER BY id DESC LIMIT 1");
        $stmt->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
        $stmt->execute();
        $json = $stmt->fetchColumn();
        if (!$json) {
            return [];
        }

        $decoded = json_decode((string)$json, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function actualizarEventosZonaConAsignacion(int $idTorneo, bool $idaVuelta): int
    {
        $grupos = $this->getGruposFaseZonas($idTorneo);
        $actualizados = 0;

        foreach ($grupos as $grupo) {
            $idGrupo = (int)$grupo['id'];
            $nombreGrupo = (string)$grupo['nombre'];
            $zona = $this->extractZonaFromGroupName($nombreGrupo);
            $esLiga = strtoupper(trim($nombreGrupo)) === 'LIGA';

            if ($zona === null && !$esLiga) {
                continue;
            }

            $sql = "SELECT et.id_equipo
                    FROM grupo_torneo_equipo gte
                    INNER JOIN equipo_torneo et ON et.id = gte.id_equipo_torneo
                    WHERE gte.id_grupo_torneo = :id_grupo
                    ORDER BY gte.posicion_inicial ASC, gte.id ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id_grupo', $idGrupo, PDO::PARAM_INT);
            $stmt->execute();
            $teamIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

            $matches = $this->buildRoundRobinMatches($teamIds, $idaVuelta);
            if (empty($matches)) {
                continue;
            }

            $prefix = $esLiga
                ? 'Liga - Fecha % - Partido %'
                : 'Zona ' . $zona . ' - Fecha % - Partido %';

            $stmt = $this->db->prepare("SELECT id
                                       FROM evento
                                       WHERE id_torneo = :id_torneo
                                         AND titulo LIKE :prefix
                                       ORDER BY numero_fecha ASC, id ASC");
            $stmt->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
            $stmt->bindValue(':prefix', $prefix);
            $stmt->execute();
            $eventIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

            $limit = min(count($eventIds), count($matches));
            for ($i = 0; $i < $limit; $i++) {
                $eventId = $eventIds[$i];
                $match = $matches[$i];
                $u = $this->db->prepare('UPDATE evento SET id_equipo_local = :local, id_equipo_visitante = :visitante WHERE id = :id');
                $u->bindValue(':local', $match['local'], PDO::PARAM_INT);
                $u->bindValue(':visitante', $match['visitante'], PDO::PARAM_INT);
                $u->bindValue(':id', $eventId, PDO::PARAM_INT);
                $u->execute();
                $actualizados++;
            }

            // Limpia eventos sobrantes si hay menos cruces asignables que placeholders.
            for ($i = $limit; $i < count($eventIds); $i++) {
                $u = $this->db->prepare('UPDATE evento SET id_equipo_local = NULL, id_equipo_visitante = NULL WHERE id = :id');
                $u->bindValue(':id', $eventIds[$i], PDO::PARAM_INT);
                $u->execute();
            }
        }

        return $actualizados;
    }

    private function actualizarEventosCruceConAsignacion(int $idTorneo): int
    {
        $zonasCerradas = $this->isFaseZonasCerrada($idTorneo);

        // When all zona matches are finished, use real standings (pts > dif > gf > nombre).
        // Otherwise fall back to the initial assignment order (preview / placeholder).
        if ($zonasCerradas) {
            $map = $this->buildStandingsMap($idTorneo);
        } else {
            $map = $this->buildMapPosicionGrupoEquipo($idTorneo);
        }

        $seedList = $this->buildSeedList($map);

        return $this->actualizarCrucesConContexto($idTorneo, $map, $seedList, $zonasCerradas);
    }

    private function actualizarCrucesConContexto(int $idTorneo, array $map, array $seedList, bool $zonasCerradas): int
    {
        $sql = "SELECT c.id, c.id_evento,
                       c.origen_local_tipo, c.origen_local_valor,
                       c.origen_visitante_tipo, c.origen_visitante_valor
                FROM cruce_torneo c
                INNER JOIN fase_torneo f ON f.id = c.id_fase_torneo
                WHERE f.id_torneo = :id_torneo
                  AND c.id_evento IS NOT NULL
                ORDER BY c.id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
        $stmt->execute();
        $cruces = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $actualizados = 0;
        foreach ($cruces as $cruce) {
            $local = $this->resolveOrigenEquipoId((string)$cruce['origen_local_tipo'], (string)$cruce['origen_local_valor'], $map, $seedList, $zonasCerradas);
            $visitante = $this->resolveOrigenEquipoId((string)$cruce['origen_visitante_tipo'], (string)$cruce['origen_visitante_valor'], $map, $seedList, $zonasCerradas);

            $u = $this->db->prepare('UPDATE evento SET id_equipo_local = :local, id_equipo_visitante = :visitante WHERE id = :id');
            $u->bindValue(':local', $local, $local === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $u->bindValue(':visitante', $visitante, $visitante === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $u->bindValue(':id', (int)$cruce['id_evento'], PDO::PARAM_INT);
            $u->execute();
            $actualizados++;
        }

        return $actualizados;
    }

    /**
     * Builds a standings map from actual match results for finished zona matches.
     * Returns: map[$zonaLetra][$rank] = $id_equipo  (rank starts at 1)
     * Sort order: pts DESC, dif DESC, gf DESC, nombre ASC.
     * Same structure as buildMapPosicionGrupoEquipo so resolveOrigenEquipoId works unchanged.
     */
    private function buildStandingsMap(int $idTorneo): array
    {
        // 1. Load teams per group.
        $sqlTeams = "SELECT g.id AS id_grupo, g.nombre AS grupo_nombre,
                            et.id_equipo, e.nombre AS equipo_nombre
                     FROM grupo_torneo g
                     INNER JOIN fase_torneo f ON f.id = g.id_fase_torneo
                     INNER JOIN grupo_torneo_equipo gte ON gte.id_grupo_torneo = g.id
                     INNER JOIN equipo_torneo et ON et.id = gte.id_equipo_torneo
                     INNER JOIN equipo e ON e.id = et.id_equipo
                     WHERE f.id_torneo = :id_torneo
                       AND UPPER(f.tipo_fase) IN ('FASE_DE_GRUPOS', 'LIGA')
                     ORDER BY g.orden ASC, g.id ASC";
        $stmtTeams = $this->db->prepare($sqlTeams);
        $stmtTeams->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
        $stmtTeams->execute();

        $grupos = [];
        foreach ($stmtTeams->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $zonaLetra = $this->extractZonaFromGroupName((string)($row['grupo_nombre'] ?? ''));
            if ($zonaLetra === null) continue;
            $idEquipo = (int)$row['id_equipo'];
            if (!isset($grupos[$zonaLetra])) $grupos[$zonaLetra] = [];
            $grupos[$zonaLetra][$idEquipo] = [
                'id'     => $idEquipo,
                'nombre' => (string)($row['equipo_nombre'] ?? ''),
                'pts'    => 0,
                'gf'     => 0,
                'gc'     => 0,
            ];
        }

        // 2. Apply finished zona match results.
        $sqlMatches = "SELECT id_equipo_local, id_equipo_visitante,
                              resultado_local, resultado_visitante, titulo
                       FROM evento
                       WHERE id_torneo = :id_torneo
                         AND LOWER(tipo_evento) = 'partido'
                         AND titulo LIKE 'Zona %'
                         AND id_estado_evento IN (4, 7)
                         AND resultado_local IS NOT NULL
                         AND resultado_visitante IS NOT NULL";
        $stmtMatches = $this->db->prepare($sqlMatches);
        $stmtMatches->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
        $stmtMatches->execute();

        foreach ($stmtMatches->fetchAll(PDO::FETCH_ASSOC) as $ev) {
            // Extract zone letter from title like "Zona A - Fecha 1 - Partido 1".
            $titulo = strtoupper((string)($ev['titulo'] ?? ''));
            if (!preg_match('/\bZONA\s+([A-Z])\b/', $titulo, $zm)) continue;
            $zonaLetra = $zm[1];
            if (!isset($grupos[$zonaLetra])) continue;

            $idL = (int)$ev['id_equipo_local'];
            $idV = (int)$ev['id_equipo_visitante'];
            $gL  = (int)$ev['resultado_local'];
            $gV  = (int)$ev['resultado_visitante'];

            if (isset($grupos[$zonaLetra][$idL])) {
                $grupos[$zonaLetra][$idL]['gf'] += $gL;
                $grupos[$zonaLetra][$idL]['gc'] += $gV;
            }
            if (isset($grupos[$zonaLetra][$idV])) {
                $grupos[$zonaLetra][$idV]['gf'] += $gV;
                $grupos[$zonaLetra][$idV]['gc'] += $gL;
            }

            if ($gL > $gV) {
                if (isset($grupos[$zonaLetra][$idL])) $grupos[$zonaLetra][$idL]['pts'] += 3;
            } elseif ($gL < $gV) {
                if (isset($grupos[$zonaLetra][$idV])) $grupos[$zonaLetra][$idV]['pts'] += 3;
            } else {
                if (isset($grupos[$zonaLetra][$idL])) $grupos[$zonaLetra][$idL]['pts'] += 1;
                if (isset($grupos[$zonaLetra][$idV])) $grupos[$zonaLetra][$idV]['pts'] += 1;
            }
        }

        // 3. Sort each zone and build positional map.
        ksort($grupos);
        $map = [];
        foreach ($grupos as $zonaLetra => $equipos) {
            $lista = array_values($equipos);
            foreach ($lista as &$eq) {
                $eq['dif'] = $eq['gf'] - $eq['gc'];
            }
            unset($eq);

            usort($lista, static function (array $a, array $b): int {
                if ($b['pts'] !== $a['pts']) return $b['pts'] <=> $a['pts'];
                if ($b['dif'] !== $a['dif']) return $b['dif'] <=> $a['dif'];
                if ($b['gf']  !== $a['gf'])  return $b['gf']  <=> $a['gf'];
                return strcasecmp((string)$a['nombre'], (string)$b['nombre']);
            });

            $map[$zonaLetra] = [];
            foreach ($lista as $rank => $eq) {
                $map[$zonaLetra][$rank + 1] = (int)$eq['id'];
            }
        }

        return $map;
    }

    private function isFaseZonasCerrada(int $idTorneo): bool
    {
        $stmtExisteFase = $this->db->prepare("SELECT 1
                                              FROM fase_torneo
                                              WHERE id_torneo = :id_torneo
                                                AND UPPER(tipo_fase) = 'FASE_DE_GRUPOS'
                                              LIMIT 1");
        $stmtExisteFase->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
        $stmtExisteFase->execute();
        $hayFaseGrupos = (bool)$stmtExisteFase->fetchColumn();

        // Si no hay fase de grupos, no bloqueamos la asignacion por seed.
        if (!$hayFaseGrupos) {
            return true;
        }

        $stmtPendientes = $this->db->prepare("SELECT COUNT(*)
                                              FROM evento
                                              WHERE id_torneo = :id_torneo
                                                AND LOWER(tipo_evento) = 'partido'
                                                AND titulo LIKE 'Zona %'
                                                AND id_estado_evento NOT IN (4, 7)");
        $stmtPendientes->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
        $stmtPendientes->execute();

        return ((int)$stmtPendientes->fetchColumn()) === 0;
    }

    private function buildMapPosicionGrupoEquipo(int $idTorneo): array
    {
        $sql = "SELECT g.nombre AS grupo_nombre, gte.posicion_inicial, et.id_equipo
                FROM grupo_torneo_equipo gte
                INNER JOIN grupo_torneo g ON g.id = gte.id_grupo_torneo
                INNER JOIN fase_torneo f ON f.id = g.id_fase_torneo
                INNER JOIN equipo_torneo et ON et.id = gte.id_equipo_torneo
                WHERE f.id_torneo = :id_torneo
                  AND UPPER(f.tipo_fase) = 'FASE_DE_GRUPOS'
                ORDER BY g.orden ASC, gte.posicion_inicial ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
        $stmt->execute();

        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $zona = $this->extractZonaFromGroupName((string)$row['grupo_nombre']);
            if ($zona === null) {
                continue;
            }
            $pos = (int)$row['posicion_inicial'];
            $map[$zona][$pos] = (int)$row['id_equipo'];
        }

        ksort($map);
        return $map;
    }

    private function buildSeedList(array $map): array
    {
        $seeds = [];
        foreach ($map as $zona => $positions) {
            ksort($positions);
            foreach ($positions as $idEquipo) {
                $seeds[] = (int)$idEquipo;
            }
        }
        return $seeds;
    }

    private function buildRandomSeedListFromInscriptos(int $idTorneo): array
    {
        $stmt = $this->db->prepare('SELECT id_equipo FROM equipo_torneo WHERE id_torneo = :id_torneo ORDER BY id ASC');
        $stmt->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
        $stmt->execute();
        $seedList = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

        for ($i = count($seedList) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$seedList[$i], $seedList[$j]] = [$seedList[$j], $seedList[$i]];
        }

        return $seedList;
    }

    private function resolveOrigenEquipoId(string $tipo, string $valor, array $map, array $seedList, bool $zonasCerradas): ?int
    {
        if (!$zonasCerradas && ($tipo === 'POSICION_GRUPO' || $tipo === 'SEED_DIRECTO')) {
            return null;
        }

        if ($tipo === 'POSICION_GRUPO' && preg_match('/^(\d+)([A-Z])$/', $valor, $m)) {
            $pos = (int)$m[1];
            $zona = $m[2];
            return isset($map[$zona][$pos]) ? (int)$map[$zona][$pos] : null;
        }

        if ($tipo === 'SEED_DIRECTO') {
            $idx = max(0, ((int)$valor) - 1);
            return $seedList[$idx] ?? null;
        }

        return null;
    }

    private function extractZonaFromGroupName(string $nombre): ?string
    {
        if (preg_match('/([A-Z])\s*$/', strtoupper($nombre), $m)) {
            return $m[1];
        }
        return null;
    }

    private function buildRoundRobinMatches(array $teamIds, bool $idaVuelta): array
    {
        $teams = array_values($teamIds);
        if (count($teams) < 2) {
            return [];
        }

        if ((count($teams) % 2) !== 0) {
            $teams[] = null;
        }

        $n = count($teams);
        $rounds = $n - 1;
        $half = intdiv($n, 2);

        $allMatches = [];
        for ($r = 0; $r < $rounds; $r++) {
            for ($i = 0; $i < $half; $i++) {
                $home = $teams[$i];
                $away = $teams[$n - 1 - $i];

                if ($home !== null && $away !== null) {
                    $allMatches[] = ['local' => (int)$home, 'visitante' => (int)$away];
                }
            }

            $fixed = $teams[0];
            $tail = array_slice($teams, 1);
            $last = array_pop($tail);
            array_unshift($tail, $last);
            $teams = array_merge([$fixed], $tail);
        }

        if ($idaVuelta) {
            $reverse = [];
            foreach ($allMatches as $m) {
                $reverse[] = ['local' => $m['visitante'], 'visitante' => $m['local']];
            }
            $allMatches = array_merge($allMatches, $reverse);
        }

        return $allMatches;
    }

    private function torneoExiste(int $idTorneo): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM torneo WHERE id = :id AND COALESCE(activo, 1) = 1 LIMIT 1');
        $stmt->bindValue(':id', $idTorneo, PDO::PARAM_INT);
        $stmt->execute();
        return (bool)$stmt->fetchColumn();
    }

    private function actualizarTorneoEnCursoSiCorresponde(int $idTorneo): void
    {
        $sql = "UPDATE torneo t
                LEFT JOIN estado_torneo et ON et.id = t.id_estado_torneo
                SET t.id_estado_torneo = :id_estado_en_curso
                WHERE t.id = :id_torneo
                  AND COALESCE(t.activo, 1) = 1
                  AND COALESCE(t.id_estado_torneo, 0) <> :id_estado_en_curso
                  AND UPPER(COALESCE(et.descripcion, '')) NOT IN ('FINALIZADO', 'FINALIZADA', 'CANCELADO', 'CANCELADA')
                  AND EXISTS (
                      SELECT 1
                      FROM evento ev
                      WHERE ev.id_torneo = t.id
                        AND LOWER(ev.tipo_evento) = 'partido'
                        AND ev.fecha_hora_inicio IS NOT NULL
                        AND ev.fecha_hora_inicio <= NOW()
                        AND ev.id_estado_evento <> 1
                  )";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_estado_en_curso', self::ESTADO_TORNEO_EN_CURSO_ID, PDO::PARAM_INT);
        $stmt->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
        $stmt->execute();
    }

    private function crearTorneoDesdePlanificacion(array $torneoNuevo, array $payload): int
    {
        $nombre = trim((string)($torneoNuevo['nombre'] ?? ''));
        if ($nombre === '') {
            $this->respond(400, ['message' => 'torneo_nuevo.nombre es obligatorio.']);
        }

        $idDisciplina = $this->nullableInt($torneoNuevo['id_disciplina'] ?? null);
        if ($idDisciplina === null) {
            $this->respond(400, ['message' => 'torneo_nuevo.id_disciplina es obligatorio.']);
        }

        $descripcion = isset($torneoNuevo['descripcion']) ? trim((string)$torneoNuevo['descripcion']) : null;
        if ($descripcion === '') {
            $descripcion = null;
        }

        $fechaInicio = isset($torneoNuevo['fecha_inicio']) ? trim((string)$torneoNuevo['fecha_inicio']) : null;
        if ($fechaInicio === '') {
            $fechaInicio = date('Y-m-d');
        }

        $valorInscripcion = isset($torneoNuevo['valor_inscripcion'])
            ? (float)$torneoNuevo['valor_inscripcion']
            : 0.0;

        $idEstadoTorneo = $this->resolveEstadoTorneoInicialId();
        $formatoManual = $payload['formato'] ?? ($payload['usa_zonas'] ? 'MIXTO' : 'ELIMINACION');
        $reglamento = isset($torneoNuevo['reglamento']) && trim((string)$torneoNuevo['reglamento']) !== ''
            ? trim((string)$torneoNuevo['reglamento'])
            : ($payload['reglamento'] ?? null);

        $sql = "INSERT INTO torneo (
                    nombre,
                    descripcion,
                    id_disciplina,
                    id_estado_torneo,
                    fecha_inicio,
                    fecha_fin,
                    fecha_fin_planificada,
                    cupo_equipos,
                    valor_inscripcion,
                    formato_manual,
                    configuracion_json,
                    reglamento
                ) VALUES (
                    :nombre,
                    :descripcion,
                    :id_disciplina,
                    :id_estado_torneo,
                    :fecha_inicio,
                    NULL,
                    NULL,
                    :cupo_equipos,
                    :valor_inscripcion,
                    :formato_manual,
                    :configuracion_json,
                    :reglamento
                )";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':nombre', $nombre);
        $stmt->bindValue(':descripcion', $descripcion);
        $stmt->bindValue(':id_disciplina', $idDisciplina, PDO::PARAM_INT);
        $stmt->bindValue(':id_estado_torneo', $idEstadoTorneo, PDO::PARAM_INT);
        $stmt->bindValue(':fecha_inicio', $fechaInicio);
        $stmt->bindValue(':cupo_equipos', (int)$payload['cantidad_equipos'], PDO::PARAM_INT);
        $stmt->bindValue(':valor_inscripcion', $valorInscripcion);
        $stmt->bindValue(':formato_manual', $formatoManual);
        $stmt->bindValue(':configuracion_json', json_encode($payload));
        $stmt->bindValue(':reglamento', $reglamento);
        $stmt->execute();

        return (int)$this->db->lastInsertId();
    }

    private function resolveEstadoTorneoInicialId(): int
    {
        $queries = [
            "SELECT id FROM estado_torneo WHERE UPPER(descripcion) = 'PLANIFICADO' LIMIT 1",
            "SELECT id FROM estado_torneo WHERE UPPER(descripcion) = 'BORRADOR' LIMIT 1",
            "SELECT id FROM estado_torneo ORDER BY id ASC LIMIT 1",
        ];

        foreach ($queries as $query) {
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $id = $stmt->fetchColumn();
            if ($id) {
                return (int)$id;
            }
        }

        $this->respond(400, ['message' => 'No existe un estado_torneo configurado para crear el torneo.']);
        return 0;
    }

    private function existeConfirmadaVigente(int $idTorneo): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM generacion_fixture WHERE id_torneo = :id_torneo AND estado = 'CONFIRMADA' LIMIT 1");
        $stmt->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
        $stmt->execute();
        return (bool)$stmt->fetchColumn();
    }

    private function getEstadoEventoProgramadoId(): int
    {
        return self::ESTADO_EVENTO_PROGRAMADO_ID;
    }

    private function getEstadoEventoProgramacionPendienteId(): int
    {
        return self::ESTADO_EVENTO_PROGRAMACION_PENDIENTE_ID;
    }

    private function insertFase(int $idTorneo, string $nombre, string $tipoFase, int $orden, array $configuracion): int
    {
        $sql = "INSERT INTO fase_torneo (id_torneo, nombre, tipo_fase, orden, configuracion_json)
                VALUES (:id_torneo, :nombre, :tipo_fase, :orden, :configuracion_json)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
        $stmt->bindValue(':nombre', $nombre);
        $stmt->bindValue(':tipo_fase', $tipoFase);
        $stmt->bindValue(':orden', $orden, PDO::PARAM_INT);
        $stmt->bindValue(':configuracion_json', json_encode($configuracion));
        $stmt->execute();

        return (int)$this->db->lastInsertId();
    }

    private function insertGrupo(int $idFaseTorneo, string $nombre, int $orden, int $cantidadEquiposObjetivo, string $criterioAsignacion): int
    {
        $sql = "INSERT INTO grupo_torneo (id_fase_torneo, nombre, orden, cantidad_equipos_objetivo, criterio_asignacion)
                VALUES (:id_fase_torneo, :nombre, :orden, :cantidad_equipos_objetivo, :criterio_asignacion)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_fase_torneo', $idFaseTorneo, PDO::PARAM_INT);
        $stmt->bindValue(':nombre', $nombre);
        $stmt->bindValue(':orden', $orden, PDO::PARAM_INT);
        $stmt->bindValue(':cantidad_equipos_objetivo', $cantidadEquiposObjetivo, PDO::PARAM_INT);
        $stmt->bindValue(':criterio_asignacion', $criterioAsignacion);
        $stmt->execute();

        return (int)$this->db->lastInsertId();
    }

    private function insertEventoPlanificado(
        int $idTorneo,
        int $idEstadoEvento,
        string $titulo,
        string $descripcion,
        int $numeroFecha,
        string $fechaHoraInicio
    ): int {
        $sql = "INSERT INTO evento (
                    id_torneo,
                    id_estado_evento,
                    tipo_evento,
                    titulo,
                    descripcion,
                    numero_fecha,
                    fecha_hora_inicio,
                    fecha_hora_fin,
                    id_cancha,
                    id_arbitro,
                    id_equipo_local,
                    id_equipo_visitante,
                    resultado_local,
                    resultado_visitante,
                    resultado_penales_local,
                    resultado_penales_visitante
                ) VALUES (
                    :id_torneo,
                    :id_estado_evento,
                    'partido',
                    :titulo,
                    :descripcion,
                    :numero_fecha,
                    :fecha_hora_inicio,
                    NULL,
                    NULL,
                    NULL,
                    NULL,
                    NULL,
                    NULL,
                    NULL,
                    NULL,
                    NULL
                )";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
        $stmt->bindValue(':id_estado_evento', $idEstadoEvento, PDO::PARAM_INT);
        $stmt->bindValue(':titulo', $titulo);
        $stmt->bindValue(':descripcion', $descripcion);
        $stmt->bindValue(':numero_fecha', $numeroFecha, PDO::PARAM_INT);
        $stmt->bindValue(':fecha_hora_inicio', $fechaHoraInicio);
        $stmt->execute();

        return (int)$this->db->lastInsertId();
    }

    private function insertCruce(
        int $idFaseTorneo,
        int $idEvento,
        string $nombre,
        int $orden,
        array $origenLocal,
        array $origenVisitante
    ): int {
        $sql = "INSERT INTO cruce_torneo (
                    id_fase_torneo,
                    id_grupo_torneo,
                    id_evento,
                    nombre,
                    orden,
                    origen_local_tipo,
                    origen_local_valor,
                    origen_visitante_tipo,
                    origen_visitante_valor
                ) VALUES (
                    :id_fase_torneo,
                    NULL,
                    :id_evento,
                    :nombre,
                    :orden,
                    :origen_local_tipo,
                    :origen_local_valor,
                    :origen_visitante_tipo,
                    :origen_visitante_valor
                )";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_fase_torneo', $idFaseTorneo, PDO::PARAM_INT);
        $stmt->bindValue(':id_evento', $idEvento, PDO::PARAM_INT);
        $stmt->bindValue(':nombre', $nombre);
        $stmt->bindValue(':orden', $orden, PDO::PARAM_INT);
        $stmt->bindValue(':origen_local_tipo', $origenLocal['tipo']);
        $stmt->bindValue(':origen_local_valor', $origenLocal['valor']);
        $stmt->bindValue(':origen_visitante_tipo', $origenVisitante['tipo']);
        $stmt->bindValue(':origen_visitante_valor', $origenVisitante['valor']);
        $stmt->execute();

        return (int)$this->db->lastInsertId();
    }

    private function parseOrigen(string $texto): array
    {
        $value = trim($texto);

        if (preg_match('/^(\d+)([A-Z])$/', $value, $matches)) {
            return [
                'tipo' => 'POSICION_GRUPO',
                'valor' => $matches[1] . $matches[2],
            ];
        }

        if (preg_match('/^Ganador\s+R(\d+)-P(\d+)$/i', $value, $matches)) {
            return [
                'tipo' => 'GANADOR_CRUCE',
                'valor' => 'R' . $matches[1] . '-P' . $matches[2],
            ];
        }

        if (preg_match('/^Equipo\s+(\d+)$/i', $value, $matches)) {
            return [
                'tipo' => 'SEED_DIRECTO',
                'valor' => (string)$matches[1],
            ];
        }

        if (strcasecmp($value, 'BYE') === 0) {
            return [
                'tipo' => 'BYE',
                'valor' => 'BYE',
            ];
        }

        return [
            'tipo' => 'TBD',
            'valor' => $value !== '' ? $value : 'TBD',
        ];
    }

    private function insertEventosZona(
        int $idTorneo,
        int $idEstadoEvento,
        string $zona,
        int $equiposZona,
        bool $idaVuelta,
        bool $esLiga = false
    ): array {
        $partidosPorFecha = max(1, intdiv($equiposZona, 2));
        $factor = $idaVuelta ? 2 : 1;
        $jornadas = (($equiposZona % 2) === 0 ? ($equiposZona - 1) : $equiposZona) * $factor;
        $totalPartidos = $this->combinatoria2($equiposZona) * $factor;

        $ids = [];
        $creados = 0;

        for ($fecha = 1; $fecha <= $jornadas; $fecha++) {
            for ($i = 1; $i <= $partidosPorFecha; $i++) {
                if ($creados >= $totalPartidos) {
                    break 2;
                }

                $titulo = $esLiga
                    ? 'Liga - Fecha ' . $fecha . ' - Partido ' . $i
                    : 'Zona ' . $zona . ' - Fecha ' . $fecha . ' - Partido ' . $i;
                $fechaHoraInicio = date('Y-m-d H:i:s', strtotime('+' . (($fecha - 1) * 3) . ' days 20:00:00'));

                $ids[] = $this->insertEventoPlanificado(
                    $idTorneo,
                    $idEstadoEvento,
                    $titulo,
                    'Evento de fase de zonas generado automaticamente.',
                    $fecha,
                    $fechaHoraInicio
                );
                $creados++;
            }
        }

        return [$ids, $jornadas];
    }

    private function normalizePayload(array $data): array
    {
        $cantidadEquipos = (int)($data['cantidad_equipos'] ?? 0);
        if ($cantidadEquipos < 2) {
            $this->respond(400, ['message' => 'La cantidad de equipos debe ser al menos 2.']);
        }

        $formato = trim((string)($data['formato'] ?? ''));
        $usaZonas = (bool)($data['usa_zonas'] ?? false);

        // GRUPOS_CON_CONSUELO y LIGA implican siempre usa_zonas
        if ($formato === 'GRUPOS_CON_CONSUELO' || $formato === 'LIGA') {
            $usaZonas = true;
        }

        $idaVuelta = (bool)($data['ida_vuelta_zonas'] ?? false);
        $tercerPuesto = (bool)($data['tercer_puesto'] ?? false);
        $cantidadZonas = $this->nullableInt($data['cantidad_zonas'] ?? null);
        $equiposPorZona = $this->nullableInt($data['equipos_por_zona'] ?? null);
        $clasificadosPorZona = max(1, (int)($data['clasificados_por_zona'] ?? 1));
        $clasificadosConsuelo = max(0, (int)($data['clasificados_consuelo'] ?? 0));
        $llaveConsuelo = $this->nullableInt($data['llave_consuelo'] ?? null);
        $llaveEquipos = $this->nullableInt($data['llave_equipos'] ?? null);
        $reglamento = isset($data['reglamento']) && trim((string)$data['reglamento']) !== ''
            ? trim((string)$data['reglamento'])
            : null;

        if ($formato === '') {
            $formato = $usaZonas ? 'MIXTO' : 'ELIMINACION';
        }

        // LIGA: todos contra todos en una sola zona, sin llave eliminatoria
        if ($formato === 'LIGA') {
            $cantidadZonas = 1;
            $clasificadosPorZona = $cantidadEquipos;
            $clasificadosConsuelo = 0;
            $llaveEquipos = null;
        }

        if ($usaZonas && $formato !== 'LIGA' && $cantidadZonas === null && $equiposPorZona === null) {
            $this->respond(400, ['message' => 'Si usas zonas, debes indicar cantidad de zonas o equipos por zona.']);
        }

        if ($usaZonas && $cantidadZonas !== null && $cantidadZonas < 1) {
            $this->respond(400, ['message' => 'La cantidad de zonas debe ser mayor o igual a 1.']);
        }

        if ($usaZonas && $equiposPorZona !== null && $equiposPorZona < 2) {
            $this->respond(400, ['message' => 'Los equipos por zona deben ser al menos 2.']);
        }

        if ($llaveEquipos !== null && (!$this->isPowerOfTwo($llaveEquipos) || $llaveEquipos < 2)) {
            $this->respond(400, ['message' => 'La llave eliminatoria debe ser potencia de 2 (2, 4, 8, 16...).']);
        }

        if ($formato === 'GRUPOS_CON_CONSUELO' && $clasificadosConsuelo < 1) {
            $this->respond(400, ['message' => 'El formato Grupos con Consuelo requiere clasificados_consuelo >= 1.']);
        }

        if ($llaveEquipos !== null && $formato === 'LIGA') {
            $this->respond(400, ['message' => 'El formato Liga no usa llave eliminatoria.']);
        }

        return [
            'formato' => $formato,
            'cantidad_equipos' => $cantidadEquipos,
            'usa_zonas' => $usaZonas,
            'cantidad_zonas' => $cantidadZonas,
            'equipos_por_zona' => $equiposPorZona,
            'clasificados_por_zona' => $clasificadosPorZona,
            'clasificados_consuelo' => $clasificadosConsuelo,
            'llave_consuelo' => $llaveConsuelo,
            'ida_vuelta_zonas' => $idaVuelta,
            'llave_equipos' => $llaveEquipos,
            'tercer_puesto' => $tercerPuesto,
            'reglamento' => $reglamento,
        ];
    }

    private function calcularFaseZonas(array $payload, array $warnings): array
    {
        $cantidadEquipos = $payload['cantidad_equipos'];
        $cantidadZonas = $payload['cantidad_zonas'];
        $equiposPorZona = $payload['equipos_por_zona'];
        $idaVuelta = $payload['ida_vuelta_zonas'];
        $clasificadosPorZona = $payload['clasificados_por_zona'];

        if ($cantidadZonas === null && $equiposPorZona !== null) {
            $cantidadZonas = (int)ceil($cantidadEquipos / $equiposPorZona);
        }

        if ($cantidadZonas === null) {
            $cantidadZonas = 1;
        }

        if ($cantidadZonas > $cantidadEquipos) {
            $this->respond(400, ['message' => 'No puede haber más zonas que equipos.']);
        }

        $base = intdiv($cantidadEquipos, $cantidadZonas);
        $resto = $cantidadEquipos % $cantidadZonas;

        if ($base < 2) {
            $this->respond(400, ['message' => 'Con esa configuración, al menos una zona tendría menos de 2 equipos.']);
        }

        $zonas = [];
        $minimoPartidosPorEquipo = PHP_INT_MAX;
        $partidosFaseZonas = 0;

        for ($i = 0; $i < $cantidadZonas; $i++) {
            $equiposZona = $base + ($i < $resto ? 1 : 0);
            $partidosZona = $this->combinatoria2($equiposZona) * ($idaVuelta ? 2 : 1);
            $partidosEquipoZona = ($equiposZona - 1) * ($idaVuelta ? 2 : 1);

            if ($clasificadosPorZona >= $equiposZona) {
                $warnings[] = 'La zona ' . $this->zonaLabel($i) . ' clasifica casi todos los equipos; revisa competitividad.';
            }

            $partidosFaseZonas += $partidosZona;
            $minimoPartidosPorEquipo = min($minimoPartidosPorEquipo, $partidosEquipoZona);

            $zonas[] = [
                'zona' => $this->zonaLabel($i),
                'equipos' => $equiposZona,
                'partidos' => $partidosZona,
                'clasificados' => $clasificadosPorZona,
            ];
        }

        return [$zonas, $partidosFaseZonas, $minimoPartidosPorEquipo, $warnings];
    }

    private function calcularLlave(array $payload, array $zonas, array $warnings): array
    {
        $cantidadEquipos = $payload['cantidad_equipos'];
        $usaZonas = $payload['usa_zonas'];
        $llavePedida = $payload['llave_equipos'];
        $tercerPuesto = $payload['tercer_puesto'];

        $maximosDisponibles = $usaZonas
            ? (count($zonas) * max(1, (int)$payload['clasificados_por_zona']))
            : $cantidadEquipos;

        $llaveEquipos = $llavePedida ?? $this->nearestPowerOfTwoLessOrEqual($maximosDisponibles);
        if ($llaveEquipos < 2) {
            $this->respond(400, ['message' => 'No hay suficientes equipos para generar una llave eliminatoria.']);
        }

        if ($llaveEquipos > $maximosDisponibles) {
            $this->respond(400, ['message' => 'La llave solicitada supera los equipos disponibles para clasificar.']);
        }

        if (!$usaZonas && $llaveEquipos < $cantidadEquipos) {
            $warnings[] = 'La llave es menor a la cantidad de equipos; se asume etapa clasificatoria previa o recorte por ranking.';
        }

        $rondas = $this->buildRounds($llaveEquipos);
        $entrantes = $usaZonas
            ? $this->buildEntrantesDesdeZonas($zonas, (int)$payload['clasificados_por_zona'], $llaveEquipos)
            : $this->buildEntrantesDirectos($cantidadEquipos, $llaveEquipos);

        $rondas = $this->asignarPrimeraRonda($rondas, $entrantes);
        $partidosEliminacion = ($llaveEquipos - 1) + ($tercerPuesto ? 1 : 0);

        if ($tercerPuesto) {
            $warnings[] = 'Se agregó partido por tercer puesto.';
        }

        return [
            [
                'equipos' => $llaveEquipos,
                'rondas' => $rondas,
            ],
            $partidosEliminacion,
            $warnings,
        ];
    }

    private function calcularLlaveConsuelo(array $payload, array $zonas, array $warnings): array
    {
        $clasificadosConsuelo = (int)$payload['clasificados_consuelo'];
        $posInicio = (int)$payload['clasificados_por_zona'] + 1;

        $llaveEquipos = $payload['llave_consuelo']
            ?? $this->nearestPowerOfTwoLessOrEqual(count($zonas) * $clasificadosConsuelo);
        if ($llaveEquipos < 2) {
            $warnings[] = 'No hay suficientes equipos para la Rueda Consuelo.';
            return [null, 0, $warnings];
        }

        $rondas = $this->buildRounds($llaveEquipos);
        $entrantes = $this->buildEntrantesConsueloDesdeZonas($zonas, $clasificadosConsuelo, $posInicio, $llaveEquipos);
        $rondas = $this->asignarPrimeraRonda($rondas, $entrantes);
        $partidos = $llaveEquipos - 1;

        return [
            [
                'equipos' => $llaveEquipos,
                'rondas' => $rondas,
            ],
            $partidos,
            $warnings,
        ];
    }

    private function buildEntrantesConsueloDesdeZonas(array $zonas, int $clasificadosConsuelo, int $posInicio, int $llaveEquipos): array
    {
        $entrantes = [];

        for ($i = 0; $i < count($zonas); $i += 2) {
            $zonaA = $zonas[$i]['zona'] ?? null;
            $zonaB = $zonas[$i + 1]['zona'] ?? null;

            if ($zonaA === null) {
                break;
            }
            if ($zonaB === null) {
                $zonaB = $zonaA;
            }

            for ($j = 0; $j < $clasificadosConsuelo; $j++) {
                if (count($entrantes) >= $llaveEquipos) {
                    break 2;
                }
                $posA = $posInicio + $j;
                $posB = $posInicio + ($clasificadosConsuelo - 1 - $j);

                $entrantes[] = $posA . $zonaA;
                if (count($entrantes) >= $llaveEquipos) {
                    break 2;
                }
                $entrantes[] = $posB . $zonaB;
            }
        }

        return $entrantes;
    }

    private function buildRounds(int $llaveEquipos): array
    {
        $rondas = [];
        $equiposEnRonda = $llaveEquipos;
        $offset = 1;

        while ($equiposEnRonda >= 2) {
            $partidos = intdiv($equiposEnRonda, 2);
            $items = [];
            for ($i = 1; $i <= $partidos; $i++) {
                $items[] = [
                    'id' => 'R' . $offset . '-P' . $i,
                    'local' => 'TBD',
                    'visitante' => 'TBD',
                ];
            }

            $rondas[] = [
                'nombre' => $this->nombreRonda($equiposEnRonda),
                'partidos' => $items,
            ];

            $equiposEnRonda = intdiv($equiposEnRonda, 2);
            $offset++;
        }

        for ($i = 1; $i < count($rondas); $i++) {
            foreach ($rondas[$i]['partidos'] as $idx => $partido) {
                $matchA = $idx * 2 + 1;
                $matchB = $idx * 2 + 2;
                $rondas[$i]['partidos'][$idx]['local'] = 'Ganador R' . $i . '-P' . $matchA;
                $rondas[$i]['partidos'][$idx]['visitante'] = 'Ganador R' . $i . '-P' . $matchB;
            }
        }

        return $rondas;
    }

    private function asignarPrimeraRonda(array $rondas, array $entrantes): array
    {
        if (empty($rondas)) {
            return $rondas;
        }

        $primera = $rondas[0]['partidos'];
        $cursor = 0;
        foreach ($primera as $idx => $partido) {
            $rondas[0]['partidos'][$idx]['local'] = $entrantes[$cursor] ?? 'BYE';
            $rondas[0]['partidos'][$idx]['visitante'] = $entrantes[$cursor + 1] ?? 'BYE';
            $cursor += 2;
        }

        return $rondas;
    }

    private function buildEntrantesDesdeZonas(array $zonas, int $clasificadosPorZona, int $llaveEquipos): array
    {
        $entrantes = [];

        // Cruce por ranking entre pares de zonas: 1A vs nB, 2A vs (n-1)B, etc.
        for ($i = 0; $i < count($zonas); $i += 2) {
            $zonaA = $zonas[$i]['zona'] ?? null;
            $zonaB = $zonas[$i + 1]['zona'] ?? null;

            if ($zonaA === null) {
                break;
            }

            // Si queda una zona sin pareja, se la empareja consigo misma para no romper la simulación.
            if ($zonaB === null) {
                $zonaB = $zonaA;
            }

            for ($pos = 1; $pos <= $clasificadosPorZona; $pos++) {
                if (count($entrantes) >= $llaveEquipos) {
                    break 2;
                }

                $opuesto = ($clasificadosPorZona - $pos + 1);
                $entrantes[] = $pos . $zonaA;

                if (count($entrantes) >= $llaveEquipos) {
                    break 2;
                }

                $entrantes[] = $opuesto . $zonaB;
            }
        }

        return $entrantes;
    }

    private function buildEntrantesDirectos(int $cantidadEquipos, int $llaveEquipos): array
    {
        $entrantes = [];
        $limit = min($cantidadEquipos, $llaveEquipos);
        for ($i = 1; $i <= $limit; $i++) {
            $entrantes[] = 'Equipo ' . $i;
        }

        return $entrantes;
    }

    private function nombreRonda(int $equipos): string
    {
        return match ($equipos) {
            2 => 'Final',
            4 => 'Semifinal',
            8 => 'Cuartos de final',
            16 => 'Octavos de final',
            32 => 'Dieciseisavos',
            default => 'Ronda de ' . $equipos,
        };
    }

    private function zonaLabel(int $index): string
    {
        return chr(65 + $index);
    }

    private function combinatoria2(int $n): int
    {
        return (int)(($n * ($n - 1)) / 2);
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (int)$value;
    }

    private function isPowerOfTwo(int $n): bool
    {
        return $n > 0 && ($n & ($n - 1)) === 0;
    }

    private function nearestPowerOfTwoLessOrEqual(int $n): int
    {
        $value = 1;
        while (($value * 2) <= $n) {
            $value *= 2;
        }
        return $value;
    }
}
