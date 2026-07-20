<template>
  <div class="container-fluid p-4 bg-white min-vh-100 position-relative animate-fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
      <div class="d-flex align-items-center">
        <button @click="$router.back()" class="btn-back-arrow me-2" title="Volver">
          <i class="bi bi-arrow-left"></i>
        </button>
        <h1 class="h5 fw-bold mb-0 text-secondary">GESTION DE TORNEOS</h1>
      </div>
    </div>

    <div class="card shadow-sm border-0 rounded-lg mb-4">
      <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h2 class="h6 fw-bold text-secondary mb-0">Torneos</h2>
          <small class="text-muted">Haz click en un torneo para gestionarlo</small>
        </div>

        <div v-if="loadingTorneos" class="text-center py-3 text-muted">
          <span class="spinner-border spinner-border-sm me-2"></span>
          Cargando torneos...
        </div>

        <div v-if="!loadingTorneos" class="torneo-grid">
          <div v-if="torneos.length === 0" class="alert alert-warning mb-0 w-100">
            No hay torneos disponibles.
          </div>

          <button
            v-for="torneo in torneos"
            :key="torneo.id"
            type="button"
            class="torneo-card"
            :class="{ active: Number(idTorneoSeleccionado) === Number(torneo.id) }"
            @click="cargarDetalle(Number(torneo.id))"
            :disabled="loadingDetalle"
          >
            <div class="d-flex justify-content-between align-items-start gap-2">
              <div>
                <div class="fw-semibold text-start">{{ torneo.nombre }}</div>
                <div class="small text-muted text-start">{{ torneo.disciplina_nombre || 'Sin disciplina' }}</div>
              </div>
              <div class="d-flex flex-column align-items-end gap-2">
                <span class="badge rounded-pill text-bg-light">#{{ torneo.id }}</span>
                <div v-if="Number(torneo.solicitudes_pendientes) > 0" class="position-relative" style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;" title="Solicitudes de inscripción pendientes de revisión">
                  <i class="bi bi-bell-fill text-warning" style="font-size: 1.3rem;"></i>
                  <span class="badge rounded-pill bg-danger text-white position-absolute" style="bottom: -4px; right: -6px; padding: 0.15rem 0.3rem; font-size: 0.55rem; min-width: 16px; display: flex; align-items: center; justify-content: center;">
                    {{ torneo.solicitudes_pendientes }}
                  </span>
                </div>
              </div>
            </div>
            <div class="text-start mt-2">
              <span
                class="badge rounded-pill torneo-estado-pill"
                :class="getEstadoTorneoBadgeClass(torneo.estado_torneo_descripcion)"
              >
                {{ torneo.estado_torneo_descripcion || 'Sin estado' }}
              </span>
            </div>
            <div
              v-if="loadingDetalle && Number(idTorneoSeleccionado) === Number(torneo.id)"
              class="small mt-1 text-primary text-start"
            >
              Cargando...
            </div>
          </button>

          <button
            type="button"
            class="torneo-card torneo-card-create"
            title="Crear nuevo torneo"
            @click="$router.push('/plantorneo')"
          >
            <div class="torneo-card-create-content" aria-hidden="true">
              <i class="bi bi-plus-lg torneo-card-create-icon"></i>
            </div>
          </button>
        </div>
      </div>
    </div>

    <div v-if="idTorneoSeleccionado && !detalle && !loadingDetalle" class="alert alert-info">
      Selecciona un torneo del mosaico para ver su información.
    </div>

    <template v-if="detalle">
      <div class="card shadow-sm border-0 rounded-lg mb-4 torneo-info-card">
        <div class="card-body p-4">
          <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
              <h2 class="h4 fw-bold text-secondary mb-2">
                <i class="bi bi-trophy-fill me-2 torneo-info-icon"></i>{{ detalle.torneo.nombre }}
              </h2>
              <div class="d-flex flex-wrap align-items-center gap-2">
                <span
                  class="badge rounded-pill torneo-estado-pill"
                  :class="getEstadoTorneoBadgeClass(detalle.torneo.estado_torneo_descripcion)"
                >
                  {{ detalle.torneo.estado_torneo_descripcion || 'Sin estado' }}
                </span>
                <span class="small text-muted">{{ detalle.torneo.disciplina_nombre || 'Sin disciplina' }}</span>
                <span v-if="detalle.torneo.formato_manual" class="small text-muted">· {{ detalle.torneo.formato_manual }}</span>
                <span class="small text-muted">· Inicio: {{ detalle.torneo.fecha_inicio ? new Date(detalle.torneo.fecha_inicio).toLocaleDateString('es-AR') : '-' }}</span>
              </div>
              <div class="d-flex flex-wrap gap-2 mt-3">
                <span class="info-stat-chip">
                  <i class="bi bi-people-fill"></i>
                  {{ detalle.inscripciones?.total || 0 }} inscriptos
                </span>
                <span class="info-stat-chip">
                  <i class="bi bi-calendar-check-fill"></i>
                  {{ detalle.eventos?.programados || 0 }}/{{ detalle.eventos?.total || 0 }} partidos programados
                </span>
              </div>
            </div>

            <div class="d-flex align-items-center gap-2">
              <button class="btn btn-outline-primary" @click="abrirEditarTorneoModal">
                <i class="bi bi-pencil me-1"></i>Editar
              </button>

              <button class="btn btn-outline-danger" @click="abrirEliminarTorneoModal">
                <i class="bi bi-trash me-1"></i>Eliminar
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
          <button type="button" class="nav-module-card position-relative" @click="irAInscripcionesPagos">
            <div class="d-flex align-items-center gap-3">
              <div class="nav-module-icon nav-module-icon-warning">
                <i class="bi bi-clipboard-check"></i>
              </div>
              <div>
                <div class="fw-semibold text-secondary">Inscripciones y pagos</div>
                <div class="small text-muted mt-1">
                  {{ detalle.inscripciones?.total || 0 }} inscriptos
                  <span v-if="Number(detalle.inscripciones?.solicitudes_activas) > 0"> · {{ detalle.inscripciones.solicitudes_activas }} solicitud(es) pendiente(s)</span>
                </div>
              </div>
            </div>
            <div class="d-flex align-items-center gap-3">
              <div v-if="Number(detalle.inscripciones?.solicitudes_activas) > 0" class="position-relative" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                <i class="bi bi-bell-fill text-warning" style="font-size: 1.4rem;"></i>
                <span class="badge rounded-pill bg-danger text-white position-absolute" style="bottom: -4px; right: -6px; padding: 0.2rem 0.35rem; font-size: 0.6rem; min-width: 18px; display: flex; align-items: center; justify-content: center;">
                  {{ detalle.inscripciones.solicitudes_activas }}
                </span>
              </div>
              <i class="bi bi-chevron-right text-muted"></i>
            </div>
          </button>
        </div>
        <div class="col-12 col-md-4">
          <button type="button" class="nav-module-card" @click="irAAsignaciones">
            <div class="d-flex align-items-center gap-3">
              <div class="nav-module-icon nav-module-icon-info">
                <i class="bi bi-diagram-3"></i>
              </div>
              <div>
                <div class="fw-semibold text-secondary">Asignaciones de equipos</div>
                <div class="small text-muted mt-1">{{ detalle.inscripciones?.asignadas || 0 }} equipos asignados</div>
              </div>
            </div>
            <i class="bi bi-chevron-right text-muted"></i>
          </button>
        </div>
        <div class="col-12 col-md-4">
          <button type="button" class="nav-module-card" @click="irAProgramacionCalendario">
            <div class="d-flex align-items-center gap-3">
              <div class="nav-module-icon nav-module-icon-success">
                <i class="bi bi-calendar3"></i>
              </div>
              <div>
                <div class="fw-semibold text-secondary">Programación y calendario</div>
                <div class="small text-muted mt-1">{{ detalle.eventos?.programados || 0 }}/{{ detalle.eventos?.total || 0 }} partidos programados</div>
              </div>
            </div>
            <i class="bi bi-chevron-right text-muted"></i>
          </button>
        </div>
      </div>
    </template>

    <Teleport to="body">
      <div v-if="showEditarTorneoModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Editar torneo</h5>
              <button type="button" class="btn-close" @click="cancelarEditarTorneo"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label small">Nombre</label>
                <input type="text" class="form-control" v-model="editarTorneoForm.nombre" placeholder="Nombre del torneo" />
              </div>

              <div class="mb-3">
                <label class="form-label small">Descripción</label>
                <textarea class="form-control" v-model="editarTorneoForm.descripcion" placeholder="Descripción del torneo (opcional)" rows="2"></textarea>
              </div>

              <div class="mb-3">
                <label class="form-label small">Estado</label>
                <select class="form-select" v-model="editarTorneoForm.id_estado_torneo" :disabled="loadingEstadosTorneo">
                  <option :value="null">Sin estado</option>
                  <option v-for="estado in estadosTorneo" :key="estado.id" :value="Number(estado.id)">
                    {{ estado.descripcion }}
                  </option>
                </select>
                <div class="form-text">
                  Mientras el torneo tenga partidos en curso, el estado se actualiza automáticamente a "En curso". Para fijarlo manualmente usá Finalizado o Cancelado.
                </div>
              </div>

              <div class="row g-3 mb-1">
                <div class="col-6">
                  <label class="form-label small">Fecha de inicio</label>
                  <input type="date" class="form-control" v-model="editarTorneoForm.fecha_inicio" />
                </div>
                <div class="col-6">
                  <label class="form-label small">Valor de inscripción</label>
                  <input type="number" min="0" step="0.01" class="form-control" v-model.number="editarTorneoForm.valor_inscripcion" placeholder="0.00" />
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button class="btn btn-outline-secondary" @click="cancelarEditarTorneo">Cancelar</button>
              <button class="btn btn-primary" @click="guardarEditarTorneo" :disabled="savingEditarTorneo || !editarTorneoForm.nombre.trim()">
                <span v-if="savingEditarTorneo" class="spinner-border spinner-border-sm me-2"></span>
                Guardar cambios
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <Teleport to="body">
      <div v-if="showEliminarTorneoModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Eliminar torneo</h5>
              <button type="button" class="btn-close" @click="cancelarEliminarTorneo"></button>
            </div>
            <div class="modal-body">
              <p class="text-muted small mb-2">
                Esta acción hará borrado lógico del torneo. Para confirmar, escribe el nombre exacto del torneo.
              </p>
              <p class="fw-semibold mb-2">{{ detalle?.torneo?.nombre }}</p>

              <label class="form-label small">Confirmación por nombre</label>
              <input type="text" class="form-control mb-3" v-model="confirmNombreEliminar" placeholder="Escribe el nombre del torneo" />

              <label class="form-label small">Motivo (opcional)</label>
              <input type="text" class="form-control" v-model="motivoBajaTorneo" placeholder="Motivo de baja" />
            </div>
            <div class="modal-footer">
              <button class="btn btn-outline-secondary" @click="cancelarEliminarTorneo">Cancelar</button>
              <button class="btn btn-danger" @click="eliminarTorneoSeleccionado" :disabled="savingEliminarTorneo || !nombreEliminarCoincide">
                <span v-if="savingEliminarTorneo" class="spinner-border spinner-border-sm me-2"></span>
                Eliminar torneo
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import datosMaestrosService from '@/services/datosMaestrosService'
import planTorneoService from '@/services/torneos/planTorneoService'
import { useToastStore } from '@/stores/toastStore'
import { useTorneoGestionStore } from '@/stores/torneoGestionStore'

const toast = useToastStore()
const route = useRoute()
const router = useRouter()
const torneoGestionStore = useTorneoGestionStore()

const torneos = ref([])
// El torneo seleccionado vive en un store compartido para que se mantenga
// al navegar a inscripciones/asignaciones/calendario y volver.
const idTorneoSeleccionado = computed({
  get: () => torneoGestionStore.idTorneoSeleccionado,
  set: (val) => torneoGestionStore.seleccionar(val),
})
const detalle = ref(null)
const loadingTorneos = ref(false)
const loadingDetalle = ref(false)
const savingEliminarTorneo = ref(false)

const showEliminarTorneoModal = ref(false)
const confirmNombreEliminar = ref('')
const motivoBajaTorneo = ref('')

const showEditarTorneoModal = ref(false)
const savingEditarTorneo = ref(false)
const estadosTorneo = ref([])
const loadingEstadosTorneo = ref(false)
const editarTorneoForm = ref({
  nombre: '',
  descripcion: '',
  id_estado_torneo: null,
  fecha_inicio: '',
  valor_inscripcion: 0,
})

const getApiMessage = (error, fallback) => error?.response?.data?.message || fallback

const getEstadoTorneoBadgeClass = (estado) => {
  const key = String(estado || '').trim().toUpperCase()

  if (key === 'PLANIFICADO') return 'bg-secondary-subtle text-secondary'
  if (key === 'EN CURSO') return 'bg-success-subtle text-success'
  if (key === 'FINALIZADO') return 'bg-dark-subtle text-dark'
  if (key === 'CANCELADO') return 'bg-danger-subtle text-danger'

  return 'bg-light text-muted'
}

const cargarTorneos = async () => {
  loadingTorneos.value = true
  try {
    torneos.value = await datosMaestrosService.getTorneos()
  } catch (error) {
    toast.showToast({ message: getApiMessage(error, 'No se pudieron cargar los torneos.'), type: 'danger' })
  } finally {
    loadingTorneos.value = false
  }
}

const cargarDetalle = async (idTorneo = null) => {
  if (idTorneo !== null) {
    idTorneoSeleccionado.value = Number(idTorneo)
  }

  if (!idTorneoSeleccionado.value) return

  if (String(route.query?.id_torneo || '') !== String(idTorneoSeleccionado.value)) {
    router.replace({ query: { ...route.query, id_torneo: idTorneoSeleccionado.value } })
  }

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

const nombreEliminarCoincide = computed(() => {
  const actual = String(detalle.value?.torneo?.nombre || '').trim()
  const confirm = String(confirmNombreEliminar.value || '').trim()
  return actual !== '' && actual === confirm
})

const abrirEliminarTorneoModal = () => {
  confirmNombreEliminar.value = ''
  motivoBajaTorneo.value = ''
  showEliminarTorneoModal.value = true
}

const cancelarEliminarTorneo = () => {
  showEliminarTorneoModal.value = false
  confirmNombreEliminar.value = ''
  motivoBajaTorneo.value = ''
}

const eliminarTorneoSeleccionado = async () => {
  if (!idTorneoSeleccionado.value || !nombreEliminarCoincide.value) {
    return
  }

  savingEliminarTorneo.value = true
  try {
    await datosMaestrosService.eliminarTorneo(
      Number(idTorneoSeleccionado.value),
      motivoBajaTorneo.value?.trim() || null,
    )

    toast.showToast({ message: 'Torneo eliminado correctamente.', type: 'success' })
    cancelarEliminarTorneo()
    idTorneoSeleccionado.value = null
    detalle.value = null
    await cargarTorneos()
  } catch (error) {
    toast.showToast({ message: getApiMessage(error, 'No se pudo eliminar el torneo.'), type: 'danger' })
  } finally {
    savingEliminarTorneo.value = false
  }
}

const cargarEstadosTorneo = async () => {
  if (estadosTorneo.value.length) return
  loadingEstadosTorneo.value = true
  try {
    estadosTorneo.value = await datosMaestrosService.getEstadosTorneo()
  } catch (error) {
    toast.showToast({ message: getApiMessage(error, 'No se pudieron cargar los estados de torneo.'), type: 'danger' })
  } finally {
    loadingEstadosTorneo.value = false
  }
}

const abrirEditarTorneoModal = () => {
  editarTorneoForm.value = {
    nombre: detalle.value?.torneo?.nombre || '',
    descripcion: detalle.value?.torneo?.descripcion || '',
    id_estado_torneo: detalle.value?.torneo?.id_estado_torneo ? Number(detalle.value.torneo.id_estado_torneo) : null,
    fecha_inicio: detalle.value?.torneo?.fecha_inicio ? String(detalle.value.torneo.fecha_inicio).slice(0, 10) : '',
    valor_inscripcion: Number(detalle.value?.torneo?.valor_inscripcion) || 0,
  }
  showEditarTorneoModal.value = true
  cargarEstadosTorneo()
}

const cancelarEditarTorneo = () => {
  showEditarTorneoModal.value = false
}

const guardarEditarTorneo = async () => {
  if (!idTorneoSeleccionado.value || !editarTorneoForm.value.nombre.trim()) return

  savingEditarTorneo.value = true
  try {
    await datosMaestrosService.actualizarTorneo({
      id: Number(idTorneoSeleccionado.value),
      nombre: editarTorneoForm.value.nombre.trim(),
      descripcion: editarTorneoForm.value.descripcion?.trim() || null,
      id_estado_torneo: editarTorneoForm.value.id_estado_torneo,
      fecha_inicio: editarTorneoForm.value.fecha_inicio || null,
      valor_inscripcion: editarTorneoForm.value.valor_inscripcion || 0,
    })

    toast.showToast({ message: 'Torneo actualizado correctamente.', type: 'success' })
    showEditarTorneoModal.value = false
    await Promise.all([cargarDetalle(), cargarTorneos()])
  } catch (error) {
    toast.showToast({ message: getApiMessage(error, 'No se pudo actualizar el torneo.'), type: 'danger' })
  } finally {
    savingEditarTorneo.value = false
  }
}

const irAInscripcionesPagos = () => {
  if (!idTorneoSeleccionado.value) return
  router.push({ path: '/torneos/inscripciones-pagos', query: { id_torneo: idTorneoSeleccionado.value } })
}

const irAAsignaciones = () => {
  if (!idTorneoSeleccionado.value) return
  router.push({ path: '/torneos/asignaciones', query: { id_torneo: idTorneoSeleccionado.value } })
}

const irAProgramacionCalendario = () => {
  if (!idTorneoSeleccionado.value) return
  router.push({ path: '/calendario-torneos', query: { id_torneo: idTorneoSeleccionado.value } })
}

onMounted(async () => {
  await cargarTorneos()

  // Prioridad: torneo pedido por URL (deep link) y, si no hay, el que ya
  // estaba elegido en el store (por ejemplo, al volver desde Inscripciones).
  const idTorneoDesdeQuery = Number(route.query?.id_torneo || 0)
  const idCandidato = idTorneoDesdeQuery > 0 ? idTorneoDesdeQuery : Number(torneoGestionStore.idTorneoSeleccionado || 0)

  if (idCandidato > 0) {
    const existeEnListado = torneos.value.some(t => Number(t.id) === idCandidato)
    if (existeEnListado) {
      await cargarDetalle(idCandidato)
    } else {
      torneoGestionStore.limpiar()
    }
  }
})
</script>

<style scoped>
.torneo-info-card {
  border-left: 4px solid #0ea5e9 !important;
  background: #f0f9ff;
}

.torneo-info-icon {
  color: #f59e0b;
}

.info-stat-chip {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 6px 14px;
  border-radius: 999px;
  background: #fff;
  border: 1px solid #dbeafe;
  font-size: 0.82rem;
  font-weight: 700;
  color: #334155;
}

.info-stat-chip i {
  color: #0ea5e9;
  font-size: 0.95rem;
}

.nav-module-card {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 16px;
  background: #fff;
  text-align: left;
  transition: all 0.2s ease;
}

.nav-module-card:hover {
  border-color: #94a3b8;
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.1);
  background: #f8fafc;
  transform: translateY(-2px);
}

.nav-module-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  flex-shrink: 0;
}

.nav-module-icon-warning {
  background: #fff3d6;
  color: #8a5700;
}

.nav-module-icon-info {
  background: #e0ecff;
  color: #0b4fbb;
}

.nav-module-icon-success {
  background: #dcf7e7;
  color: #0f6a38;
}

.torneo-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 12px;
}

.torneo-card {
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 12px;
  background: #fff;
  transition: all 0.2s ease;
  width: 100%;
  min-height: 110px;
  text-align: left;
}

.torneo-card:hover {
  border-color: #94a3b8;
  box-shadow: 0 6px 16px rgba(15, 23, 42, 0.08);
}

.torneo-card.active {
  border-color: #0ea5e9;
  box-shadow: 0 0 0 2px rgba(14, 165, 233, 0.12);
  background: #f0f9ff;
}

.torneo-estado-pill {
  font-size: 0.74rem;
  font-weight: 600;
  letter-spacing: 0.01em;
}

.torneo-card-create {
  display: flex;
  align-items: center;
  justify-content: center;
  border-style: dashed;
  border-color: #94a3b8;
  background: #f8fafc;
}

.torneo-card-create:hover {
  border-color: #3b82f6;
  background: #eff6ff;
}

.torneo-card-create-icon {
  font-size: 2rem;
  color: #64748b;
  transition: transform 0.2s ease, color 0.2s ease;
}

.torneo-card-create:hover .torneo-card-create-icon {
  color: #2563eb;
  transform: scale(1.08);
}

</style>
