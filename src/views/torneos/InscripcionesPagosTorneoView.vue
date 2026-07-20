<template>
  <div class="container-fluid p-4 bg-white min-vh-100 animate-fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom flex-wrap gap-2">
      <div class="d-flex align-items-center">
        <button @click="$router.back()" class="btn-back-arrow me-2" title="Volver">
          <i class="bi bi-arrow-left"></i>
        </button>
        <h1 class="h5 fw-bold mb-0 text-secondary">INSCRIPCIONES Y PAGOS</h1>
      </div>
      <div v-if="detalle" class="d-flex align-items-center gap-3">
        <span class="h5 fw-bold mb-0 text-dark">{{ detalle.torneo.nombre }}</span>
        <span
          class="badge rounded-pill fs-6 px-3 py-2"
          :class="inscripcionesCompletas ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning'"
        >
          Inscriptos {{ detalle.inscripciones?.total || 0 }}/{{ totalInscriptosObjetivo }}
        </span>
      </div>
    </div>

    <div v-if="!idTorneoSeleccionado" class="alert alert-info mb-0 d-flex align-items-center justify-content-between flex-wrap gap-2">
      <span>No hay un torneo seleccionado.</span>
      <button class="btn btn-sm btn-outline-primary" @click="$router.push('/gestiontorneos')">
        Ir a Gestión de torneos
      </button>
    </div>

    <div v-else-if="loadingDetalle" class="card shadow-sm border-0 rounded-lg">
      <div class="card-body py-4 text-center text-muted">
        <span class="spinner-border spinner-border-sm me-2"></span>
        Cargando...
      </div>
    </div>

    <template v-else-if="detalle">
      <InscripcionesPortalSection
        ref="portalSectionRef"
        :id-torneo="idTorneoSeleccionado"
        @aprobada="cargarDetalle(idTorneoSeleccionado)"
        @rechazadas="solicitudesRechazadas = $event"
      />

      <!-- Equipos confirmados / cupos del torneo -->
      <div class="seccion-solicitudes seccion-confirmados mb-3">
        <div class="seccion-header d-flex align-items-center justify-content-between gap-2 p-3">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-check-circle text-success fs-5"></i>
            <div>
              <div class="fw-bold">Equipos confirmados</div>
              <div class="small text-muted">Equipos oficialmente inscriptos en el torneo. Tienen su cupo asegurado.</div>
            </div>
          </div>
          <span class="badge rounded-pill bg-success-subtle text-success">
            {{ detalle.inscripciones?.total || 0 }}/{{ totalInscriptosObjetivo }}
          </span>
        </div>

        <div class="table-responsive border-top">
          <table class="table table-sm table-cupos align-middle mb-0">
            <thead>
              <tr>
                <th style="width:70px">Cupo</th>
                <th>Equipo</th>
                <th>Pago</th>
                <th>Fecha inscripción</th>
                <th class="text-end">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(cupo, idx) in cuposVisual" :key="idx" :class="cupo ? 'fila-cupo-confirmado' : 'fila-cupo-vacio'">
                <td class="text-muted">{{ idx + 1 }}</td>
                <td v-if="cupo">
                  <div class="d-flex align-items-center gap-2">
                    <img v-if="cupo.escudo" :src="resolveEscudoUrl(cupo.escudo)" alt="escudo" class="escudo-thumb" />
                    <span class="fw-semibold">{{ cupo.equipo_nombre }}</span>
                  </div>
                </td>
                <td v-else>
                  <span class="badge rounded-pill bg-light text-muted border">
                    <i class="bi bi-dash-circle me-1"></i>Disponible
                  </span>
                </td>
                <td v-if="cupo">
                  <div v-if="cupo.comprobante_pago" class="d-flex align-items-center gap-2">
                    <span class="badge rounded-pill bg-success-subtle text-success">
                      <i class="bi bi-check-circle me-1"></i>Comprobante subido
                    </span>
                  </div>
                  <span v-else class="badge rounded-pill bg-secondary-subtle text-secondary">
                    Sin comprobante
                  </span>
                </td>
                <td v-else>-</td>
                <td>{{ cupo ? (cupo.fecha_inscripcion || '-') : '-' }}</td>
                <td class="text-end">
                  <div v-if="cupo" class="d-inline-flex gap-2">
                    <button
                      v-if="cupo.id_inscripcion_equipo"
                      class="btn btn-sm btn-outline-secondary"
                      @click="portalSectionRef?.abrirVisualizarPorId(cupo.id_inscripcion_equipo)"
                    >
                      Ver inscripción
                    </button>
                    <!-- <button class="btn btn-sm btn-outline-danger" @click="eliminarInscripcion(cupo)">
                      Quitar
                    </button> -->
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Rechazadas -->
      <div v-if="solicitudesRechazadas.length" class="seccion-solicitudes seccion-rechazadas">
        <div class="seccion-header d-flex align-items-center justify-content-between gap-2 p-3">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-x-circle text-danger fs-5"></i>
            <div>
              <div class="fw-bold">Rechazadas</div>
              <div class="small text-muted">Inscripciones que fueron rechazadas.</div>
            </div>
          </div>
          <span class="badge rounded-pill bg-danger-subtle text-danger">{{ solicitudesRechazadas.length }}</span>
        </div>

        <div class="border-top">
          <div v-for="sol in solicitudesRechazadas" :key="sol.id" class="solicitud-row d-flex flex-wrap align-items-center justify-content-between gap-2 p-3">
            <div>
              <div class="fw-semibold">{{ sol.nombre_equipo }}</div>
              <div class="small text-muted">
                <i class="bi bi-envelope me-1"></i>{{ sol.email_solicitante }}
                <span class="mx-1">·</span>
                <i class="bi bi-calendar3 me-1"></i>{{ formatFecha(sol.fecha_creacion) }}
              </div>
            </div>
            <div class="d-flex align-items-center gap-2">
              <span class="badge rounded-pill bg-danger-subtle text-danger">{{ sol.estado }}</span>
              <button class="btn btn-sm btn-outline-danger" @click="portalSectionRef?.abrirVisualizar(sol)">
                <i class="bi bi-eye me-1"></i>Ver
              </button>
            </div>
          </div>
        </div>
      </div>
    </template>

    <ConfirmModal
      v-model="showEliminarInscripcionModal"
      title="Eliminar inscripción"
      :message="`¿Eliminar la inscripción de ${nombreEquipoAEliminarInscripcion}?`"
      confirm-button-text="Eliminar"
      variant="danger"
      @confirm="confirmarEliminarInscripcion"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import planTorneoService from '@/services/torneos/planTorneoService'
import { useToastStore } from '@/stores/toastStore'
import { useTorneoGestionStore } from '@/stores/torneoGestionStore'
import { resolveComprobantePagoUrl } from '@/utils/comprobantesPago'
import InscripcionesPortalSection from '@/components/torneos/InscripcionesPortalSection.vue'
import ConfirmModal from '@/components/ConfirmModal.vue'

const toast = useToastStore()
const route = useRoute()
const torneoGestionStore = useTorneoGestionStore()

// El torneo lo elige el usuario en Gestión de torneos; acá solo se lee del store.
const idTorneoSeleccionado = computed(() => torneoGestionStore.idTorneoSeleccionado)
const detalle = ref(null)
const loadingDetalle = ref(false)

// InscripcionesPortalSection es dueña de los datos y del modal de revisión;
// esta vista solo necesita la lista de rechazadas (vía evento) y una referencia
// al componente para poder abrir el modal desde la sección "Rechazadas" de acá.
const portalSectionRef = ref(null)
const solicitudesRechazadas = ref([])

const getApiMessage = (error, fallback) => error?.response?.data?.message || fallback

const formatFecha = (value) => {
  if (!value) return '-'
  return new Date(String(value).replace(' ', 'T')).toLocaleDateString('es-AR')
}

const resolveEscudoUrl = (escudo) => {
  if (!escudo) return ''

  const value = String(escudo).trim()
  if (value === '') return ''
  if (/^https?:\/\//i.test(value) || value.startsWith('data:')) return value

  const apiBase = import.meta.env.VITE_API_URL
  if (!apiBase) return value

  try {
    const apiUrl = new URL(apiBase, window.location.origin)
    const apiPath = String(apiUrl.pathname || '').replace(/\/+$/, '')
    const projectBasePath = apiPath.endsWith('/api') ? apiPath.slice(0, -4) : ''

    if (value.startsWith('/')) {
      if (projectBasePath && !value.startsWith(projectBasePath + '/')) {
        return `${apiUrl.origin}${projectBasePath}${value}`
      }
      return `${apiUrl.origin}${value}`
    }

    if (projectBasePath && value.startsWith('uploads/')) {
      return `${apiUrl.origin}${projectBasePath}/${value}`
    }

    return new URL(value, apiUrl.href).toString()
  } catch {
    return value
  }
}

const cargarDetalle = async (idTorneo = null) => {
  if (idTorneo !== null) {
    torneoGestionStore.seleccionar(idTorneo)
  }

  if (!idTorneoSeleccionado.value) return
  loadingDetalle.value = true
  try {
    detalle.value = await planTorneoService.getDetalleGestion(idTorneoSeleccionado.value)
  } catch (error) {
    detalle.value = null
    toast.showToast({ message: getApiMessage(error, 'No se pudo cargar el detalle del torneo.'), type: 'danger' })
  } finally {
    loadingDetalle.value = false
  }
}

const totalSlots = computed(() => {
  let total = 0
  for (const g of (detalle.value?.grupos || [])) {
    total += Number(g.cantidad_equipos_objetivo || 0)
  }
  return total
})

const totalInscriptosObjetivo = computed(() => {
  const cupo = Number(detalle.value?.torneo?.cupo_equipos || 0)
  if (cupo > 0) return cupo
  return totalSlots.value
})

const inscripcionesCompletas = computed(() => {
  const total = Number(detalle.value?.inscripciones?.total || 0)
  const objetivo = Number(totalInscriptosObjetivo.value || 0)
  if (objetivo <= 0) return false
  return total >= objetivo
})

// Un cupo por equipo inscripto, más filas vacías ("Disponible") hasta completar el objetivo del torneo.
const cuposVisual = computed(() => {
  const inscriptos = detalle.value?.inscriptos || []
  const objetivo = Number(totalInscriptosObjetivo.value || 0)
  if (objetivo <= inscriptos.length) return inscriptos
  const vacios = Array.from({ length: objetivo - inscriptos.length }, () => null)
  return [...inscriptos, ...vacios]
})

const showEliminarInscripcionModal = ref(false)
const equipoAEliminarInscripcion = ref(null)

const nombreEquipoAEliminarInscripcion = computed(() => equipoAEliminarInscripcion.value?.equipo_nombre || 'este equipo')

const eliminarInscripcion = (item) => {
  equipoAEliminarInscripcion.value = item
  showEliminarInscripcionModal.value = true
}

const confirmarEliminarInscripcion = async () => {
  const item = equipoAEliminarInscripcion.value
  if (!item) return

  try {
    await planTorneoService.eliminarInscripcion({
      id_torneo: idTorneoSeleccionado.value,
      id_equipo_torneo: Number(item.id),
    })
    toast.showToast({ message: 'Inscripción eliminada correctamente.', type: 'success' })
    await cargarDetalle()
  } catch (error) {
    toast.showToast({ message: getApiMessage(error, 'No se pudo eliminar la inscripción.'), type: 'danger' })
  }
}

onMounted(async () => {
  // Prioridad: torneo pedido por URL (deep link) y, si no hay, el que ya
  // estaba elegido en Gestión de torneos.
  const idTorneoDesdeQuery = Number(route.query?.id_torneo || 0)
  const idCandidato = idTorneoDesdeQuery > 0 ? idTorneoDesdeQuery : Number(torneoGestionStore.idTorneoSeleccionado || 0)

  if (idCandidato > 0) {
    await cargarDetalle(idCandidato)
  }
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

.escudo-thumb {
  width: 24px;
  height: 24px;
  object-fit: cover;
  border-radius: 50%;
  border: 1px solid #cbd5e1;
}

.seccion-solicitudes {
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  overflow: hidden;
  background: #fff;
}

.seccion-confirmados {
  border-left: 4px solid #16a34a;
}

.seccion-rechazadas {
  border-left: 4px solid #dc2626;
}

.solicitud-row + .solicitud-row {
  border-top: 1px solid #f1f5f9;
}

.solicitud-row:hover {
  background: #f8fafc;
}

.table-cupos th,
.table-cupos td {
  padding: 0.75rem 1.25rem;
}

.fila-cupo-confirmado {
  background-color: #f0fdf4;
}

.fila-cupo-vacio {
  background-color: #f8fafc;
}

.fila-cupo-vacio td {
  color: #94a3b8;
}
</style>
