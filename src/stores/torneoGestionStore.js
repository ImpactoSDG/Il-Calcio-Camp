import { defineStore } from 'pinia'
import { ref } from 'vue'

// Recuerda qué torneo está seleccionado en el módulo "Gestión de torneos"
// mientras el usuario navega entre sus vistas (inscripciones, asignaciones,
// calendario), para que no haya que volver a elegirlo en cada una.
// Se limpia automáticamente al salir del módulo (ver router/index.js).
export const useTorneoGestionStore = defineStore('torneoGestion', () => {
  const idTorneoSeleccionado = ref(null)

  function seleccionar(id) {
    idTorneoSeleccionado.value = id ? Number(id) : null
  }

  function limpiar() {
    idTorneoSeleccionado.value = null
  }

  return { idTorneoSeleccionado, seleccionar, limpiar }
})
