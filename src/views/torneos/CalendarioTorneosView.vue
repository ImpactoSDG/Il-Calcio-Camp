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

        <ul class="nav nav-tabs mb-3" role="tablist" aria-label="Vista de calendario y lista">
          <li class="nav-item" role="presentation">
            <button
              class="nav-link"
              :class="{ active: pestanaActiva === 'calendario' }"
              type="button"
              @click="pestanaActiva = 'calendario'"
            >
              Calendario
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button
              class="nav-link"
              :class="{ active: pestanaActiva === 'lista' }"
              type="button"
              @click="pestanaActiva = 'lista'"
            >
              Lista
            </button>
          </li>
        </ul>

        <div v-if="pestanaActiva === 'calendario'">
          <TorneoCalendar
            :eventos="eventosCalendario"
            :torneo-nombre="torneoNombreCalendario"
            :show-title="false"
          />
        </div>

        <div v-else class="d-flex flex-column gap-2">
          <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
            <input
              v-model.trim="busquedaLista"
              type="search"
              class="form-control form-control-sm buscador-lista"
              placeholder="Buscar por torneo, equipo, cancha o arbitro"
              aria-label="Buscar eventos en la lista"
            />
            <select
              v-model="ordenLista"
              class="form-select form-select-sm orden-lista"
              aria-label="Ordenar eventos por fecha"
            >
              <option value="asc">Fecha ascendente</option>
              <option value="desc">Fecha descendente</option>
            </select>
          </div>

          <div v-if="eventosListaFiltrada.length === 0" class="alert alert-light border mb-0">
            No hay eventos para mostrar en la lista.
          </div>

          <div v-else class="d-flex flex-column gap-2">
            <div v-for="ev in eventosListaFiltrada" :key="`lista-evento-${ev.id}`" class="evento-lista-item">
              <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                <div class="small text-muted">{{ formatearFechaHora(ev.fecha_hora_inicio) }}</div>
                <span class="badge rounded-pill" :class="getEstadoEventoBadgeClass(ev)">{{ ev.estado_evento_descripcion || 'Sin estado' }}</span>
              </div>

              <div class="fw-semibold mt-1">{{ ev.titulo || `${ev.equipo_local_nombre || '-'} vs ${ev.equipo_visitante_nombre || '-'}` }}</div>

              <div class="small text-muted mt-1">
                <span v-if="ev.torneo_nombre">Torneo: {{ ev.torneo_nombre }}</span>
                <span v-if="ev.cancha_nombre"> · Cancha: {{ ev.cancha_nombre }}</span>
                <span v-if="ev.arbitro_nombre_completo"> · Arbitro: {{ ev.arbitro_nombre_completo }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import datosMaestrosService from '@/services/datosMaestrosService'
import planTorneoService from '@/services/torneos/planTorneoService'
import TorneoCalendar from '@/components/torneos/TorneoCalendar.vue'

const torneos = ref([])
const idTorneoSeleccionado = ref(0)
const detalle = ref(null)
const loadingTorneos = ref(false)
const loadingDetalle = ref(false)
const errorMensaje = ref('')
const pestanaActiva = ref('calendario')
const busquedaLista = ref('')
const ordenLista = ref('asc')

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

const eventosLista = computed(() =>
  [...eventosCalendario.value]
    .map((ev) => {
      const dt = new Date(String(ev.fecha_hora_inicio).replace(' ', 'T'))
      return {
        ...ev,
        fecha_dt: dt,
      }
    })
)

const eventosListaFiltrada = computed(() => {
  const termino = busquedaLista.value.trim().toLowerCase()
  const ordenados = [...eventosLista.value].sort((a, b) => {
    if (ordenLista.value === 'desc') return b.fecha_dt.getTime() - a.fecha_dt.getTime()
    return a.fecha_dt.getTime() - b.fecha_dt.getTime()
  })

  if (!termino) return ordenados

  return ordenados.filter((ev) => {
    const campos = [
      ev.titulo,
      ev.torneo_nombre,
      ev.equipo_local_nombre,
      ev.equipo_visitante_nombre,
      ev.cancha_nombre,
      ev.arbitro_nombre_completo,
      ev.estado_evento_descripcion,
    ]
      .map((campo) => String(campo || '').toLowerCase())
      .join(' ')

    return campos.includes(termino)
  })
})

const formatearFechaHora = (fechaHora) => {
  if (!fechaHora) return '-'
  const dt = new Date(String(fechaHora).replace(' ', 'T'))
  if (Number.isNaN(dt.getTime())) return '-'
  return dt.toLocaleString('es-AR', {
    weekday: 'short',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
  })
}

const getEstadoEventoKey = (ev) => {
  const estadoId = Number(ev?.id_estado_evento || 0)
  const estado = String(ev?.estado_evento_descripcion || '').trim().toUpperCase()

  if (estadoId === 4 || estado.includes('FINALIZ')) return 'finalizado'
  if (estadoId === 2 || estado.includes('PROGRAMAD')) return 'programado'
  if (estadoId === 1 || estado.includes('PENDIENTE')) return 'pendiente'
  if (estado.includes('CANCEL') || estado.includes('SUSPEND')) return 'cancelado'

  return 'otro'
}

const getEstadoEventoBadgeClass = (ev) => {
  const key = getEstadoEventoKey(ev)
  if (key === 'finalizado') return 'estado-badge estado-finalizado'
  if (key === 'programado') return 'estado-badge estado-programado'
  if (key === 'pendiente') return 'estado-badge estado-pendiente'
  if (key === 'cancelado') return 'estado-badge estado-cancelado'
  return 'estado-badge estado-otro'
}

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
  min-width: 128px;
  max-width: 165px;
}

.evento-lista-item {
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 0.65rem 0.8rem;
  background: #fff;
}

.estado-badge {
  border: 1px solid transparent;
}

.estado-programado {
  background: #e0ecff;
  color: #0b4fbb;
  border-color: #b7d0ff;
}

.estado-pendiente {
  background: #fff3d6;
  color: #8a5700;
  border-color: #ffe19a;
}

.estado-finalizado {
  background: #dcf7e7;
  color: #0f6a38;
  border-color: #afe8c8;
}

.estado-cancelado {
  background: #ffe1e1;
  color: #9f1d1d;
  border-color: #ffc0c0;
}

.estado-otro {
  background: #eef2f6;
  color: #4b5563;
  border-color: #d7dee6;
}

.buscador-lista {
  min-width: 230px;
  max-width: 420px;
}

.orden-lista {
  width: 180px;
}
</style>