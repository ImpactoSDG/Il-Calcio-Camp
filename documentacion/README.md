# Arquitectura Técnica y Seguridad - Auditoría 05/03/2026

Este documento detalla el **cómo** y el **porqué** de la reestructuración profunda realizada en el backend PHP de **Il-Calcio-Camp**.

## 🛡️ Saneamiento de Seguridad y Arquitectura

### 1. Gestión Dinámica de Configuración (Environment Injection)
**Implementación:** Se ha desacoplado la configuración del entorno del código fuente.
*   **El Mecanismo:** Mediante la nueva clase `Env.php` en `/api/core/`, el sistema escanea el archivo `.env` al inicio de cada petición en el `index.php`. 
*   **Funcionamiento Interno:** Utiliza `file()` para leer el archivo línea a línea, limpia espacios y comentarios, y usa `putenv()` junto con la superglobal `$_ENV` para inyectar estos valores en la memoria del proceso PHP.
*   **Seguridad:** Esto evita que credenciales sensibles (DB_PASS, JWT_SECRET) queden expuestas en el historial de Git o en servidores de despliegue.

### 2. Autenticación Stateless (JWT)
**Implementación:** Se reemplazó la falta de control por un sistema de tokens portadores (Bearer Tokens).
*   **Generador (`JwtHandler.php`):** Implementa un estándar JWT ligero. Construye un JSON con el `header` (algoritmo HS256) y el `payload` (datos del usuario + `exp` de 24h). Ambos se codifican en `base64Url`. 
*   **Firma Digital:** Se genera un `hash_hmac` usando la `JWT_SECRET` del `.env`. Si un solo bit del token es alterado, la firma no coincidirá y el servidor rechazará la petición.
*   **Middleware de Intercepción:** En `rutas.php`, la función `verifyAuth()` actúa como un guardia. Antes de que el controlador procese cualquier dato sensible (CRUD de usuarios, etc.), extrae el token del header `Authorization`, lo decodifica y verifica su validez y expiración.

### 3. Persistencia de Datos (Refactor PDO)
**Implementación:** Se centralizó la lógica de conexión en `Database.php`.
*   **El Proceso:** Ahora la clase `Database` no tiene valores fijos (hardcoded). En su constructor, solicita las variables de entorno inyectadas previamente por `Env.php`. 
*   **Seguridad SQL:** Se mantiene el uso de `PDO` con `prepare()` y `bindValue()` para garantizar que todas las entradas de usuario sean tratadas como datos y no como código ejecutable, mitigando ataques de Inyección SQL.

## 🚀 Guía de Mantenimiento

### Variables de Entorno Requeridas (`api/.env`)
```ini
DB_HOST=127.0.0.1
DB_NAME=nombre_db
DB_USER=usuario_db
DB_PASS=password_segura
JWT_SECRET=generar_uuid_o_hash_largo
```

### Flujo de Peticiones Protegidas
1.  **POST `/login`:** El cliente envía credenciales. La API devuelve un `token`.
2.  **Solicitud Protegida:** El cliente añade el header: `Authorization: Bearer <token>`.
3.  **Validación:** `rutas.php` invoca `verifyAuth()`. Si el token expira o la firma es inválida, se devuelve un `401 Unauthorized` sin procesar la lógica del negocio.


He corregido el flujo completo en el frontend para que todo vuelva a funcionar automáticamente:

Guardado del Token: He actualizado el userStore.js para que guarde el token junto con los datos del usuario cuando haces login.
Captura en el Login: Modifiqué el LoginView.vue para que extraiga el token de la respuesta de la API y lo pase al store.
Inyección Automática (Interceptor): Configuré un interceptor en api.js. Esto es lo más importante: ahora, cada vez que hagas una petición a la API, Axios buscará el token en el store y lo añadirá automáticamente en la cabecera Authorization: Bearer <token>
---
*Senior Backend Architect & Security Consultant*
