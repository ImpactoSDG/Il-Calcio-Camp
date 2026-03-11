# Convenciones para Proyectos

Este documento describe las convenciones que deben seguirse en todos los proyectos para garantizar consistencia y calidad en el desarrollo.

---

## 1. Formato de Números
- **Separador de miles:** Todos los números deben incluir separadores de miles para mejorar la legibilidad.
- **Decimales:** Los números deben aceptar tanto punto (`.`) como coma (`,`) como separadores decimales.
- **Montos:** Los montos monetarios deben mostrarse sin decimales si estos son `,00` (es decir, no se muestran los decimales si son ceros). Si los montos tienen decimales distintos de cero, deben mostrarse.

---

## 2. Tablas
- Las columnas de las tablas deben ser ordenables en orden ascendente o descendente al hacer doble clic en el encabezado de la columna.

---

## 3. Buscadores
- Todos los buscadores deben implementar **búsqueda difusa** para mejorar la experiencia del usuario.
- La librería recomendada para implementar búsqueda difusa es **Fuse.js**.

---

## 4. Botones de Volver
- Todos los botones de "Volver" deben:
  - Estar ubicados en la parte superior izquierda de la pantalla.
  - Realizar la acción de retroceder una página en el historial del navegador.

---

## 5. Fechas y Horarios
- Todas las fechas y horarios deben estar configurados en el huso horario de **Argentina (GMT-3)**.
- Verificar que las fechas y horas se muestren correctamente en este formato.

---

## 6. Campos Obligatorios
- Los campos obligatorios deben estar claramente indicados con un asterisco (`*`) de color rojo en el `label` correspondiente.

---

## 7. Botones en Modales
- En los modales, los botones deben seguir esta convención:
  - **Acción secundaria:** Ubicada a la izquierda, con un estilo gris.
  - **Acción principal:** Ubicada a la derecha, con un color destacado.

---

## 8. Base de Datos
- Todas las tablas de datos maestros deben incluir los siguientes campos:
  - **Fecha de creación:** Para registrar cuándo se creó el registro.
  - **Autor:** Para identificar quién creó el registro.


----------------------------------------------------------

cualquier documentacion que consideres importante para vos u otros desarrolladores ponerla en la carpeta de documentacion.

avisar (en un commit o por mensaje) si se instalan librerias o dependencias para que todos los desarrolladores esten en sintonia y no haya conflictos en el sistema por falta de dependencias

seguir los estilos que tienen las otras vistas y el global.css

cada entidad debe tener su modelo, controlador, servicio, etc. evitar agrupar entidades en un mismo modelo por ejemplo.
----------------------------------------------------------

CAPAS DEL FRONT:
assets/: Contiene recursos estáticos como hojas de estilo (global.css) que se aplican globalmente en la aplicación.

components/: Incluye componentes reutilizables. 

composables/: Contiene funciones reutilizables.

router/: Define las rutas de la aplicación en index.js, gestionando la navegación entre vistas.

services/: Implementa la lógica para interactuar con la API.

stores/: Contiene la lógica de estado global utilizando herramientas como Pinia o Vuex. 

utils/: Incluye utilidades y funciones auxiliares, como formatters.js para formatear datos.

views/: Contiene las vistas principales de la aplicación, que representan páginas completas.
Revisar vistas grandes: Si algún componente/vista Vue es demasiado extenso, divídelo en subcomponentes más pequeños y reutilizables.
Implementar lazy loading para vistas y componentes grandes.

---------------------------------------------------------
CAPAS DEL BACK:
MODELO:
Responsabilidad: Representar la estructura de los datos y manejar la lógica relacionada con la base de datos.
Qué incluir:
Definición de las propiedades del modelo y Métodos relacionados con la base de datos (consultas, relaciones, validaciones).
revisar que las consultas sean eficientes, poner filtros de ser necesario

CONTROLADOR:
Responsabilidad: Gestionar las solicitudes HTTP y coordinar la lógica entre los modelos y las vistas (o respuestas).
Qué incluir:
Métodos para manejar las rutas (endpoints) específicas.
Validación de datos de entrada.
Llamadas a los modelos para realizar operaciones.

RUTAS:
Responsabilidad: Definir los endpoints de la API y asociarlos con los métodos de los controladores.
Qué incluir:
Definición de las rutas HTTP (GET, POST, PUT, DELETE).
Asociación de cada ruta con un método del controlador.

tener una carpeta de SERVICIOS para tareas específicas como el envío de correos o la generación/impresión de PDFs es una buena práctica. Esto ayuda a mantener el código modular, organizado y reutilizable.

----------------------------------------------------------
