<template>
  <div class="container-fluid p-4 bg-white min-vh-100 animate-fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom flex-wrap gap-2">
      <div class="d-flex align-items-center">
        <button @click="$router.back()" class="btn-back-arrow me-2" title="Volver">
          <i class="bi bi-arrow-left"></i>
        </button>
        <h1 class="h5 fw-bold mb-0 text-secondary">CALENDARIO DE TORNEOS</h1>
      </div>
      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-outline-primary" @click="$router.push('/eventos')">
          <i class="bi bi-pencil-square me-1"></i>Editar eventos
        </button>
        <button class="btn btn-outline-secondary" @click="cargarTorneos" :disabled="loadingTorneos || loadingDetalle">
          <span v-if="loadingTorneos" class="spinner-border spinner-border-sm me-2"></span>
          Recargar
        </button>
      </div>
    </div>

    <div v-if="loadingDetalle" class="card shadow-sm border-0 rounded-lg">
      <div class="card-body py-4 text-center text-muted">
        <span class="spinner-border spinner-border-sm me-2"></span>
        Cargando calendario...
      </div>
    </div>

    <div v-else-if="errorMensaje" class="alert alert-danger mb-0">
      {{ errorMensaje }}
    </div>

    <div v-else-if="detalle" class="card shadow-sm border-0 rounded-lg">
      <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div>
            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
              <h2 class="h6 fw-bold mb-0">{{ tituloCalendario }}</h2>
              <select
                class="form-select form-select-sm filtro-torneo"
                v-model.number="idTorneoSeleccionado"
                :disabled="loadingTorneos || !torneos.length"
                @change="onChangeTorneo"
                aria-label="Filtrar torneo"
              >
                <option :value="0">Todos</option>
                <option v-for="torneo in torneos" :key="torneo.id" :value="Number(torneo.id)">
                  {{ torneo.nombre }}
                </option>
              </select>
            </div>
            <div class="small text-muted">
              {{ subtituloCalendario }}
            </div>
          </div>

          <button class="btn btn-outline-primary" @click="onChangeTorneo">
            Actualizar calendario
          </button>
        </div>

        <TorneoCalendar
          :eventos="eventosCalendario"
          :torneo-nombre="torneoNombreCalendario"
          :show-title="false"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import datosMaestrosService from '@/services/datosMaestrosService'
import planTorneoService from '@/services/planTorneoService'
import TorneoCalendar from '@/components/torneos/TorneoCalendar.vue'

const torneos = ref([])
const idTorneoSeleccionado = ref(0)
const detalle = ref(null)
const loadingTorneos = ref(false)
const loadingDetalle = ref(false)
const errorMensaje = ref('')

const torneoSeleccionado = computed(() =>
  torneos.value.find((t) => Number(t.id) === Number(idTorneoSeleccionado.value)) || null
)

const tituloCalendario = computed(() =>
  Number(idTorneoSeleccionado.value) > 0
    ? (torneoSeleccionado.value?.nombre || detalle.value?.torneo?.nombre || 'Calendario del torneo')
    : 'Calendario general de torneos'
)

const subtituloCalendario = computed(() => {
  if (Number(idTorneoSeleccionado.value) > 0) {
    return detalle.value?.torneo?.disciplina_nombre || 'Sin disciplina'
  }
  return `${torneos.value.length} torneos en vista consolidada`
})

const torneoNombreCalendario = computed(() =>
  Number(idTorneoSeleccionado.value) > 0 ? (detalle.value?.torneo?.nombre || '') : ''
)

const eventosCalendario = computed(() =>
  (detalle.value?.eventos_partido || [])
    .map((ev) => ({ ...ev, id: Number(ev.id) }))
    .filter((ev) => {
      if (!ev.fecha_hora_inicio) return false
      const dt = new Date(String(ev.fecha_hora_inicio).replace(' ', 'T'))
      return !Number.isNaN(dt.getTime())
    })
)

const cargarDetalle = async (idTorneo) => {
  const id = Number(idTorneo || 0)
  loadingDetalle.value = true
  errorMensaje.value = ''

  try {
    if (id > 0) {
      detalle.value = await planTorneoService.getDetalleGestion(id)
      return
    }

    const detalles = await Promise.all(
      torneos.value.map((torneo) => planTorneoService.getDetalleGestion(Number(torneo.id)))
    )

    const eventosPartido = detalles.flatMap((d) => {
      const torneoNombre = String(d?.torneo?.nombre || '').trim()
      return (d?.eventos_partido || []).map((ev) => ({
        ...ev,
        torneo_nombre: torneoNombre,
      }))
    })

    detalle.value = {
      torneo: {
        nombre: 'Todos los torneos',
        disciplina_nombre: 'Vista consolidada',
      },
      eventos_partido: eventosPartido,
    }
  } catch (error) {
    detalle.value = null
    errorMensaje.value = error?.response?.data?.message || 'No se pudo cargar el calendario.'
  } finally {
    loadingDetalle.value = false
  }
}

const cargarTorneos = async () => {
  loadingTorneos.value = true
  errorMensaje.value = ''

  try {
    const data = await datosMaestrosService.getTorneos()
    torneos.value = Array.isArray(data) ? data : []

    if (!torneos.value.length) {
      idTorneoSeleccionado.value = 0
      detalle.value = null
      return
    }

    const existeSeleccion = torneos.value.some((t) => Number(t.id) === Number(idTorneoSeleccionado.value))
    if (!existeSeleccion) idTorneoSeleccionado.value = 0

    await cargarDetalle(idTorneoSeleccionado.value)
  } catch (error) {
    torneos.value = []
    detalle.value = null
    errorMensaje.value = error?.response?.data?.message || 'No se pudieron cargar los torneos.'
  } finally {
    loadingTorneos.value = false
  }
}

const onChangeTorneo = async () => {
  await cargarDetalle(idTorneoSeleccionado.value)
}

onMounted(async () => {
  await cargarTorneos()
})
</script>

<style scoped>
.btn-back-arrow {
  border: none;
  background: transparent;
  color: #6c757d;
  font-size: 1.1rem;
  width: 34px;
  height: 34px;
  border-radius: 999px;
  transition: background-color 0.2s ease;
}

.btn-back-arrow:hover {
  background: #f1f5f9;
}

.filtro-torneo {
  min-width: 190px;
  max-width: 280px;
}
</style>