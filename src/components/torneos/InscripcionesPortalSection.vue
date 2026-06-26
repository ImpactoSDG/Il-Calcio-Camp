<template>
  <div>

    <!-- Solicitudes del portal web -->
    <div class="mb-3">
      <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
          <h3 class="h6 fw-bold text-secondary mb-1">Solicitudes del portal web</h3>
          <p class="small text-muted mb-0">Inscripciones enviadas por delegados. Revisá y gestioná cada una.</p>
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
    <div v-else-if="gruposSolicitudes.length === 0" class="alert alert-light border mb-0 small">
      No hay solicitudes del portal para este torneo<span v-if="filtroEstado"> con ese estado</span>.
    </div>

    <!-- Tabla con grupos -->
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
          <template v-for="grupo in gruposSolicitudes" :key="grupo.idEstado">
            <tr class="table-secondary">
              <td colspan="5" class="py-1 px-2">
                <span class="badge rounded-pill me-1" :class="badgeEstado(grupo.label)">{{ grupo.label }}</span>
                <span class="small text-muted">{{ grupo.solicitudes.length }} solicitud{{ grupo.solicitudes.length !== 1 ? 'es' : '' }}</span>
              </td>
            </tr>
            <tr v-for="sol in grupo.solicitudes" :key="sol.id">
              <td class="fw-semibold">{{ sol.nombre_equipo }}</td>
              <td class="small text-muted">{{ sol.email_solicitante }}</td>
              <td>
                <span class="badge rounded-pill" :class="badgeEstado(sol.estado)">{{ sol.estado }}</span>
                <span
                  v-if="Number(sol.id_estado) === 7 && sol.tiene_docs_nuevas"
                  class="badge bg-warning text-dark rounded-pill ms-1"
                  title="El delegado actualizó documentación desde la última revisión"
                >Documentación a revisar</span>
              </td>
              <td class="small text-muted">{{ formatFecha(sol.fecha_creacion) }}</td>
              <td class="text-end">
                <button class="btn btn-sm btn-outline-secondary" @click="abrirVisualizar(sol)">
                  <i class="bi bi-eye me-1"></i>Visualizar
                </button>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>

    <!-- Equipos inscriptos al torneo -->
    <div v-if="!loading && equiposInscriptos.length > 0" class="mt-4 pt-3 border-top">
      <div class="mb-2">
        <h3 class="h6 fw-bold text-secondary mb-1">
          <i class="bi bi-shield-check me-1 text-success"></i>Equipos inscriptos al torneo
        </h3>
        <p class="small text-muted mb-0">Solicitudes aprobadas. Estos equipos ya están participando del torneo.</p>
      </div>
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0 bg-success-subtle">
          <thead class="table-success">
            <tr>
              <th>Equipo</th>
              <th>Categoría</th>
              <th>Delegado</th>
              <th>Fecha de inscripción</th>
              <th class="text-end">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="sol in equiposInscriptos" :key="sol.id">
              <td class="fw-semibold">{{ sol.nombre_equipo }}</td>
              <td class="small text-muted">{{ sol.categoria || '-' }}</td>
              <td class="small text-muted">{{ sol.email_solicitante }}</td>
              <td class="small text-muted">{{ formatFecha(sol.fecha_actualizacion_estado || sol.fecha_creacion) }}</td>
              <td class="text-end">
                <button class="btn btn-sm btn-outline-secondary" @click="abrirVisualizar(sol)">
                  <i class="bi bi-eye me-1"></i>Ver solicitud
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  </div>

  <!-- ============================================================
       MODAL PRINCIPAL: Visualizar solicitud
       ============================================================ -->
  <Teleport to="body">
    <div v-if="showVisualizarModal" class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,0.5)">
      <div class="modal-dialog modal-xxl modal-dialog-scrollable">
        <div class="modal-content">

          <div class="modal-header">
            <h5 class="modal-title">
              Solicitud: <strong>{{ solicitudActual?.nombre_equipo }}</strong>
            </h5>
            <button class="btn-close" @click="showVisualizarModal = false"></button>
          </div>

          <div class="modal-body">

            <!-- Sección 1: Datos del equipo -->
            <div class="mb-4">
              <h6 class="fw-bold text-secondary mb-2">Datos de la inscripción</h6>
              <div class="row g-2 small">
                <div class="col-sm-6 col-lg-3">
                  <span class="text-muted">Equipo:</span><br>
                  <strong>{{ solicitudActual?.nombre_equipo }}</strong>
                </div>
                <div v-if="solicitudActual?.categoria" class="col-sm-6 col-lg-3">
                  <span class="text-muted">Categoría:</span><br>
                  {{ solicitudActual.categoria }}
                </div>
                <div class="col-sm-6 col-lg-3">
                  <span class="text-muted">Estado:</span><br>
                  <span class="badge rounded-pill" :class="badgeEstado(solicitudActual?.estado)">
                    {{ solicitudActual?.estado }}
                  </span>
                </div>
                <div class="col-sm-6 col-lg-3">
                  <span class="text-muted">Fecha de solicitud:</span><br>
                  {{ formatFecha(solicitudActual?.fecha_creacion) }}
                </div>
                <div v-if="capitan" class="col-sm-6 col-lg-3">
                  <span class="text-muted">Capitán / Delegado:</span><br>
                  {{ capitan.apellido }}, {{ capitan.nombre }}
                </div>
                <div v-if="capitan?.email" class="col-sm-6 col-lg-3">
                  <span class="text-muted">Email capitán:</span><br>
                  {{ capitan.email }}
                </div>
                <div v-if="capitan?.telefono" class="col-sm-6 col-lg-3">
                  <span class="text-muted">Teléfono capitán:</span><br>
                  {{ capitan.telefono }}
                </div>
                <div v-if="solicitudActual?.email_solicitante" class="col-sm-6 col-lg-3">
                  <span class="text-muted">Email cuenta web:</span><br>
                  {{ solicitudActual.email_solicitante }}
                </div>
              </div>

              <!-- Mensaje previo enviado al delegado -->
              <div v-if="solicitudActual?.observacion_admin" class="mt-3">
                <button
                  class="btn btn-sm btn-warning d-flex align-items-center gap-1"
                  @click="mostrarMensajeAdmin = !mostrarMensajeAdmin"
                >
                  <i class="bi" :class="mostrarMensajeAdmin ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                  Ver último mensaje enviado al delegado
                </button>
                <div v-if="mostrarMensajeAdmin" class="alert alert-warning small mt-2 mb-0">
                  <pre class="mb-0" style="white-space:pre-wrap;font-size:0.8rem">{{ solicitudActual.observacion_admin }}</pre>
                </div>
              </div>
            </div>

            <!-- Sección 2: Jugadores -->
            <div class="mb-4">
              <h6 class="fw-bold text-secondary mb-2">Jugadores</h6>

              <div v-if="loadingJugadores" class="text-center py-3 text-muted">
                <span class="spinner-border spinner-border-sm me-2"></span>Cargando jugadores...
              </div>

              <div v-else style="max-height:350px;overflow-y:auto">
                <table class="table table-sm align-middle mb-0">
                  <thead class="table-light sticky-top">
                    <tr>
                      <th>Apellido y nombre</th>
                      <th>Rol</th>
                      <th>DNI</th>
                      <th>Edad</th>
                      <th>Email</th>
                      <th>Teléfono</th>
                      <th>Ficha médica</th>
                      <th>Revisión</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="j in jugadores" :key="j.id" :class="{ 'table-warning': jugadorActualizado(j) }">
                      <td>
                        {{ j.apellido }}, {{ j.nombre }}
                        <span
                          v-if="jugadorActualizado(j)"
                          class="badge bg-warning text-dark ms-1"
                          title="Actualizó documentación desde la última revisión"
                        >Actualizado</span>
                      </td>
                      <td class="small">
                        <span v-if="Number(j.es_capitan) === 1" class="badge bg-primary-subtle text-primary">Capitán</span>
                        <span v-else-if="Number(j.es_arquero) === 1" class="badge bg-secondary-subtle text-secondary">Arquero</span>
                        <span v-else class="badge bg-light text-secondary border">Jugador</span>
                      </td>
                      <td class="small">{{ j.dni }}</td>
                      <td class="small">
                        <template v-if="calcularEdad(j.fecha_nac) !== null">
                          <div>{{ calcularEdad(j.fecha_nac) }} años</div>
                          <div class="text-muted" style="font-size:0.75rem">{{ formatFechaNac(j.fecha_nac) }}</div>
                        </template>
                        <span v-else class="text-muted">-</span>
                      </td>
                      <td class="small">{{ j.email || '-' }}</td>
                      <td class="small">{{ j.telefono || '-' }}</td>
                      <td>
                        <a
                          v-if="j.archivo_documentacion"
                          :href="fichaUrl(j.archivo_documentacion)"
                          target="_blank"
                          rel="noopener"
                          class="btn btn-sm btn-outline-secondary"
                        >
                          <i class="bi bi-file-earmark-text me-1"></i>Ver
                        </a>
                        <span v-else class="small text-muted">Sin archivo</span>
                      </td>
                      <td @click.stop>
                        <div v-if="j.archivo_documentacion" class="d-flex gap-1 align-items-center">
                          <button
                            class="btn btn-sm"
                            :class="j.estado_documentacion === 'pendiente de revision' ? 'btn-warning' : 'btn-outline-secondary'"
                            :disabled="savingDocId === j.id"
                            @click="seleccionarEstadoDoc(j, 'pendiente de revision')"
                            title="Pendiente de revisión"
                          ><i class="bi bi-clock"></i></button>
                          <button
                            class="btn btn-sm"
                            :class="j.estado_documentacion === 'aprobada' ? 'btn-success' : 'btn-outline-secondary'"
                            :disabled="savingDocId === j.id"
                            @click="seleccionarEstadoDoc(j, 'aprobada')"
                            title="Aprobada"
                          ><i class="bi bi-check-lg"></i></button>
                          <button
                            class="btn btn-sm"
                            :class="j.estado_documentacion === 'rechazada' ? 'btn-danger' : 'btn-outline-secondary'"
                            :disabled="savingDocId === j.id"
                            @click="seleccionarEstadoDoc(j, 'rechazada')"
                            title="Rechazada"
                          ><i class="bi bi-x-lg"></i></button>
                          <span v-if="savingDocId === j.id" class="spinner-border spinner-border-sm text-secondary ms-1"></span>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Sección 3: Cambio de estado -->
            <div class="border-top pt-3">

              <!-- Bloqueado: aprobada o rechazada -->
              <template v-if="[5, 8].includes(Number(solicitudActual?.id_estado)) && !desbloqueado">
                <p class="small text-muted mb-2">
                  Esta solicitud está en estado
                  <span class="badge rounded-pill" :class="badgeEstado(solicitudActual?.estado)">{{ solicitudActual?.estado }}</span>.
                </p>
                <button class="btn btn-sm btn-outline-secondary" @click="desbloqueado = true">
                  <i class="bi bi-pencil me-1"></i>Editar estado
                </button>
              </template>

              <!-- Acciones disponibles -->
              <template v-else>
                <!-- Selector de acción -->
                <div v-if="!accionSeleccionada" class="d-flex flex-column gap-3">

                  <!-- Aprobar -->
                  <div v-if="Number(solicitudActual?.id_estado) !== 8" class="border rounded p-3">
                    <div class="d-flex align-items-start gap-3">
                      <button
                        class="btn btn-sm btn-outline-success flex-shrink-0"
                        @click="intentarAprobar"
                      >
                        <i class="bi bi-check-lg me-1"></i>Aprobar
                      </button>
                      <div class="small">
                        <template v-if="puedeAprobar">
                          Aprueba la inscripción del equipo. Se creará el equipo y se darán de alta los jugadores en el torneo. Se enviará un mail de confirmación al delegado.
                        </template>
                        <span v-else class="text-danger">
                          <i class="bi bi-exclamation-circle me-1"></i>
                          No podés aprobar hasta que todos los jugadores tengan su documentación aprobada. Revisá la columna de revisión en la tabla de arriba.
                        </span>
                      </div>
                    </div>
                  </div>

                  <!-- Informar por mail -->
                  <div class="border rounded p-3">
                    <div class="d-flex align-items-start gap-3">
                      <button
                        class="btn btn-sm btn-outline-warning flex-shrink-0"
                        @click="abrirInformarPorMail"
                      >
                        <i class="bi bi-envelope me-1"></i>Informar por mail
                      </button>
                      <p class="small mb-0">
                        Enviá un mensaje al delegado para notificarle sobre el estado de la documentación. La solicitud pasará a estado <strong>Observada</strong> y el delegado podrá volver a cargar archivos.
                      </p>
                    </div>
                  </div>

                  <!-- Rechazar -->
                  <div v-if="Number(solicitudActual?.id_estado) !== 5" class="border rounded p-3">
                    <div class="d-flex align-items-start gap-3">
                      <button
                        class="btn btn-sm btn-outline-danger flex-shrink-0"
                        @click="accionSeleccionada = 'rechazar'"
                      >
                        <i class="bi bi-x-lg me-1"></i>Rechazar
                      </button>
                      <p class="small mb-0">
                        Rechaza definitivamente la inscripción del equipo. Deberás escribir un motivo que se le enviará al delegado por mail.
                      </p>
                    </div>
                  </div>

                  <div v-if="desbloqueado">
                    <button class="btn btn-sm btn-outline-secondary" @click="desbloqueado = false">
                      <i class="bi bi-x me-1"></i>Cancelar
                    </button>
                  </div>

                </div>

                <!-- Formulario: Rechazar -->
                <div v-if="accionSeleccionada === 'rechazar'" class="mt-2">
                  <label class="form-label small fw-semibold">Motivo de rechazo</label>
                  <textarea v-model="textoAccion" class="form-control form-control-sm" rows="3" placeholder="Escribí el motivo..."></textarea>
                  <div class="d-flex gap-2 mt-2">
                    <button class="btn btn-sm btn-danger" :disabled="!textoAccion.trim()" @click="continuarRechazar">
                      Continuar
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" @click="accionSeleccionada = null">Volver</button>
                  </div>
                </div>
              </template>
            </div>

          </div><!-- /modal-body -->

          <div class="modal-footer">
            <button class="btn btn-secondary" @click="showVisualizarModal = false">Cerrar</button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>

  <!-- ============================================================
       MODAL SECUNDARIO: Previsualización / edición del email
       ============================================================ -->
  <Teleport to="body">
    <div v-if="showEmailModal" class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,0.6);z-index:1060">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">

          <div class="modal-header">
            <h5 class="modal-title">
              Email que se enviará a
              <span class="text-primary">{{ solicitudActual?.email_solicitante }}</span>
            </h5>
            <button class="btn-close" @click="showEmailModal = false"></button>
          </div>

          <div class="modal-body">
            <p class="small text-muted mb-2">Podés editar el mensaje antes de enviarlo.</p>
            <textarea
              v-model="emailBody"
              class="form-control"
              rows="16"
              style="font-family:monospace;font-size:0.875rem"
            ></textarea>
          </div>

          <div class="modal-footer">
            <button class="btn btn-secondary" @click="showEmailModal = false">Cancelar</button>
            <button class="btn btn-primary" :disabled="!emailBody.trim() || saving" @click="confirmarConEmail">
              <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
              Confirmar y enviar
            </button>
          </div>

        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import inscripcionesService from '@/services/torneos/inscripcionesService'
import { useToastStore } from '@/stores/toastStore'
import { useUserStore } from '@/stores/userStore'

const props = defineProps({
  idTorneo: { type: Number, required: true },
})

const emit = defineEmits(['aprobada'])

const toast = useToastStore()
const userStore = useUserStore()

// --- Estado de lista ---
const solicitudes = ref([])
const loading = ref(false)
const filtroEstado = ref(null)

// --- Modal visualizar ---
const showVisualizarModal = ref(false)
const solicitudActual = ref(null)
const jugadores = ref([])
const loadingJugadores = ref(false)
const desbloqueado = ref(false)
const accionSeleccionada = ref(null) // 'observar' | 'rechazar' | null
const textoAccion = ref('')
const saving = ref(false)
const mostrarMensajeAdmin = ref(false)

// --- Estado doc ---
const savingDocId = ref(null)

// --- Modal email ---
const showEmailModal = ref(false)
const emailBody = ref('')
const accionPendiente = ref(null) // 'aprobar' | 'rechazar'

// -------------------------------------------------------
// Helpers
// -------------------------------------------------------

const capitan = computed(() => jugadores.value.find(j => Number(j.es_capitan) === 1) ?? null)

const puedeAprobar = computed(() =>
  jugadores.value.every(j => j.estado_documentacion === 'aprobada')
)

const GRUPOS_ESTADO = [
  { idEstado: 1, label: 'Pendiente' },
  { idEstado: 7, label: 'Observada' },
  { idEstado: 8, label: 'Aprobada' },
  { idEstado: 5, label: 'Rechazada' },
]

const solicitudesFiltradas = computed(() => {
  if (filtroEstado.value === null) return solicitudes.value
  return solicitudes.value.filter(s => Number(s.id_estado) === filtroEstado.value)
})

const gruposSolicitudes = computed(() => {
  return GRUPOS_ESTADO
    .map(grupo => ({
      ...grupo,
      solicitudes: solicitudesFiltradas.value.filter(s => Number(s.id_estado) === grupo.idEstado),
    }))
    .filter(grupo => grupo.solicitudes.length > 0)
})

const equiposInscriptos = computed(() =>
  solicitudes.value.filter(s => Number(s.id_estado) === 8)
)

const formatFecha = (value) => {
  if (!value) return '-'
  return new Date(String(value).replace(' ', 'T')).toLocaleDateString('es-AR')
}

const formatFechaNac = (value) => {
  if (!value) return '-'
  const [anio, mes, dia] = String(value).split('-')
  return `${dia}/${mes}/${anio}`
}

const jugadorActualizado = (j) => {
  if (!j.fecha_actualizacion_documentacion || !solicitudActual.value?.fecha_actualizacion_estado) return false
  return new Date(j.fecha_actualizacion_documentacion) > new Date(solicitudActual.value.fecha_actualizacion_estado)
}

const calcularEdad = (fechaNac) => {
  if (!fechaNac) return null
  const hoy = new Date()
  const nac = new Date(fechaNac + 'T00:00:00')
  let edad = hoy.getFullYear() - nac.getFullYear()
  const m = hoy.getMonth() - nac.getMonth()
  if (m < 0 || (m === 0 && hoy.getDate() < nac.getDate())) edad--
  return edad
}

const badgeEstado = (estado) => {
  const key = String(estado || '').toUpperCase()
  if (key === 'PENDIENTE')             return 'bg-warning-subtle text-warning'
  if (key === 'PENDIENTE DE REVISION') return 'bg-info-subtle text-info'
  if (key === 'OBSERVADA')             return 'bg-info-subtle text-info'
  if (key === 'APROBADA')              return 'bg-success-subtle text-success'
  if (key === 'RECHAZADA')             return 'bg-danger-subtle text-danger'
  return 'bg-secondary-subtle text-secondary'
}

const fichaUrl = (nombreArchivo) => {
  if (!nombreArchivo) return ''
  const base = (import.meta.env.VITE_FICHAS_URL || '').replace(/\/+$/, '')
  return `${base}/${encodeURIComponent(nombreArchivo)}`
}

// -------------------------------------------------------
// Templates de email
// -------------------------------------------------------

const buildTemplateAprobada = () => {
  const nombre = solicitudActual.value?.nombre_equipo || ''
  return `Hola,

Te informamos que la inscripción del equipo ${nombre} ha sido aprobada correctamente.

Saludos,
Equipo de Il Calcio Camp`
}

const buildTemplateObservada = () => {
  const nombre = solicitudActual.value?.nombre_equipo || ''

  const rechazados = jugadores.value.filter(j => j.estado_documentacion === 'rechazada')
  const pendientes = jugadores.value.filter(j => j.estado_documentacion === 'pendiente')

  const listaRechazados = rechazados.length
    ? '\nDocumentación rechazada:\n' + rechazados.map(j => `- ${j.apellido}, ${j.nombre} (DNI: ${j.dni})`).join('\n')
    : ''

  const listaPendientes = pendientes.length
    ? '\nDocumentación pendiente:\n' + pendientes.map(j => `- ${j.apellido}, ${j.nombre} (DNI: ${j.dni})`).join('\n')
    : ''

  const seccionMotivo = rechazados.length ? '\nMotivo:\n' : ''

  return `Hola, necesitamos algunos datos o documentos más para poder continuar con la inscripción del equipo ${nombre}.
${listaRechazados}${seccionMotivo}
${listaPendientes}

Por favor, ingresá nuevamente a la web de inscripción https://ilcalciocamp.impactosdg.com
y completá la información solicitada.

Saludos,
Equipo de Il Calcio Camp`
}

const buildTemplateRechazada = (motivo) => {
  const nombre = solicitudActual.value?.nombre_equipo || ''

  const rechazados = jugadores.value.filter(j => j.estado_documentacion === 'rechazada')
  const pendientes = jugadores.value.filter(j => j.estado_documentacion === 'pendiente')

  const listaRechazados = rechazados.length
    ? '\nDocumentación rechazada:\n' + rechazados.map(j => `- ${j.apellido}, ${j.nombre} (DNI: ${j.dni})`).join('\n')
    : ''

  const listaPendientes = pendientes.length
    ? '\nDocumentación pendiente:\n' + pendientes.map(j => `- ${j.apellido}, ${j.nombre} (DNI: ${j.dni})`).join('\n')
    : ''

  return `Hola,

Te informamos que la inscripción del equipo ${nombre} no pudo ser aprobada.

Motivo:
${motivo}

Saludos,
Equipo de Il Calcio Camp`
}

// -------------------------------------------------------
// Carga de datos
// -------------------------------------------------------

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

// -------------------------------------------------------
// Abrir modal visualizar
// -------------------------------------------------------

const abrirVisualizar = async (sol) => {
  solicitudActual.value = sol
  desbloqueado.value = false
  mostrarMensajeAdmin.value = false
  accionSeleccionada.value = null
  textoAccion.value = ''
  jugadores.value = []
  showVisualizarModal.value = true

  loadingJugadores.value = true
  try {
    jugadores.value = await inscripcionesService.getJugadores(sol.id)
  } catch {
    toast.error('Error al cargar jugadores')
  } finally {
    loadingJugadores.value = false
  }
}

// -------------------------------------------------------
// Estado doc
// -------------------------------------------------------

const seleccionarEstadoDoc = (jugador, nuevoEstado) => {
  if (jugador.estado_documentacion === nuevoEstado) return

  if (nuevoEstado === 'pendiente de revision' && ['aprobada', 'rechazada'].includes(jugador.estado_documentacion)) {
    toast.error('No se puede volver a pendiente de revisión una vez que la documentación fue aprobada o rechazada.')
    return
  }

  cambiarEstadoDoc(jugador, nuevoEstado)
}

const cambiarEstadoDoc = async (jugador, nuevoEstado) => {
  const estadoAnterior = jugador.estado_documentacion
  jugador.estado_documentacion = nuevoEstado
  savingDocId.value = jugador.id
  try {
    await inscripcionesService.setEstadoDocumentacion(jugador.id, nuevoEstado)
  } catch {
    jugador.estado_documentacion = estadoAnterior
    toast.error('Error al actualizar el estado del documento')
  } finally {
    savingDocId.value = null
  }
}

// -------------------------------------------------------
// Acciones de estado
// -------------------------------------------------------

const intentarAprobar = () => {
  if (!puedeAprobar.value) {
    toast.error('No podés aprobar la solicitud mientras haya archivos pendientes, en revisión o rechazados.')
    return
  }
  continuarAprobar()
}

const continuarAprobar = () => {
  accionPendiente.value = 'aprobar'
  emailBody.value = buildTemplateAprobada()
  showEmailModal.value = true
}

const abrirInformarPorMail = () => {
  accionPendiente.value = 'observar'
  emailBody.value = buildTemplateObservada()
  showEmailModal.value = true
}

const continuarRechazar = () => {
  if (!textoAccion.value.trim()) return
  accionPendiente.value = 'rechazar'
  emailBody.value = buildTemplateRechazada(textoAccion.value.trim())
  showEmailModal.value = true
}

const confirmarConEmail = async () => {
  if (!emailBody.value.trim() || saving.value) return
  saving.value = true
  try {
    if (accionPendiente.value === 'aprobar') {
      await inscripcionesService.aprobar(solicitudActual.value.id, emailBody.value)
      toast.success('Solicitud aprobada. Equipo y jugadores creados.')
      actualizarEstadoLocal(8, 'Aprobada', emailBody.value)
      emit('aprobada')
    } else if (accionPendiente.value === 'observar') {
      await inscripcionesService.observar(solicitudActual.value.id, 'Solicitud en revisión', emailBody.value)
      toast.success('Solicitud marcada como observada')
      actualizarEstadoLocal(7, 'Observada', emailBody.value)
    } else {
      await inscripcionesService.rechazar(solicitudActual.value.id, textoAccion.value, emailBody.value)
      toast.success('Solicitud rechazada')
      actualizarEstadoLocal(5, 'Rechazada', emailBody.value)
    }
    showEmailModal.value = false
    accionSeleccionada.value = null
    textoAccion.value = ''
    desbloqueado.value = false
    await cargar()
  } catch (e) {
    toast.error(e?.response?.data?.message || 'Error al procesar la acción')
  } finally {
    saving.value = false
  }
}

// Actualiza el objeto solicitudActual en memoria para que el modal refleje el nuevo estado sin recargar
const actualizarEstadoLocal = (idEstado, estadoTexto, observacion) => {
  if (!solicitudActual.value) return
  solicitudActual.value = {
    ...solicitudActual.value,
    id_estado: idEstado,
    estado: estadoTexto,
    observacion_admin: observacion ?? solicitudActual.value.observacion_admin,
  }
}

onMounted(async () => {
  await cargar()
})
</script>

<style scoped>
.modal-xxl {
  max-width: 1500px;
}

</style>
