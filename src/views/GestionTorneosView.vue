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

        <div v-else-if="torneos.length === 0" class="alert alert-warning mb-0">
          No hay torneos disponibles.
        </div>

        <div v-else class="torneo-grid">
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
              <span class="badge rounded-pill text-bg-light">#{{ torneo.id }}</span>
            </div>
            <div class="small text-muted text-start mt-2">
              {{ torneo.estado_torneo_descripcion || 'Sin estado' }}
            </div>
            <div
              v-if="loadingDetalle && Number(idTorneoSeleccionado) === Number(torneo.id)"
              class="small mt-1 text-primary text-start"
            >
              Cargando...
            </div>
          </button>
        </div>
      </div>
    </div>

    <div v-if="idTorneoSeleccionado && !detalle && !loadingDetalle" class="alert alert-info">
      Selecciona un torneo del mosaico para ver su información.
    </div>

    <div v-if="detalle" class="card shadow-sm border-0 rounded-lg">
      <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h2 class="h6 fw-bold text-secondary mb-0">Gestión del torneo: {{ detalle.torneo.nombre }}</h2>
          <div class="d-flex align-items-center gap-2">
            <button class="btn btn-outline-secondary" @click="cargarDetalle()" :disabled="loadingDetalle">Recargar</button>

            <div class="position-relative">
              <button class="btn btn-outline-secondary px-2" @click="showAccionesMenu = !showAccionesMenu" title="Acciones torneo">
                <i class="bi bi-three-dots-vertical"></i>
              </button>

              <div v-if="showAccionesMenu" class="acciones-menu shadow-sm">
                <button class="acciones-item acciones-item-danger" @click="abrirEliminarTorneoModal">
                  Eliminar torneo
                </button>
              </div>
            </div>
          </div>
        </div>

        <ul class="nav torneo-tabs mb-4">
          <li class="nav-item">
            <button class="torneo-tab" :class="{ active: tabActiva === 'resumen' }" @click="tabActiva = 'resumen'">
              <i class="bi bi-grid me-1"></i>Resumen
            </button>
          </li>
          <li class="nav-item">
            <button class="torneo-tab" :class="{ active: tabActiva === 'inscripciones' }" @click="tabActiva = 'inscripciones'">
              <i class="bi bi-person-plus me-1"></i>Inscripciones
              <span class="badge ms-1" :class="tabActiva === 'inscripciones' ? 'bg-primary text-white' : 'bg-secondary-subtle text-secondary'">
                {{ detalle.inscripciones?.total || 0 }}
              </span>
            </button>
          </li>
          <li class="nav-item">
            <button class="torneo-tab" :class="{ active: tabActiva === 'asignaciones' }" @click="tabActiva = 'asignaciones'">
              <i class="bi bi-diagram-3 me-1"></i>Asignaciones
              <span class="badge ms-1" :class="tabActiva === 'asignaciones' ? 'bg-info text-white' : 'bg-info-subtle text-info'">
                {{ detalle.inscripciones?.asignadas || 0 }}
              </span>
            </button>
          </li>
        </ul>

        <template v-if="tabActiva === 'resumen'">
          <div class="row g-3">
            <div class="col-12 col-lg-6">
              <div class="resumen-box h-100">
                <p class="mb-1"><strong>{{ detalle.torneo.nombre }}</strong></p>
                <p class="mb-1 text-muted">Disciplina: {{ detalle.torneo.disciplina_nombre || '-' }}</p>
                <p class="mb-1 text-muted">Estado: {{ detalle.torneo.estado_nombre || '-' }}</p>
                <p class="mb-0 text-muted">Formato: {{ detalle.torneo.formato_manual || '-' }}</p>
              </div>
            </div>
            <div class="col-12 col-lg-6">
              <div class="resumen-box h-100">
                <p class="mb-1 text-muted">Eventos: {{ detalle.eventos.total }} (zonas: {{ detalle.eventos.zona }}, eliminación: {{ detalle.eventos.eliminacion }})</p>
                <p class="mb-1 text-muted">Inscripciones: {{ detalle.inscripciones?.total || 0 }}</p>
                <p class="mb-1 text-muted">Pagas: {{ detalle.inscripciones?.pagas || 0 }}</p>
                <p class="mb-0 text-muted">Equipos asignados: {{ detalle.inscripciones?.asignadas || 0 }}</p>
              </div>
            </div>
          </div>
        </template>

        <template v-if="tabActiva === 'inscripciones'">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <p class="mb-1 text-muted">Listado de equipos inscriptos y estado administrativo.</p>
              <div class="d-flex align-items-center gap-2 small">
                <span class="text-secondary">Inscriptos:</span>
                <span class="badge rounded-pill" :class="inscripcionesCompletas ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning'">
                  {{ detalle.inscripciones?.total || 0 }}/{{ totalInscriptosObjetivo }}
                </span>
              </div>
            </div>
            <button class="btn btn-outline-primary" @click="abrirInscripcionesModal">Agregar nueva inscripción</button>
          </div>

          <div v-if="!detalle.inscriptos?.length" class="alert alert-info mb-0">
            Este torneo aún no tiene equipos inscriptos.
          </div>

          <div v-else class="table-responsive">
            <table class="table table-sm align-middle mb-0">
              <thead>
                <tr>
                  <th>Equipo</th>
                  <th>Estado</th>
                  <th>Fecha pago</th>
                  <th>Comprobante</th>
                  <th>Asignado</th>
                  <th class="text-end">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in detalle.inscriptos" :key="item.id">
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <img v-if="item.escudo" :src="item.escudo" alt="escudo" class="escudo-thumb" />
                      <span>{{ item.equipo_nombre }}</span>
                    </div>
                  </td>
                  <td>{{ item.estado_inscripcion || '-' }}</td>
                  <td>{{ item.fecha_pago || '-' }}</td>
                  <td>
                    <a v-if="item.comprobante_pago" :href="item.comprobante_pago" target="_blank" rel="noopener noreferrer">Ver</a>
                    <span v-else>-</span>
                  </td>
                  <td>
                    <span class="badge rounded-pill" :class="Number(item.asignado) ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary'">
                      {{ Number(item.asignado) ? 'Sí' : 'No' }}
                    </span>
                  </td>
                  <td class="text-end">
                    <div class="d-inline-flex gap-2">
                      <button class="btn btn-sm btn-outline-secondary" @click="editarInscripcion(item)">Editar</button>
                      <button class="btn btn-sm btn-outline-danger" @click="eliminarInscripcion(item)">Eliminar</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>

        <template v-if="tabActiva === 'asignaciones'">
          <div v-if="!detalle.grupos?.length" class="alert alert-warning mb-0">
            Este torneo no tiene grupos configurados (fase de zonas).
          </div>

          <template v-else>
            <div class="table-responsive mb-3">
              <table class="table table-sm align-middle mb-0">
                <thead>
                  <tr>
                    <th>Grupo</th>
                    <th class="text-center">Asignados</th>
                    <th class="text-center">Cupo</th>
                    <th class="text-center">Estado</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="grupo in detalle.grupos" :key="grupo.id">
                    <td>{{ grupo.nombre }}</td>
                    <td class="text-center">{{ grupo.asignados }}</td>
                    <td class="text-center">{{ grupo.cantidad_equipos_objetivo }}</td>
                    <td class="text-center">
                      <span class="badge rounded-pill" :class="Number(grupo.asignados) >= Number(grupo.cantidad_equipos_objetivo) ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning'">
                        {{ Number(grupo.asignados) >= Number(grupo.cantidad_equipos_objetivo) ? 'Completo' : 'Pendiente' }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="pool-box mb-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <strong>Equipos para sorteo</strong>
                <small class="text-muted">{{ selectedPool.length }}/{{ totalSlots }} seleccionados</small>
              </div>
              <div class="row g-2">
                <div v-for="equipo in poolTeams" :key="equipo.id" class="col-12 col-md-6">
                  <label class="pool-item">
                    <input type="checkbox" :value="Number(equipo.id)" v-model="selectedPool" />
                    <img v-if="equipo.escudo" :src="equipo.escudo" alt="escudo" class="escudo-thumb" />
                    <span class="me-auto">{{ equipo.nombre }}</span>
                    <span v-if="equipo.pagada" class="badge rounded-pill bg-success-subtle text-success">Paga</span>
                    <span v-else class="badge rounded-pill bg-warning-subtle text-warning">Pendiente</span>
                    <span v-if="equipo.asignado" class="badge rounded-pill bg-info-subtle text-info">Asignado</span>
                  </label>
                </div>
              </div>
            </div>

            <div class="row g-3">
              <div v-for="grupo in detalle.grupos" :key="grupo.id" class="col-12 col-md-6">
                <div class="group-card h-100">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <h3 class="h6 mb-0">{{ grupo.nombre }}</h3>
                    <small class="text-muted">{{ grupo.asignados }}/{{ grupo.cantidad_equipos_objetivo }}</small>
                  </div>

                  <div v-for="pos in Number(grupo.cantidad_equipos_objetivo || 0)" :key="`${grupo.id}-${pos}`" class="mb-2">
                    <label class="form-label small mb-1">Posición {{ pos }}</label>
                    <select class="form-select form-select-sm" :value="getSelected(grupo.id, pos)" @change="setSelected(grupo.id, pos, $event.target.value)">
                      <option value="">Sin asignar</option>
                      <option v-for="equipo in getAvailableOptions(grupo.id, pos)" :key="equipo.id" :value="equipo.id">
                        {{ equipo.nombre }}
                      </option>
                    </select>
                    <div v-if="getSelectedTeam(grupo.id, pos)?.escudo" class="mt-1 small text-muted d-flex align-items-center gap-2">
                      <img :src="getSelectedTeam(grupo.id, pos).escudo" alt="escudo" class="escudo-thumb" />
                      Escudo cargado
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="d-flex justify-content-between mt-3">
              <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary" @click="limpiarAsignacion" :disabled="savingAsignacion">Limpiar</button>
                <button class="btn btn-outline-primary" @click="asignarAleatorio" :disabled="savingAsignacion">Asignar aleatorio</button>
              </div>
              <button class="btn btn-success" @click="guardarAsignaciones" :disabled="savingAsignacion">
                <span v-if="savingAsignacion" class="spinner-border spinner-border-sm me-2"></span>
                Guardar asignación
              </button>
            </div>
          </template>
        </template>
      </div>
    </div>

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

    <Teleport to="body">
      <div v-if="showInscripcionModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
        <div class="modal-dialog modal-dialog-centered modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Cargar inscripciones al torneo</h5>
              <button type="button" class="btn-close" @click="showInscripcionModal = false"></button>
            </div>

            <div class="modal-body">
              <p class="small text-muted mb-3">Selecciona un equipo y carga sus datos de inscripción.</p>

              <div v-if="!equiposParaInscripcion.length" class="alert alert-info mb-0">
                No hay equipos disponibles para gestionar inscripciones.
              </div>

              <div v-else class="row g-3">
                <div class="col-12">
                  <label class="form-label">Equipo</label>
                  <select class="form-select" v-model="inscripcionForm.id_equipo" @change="onEquipoInscripcionChange">
                    <option value="">Seleccionar equipo</option>
                    <option v-for="equipo in equiposParaInscripcion" :key="equipo.id" :value="String(equipo.id)">
                      {{ equipo.nombre }}{{ equipo.ya_inscripto ? ' (ya inscripto)' : '' }}
                    </option>
                  </select>
                </div>

                <div class="col-12 col-md-4">
                  <label class="form-label">Estado inscripción</label>
                  <select class="form-select" v-model="inscripcionForm.id_estado_inscripcion" :disabled="!inscripcionForm.id_equipo">
                    <option value="">Seleccionar estado</option>
                    <option v-for="estado in estadosInscripcionOptions" :key="estado.id" :value="String(estado.id)">
                      {{ estado.descripcion }}
                    </option>
                  </select>
                </div>

                <div class="col-12 col-md-3">
                  <label class="form-label">Fecha pago</label>
                  <input type="date" class="form-control" v-model="inscripcionForm.fecha_pago" :disabled="!inscripcionForm.id_equipo" />
                </div>

                <div class="col-12 col-md-5">
                  <label class="form-label">Comprobante pago</label>
                  <input
                    type="file"
                    class="form-control"
                    accept=".pdf,image/*"
                    @change="onComprobanteFileChange"
                    :disabled="!inscripcionForm.id_equipo"
                  />
                  <div v-if="inscripcionForm.comprobante_pago" class="form-text">
                    Comprobante actual:
                    <a :href="inscripcionForm.comprobante_pago" target="_blank" rel="noopener noreferrer">ver archivo</a>
                  </div>
                </div>

                <div class="col-12">
                  <label class="form-label">Observación</label>
                  <input type="text" class="form-control" placeholder="Dato adicional" v-model="inscripcionForm.observacion" :disabled="!inscripcionForm.id_equipo" />
                </div>
              </div>
            </div>

            <div class="modal-footer d-flex justify-content-between">
              <small class="text-muted">La carga se registra de a un equipo por vez.</small>
              <button class="btn btn-success" @click="guardarInscripciones" :disabled="savingInscripciones || !inscripcionForm.id_equipo">
                <span v-if="savingInscripciones" class="spinner-border spinner-border-sm me-2"></span>
                Guardar inscripción
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
import datosMaestrosService from '@/services/datosMaestrosService'
import planTorneoService from '@/services/planTorneoService'
import { useToastStore } from '@/stores/toastStore'

const toast = useToastStore()

const torneos = ref([])
const idTorneoSeleccionado = ref(null)
const detalle = ref(null)
const equiposDisponibles = ref([])
const loadingTorneos = ref(false)
const loadingDetalle = ref(false)
const savingAsignacion = ref(false)
const savingInscripciones = ref(false)
const savingEliminarTorneo = ref(false)
const selected = ref({})
const selectedPool = ref([])
const showInscripcionModal = ref(false)
const showEliminarTorneoModal = ref(false)
const showAccionesMenu = ref(false)
const confirmNombreEliminar = ref('')
const motivoBajaTorneo = ref('')
const inscripcionForm = ref({
  id_equipo: '',
  id_estado_inscripcion: '',
  fecha_pago: '',
  comprobante_pago: '',
  comprobante_file: null,
  observacion: '',
})
const tabActiva = ref('resumen')

const getApiMessage = (error, fallback) => error?.response?.data?.message || fallback

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

const keyOf = (idGrupo, pos) => `${idGrupo}-${pos}`

const cargarDetalle = async (idTorneo = null) => {
  if (idTorneo !== null) {
    idTorneoSeleccionado.value = Number(idTorneo)
    tabActiva.value = 'resumen'
  }

  if (!idTorneoSeleccionado.value) return
  loadingDetalle.value = true
  try {
    const [detalleData, disponibles] = await Promise.all([
      planTorneoService.getDetalleGestion(idTorneoSeleccionado.value),
      planTorneoService.getEquiposDisponibles(idTorneoSeleccionado.value),
    ])

    detalle.value = detalleData
    equiposDisponibles.value = disponibles

    const next = {}
    for (const item of (detalleData.asignaciones || [])) {
      next[keyOf(item.id_grupo_torneo, item.posicion_inicial)] = Number(item.id_equipo)
    }
    selected.value = next
    selectedPool.value = (detalleData.inscriptos || []).map(i => Number(i.id_equipo))
  } catch (error) {
    detalle.value = null
    toast.showToast({ message: getApiMessage(error, 'No se pudo cargar el detalle del torneo.'), type: 'danger' })
  } finally {
    loadingDetalle.value = false
  }
}

const allTeamsMap = computed(() => {
  const map = {}
  for (const item of (detalle.value?.inscriptos || [])) {
    const id = Number(item.id_equipo)
    const estado = String(item.estado_inscripcion || '').toUpperCase()
    map[id] = {
      id,
      nombre: item.equipo_nombre,
      escudo: item.escudo || null,
      pagada: Boolean(item.fecha_pago) || estado === 'INSCRIPTA',
      asignado: Number(item.asignado || 0) === 1,
    }
  }
  return map
})

const poolTeams = computed(() =>
  Object.values(allTeamsMap.value)
    .map(item => ({ ...item, id: Number(item.id) }))
    .sort((a, b) => String(a.nombre).localeCompare(String(b.nombre), 'es'))
)

const equiposDisponiblesSorted = computed(() =>
  [...equiposDisponibles.value].sort((a, b) => String(a.nombre).localeCompare(String(b.nombre), 'es'))
)

const equiposParaInscripcion = computed(() => {
  const map = {}
  for (const equipo of equiposDisponiblesSorted.value) {
    map[Number(equipo.id)] = { ...equipo, ya_inscripto: false }
  }

  for (const item of (detalle.value?.inscriptos || [])) {
    const id = Number(item.id_equipo)
    map[id] = {
      id,
      nombre: item.equipo_nombre,
      escudo: item.escudo || null,
      ya_inscripto: true,
    }
  }

  return Object.values(map).sort((a, b) => String(a.nombre).localeCompare(String(b.nombre), 'es'))
})

const estadosInscripcionOptions = computed(() =>
  (detalle.value?.estados_inscripcion || []).map(e => ({ id: Number(e.id), descripcion: e.descripcion }))
)

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

const nombreEliminarCoincide = computed(() => {
  const actual = String(detalle.value?.torneo?.nombre || '').trim()
  const confirm = String(confirmNombreEliminar.value || '').trim()
  return actual !== '' && actual === confirm
})

const totalSlots = computed(() => {
  let total = 0
  for (const g of (detalle.value?.grupos || [])) {
    total += Number(g.cantidad_equipos_objetivo || 0)
  }
  return total
})

const selectedIds = computed(() => {
  const ids = []
  for (const value of Object.values(selected.value)) {
    const id = Number(value)
    if (id > 0) ids.push(id)
  }
  return ids
})

const getSelected = (idGrupo, pos) => selected.value[keyOf(idGrupo, pos)] ?? ''

const setSelected = (idGrupo, pos, value) => {
  const key = keyOf(idGrupo, pos)
  if (!value) {
    delete selected.value[key]
    return
  }
  selected.value[key] = Number(value)
}

const getSelectedTeam = (idGrupo, pos) => {
  const id = Number(getSelected(idGrupo, pos))
  return allTeamsMap.value[id] || null
}

const getAvailableOptions = (idGrupo, pos) => {
  const currentId = Number(getSelected(idGrupo, pos) || 0)
  const selectedSet = new Set(selectedIds.value.filter(id => id !== currentId))
  return Object.values(allTeamsMap.value)
    .filter(e => !selectedSet.has(Number(e.id)) || Number(e.id) === currentId)
    .sort((a, b) => String(a.nombre).localeCompare(String(b.nombre), 'es'))
}

const limpiarAsignacion = () => {
  selected.value = {}
}

const asignarAleatorio = () => {
  const grupos = detalle.value?.grupos || []
  if (!grupos.length) return

  const needed = totalSlots.value
  const pool = Array.from(new Set(selectedPool.value.map(Number).filter(v => v > 0)))

  if (pool.length < needed) {
    toast.showToast({
      message: `Debes seleccionar al menos ${needed} equipos para sortear.`,
      type: 'warning',
    })
    return
  }

  const shuffled = [...pool]
  for (let i = shuffled.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1))
    ;[shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]]
  }

  const next = {}
  let cursor = 0
  for (const grupo of grupos) {
    const cupo = Number(grupo.cantidad_equipos_objetivo || 0)
    for (let pos = 1; pos <= cupo; pos++) {
      next[keyOf(grupo.id, pos)] = Number(shuffled[cursor])
      cursor++
    }
  }

  selected.value = next
  toast.showToast({ message: 'Asignación aleatoria generada. Puedes editarla manualmente.', type: 'info' })
}

const guardarAsignaciones = async () => {
  if (!detalle.value?.grupos?.length) return

  const asignaciones = []
  for (const grupo of detalle.value.grupos) {
    const cupo = Number(grupo.cantidad_equipos_objetivo || 0)
    for (let pos = 1; pos <= cupo; pos++) {
      const idEquipo = Number(getSelected(grupo.id, pos) || 0)
      if (idEquipo > 0) {
        asignaciones.push({
          id_grupo_torneo: Number(grupo.id),
          id_equipo: idEquipo,
          posicion_inicial: pos,
        })
      }
    }
  }

  if (!asignaciones.length) {
    toast.showToast({ message: 'No hay equipos seleccionados para asignar.', type: 'warning' })
    return
  }

  savingAsignacion.value = true
  try {
    await planTorneoService.asignarEquipos({
      id_torneo: idTorneoSeleccionado.value,
      asignaciones,
    })
    toast.showToast({ message: 'Asignación guardada correctamente.', type: 'success' })
    await cargarDetalle()
  } catch (error) {
    toast.showToast({ message: getApiMessage(error, 'No se pudo guardar la asignación.'), type: 'danger' })
  } finally {
    savingAsignacion.value = false
  }
}

const abrirInscripcionesModal = () => {
  if (!idTorneoSeleccionado.value) {
    toast.showToast({ message: 'Selecciona un torneo antes de cargar inscripciones.', type: 'warning' })
    return
  }
  resetInscripcionForm()
  showInscripcionModal.value = true
}

const abrirEliminarTorneoModal = () => {
  showAccionesMenu.value = false
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
    tabActiva.value = 'resumen'
    await cargarTorneos()
  } catch (error) {
    toast.showToast({ message: getApiMessage(error, 'No se pudo eliminar el torneo.'), type: 'danger' })
  } finally {
    savingEliminarTorneo.value = false
  }
}

const resetInscripcionForm = () => {
  inscripcionForm.value = {
    id_equipo: '',
    id_estado_inscripcion: '',
    fecha_pago: '',
    comprobante_pago: '',
    comprobante_file: null,
    observacion: '',
  }
}

const onEquipoInscripcionChange = () => {
  const idEquipo = Number(inscripcionForm.value.id_equipo || 0)
  if (!idEquipo) {
    resetInscripcionForm()
    return
  }

  const inscripto = (detalle.value?.inscriptos || []).find(i => Number(i.id_equipo) === idEquipo)
  inscripcionForm.value = {
    id_equipo: String(idEquipo),
    id_estado_inscripcion: inscripto?.id_estado_inscripcion ? String(inscripto.id_estado_inscripcion) : '',
    fecha_pago: inscripto?.fecha_pago || '',
    comprobante_pago: inscripto?.comprobante_pago || '',
    comprobante_file: null,
    observacion: inscripto?.observacion || '',
  }
}

const onComprobanteFileChange = (event) => {
  const file = event?.target?.files?.[0] || null
  inscripcionForm.value.comprobante_file = file
}

const editarInscripcion = (item) => {
  if (!item?.id_equipo) return
  inscripcionForm.value = {
    id_equipo: String(item.id_equipo),
    id_estado_inscripcion: item?.id_estado_inscripcion ? String(item.id_estado_inscripcion) : '',
    fecha_pago: item?.fecha_pago || '',
    comprobante_pago: item?.comprobante_pago || '',
    comprobante_file: null,
    observacion: item?.observacion || '',
  }
  showInscripcionModal.value = true
}

const eliminarInscripcion = async (item) => {
  const nombre = item?.equipo_nombre || 'este equipo'
  const ok = window.confirm(`¿Eliminar la inscripción de ${nombre}?`)
  if (!ok) return

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

const guardarInscripciones = async () => {
  const idEquipo = Number(inscripcionForm.value.id_equipo || 0)
  if (!idEquipo) {
    toast.showToast({ message: 'Selecciona un equipo para inscribir.', type: 'warning' })
    return
  }

  let comprobantePago = inscripcionForm.value.comprobante_pago || null

  savingInscripciones.value = true
  try {
    if (inscripcionForm.value.comprobante_file) {
      const formData = new FormData()
      formData.append('comprobante', inscripcionForm.value.comprobante_file)
      formData.append('id_torneo', String(idTorneoSeleccionado.value || ''))
      const uploadResp = await planTorneoService.subirComprobante(formData)
      comprobantePago = uploadResp?.comprobante_pago || comprobantePago
    }

    const payload = [{
      id_equipo: idEquipo,
      pagada: Boolean(inscripcionForm.value.fecha_pago),
      fecha_pago: inscripcionForm.value.fecha_pago || null,
      id_estado_inscripcion: inscripcionForm.value.id_estado_inscripcion ? Number(inscripcionForm.value.id_estado_inscripcion) : null,
      comprobante_pago: comprobantePago ? String(comprobantePago).trim() : null,
      observacion: inscripcionForm.value.observacion?.trim() || null,
    }]

    await planTorneoService.inscribirEquipos({
      id_torneo: idTorneoSeleccionado.value,
      inscripciones: payload,
    })

    toast.showToast({ message: 'Inscripción guardada correctamente.', type: 'success' })
    showInscripcionModal.value = false
    resetInscripcionForm()
    await cargarDetalle()
  } catch (error) {
    toast.showToast({ message: getApiMessage(error, 'No se pudieron guardar las inscripciones.'), type: 'danger' })
  } finally {
    savingInscripciones.value = false
  }
}

onMounted(cargarTorneos)
</script>

<style scoped>
.torneo-tabs {
  border-bottom: 1px solid #e5e7eb;
  gap: 6px;
}

.torneo-tab {
  border: 0;
  background: transparent;
  padding: 8px 12px;
  border-radius: 10px 10px 0 0;
  color: #64748b;
  font-weight: 600;
}

.torneo-tab.active {
  color: #0f172a;
  background: #f1f5f9;
}

.resumen-box {
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 12px;
  background: #f8fafc;
}

.acciones-menu {
  position: absolute;
  right: 0;
  top: calc(100% + 4px);
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  min-width: 180px;
  z-index: 20;
  padding: 4px;
}

.acciones-item {
  width: 100%;
  border: 0;
  background: transparent;
  text-align: left;
  padding: 8px 10px;
  border-radius: 6px;
}

.acciones-item:hover {
  background: #f8fafc;
}

.acciones-item-danger {
  color: #b91c1c;
}

.group-card {
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 12px;
  background: #f8fafc;
}

.pool-box {
  border: 1px dashed #cbd5e1;
  border-radius: 12px;
  padding: 12px;
  background: #f8fafc;
}

.pool-item {
  display: flex;
  align-items: center;
  gap: 8px;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 6px 8px;
  background: #fff;
  font-size: 0.9rem;
}

.inscripcion-row {
  display: block;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 8px 10px;
  background: #fff;
}

.escudo-thumb {
  width: 24px;
  height: 24px;
  object-fit: cover;
  border-radius: 50%;
  border: 1px solid #cbd5e1;
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
</style>
