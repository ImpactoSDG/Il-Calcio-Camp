# 🗄️ Base de Datos — `impactos_Il_Calcio_Camp`

Documentación de la estructura de tablas de la base de datos del sistema.


## 🏆 Gestión de Torneos

### `torneo`
| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id` | int | No | — |
| `nombre` | varchar(150) | Sí | NULL |
| `descripcion` | varchar(255) | Sí | NULL |
| `id_disciplina` | int | Sí | NULL |
| `id_estado_torneo` | int | Sí | NULL |
| `fecha_inicio` | date | Sí | NULL |
| `fecha_fin` | date | Sí | NULL |
| `fecha_fin_planificada` | date | Sí | NULL |
| `cupo_equipos` | int | Sí | NULL |
| `valor_inscripcion` | decimal(10,2) | Sí | NULL |
| `formato_manual` | varchar(50) | Sí | NULL |
| `configuracion_json` | text | Sí | NULL |
| `activo` | tinyint(1) | No | 1 |
| `deleted_at` | datetime | Sí | NULL |
| `deleted_by` | int | Sí | NULL |
| `motivo_baja` | varchar(255) | Sí | NULL |

---

### `disciplina`
| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id` | int | No | — |
| `nombre` *(único)* | varchar(150) | No | — |
| `fecha_creacion` | varchar(45) | Sí | NULL |

---

### `estado_torneo`
| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id` | int | No | — |
| `descripcion` *(único)* | varchar(50) | No | — |
| `activo` | tinyint | Sí | 1 |

---

### `estado_torneo_hist`
Historial de cambios de estado de los torneos.

| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id` | int | No | — |
| `id_torneo` | int | No | — |
| `id_estado_torneo` | int | No | — |
| `fecha_cambio` | datetime | Sí | NULL |
| `observacion` | varchar(255) | Sí | NULL |

---

### `fase_torneo`
| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id` | int | No | — |
| `id_torneo` | int | No | — |
| `nombre` | varchar(100) | Sí | NULL |
| `tipo_fase` | varchar(50) | Sí | NULL |
| `orden` | int | Sí | NULL |
| `configuracion_json` | text | Sí | NULL |

---

### `grupo_torneo`
| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id` | int | No | — |
| `id_fase_torneo` | int | No | — |
| `nombre` | varchar(100) | Sí | NULL |
| `orden` | int | Sí | NULL |
| `cantidad_equipos_objetivo` | int | Sí | NULL |
| `criterio_asignacion` | varchar(50) | Sí | NULL |

---

### `grupo_torneo_equipo`
Relación entre grupos y equipos dentro de un torneo.

| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id` | int | No | — |
| `id_grupo_torneo` | int | No | — |
| `id_equipo_torneo` | int | No | — |
| `posicion_inicial` | int | Sí | NULL |

---

### `equipo_torneo`
Inscripciones de equipos a torneos.

| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id` | int | No | — |
| `id_equipo` | int | No | — |
| `id_torneo` | int | No | — |
| `id_estado_inscripcion` | int | Sí | NULL |
| `fecha_inscripcion` | date | Sí | NULL |
| `fecha_pago` | date | Sí | NULL |
| `comprobante_pago` | varchar(255) | Sí | NULL |
| `observacion` | varchar(255) | Sí | NULL |

---

### `estado_inscripcion`
| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id` | int | No | — |
| `descripcion` *(único)* | varchar(50) | No | — |
| `activo` | tinyint | Sí | 1 |

---

### `cruce_torneo`
| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id` | int | No | — |
| `id_fase_torneo` | int | No | — |
| `id_grupo_torneo` | int | Sí | NULL |
| `id_evento` | int | Sí | NULL |
| `nombre` | varchar(100) | Sí | NULL |
| `orden` | int | Sí | NULL |
| `origen_local_tipo` | varchar(50) | Sí | NULL |
| `origen_local_valor` | varchar(100) | Sí | NULL |
| `origen_visitante_tipo` | varchar(50) | Sí | NULL |
| `origen_visitante_valor` | varchar(100) | Sí | NULL |

---

### `generacion_fixture`
Registro de generaciones automáticas de fixture.

| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id` | int | No | — |
| `id_torneo` | int | No | — |
| `fecha_generacion` | datetime | Sí | NULL |
| `motor_generacion` | varchar(100) | Sí | NULL |
| `version_algoritmo` | varchar(50) | Sí | NULL |
| `parametros_json` | text | Sí | NULL |
| `resultado_json` | text | Sí | NULL |
| `estado` | varchar(50) | Sí | NULL |
| `observacion` | varchar(255) | Sí | NULL |

---

## 👥 Equipos y Jugadores

### `equipo`
| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id` | int | No | — |
| `nombre` | varchar(45) | Sí | NULL |
| `disciplina` | varchar(45) | Sí | NULL |
| `id_disciplina` | int | Sí | NULL |
| `escudo` | varchar(255) | Sí | NULL |
| `activo` | tinyint | Sí | NULL |

---

### `jugador`
| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id` | int | No | — |
| `nombre` | varchar(100) | Sí | NULL |
| `apellido` | varchar(100) | Sí | NULL |
| `dni` | varchar(20) | Sí | NULL |
| `fecha_nac` | date | Sí | NULL |
| `fecha_alta` | date | Sí | NULL |
| `activo` | tinyint | Sí | 1 |

---

### `jugador_equipo`
Vinculación activa de jugadores a equipos.

| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id` | int | No | — |
| `id_jugador` | int | No | — |
| `id_equipo` | int | No | — |
| `fecha_desde` | date | Sí | NULL |
| `fecha_hasta` | date | Sí | NULL |

---

### `jugador_equipo_hist`
Historial de cambios de equipo por jugador.

| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id` | int | No | — |
| `id_jugador_equipo` | int | No | — |
| `id_jugador` | int | No | — |
| `id_equipo` | int | No | — |
| `fecha_desde` | date | Sí | NULL |
| `fecha_hasta` | date | Sí | NULL |
| `fecha_cambio` | datetime | Sí | NULL |
| `accion` | varchar(50) | Sí | NULL |

---

### `arbitro`
| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id` | int | No | — |
| `nombre` | varchar(100) | Sí | NULL |
| `apellido` | varchar(100) | Sí | NULL |
| `dni` | varchar(20) | Sí | NULL |
| `telefono` | varchar(30) | Sí | NULL |
| `email` | varchar(100) | Sí | NULL |
| `activo` | tinyint | Sí | 1 |

---

### `cancha`
| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id` | int | No | — |
| `nombre` | varchar(100) | Sí | NULL |
| `descripcion` | varchar(255) | Sí | NULL |
| `activo` | tinyint | Sí | 1 |

---

### `cliente`
| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id` | int | No | — |
| `nombre_cliente` | varchar(45) | Sí | NULL |
| `condicion_iva` | varchar(45) | Sí | NULL |
| `id_condicion_iva_receptor` | int | Sí | NULL |
| `direccion` | varchar(45) | Sí | NULL |
| `id_provinica` | int | Sí | NULL |

---

### `cliente_equipo`
Relación entre clientes y equipos.

| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id_cliente_equipo` | int | No | — |
| `id_cliente` | int | No | — |
| `id_equipo` | int | No | — |

---

## ⚽ Eventos y Partidos

### `evento`
| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id` | int | No | — |
| `id_torneo` | int | Sí | NULL |
| `id_estado_evento` | int | Sí | NULL |
| `tipo_evento` | enum('partido','festejo','reunion','otro') | Sí | NULL |
| `titulo` | varchar(150) | Sí | NULL |
| `descripcion` | varchar(255) | Sí | NULL |
| `numero_fecha` | int | Sí | NULL |
| `fecha_hora_inicio` | datetime | Sí | NULL |
| `fecha_hora_fin` | datetime | Sí | NULL |
| `id_cancha` | int | Sí | NULL |
| `id_arbitro` | int | Sí | NULL |
| `id_equipo_local` | int | Sí | NULL |
| `id_equipo_visitante` | int | Sí | NULL |
| `resultado_local` | int | Sí | NULL |
| `resultado_visitante` | int | Sí | NULL |
| `resultado_penales_local` | int | Sí | NULL |
| `resultado_penales_visitante` | int | Sí | NULL |

---

### `estado_evento`
| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id` | int | No | — |
| `descripcion` *(único)* | varchar(50) | No | — |
| `activo` | tinyint | Sí | 1 |

---

### `estado_evento_hist`
Historial de cambios de estado de los eventos.

| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id` | int | No | — |
| `id_evento` | int | No | — |
| `id_estado_evento` | int | No | — |
| `fecha_cambio` | datetime | Sí | NULL |
| `observacion` | varchar(255) | Sí | NULL |

---

### `evento_partido`
Incidencias registradas durante un partido.

| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id` | int | No | — |
| `id_evento` | int | No | — |
| `id_tipo_evento_partido` | int | No | — |
| `id_jugador` | int | Sí | NULL |
| `id_equipo` | int | Sí | NULL |
| `minuto` | int | Sí | NULL |
| `observacion` | varchar(255) | Sí | NULL |

---

### `tipo_evento_partido`
Catálogo de tipos de incidencias (gol, tarjeta, etc.).

| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id` | int | No | — |
| `descripcion` *(único)* | varchar(50) | No | — |
| `activo` | tinyint | Sí | 1 |

---

## 🛒 Ventas e Inventario

### `articulo`
| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id` | int | No | — |
| `nombre` | varchar(255) | Sí | NULL |
| `precio_actual` | decimal(10,2) | Sí | NULL |
| `costo_actual` | decimal(10,2) | Sí | NULL |
| `cod_barra` | varchar(1000) | Sí | NULL |
| `id_categoria_articulo` | int | Sí | NULL |
| `url_imagen` | varchar(255) | Sí | NULL |
| `ROP` | int | Sí | 1 |
| `activo` | tinyint | Sí | NULL |

---

### `categoria_articulo`
| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id` | int | No | — |
| `descripcion` | varchar(45) | Sí | NULL |

---

### `ingreso_articulo`
Registros de stock entrante (compras o ajustes).

| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id` | int | No | — |
| `id_articulo` | int | No | — |
| `fecha_ingreso` | date | Sí | NULL |
| `vencimiento` | date | Sí | NULL |
| `cantidad` | decimal(10,2) | Sí | NULL |
| `precio_unitario` | decimal(10,2) | Sí | NULL |
| `total` | decimal(10,2) | Sí | NULL |
| `es_ajuste` | tinyint | Sí | NULL |
| `es_perecedero` | tinyint | Sí | NULL |
| `id_pedido_proveedor` | int | Sí | NULL |

---

### `venta`
| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id` | int | No | — |
| `fecha` | date | Sí | NULL |
| `id_equipo` | int | Sí | NULL |
| `id_cliente` | int | Sí | NULL |
| `descripcion_cliente` | varchar(255) | Sí | NULL |
| `id_estado_venta` | int | No | — |
| `simbolo` | varchar(45) | No | — |
| `tipo_vta` | tinyint(1) | Sí | 1 |
| `es_ajuste` | tinyint(1) | Sí | 0 |

---

### `estado_venta`
| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id` | int | No | — |
| `descripcion` | varchar(45) | Sí | NULL |

---

### `articulo_venta`
Líneas de detalle de cada venta.

| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id_articulo_venta` | int | No | — |
| `id_articulo` | int | No | — |
| `id_venta` | int | No | — |
| `cantidad` | double(10,2) | No | — |
| `precio_unitario` | double(10,2) | No | — |
| `total` | double(10,2) | No | — |

---

### `articulo_venta_ingreso_articulo`
Relación entre artículos vendidos y su lote de ingreso (trazabilidad).

| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id_articulo_venta_ingreso_articulo` | int | No | — |
| `articulo_venta_id_articulo_venta` | int | No | — |
| `ingreso_articulo_id` | int | No | — |
| `cantidad` | decimal(10,2) | Sí | NULL |

---

### `cobro`
| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id` | int | No | — |
| `cliente_id` | int | Sí | NULL |
| `fecha` | timestamp | Sí | CURRENT_TIMESTAMP |

---

### `venta_cobro`
Relación entre ventas y cobros (permite pagos parciales o múltiples medios).

| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id_venta_cobro` | int | No | — |
| `id_venta` | int | No | — |
| `id_cobro` | int | No | — |
| `id_medio_pago` | int | No | — |
| `monto` | double(10,2) | Sí | NULL |

---

### `medio_cobro`
| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id` | int | No | — |
| `descripcion` | varchar(255) | Sí | NULL |
| `activo` | tinyint | Sí | NULL |

---

### `proveedor`
| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id_proveedor` | int | No | — |
| `nombre` | varchar(150) | Sí | NULL |
| `apellido` | varchar(150) | Sí | NULL |
| `nombre_fantasia` | varchar(150) | Sí | NULL |
| `telefono` | varchar(30) | Sí | NULL |
| `direccion` | varchar(150) | Sí | NULL |
| `activo` | tinyint | Sí | 1 |

---

### `pedido_proveedor`
| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id_pedido_proveedor` | int | No | — |
| `id_proveedor` | int | No | — |
| `fecha_pedido` | datetime | Sí | CURRENT_TIMESTAMP |
| `fecha_entrega` | date | Sí | NULL |
| `estado` | enum('pendiente','recibido','cancelado') | Sí | pendiente |
| `observaciones` | varchar(255) | Sí | NULL |

---

### `pago_proveedor`
| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id_pago_proveedor` | int | No | — |
| `id_proveedor` | int | No | — |
| `fecha_pago` | datetime | Sí | CURRENT_TIMESTAMP |
| `monto` | decimal(10,2) | No | — |
| `id_medio_cobro` | int | No | — |
| `observacion` | varchar(255) | Sí | NULL |

---

## ⚙️ Configuración del Sistema

### `configuraciones`
| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id` | int | No | — |
| `clave` *(único)* | varchar(100) | No | — |
| `valor` | text | No | — |
| `descripcion` | varchar(255) | Sí | NULL |
| `fecha_modificacion` | timestamp | Sí | CURRENT_TIMESTAMP |

---

### `usuario`
| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id` | int | No | — |
| `nombre` | varchar(255) | Sí | NULL |
| `email` | varchar(50) | Sí | NULL |
| `contrasena` | varchar(255) | Sí | NULL |
| `id_rol` | int | No | — |

---

### `rol`
| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id` | int | No | — |
| `nombre` | varchar(45) | Sí | NULL |
| `descripcion` | varchar(255) | Sí | NULL |

---

### `modulo`
Menú y rutas del sistema.

| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id` | int | No | — |
| `nombre` | varchar(45) | Sí | NULL |
| `ruta` | varchar(100) | Sí | NULL |
| `id_padre` | int | Sí | NULL |
| `orden_visualizacion` | int | Sí | NULL |
| `categoria` | varchar(255) | Sí | NULL |
| `icon` | varchar(100) | Sí | bi-app-indicator |
| `bg` | varchar(20) | Sí | #6c757d |

---

### `usuario_modulo`
Permisos de acceso por usuario y módulo.

| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id` | int | No | — |
| `id_modulo` | int | No | — |
| `id_usuario` | int | No | — |

---

### `condicion_iva_receptor`
| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id` | int | No | — |
| `descripcion_condicion` | varchar(45) | Sí | NULL |

---

### `provincia`
| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id` | int | No | — |
| `provincia` | varchar(100) | Sí | NULL |

---

### `impresoras_tiquetera`
| Columna | Tipo | Nulo | Predeterminado |
|---|---|---|---|
| 🔑 `id` | int | No | — |
| `nombre` | varchar(100) | No | — |
| `descripcion` | varchar(255) | Sí | NULL |
| `comando_corte` | varchar(20) | No | x1Dx56x00 |
| `lineas_avance` | tinyint | No | 4 |
| `es_default` | tinyint(1) | No | 0 |
| `fecha_creacion` | datetime | No | CURRENT_TIMESTAMP |
| `fecha_modificacion` | datetime | No | CURRENT_TIMESTAMP |

---

> 🔑 **PK** = Clave primaria  |  Las columnas marcadas como *único* tienen restricción `UNIQUE`