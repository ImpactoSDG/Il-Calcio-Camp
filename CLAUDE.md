# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Il-Calcio-Camp** is a sports management and sales platform. Frontend: Vue 3 + Vite + Pinia + Bootstrap 5. Backend: PHP (PDO/MySQL, JWT, no ORM). Key features: tournament management, venue scheduling, inventory with FIFO cost tracking, sales + AFIP billing, thermal printing via QZ Tray.

## Commands

```bash
npm install       # Install frontend dependencies
npm run dev       # Start Vite dev server (localhost:5173)
npm run build     # Build for production → dist/
npm run preview   # Preview production build
```

Backend is PHP/Apache — no build step. API changes take effect immediately.

## Configuration

- **Frontend:** `VITE_API_URL` — dev default: `http://localhost/Il-Calcio-Camp/api`
- **Backend:** `api/.env` — set `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `JWT_SECRET`, `APP_DEBUG`
- **Database schema:** See `estructura-bd.md`
- **Billing flow:** See `FLUJO_FACTURACION.md`
- **Folder overview:** See `documentacion/ESTRUCTURA_CARPETAS.md`

## Frontend Architecture (`src/`)

**Layered structure:** views → services → `api.js` (Axios) → PHP API

```
src/
├── main.js                  # App entry (registers Pinia, Router, plugins)
├── router/index.js          # All routes; auth guards + per-route idModulo permission checks
├── stores/                  # Pinia stores (userStore persisted to sessionStorage)
├── services/                # Domain-specific API clients (wrap the shared Axios instance)
│   └── api.js               # Axios instance; JWT injected via request interceptor
├── views/                   # Page-level components organized by domain
├── components/              # Reusable modals, tables, forms
├── composables/             # Shared logic (e.g., usePrinterConfig for QZ Tray)
└── utils/                   # Formatters, constants
```

**Auth flow:** Login → JWT returned → stored in `userStore` (sessionStorage) → router guard checks `userStore.isLoggedIn` → Axios interceptor injects `Authorization: Bearer {token}` on every request. Token expires after 24 h; no refresh mechanism — users must re-login.

**Dynamic permission system:** The `modulo` DB table defines hierarchical menu items (parent/child via `id_padre`). Each route has `meta.idModulo`. On navigation, the router guard cross-checks `idModulo` against the logged-in user's assigned modules (`userStore.user.modulos`). Menu items are hidden/shown at runtime based on `usuario_modulo` table.

## Backend Architecture (`api/`)

**Pattern:** Single PDO connection per request, injected into every controller and model.

```
api/
├── index.php          # CORS headers, URI parsing, dispatches to rutas.php
├── rutas.php          # Creates the single PDO connection; requires all controllers; routes requests
├── core/
│   ├── Database.php       # PDO factory (utf8mb4)
│   ├── JwtHandler.php     # HS256 sign/verify, 24 h expiry
│   ├── BaseController.php # respond() and handleError() — all JSON responses go through here
│   └── Env.php            # .env loader
├── controllers/       # Validate input, check auth, delegate to models
└── models/            # Raw SQL with PDO prepared statements; no ORM
```

**Single shared connection:** `rutas.php` instantiates `Database::connect()` once, then passes `$db` (PDO) into every controller constructor. Models receive it via `__construct(PDO $db)`. This prevents connection pool exhaustion.

**Error handling:** All controllers extend `BaseController`. `handleError($e, $message)` catches exceptions and returns `{"message": "...", "error": "..."}`. When `APP_DEBUG=false`, technical details are hidden.

## Domain Data Model

| Domain | Key Tables |
|--------|-----------|
| Users & Permissions | `usuario`, `rol`, `modulo`, `usuario_modulo` |
| Tournaments | `torneo`, `equipo`, `jugador`, `disciplina`, `evento`, `cruce_torneo`, `equipo_torneo` |
| Venues | `cancha`, `evento`, `estado_evento` |
| Inventory | `articulo`, `categoria_articulo`, `ingreso_articulo`, `articulo_venta_ingreso_articulo`, `proveedor`, `pedido_proveedor` |
| Sales & Billing | `cliente`, `venta`, `articulo_venta`, `cobro`, `venta_cobro`, `factura`, `tipo_comprobante` |

## Critical Workflows

### Sales + AFIP Billing (see `FLUJO_FACTURACION.md` for full detail)

1. Frontend creates `venta` → backend runs a **single transaction**: inserts `venta` + `articulo_venta` rows, decrements stock (FIFO via `articulo_venta_ingreso_articulo`), records payment. On failure → ROLLBACK.
2. Frontend calls `/facturar/{idVenta}` → backend **closes the MySQL connection**, then calls an external Node.js AFIP service via `shell_exec()` with a 15 s timeout.
3. Backend reconnects to MySQL, saves the `factura` record, updates `venta.estado_factura` ('facturada' | 'error' | 'rechazada').
4. Frontend prints ticket + invoice via QZ Tray websocket.

Facturación is **idempotent**: if re-called with an existing `idVenta`, it checks for an existing CAE and returns it rather than re-billing.

### FIFO Stock Tracking

`articulo_venta_ingreso_articulo` links each sold unit to its purchase lot (`ingreso_articulo`). Stock is consumed from the oldest lot first. This allows cost-of-goods tracking but can create negative stock if demand exceeds supply.

### Printer Integration (QZ Tray)

`composables/usePrinterConfig.js` manages the QZ Tray WebSocket connection. Certificates are stored in the `qz_certificados` DB table. QZ Tray must be installed locally on each user's machine.

## Adding a New Feature

1. Add table(s) to the DB and document in `estructura-bd.md`.
2. Create `api/models/[domain]/MyModel.php` with `__construct(PDO $db)`.
3. Create `api/controllers/[domain]/MyController.php` extending `BaseController`.
4. Register in `api/rutas.php`: require the controller, instantiate with `$db`, add route case.
5. Create `src/services/[domain]/myService.js` wrapping the shared Axios instance.
6. Create `.vue` view + add route entry in `src/router/index.js` with `meta: { requiresAuth: true, idModulo: X }`.
7. Insert a row into `modulo` table and assign to relevant users via `usuario_modulo`.

## External Services

- **AFIP Node.js service:** Called via `shell_exec()` from `FacturaController`; located at `/home/impactos/nodevenv/franconovara/afip-service/`. 15 s timeout hardcoded in `FacturaController::shellExecWithTimeout()`.
- **QZ Tray:** Client-side WebSocket printer driver; must be installed on each operator's machine.
- **MySQL:** Local (XAMPP) or remote; credentials in `api/.env`.


# Principios de desarrollo

## Legibilidad
- Priorizar código legible sobre código con abstracciones innecesarias.
- Escribir código al nivel de un desarrollador junior/intermedio.
- Los nombres deben ser descriptivos y en lo posible en español.

## Mantenibilidad
- Evitar duplicación de código.
- Mantener funciones cortas y con una sola responsabilidad.
- No introducir patrones complejos sin una necesidad real.
- Preferir claridad antes que optimización prematura.

## Refactorización
- Cuando modifiques código existente, explicar qué cambió y por qué.
- Si detectas deuda técnica, señalarla antes de implementar.
- No aumentar la complejidad accidentalmente.

## Buenas prácticas
- Seguir principios SOLID cuando aporten valor.
- Evitar código espagueti.
- Evitar funciones gigantes.
- Evitar anidaciones profundas.
- No usar variables de una sola letra salvo en iteradores simples.

## Antes de escribir código
Preguntate:
1. ¿Un junior podría entender esto en 6 meses?
2. ¿La solución más simple resolvería el problema?
3. ¿Estoy introduciendo complejidad innecesaria?