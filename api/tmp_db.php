<?php
$db = new PDO('mysql:host=67.225.220.9;dbname=impactos_Il_Calcio_Camp;charset=utf8mb4', 'impactos_Il_Calcio_Camp', '_G#66zp!Qn7^zYU^', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// Ver torneos
$torneos = $db->query("SELECT id, nombre, formato_manual, activo FROM torneo ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
echo "=== TORNEOS ===\n";
foreach ($torneos as $t) echo "ID:{$t['id']} | {$t['nombre']} | {$t['formato_manual']} | activo:{$t['activo']}\n";

// Ver equipos inscriptos en cada torneo
echo "\n=== EQUIPOS POR TORNEO ===\n";
$rows = $db->query("SELECT et.id_torneo, t.nombre AS torneo, e.id AS id_equipo, e.nombre AS equipo, et.confirmar
                    FROM equipo_torneo et
                    JOIN equipo e ON e.id = et.id_equipo
                    JOIN torneo t ON t.id = et.id_torneo
                    ORDER BY et.id_torneo, e.nombre")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) echo "T{$r['id_torneo']} ({$r['torneo']}) → E{$r['id_equipo']} {$r['equipo']} (confirmar:{$r['confirmar']})\n";
