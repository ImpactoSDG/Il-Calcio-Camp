# Plan de Implementacion - Planificacion de Torneos

## Objetivo

Implementar el flujo completo en dos etapas:

1. Simulacion y confirmacion de estructura.
2. Asignacion posterior de equipos existentes y actualizacion de cruces/eventos.

Regla de negocio acordada: solo se guardan corridas confirmadas.

---

## Fase 1 - Confirmacion minima (ejecutar primero)

### Endpoint: POST /planificacion-torneo/simular

- [x] Validar input de simulacion.
- [x] Devolver resumen, zonas, llave y observaciones.
- [x] No persistir datos.

### Endpoint: POST /planificacion-torneo/confirmar

- [x] Validar id_torneo.
- [x] Verificar existencia de torneo.
- [x] Bloquear si ya existe confirmada vigente (409).
- [x] Recalcular o validar resultado_simulacion.
- [x] Persistir corrida en generacion_fixture (estado CONFIRMADA) dentro de transaccion.
- [x] Devolver id de corrida confirmada.

### Endpoint: GET /planificacion-torneo/confirmada

- [x] Recibir id_torneo por query.
- [x] Devolver ultima corrida CONFIRMADA.
- [x] Responder 404 si no existe.

---

## Fase 2 - Persistencia estructural de fixture

### Confirmacion extendida

- [x] Crear fase_torneo para grupos (si aplica).
- [x] Crear fase_torneo para eliminacion.
- [x] Crear grupo_torneo para zonas.
- [x] Crear cruce_torneo con origenes logicos (1A, 2B, etc).
- [x] Crear eventos del fixture con estado PROGRAMADO.
- [x] Devolver ids de fases, grupos, cruces y eventos.

### Frontend de confirmacion

- [x] Agregar selector de torneo en la vista /plantorneo.
- [x] Agregar boton "Confirmar simulacion" conectado a endpoint.
- [x] Mostrar feedback con IDs persistidos.

---

## Fase 3 - Asignacion de equipos existentes

### Endpoint: GET /planificacion-torneo/equipos-disponibles

- [x] Listar equipos activos.
- [x] Excluir equipos ya vinculados al torneo si corresponde.

### Endpoint: POST /planificacion-torneo/asignar-equipos

- [x] Validar corrida CONFIRMADA.
- [x] Validar equipos existentes, activos y sin duplicados.
- [x] Insertar en grupo_torneo_equipo.
- [x] Resolver placeholders de cruces/eventos (1A, 2B) a equipos reales.
- [x] Mantener estado_evento = PROGRAMADO.

### Vista /gestiontorneos

- [x] Crear vista para seleccionar torneo y ver resumen.
- [x] Mostrar grupos y asignaciones actuales.
- [x] Permitir asignar equipos disponibles por grupo/posicion.
- [x] Guardar asignacion y refrescar datos.

---

## Fase 4 - Replanificacion (pendiente de regla final)

- [ ] Definir politica cuando ya existen resultados cargados.
- [ ] Permitir nueva version solo si no rompe historial competitivo.
- [ ] Mantener trazabilidad por generacion_fixture.

---

## Criterios de QA minimos

- [ ] Simulacion valida para zonas + eliminacion.
- [x] Confirmacion valida crea corrida CONFIRMADA.
- [x] Confirmacion duplicada retorna 409.
- [x] Consulta de confirmada retorna ultima corrida.
- [ ] Errores de validacion retornan 400/404/409 segun corresponda.
