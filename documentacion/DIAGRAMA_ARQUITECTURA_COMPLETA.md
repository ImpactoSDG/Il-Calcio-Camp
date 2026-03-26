# 📋 DIAGRAMA DE ARQUITECTURA DEL PROYECTO IL CALCIO CAMP

Estructura organizada por dominio de negocio con sus respectivos **Views**, **Services**, **Components**, **Controllers** y **Models**.

---

## 🔐 AUTH (Autenticación y Accesos)

```
📁 auth/
├── 📄 Frontend (src/)
│   ├── 🎨 Views
│   │   ├── LoginView.vue
│   │   └── RegisterView.vue
│   ├── 🔧 Services
│   │   └── (importa via publicApi.js)
│   └── 📦 Components
│       └── (componentes genéricos compartidos)
│
└── 🖥️ Backend (api/)
    ├── 🎛️ Controllers
    │   ├── AuthController.php
    │   └── RegistroPublicoController.php
    └── 📊 Models
        └── Usuario.php
```

---

## 👥 USUARIOS (Gestión de Usuarios y Permisos)

```
📁 usuarios/
├── 📄 Frontend (src/)
│   ├── 🎨 Views
│   │   ├── UsuariosView.vue
│   │   ├── GestionUsuariosView.vue
│   │   ├── PermisosView.vue
│   │   └── ConfiguracionesView.vue
│   ├── 🔧 Services
│   │   ├── usuariosService.js
│   │   ├── permisosService.js
│   │   ├── moduloService.js
│   │   ├── configuracionService.js
│   │   └── (importan via api.js)
│   └── 📦 Components
│       └── (componentes compartidos: ConfirmModal, FuzzySearch, etc.)
│
└── 🖥️ Backend (api/)
    ├── 🎛️ Controllers
    │   ├── UsuarioController.php
    │   ├── PermisoController.php
    │   ├── ModuloController.php
    │   └── ConfiguracionController.php
    └── 📊 Models
        ├── Usuario.php
        ├── Permiso.php
        ├── Modulo.php
        └── Configuracion.php
```

---

## 🏆 TORNEOS (Gestión Deportiva)

```
📁 torneos/
├── 📄 Frontend (src/)
│   ├── 🎨 Views
│   │   ├── ArbitrosView.vue
│   │   ├── CalendarioTorneosView.vue
│   │   ├── EquiposView.vue
│   │   ├── GestionTorneosView.vue
│   │   ├── JugadoresView.vue
│   │   ├── PlanTorneoView.vue
│   │   ├── RtadoPartidoView.vue
│   │   ├── RtadoTorneoView.vue
│   │   └── RegistroPublicoView.vue
│   ├── 🔧 Services
│   │   ├── torneoDashboardService.js
│   │   ├── planTorneoService.js
│   │   └── (acceso a endpoints via api.js)
│   └── 📦 Components
│       ├── components/torneos/TorneoCalendar.vue
│       └── (componentes compartidos)
│
└── 🖥️ Backend (api/)
    ├── 🎛️ Controllers
    │   ├── ArbitroController.php
    │   ├── EquipoController.php
    │   ├── JugadorController.php
    │   ├── TorneoController.php
    │   ├── PlanTorneoController.php
    │   ├── ClienteEquipoController.php
    │   ├── EventoPartidoController.php
    │   └── TipoEventoPartidoController.php
    └── 📊 Models
        ├── Arbitro.php
        ├── Equipo.php
        ├── Jugador.php
        ├── Torneo.php
        ├── PlanTorneo.php
        ├── ClienteEquipo.php
        ├── EventoPartido.php
        └── TipoEventoPartido.php
```

---

## 🏢 INSTALACIONES (Canchas y Eventos)

```
📁 instalaciones/
├── 📄 Frontend (src/)
│   ├── 🎨 Views
│   │   ├── CanchasView.vue
│   │   ├── GrillaCanchasView.vue
│   │   └── EventosView.vue
│   ├── 🔧 Services
│   │   ├── eventosService.js
│   │   └── (acceso via api.js)
│   └── 📦 Components
│       └── (componentes compartidos)
│
└── 🖥️ Backend (api/)
    ├── 🎛️ Controllers
    │   ├── CanchaController.php
    │   ├── EventoController.php
    │   └── EstadoEventoController.php
    └── 📊 Models
        ├── Cancha.php
        ├── Evento.php
        └── EstadoEvento.php
```

---

## 📦 INVENTARIO (Stock y Proveedores)

```
📁 inventario/
├── 📄 Frontend (src/)
│   ├── 🎨 Views
│   │   ├── ArticulosView.vue
│   │   ├── CategoriasArticuloView.vue
│   │   ├── DescontarStockView.vue
│   │   ├── IngresoArticuloView.vue
│   │   ├── StockView.vue
│   │   ├── ProveedoresView.vue
│   │   └── PedidosProveedorView.vue
│   ├── 🔧 Services
│   │   ├── articulosService.js
│   │   ├── proveedoresService.js
│   │   └── (acceso via api.js)
│   └── 📦 Components
│       └── (componentes compartidos)
│
└── 🖥️ Backend (api/)
    ├── 🎛️ Controllers
    │   ├── ArticuloController.php
    │   ├── CategoriaArticuloController.php
    │   ├── IngresoArticuloController.php
    │   ├── ProveedorController.php
    │   ├── PedidoProveedorController.php
    │   ├── ArticuloVentaIngresoArticuloController.php
    │   └── PagoProveedorController.php
    └── 📊 Models
        ├── Articulo.php
        ├── CategoriaArticulo.php
        ├── IngresoArticulo.php
        ├── Proveedor.php
        ├── PedidoProveedor.php
        ├── ArticuloVenta.php
        ├── ArticuloVentaIngresoArticulo.php
        └── PagoProveedor.php
```

---

## 💳 COMERCIAL (Ventas y Clientes)

```
📁 comercial/
├── 📄 Frontend (src/)
│   ├── 🎨 Views
│   │   ├── ClientesView.vue
│   │   ├── ClienteEquipoView.vue
│   │   ├── CobroView.vue
│   │   └── VentasView.vue
│   ├── 🔧 Services
│   │   ├── clientesService.js
│   │   ├── ventasService.js
│   │   ├── cobrosService.js
│   │   └── (acceso via api.js)
│   └── 📦 Components
│       ├── components/venta/VentaModal.vue
│       ├── components/venta/DetallesVentaModal.vue
│       ├── components/venta/QuickClientModal.vue
│       ├── components/venta/QuickAssignTeamModal.vue
│       └── (componentes compartidos)
│
└── 🖥️ Backend (api/)
    ├── 🎛️ Controllers
    │   ├── ClienteController.php
    │   ├── VentaController.php
    │   ├── CobroController.php
    │   ├── ArticuloVentaController.php
    │   ├── TicketVentaController.php
    │   ├── VentaCobroController.php
    │   └── SimboloDiaController.php
    └── 📊 Models
        ├── Cliente.php
        ├── Venta.php
        ├── Cobro.php
        ├── ArticuloVenta.php
        └── VentaCobro.php
```

---

## 📋 CATALOGO (Datos Maestros - Shared)

```
📁 catalogo/
├── 📄 Frontend (src/)
│   └── 🔧 Services
│       └── datosMaestrosService.js (acceso centralizado via api.js)
│
└── 🖥️ Backend (api/)
    ├── 🎛️ Controllers
    │   ├── DisciplinaController.php
    │   ├── CondicionIvaReceptorController.php
    │   ├── EstadoVentaController.php
    │   ├── MedioCobroController.php
    │   └── ProvinciaController.php
    └── 📊 Models
        ├── Disciplina.php
        ├── CondicionIvaReceptor.php
        ├── EstadoVenta.php
        ├── MedioCobro.php
        └── Provincia.php
```

---

## 🖨️ HARDWARE (Impresoras y Certificados)

```
📁 hardware/
├── 📄 Frontend (src/)
│   ├── 🔧 Services
│   │   ├── impresoraTiqueteraService.js
│   │   ├── qzCertificadoService.js
│   │   └── (acceso via api.js)
│   └── 🧩 Composables
│       └── usePrinterConfig.js (manejo de QZ Tray)
│
└── 🖥️ Backend (api/)
    ├── 🎛️ Controllers
    │   ├── ImpresoraTiqueteraController.php
    │   └── QzCertificadoController.php
    └── 📊 Models
        ├── ImpresoraTiquetera.php
        └── QzCertificado.php
```

---

## 🔌 SERVICIOS COMPARTIDOS (Shared Services)

```
📁 src/services/
├── 🔧 Servicios Globales
│   ├── api.js                          (cliente axios configurado)
│   ├── datosMaestrosService.js         (datos globales: estados, provincias, etc.)
│   ├── impresoraTiqueteraService.js    (gestión de impresoras)
│   ├── qzCertificadoService.js         (certificados para QZ Tray)
│   └── publicApi.js                    (endpoint público sin autenticación)
│
└── 📁 Por Dominio
    ├── auth/
    │   └── (usa publicApi.js y services genéricos)
    ├── usuarios/
    │   ├── usuariosService.js
    │   ├── permisosService.js
    │   ├── moduloService.js
    │   └── configuracionService.js
    ├── torneos/
    │   ├── torneoDashboardService.js
    │   └── planTorneoService.js
    ├── instalaciones/
    │   └── eventosService.js
    ├── inventario/
    │   ├── articulosService.js
    │   └── proveedoresService.js
    └── comercial/
        ├── clientesService.js
        ├── ventasService.js
        └── cobrosService.js
```

---

## 🧩 COMPONENTES COMPARTIDOS (Shared Components)

```
📁 src/components/
├── ✅ Modales Genéricos
│   ├── ConfirmModal.vue
│   └── ToastNotification.vue
├── 🔍 Búsqueda y Filtrado
│   └── FuzzySearch.vue
├── 📊 Tablas
│   └── SortableTableHead.vue
├── 🎨 UI Genéricos
│   ├── Header.vue
│   └── CustomNumberInput.vue
│
└── 📁 Por Dominio
    ├── torneos/
    │   └── TorneoCalendar.vue
    └── venta/
        ├── VentaModal.vue
        ├── DetallesVentaModal.vue
        ├── QuickClientModal.vue
        └── QuickAssignTeamModal.vue
```

---

## 🎬 VISTAS PRINCIPALES (Layout)

```
📁 src/views/
├── 📏 Layout Compartido
│   ├── MenuView.vue            (menú principal)
│   └── SubmenuView.vue         (submenú por sección)
│
└── 📁 Por Dominio (9 vistas principales)
    ├── auth/ (2 vistas)
    ├── usuarios/ (4 vistas)
    ├── torneos/ (9 vistas)
    ├── instalaciones/ (3 vistas)
    ├── inventario/ (7 vistas)
    └── comercial/ (4 vistas)
```

---

## 🖥️ BACKEND - ESTRUCTURA CORE

```
📁 api/
├── 📄 index.php                (punto de entrada)
├── 📄 rutas.php                (definición de rutas y require_once)
│
├── 🔧 core/
│   ├── BaseController.php      (clase base para todos los controllers)
│   ├── Database.php            (conexión a BD con PDO)
│   ├── Env.php                 (variables de entorno)
│   └── JwtHandler.php          (manejo de tokens JWT)
│
├── 🎛️ controllers/ (35 controllers)
│   ├── auth/
│   ├── usuarios/
│   ├── torneos/
│   ├── instalaciones/
│   ├── inventario/
│   ├── comercial/
│   ├── catalogo/
│   └── hardware/
│
├── 📊 models/ (31+ models)
│   ├── auth/
│   ├── usuarios/
│   ├── torneos/
│   ├── instalaciones/
│   ├── inventario/
│   ├── comercial/
│   ├── catalogo/
│   └── hardware/
│
├── 📂 scripts/                 (scripts auxiliares)
├── 📂 uploads/                 (archivos subidos: escudos, certificados)
└── 📂 vendor/                  (dependencias Composer)
```
