import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import datosMaestrosService from '@/services/datosMaestrosService'

export const useNotificacionesTorneoStore = defineStore('notificacionesTorneo', () => {
  const torneos = ref([])
  const loading = ref(false)

  const totalSolicitudesPendientes = computed(() => {
    return torneos.value.reduce((sum, t) => {
      return sum + (Number(t.solicitudes_pendientes) || 0)
    }, 0)
  })

  const cargarTorneos = async () => {
    if (loading.value) return
    loading.value = true
    try {
      torneos.value = await datosMaestrosService.getTorneos()
    } catch (error) {
      console.error('Error cargando torneos para notificaciones:', error)
    } finally {
      loading.value = false
    }
  }

  return {
    torneos,
    loading,
    totalSolicitudesPendientes,
    cargarTorneos,
  }
})
