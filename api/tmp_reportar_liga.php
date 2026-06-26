<?php
set_time_limit(300);
$db = new PDO('mysql:host=67.225.220.9;dbname=impactos_Il_Calcio_Camp;charset=utf8mb4', 'impactos_Il_Calcio_Camp', '_G#66zp!Qn7^zYU^', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$ID_TORNEO   = 4;
$MAX_FECHA   = 10;
$ESTADO_FINALIZADO_REPORTADO = 7;
$TIPO_GOL    = 1;
$TIPO_AM     = 2;
$TIPO_ROJA   = 3;

// Obtener partidos hasta fecha 10, con equipos asignados
$stmt = $db->prepare("
    SELECT e.id, e.id_equipo_local, e.id_equipo_visitante, e.numero_fecha
    FROM evento e
    WHERE e.id_torneo = ?
      AND LOWER(e.tipo_evento) = 'partido'
      AND e.numero_fecha <= ?
      AND e.id_equipo_local IS NOT NULL
      AND e.id_equipo_visitante IS NOT NULL
      AND e.id_estado_evento != ?
    ORDER BY e.numero_fecha ASC, e.id ASC
");
$stmt->execute([$ID_TORNEO, $MAX_FECHA, $ESTADO_FINALIZADO_REPORTADO]);
$partidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Partidos a reportar: " . count($partidos) . "\n";

// Helper: jugadores de un equipo en este torneo
function getJugadores(PDO $db, int $idEquipo, int $idTorneo): array {
    static $cache = [];
    $key = $idEquipo . '_' . $idTorneo;
    if (!isset($cache[$key])) {
        $st = $db->prepare("
            SELECT j.id FROM jugador j
            INNER JOIN jugador_equipo je ON je.id_jugador = j.id
            WHERE je.id_equipo = ?
            LIMIT 20
        ");
        $st->execute([$idEquipo]);
        $cache[$key] = $st->fetchAll(PDO::FETCH_COLUMN);
    }
    return $cache[$key];
}

function rnd(int $min, int $max): int { return random_int($min, $max); }

$reportados = 0;
$errores    = 0;

foreach ($partidos as $p) {
    $idEvento   = (int)$p['id'];
    $idLocal    = (int)$p['id_equipo_local'];
    $idVisit    = (int)$p['id_equipo_visitante'];

    $golesL = rnd(0, 4);
    $golesV = rnd(0, 4);

    try {
        $db->beginTransaction();

        // Borrar incidencias previas
        $db->prepare("DELETE FROM evento_partido WHERE id_evento = ?")->execute([$idEvento]);

        // Determinar ganador
        if ($golesL > $golesV)      $idGanador = $idLocal;
        elseif ($golesV > $golesL)  $idGanador = $idVisit;
        else                         $idGanador = null;

        // Actualizar resultado y estado
        $db->prepare("
            UPDATE evento
            SET resultado_local = ?, resultado_visitante = ?, id_estado_evento = ?
            WHERE id = ?
        ")->execute([$golesL, $golesV, $ESTADO_FINALIZADO_REPORTADO, $idEvento]);

        $insInc = $db->prepare("
            INSERT INTO evento_partido (id_evento, id_tipo_evento_partido, id_jugador, id_equipo, minuto)
            VALUES (?, ?, ?, ?, ?)
        ");

        // Insertar incidencias de gol
        foreach ([[$idLocal, $golesL], [$idVisit, $golesV]] as [$idEq, $goles]) {
            $jugs = getJugadores($db, $idEq, $ID_TORNEO);
            for ($i = 0; $i < $goles; $i++) {
                $idJug = !empty($jugs) ? $jugs[array_rand($jugs)] : null;
                $insInc->execute([$idEvento, $TIPO_GOL, $idJug, $idEq, rnd(1, 90)]);
            }
        }

        // Amarillas: 0-2 por equipo
        foreach ([$idLocal, $idVisit] as $idEq) {
            $jugs = getJugadores($db, $idEq, $ID_TORNEO);
            $cant = rnd(0, 2);
            for ($i = 0; $i < $cant; $i++) {
                $idJug = !empty($jugs) ? $jugs[array_rand($jugs)] : null;
                $insInc->execute([$idEvento, $TIPO_AM, $idJug, $idEq, rnd(1, 90)]);
            }
        }

        // Roja: 20% de chance por equipo
        foreach ([$idLocal, $idVisit] as $idEq) {
            if (rnd(1, 5) === 1) {
                $jugs = getJugadores($db, $idEq, $ID_TORNEO);
                $idJug = !empty($jugs) ? $jugs[array_rand($jugs)] : null;
                $insInc->execute([$idEvento, $TIPO_ROJA, $idJug, $idEq, rnd(30, 90)]);
            }
        }

        $db->commit();

        echo "✅ [Fecha {$p['numero_fecha']}] Evento $idEvento → $golesL-$golesV\n";
        $reportados++;

    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        echo "❌ Evento $idEvento → " . $e->getMessage() . "\n";
        $errores++;
    }
}

echo "\nListo: $reportados reportados, $errores errores.\n";
