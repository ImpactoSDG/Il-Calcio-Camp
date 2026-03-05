# Colección Bruno - Il Calcio Camp API

Esta carpeta contiene la colección completa de peticiones HTTP para probar la API de Il Calcio Camp usando [Bruno](https://www.usebruno.com/).

## 📁 Estructura de Carpetas

```
bruno/
├── environments/
│   └── Local.bru              # Variables de entorno
├── Auth/                      # Endpoints de autenticación
├── Catalogo/                  # Entidades de catálogo
│   ├── Categorias Articulo/
│   ├── Estados Venta/
│   └── Equipos/
├── Negocio/                   # Entidades de negocio
│   ├── Articulos/
│   ├── Clientes/
│   ├── Ingresos Articulo/
│   └── Ventas/
└── Relacionales/              # Entidades relacionales
    ├── Articulos Venta/
    └── Clientes Equipos/
```

## 🚀 Configuración Inicial

### 1. Configurar Variables de Entorno

Edita el archivo `environments/Local.bru` y actualiza las variables:

```
vars {
  base_url: http://localhost/Il-Calcio-Camp/api
  token: your_jwt_token_here
}
```

### 2. Obtener Token de Autenticación

1. Ejecuta la petición `Auth/Login.bru`
2. El token se guardará automáticamente en la variable `{{token}}`
3. Todas las demás peticiones usarán este token automáticamente

## 📝 Uso de las Peticiones

### Orden Recomendado de Prueba

1. **Autenticación**
   - Primero ejecuta `Login` para obtener el token
   - Opcionalmente prueba `Register` para crear usuarios

2. **Catálogo** (crear datos base)
   - Categorías de Artículo
   - Estados de Venta
   - Equipos

3. **Negocio** (crear datos principales)
   - Artículos
   - Clientes
   - Ingresos de Artículos
   - Ventas

4. **Relacionales** (crear relaciones)
   - Artículos Venta
   - Clientes Equipos

### Validaciones Automáticas

Cada petición incluye scripts de validación que:
- ✅ Verifican el código de estado HTTP (200, 201, etc.)
- 📊 Muestran información relevante en la consola
- 🔍 Ayudan a identificar errores rápidamente

### Ejemplo de Script de Validación

```javascript
script:post-response {
  if (res.status === 201) {
    console.log("Recurso creado con ID:", res.body.id);
  } else {
    console.log("Error:", res.status, res.body);
  }
}
```

## 🎯 Datos de Ejemplo

Los archivos .bru incluyen datos realistas relacionados con un club deportivo:

- **Artículos**: Pelota Nike Strike Team N°5, equipamiento deportivo
- **Equipos**: Juveniles Sub-16, categorías de fútbol
- **Clientes**: DNI argentinos, direcciones de Córdoba
- **Precios**: En pesos argentinos (formato: 15000.00)

## 🔧 Modificar Datos de Prueba

Puedes editar los datos en el cuerpo JSON de cada petición:

```json
{
  "nombre": "Tu Artículo Aquí",
  "precio_actual": 10000.00,
  "activo": true
}
```

## 📊 Endpoints Disponibles

### Autenticación (2)
- Login
- Register

### Catálogo (18)
- Categorías Artículo (5 peticiones)
- Estados Venta (5 peticiones)
- Equipos (6 peticiones)

### Negocio (24)
- Artículos (6 peticiones)
- Clientes (6 peticiones)
- Ingresos Artículo (5 peticiones)
- Ventas (6 peticiones)

### Relacionales (9)
- Artículos Venta (5 peticiones)
- Clientes Equipos (4 peticiones)

**Total**: 53 peticiones HTTP

## 💡 Tips y Recomendaciones

1. **Ejecuta Login primero** - Necesitas el token para todas las demás peticiones
2. **Sigue el orden lógico** - Crea categorías antes de artículos, artículos antes de ventas
3. **Revisa la consola** - Los scripts muestran información útil después de cada petición
4. **Actualiza IDs** - Cambia los IDs en las peticiones según los datos creados
5. **Usa filtros** - Aprovecha los query parameters para filtrar resultados

## 🐛 Solución de Problemas

### Error 401 - Unauthorized
- Verifica que el token sea válido
- Ejecuta Login nuevamente si el token expiró

### Error 404 - Not Found
- Verifica que el recurso con ese ID exista
- Ejecuta GET All para ver los IDs disponibles

### Error 400 - Bad Request
- Revisa que todos los campos requeridos estén presentes
- Verifica el formato de los datos (fechas, números, etc.)

## 📚 Documentación Adicional

Para más detalles sobre los endpoints, ver:
- `documentacion/API_ENDPOINTS.md` - Documentación completa de la API
- `documentacion/estructura-BD.md` - Esquema de la base de datos

---

**Nota**: Asegúrate de tener el servidor XAMPP corriendo y la base de datos configurada antes de ejecutar las peticiones.
