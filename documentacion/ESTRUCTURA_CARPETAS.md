# Estructura de Carpetas — Il-Calcio-Camp

---

## Frontend (`src/`)

### `src/views/`
Vistas principales de la aplicación organizadas por dominio.

| Carpeta | Descripción |
|---|---|
| `auth/` | Pantallas de acceso: login y registro de usuario. |
| `usuarios/` | Gestión de usuarios, permisos por rol y configuraciones globales del sistema. |
| `torneos/` | Todo el ciclo deportivo: árbitros, equipos, jugadores, torneos, plan de torneos, resultados de partidos y tabla de posiciones. Incluye el registro público de inscripción de equipos. |
| `instalaciones/` | Gestión de canchas, eventos y la grilla de ocupación de canchas. |
| `inventario/` | Artículos, categorías, ingresos de stock, stock actual, descuento de stock, proveedores y pedidos a proveedores. |
| `comercial/` | Clientes, relación cliente-equipo, ventas y cobros. |

Las vistas `MenuView.vue` y `SubmenuView.vue` permanecen en la raíz de `views/` por ser componentes de layout global.

---

### `src/services/`
Capa de comunicación con la API REST. Cada archivo agrupa las llamadas HTTP de su dominio.

| Carpeta / Archivo | Descripción |
|---|---|
| `api.js` | Instancia base de Axios con interceptor que inyecta el token JWT en cada petición. |
| `publicApi.js` | Instancia de Axios sin autenticación, usada por el registro público. |
| `datosMaestrosService.js` | Catálogos compartidos entre dominios: canchas, equipos, torneos, árbitros, disciplinas, provincias, condiciones IVA, medios de cobro, etc. |
| `impresoraTiqueteraService.js` | Gestión de impresoras térmicas por máquina. |
| `qzCertificadoService.js` | Gestión de certificados para QZ Tray (impresión directa). |
| `usuarios/` | Servicios de usuarios, permisos, módulos y configuración del sistema. |
| `torneos/` | Servicios de plan de torneo y dashboard de resultados. |
| `instalaciones/` | Servicio de eventos (creación, edición, estados). |
| `inventario/` | Servicios de artículos y proveedores. |
| `comercial/` | Servicios de clientes, cobros y ventas. |

---

### `src/components/`
Componentes reutilizables.

| Archivo / Carpeta | Descripción |
|---|---|
| `ConfirmModal.vue` | Modal genérico de confirmación (Sí / No). |
| `FuzzySearch.vue` | Input de búsqueda con filtrado aproximado. |
| `SortableTableHead.vue` | Cabecera de tabla con ordenamiento. Exporta el composable `useSorting`. |
| `CustomNumberInput.vue` | Input numérico con controles +/−. |
| `ToastNotification.vue` | Componente de notificaciones tipo toast. |
| `Header.vue` | Barra de navegación superior. |
| `torneos/TorneoCalendar.vue` | Calendario visual de fixtures de torneo. |
| `venta/VentaModal.vue` | Modal completo de creación/edición de venta. |
| `venta/DetallesVentaModal.vue` | Modal de detalle de una venta existente. |
| `venta/QuickClientModal.vue` | Alta rápida de cliente desde la pantalla de venta. |
| `venta/QuickAssignTeamModal.vue` | Asignación rápida de equipo a cliente desde la venta. |

---

### `src/stores/`
Estado global con Pinia.

| Archivo | Descripción |
|---|---|
| `userStore.js` | Datos del usuario autenticado, token JWT y estado de sesión. |
| `toastStore.js` | Cola de notificaciones toast accesible desde cualquier vista. |

---

### `src/composables/`

| Archivo | Descripción |
|---|---|
| `usePrinterConfig.js` | Lógica de conexión y configuración de QZ Tray para impresión directa de tickets. |

---

### `src/utils/`

| Archivo | Descripción |
|---|---|
| `formatters.js` | Funciones de formato: moneda, fechas, etc. |
| `incidencias.js` | Constantes y helpers de tipos de incidencias de partido (goles, tarjetas, etc.). |

---

### `src/router/`
Definición de rutas de Vue Router con guards de autenticación y control de acceso por módulo (`idModulo` en `meta`).

---
---

## Backend (`api/`)

### `api/core/`
Clases base que sostienen toda la arquitectura.

| Archivo | Descripción |
|---|---|
| `Env.php` | Lee el archivo `.env` e inyecta las variables en el proceso PHP. |
| `Database.php` | Crea y expone la única conexión PDO de la petición. |
| `JwtHandler.php` | Genera y valida tokens JWT (HS256, expiración 24h). |
| `BaseController.php` | Clase padre de todos los controladores. Provee `respond()` para estandarizar respuestas JSON y `handleError()` para manejo centralizado de excepciones. |

---

### `api/controllers/`
Reciben la petición HTTP, validan autorización y delegan en los modelos. Organizados por dominio:

| Carpeta | Controladores |
|---|---|
| `auth/` | `AuthController` (login/logout), `RegistroPublicoController` (inscripción de equipos sin auth). |
| `usuarios/` | `UsuarioController`, `PermisoController`, `ModuloController`, `ConfiguracionController`. |
| `torneos/` | `ArbitroController`, `EquipoController`, `JugadorController`, `TorneoController`, `PlanTorneoController`, `ClienteEquipoController`, `EventoPartidoController`, `TipoEventoPartidoController`. |
| `instalaciones/` | `CanchaController`, `EventoController`, `EstadoEventoController`. |
| `inventario/` | `ArticuloController`, `CategoriaArticuloController`, `IngresoArticuloController`, `ArticuloVentaIngresoArticuloController`, `ProveedorController`, `PedidoProveedorController`, `PagoProveedorController`. |
| `comercial/` | `ClienteController`, `VentaController`, `ArticuloVentaController`, `CobroController`, `VentaCobroController`, `TicketVentaController`, `SimboloDiaController`. |
| `catalogo/` | Datos de referencia de solo lectura: `DisciplinaController`, `CondicionIvaReceptorController`, `ProvinciaController`, `EstadoVentaController`, `MedioCobroController`. |
| `hardware/` | `ImpresoraTiqueteraController`, `QzCertificadoController`. |

---

### `api/models/`
Encapsulan todas las queries SQL usando PDO con sentencias preparadas. Organizados por dominio (misma estructura que `controllers/`).

| Carpeta | Modelos |
|---|---|
| `auth/` | `Usuario` |
| `usuarios/` | `Configuracion`, `Modulo` |
| `torneos/` | `Arbitro`, `Equipo`, `Jugador`, `Torneo`, `ClienteEquipo`, `EventoPartido`, `TipoEventoPartido` |
| `instalaciones/` | `Cancha`, `Evento`, `EstadoEvento` |
| `inventario/` | `Articulo`, `ArticuloVenta`, `ArticuloVentaIngresoArticulo`, `CategoriaArticulo`, `IngresoArticulo`, `Proveedor`, `PedidoProveedor`, `PagoProveedor` |
| `comercial/` | `Cliente`, `Venta`, `Cobro`, `VentaCobro` |
| `catalogo/` | `Disciplina`, `CondicionIvaReceptor`, `Provincia`, `EstadoVenta`, `MedioCobro` |
| `hardware/` | `ImpresoraTiquetera`, `QzCertificado` |

---

### Archivos raíz de la API

| Archivo | Descripción |
|---|---|
| `index.php` | Punto de entrada. Configura CORS, parsea la URI, carga el `.env` y delega en `rutas.php`. |
| `rutas.php` | Carga todos los controladores, instancia la conexión DB, define `verifyAuth()` y resuelve cada ruta mediante un `switch` sobre el recurso y método HTTP. |

---

### `api/uploads/`

| Carpeta | Descripción |
|---|---|
| `escudos/` | Imágenes de escudos de equipos subidas por los usuarios. |
| `qz_certs/` | Certificados digitales para QZ Tray. |
