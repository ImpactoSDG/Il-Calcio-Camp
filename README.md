# 🚀 Proyecto Base PHP + Vue 3

Plantilla base para futuros desarrollos.

Incluye:

* Frontend con **Vue 3 + Vite**
* Backend en **PHP (estructura MVC)**
* Autenticación
* Base de datos inicial `impactos_proyecto_base` con tablas:

  * `usuario`
  * `rol`
  * `modulo`
  * `usuario_modulo`

---

## ⚙️ Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/tuusuario/proyecto-base.git
cd proyecto-base
```

### 2. Instalar dependencias

```bash
npm install
npm run dev
```

### 3. Base de datos

* Clonar la base de datos `impactos_proyecto_base` en una nueva base.
* Completar el archivo `api/core/Database.php` con las credenciales correspondientes.

---

## 🖥️ Gestión de Módulos (Menús y Submenús)

El sistema genera el menú de forma dinámica basado en la tabla `modulos`.

La columna `ruta` define el comportamiento del acceso:

### 1. Enlaces directos (internos o externos)

* **Rutas internas**: especificar el path (ej: `/gestion`, `/configuraciones`).
* **Rutas externas**: especificar la URL completa (ej: `https://google.com`).

  * Se abrirán en una nueva pestaña.

### 2. Módulos con submenús (contenedores)

* Si un módulo actúa como contenedor de otros, su `ruta` debe seguir el patrón: `/submenu/{id}` donde `{id}` es el ID del propio módulo en la tabla.

* Los sub-módulos asociados deben tener el campo `id_padre` apuntando al ID del módulo contenedor.

* El sistema filtrará y mostrará automáticamente los hijos dentro de la vista `SubmenuView`.

### 3. Orden y categorización

* `orden_visualizacion`: define la prioridad de aparición (orden ascendente).
* `categoria`: agrupa los módulos en el menú principal (ej: General, Sistema, Operaciones).

---

## ⚙️ Configuración del proyecto

### 4. Archivo `.env`

* Cambiar `proyecto-base` por el nombre del nuevo proyecto.

### 5. Archivo `src/assets/global.css`

* Configurar los colores base del nuevo proyecto.

---

## 🎨 Personalización

* Cambiar el nombre del proyecto en `package.json`.
* Actualizar favicon y logo en `src/assets`.
* Ajustar título y descripción en `index.html`.

---

## 🚀 Producción

**Recordatorio:**

Al subir a producción, en el archivo `api/.htaccess`:

* Comentar o eliminar: `RewriteBase /proyecto-base/api/`
* Y dejar: `RewriteBase /api/`
