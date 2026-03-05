# API REST - Il Calcio Camp - Documentación de Endpoints

Esta documentación describe todos los endpoints disponibles en la API RESTful para la gestión de Il Calcio Camp.

## Autenticación

Todos los endpoints (excepto `/login`, `/register` y `/roles`) requieren autenticación mediante JWT.

**Header requerido:**
```
Authorization: Bearer {token}
```

---

## Endpoints de Catálogo

### 1. Categorías de Artículo

**Base URL:** `/api/categorias-articulo`

#### GET - Listar todas las categorías
```http
GET /api/categorias-articulo
```

#### GET - Obtener categoría por ID
```http
GET /api/categorias-articulo/{id}
```

#### POST - Crear nueva categoría
```http
POST /api/categorias-articulo
Content-Type: application/json

{
  "descripcion": "Bebidas"
}
```

#### PUT - Actualizar categoría
```http
PUT /api/categorias-articulo
Content-Type: application/json

{
  "id": 1,
  "descripcion": "Bebidas Alcohólicas"
}
```

#### DELETE - Eliminar categoría
```http
DELETE /api/categorias-articulo
Content-Type: application/json

{
  "id": 1
}
```

---

### 2. Estados de Venta

**Base URL:** `/api/estados-venta`

#### GET - Listar todos los estados
```http
GET /api/estados-venta
```

#### POST - Crear nuevo estado
```http
POST /api/estados-venta
Content-Type: application/json

{
  "descripcion": "Pendiente"
}
```

#### PUT - Actualizar estado
```http
PUT /api/estados-venta
Content-Type: application/json

{
  "id": 1,
  "descripcion": "Completada"
}
```

---

### 3. Condiciones de IVA

**Base URL:** `/api/condiciones-iva`

#### GET - Listar todas las condiciones
```http
GET /api/condiciones-iva
```

#### POST - Crear nueva condición
```http
POST /api/condiciones-iva
Content-Type: application/json

{
  "id": 1,
  "descripcion_condicion": "Responsable Inscripto"
}
```

---

### 4. Provincias

**Base URL:** `/api/provincias`

#### GET - Listar todas las provincias
```http
GET /api/provincias
```

#### POST - Crear nueva provincia
```http
POST /api/provincias
Content-Type: application/json

{
  "id": 1,
  "provincia": "Córdoba"
}
```

---

### 5. Medios de Cobro

**Base URL:** `/api/medios-cobro`

#### GET - Listar todos los medios de cobro
```http
GET /api/medios-cobro
```

#### GET - Listar solo medios de cobro activos
```http
GET /api/medios-cobro?activos=1
```

#### POST - Crear nuevo medio de cobro
```http
POST /api/medios-cobro
Content-Type: application/json

{
  "id": 1,
  "descripcion": "Efectivo",
  "activo": true
}
```

#### PUT - Actualizar medio de cobro
```http
PUT /api/medios-cobro
Content-Type: application/json

{
  "id": 1,
  "descripcion": "Efectivo",
  "activo": false
}
```

---

### 6. Equipos

**Base URL:** `/api/equipos`

#### GET - Listar todos los equipos
```http
GET /api/equipos
```

#### GET - Listar solo equipos activos
```http
GET /api/equipos?activos=1
```

#### GET - Filtrar por disciplina
```http
GET /api/equipos?disciplina=Fútbol
```

#### POST - Crear nuevo equipo
```http
POST /api/equipos
Content-Type: application/json

{
  "id": 1,
  "nombre": "Juveniles 2010",
  "disciplina": "Fútbol",
  "activo": true
}
```

#### PUT - Actualizar equipo
```http
PUT /api/equipos
Content-Type: application/json

{
  "id": 1,
  "nombre": "Juveniles 2010 - A",
  "disciplina": "Fútbol",
  "activo": true
}
```

---

## Endpoints de Negocio

### 7. Artículos

**Base URL:** `/api/articulos`

#### GET - Listar todos los artículos
```http
GET /api/articulos
```

#### GET - Listar solo artículos activos
```http
GET /api/articulos?activos=1
```

#### GET - Filtrar por categoría
```http
GET /api/articulos?categoria=2
```

#### GET - Buscar por código de barras
```http
GET /api/articulos?cod_barra=7790001234567
```

#### GET - Obtener artículo por ID
```http
GET /api/articulos/{id}
```

#### POST - Crear nuevo artículo
```http
POST /api/articulos
Content-Type: application/json

{
  "nombre": "Coca Cola 500ml",
  "precio_actual": 500.00,
  "costo_actual": 300.00,
  "cod_barra": "7790001234567",
  "id_categoria_articulo": 1,
  "activo": true
}
```

#### PUT - Actualizar artículo
```http
PUT /api/articulos
Content-Type: application/json

{
  "id": 1,
  "nombre": "Coca Cola 500ml",
  "precio_actual": 550.00,
  "costo_actual": 320.00,
  "cod_barra": "7790001234567",
  "id_categoria_articulo": 1,
  "activo": true
}
```

---

### 8. Clientes

**Base URL:** `/api/clientes`

#### GET - Listar todos los clientes
```http
GET /api/clientes
```

#### GET - Filtrar por provincia
```http
GET /api/clientes?provincia=1
```

#### GET - Obtener cliente por ID
```http
GET /api/clientes/{id}
```

#### GET - Obtener equipos de un cliente
```http
GET /api/clientes/{id}?action=equipos
```

#### POST - Crear nuevo cliente
```http
POST /api/clientes
Content-Type: application/json

{
  "id": 123456789,
  "nombre_cliente": "Juan Pérez",
  "condicion_iva": "Consumidor Final",
  "id_condicion_iva_receptor": 5,
  "direccion": "Av. Colón 1234",
  "id_provinica": 1
}
```

#### PUT - Actualizar cliente
```http
PUT /api/clientes
Content-Type: application/json

{
  "id": 123456789,
  "nombre_cliente": "Juan Pérez González",
  "condicion_iva": "Responsable Inscripto",
  "id_condicion_iva_receptor": 1,
  "direccion": "Av. Colón 1234 - Piso 2",
  "id_provinica": 1
}
```

---

### 9. Ingresos de Artículos

**Base URL:** `/api/ingresos-articulo`

#### GET - Listar todos los ingresos
```http
GET /api/ingresos-articulo
```

#### GET - Filtrar por artículo
```http
GET /api/ingresos-articulo?articulo=5
```

#### GET - Filtrar por rango de fechas
```http
GET /api/ingresos-articulo?fecha_desde=2026-01-01&fecha_hasta=2026-03-31
```

#### POST - Crear nuevo ingreso
```http
POST /api/ingresos-articulo
Content-Type: application/json

{
  "fecha_ingreso": "2026-03-05",
  "vencimiento": "2026-09-05",
  "es_ajuste": false,
  "cantidad": 24,
  "id_articulo": 5,
  "precio_unitario": 300.00,
  "total": 7200.00,
  "es_perecedero": true
}
```

#### PUT - Actualizar ingreso
```http
PUT /api/ingresos-articulo
Content-Type: application/json

{
  "id": 1,
  "fecha_ingreso": "2026-03-05",
  "vencimiento": "2026-09-05",
  "es_ajuste": false,
  "cantidad": 25,
  "id_articulo": 5,
  "precio_unitario": 300.00,
  "total": 7500.00,
  "es_perecedero": true
}
```

---

### 10. Ventas

**Base URL:** `/api/ventas`

#### GET - Listar todas las ventas
```http
GET /api/ventas
```

#### GET - Filtrar por cliente
```http
GET /api/ventas?cliente=123456789
```

#### GET - Filtrar por estado
```http
GET /api/ventas?estado=1
```

#### GET - Filtrar por rango de fechas
```http
GET /api/ventas?fecha_desde=2026-03-01&fecha_hasta=2026-03-31
```

#### GET - Obtener venta por ID
```http
GET /api/ventas/{id}
```

#### GET - Obtener artículos de una venta
```http
GET /api/ventas/{id}?action=articulos
```

#### POST - Crear nueva venta
```http
POST /api/ventas
Content-Type: application/json

{
  "fecha": "2026-03-05",
  "id_equipo": 1,
  "descripcion_cliente": "Venta equipo juveniles",
  "id_estado_venta": 1,
  "simbolo": "$",
  "id_cliente": 123456789,
  "tipo_vta": "A"
}
```

#### PUT - Actualizar venta
```http
PUT /api/ventas
Content-Type: application/json

{
  "id": 1,
  "fecha": "2026-03-05",
  "id_equipo": 1,
  "descripcion_cliente": "Venta equipo juveniles - Actualizada",
  "id_estado_venta": 2,
  "simbolo": "$",
  "id_cliente": 123456789,
  "tipo_vta": "A"
}
```

---

### 11. Cobros

**Base URL:** `/api/cobros`

#### GET - Listar todos los cobros
```http
GET /api/cobros
```

#### GET - Filtrar por cliente
```http
GET /api/cobros?cliente=123456789
```

#### GET - Obtener cobro por ID
```http
GET /api/cobros/{id}
```

#### GET - Obtener ventas de un cobro
```http
GET /api/cobros/{id}?action=ventas
```

#### POST - Crear nuevo cobro
```http
POST /api/cobros
Content-Type: application/json

{
  "id": 1,
  "cliente_id": 123456789
}
```

---

## Endpoints de Entidades Relacionales

### 12. Artículos de Venta

**Base URL:** `/api/articulos-venta`

#### GET - Listar todos los artículos de ventas
```http
GET /api/articulos-venta
```

#### GET - Filtrar por venta
```http
GET /api/articulos-venta?venta=15
```

#### POST - Agregar artículo a una venta
```http
POST /api/articulos-venta
Content-Type: application/json

{
  "id_articulo": 5,
  "id_venta": 15,
  "cantidad": 10,
  "precio_unitario": 550.00,
  "total": 5500.00
}
```

#### PUT - Actualizar artículo de venta
```http
PUT /api/articulos-venta
Content-Type: application/json

{
  "id": 1,
  "id_articulo": 5,
  "id_venta": 15,
  "cantidad": 12,
  "precio_unitario": 550.00,
  "total": 6600.00
}
```

---

### 13. Ventas-Cobros

**Base URL:** `/api/ventas-cobro`

#### GET - Listar todas las relaciones
```http
GET /api/ventas-cobro
```

#### GET - Filtrar por venta
```http
GET /api/ventas-cobro?venta=15
```

#### GET - Filtrar por cobro
```http
GET /api/ventas-cobro?cobro=1
```

#### POST - Crear nueva relación venta-cobro
```http
POST /api/ventas-cobro
Content-Type: application/json

{
  "id_venta_cobro": 1,
  "id_venta": 15,
  "id_cobro": 1,
  "id_medio_pago": 1,
  "monto": 5500.00
}
```

---

### 14. Clientes-Equipos

**Base URL:** `/api/clientes-equipos`

#### GET - Listar todas las relaciones
```http
GET /api/clientes-equipos
```

#### GET - Filtrar por cliente
```http
GET /api/clientes-equipos?cliente=123456789
```

#### GET - Filtrar por equipo
```http
GET /api/clientes-equipos?equipo=1
```

#### POST - Asignar cliente a equipo
```http
POST /api/clientes-equipos
Content-Type: application/json

{
  "id_cliente_equipo": 1,
  "id_cliente": 123456789,
  "id_equipo": 1
}
```

#### DELETE - Eliminar relación cliente-equipo
```http
DELETE /api/clientes-equipos
Content-Type: application/json

{
  "id_cliente_equipo": 1
}
```

---

### 15. Artículos Venta - Ingresos

**Base URL:** `/api/articulos-venta-ingresos`

#### GET - Listar todas las relaciones
```http
GET /api/articulos-venta-ingresos
```

#### GET - Filtrar por artículo de venta
```http
GET /api/articulos-venta-ingresos?articulo_venta=1
```

#### POST - Crear nueva relación
```http
POST /api/articulos-venta-ingresos
Content-Type: application/json

{
  "id_articulo_venta_ingreso_articulo": "AVI-001",
  "articulo_venta_id_articulo_venta": 1,
  "ingreso_articulo_id": 5,
  "cantidad": "10"
}
```

---

## Códigos de Respuesta HTTP

- **200 OK**: Solicitud exitosa
- **201 Created**: Recurso creado exitosamente
- **400 Bad Request**: Datos incompletos o inválidos
- **401 Unauthorized**: No autorizado (token inválido o ausente)
- **404 Not Found**: Recurso no encontrado
- **405 Method Not Allowed**: Método HTTP no permitido
- **500 Internal Server Error**: Error interno del servidor

---

## Ejemplos de Respuestas

### Respuesta exitosa (200):
```json
{
  "id": 1,
  "nombre": "Coca Cola 500ml",
  "precio_actual": 550.00,
  "costo_actual": 320.00,
  "cod_barra": "7790001234567",
  "id_categoria_articulo": 1,
  "activo": 1,
  "categoria_descripcion": "Bebidas"
}
```

### Respuesta de error (400):
```json
{
  "message": "Datos incompletos. Se requieren nombre, precio_actual y id_articulo."
}
```

### Respuesta de error (401):
```json
{
  "message": "No autorizado: Token inválido o expirado."
}
```

### Respuesta de error (404):
```json
{
  "message": "Artículo no encontrado."
}
```

---

## Notas Importantes

1. **Relaciones FK**: Todas las entidades respetan las relaciones de clave foránea definidas en la base de datos.

2. **Filtros**: Los endpoints soportan filtros mediante query parameters según se especifica en cada caso.

3. **Validaciones**: Todos los endpoints incluyen validaciones básicas de datos requeridos.

4. **Manejo de errores**: Utiliza try-catch y retorna mensajes descriptivos en caso de error.

5. **Seguridad**: Todos los endpoints (excepto login/register) requieren autenticación JWT.

6. **Formatos de fecha**: Todas las fechas deben enviarse en formato `YYYY-MM-DD`.

7. **Decimales**: Los valores numéricos decimales deben enviarse con punto decimal (ej: 550.00).

---

## Estructura de la Base de Datos

Para más detalles sobre la estructura de la base de datos, consulte el archivo `documentacion/estructura-BD.md`.
