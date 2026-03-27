<?php

declare(strict_types=1);

class Venta
{
    private PDO $conn;
    public string $table = 'venta';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Obtiene todas las ventas con información relacionada
     */
    public function getAll(): array
    {
        $sql = "SELECT v.id, v.fecha, v.id_equipo, v.descripcion_cliente, v.id_estado_venta, 
                       v.simbolo, v.id_cliente, v.tipo_vta, v.es_ajuste,
                       ev.descripcion AS estado_descripcion,
                       c.nombre_cliente AS cliente_nombre,
                       e.nombre AS equipo_nombre,
                       vc.id_medio_pago AS id_medio_cobro,
                       vc.monto AS monto_cobrado,
                       mc.descripcion AS medio_cobro_nombre,
                       (
                           SELECT GROUP_CONCAT(CONCAT(av.cantidad, 'x ', a.nombre) SEPARATOR '||')
                           FROM articulo_venta av
                           INNER JOIN articulo a ON av.id_articulo = a.id
                           WHERE av.id_venta = v.id
                       ) AS articulos_list,
                       (
                           SELECT a.url_imagen 
                           FROM articulo_venta av 
                           INNER JOIN articulo a ON av.id_articulo = a.id 
                           WHERE av.id_venta = v.id 
                           LIMIT 1
                       ) AS url_imagen_principal,
                       (
                           SELECT SUM(av.total)
                           FROM articulo_venta av
                           WHERE av.id_venta = v.id
                       ) AS total_venta
                FROM {$this->table} v
                LEFT JOIN estado_venta ev ON v.id_estado_venta = ev.id
                LEFT JOIN cliente c ON v.id_cliente = c.id
                LEFT JOIN equipo e ON v.id_equipo = e.id
                LEFT JOIN (
                    SELECT id_venta, MAX(id_medio_pago) as id_medio_pago, SUM(monto) as monto 
                    FROM venta_cobro 
                    GROUP BY id_venta
                ) vc ON v.id = vc.id_venta
                LEFT JOIN medio_cobro mc ON vc.id_medio_pago = mc.id
                ORDER BY v.fecha DESC, v.id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene una venta por ID con detalles completos
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT v.id, v.fecha, v.id_equipo, v.descripcion_cliente, v.id_estado_venta, 
                       v.simbolo, v.id_cliente, v.tipo_vta, v.es_ajuste,
                       ev.descripcion AS estado_descripcion,
                       c.nombre_cliente AS cliente_nombre,
                       e.nombre AS equipo_nombre,
                       vc.id_medio_pago AS id_medio_cobro,
                       vc.monto AS monto_cobrado,
                       mc.descripcion AS medio_cobro_nombre
                FROM {$this->table} v
                LEFT JOIN estado_venta ev ON v.id_estado_venta = ev.id
                LEFT JOIN cliente c ON v.id_cliente = c.id
                LEFT JOIN equipo e ON v.id_equipo = e.id
                LEFT JOIN (
                    SELECT id_venta, MAX(id_medio_pago) as id_medio_pago, SUM(monto) as monto 
                    FROM venta_cobro 
                    GROUP BY id_venta
                ) vc ON v.id = vc.id_venta
                LEFT JOIN medio_cobro mc ON vc.id_medio_pago = mc.id
                WHERE v.id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Obtiene ventas por cliente
     */
    public function getByCliente(int $idCliente): array
    {
        $sql = "SELECT v.id, v.fecha, v.id_equipo, v.descripcion_cliente, v.id_estado_venta, 
                       v.simbolo, v.id_cliente, v.tipo_vta, v.es_ajuste,
                       ev.descripcion AS estado_descripcion,
                       vc.id_medio_pago AS id_medio_cobro,
                       mc.descripcion AS medio_cobro_nombre
                FROM {$this->table} v
                LEFT JOIN estado_venta ev ON v.id_estado_venta = ev.id
                LEFT JOIN (
                    SELECT id_venta, MAX(id_medio_pago) as id_medio_pago 
                    FROM venta_cobro 
                    GROUP BY id_venta
                ) vc ON v.id = vc.id_venta
                LEFT JOIN medio_cobro mc ON vc.id_medio_pago = mc.id
                WHERE v.id_cliente = :id_cliente
                ORDER BY v.fecha DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_cliente', $idCliente, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene ventas por estado
     */
    public function getByEstado(int $idEstado): array
    {
        $sql = "SELECT v.id, v.fecha, v.id_equipo, v.descripcion_cliente, v.id_estado_venta, 
                       v.simbolo, v.id_cliente, v.tipo_vta, v.es_ajuste,
                       c.nombre_cliente AS cliente_nombre,
                       vc.id_medio_pago AS id_medio_cobro,
                       mc.descripcion AS medio_cobro_nombre
                FROM {$this->table} v
                LEFT JOIN cliente c ON v.id_cliente = c.id
                LEFT JOIN (
                    SELECT id_venta, MAX(id_medio_pago) as id_medio_pago 
                    FROM venta_cobro 
                    GROUP BY id_venta
                ) vc ON v.id = vc.id_venta
                LEFT JOIN medio_cobro mc ON vc.id_medio_pago = mc.id
                WHERE v.id_estado_venta = :id_estado
                ORDER BY v.fecha DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_estado', $idEstado, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene ventas por rango de fechas
     */
    public function getByFechas(string $fechaDesde, string $fechaHasta): array
    {
        $sql = "SELECT v.id, v.fecha, v.id_equipo, v.descripcion_cliente, v.id_estado_venta, 
                       v.simbolo, v.id_cliente, v.tipo_vta, v.es_ajuste,
                       ev.descripcion AS estado_descripcion,
                       c.nombre_cliente AS cliente_nombre,
                       vc.id_medio_pago AS id_medio_cobro,
                       vc.monto AS monto_cobrado,
                       mc.descripcion AS medio_cobro_nombre
                FROM {$this->table} v
                LEFT JOIN estado_venta ev ON v.id_estado_venta = ev.id
                LEFT JOIN cliente c ON v.id_cliente = c.id
                LEFT JOIN (
                    SELECT id_venta, MAX(id_medio_pago) as id_medio_pago, SUM(monto) as monto 
                    FROM venta_cobro 
                    GROUP BY id_venta
                ) vc ON v.id = vc.id_venta
                LEFT JOIN medio_cobro mc ON vc.id_medio_pago = mc.id
                WHERE v.fecha BETWEEN :fecha_desde AND :fecha_hasta
                ORDER BY v.fecha DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':fecha_desde', $fechaDesde);
        $stmt->bindValue(':fecha_hasta', $fechaHasta);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene artículos de una venta
     */
    public function getArticulos(int $idVenta): array
    {
        $sql = "SELECT av.id_articulo_venta, av.id_articulo, av.cantidad, av.precio_unitario, av.total,
                       a.nombre AS articulo_nombre, a.cod_barra, a.url_imagen
                FROM articulo_venta av
                INNER JOIN articulo a ON av.id_articulo = a.id
                WHERE av.id_venta = :id_venta
                ORDER BY av.id_articulo_venta ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_venta', $idVenta, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crea una nueva venta y sus artículos asociados con descuento de stock (Transaccional)
     */
    public function createWithDetails(array $data, array $articulos, ?int $idUsuario = null): array
    {
        try {
            $this->conn->beginTransaction();

            // 1. Crear la cabecera de la venta
            $sql = "INSERT INTO {$this->table} (fecha, id_equipo, descripcion_cliente, id_estado_venta, simbolo, id_cliente, tipo_vta, es_ajuste) 
                    VALUES (:fecha, :id_equipo, :descripcion_cliente, :id_estado_venta, :simbolo, :id_cliente, :tipo_vta, :es_ajuste)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':fecha', $data['fecha']);
            $stmt->bindValue(':id_equipo', $data['id_equipo'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':descripcion_cliente', $data['descripcion_cliente'] ?? null);
            $stmt->bindValue(':id_estado_venta', $data['id_estado_venta'], PDO::PARAM_INT);
            $stmt->bindValue(':simbolo', $data['simbolo']);
            $stmt->bindValue(':id_cliente', $data['id_cliente'] ?? null, PDO::PARAM_INT);
            // Si es cerrada (tipo_vta != 0), forzamos a 1 (pagada inmediatamente). Si no, a 0 (A Cuenta).
            $tipoVtaInt = (int)($data['id_estado_venta'] == ($data['id_estado_cerrada'] ?? 2) ? 1 : 0);
            $stmt->bindValue(':tipo_vta', $tipoVtaInt, PDO::PARAM_INT);
            $stmt->bindValue(':es_ajuste', $data['es_ajuste'] ?? 0, PDO::PARAM_INT);
            $stmt->execute();
            $idVenta = (int)$this->conn->lastInsertId();

            // 2. Procesar artículos
            require_once __DIR__ . '/../inventario/Articulo.php';
            $articuloModel = new Articulo($this->conn);

            // Determinar si la venta está en estado pausa (no descuenta stock)
            $idEstadoPausa = (int)($data['id_estado_pausa'] ?? 0);
            $esPausa = $idEstadoPausa > 0 && (int)$data['id_estado_venta'] === $idEstadoPausa;

            foreach ($articulos as $art) {
                // a. Crear el registro en articulo_venta
                $sqlAV = "INSERT INTO articulo_venta (id_articulo, id_venta, cantidad, precio_unitario, total) 
                         VALUES (:id_articulo, :id_venta, :cantidad, :precio_unitario, :total)";
                $stmtAV = $this->conn->prepare($sqlAV);
                $stmtAV->bindValue(':id_articulo', $art['id_articulo'], PDO::PARAM_INT);
                $stmtAV->bindValue(':id_venta', $idVenta, PDO::PARAM_INT);
                $stmtAV->bindValue(':cantidad', $art['cantidad']);
                $stmtAV->bindValue(':precio_unitario', $art['precio_unitario']);
                $stmtAV->bindValue(':total', $art['total']);
                $stmtAV->execute();
                $idArticuloVenta = (int)$this->conn->lastInsertId();

                // b. Lógica de Descuento de Stock (FIFO) — solo si NO está en pausa
                if (!$esPausa) {
                    $lotes = $articuloModel->getLotesDisponibles((int)$art['id_articulo']);
                    $cantidadPendiente = (float)($art['cantidad'] ?? 0);

                    // c. Distribuir contra lotes disponibles (FIFO)
                    foreach ($lotes as $lote) {
                        if ($cantidadPendiente <= 0) break;

                        $disponibleEnLote = (float)$lote['disponible'];
                        $aDescontar = min($cantidadPendiente, $disponibleEnLote);

                        $sqlAVIA = "INSERT INTO articulo_venta_ingreso_articulo (articulo_venta_id_articulo_venta, ingreso_articulo_id, cantidad) 
                                   VALUES (:id_av, :id_ingreso, :cant)";
                        $stmtAVIA = $this->conn->prepare($sqlAVIA);
                        $stmtAVIA->bindValue(':id_av', $idArticuloVenta, PDO::PARAM_INT);
                        $stmtAVIA->bindValue(':id_ingreso', $lote['id'], PDO::PARAM_INT);
                        $stmtAVIA->bindValue(':cant', (float)$aDescontar);
                        $stmtAVIA->execute();

                        $cantidadPendiente -= $aDescontar;
                    }

                    // d. Si aún queda cantidad (sin stock suficiente), registrar contra el último lote
                    //    o crear un ingreso de ajuste — el stock resultará negativo.
                    if ($cantidadPendiente > 0) {
                        $idArticuloInt = (int)$art['id_articulo'];
                        $loteFallback = $articuloModel->getUltimoLote($idArticuloInt);
                        $idLoteFallback = $loteFallback
                            ? $loteFallback['id']
                            : $articuloModel->crearIngresoAjuste($idArticuloInt);

                        $sqlAVIA = "INSERT INTO articulo_venta_ingreso_articulo (articulo_venta_id_articulo_venta, ingreso_articulo_id, cantidad) 
                                   VALUES (:id_av, :id_ingreso, :cant)";
                        $stmtAVIA = $this->conn->prepare($sqlAVIA);
                        $stmtAVIA->bindValue(':id_av', $idArticuloVenta, PDO::PARAM_INT);
                        $stmtAVIA->bindValue(':id_ingreso', $idLoteFallback, PDO::PARAM_INT);
                        $stmtAVIA->bindValue(':cant', (float)$cantidadPendiente);
                        $stmtAVIA->execute();
                    }
                }
            }

            // 3. Registrar Pago automático si corresponde (Cerrada Común o A Cuenta con monto parcial)
            // Calculamos el total de la venta de los items
            $totalVentaCalculado = array_sum(array_column($articulos, 'total'));
            
            if ($data['id_estado_venta'] == $data['id_estado_cerrada'] || !empty($data['monto_cobrado'])) {
                // Solo registramos pago si hay un medio de cobro seleccionado Y el estado NO es pausa
                if (!empty($data['id_medio_cobro']) && (int)$data['id_estado_venta'] !== (int)($data['id_estado_pausa'] ?? 0)) {
                    $montoARegistrar = $data['monto_cobrado'] ?? $totalVentaCalculado;
                    if ($montoARegistrar > 0) {
                        // En la UNIÓN inicial o creación, es esAditivo = false porque el usuario está definiendo el monto total cobrado en ese momento
                        $this->registrarPago($idVenta, (int)$data['id_medio_cobro'], (float)$montoARegistrar, $data['fecha'], $idUsuario, false);
                    }
                }
            }

            $this->conn->commit();
            return ['success' => true, 'id' => $idVenta];
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function registrarPago(int $idVenta, int $idMedioCobro, float $monto, string $fecha, ?int $idUsuario = null, bool $esAditivo = true): bool
    {
        // 1. Verificar si ya existe un cobro para esta venta
        $sqlCheck = "SELECT id_cobro FROM venta_cobro WHERE id_venta = :id_v LIMIT 1";
        $stmtCheck = $this->conn->prepare($sqlCheck);
        $stmtCheck->bindValue(':id_v', $idVenta, PDO::PARAM_INT);
        $stmtCheck->execute();
        $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // Si es aditivo sumamos (para CobroView), si no sobreescribimos (para VentasView/Edición)
            $operacionMonto = $esAditivo ? "monto + :monto" : ":monto";
            
            $sqlUpdVC = "UPDATE venta_cobro 
                         SET id_medio_pago = :id_medio, monto = $operacionMonto 
                         WHERE id_venta = :id_v";
            $stmtUpdVC = $this->conn->prepare($sqlUpdVC);
            $stmtUpdVC->bindValue(':id_v', $idVenta, PDO::PARAM_INT);
            $stmtUpdVC->bindValue(':id_medio', $idMedioCobro, PDO::PARAM_INT);
            $stmtUpdVC->bindValue(':monto', $monto);
            return $stmtUpdVC->execute();
        }

        // 2. Si no existe, crear el proceso de cobro completo
        // Obtener el cliente de la venta
        $sqlV = "SELECT id_cliente FROM {$this->table} WHERE id = :id";
        $stmtV = $this->conn->prepare($sqlV);
        $stmtV->bindValue(':id', $idVenta, PDO::PARAM_INT);
        $stmtV->execute();
        $ventaData = $stmtV->fetch(PDO::FETCH_ASSOC);
        $idCliente = $ventaData['id_cliente'] ?? null;

        // Crear el registro en 'cobro'
        $sqlCobro = "INSERT INTO cobro (cliente_id, id_usuario) VALUES (:id_cliente, :id_usuario)";
        $stmtCobro = $this->conn->prepare($sqlCobro);
        $stmtCobro->bindValue(':id_cliente', $idCliente, $idCliente ? PDO::PARAM_INT : PDO::PARAM_NULL);
        
        $idUsuarioVal = ($idUsuario && (int)$idUsuario > 0) ? (int)$idUsuario : null;
        $stmtCobro->bindValue(':id_usuario', $idUsuarioVal, $idUsuarioVal ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmtCobro->execute();
        $idCobro = (int)$this->conn->lastInsertId();

        // Relacionar cobro con la venta en 'venta_cobro'
        $sqlVC = "INSERT INTO venta_cobro (id_venta, id_cobro, id_medio_pago, monto) 
                  VALUES (:id_v, :id_c, :id_medio, :monto)";
        $stmtVC = $this->conn->prepare($sqlVC);
        $stmtVC->bindValue(':id_v', $idVenta, PDO::PARAM_INT);
        $stmtVC->bindValue(':id_c', $idCobro, PDO::PARAM_INT);
        $stmtVC->bindValue(':id_medio', $idMedioCobro, PDO::PARAM_INT);
        $stmtVC->bindValue(':monto', $monto);
        return $stmtVC->execute();
    }

    /**
     * Crea una nueva venta (Método antiguo - mantenido para compatibilidad)
     */
    public function create(string $fecha, ?int $idEquipo, ?string $descripcionCliente, int $idEstadoVenta, string $simbolo, ?int $idCliente, ?string $tipoVta): int|false
    {
        $sql = "INSERT INTO {$this->table} (fecha, id_equipo, descripcion_cliente, id_estado_venta, simbolo, id_cliente, tipo_vta) 
                VALUES (:fecha, :id_equipo, :descripcion_cliente, :id_estado_venta, :simbolo, :id_cliente, :tipo_vta)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':fecha', $fecha);
        $stmt->bindValue(':id_equipo', $idEquipo, PDO::PARAM_INT);
        $stmt->bindValue(':descripcion_cliente', $descripcionCliente);
        $stmt->bindValue(':id_estado_venta', $idEstadoVenta, PDO::PARAM_INT);
        $stmt->bindValue(':simbolo', $simbolo);
        $stmt->bindValue(':id_cliente', $idCliente, PDO::PARAM_INT);
        $stmt->bindValue(':tipo_vta', $tipoVta);
        
        if ($stmt->execute()) {
            return (int)$this->conn->lastInsertId();
        }
        return false;
    }

    /**
     * Actualiza una venta
     */
    public function update(int $id, string $fecha, ?int $idEquipo, ?string $descripcionCliente, int $idEstadoVenta, string $simbolo, ?int $idCliente, ?string $tipoVta): bool
    {
        $sql = "UPDATE {$this->table} 
                SET fecha = :fecha, 
                    id_equipo = :id_equipo, 
                    descripcion_cliente = :descripcion_cliente, 
                    id_estado_venta = :id_estado_venta, 
                    simbolo = :simbolo, 
                    id_cliente = :id_cliente, 
                    tipo_vta = :tipo_vta 
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':fecha', $fecha);
        $stmt->bindValue(':id_equipo', $idEquipo, PDO::PARAM_INT);
        $stmt->bindValue(':descripcion_cliente', $descripcionCliente);
        $stmt->bindValue(':id_estado_venta', $idEstadoVenta, PDO::PARAM_INT);
        $stmt->bindValue(':simbolo', $simbolo);
        $stmt->bindValue(':id_cliente', $idCliente, PDO::PARAM_INT);
        $stmt->bindValue(':tipo_vta', $tipoVta);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Actualiza una venta y sus artículos asociados (Transaccional)
     */
    public function updateWithDetails(array $data, array $articulos, ?int $idUsuario = null): array
    {
        try {
            $this->conn->beginTransaction();
            $idVenta = (int)$data['id'];

            // 1. Actualizar la cabecera de la venta
            $sql = "UPDATE {$this->table} 
                    SET fecha = :fecha, 
                        id_equipo = :id_equipo, 
                        descripcion_cliente = :descripcion_cliente, 
                        id_estado_venta = :id_estado_venta, 
                        simbolo = :simbolo, 
                        id_cliente = :id_cliente, 
                        tipo_vta = :tipo_vta 
                    WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':fecha', $data['fecha']);
            $stmt->bindValue(':id_equipo', $data['id_equipo'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':descripcion_cliente', $data['descripcion_cliente'] ?? null);
            $stmt->bindValue(':id_estado_venta', $data['id_estado_venta'], PDO::PARAM_INT);
            $stmt->bindValue(':simbolo', $data['simbolo']);
            $stmt->bindValue(':id_cliente', $data['id_cliente'] ?? null, PDO::PARAM_INT);
            $tipoVtaUpdate = (int)($data['id_estado_venta'] == ($data['id_estado_cerrada'] ?? 2) ? 1 : 0);
            $stmt->bindValue(':tipo_vta', $tipoVtaUpdate, PDO::PARAM_INT);
            $stmt->bindValue(':id', $idVenta, PDO::PARAM_INT);
            $stmt->execute();

            // 2. Manejo de artículos: Eliminar antiguos y agregar nuevos (Simplificado)
            // Nota: En un sistema real, se debería revertir el stock de los eliminados
            // Para este caso, asumimos que el modal envía la lista completa y actualizada.
            
            // a. Limpiar las relaciones de stock (articulos_venta_ingreso_articulo) antes de borrar articulo_venta
            $sqlDelRel = "DELETE FROM articulo_venta_ingreso_articulo 
                          WHERE articulo_venta_id_articulo_venta IN (SELECT id_articulo_venta FROM articulo_venta WHERE id_venta = :id_v)";
            $stmtDelRel = $this->conn->prepare($sqlDelRel);
            $stmtDelRel->bindValue(':id_v', $idVenta, PDO::PARAM_INT);
            $stmtDelRel->execute();

            $sqlDel = "DELETE FROM articulo_venta WHERE id_venta = :id_v";
            $stmtDel = $this->conn->prepare($sqlDel);
            $stmtDel->bindValue(':id_v', $idVenta, PDO::PARAM_INT);
            $stmtDel->execute();

                // b. Insertar los nuevos artículos (reutilizamos la lógica de creación)
            require_once __DIR__ . '/../inventario/Articulo.php';
            $articuloModel = new Articulo($this->conn);

            // Determinar si la venta está en estado pausa (no descuenta stock)
            $idEstadoPausa = (int)($data['id_estado_pausa'] ?? 0);
            $esPausa = $idEstadoPausa > 0 && (int)$data['id_estado_venta'] === $idEstadoPausa;

            foreach ($articulos as $art) {
                $sqlAV = "INSERT INTO articulo_venta (id_articulo, id_venta, cantidad, precio_unitario, total) 
                         VALUES (:id_articulo, :id_venta, :cantidad, :precio_unitario, :total)";
                $stmtAV = $this->conn->prepare($sqlAV);
                $stmtAV->bindValue(':id_articulo', $art['id_articulo'], PDO::PARAM_INT);
                $stmtAV->bindValue(':id_venta', $idVenta, PDO::PARAM_INT);
                $stmtAV->bindValue(':cantidad', $art['cantidad']);
                $stmtAV->bindValue(':precio_unitario', $art['precio_unitario']);
                $stmtAV->bindValue(':total', $art['total']);
                $stmtAV->execute();
                $idArticuloVenta = (int)$this->conn->lastInsertId();

                // Lógica de Descuento de Stock (FIFO) — solo si NO está en pausa
                if (!$esPausa) {
                    $lotes = $articuloModel->getLotesDisponibles((int)$art['id_articulo']);
                    $cantidadPendiente = (float)($art['cantidad'] ?? 0);

                    // Distribuir contra lotes disponibles (FIFO)
                    foreach ($lotes as $lote) {
                        if ($cantidadPendiente <= 0) break;

                        $disponibleEnLote = (float)$lote['disponible'];
                        $aDescontar = min($cantidadPendiente, $disponibleEnLote);

                        $sqlAVIA = "INSERT INTO articulo_venta_ingreso_articulo (articulo_venta_id_articulo_venta, ingreso_articulo_id, cantidad) 
                                   VALUES (:id_av, :id_ingreso, :cant)";
                        $stmtAVIA = $this->conn->prepare($sqlAVIA);
                        $stmtAVIA->bindValue(':id_av', $idArticuloVenta, PDO::PARAM_INT);
                        $stmtAVIA->bindValue(':id_ingreso', $lote['id'], PDO::PARAM_INT);
                        $stmtAVIA->bindValue(':cant', (float)$aDescontar);
                        $stmtAVIA->execute();

                        $cantidadPendiente -= $aDescontar;
                    }

                    // Si aún queda cantidad (sin stock suficiente), registrar contra el último lote
                    // o crear un ingreso de ajuste — el stock resultará negativo.
                    if ($cantidadPendiente > 0) {
                        $idArticuloInt = (int)$art['id_articulo'];
                        $loteFallback = $articuloModel->getUltimoLote($idArticuloInt);
                        $idLoteFallback = $loteFallback
                            ? $loteFallback['id']
                            : $articuloModel->crearIngresoAjuste($idArticuloInt);

                        $sqlAVIA = "INSERT INTO articulo_venta_ingreso_articulo (articulo_venta_id_articulo_venta, ingreso_articulo_id, cantidad) 
                                   VALUES (:id_av, :id_ingreso, :cant)";
                        $stmtAVIA = $this->conn->prepare($sqlAVIA);
                        $stmtAVIA->bindValue(':id_av', $idArticuloVenta, PDO::PARAM_INT);
                        $stmtAVIA->bindValue(':id_ingreso', $idLoteFallback, PDO::PARAM_INT);
                        $stmtAVIA->bindValue(':cant', (float)$cantidadPendiente);
                        $stmtAVIA->execute();
                    }
                }
            }

            // 3. Registrar Pago si se está cerrando o hay monto cobrado (Sobreescribimos el monto anterior)
            $totalVentaCalculado = array_sum(array_column($articulos, 'total'));
            if ($data['id_estado_venta'] == $data['id_estado_cerrada'] || !empty($data['monto_cobrado'])) {
                if (!empty($data['id_medio_cobro'])) {
                    $montoARegistrar = $data['monto_cobrado'] ?? $totalVentaCalculado;
                    if ($montoARegistrar > 0) {
                        // esAditivo = false porque al EDITAR establecemos el nuevo monto total
                        $this->registrarPago($idVenta, (int)$data['id_medio_cobro'], (float)$montoARegistrar, $data['fecha'], $idUsuario, false);
                    }
                }
            }

            $this->conn->commit();
            return ['success' => true];
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) $this->conn->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Elimina una venta y sus detalles de manera transaccional
     */
    public function delete(int $id): bool
    {
        try {
            if (!$this->conn->inTransaction()) {
                $this->conn->beginTransaction();
            }

            // 1. Obtener IDs de articulo_venta para borrar relaciones con lotes
            $sqlAV = "SELECT id_articulo_venta FROM articulo_venta WHERE id_venta = :id_v";
            $stmtAV = $this->conn->prepare($sqlAV);
            $stmtAV->bindValue(':id_v', $id, PDO::PARAM_INT);
            $stmtAV->execute();
            $avIds = $stmtAV->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($avIds)) {
                $placeholders = implode(',', array_fill(0, count($avIds), '?'));
                
                // 2. Borrar relaciones de stock (esto devuelve el stock al cálculo de 'getActivos')
                $sqlDelAVIA = "DELETE FROM articulo_venta_ingreso_articulo WHERE articulo_venta_id_articulo_venta IN ($placeholders)";
                $stmtDelAVIA = $this->conn->prepare($sqlDelAVIA);
                $stmtDelAVIA->execute($avIds);

                // 3. Borrar detalles de venta
                $sqlDelAV = "DELETE FROM articulo_venta WHERE id_venta = :id_v";
                $stmtDelAV = $this->conn->prepare($sqlDelAV);
                $stmtDelAV->bindValue(':id_v', $id, PDO::PARAM_INT);
                $stmtDelAV->execute();
            }

            // 4. Borrar cobros asociados (venta_cobro -> cobro)
            $sqlVC = "SELECT id_cobro FROM venta_cobro WHERE id_venta = :id_v";
            $stmtVC = $this->conn->prepare($sqlVC);
            $stmtVC->bindValue(':id_v', $id, PDO::PARAM_INT);
            $stmtVC->execute();
            $cobroIds = $stmtVC->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($cobroIds)) {
                $sqlDelVC = "DELETE FROM venta_cobro WHERE id_venta = :id_v";
                $stmtDelVC = $this->conn->prepare($sqlDelVC);
                $stmtDelVC->bindValue(':id_v', $id, PDO::PARAM_INT);
                $stmtDelVC->execute();

                $placeholdersC = implode(',', array_fill(0, count($cobroIds), '?'));
                $sqlDelC = "DELETE FROM cobro WHERE id IN ($placeholdersC)";
                $stmtDelC = $this->conn->prepare($sqlDelC);
                $stmtDelC->execute($cobroIds);
            }

            // 5. Por último borrar la cabecera de la venta
            $sqlV = "DELETE FROM {$this->table} WHERE id = :id";
            $stmtV = $this->conn->prepare($sqlV);
            $stmtV->bindValue(':id', $id, PDO::PARAM_INT);
            $stmtV->execute();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log("Error deleting sale: " . $e->getMessage());
            return false;
        }
    }
}
