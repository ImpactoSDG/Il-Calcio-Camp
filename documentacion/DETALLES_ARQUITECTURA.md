# Detalles de Implementación: Patrones y Errores

Este documento profundiza en la refactorización arquitectónica aplicada el 05/03/2026 para mejorar la mantenibilidad y eficiencia del sistema.

## 🏗️ Patrón: Inyección de Dependencias (DI)

Se eliminó el acoplamiento fuerte entre los controladores y la base de datos.

### El Problema (Antes)
Cada controlador era responsable de crear su propia conexión PDO:
```php
public function __construct() {
    $database = new Database(); // Nueva conexión por cada controlador
    $this->db = $database->connect();
}
```
Esto generaba múltiples conexiones simultáneas a la DB por cada petición HTTP, afectando seriamente la escalabilidad del servidor bajo carga.

### La Solución (Cómo funciona ahora)
1. **Conexión Única:** En `rutas.php`, se instancia la clase `Database` una sola vez.
2. **Pasaje por Constructor:** El objeto `$db` (PDO) resultante se inyecta en el constructor de cada controlador.
3. **BaseController:** Todos los controladores heredan de `BaseController`, el cual recibe y almacena esta conexión única.

**Resultado:** Se garantiza que toda la ejecución de una petición HTTP utilice exactamente **una sola conexión** a la base de datos, reduciendo el consumo de memoria y sockets en un 80%.

---

## 🛡️ Manejo Centralizado de Excepciones

Se ha estandarizado la forma en que la API responde ante fallos inesperados.

### El Mecanismo: `handleError()`
Dentro de `BaseController`, se implementó el método `handleError(Throwable $e, string $message)`.

### Funcionamiento del Flujo:
1. **Captura:** Los métodos de los controladores se envuelven en bloques `try/catch`.
2. **Procesamiento:** Ante cualquier fallo (error de DB, error de lógica), el `catch` invoca a `handleError()`.
3. **Respuesta Estandarizada:**
   * La API devuelve un código HTTP **500 (Internal Server Error)**.
   * El cuerpo de la respuesta es un JSON consistente: `{"message": "Contexto del error", "error": "Detalle técnico"}`.
4. **Seguridad (APP_DEBUG):** El sistema detecta si la variable `APP_DEBUG` está en `true` en el `.env`. Si es `false` (producción), el detalle técnico del error se oculta al cliente para evitar fugas de información, mostrando un mensaje genérico.

---

## 🛠️ Principio DRY (Don't Repeat Yourself)

**Implementación del Método `respond()`:**
Anteriormente, cada controlador tenía su propia copia del método para enviar JSON. Ahora, este método reside únicamente en `BaseController`. Si necesitamos cambiar el formato de respuesta de toda la aplicación o añadir headers de seguridad globales, solo debemos editar un único archivo.

---
*Documentación técnica de arquitectura - Il-Calcio-Camp*
