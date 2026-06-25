<template>
  <div class="mt-4 pt-3 border-top">
    <!-- Encabezado -->
    <div class="mb-3">
      <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
          <h3 class="h6 fw-bold text-secondary mb-1">Solicitudes del portal web</h3>
          <p class="small text-muted mb-0">Inscripciones enviadas por delegados. Revisá y aprobá o rechazá cada una.</p>
        </div>
        <div class="d-flex gap-2 align-items-center flex-shrink-0">
          <select v-model="filtroEstado" class="form-select form-select-sm" style="min-width:150px">
            <option :value="null">Todos los estados</option>
            <option :value="1">Pendiente</option>
            <option :value="7">Observada</option>
            <option :value="8">Aprobada</option>
            <option :value="5">Rechazada</option>
          </select>
          <button class="btn btn-sm btn-outline-secondary" @click="cargar" :disabled="loading">
            <span v-if="loading" class="spinner-border spinner-border-sm"></span>
            <i v-else class="bi bi-arrow-clockwise"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- Estado de carga -->
    <div v-if="loading" class="text-center py-3 text-muted">
      <span class="spinner-border spinner-border-sm me-2"></span>Cargando solicitudes...
    </div>

    <!-- Sin resultados -->
    <div v-else-if="solicitudesFiltradas.length === 0" class="alert alert-light border mb-0 small">
      No hay solicitudes del portal para este torneo<span v-if="filtroEstado"> con ese estado</span>.
    </div>

    <!-- Tabla -->
    <div v-else class="table-responsive">
      <table class="table table-sm align-middle mb-0">
        <thead>
          <tr>
            <th>Equipo</th>
            <th>Solicitante</th>
            <th>Estado</th>
            <th>Fecha</th>
            <th class="text-end">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="sol in solicitudesFiltradas" :key="sol.id">
            <td class="fw-semibold">{{ sol.nombre_equipo }}</td>
            <td class="small text-muted">{{ sol.email_solicitante }}</td>
            <td>
              <span class="badge rounded-pill" :class="badgeEstado(sol.estado)">{{ sol.estado }}</span>
            </td>
            <td class="small text-muted">{{ formatFecha(sol.fecha_creacion) }}</td>
            <td class="text-end">
              <div class="d-inline-flex gap-1">
                <button class="btn btn-sm btn-outline-secondary" @click="verDetalle(sol)" title="Ver jugadores">
                  <i class="bi bi-eye"></i>
                </button>
                <!-- Solicitud bloqueada (aprobada o rechazada): solo lápiz para desbloquear -->
                <button
                  v-if="[5, 8].includes(Number(sol.id_estado)) && solicitudDesbloqueadaId !== sol.id"
                  class="btn btn-sm btn-outline-secondary"
                  title="Editar estado"
                  @click="solicitudDesbloqueadaId = sol.id"
                >
                  <i class="bi bi-pencil"></i>
                </button>
                <!-- Botón para volver a bloquear -->
                <button
                  v-if="solicitudDesbloqueadaId === sol.id"
                  class="btn btn-sm btn-outline-secondary"
                  title="Cancelar edición"
                  @click="solicitudDesbloqueadaId = null"
                >
                  <i class="bi bi-x"></i>
                </button>
                <!-- Acciones disponibles: pendiente/observada, o desbloqueada manualmente -->
                <template v-if="![5, 8].includes(Number(sol.id_estado)) || solicitudDesbloqueadaId === sol.id">
                  <button
                    v-if="Number(sol.id_estado) !== 8"
                    class="btn btn-sm btn-outline-success"
                    @click="abrirAprobar(sol)"
                    title="Aprobar"
                  >
                    <i class="bi bi-check-lg"></i>
                  </button>
                  <button
                    v-if="Number(sol.id_estado) !== 8"
                    class="btn btn-sm btn-outline-warning"
                    @click="abrirAccion(sol, 'observar')"
                    title="Observar"
                  >
                    <i class="bi bi-chat-left-text"></i>
                  </button>
                  <button
                    v-if="Number(sol.id_estado) !== 5"
                    class="btn btn-sm btn-outline-danger"
                    @click="abrirAccion(sol, 'rechazar')"
                    title="Rechazar"
                  >
                    <i class="bi bi-x-lg"></i>
                  </button>
                </template>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Modal: ver jugadores -->
  <div v-if="showDetalleModal" class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,0.4)" @click="dropdownAbierto = null">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Solicitud: {{ solicitudActual?.nombre_equipo }}</h5>
          <button class="btn-close" @click="showDetalleModal = false"></button>
        </div>
        <div class="modal-body">
          <p class="small text-muted mb-3">
            Solicitante: <strong>{{ solicitudActual?.email_solicitante }}</strong> —
            Estado: <span class="badge" :class="badgeEstado(solicitudActual?.estado)">{{ solicitudActual?.estado }}</span>
          </p>
          <div v-if="solicitudActual?.observacion_admin" class="alert alert-warning small mb-3">
            <strong>Observación:</strong> {{ solicitudActual.observacion_admin }}
          </div>
          <div v-if="loadingJugadores" class="text-center py-3 text-muted">
            <span class="spinner-border spinner-border-sm me-2"></span>Cargando jugadores...
          </div>
          <table v-else class="table table-sm align-middle">
            <thead>
              <tr>
                <th>Nombre</th>
                <th>DNI</th>
                <th>Email</th>
                <th>Documento</th>
                <th colspan="2">Estado doc.</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="j in jugadores" :key="j.id">
                <td>{{ j.apellido }}, {{ j.nombre }}</td>
                <td class="small">{{ j.dni }}</td>
                <td class="small">{{ j.email || '-' }}</td>
                <td>
                  <a
                    v-if="j.archivo_documentacion"
                    :href="resolveArchivoUrl(j.archivo_documentacion)"
                    target="_blank"
                    rel="noopener"
                    class="btn btn-sm btn-outline-secondary"
                    title="Ver archivo"
                  >
                    <i class="bi bi-file-earmark-text me-1"></i>Ver
                  </a>
                  <span v-else class="small text-muted">Sin archivo</span>
                </td>
                <td colspan="2">
                  <span v-if="savingDocId === j.id" class="spinner-border spinner-border-sm text-secondary"></span>
                  <div v-else class="estado-doc-dropdown" @click.stop>
                    <button
                      class="estado-doc-btn"
                      :class="estadoDocClass(j.estado_documentacion)"
                      @click="toggleDropdown(j.id)"
                    >
                      {{ j.estado_documentacion }}
                      <i class="bi bi-chevron-down ms-1" style="font-size:0.65rem"></i>
                    </button>
                    <ul v-if="dropdownAbierto === j.id" class="estado-doc-menu">
                      <li
                        v-for="op in estadosDoc"
                        :key="op.value"
                        :class="['estado-doc-item', op.clase, { active: j.estado_documentacion === op.value }]"
                        @click="seleccionarEstadoDoc(j, op.value)"
                      >
                        {{ op.label }}
                      </li>
                    </ul>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="showDetalleModal = false">Cerrar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal: observar / rechazar -->
  <div v-if="showAccionModal" class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,0.4)">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ accion === 'observar' ? 'Observar' : 'Rechazar' }} solicitud</h5>
          <button class="btn-close" @click="showAccionModal = false"></button>
        </div>
        <div class="modal-body">
          <p class="small text-muted">Equipo: <strong>{{ solicitudActual?.nombre_equipo }}</strong></p>
          <label class="form-label">{{ accion === 'observar' ? 'Observación para el delegado' : 'Motivo de rechazo' }}</label>
          <textarea v-model="textoAccion" class="form-control" rows="3" placeholder="Escribí el mensaje..."></textarea>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="showAccionModal = false">Cancelar</button>
          <button
            class="btn"
            :class="accion === 'observar' ? 'btn-warning' : 'btn-danger'"
            @click="confirmarAccion"
            :disabled="!textoAccion.trim() || saving"
          >
            <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
            {{ accion === 'observar' ? 'Observar' : 'Rechazar' }}
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal: aprobar -->
  <div v-if="showAprobarModal" class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,0.4)">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Aprobar solicitud</h5>
          <button class="btn-close" @click="showAprobarModal = false"></button>
        </div>
        <div class="modal-body">
          <p class="small text-muted">Se creará el equipo <strong>{{ solicitudActual?.nombre_equipo }}</strong> y sus jugadores.</p>
          <label class="form-label">Disciplina del equipo</label>
          <select v-model="idDisciplina" class="form-select">
            <option v-for="d in disciplinas" :key="d.id" :value="d.id">{{ d.nombre }}</option>
          </select>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="showAprobarModal = false">Cancelar</button>
          <button class="btn btn-success" @click="confirmarAprobar" :disabled="!idDisciplina || saving">
            <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
            Aprobar y crear equipo
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import inscripcionesService from '@/services/torneos/inscripcionesService'
import datosMaestrosService from '@/services/datosMaestrosService'
import { useToastStore } from '@/stores/toastStore'

const props = defineProps({
  idTorneo: { type: Number, required: true },
  idDisciplinaDefault: { type: Number, default: null },
})

const emit = defineEmits(['aprobada'])

const toast = useToastStore()

const solicitudes = ref([])
const loading = ref(false)
const filtroEstado = ref(null)
const solicitudActual = ref(null)
const jugadores = ref([])
const loadingJugadores = ref(false)
const disciplinas = ref([])
const saving = ref(false)
const accion = ref('')
const textoAccion = ref('')
const idDisciplina = ref(null)

const savingDocId = ref(null)
const dropdownAbierto = ref(null)
const solicitudDesbloqueadaId = ref(null)

const estadosDoc = [
  { value: 'pendiente',  label: 'pendiente',  clase: 'text-secondary' },
  { value: 'aprobada',   label: 'aprobada',   clase: 'text-success'   },
  { value: 'rechazada',  label: 'rechazada',  clase: 'text-danger'    },
]

const estadoDocClass = (estado) => {
  if (estado === 'aprobada')  return 'text-success border-success'
  if (estado === 'rechazada') return 'text-danger border-danger'
  return 'text-secondary border-secondary'
}

const toggleDropdown = (id) => {
  dropdownAbierto.value = dropdownAbierto.value === id ? null : id
}

const seleccionarEstadoDoc = (jugador, nuevoEstado) => {
  dropdownAbierto.value = null
  editandoDocId.value = null
  if (jugador.estado_documentacion !== nuevoEstado) {
    cambiarEstadoDoc(jugador, nuevoEstado)
  }
}

const showDetalleModal = ref(false)
const showAccionModal = ref(false)
const showAprobarModal = ref(false)

const solicitudesFiltradas = computed(() => {
  if (filtroEstado.value === null) return solicitudes.value
  return solicitudes.value.filter(s => Number(s.id_estado) === filtroEstado.value)
})

const cargar = async () => {
  loading.value = true
  try {
    solicitudes.value = await inscripcionesService.getByTorneo(props.idTorneo)
  } catch {
    toast.error('Error al cargar solicitudes del portal')
  } finally {
    loading.value = false
  }
}

const formatFecha = (value) => {
  if (!value) return '-'
  return new Date(String(value).replace(' ', 'T')).toLocaleDateString('es-AR')
}

const badgeEstado = (estado) => {
  const key = String(estado || '').toUpperCase()
  if (key === 'PENDIENTE') return 'bg-warning-subtle text-warning'
  if (key === 'OBSERVADA')  return 'bg-info-subtle text-info'
  if (key === 'APROBADA')   return 'bg-success-subtle text-success'
  if (key === 'RECHAZADA')  return 'bg-danger-subtle text-danger'
  return 'bg-secondary-subtle text-secondary'
}

const resolveArchivoUrl = (ruta) => {
  if (!ruta) return ''
  if (/^https?:\/\//i.test(ruta)) return ruta
  const apiBase = import.meta.env.VITE_API_URL || ''
  try {
    const apiUrl = new URL(apiBase, window.location.origin)
    const apiPath = String(apiUrl.pathname || '').replace(/\/+$/, '')
    const projectBase = apiPath.endsWith('/api') ? apiPath.slice(0, -4) : ''
    return `${apiUrl.origin}${projectBase}/${ruta}`
  } catch {
    return ruta
  }
}

const cambiarEstadoDoc = async (jugador, nuevoEstado) => {
  savingDocId.value = jugador.id
  try {
    await inscripcionesService.setEstadoDocumentacion(jugador.id, nuevoEstado)
    jugador.estado_documentacion = nuevoEstado
    toast.success(`Documento ${nuevoEstado === 'aprobada' ? 'aprobado' : 'rechazado'}.`)
  } catch {
    toast.error('Error al actualizar el estado del documento')
  } finally {
    savingDocId.value = null
  }
}

const verDetalle = async (sol) => {
  solicitudActual.value = sol
  showDetalleModal.value = true
  loadingJugadores.value = true
  try {
    jugadores.value = await inscripcionesService.getJugadores(sol.id)
  } catch {
    toast.error('Error al cargar jugadores')
  } finally {
    loadingJugadores.value = false
  }
}

const abrirAccion = (sol, tipo) => {
  solicitudActual.value = sol
  accion.value = tipo
  textoAccion.value = ''
  showAccionModal.value = true
}

const confirmarAccion = async () => {
  if (!textoAccion.value.trim()) return
  saving.value = true
  try {
    if (accion.value === 'observar') {
      await inscripcionesService.observar(solicitudActual.value.id, textoAccion.value)
      toast.success('Solicitud marcada como observada')
    } else {
      await inscripcionesService.rechazar(solicitudActual.value.id, textoAccion.value)
      toast.success('Solicitud rechazada')
    }
    showAccionModal.value = false
    solicitudDesbloqueadaId.value = null
    await cargar()
  } catch {
    toast.error('Error al procesar la acción')
  } finally {
    saving.value = false
  }
}

const abrirAprobar = (sol) => {
  solicitudActual.value = sol
  idDisciplina.value = props.idDisciplinaDefault
  showAprobarModal.value = true
}

const confirmarAprobar = async () => {
  if (!idDisciplina.value) return
  saving.value = true
  try {
    await inscripcionesService.aprobar(solicitudActual.value.id, idDisciplina.value)
    toast.success('Solicitud aprobada. Equipo y jugadores creados.')
    showAprobarModal.value = false
    solicitudDesbloqueadaId.value = null
    emit('aprobada')
    await cargar()
  } catch (e) {
    toast.error(e?.response?.data?.message || 'Error al aprobar la solicitud')
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  await cargar()
  datosMaestrosService.getDisciplinas().then(d => { disciplinas.value = d }).catch(() => {})
})
</script>

<style scoped>
.estado-doc-dropdown {
  position: relative;
  display: inline-block;
}

.estado-doc-btn {
  background: transparent;
  border: 1px solid;
  border-radius: 4px;
  padding: 0.2rem 0.5rem;
  font-size: 0.875rem;
  cursor: pointer;
  white-space: nowrap;
}

.estado-doc-menu {
  position: absolute;
  z-index: 1050;
  top: calc(100% + 2px);
  left: 0;
  background: #fff;
  border: 1px solid #dee2e6;
  border-radius: 4px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  list-style: none;
  margin: 0;
  padding: 0.25rem 0;
  min-width: 120px;
}

.estado-doc-item {
  padding: 0.35rem 0.75rem;
  font-size: 0.875rem;
  cursor: pointer;
  font-weight: 500;
}

.estado-doc-item:hover {
  background: #f8f9fa;
}

.estado-doc-item.active {
  background: #f0f0f0;
}
</style>
