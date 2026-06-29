<?php

declare(strict_types=1);

class Torneo
{
    private const ESTADO_TORNEO_EN_CURSO_ID = 4;
    private PDO $conn;
    public string $table = 'torneo';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getAll(): array
    {
        $this->actualizarTorneosEnCursoSiCorresponde();

        $sql = "SELECT t.id, t.nombre, t.descripcion, t.id_disciplina, t.id_estado_torneo,
                       t.fecha_inicio, t.fecha_fin, t.fecha_fin_planificada, t.cupo_equipos,
                       t.valor_inscripcion, t.formato_manual, t.configuracion_json,
                       COALESCE(t.activo, 1) AS activo,
                       d.nombre AS disciplina_nombre,
                       et.descripcion AS estado_torneo_descripcion,
                       COALESCE(ie.solicitudes_pendientes, 0) AS solicitudes_pendientes
                FROM {$this->table} t
                LEFT JOIN disciplina d ON t.id_disciplina = d.id
                LEFT JOIN estado_torneo et ON t.id_estado_torneo = et.id
                LEFT JOIN (
                    SELECT id_torneo, COUNT(*) AS solicitudes_pendientes
                    FROM inscripcion_equipo
                    WHERE id_estado = 1
                    GROUP BY id_torneo
                ) ie ON ie.id_torneo = t.id
                WHERE COALESCE(t.activo, 1) = 1
                ORDER BY t.nombre ASC, t.id ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $this->actualizarTorneosEnCursoSiCorresponde();

        $sql = "SELECT t.id, t.nombre, t.descripcion, t.id_disciplina, t.id_estado_torneo,
                       t.fecha_inicio, t.fecha_fin, t.fecha_fin_planificada, t.cupo_equipos,
                       t.valor_inscripcion, t.formato_manual, t.configuracion_json,
                       COALESCE(t.activo, 1) AS activo,
                       d.nombre AS disciplina_nombre,
                       et.descripcion AS estado_torneo_descripcion
                FROM {$this->table} t
                LEFT JOIN disciplina d ON t.id_disciplina = d.id
                LEFT JOIN estado_torneo et ON t.id_estado_torneo = et.id
                WHERE t.id = :id
                  AND COALESCE(t.activo, 1) = 1
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    private function actualizarTorneosEnCursoSiCorresponde(): void
    {
        $sql = "UPDATE torneo t
                LEFT JOIN estado_torneo et ON et.id = t.id_estado_torneo
                SET t.id_estado_torneo = :id_estado_en_curso
                WHERE COALESCE(t.activo, 1) = 1
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

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_estado_en_curso', self::ESTADO_TORNEO_EN_CURSO_ID, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function getDashboardById(int $idTorneo): ?array
    {
        $torneo = $this->getById($idTorneo);
        if (!$torneo) {
            return null;
        }

        $stmtFases = $this->conn->prepare("SELECT id, nombre, tipo_fase, orden
                                          FROM fase_torneo
                                          WHERE id_torneo = :id_torneo
                                          ORDER BY orden ASC, id ASC");
        $stmtFases->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
        $stmtFases->execute();
        $fases = $stmtFases->fetchAll(PDO::FETCH_ASSOC);

        $stmtEstados = $this->conn->prepare("SELECT ev.id_estado_evento,
                                                    ee.descripcion AS estado,
                                                    COUNT(*) AS cantidad
                                             FROM evento ev
                                             LEFT JOIN estado_evento ee ON ee.id = ev.id_estado_evento
                                             WHERE ev.id_torneo = :id_torneo
                                               AND LOWER(ev.tipo_evento) = 'partido'
                                             GROUP BY ev.id_estado_evento, ee.descripcion
                                             ORDER BY ev.id_estado_evento ASC");
        $stmtEstados->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
        $stmtEstados->execute();
        $estadoPartidos = $stmtEstados->fetchAll(PDO::FETCH_ASSOC);

        $stmtUltimos = $this->conn->prepare("SELECT ev.id, ev.titulo, ev.numero_fecha, ev.fecha_hora_inicio,
                                                    ev.id_estado_evento, ee.descripcion AS estado,
                                                    ev.id_equipo_local, el.nombre AS equipo_local_nombre, el.escudo AS equipo_local_escudo,
                                                    ev.id_equipo_visitante, evt.nombre AS equipo_visitante_nombre, evt.escudo AS equipo_visitante_escudo,
                                                    ev.resultado_local, ev.resultado_visitante,
                                                    ev.resultado_penales_local, ev.resultado_penales_visitante
                                             FROM evento ev
                                             LEFT JOIN estado_evento ee ON ee.id = ev.id_estado_evento
                                             LEFT JOIN equipo el ON el.id = ev.id_equipo_local
                                             LEFT JOIN equipo evt ON evt.id = ev.id_equipo_visitante
                                             WHERE ev.id_torneo = :id_torneo
                                               AND LOWER(ev.tipo_evento) = 'partido'
                                               AND ev.id_estado_evento IN (4, 7)
                                             ORDER BY COALESCE(ev.fecha_hora_fin, ev.fecha_hora_inicio) DESC, ev.id DESC
                                             LIMIT 8");
        $stmtUltimos->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
        $stmtUltimos->execute();
        $ultimosResultados = $stmtUltimos->fetchAll(PDO::FETCH_ASSOC);

        $stmtEventosResumen = $this->conn->prepare("SELECT id, titulo, numero_fecha, id_estado_evento
                                                    FROM evento
                                                    WHERE id_torneo = :id_torneo
                                                      AND LOWER(tipo_evento) = 'partido'
                                                    ORDER BY numero_fecha ASC, id ASC");
        $stmtEventosResumen->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
        $stmtEventosResumen->execute();
        $eventosResumen = $stmtEventosResumen->fetchAll(PDO::FETCH_ASSOC);

        $totZona = 0;
        $pendZona = 0;
        $totElim = 0;
        $pendElim = 0;
        foreach ($eventosResumen as $ev) {
            $isZona = preg_match('/^Zona\s+/i', (string)($ev['titulo'] ?? '')) === 1;
            $estado = (int)($ev['id_estado_evento'] ?? 0);
            $isPendiente = !in_array($estado, [4, 7], true);

            if ($isZona) {
                $totZona++;
                if ($isPendiente) {
                    $pendZona++;
                }
            } else {
                $totElim++;
                if ($isPendiente) {
                    $pendElim++;
                }
            }
        }

        $faseActual = null;
        if ($pendZona > 0) {
            $faseActual = 'Fase de grupos';
        } elseif ($pendElim > 0) {
            $faseActual = 'Eliminación directa';
        } elseif ($totElim > 0) {
            $faseActual = 'Eliminación directa';
        } elseif ($totZona > 0) {
            $faseActual = 'Fase de grupos';
        } elseif (!empty($fases)) {
            $faseActual = (string)($fases[0]['nombre'] ?? null);
        }

        $stmtZonasEquipos = $this->conn->prepare("SELECT g.id AS id_grupo_torneo, g.nombre AS grupo_nombre,
                                                         et.id_equipo, e.nombre AS equipo_nombre, e.escudo
                                                  FROM grupo_torneo g
                                                  INNER JOIN fase_torneo f ON f.id = g.id_fase_torneo
                                                  LEFT JOIN grupo_torneo_equipo gte ON gte.id_grupo_torneo = g.id
                                                  LEFT JOIN equipo_torneo et ON et.id = gte.id_equipo_torneo
                                                  LEFT JOIN equipo e ON e.id = et.id_equipo
                                                  WHERE f.id_torneo = :id_torneo
                                                    AND UPPER(f.tipo_fase) IN ('FASE_DE_GRUPOS', 'LIGA')
                                                  ORDER BY g.orden ASC, g.id ASC, gte.posicion_inicial ASC");
        $stmtZonasEquipos->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
        $stmtZonasEquipos->execute();
        $rowsZonasEquipos = $stmtZonasEquipos->fetchAll(PDO::FETCH_ASSOC);

        $zonas = [];
        $zonaByKey = [];
        foreach ($rowsZonasEquipos as $row) {
            $idGrupo = (int)($row['id_grupo_torneo'] ?? 0);
            if ($idGrupo <= 0) {
                continue;
            }
            if (!isset($zonas[$idGrupo])) {
                $nombreZona = (string)($row['grupo_nombre'] ?? ('Zona ' . $idGrupo));
                $zonas[$idGrupo] = [
                    'id_grupo_torneo' => $idGrupo,
                    'nombre' => $nombreZona,
                    'zona_key' => $this->extractZonaKey($nombreZona),
                    'equipos' => [],
                ];
                $zonaByKey[$zonas[$idGrupo]['zona_key']] = $idGrupo;
            }

            $idEquipo = isset($row['id_equipo']) ? (int)$row['id_equipo'] : 0;
            if ($idEquipo <= 0) {
                continue;
            }

            $zonas[$idGrupo]['equipos'][$idEquipo] = [
                'id' => $idEquipo,
                'nombre' => (string)($row['equipo_nombre'] ?? ('Equipo ' . $idEquipo)),
                'escudo' => $row['escudo'] ?? null,
                'pj' => 0,
                'pg' => 0,
                'pe' => 0,
                'pp' => 0,
                'gf' => 0,
                'gc' => 0,
                'dif' => 0,
                'pts' => 0,
            ];
        }

        // Detectar si es formato Liga (una sola zona sin zona_key por título)
        $esFormatoLiga = ($torneo['formato_manual'] ?? '') === 'LIGA';

        // Para LIGA: traer eventos por id_equipo directamente (sin depender del título)
        // Para zonas: traer por título con prefijo "Zona X"
        $stmtEventosZona = $this->conn->prepare("SELECT ev.id, ev.titulo, ev.id_equipo_local, ev.id_equipo_visitante,
                                                        ev.resultado_local, ev.resultado_visitante, ev.id_estado_evento,
                                                        gte_l.id_grupo_torneo AS id_grupo_local,
                                                        gte_v.id_grupo_torneo AS id_grupo_visitante
                                                 FROM evento ev
                                                 LEFT JOIN grupo_torneo_equipo gte_l ON gte_l.id_equipo_torneo = (
                                                     SELECT et.id FROM equipo_torneo et WHERE et.id_equipo = ev.id_equipo_local AND et.id_torneo = ev.id_torneo LIMIT 1
                                                 )
                                                 LEFT JOIN grupo_torneo_equipo gte_v ON gte_v.id_equipo_torneo = (
                                                     SELECT et.id FROM equipo_torneo et WHERE et.id_equipo = ev.id_equipo_visitante AND et.id_torneo = ev.id_torneo LIMIT 1
                                                 )
                                                 WHERE ev.id_torneo = :id_torneo
                                                   AND LOWER(ev.tipo_evento) = 'partido'
                                                   AND ev.id_estado_evento IN (4, 7)
                                                   AND ev.resultado_local IS NOT NULL
                                                   AND ev.resultado_visitante IS NOT NULL
                                                   AND (ev.titulo LIKE 'Zona %' OR ev.titulo LIKE 'Liga %')");
        $stmtEventosZona->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
        $stmtEventosZona->execute();
        $eventosZona = $stmtEventosZona->fetchAll(PDO::FETCH_ASSOC);

        foreach ($eventosZona as $ev) {
            if ($esFormatoLiga) {
                // Para LIGA: el único grupo es el primero disponible
                $idGrupo = !empty($zonas) ? array_key_first($zonas) : null;
                if ($idGrupo === null) continue;
            } else {
                $zonaKey = $this->extractZonaKey((string)($ev['titulo'] ?? ''));
                if (!isset($zonaByKey[$zonaKey])) {
                    continue;
                }
                $idGrupo = $zonaByKey[$zonaKey];
            }
            $idLocal = isset($ev['id_equipo_local']) ? (int)$ev['id_equipo_local'] : 0;
            $idVisitante = isset($ev['id_equipo_visitante']) ? (int)$ev['id_equipo_visitante'] : 0;
            $golLocal = (int)($ev['resultado_local'] ?? 0);
            $golVisitante = (int)($ev['resultado_visitante'] ?? 0);

            if ($idLocal > 0 && isset($zonas[$idGrupo]['equipos'][$idLocal])) {
                $zonas[$idGrupo]['equipos'][$idLocal]['pj']++;
                $zonas[$idGrupo]['equipos'][$idLocal]['gf'] += $golLocal;
                $zonas[$idGrupo]['equipos'][$idLocal]['gc'] += $golVisitante;
            }
            if ($idVisitante > 0 && isset($zonas[$idGrupo]['equipos'][$idVisitante])) {
                $zonas[$idGrupo]['equipos'][$idVisitante]['pj']++;
                $zonas[$idGrupo]['equipos'][$idVisitante]['gf'] += $golVisitante;
                $zonas[$idGrupo]['equipos'][$idVisitante]['gc'] += $golLocal;
            }

            if ($golLocal > $golVisitante) {
                if ($idLocal > 0 && isset($zonas[$idGrupo]['equipos'][$idLocal])) {
                    $zonas[$idGrupo]['equipos'][$idLocal]['pg']++;
                    $zonas[$idGrupo]['equipos'][$idLocal]['pts'] += 3;
                }
                if ($idVisitante > 0 && isset($zonas[$idGrupo]['equipos'][$idVisitante])) {
                    $zonas[$idGrupo]['equipos'][$idVisitante]['pp']++;
                }
            } elseif ($golLocal < $golVisitante) {
                if ($idVisitante > 0 && isset($zonas[$idGrupo]['equipos'][$idVisitante])) {
                    $zonas[$idGrupo]['equipos'][$idVisitante]['pg']++;
                    $zonas[$idGrupo]['equipos'][$idVisitante]['pts'] += 3;
                }
                if ($idLocal > 0 && isset($zonas[$idGrupo]['equipos'][$idLocal])) {
                    $zonas[$idGrupo]['equipos'][$idLocal]['pp']++;
                }
            } else {
                if ($idLocal > 0 && isset($zonas[$idGrupo]['equipos'][$idLocal])) {
                    $zonas[$idGrupo]['equipos'][$idLocal]['pe']++;
                    $zonas[$idGrupo]['equipos'][$idLocal]['pts'] += 1;
                }
                if ($idVisitante > 0 && isset($zonas[$idGrupo]['equipos'][$idVisitante])) {
                    $zonas[$idGrupo]['equipos'][$idVisitante]['pe']++;
                    $zonas[$idGrupo]['equipos'][$idVisitante]['pts'] += 1;
                }
            }
        }

        $zonasList = [];
        foreach ($zonas as $zona) {
            $equiposZona = array_values($zona['equipos']);
            foreach ($equiposZona as &$eq) {
                $eq['dif'] = $eq['gf'] - $eq['gc'];
            }
            unset($eq);

            usort($equiposZona, static function (array $a, array $b): int {
                if ((int)$b['pts'] !== (int)$a['pts']) {
                    return (int)$b['pts'] <=> (int)$a['pts'];
                }
                if ((int)$b['dif'] !== (int)$a['dif']) {
                    return (int)$b['dif'] <=> (int)$a['dif'];
                }
                if ((int)$b['gf'] !== (int)$a['gf']) {
                    return (int)$b['gf'] <=> (int)$a['gf'];
                }
                return strcasecmp((string)$a['nombre'], (string)$b['nombre']);
            });

            $zonasList[] = [
                'id_grupo_torneo' => $zona['id_grupo_torneo'],
                'nombre' => $zona['nombre'],
                'equipos' => $equiposZona,
            ];
        }

        $stmtLlave = $this->conn->prepare("SELECT c.id, c.nombre, c.orden,
                                                  c.origen_local_tipo, c.origen_local_valor,
                                                  c.origen_visitante_tipo, c.origen_visitante_valor,
                                                  f.tipo_fase,
                                                  ev.id AS id_evento, ev.titulo, ev.numero_fecha, ev.id_estado_evento,
                                                  ee.descripcion AS estado,
                                                  ev.id_equipo_local, el.nombre AS equipo_local_nombre, el.escudo AS equipo_local_escudo,
                                                  ev.id_equipo_visitante, evt.nombre AS equipo_visitante_nombre, evt.escudo AS equipo_visitante_escudo,
                                                  ev.resultado_local, ev.resultado_visitante,
                                                  ev.resultado_penales_local, ev.resultado_penales_visitante
                                           FROM cruce_torneo c
                                           INNER JOIN fase_torneo f ON f.id = c.id_fase_torneo
                                           INNER JOIN evento ev ON ev.id = c.id_evento
                                           LEFT JOIN estado_evento ee ON ee.id = ev.id_estado_evento
                                           LEFT JOIN equipo el ON el.id = ev.id_equipo_local
                                           LEFT JOIN equipo evt ON evt.id = ev.id_equipo_visitante
                                           WHERE f.id_torneo = :id_torneo
                                           ORDER BY ev.numero_fecha ASC, c.orden ASC, c.id ASC");
        $stmtLlave->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
        $stmtLlave->execute();
        $cruces = $stmtLlave->fetchAll(PDO::FETCH_ASSOC);

        $crucesGanadores = array_filter($cruces, fn($c) => ($c['tipo_fase'] ?? '') !== 'RUEDA_CONSUELO');
        $crucesConsuelo  = array_filter($cruces, fn($c) => ($c['tipo_fase'] ?? '') === 'RUEDA_CONSUELO');

        $llave         = $this->buildLlaveFromCruces(array_values($crucesGanadores));
        $llaveConsuelo = $this->buildLlaveFromCruces(array_values($crucesConsuelo));

        $totalPartidos = 0;
        $totalFinalizados = 0;
        foreach ($estadoPartidos as $row) {
            $cant = (int)($row['cantidad'] ?? 0);
            $totalPartidos += $cant;
            if (in_array((int)($row['id_estado_evento'] ?? 0), [4, 7], true)) {
                $totalFinalizados += $cant;
            }
        }

        // Para LIGA: tabla_liga es la zona única (todos los equipos juntos)
        $tablaLiga = null;
        if ($esFormatoLiga && !empty($zonasList)) {
            $tablaLiga = $zonasList[0]['equipos'] ?? [];
        }

        return [
            'torneo' => $torneo,
            'fases' => $fases,
            'fase_actual' => $esFormatoLiga ? 'Liga' : $faseActual,
            'resumen' => [
                'total_partidos' => $totalPartidos,
                'partidos_finalizados' => $totalFinalizados,
                'partidos_pendientes' => max(0, $totalPartidos - $totalFinalizados),
                'eventos_zona_total' => $totZona,
                'eventos_eliminacion_total' => $esFormatoLiga ? 0 : $totElim,
            ],
            'estado_partidos' => $estadoPartidos,
            'ultimos_resultados' => $ultimosResultados,
            'zonas' => $esFormatoLiga ? [] : $zonasList,
            'tabla_liga' => $tablaLiga,
            'llave' => $esFormatoLiga ? [] : $llave,
            'llave_consuelo' => $esFormatoLiga ? [] : $llaveConsuelo,
        ];
    }

    private function buildLlaveFromCruces(array $cruces): array
    {
        $minFecha = null;
        foreach ($cruces as $c) {
            $num = (int)($c['numero_fecha'] ?? 0);
            if ($num > 0 && ($minFecha === null || $num < $minFecha)) {
                $minFecha = $num;
            }
        }

        $byRound = [];
        foreach ($cruces as $c) {
            $numFecha = (int)($c['numero_fecha'] ?? 0);
            $round = ($minFecha !== null && $numFecha > 0) ? ($numFecha - $minFecha) + 1 : 1;

            if (!isset($byRound[$round])) {
                $byRound[$round] = ['round' => $round, 'nombre' => 'Ronda ' . $round, 'partidos' => []];
            }

            $byRound[$round]['partidos'][] = [
                'id_cruce'        => (int)$c['id'],
                'nombre'          => $c['nombre'],
                'orden'           => (int)($c['orden'] ?? 0),
                'id_evento'       => (int)$c['id_evento'],
                'titulo'          => $c['titulo'],
                'estado'          => $c['estado'],
                'id_estado_evento'=> (int)($c['id_estado_evento'] ?? 0),
                'equipo_local' => [
                    'id'        => isset($c['id_equipo_local']) ? (int)$c['id_equipo_local'] : null,
                    'nombre'    => $c['equipo_local_nombre'] ?? null,
                    'escudo'    => $c['equipo_local_escudo'] ?? null,
                    'resultado' => isset($c['resultado_local']) ? (int)$c['resultado_local'] : null,
                    'penales'   => isset($c['resultado_penales_local']) ? (int)$c['resultado_penales_local'] : null,
                ],
                'equipo_visitante' => [
                    'id'        => isset($c['id_equipo_visitante']) ? (int)$c['id_equipo_visitante'] : null,
                    'nombre'    => $c['equipo_visitante_nombre'] ?? null,
                    'escudo'    => $c['equipo_visitante_escudo'] ?? null,
                    'resultado' => isset($c['resultado_visitante']) ? (int)$c['resultado_visitante'] : null,
                    'penales'   => isset($c['resultado_penales_visitante']) ? (int)$c['resultado_penales_visitante'] : null,
                ],
                'origen_local'     => ['tipo' => $c['origen_local_tipo'],     'valor' => $c['origen_local_valor']],
                'origen_visitante' => ['tipo' => $c['origen_visitante_tipo'],  'valor' => $c['origen_visitante_valor']],
            ];
        }

        ksort($byRound);
        return array_values($byRound);
    }

    private function extractZonaKey(string $texto): string
    {
        if (preg_match('/\bZona\s+([A-Z0-9]+)/i', $texto, $m)) {
            return strtoupper((string)$m[1]);
        }

        if (preg_match('/([A-Z0-9])\s*$/i', trim($texto), $m)) {
            return strtoupper((string)$m[1]);
        }

        return strtoupper(trim($texto));
    }

    public function exists(int $id, bool $onlyActive = true): bool
    {
        $sql = "SELECT 1 FROM {$this->table} WHERE id = :id";
        if ($onlyActive) {
            $sql .= " AND COALESCE(activo, 1) = 1";
        }
        $sql .= " LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return (bool)$stmt->fetchColumn();
    }

    public function softDelete(int $idTorneo, ?int $deletedBy = null, ?string $motivo = null): bool
    {
        $sql = "UPDATE {$this->table}
                SET activo = 0,
                    deleted_at = NOW(),
                    deleted_by = :deleted_by,
                    motivo_baja = :motivo_baja
                WHERE id = :id
                  AND COALESCE(activo, 1) = 1";
        $stmt = $this->conn->prepare($sql);
        if ($deletedBy !== null) {
            $stmt->bindValue(':deleted_by', $deletedBy, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':deleted_by', null, PDO::PARAM_NULL);
        }
        if ($motivo !== null && trim($motivo) !== '') {
            $stmt->bindValue(':motivo_baja', trim($motivo));
        } else {
            $stmt->bindValue(':motivo_baja', null, PDO::PARAM_NULL);
        }
        $stmt->bindValue(':id', $idTorneo, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function deleteCascade(int $idTorneo): array
    {
        $this->conn->beginTransaction();
        try {
            $idsFase = $this->getIdsFase($idTorneo);
            $idsGrupo = $this->getIdsGrupo($idsFase);
            $idsEvento = $this->getIdsEvento($idTorneo);

            $deleted = [
                'evento_partido' => 0,
                'estado_evento_hist' => 0,
                'cruce_torneo' => 0,
                'grupo_torneo_equipo' => 0,
                'grupo_torneo' => 0,
                'fase_torneo' => 0,
                'evento' => 0,
                'equipo_torneo' => 0,
                'estado_torneo_hist' => 0,
                'generacion_fixture' => 0,
                'torneo' => 0,
            ];

            if (!empty($idsEvento)) {
                $deleted['evento_partido'] = $this->deleteIn('evento_partido', 'id_evento', $idsEvento);
                $deleted['estado_evento_hist'] = $this->deleteIn('estado_evento_hist', 'id_evento', $idsEvento);
            }

            if (!empty($idsFase) || !empty($idsEvento)) {
                $deleted['cruce_torneo'] = $this->deleteCruces($idsFase, $idsEvento);
            }

            if (!empty($idsGrupo)) {
                $deleted['grupo_torneo_equipo'] = $this->deleteIn('grupo_torneo_equipo', 'id_grupo_torneo', $idsGrupo);
                $deleted['grupo_torneo'] = $this->deleteIn('grupo_torneo', 'id', $idsGrupo);
            }

            if (!empty($idsFase)) {
                $deleted['fase_torneo'] = $this->deleteIn('fase_torneo', 'id', $idsFase);
            }

            $deleted['evento'] = $this->deleteByTournament('evento', $idTorneo);
            $deleted['equipo_torneo'] = $this->deleteByTournament('equipo_torneo', $idTorneo);
            $deleted['estado_torneo_hist'] = $this->deleteByTournament('estado_torneo_hist', $idTorneo);
            $deleted['generacion_fixture'] = $this->deleteByTournament('generacion_fixture', $idTorneo);
            $deleted['torneo'] = $this->deleteByTournament($this->table, $idTorneo);

            $this->conn->commit();
            return $deleted;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    private function getIdsFase(int $idTorneo): array
    {
        $stmt = $this->conn->prepare('SELECT id FROM fase_torneo WHERE id_torneo = :id_torneo');
        $stmt->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
        $stmt->execute();
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    private function getIdsGrupo(array $idsFase): array
    {
        if (empty($idsFase)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($idsFase), '?'));
        $stmt = $this->conn->prepare("SELECT id FROM grupo_torneo WHERE id_fase_torneo IN ($placeholders)");
        foreach ($idsFase as $i => $idFase) {
            $stmt->bindValue($i + 1, $idFase, PDO::PARAM_INT);
        }
        $stmt->execute();
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    private function getIdsEvento(int $idTorneo): array
    {
        $stmt = $this->conn->prepare('SELECT id FROM evento WHERE id_torneo = :id_torneo');
        $stmt->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
        $stmt->execute();
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    private function deleteByTournament(string $table, int $idTorneo): int
    {
        $stmt = $this->conn->prepare("DELETE FROM {$table} WHERE id_torneo = :id_torneo");
        $stmt->bindValue(':id_torneo', $idTorneo, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount();
    }

    private function deleteIn(string $table, string $column, array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->conn->prepare("DELETE FROM {$table} WHERE {$column} IN ($placeholders)");
        foreach ($ids as $i => $id) {
            $stmt->bindValue($i + 1, $id, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->rowCount();
    }

    private function deleteCruces(array $idsFase, array $idsEvento): int
    {
        $conditions = [];
        $params = [];

        if (!empty($idsFase)) {
            $conditions[] = 'id_fase_torneo IN (' . implode(',', array_fill(0, count($idsFase), '?')) . ')';
            $params = array_merge($params, $idsFase);
        }

        if (!empty($idsEvento)) {
            $conditions[] = 'id_evento IN (' . implode(',', array_fill(0, count($idsEvento), '?')) . ')';
            $params = array_merge($params, $idsEvento);
        }

        if (empty($conditions)) {
            return 0;
        }

        $sql = 'DELETE FROM cruce_torneo WHERE ' . implode(' OR ', $conditions);
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $i => $param) {
            $stmt->bindValue($i + 1, $param, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->rowCount();
    }
}