===Base de datos impactos_Il_Calcio_Camp

== Estructura de tabla para la tabla arbitro

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id**//|int|No|
|nombre|varchar(100)|Sí|NULL
|apellido|varchar(100)|Sí|NULL
|dni|varchar(20)|Sí|NULL
|telefono|varchar(30)|Sí|NULL
|email|varchar(100)|Sí|NULL
|activo|tinyint|Sí|1
== Estructura de tabla para la tabla articulo

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id**//|int|No|
|nombre|varchar(255)|Sí|NULL
|precio_actual|decimal(10,2)|Sí|NULL
|costo_actual|decimal(10,2)|Sí|NULL
|cod_barra|varchar(1000)|Sí|NULL
|id_categoria_articulo|int|Sí|NULL
|activo|tinyint|Sí|NULL
|url_imagen|varchar(255)|Sí|NULL
|ROP|int|Sí|1
== Estructura de tabla para la tabla articulo_venta

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id_articulo_venta**//|int|No|
|id_articulo|int|No|
|id_venta|int|No|
|cantidad|double(10,2)|No|
|precio_unitario|double(10,2)|No|
|total|double(10,2)|No|
== Estructura de tabla para la tabla articulo_venta_ingreso_articulo

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id_articulo_venta_ingreso_articulo**//|int|No|
|articulo_venta_id_articulo_venta|int|No|
|ingreso_articulo_id|int|No|
|cantidad|decimal(10,2)|Sí|NULL
== Estructura de tabla para la tabla cancha

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id**//|int|No|
|nombre|varchar(100)|Sí|NULL
|descripcion|varchar(255)|Sí|NULL
|id_disciplina|int|No|
|activo|tinyint|Sí|1
== Estructura de tabla para la tabla categoria_articulo

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id**//|int|No|
|descripcion|varchar(45)|Sí|NULL
== Estructura de tabla para la tabla cliente

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id**//|int|No|
|nombre_cliente|varchar(45)|Sí|NULL
|cuit_dni|bigint|Sí|NULL
|condicion_iva|varchar(45)|Sí|NULL
|id_condicion_iva_receptor|int|Sí|NULL
|direccion|varchar(45)|Sí|NULL
|id_provinica|int|Sí|NULL
|telefono|varchar(100)|Sí|NULL
== Volcado de datos para la tabla cliente

|2|facu|NULL|Consumidor final|1| |1|NULL
|3|silvina|NULL|Consumidor final|1| |1|NULL
|4|Jeremias|NULL|Consumidor final|1| |1|NULL
|5|Dani Lamberghini|NULL|Consumidor final|1| |1|NULL
|6|Milka|NULL|Consumidor final|1| |1|NULL
|7|nico b|NULL|Consumidor final|1| |1|NULL
|8|Franco Mozo|NULL|Consumidor final|1| |1|NULL
|9|mario ochonga|NULL|Consumidor final|1| |1|NULL
|10|Facu|NULL|Consumidor final|1| |1|NULL
|11|Thiago|NULL|Consumidor final|1| |1|NULL
|12|tata|NULL|Consumidor final|1| |1|NULL
|13|jeremias|NULL|Consumidor final|1| |1|NULL
|14|octavio|NULL|Consumidor final|1| |1|NULL
|15|juanma|NULL|Consumidor final|1| |1|NULL
|16|garza|NULL|Consumidor final|1| |1|NULL
|17|garza|NULL|Consumidor final|1| |1|NULL
|18|tata|NULL|Consumidor final|1| |1|NULL
|19|asado|NULL|Consumidor final|1| |1|NULL
|20|Kiko|NULL|Consumidor final|1| |1|NULL
|21|valentin|NULL|Consumidor final|1| |1|NULL
|22|Parroquial|NULL|Consumidor final|1| |1|NULL
|23|Santo tomas|NULL|Consumidor final|1| |1|NULL
|24|Villa Merced|NULL|Consumidor final|1| |1|NULL
|25|San Martin|NULL|Consumidor final|1| |1|NULL
|26|MAO|NULL|Consumidor final|1| |1|NULL
|27|cATEDRAL|NULL|Consumidor final|1| |1|NULL
== Estructura de tabla para la tabla cliente_equipo

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id_cliente_equipo**//|int|No|
|id_cliente|int|No|
|id_equipo|int|No|
== Estructura de tabla para la tabla cobro

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id**//|int|No|
|cliente_id|int|Sí|NULL
|fecha|timestamp|Sí|CURRENT_TIMESTAMP
|id_usuario|int|Sí|NULL
== Estructura de tabla para la tabla condicion_iva_receptor

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id**//|int|No|
|descripcion_condicion|varchar(45)|Sí|NULL
|codigo_afip|int|Sí|NULL
== Volcado de datos para la tabla condicion_iva_receptor

|1|Responsable Inscripto Activo|NULL
== Estructura de tabla para la tabla configuraciones

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id**//|int|No|
|**clave**|varchar(100)|No|
|valor|text|No|
|descripcion|varchar(255)|Sí|NULL
|fecha_modificacion|timestamp|Sí|CURRENT_TIMESTAMP
== Estructura de tabla para la tabla cruce_torneo

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id**//|int|No|
|id_fase_torneo|int|No|
|id_grupo_torneo|int|Sí|NULL
|id_evento|int|Sí|NULL
|nombre|varchar(100)|Sí|NULL
|orden|int|Sí|NULL
|origen_local_tipo|varchar(50)|Sí|NULL
|origen_local_valor|varchar(100)|Sí|NULL
|origen_visitante_tipo|varchar(50)|Sí|NULL
|origen_visitante_valor|varchar(100)|Sí|NULL
== Estructura de tabla para la tabla disciplina

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id**//|int|No|
|**nombre**|varchar(150)|No|
|fecha_creacion|varchar(45)|Sí|NULL
|cantidad_jugadores_equipo|int|Sí|NULL
== Estructura de tabla para la tabla equipo

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id**//|int|No|
|activo|tinyint|Sí|NULL
|disciplina|varchar(45)|Sí|NULL
|nombre|varchar(45)|Sí|NULL
|id_disciplina|int|Sí|NULL
|escudo|varchar(255)|Sí|NULL
|confirmar|tinyint(1)|Sí|NULL
== Estructura de tabla para la tabla equipo_torneo

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id**//|int|No|
|id_equipo|int|No|
|id_torneo|int|No|
|id_estado_inscripcion|int|Sí|NULL
|fecha_inscripcion|date|Sí|NULL
|fecha_pago|date|Sí|NULL
|comprobante_pago|varchar(255)|Sí|NULL
|observacion|varchar(255)|Sí|NULL
== Estructura de tabla para la tabla estado_evento

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id**//|int|No|
|**descripcion**|varchar(50)|No|
|activo|tinyint|Sí|1
== Estructura de tabla para la tabla estado_evento_hist

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id**//|int|No|
|id_evento|int|No|
|id_estado_evento|int|No|
|fecha_cambio|datetime|Sí|NULL
|observacion|varchar(255)|Sí|NULL
== Estructura de tabla para la tabla estado_inscripcion

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id**//|int|No|
|**descripcion**|varchar(50)|No|
|activo|tinyint|Sí|1
== Estructura de tabla para la tabla estado_torneo

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id**//|int|No|
|**descripcion**|varchar(50)|No|
|activo|tinyint|Sí|1
== Estructura de tabla para la tabla estado_torneo_hist

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id**//|int|No|
|id_torneo|int|No|
|id_estado_torneo|int|No|
|fecha_cambio|datetime|Sí|NULL
|observacion|varchar(255)|Sí|NULL
== Estructura de tabla para la tabla estado_venta

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id**//|int|No|
|descripcion|varchar(45)|Sí|NULL
== Estructura de tabla para la tabla evento

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id**//|int|No|
|id_torneo|int|Sí|NULL
|id_estado_evento|int|Sí|NULL
|tipo_evento|enum(&#039;partido&#039;, &#039;festejo&#039;, &#039;reunion&#039;, &#039;otro&#039;)|Sí|NULL
|titulo|varchar(150)|Sí|NULL
|descripcion|varchar(255)|Sí|NULL
|numero_fecha|int|Sí|NULL
|fecha_hora_inicio|datetime|Sí|NULL
|fecha_hora_fin|datetime|Sí|NULL
|id_cancha|int|Sí|NULL
|id_arbitro|int|Sí|NULL
|id_equipo_local|int|Sí|NULL
|id_equipo_visitante|int|Sí|NULL
|resultado_local|int|Sí|NULL
|resultado_visitante|int|Sí|NULL
|resultado_penales_local|int|Sí|NULL
|resultado_penales_visitante|int|Sí|NULL
|pago_local_realizado|tinyint(1)|No|0
|url_comprobante_pago_local|varchar(255)|Sí|NULL
|pago_visitante_realizado|tinyint(1)|No|0
|url_comprobante_pago_visitante|varchar(255)|Sí|NULL
== Estructura de tabla para la tabla evento_partido

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id**//|int|No|
|id_evento|int|No|
|id_tipo_evento_partido|int|No|
|id_jugador|int|Sí|NULL
|id_equipo|int|Sí|NULL
|minuto|int|Sí|NULL
|observacion|varchar(255)|Sí|NULL
== Estructura de tabla para la tabla factura

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**Id_factura**//|bigint|No|
|Id_maestro|bigint|Sí|NULL
|cuit_dni_receptor|double|Sí|NULL
|nombre_receptor|text|Sí|NULL
|Id_condicion_IVA_comprador|bigint|Sí|NULL
|id_tipo_comprobante|bigint|Sí|NULL
|fecha_emision|datetime|Sí|NULL
|fecha_desde|datetime|Sí|NULL
|fecha_hasta|datetime|Sí|NULL
|Id_tipo_concepto|bigint|Sí|NULL
|Id_alicuota_IVA|bigint|Sí|NULL
|IVA|double|Sí|NULL
|importe_total|double|Sí|NULL
|Direccion_receptor|text|Sí|NULL
|Localidad_receptor|text|Sí|NULL
|Provincia_receptor|text|Sí|NULL
|nro_comprobante|text|Sí|NULL
|pto_venta|text|Sí|NULL
|cae|text|Sí|NULL
|vto_cae|text|Sí|NULL
|fecha_comp|text|Sí|NULL
|fecha_hora_creacion|datetime|Sí|NULL
|descripcion|text|Sí|NULL
== Volcado de datos para la tabla factura

== Estructura de tabla para la tabla facturacion_datos_emisor

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**Id_datos_facturacion**//|bigint|No|
|nombre|text|Sí|NULL
|pto_vta|text|Sí|NULL
|cond_iva|text|Sí|NULL
|cond_IIBB|text|Sí|NULL
|fecha_inicio_de_acts|datetime|Sí|NULL
|id_tipo_comprobante|bigint|Sí|NULL
|Id_tipo_concepto|bigint|Sí|NULL
|ciudad_cliente|text|Sí|NULL
|direccion_cliente|text|Sí|NULL
|nombre_empresa|text|Sí|NULL
|CUIT|double|Sí|NULL
== Volcado de datos para la tabla facturacion_datos_emisor

== Estructura de tabla para la tabla fase_torneo

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id**//|int|No|
|id_torneo|int|No|
|nombre|varchar(100)|Sí|NULL
|tipo_fase|varchar(50)|Sí|NULL
|orden|int|Sí|NULL
|configuracion_json|text|Sí|NULL
== Estructura de tabla para la tabla generacion_fixture

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id**//|int|No|
|id_torneo|int|No|
|fecha_generacion|datetime|Sí|NULL
|motor_generacion|varchar(100)|Sí|NULL
|version_algoritmo|varchar(50)|Sí|NULL
|parametros_json|text|Sí|NULL
|resultado_json|text|Sí|NULL
|estado|varchar(50)|Sí|NULL
|observacion|varchar(255)|Sí|NULL
== Estructura de tabla para la tabla grupo_torneo

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id**//|int|No|
|id_fase_torneo|int|No|
|nombre|varchar(100)|Sí|NULL
|orden|int|Sí|NULL
|cantidad_equipos_objetivo|int|Sí|NULL
|criterio_asignacion|varchar(50)|Sí|NULL
== Estructura de tabla para la tabla grupo_torneo_equipo

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id**//|int|No|
|id_grupo_torneo|int|No|
|id_equipo_torneo|int|No|
|posicion_inicial|int|Sí|NULL
== Estructura de tabla para la tabla impresoras_tiquetera

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id**//|int|No|
|nombre|varchar(100)|No|
|comando_corte|varchar(20)|No|x1Dx56x00
|lineas_avance|tinyint|No|4
|es_default|tinyint(1)|No|0
|descripcion|varchar(255)|Sí|NULL
|fecha_creacion|datetime|No|CURRENT_TIMESTAMP
|fecha_modificacion|datetime|No|CURRENT_TIMESTAMP
== Estructura de tabla para la tabla ingreso_articulo

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id**//|int|No|
|fecha_ingreso|date|Sí|NULL
|vencimiento|date|Sí|NULL
|es_ajuste|tinyint|Sí|NULL
|cantidad|decimal(10,2)|Sí|NULL
|id_articulo|int|No|
|precio_unitario|decimal(10,2)|Sí|NULL
|total|decimal(10,2)|Sí|NULL
|es_perecedero|tinyint|Sí|NULL
|id_pedido_proveedor|int|Sí|NULL
== Estructura de tabla para la tabla jugador

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id**//|int|No|
|nombre|varchar(100)|Sí|NULL
|apellido|varchar(100)|Sí|NULL
|dni|varchar(20)|Sí|NULL
|fecha_nac|date|Sí|NULL
|fecha_alta|date|Sí|NULL
|activo|tinyint|Sí|1
== Estructura de tabla para la tabla jugador_equipo

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id**//|int|No|
|id_jugador|int|No|
|id_equipo|int|No|
|fecha_desde|date|Sí|NULL
|fecha_hasta|date|Sí|NULL
|es_capitan|tinyint(1)|Sí|0
|es_arquero|tinyint(1)|No|0
== Estructura de tabla para la tabla jugador_equipo_hist

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id**//|int|No|
|id_jugador_equipo|int|No|
|id_jugador|int|No|
|id_equipo|int|No|
|fecha_desde|date|Sí|NULL
|fecha_hasta|date|Sí|NULL
|fecha_cambio|datetime|Sí|NULL
|accion|varchar(50)|Sí|NULL
== Estructura de tabla para la tabla medio_cobro

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id**//|int|No|
|descripcion|varchar(255)|Sí|NULL
|activo|tinyint|Sí|NULL
== Estructura de tabla para la tabla modulo

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id**//|int|No|
|nombre|varchar(45)|Sí|NULL
|ruta|varchar(100)|Sí|NULL
|id_padre|int|Sí|NULL
|orden_visualizacion|int|Sí|NULL
|categoria|varchar(255)|Sí|NULL
|icon|varchar(100)|Sí|bi-app-indicator
|bg|varchar(20)|Sí|#6c757d
== Estructura de tabla para la tabla pago_proveedor

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id_pago_proveedor**//|int|No|
|id_proveedor|int|No|
|fecha_pago|datetime|Sí|CURRENT_TIMESTAMP
|monto|decimal(10,2)|No|
|id_medio_cobro|int|No|
|observacion|varchar(255)|Sí|NULL
== Estructura de tabla para la tabla pedido_proveedor

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id_pedido_proveedor**//|int|No|
|id_proveedor|int|No|
|fecha_pedido|datetime|Sí|CURRENT_TIMESTAMP
|fecha_entrega|date|Sí|NULL
|estado|enum(&#039;pendiente&#039;, &#039;recibido&#039;, &#039;cancelado&#039;)|Sí|pendiente
|observaciones|varchar(255)|Sí|NULL
== Estructura de tabla para la tabla proveedor

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id_proveedor**//|int|No|
|nombre|varchar(150)|Sí|NULL
|apellido|varchar(150)|Sí|NULL
|nombre_fantasia|varchar(150)|Sí|NULL
|telefono|varchar(30)|Sí|NULL
|direccion|varchar(150)|Sí|NULL
|activo|tinyint|Sí|1
== Estructura de tabla para la tabla provincia

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id**//|int|No|
|provincia|varchar(100)|Sí|NULL
== Estructura de tabla para la tabla qz_certificados

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id**//|int|No|
|**machine_id**|varchar(64)|No|
|nombre_maquina|varchar(100)|Sí|NULL
|cert_filename|varchar(255)|No|
|pk_filename|varchar(255)|No|
|fecha_modificacion|timestamp|Sí|CURRENT_TIMESTAMP
== Estructura de tabla para la tabla rol

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id**//|int|No|
|nombre|varchar(45)|Sí|NULL
|descripcion|varchar(255)|Sí|NULL
== Estructura de tabla para la tabla tipo_comprobante

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**Id_tipo_comp**//|bigint|No|
|tipo_comp|bigint|Sí|NULL
|denominacion|text|Sí|NULL
|denominacion_portal_AFIP|text|Sí|NULL
|tipo_letra|text|Sí|NULL
|factor_multiplicador|bigint|Sí|NULL
|activo|tinyint(1)|Sí|NULL
== Volcado de datos para la tabla tipo_comprobante

|1|1|FACTURAS A|1 - Factura A|A|1|1
|2|2|Notas DE DEBITO A|2 - Nota de Débito A|A|1|0
|3|3|Notas DE CREDITO A|3 - Nota de Crédito A|A|-1|1
|4|4|RECIBOS A|4 - Recibo A|A|1|0
|5|5|Notas DE VENTA AL CONTADO A|NULL|A|1|0
|6|6|FACTURAS B|6 - Factura B|B|1|1
|7|7|Notas DE DEBITO B|7 - Nota de Débito B|B|1|1
|8|8|Notas DE CREDITO B|8 - Nota de Crédito B|B|-1|1
|9|9|RECIBOS B|9 - Recibo B|B|1|1
|10|10|Notas DE VENTA AL CONTADO B|NULL|B|1|0
|11|11|FACTURAS C|11 - Factura C|C|1|1
|12|12|Notas DE DEBITO C|NULL|C|1|1
|13|13|Notas DE CREDITO C|13 - Nota de Crédito C|C|-1|0
|14|15|RECIBOS C|15 - Recibo C|C|1|1
|15|16|Notas DE VENTA AL CONTADO C|NULL|C|1|0
|16|17|LIQUIDACION DE SERVICIOS PUBLICOS CLASE A|17 - Liquidación de Servicios Públicos Clase A|A|1|0
|17|18|LIQUIDACION DE SERVICIOS PUBLICOS CLASE B|NULL|B|1|0
|18|19|FACTURAS DE EXPORTACION|19 - Factura de Exportación E|E|1|0
|19|20|Notas DE DEBITO POR OPERACIONES CON EL EXTERIOR|NULL|NULL|1|0
|20|21|Notas DE CREDITO POR OPERACIONES CON EL EXTERIOR|NULL|NULL|-1|0
|21|22|FACTURAS - PERMISO EXPORTACION SIMPLIFICADO - DTO. 855/97|NULL|NULL|1|0
|22|23|COMPROBANTES “A” DE COMPRA PRIMARIA PARA EL SECTOR PESQUERO MARITIMO|NULL|NULL|1|0
|23|24|COMPROBANTES “A” DE CONSIGNACION PRIMARIA PARA EL SECTOR PESQUERO MARITIMO|NULL|NULL|1|0
|24|25|COMPROBANTES “B” DE COMPRA PRIMARIA PARA EL SECTOR PESQUERO MARITIMO|NULL|NULL|1|0
|25|26|COMPROBANTES “B” DE CONSIGNACION PRIMARIA PARA EL SECTOR PESQUERO MARITIMO|NULL|NULL|1|0
|26|27|LIQUIDACION UNICA COMERCIAL IMPOSITIVA CLASE A|27 - LIQUIDACION UNICA COMERCIAL IMPOSITIVA CLASE A|A|1|0
|27|28|LIQUIDACION UNICA COMERCIAL IMPOSITIVA CLASE B|NULL|B|1|0
|28|29|LIQUIDACION UNICA COMERCIAL IMPOSITIVA CLASE C|NULL|C|1|0
|29|30|COMPROBANTES DE COMPRA DE BIENES USADOS|NULL|NULL|1|0
|30|32|COMPROBANTES PARA RECICLAR MATERIALES|NULL|NULL|1|0
|31|33|LIQUIDACION PRIMARIA DE GRANOS|33 - LIQUIDACION PRIMARIA DE GRANOS|A|1|0
|32|34|COMPROBANTES A DEL APARTADO A INCISO F R G N 1415|NULL|NULL|1|0
|33|35|COMPROBANTES B DEL ANEXO I, APARTADO A, INC. F), RG N° 1415|NULL|NULL|1|0
|34|36|COMPROBANTES C DEL ANEXO I, APARTADO A, INC.F), R.G. N° 1415|NULL|NULL|1|0
|35|37|Notas DE DEBITO O DOCUMENTO EQUIVALENTE QUE CUMPLAN CON LA R.G. N° 1415|NULL|NULL|1|0
|36|38|Notas DE CREDITO O DOCUMENTO EQUIVALENTE QUE CUMPLAN CON LA R.G. N° 1415|NULL|NULL|-1|0
|37|39|OTROS COMPROBANTES A QUE CUMPLEN CON LA R G 1415|39 - Otro Comprobante A que cumple con la RG 1415|A|1|0
|38|40|OTROS COMPROBANTES B QUE CUMPLAN CON LA R.G. 1415|NULL|NULL|1|0
|39|41|OTROS COMPROBANTES C QUE CUMPLAN CON LA R.G. 1415|NULL|NULL|1|0
|40|43|Notas DE CREDITO LIQUIDACION UNICA COMERCIAL IMPOSITIVA CLASE B|NULL|B|-1|0
|41|44|Notas DE CREDITO LIQUIDACION UNICA COMERCIAL IMPOSITIVA CLASE C|NULL|C|-1|0
|42|45|Notas DE DEBITO LIQUIDACION UNICA COMERCIAL IMPOSITIVA CLASE A|NULL|A|1|0
|43|46|Notas DE DEBITO LIQUIDACION UNICA COMERCIAL IMPOSITIVA CLASE B|NULL|B|1|0
|44|47|Notas DE DEBITO LIQUIDACION UNICA COMERCIAL IMPOSITIVA CLASE C|NULL|C|1|0
|45|48|Notas DE CREDITO LIQUIDACION UNICA COMERCIAL IMPOSITIVA CLASE A|NULL|A|-1|0
|46|49|COMPROBANTES DE COMPRA DE BIENES NO REGISTRABLES A CONSUMIDORES FINALES|NULL|NULL|1|0
|47|50|RECIBO FACTURA A REGIMEN DE FACTURA DE CREDITO|NULL|NULL|1|0
|48|51|FACTURAS M|51 - Factura M|M|1|0
|49|52|Notas DE DEBITO M|NULL|M|1|0
|50|53|Notas DE CREDITO M|53 - Nota de Crédito M|M|-1|0
|51|54|RECIBOS M|NULL|M|1|0
|52|55|Notas DE VENTA AL CONTADO M|NULL|M|1|0
|53|56|COMPROBANTES M DEL ANEXO I APARTADO A INC F R G N 1415|NULL|A|1|0
|54|57|OTROS COMPROBANTES M QUE CUMPLAN CON LA R G N 1415|NULL|NULL|1|0
|55|58|CUENTAS DE VENTA Y LIQUIDO PRODUCTO M|NULL|M|1|0
|56|59|LIQUIDACIONES M|NULL|M|1|0
|57|60|CUENTAS DE VENTA Y LIQUIDO PRODUCTO A|60 - CUENTAS DE VENTA Y LIQUIDO PRODUCTO A|A|1|0
|58|61|CUENTAS DE VENTA Y LIQUIDO PRODUCTO B|NULL|B|1|0
|59|63|LIQUIDACIONES A|63 - Liquidación A|NULL|1|0
|60|64|LIQUIDACIONES B|NULL|B|1|0
|61|66|DESPACHO DE IMPORTACION|66 - DESPACHO DE IMPORTACION|A|1|0
|62|68|LIQUIDACION C|68 - Liquidación C|c|1|0
|63|70|RECIBOS FACTURA DE CREDITO|NULL|NULL|-1|0
|64|81|TIQUE FACTURA A CONTROLADORES FISCALES|81 - Tique Factura A Controladores Fiscales|A|1|0
|65|82|TIQUE - FACTURA B|NULL|b|1|1
|66|83|TIQUE|83 - Tique|a|1|1
|67|90|Notas DE CREDITO OTROS COMP QUE NO CUMPLEN CON LA R G 1415 Y SUS MODIF|NULL|NULL|-1|0
|68|99|OTROS COMP QUE NO CUMPLEN CON LA R G 1415 Y SUS MODIF|NULL|NULL|1|0
|69|110|TIQUE Notas DE CREDITO|NULL|NULL|-1|0
|70|111|TIQUE FACTURA C|111 - TIQUE FACTURA C|c|1|1
|71|112|TIQUE Notas DE CREDITO A|NULL|a|-1|0
|72|113|TIQUE Notas DE CREDITO B|NULL|b|-1|0
|73|114|TIQUE Notas DE CREDITO C|NULL|c|-1|0
|74|115|TIQUE Notas DE DEBITO A|NULL|a|1|0
|75|116|TIQUE Notas DE DEBITO B|NULL|b|1|0
|76|117|TIQUE Notas DE DEBITO C|NULL|c|1|0
|77|118|TIQUE FACTURA M|NULL|m|1|0
|78|119|TIQUE Notas DE CREDITO M|NULL|m|-1|0
|79|120|TIQUE Notas DE DEBITO M|NULL|m|1|0
|80|150|LIQUIDACION DE COMPRA PRIMARIA PARA EL SECTOR TABACALERO A|NULL|a|1|0
|81|151|LIQUIDACION DE COMPRA PRIMARIA PARA EL SECTOR TABACALERO B|NULL|b|1|0
|82|157|CUENTA DE VENTA Y LÍQUIDO PRODUCTO A – SECTOR AVÍCOLA|NULL|a|1|0
|83|158|CUENTA DE VENTA Y LÍQUIDO PRODUCTO B – SECTOR AVÍCOLA|NULL|b|1|0
|84|159|LIQUIDACIÓN DE COMPRA A – SECTOR AVÍCOLA|NULL|a|1|0
|85|160|LIQUIDACIÓN DE COMPRA B – SECTOR AVÍCOLA|NULL|b|1|0
|86|161|LIQUIDACIÓN DE COMPRA DIRECTA A - SECTOR AVÍCOLA|NULL|a|1|0
|87|162|LIQUIDACIÓN DE COMPRA DIRECTA B - SECTOR AVÍCOLA|NULL|b|1|0
|88|163|LIQUIDACIÓN DE COMPRA DIRECTA C - SECTOR AVÍCOLA|NULL|c|1|0
|89|164|LIQUIDACIÓN DE VENTA DIRECTA A - SECTOR AVÍCOLA|NULL|a|1|0
|90|165|LIQUIDACIÓN DE VENTA DIRECTA B - SECTOR AVÍCOLA|NULL|b|1|0
|91|166|LIQUIDACIÓN DE CONTRATACIÓN DE CRIANZA POLLOS PARRILLEROS A|NULL|a|1|0
|92|167|LIQUIDACIÓN DE CONTRATACIÓN DE CRIANZA POLLOS PARRILLEROS B|NULL|b|1|0
|93|168|LIQUIDACIÓN DE CONTRATACIÓN DE CRIANZA POLLOS PARRILLEROS C|MAL ASIGNADO|c|1|0
|94|169|LIQUIDACIÓN DE CRIANZA POLLOS PARRILLEROS A|NULL|a|1|0
|95|170|LIQUIDACIÓN DE CRIANZA POLLOS PARRILLEROS B|NULL|b|1|0
|96|171|LIQUIDACIÓN DE COMPRA DE CAÑA DE AZÚCAR A|NULL|a|1|0
|97|172|LIQUIDACIÓN DE COMPRA DE CAÑA DE AZÚCAR B|NULL|b|1|0
|98|180|CUENTA DE VENTA Y LÍQUIDO PRODUCTO A - SECTOR PECUARIO|NULL|a|1|0
|99|182|CUENTA DE VENTA Y LÍQUIDO PRODUCTO B - SECTOR PECUARIO|NULL|b|1|0
|100|183|LIQUIDACIÓN DE COMPRA A - SECTOR PECUARIO|NULL|a|1|0
|101|185|LIQUIDACIÓN DE COMPRA B - SECTOR PECUARIO|NULL|b|1|0
|102|186|LIQUIDACIÓN DE COMPRA DIRECTA A - SECTOR PECUARIO|NULL|a|1|0
|103|188|LIQUIDACIÓN DE COMPRA DIRECTA B - SECTOR PECUARIO|NULL|b|1|0
|104|189|LIQUIDACIÓN DE COMPRA DIRECTA C - SECTOR PECUARIO|NULL|c|1|0
|105|190|LIQUIDACIÓN DE VENTA DIRECTA A - SECTOR PECUARIO|190 - LIQUIDACIÓN DE VENTA DIRECTA A - SECTOR PECUARIO|A|1|0
|106|191|LIQUIDACIÓN DE VENTA DIRECTA B - SECTOR PECUARIO|NULL|b|1|0
|107|195|FACTURA CLASE “T”|NULL|t|1|0
|108|196|Notas DE DEBITO CLASE “T”|NULL|t|1|0
|109|197|Notas DE CREDITO CLASE “T”|NULL|t|-1|0
|110|201|FACTURA DE CREDITO ELECTRÓNICA MiPyMEs (FCE) A|201 - Factura de Crédito Electrónica MyPyMEs (FCE) A|A|1|0
|111|202|Notas DE DEBITO ELECTRÓNICA MiPyMEs (FCE) A|202 - Nota de Débito Electrónica MyPyMEs (FCE) A|A|1|0
|112|203|Notas DE CREDITO ELECTRÓNICA MiPyMEs (FCE) A|203 - Nota de Crédito Electrónica MyPyMEs (FCE) A|a|-1|0
|113|206|FACTURA DE CREDITO ELECTRÓNICA MiPyMEs (FCE) B|NULL|b|-1|1
|114|207|Notas DE DEBITO ELECTRÓNICA MiPyMEs (FCE) B|NULL|b|1|0
|115|208|Notas DE CREDITO ELECTRÓNICA MiPyMEs (FCE) B|NULL|b|-1|0
|116|211|FACTURA DE CREDITO ELECTRÓNICA MiPyMEs (FCE) C|NULL|c|-1|1
|117|212|Notas DE DEBITO ELECTRÓNICA MiPyMEs (FCE) C|NULL|c|1|0
|118|213|Notas DE CREDITO ELECTRÓNICA MiPyMEs (FCE) C|NULL|c|-1|0
|119|331|LIQUIDACION SECUNDARIA DE GRANOS|NULL|NULL|1|0
|120|332|CERTIFICACION ELECTRONICA (GRANOS)|NULL|NULL|1|0
|122|80|INFORME DIARIO DE CIERRE (ZETA) - CONTROLADORES FISCALES|80 - Tique Z (tipo C)|C|0|0
|123|9999|Comprobante de pago electrónico|Comprobante de pago electrónico|B|1|1
|125|0|NULL|NULL|NULL|0|0
|126|88|Remito|88 - Remito|NULL|-1|1
== Estructura de tabla para la tabla tipo_concepto

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**Id_tipo_concepto**//|bigint|No|
|cod_concepto|bigint|Sí|NULL
|descripcion_concepto|text|Sí|NULL
== Volcado de datos para la tabla tipo_concepto

|1|1|Productos
|2|2|Servicios
|3|3|Productos y Servicios
== Estructura de tabla para la tabla tipo_evento_partido

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id**//|int|No|
|**descripcion**|varchar(50)|No|
|activo|tinyint|Sí|1
== Estructura de tabla para la tabla torneo

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id**//|int|No|
|nombre|varchar(150)|Sí|NULL
|descripcion|varchar(255)|Sí|NULL
|id_disciplina|int|Sí|NULL
|id_estado_torneo|int|Sí|NULL
|fecha_inicio|date|Sí|NULL
|fecha_fin|date|Sí|NULL
|fecha_fin_planificada|date|Sí|NULL
|cupo_equipos|int|Sí|NULL
|valor_inscripcion|decimal(10,2)|Sí|NULL
|formato_manual|varchar(50)|Sí|NULL
|configuracion_json|text|Sí|NULL
|activo|tinyint(1)|No|1
|deleted_at|datetime|Sí|NULL
|deleted_by|int|Sí|NULL
|motivo_baja|varchar(255)|Sí|NULL
== Estructura de tabla para la tabla usuario

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id**//|int|No|
|nombre|varchar(255)|Sí|NULL
|email|varchar(50)|Sí|NULL
|contrasena|varchar(255)|Sí|NULL
|id_rol|int|No|
== Estructura de tabla para la tabla usuario_modulo

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id**//|int|No|
|id_modulo|int|No|
|id_usuario|int|No|
|favorito|tinyint(1)|Sí|0
|orden_usuario|int|Sí|NULL
== Estructura de tabla para la tabla venta

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id**//|int|No|
|fecha|date|Sí|NULL
|id_equipo|int|Sí|NULL
|descripcion_cliente|varchar(255)|Sí|NULL
|id_estado_venta|int|No|
|simbolo|varchar(45)|No|
|id_cliente|int|Sí|NULL
|tipo_vta|tinyint(1)|Sí|1
|es_ajuste|tinyint(1)|Sí|0
|id_factura|int|Sí|NULL
|facturada|tinyint|Sí|0
== Estructura de tabla para la tabla venta_cobro

|------
|Columna|Tipo|Nulo|Predeterminado
|------
|//**id_venta_cobro**//|int|No|
|id_venta|int|No|
|id_cobro|int|No|
|id_medio_pago|int|No|
|monto|double(10,2)|Sí|NULL
