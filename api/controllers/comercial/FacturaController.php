<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseController.php';
require_once __DIR__ . '/../../models/comercial/Factura.php';

class FacturaController extends BaseController
{
    private Factura $facturaModel;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->facturaModel = new Factura($db);
    }

    // =========================================================================
    // ENDPOINT PÚBLICO: POST /facturar-venta
    // =========================================================================

    /**
     * Recibe { id_venta } en el body JSON y emite la factura electrónica AFIP.
     */
    public function facturarVenta(): void
    {
        try {
            $data     = json_decode(file_get_contents("php://input"), true) ?? [];
            $idVenta  = isset($data['id_venta']) ? (int)$data['id_venta'] : 0;

            if (!$idVenta) {
                $this->respond(400, ['message' => 'ID de venta requerido.']);
            }

            $resultado = $this->procesarFacturacion($idVenta);

            if ($resultado['success']) {
                $this->respond(200, $resultado);
            } else {
                $this->respond(422, ['message' => $resultado['error'] ?? 'Error al facturar.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al procesar la facturación');
        }
    }

    // =========================================================================
    // ENDPOINT PÚBLICO: GET /facturas/{id}
    // =========================================================================

    public function getById(): void
    {
        try {
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if (!$id) {
                $this->respond(400, ['message' => 'ID de factura requerido.']);
            }

            $factura = $this->facturaModel->getById($id);

            if ($factura) {
                $this->respond(200, $factura);
            } else {
                $this->respond(404, ['message' => 'Factura no encontrada.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener factura');
        }
    }

    public function getByVentaId(): void
    {
        try {
            $idVenta = isset($_GET['id_venta']) ? (int)$_GET['id_venta'] : 0;
            if (!$idVenta) {
                $this->respond(400, ['message' => 'ID de venta requerido.']);
            }

            $factura = $this->facturaModel->getByVentaId($idVenta);

            if ($factura) {
                $this->respond(200, $factura);
            } else {
                $this->respond(404, ['message' => 'No se encontró factura para esta venta.']);
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al obtener factura por venta');
        }
    }

    // =========================================================================
    // ENDPOINT PÚBLICO: PUT /facturas/{id}/receptor
    // =========================================================================

    /**
     * Permite editar los datos del receptor después de emitida la factura.
     * Recibe { direccion, localidad, provincia, descripcion } en el body.
     */
    public function updateReceptor(): void
    {
        try {
            $id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            $data = json_decode(file_get_contents("php://input"), true) ?? [];

            if (!$id) {
                $this->respond(400, ['message' => 'ID de factura requerido.']);
            }

            $ok = $this->facturaModel->updateReceptor(
                $id,
                $data['direccion']   ?? '',
                $data['localidad']   ?? '',
                $data['provincia']   ?? '',
                $data['descripcion'] ?? ''
            );

            $ok
                ? $this->respond(200, ['message' => 'Datos del receptor actualizados.'])
                : $this->respond(500, ['message' => 'No se pudo actualizar.']);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al actualizar receptor');
        }
    }

    // =========================================================================
    // LÓGICA INTERNA
    // =========================================================================

    /**
     * Orquesta el proceso completo de facturación de una venta.
     * Valida que todos los datos obligatorios estén presentes.
     */
    public function procesarFacturacion(int $idVenta): array
    {
        // 1. Obtener la venta y su total calculado
        $stmt = $this->db->prepare("
            SELECT v.*,
                   COALESCE(SUM(av.total), 0) AS importe_total
            FROM venta v
            LEFT JOIN articulo_venta av ON av.id_venta = v.id
            WHERE v.id = ?
            GROUP BY v.id
        ");
        $stmt->execute([$idVenta]);
        $venta = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$venta) {
            return ['success' => false, 'error' => 'Venta no encontrada.'];
        }

        if (empty($venta['importe_total']) || $venta['importe_total'] <= 0) {
            return ['success' => false, 'error' => 'La venta debe tener al menos un artículo.'];
        }

        // 1b. Verificar límite diario de facturación (incluyendo el monto de esta venta)
        $estadoDiario = $this->getEstadoDiarioData((float)$venta['importe_total']);
        if (!$estadoDiario['puede_facturar']) {
            return [
                'success'       => false,
                'limite_diario' => true,
                'error'         => sprintf(
                    'Límite diario de facturación alcanzado ($%s acumulado / $%s límite). La venta se registró sin factura electrónica.',
                    number_format($estadoDiario['acumulado'], 2, '.', ','),
                    number_format($estadoDiario['limite'], 2, '.', ',')
                ),
            ];
        }

        // Idempotencia: si ya tiene factura con CAE, devolver la existente
        if (!empty($venta['id_factura'])) {
            $factExistente = $this->facturaModel->getById((int)$venta['id_factura']);
            if ($factExistente && !empty($factExistente['cae'])) {
                return [
                    'success'        => true,
                    'already_issued' => true,
                    'id_factura'     => $factExistente['Id_factura'],
                    'cae'            => $factExistente['cae'],
                    'factura'        => $factExistente,
                ];
            }
        }

        // 2. Obtener datos del Emisor con JOINs para validar FKs de entrada
        $stmtEmisor = $this->db->query("
            SELECT e.*, 
                   tc.tipo_comp as codigo_afip_comprobante,
                   tn.cod_concepto as codigo_afip_concepto
            FROM facturacion_datos_emisor e
            LEFT JOIN tipo_comprobante tc ON tc.Id_tipo_comp = e.id_tipo_comprobante
            LEFT JOIN tipo_concepto tn ON tn.Id_tipo_concepto = e.Id_tipo_concepto
            LIMIT 1
        ");
        $emisor = $stmtEmisor->fetch(PDO::FETCH_ASSOC);

        if (!$emisor) {
            return ['success' => false, 'error' => 'No hay datos de emisor configurados. Complete la tabla facturacion_datos_emisor.'];
        }

        // Validar datos del emisor y sus relaciones
        $erroresEmisor = [];
        if (empty($emisor['CUIT'])) $erroresEmisor[] = 'CUIT del emisor';
        
        $ptoVta = (int)($emisor['pto_vta'] ?? 0);
        if ($ptoVta <= 0) $erroresEmisor[] = 'Punto de venta inválido (debe ser > 0)';

        if (empty($emisor['id_tipo_comprobante'])) {
            $erroresEmisor[] = 'Tipo de comprobante no asignado';
        } elseif (empty($emisor['codigo_afip_comprobante'])) {
            $erroresEmisor[] = 'El tipo de comprobante asignado no tiene un código AFIP válido';
        }

        if (empty($emisor['Id_tipo_concepto'])) {
            $erroresEmisor[] = 'Tipo de concepto no asignado';
        } elseif (empty($emisor['codigo_afip_concepto'])) {
            $erroresEmisor[] = 'El tipo de concepto asignado no tiene un código AFIP válido';
        }

        if (!empty($erroresEmisor)) {
            return ['success' => false, 'error' => 'Configuración de facturación incorrecta: ' . implode(', ', $erroresEmisor) . '. Revise la configuración del emisor.'];
        }

        // 3. Obtener datos del Receptor (Cliente)
        $receptor = null;
        if (!empty($venta['id_cliente'])) {
            $stmtCliente = $this->db->prepare("
                SELECT c.*, p.provincia AS nombre_provincia
                FROM cliente c
                LEFT JOIN provincia p ON p.id = c.id_provinica
                WHERE c.id = ?
            ");
            $stmtCliente->execute([$venta['id_cliente']]);
            $receptor = $stmtCliente->fetch(PDO::FETCH_ASSOC);
        }

        // 4. Validar datos del receptor (Opcional para Consumidor Final)
        if ($receptor) {
            $erroresReceptor = [];
            if (empty($receptor['nombre_cliente'])) $erroresReceptor[] = 'nombre del cliente';
            if (empty($receptor['id_condicion_iva_receptor'])) $erroresReceptor[] = 'condición IVA del cliente';

            // El DNI/CUIT es obligatorio SOLO si NO es Consumidor Final (id_condicion_iva_receptor != 2)
            $esConsumidorFinal = (int)($receptor['id_condicion_iva_receptor'] ?? 0) === 2;
            if (!$esConsumidorFinal && empty($receptor['cuit_dni'])) {
                $erroresReceptor[] = 'DNI/CUIT (requerido para clientes no Consumidor Final)';
            }

            if (!empty($erroresReceptor)) {
                return [
                    'success' => false,
                    'error' => 'Datos del cliente incompletos para facturar: ' . implode(', ', $erroresReceptor) . '. Actualice los datos del cliente en Ventas > Clientes.'
                ];
            }
        } else {
            // Si es anónimo/sin cliente, definimos los datos por defecto para Consumidor Final
            $receptor = [
                'nombre_cliente' => 'Consumidor Final',
                'id_condicion_iva_receptor' => 2, // ID 2 = Consumidor Final
                'cuit_dni' => '0',
                'direccion' => null,
                'nombre_provincia' => null
            ];
        }

        // 5. Preparar payload para el afip-service
        $payload = $this->prepareAfipPayload($venta, (float)$venta['importe_total'], $emisor, $receptor);

        // 6. Llamar al servicio externo de AFIP (Node.js)
        $afipResponse = $this->callAfipService($payload);

        if (!$afipResponse['success']) {
            // No es necesario llamar a marcarPendienteAfip aquí porque el frontend lo hará
            // o el estado 'error' ya fue persistido inicialmente por Venta.php
            return ['success' => false, 'error' => $afipResponse['error'] ?? 'Error desconocido al comunicarse con AFIP.'];
        }

        // 7. Usar IDs internos ya validados
        $idTipoComprobanteBD = (int)$emisor['id_tipo_comprobante'];
        $idTipoConceptoBD    = (int)$emisor['Id_tipo_concepto'];

        // 8. Usar condición IVA del receptor validada
        $idCondIvaBD = (int)$receptor['id_condicion_iva_receptor'];

        // 9. Persistir la factura
        $idFactura = $this->facturaModel->create([
            'Id_maestro'                => $idVenta,
            'cuit_dni_receptor'         => $payload['docNro'],
            'nombre_receptor'           => $receptor['nombre_cliente'],
            'Id_condicion_IVA_comprador' => $idCondIvaBD,
            'id_tipo_comprobante'       => $idTipoComprobanteBD,
            'fecha_emision'             => date('Y-m-d H:i:s'),
            'fecha_desde'               => date('Y-m-01', strtotime($venta['fecha'])),
            'fecha_hasta'               => date('Y-m-t',  strtotime($venta['fecha'])),
            'Id_tipo_concepto'          => $idTipoConceptoBD,
            'Id_alicuota_IVA'           => 5, // 5 = IVA exento / no corresponde (Monotributo)
            'IVA'                       => 0,
            'importe_total'             => $venta['importe_total'],
            'Direccion_receptor'        => $receptor['direccion']        ?? null,
            'Localidad_receptor'        => null,
            'Provincia_receptor'        => $receptor['nombre_provincia'] ?? null,
            'nro_comprobante'           => $afipResponse['nro_comprobante'],
            'pto_venta'                 => $afipResponse['pto_venta'],
            'cae'                       => $afipResponse['cae'],
            'vto_cae'                   => $afipResponse['vto_cae'],
            'descripcion'               => $venta['descripcion_cliente'] ?? null,
        ]);

        if (!$idFactura) {
            return ['success' => false, 'error' => 'AFIP aprobó la factura pero no se pudo guardar en la base de datos.'];
        }

        // 10. Vincular la venta con la factura recién creada
        $updVenta = $this->db->prepare("
            UPDATE venta
            SET id_factura    = ?,
                estado_factura = 'facturada'
            WHERE id = ?
        ");
        $updVenta->execute([$idFactura, $idVenta]);

        return [
            'success'    => true,
            'id_factura' => $idFactura,
            'cae'        => $afipResponse['cae'],
            'factura'    => $this->facturaModel->getById($idFactura),
        ];
    }

    // =========================================================================
    // ENDPOINT PÚBLICO: POST /facturas/marcar-pendiente
    // =========================================================================

    /**
     * Marca una venta con estado 'error' o 'rechazada' luego de un fallo al facturar.
     * Recibe { id_venta, error_msg, rechazar } en el body JSON.
     */
    public function marcarPendienteAfip(): void
    {
        try {
            $data     = json_decode(file_get_contents("php://input"), true) ?? [];
            $idVenta  = isset($data['id_venta']) ? (int)$data['id_venta'] : 0;
            $errorMsg = $data['error_msg'] ?? '';
            $rechazar = isset($data['rechazar']) ? (bool)$data['rechazar'] : false;

            if (!$idVenta) {
                $this->respond(400, ['message' => 'ID de venta requerido.']);
                return;
            }

            $nuevoEstado = $rechazar ? 'rechazada' : 'error';

            $stmt = $this->db->prepare("
                UPDATE venta
                SET estado_factura = ?
                WHERE id = ?
            ");
            $stmt->execute([$nuevoEstado, $idVenta]);

            // Log del error en error_log del servidor para trazabilidad
            if ($errorMsg) {
                error_log("[AFIP][Venta #{$idVenta}] Fallo al facturar ({$nuevoEstado}): {$errorMsg}");
            }

            $this->respond(200, ['message' => "Estado actualizado a {$nuevoEstado}."]);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al marcar fallo AFIP');
        }
    }

    // =========================================================================
    // HELPERS PRIVADOS
    // =========================================================================

    /**
     * Calcula el estado de la facturación diaria: acumulado del día vs. límite configurado.
     * Retorna un array con { acumulado, limite, puede_facturar }.
     * Si no existe configuración de límite, siempre devuelve puede_facturar = true.
     */
    private function getEstadoDiarioData(float $montoActual = 0.0): array
    {
        $stmtLimite = $this->db->query(
            "SELECT valor FROM configuraciones WHERE clave = 'monto_limite' LIMIT 1"
        );
        $rowLimite = $stmtLimite->fetch(PDO::FETCH_ASSOC);

        // Si no hay límite configurado, no hay restricción
        if (!$rowLimite || $rowLimite['valor'] === null || $rowLimite['valor'] === '') {
            return ['acumulado' => 0.0, 'limite' => null, 'puede_facturar' => true];
        }

        $limite = (float)$rowLimite['valor'];

        $stmtAcum = $this->db->query(
            "SELECT COALESCE(SUM(importe_total + IVA), 0) AS acumulado
             FROM factura
             WHERE DATE(fecha_hora_creacion) = DATE(NOW())"
        );
        $acumulado = (float)$stmtAcum->fetchColumn();

        // Bloquear si el acumulado del día más el monto de esta venta supera el límite
        return [
            'acumulado'      => $acumulado,
            'limite'         => $limite,
            'puede_facturar' => ($acumulado + $montoActual) <= $limite,
        ];
    }

    /**
     * Endpoint público: GET /facturacion-estado-diario
     * Devuelve el acumulado facturado hoy, el límite y si aún se puede facturar.
     */
    public function getEstadoDiario(): void
    {
        try {
            $data = $this->getEstadoDiarioData();
            $this->respond(200, $data);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al calcular estado de facturación diaria');
        }
    }

    /**
     * Construye el payload que espera el script Node.js de afip-service.
     */
    private function prepareAfipPayload(array $venta, float $total, array $emisor, array $receptor): array
    {
        // Normalizar documento del receptor
        $docNro  = preg_replace('/[^0-9]/', '', (string)($receptor['cuit_dni'] ?? '0'));
        $docTipo = match(true) {
            strlen($docNro) === 11 => 80,   // CUIT
            strlen($docNro) >= 7  => 96,   // DNI
            default               => 99,   // Sin documento / Consumidor Final
        };

        // Obtener código AFIP para la condición IVA del receptor
        $condIvaAfip = 5; // Default: Consumidor Final
        if (!empty($receptor['id_condicion_iva_receptor'])) {
            $stmtIva = $this->db->prepare("SELECT codigo_afip FROM condicion_iva_receptor WHERE id = ?");
            $stmtIva->execute([$receptor['id_condicion_iva_receptor']]);
            $resIva = $stmtIva->fetch(PDO::FETCH_ASSOC);
            if ($resIva && !empty($resIva['codigo_afip'])) {
                $condIvaAfip = (int)$resIva['codigo_afip'];
            }
        }

        // Usar los códigos AFIP que ya vinieron en el emisor (o lanzar excepción si faltan)
        if (empty($emisor['codigo_afip_comprobante'])) {
            throw new Exception("Código AFIP de comprobante no disponible.");
        }
        if (empty($emisor['codigo_afip_concepto'])) {
            throw new Exception("Código AFIP de concepto no disponible.");
        }

        $tipoCbteAfip = (int)$emisor['codigo_afip_comprobante'];
        $tipoConsAfip = (int)$emisor['codigo_afip_concepto'];

        $cuitEmisor = preg_replace('/[^0-9]/', '', (string)($emisor['CUIT'] ?? ''));

        return [
            'cuit'         => $cuitEmisor,
            'monto'        => $total,
            'ptoVta'       => (int)($emisor['pto_vta'] ?? 1),
            'tipoCbte'     => $tipoCbteAfip,
            'docTipo'      => $docTipo,
            'docNro'       => $docNro ?: '0',
            'condicionIva' => $condIvaAfip,
            'concepto'     => $tipoConsAfip,
            'cbteFch'      => date('Ymd'),
            'fechaDesde'   => date('Ym01', strtotime($venta['fecha'])),
            'fechaHasta'   => date('Ymt',  strtotime($venta['fecha'])),
            'fechaVto'     => date('Ymd',  strtotime($venta['fecha'] . ' +10 days')),
        ];
    }

    /**
     * Ejecuta el script Node.js del afip-service compartido y retorna la respuesta parseada.
     * En este proyecto, siempre se delega al servicio remoto ya que localmente no existe el entorno Node/AFIP.
     */
    private function callAfipService(array $payload): array
    {
        // Forzamos el uso de la configuración de producción/remota incluso en local
        // ya que el entorno AFIP (certificados y script node) reside en el servidor.
        
        $nodeBin = '/home/impactos/nodevenv/franconovara/afip-service/20/bin/node --tls-cipher-list="DEFAULT@SECLEVEL=1"';
        $posiblesRutas = [
            '/home/impactos/franconovara/afip-service/afip-service/facturar.js',
            '/home/impactos/franconovara/afip-service/facturar.js',
        ];

        $scriptPath = '';
        // Nota: file_exists fallará si ejecutamos esto desde Windows hacia una ruta de Linux,
        // pero el comando final se ejecutará correctamente si el sistema es el que orquesta la llamada remota
        // o si estamos en el entorno de producción.
        // Para asegurar que el comando se construya, usaremos la primera ruta como default si no se encuentra.
        foreach ($posiblesRutas as $ruta) {
            if (file_exists($ruta)) {
                $scriptPath = $ruta;
                break;
            }
        }
        
        if (!$scriptPath) {
            $scriptPath = $posiblesRutas[0]; 
        }

        $payloadBase64 = base64_encode(json_encode($payload));
        $command       = "$nodeBin \"$scriptPath\" " . escapeshellarg($payloadBase64);

        error_log("AFIP-COMMAND [IlCalcio]: $command");

        $outputString = shell_exec($command . ' 2>&1') ?? '';

        error_log("AFIP-OUTPUT [IlCalcio]: $outputString");

        // Limpiar la salida por si Node.js devolvió advertencias (warnings) antes del JSON
        $jsonStart = strpos($outputString, '{');
        $jsonEnd   = strrpos($outputString, '}');

        if ($jsonStart !== false && $jsonEnd !== false) {
            $response = json_decode(substr($outputString, $jsonStart, $jsonEnd - $jsonStart + 1), true);
        } else {
            error_log("AFIP-SERVICE [IlCalcio]: No se encontró JSON válido en la salida. Salida completa: $outputString");
            $response = null;
        }

        if ($response && isset($response['success'])) {
            if ($response['success']) {
                // Normalizar nombres de campo (el script usa nro/vto/ptovta/tipo)
                $response['nro_comprobante'] = $response['nro']    ?? null;
                $response['vto_cae']         = $response['vto']    ?? null;
                $response['pto_venta']       = $response['ptovta'] ?? null;
                $response['tipo_cbte']       = $response['tipo']   ?? null;
                $response['cae']             = $response['cae']    ?? null;
            } else {
                error_log("AFIP-SERVICE [IlCalcio] Error AFIP: " . ($response['error'] ?? 'Sin detalle'));
            }
            return $response;
        }

        error_log("AFIP-SERVICE [IlCalcio] Error crítico: respuesta inválida. Salida: $outputString");
        return [
            'success' => false,
            'error'   => 'Error de comunicación con AFIP. Consulte al administrador.',
        ];
    }
}
