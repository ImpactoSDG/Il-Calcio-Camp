# 🚀 Proyecto Base PHP + Vue 3

Plantilla base para futuros desarrollos.  
Incluye:
- Frontend con **Vue 3 + Vite**
- Backend en **PHP (estructura MVC)**
- Autenticación
- Base de datos inicial `impactos_proyecto_base` con `usuario`, `rol`, `modulo` y `usuario_modulo`

---

## ⚙️ Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/tuusuario/proyecto-base.git
cd proyecto-base

2. Instalar dependencias
npm install
npm run dev

3. Base de datos
clonar impactos_proyecto_base en nueva bd
completar el archivo api/core/Database.php con las credenciales

4. Archivo .env
Cambiar proyecto-base por el nombre del nuevo proyecto.

5. Archivo src/assets/global.css
Configurá los colores base del nuevo proyecto.

Personalización:
Cambiar el nombre del proyecto en package.json
Actualizar favicon y logo en src/assets
Ajustar título y descripción en index.html

Recordatorio: Al subir a produccion, en api/.htaccess se comenta o elimina "RewriteBase /proyecto-base/api/" y se deja "RewriteBase /api/"

