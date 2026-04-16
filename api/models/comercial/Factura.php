<?php

declare(strict_types=1);

class Factura
{
    private PDO $conn;
    private string $table = 'factura';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Crea un registro de factura en la BD.
     * Retorna el ID del nuevo registro o false en caso de error.
     */
    public function create(array $data): int|false
    {
        $sql = "INSERT INTO {$this->table} (
                    Id_maestro, cuit_dni_receptor, nombre_receptor, Id_condicion_IVA_comprador,
                    id_tipo_comprobante, fecha_emision, fecha_desde, fecha_hasta,
                    Id_tipo_concepto, Id_alicuota_IVA, IVA, importe_total,
                    Direccion_receptor, Localidad_receptor, Provincia_receptor,
                    nro_comprobante, pto_venta, cae, vto_cae,
                    fecha_comp, fecha_hora_creacion, descripcion
                ) VALUES (
                    :id_maestro, :cuit_dni, :nombre, :id_cond_iva,
                    :id_tipo_cbte, :f_emision, :f_desde, :f_hasta,
                    :id_concepto, :id_alicuota, :iva, :total,
                    :dir, :loc, :prov,
                    :nro, :pto, :cae, :vto,
                    :f_comp, NOW(), :desc
                )";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':id_maestro'   => $data['Id_maestro']               ?? null,
            ':cuit_dni'     => $data['cuit_dni_receptor']         ?? null,
            ':nombre'       => $data['nombre_receptor']           ?? null,
            ':id_cond_iva'  => $data['Id_condicion_IVA_comprador'] ?? null,
            ':id_tipo_cbte' => $data['id_tipo_comprobante']       ?? null,
            ':f_emision'    => $data['fecha_emision']             ?? date('Y-m-d H:i:s'),
            ':f_desde'      => $data['fecha_desde']               ?? null,
            ':f_hasta'      => $data['fecha_hasta']               ?? null,
            ':id_concepto'  => $data['Id_tipo_concepto']          ?? 1,
            ':id_alicuota'  => $data['Id_alicuota_IVA']           ?? 5,
            ':iva'          => $data['IVA']                       ?? 0,
            ':total'        => $data['importe_total']             ?? 0,
            ':dir'          => $data['Direccion_receptor']        ?? null,
            ':loc'          => $data['Localidad_receptor']        ?? null,
            ':prov'         => $data['Provincia_receptor']        ?? null,
            ':nro'          => $data['nro_comprobante']           ?? null,
            ':pto'          => $data['pto_venta']                 ?? null,
            ':cae'          => $data['cae']                       ?? null,
            ':vto'          => $data['vto_cae']                   ?? null,
            ':f_comp'       => date('Y-m-d'),
            ':desc'         => $data['descripcion']               ?? null,
        ]);

        if ($stmt->rowCount() > 0) {
            return (int)$this->conn->lastInsertId();
        }
        return false;
    }

    /**
     * Obtiene una factura por ID, enriquecida con datos de venta, cliente, catálogos y emisor.
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT
                    f.*,
                    tc.denominacion            AS tipo_comprobante_nombre,
                    tc.tipo_letra,
                    tc.tipo_comp               AS codigo_afip_comprobante,
                    tco.descripcion_concepto   AS concepto_nombre,
                    cir.descripcion_condicion  AS condicion_iva_receptor_nombre,
                    v.fecha                    AS fecha_venta,
                    v.descripcion_cliente,
                    c.nombre_cliente           AS cliente_nombre,
                    e.nombre_empresa           AS emisor_nombre,
                    e.CUIT                     AS emisor_cuit,
                    e.cond_iva                 AS emisor_condicion_iva,
                    e.cond_IIBB                AS emisor_condicion_iibb,
                    e.pto_vta                  AS emisor_pto_vta,
                    e.fecha_inicio_de_acts     AS emisor_fecha_inicio_acts,
                    e.direccion_cliente        AS emisor_direccion,
                    e.ciudad_cliente           AS emisor_ciudad
                FROM {$this->table} f
                LEFT JOIN tipo_comprobante tc      ON tc.Id_tipo_comp      = f.id_tipo_comprobante
                LEFT JOIN tipo_concepto    tco     ON tco.Id_tipo_concepto = f.Id_tipo_concepto
                LEFT JOIN condicion_iva_receptor cir ON cir.id            = f.Id_condicion_IVA_comprador
                LEFT JOIN venta            v        ON v.id               = f.Id_maestro
                LEFT JOIN cliente          c        ON c.id               = v.id_cliente
                CROSS JOIN (SELECT nombre_empresa, CUIT, cond_iva, cond_IIBB, pto_vta, fecha_inicio_de_acts, direccion_cliente, ciudad_cliente FROM facturacion_datos_emisor LIMIT 1) e
                WHERE f.Id_factura = :id
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Busca la factura asociada a una venta (por Id_maestro).
     */
    public function getByVentaId(int $idVenta): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT Id_factura FROM {$this->table} WHERE Id_maestro = ? ORDER BY Id_factura DESC LIMIT 1"
        );
        $stmt->execute([$idVenta]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        return $this->getById((int)$row['Id_factura']);
    }

    /**
     * Actualiza los datos del receptor que son editables post-emisión
     * (dirección, localidad, provincia y descripción).
     */
    public function updateReceptor(int $idFactura, string $direccion, string $localidad, string $provincia, string $descripcion = ''): bool
    {
        $sql = "UPDATE {$this->table}
                SET Direccion_receptor = :dir,
                    Localidad_receptor = :loc,
                    Provincia_receptor = :prov,
                    descripcion        = :desc
                WHERE Id_factura = :id";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':dir'  => $direccion,
            ':loc'  => $localidad,
            ':prov' => $provincia,
            ':desc' => $descripcion,
            ':id'   => $idFactura,
        ]);
    }
}
