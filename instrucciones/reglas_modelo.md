# Reglas y Decisiones del Modelo ILCAMP

## Jugador / Equipo

- La relacion entre jugador y equipo se resuelve con la tabla `jugador_equipo`.
- Se modela como N:M porque un jugador puede pertenecer a mas de un equipo si participa en distintos deportes.
- `jugador_equipo` representa la vinculacion vigente o historica entre un jugador y un equipo.
- `fecha_desde` indica desde cuando el jugador pertenece al equipo.
- `fecha_hasta` en `null` indica que la vinculacion sigue activa.
- `fecha_hasta` con valor indica que la vinculacion finalizo.

## Historial Jugador / Equipo

- Los cambios de asignacion deben registrarse en `jugador_equipo_hist`.
- `jugador_equipo_hist` guarda trazabilidad de altas, bajas y modificaciones.
- `fecha_cambio` registra cuando se produjo el cambio.
- `accion` puede indicar por ejemplo: `ALTA`, `BAJA`, `ACTUALIZACION`.

## Disciplina / Equipo

- `equipo.id_disciplina` referencia a `disciplina.id`.
- Definir mas adelante si esta relacion debe ser obligatoria con `not null`.

## Pendientes

- [ ] Validar si un jugador puede tener dos equipos dentro de la misma disciplina.
- [ ] Evaluar si `numero_camiseta` debe ir en `jugador_equipo`.
- [ ] Evaluar si `capitan` debe ir en `jugador_equipo`.
- [ ] Definir restriccion de `dni` unico para `jugador`.
- [ ] Definir si `fecha_alta` de `jugador` es obligatoria.

## Torneos y Generacion de Fixtures

- La generacion de fechas, cruces y programacion se resolvera por codigo, algoritmo o IA.
- Esa logica trabajara sobre las tablas existentes y generara eventos o programacion segun lo solicitado.
- Debe poder soportar formatos como zonas, eliminacion directa y otros esquemas futuros.
- De momento no se modela la logica de generacion dentro de la base.
- `tipo_torneo` no es obligatorio en esta etapa si el comportamiento lo resuelve la capa de aplicacion.
- Se puede agregar `tipo_torneo` mas adelante si hace falta clasificar, filtrar o parametrizar torneos de forma persistente.
- El algoritmo no se guarda en la base: lo que se persiste es la configuracion de entrada, la estructura generada y la trazabilidad de la generacion.

## Torneo

- `torneo` representa una competencia concreta.
- Un torneo debe existir aunque la logica de fechas y cruces se genere por codigo.

### Campos sugeridos de torneo

- `id`
- `nombre`
- `descripcion`
- `id_disciplina`
- `id_estado_torneo`
- `fecha_inicio`
- `fecha_fin`
- `fecha_fin_planificada`
- `cupo_equipos`
- `formato_manual`
- `reglas_json` o `configuracion_json`

- `formato_manual` podria guardar un valor simple como `ZONAS`, `ELIMINATORIA` o `MIXTO` solo si hace falta mostrarlo o filtrarlo.
- `configuracion_json` podria guardar parametros como cantidad de zonas, ida y vuelta, clasificados por zona, etc.

## Estado de Torneo

- Se crea una tabla `estado_torneo` para manejar los estados posibles de un torneo.
- `torneo.id_estado_torneo` representa el estado actual del torneo.
- Estados basicos sugeridos: `BORRADOR`, `PLANIFICADO`, `INSCRIPCION`, `EN_CURSO`, `FINALIZADO`, `CANCELADO`.
- Se crea una tabla `estado_torneo_hist` para guardar el historial de cambios de estado.
- `estado_torneo_hist` registra torneo, estado, fecha de cambio y observacion.

## Inscripcion de Equipos a Torneo

- Se crea una tabla `equipo_torneo` para registrar las inscripciones de equipos a cada torneo.
- `equipo_torneo` resuelve la relacion entre equipo y torneo.
- Campos base definidos: `id`, `id_equipo`, `id_torneo`, `id_estado_inscripcion`, `fecha_inscripcion`, `fecha_pago`, `comprobante_pago`, `observacion`.
- Se crea una tabla `estado_inscripcion` para manejar el estado integral de la inscripcion.
- En `estado_inscripcion` se resuelve tanto la situacion administrativa como la de pago.
- Estados sugeridos: `PENDIENTE`, `PENDIENTE_PAGO`, `PAGO_EN_REVISION`, `INSCRIPTA`, `RECHAZADA`, `BAJA`.
- `fecha_pago` registra la fecha en que la inscripcion quedo paga o se imputo el pago principal.
- `comprobante_pago` guarda una URL del comprobante subido por quien se inscribe.
- Esa URL apunta a un recurso almacenado en el servidor propio de la aplicacion.
- Esta tabla puede ampliarse mas adelante con datos como `seed`, zona inicial, prioridad o pago de inscripcion.

## Valor de Inscripcion

- `torneo.valor_inscripcion` representa el valor base de inscripcion al torneo.
- Si mas adelante hiciera falta manejar descuentos, recargos o pagos parciales, eso deberia resolverse con una tabla de pagos de inscripcion.

## Fases, Grupos, Cruces y Generacion

- Se crea una tabla `fase_torneo` para persistir las etapas generadas de cada torneo.
- `fase_torneo` puede representar ejemplos como `FASE_DE_GRUPOS`, `OCTAVOS`, `CUARTOS`, `SEMIFINAL`, `FINAL`.
- Campos base sugeridos para `fase_torneo`: `id`, `id_torneo`, `nombre`, `tipo_fase`, `orden`, `configuracion_json`.
- Se crea una tabla `grupo_torneo` para representar zonas o grupos dentro de una fase cuando aplique.
- Campos base sugeridos para `grupo_torneo`: `id`, `id_fase_torneo`, `nombre`, `orden`, `cantidad_equipos_objetivo`, `criterio_asignacion`.
- `grupo_torneo` define la estructura del grupo y su capacidad esperada.
- Se crea una tabla `grupo_torneo_equipo` para persistir que equipos quedaron asignados a cada grupo.
- Campos base sugeridos para `grupo_torneo_equipo`: `id`, `id_grupo_torneo`, `id_equipo_torneo`, `posicion_inicial`.
- La composicion de grupos (que equipo va en cada grupo) debe guardarse en `grupo_torneo_equipo`.
- Se crea una tabla `cruce_torneo` para guardar la estructura de llaves o cruces de una fase.
- Campos base sugeridos para `cruce_torneo`: `id`, `id_fase_torneo`, `id_grupo_torneo`, `id_evento`, `nombre`, `orden`, `origen_local_tipo`, `origen_local_valor`, `origen_visitante_tipo`, `origen_visitante_valor`.
- `cruce_torneo` permite guardar origenes logicos como `GANADOR_EVENTO`, `PRIMERO_GRUPO`, `SEGUNDO_GRUPO` y no solo equipos fijos.
- `id_evento` en `cruce_torneo` vincula el cruce con el partido o evento concreto programado.
- Se crea una tabla `generacion_fixture` para registrar cada corrida del algoritmo o motor generador.
- Campos base sugeridos para `generacion_fixture`: `id`, `id_torneo`, `fecha_generacion`, `motor_generacion`, `version_algoritmo`, `parametros_json`, `resultado_json`, `estado`, `observacion`.
- `generacion_fixture` permite auditar con que parametros se genero un fixture y distinguir simulaciones de generaciones definitivas.
- En este modelo, el algoritmo calcula en memoria y luego persiste filas en `fase_torneo`, `grupo_torneo`, `cruce_torneo`, `evento` y `generacion_fixture`.
- Para el flujo inicial de usuario, la entrada deberia ser simple: cantidad de equipos, zonas si/no, y llave eliminatoria de X equipos.
- Con esos datos, el algoritmo deberia devolver primero un resumen de planificacion antes de persistir detalle.
- Ese resumen puede incluir: cantidad aproximada de partidos, estructura de llave, cantidad minima de partidos por equipo y observaciones de consistencia.
- En una primera etapa, `fase_torneo`, `grupo_torneo` y `cruce_torneo` no son obligatorios para pedir datos al usuario.
- Esas tablas sirven mas como persistencia interna del resultado cuando el usuario confirma la planificacion generada.
- Si solo se quiere simular y devolver una propuesta, podria alcanzarse con leer `torneo.configuracion_json` y guardar la salida resumida en `generacion_fixture.resultado_json`.
- Si luego el usuario aprueba la propuesta, recien ahi conviene persistir `fase_torneo`, `grupo_torneo`, `grupo_torneo_equipo`, `cruce_torneo` y `evento`.

## Flujo de Planificacion para Organizador

- El organizador debe poder cargar un input simple de planificacion: con zonas si/no, cantidad total de equipos, cantidad de zonas o equipos por zona, y llave eliminatoria de X equipos.
- Con ese input, el algoritmo debe devolver una organizacion basica estructural y grafica.
- En esta etapa no se asignan equipos concretos a grupos ni cruces.
- La salida esperada es una propuesta editable: estructura de fases, grupos, llaves y cantidad aproximada de partidos.
- La propuesta debe incluir una referencia de cantidad minima de partidos por equipo segun el formato.
- El organizador puede aceptar la propuesta o reformular y volver a simular.
- Solo despues de aceptar se persiste el detalle final en tablas estructurales y eventos programados.
- Una vez confirmada la estructura, se realiza la asignacion aleatoria de equipos a zonas y/o llaves.
- La asignacion aleatoria debe persistirse en `grupo_torneo_equipo` y, cuando aplique, en cruces o eventos vinculados.
- La corrida de asignacion debe quedar trazada en `generacion_fixture` para poder auditar o repetir la misma semilla.

## Eventos y Partidos

- `evento` representa una unidad programable dentro del torneo.
- `evento` debe servir para fechas generadas, programacion de partidos y otros tipos de agenda que puedan aparecer.
- `evento` se vincula a torneo, `estado_evento` y opcionalmente a `cancha`.
- Se crea una tabla `arbitro` y `evento` puede vincular un arbitro asignado mediante `id_arbitro`.
- `id_arbitro` es opcional y aplica principalmente cuando `tipo_evento = partido`.
- `evento` puede vincular equipo local y equipo visitante cuando se trate de un partido.
- `tipo_evento` se resuelve con un enum cerrado.
- Valores permitidos para `tipo_evento`: `partido`, `festejo`, `reunion`, `otro`.
- `numero_fecha` permite agrupar eventos por fecha o jornada.
- `fecha_hora_inicio` y `fecha_hora_fin` permiten programar el evento.
- `resultado_local` y `resultado_visitante` representan el marcador resumido del partido.
- `resultado_penales_local` y `resultado_penales_visitante` permiten guardar una definicion por penales cuando exista.
- Estos campos aplican principalmente cuando `tipo_evento = partido`.
- A nivel de persistencia no conviene guardar el resultado como texto mezclado.
- Si hay penales, la aplicacion puede mostrar el resultado con formato visual como: `local 1 (5) vs visitante 1 (4)`.
- En base se guardan separados los goles del tiempo regular y los penales para facilitar calculos, filtros y estadisticas.

## Detalle de Partido

- `evento_partido` registra incidencias del partido asociadas a un evento.
- Cada fila de `evento_partido` representa una incidencia puntual del partido.
- `tipo_evento_partido` define la clase de incidencia, por ejemplo: `GOL`, `TARJETA_AMARILLA`, `TARJETA_ROJA`.
- La definicion por penales tambien se registra en `evento_partido` como otro `tipo_evento_partido`.
- `evento_partido` debe indicar de que jugador fue la incidencia.
- `evento_partido` tambien registra el equipo del jugador para evitar ambiguedad historica.
- `minuto` permite guardar en que momento del partido ocurrio la incidencia.
- `observacion` permite guardar detalle adicional si hace falta.
- A futuro, al finalizar un partido, el sistema puede recalcular automaticamente `resultado_local` y `resultado_visitante` a partir de las incidencias de tipo `GOL` registradas en `evento_partido`.
- En ese flujo, `evento_partido` seria la fuente de verdad del detalle y `evento` guardaria el resumen final del marcador.
- Si hubiera definicion por penales, el sistema puede completar `resultado_penales_local` y `resultado_penales_visitante` al cierre del evento.
- En este modelo, los penales ejecutados, convertidos o fallados deben salir de `evento_partido` y no cargarse manualmente solo en el resumen.

## Estado de Evento

- `estado_evento` define estados como `PROGRAMADO`, `REPROGRAMADO`, `EN_CURSO`, `FINALIZADO`, `SUSPENDIDO`, `CANCELADO`.
- `estado_evento_hist` guarda la trazabilidad de cambios de estado de cada evento.

## Arbitros

- Se crea una tabla `arbitro` para gestionar los arbitros disponibles.
- Campos base sugeridos para `arbitro`: `id`, `nombre`, `apellido`, `dni`, `telefono`, `email`, `activo`.
- Un evento de tipo partido puede tener un arbitro asignado desde `evento.id_arbitro`.
- En eventos no deportivos o sin designacion, `id_arbitro` puede quedar en `null`.

## Criterio Actual

- Priorizar una tabla `torneo` flexible y simple.
- Evitar crear `tipo_torneo` hasta tener un caso claro donde aporte valor real al modelo.