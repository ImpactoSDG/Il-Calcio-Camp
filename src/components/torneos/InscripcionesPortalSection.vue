<template>
  <div class="d-flex flex-column gap-3 mb-4">
    <!-- Pendientes de aprobación -->
    <div class="seccion-solicitudes seccion-pendientes">
      <div class="seccion-header d-flex align-items-center justify-content-between gap-2 p-3">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-hourglass-split text-warning fs-5"></i>
          <div>
            <div class="fw-bold">Pendientes de aprobación</div>
            <div class="small text-muted">Inscripciones enviadas por delegados que todavía no fueron aprobadas ni rechazadas.</div>
          </div>
        </div>
        <div v-if="solicitudesPendientes.length > 0" class="position-relative" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
          <i class="bi bi-bell-fill text-warning" style="font-size: 1.4rem;"></i>
          <span class="badge rounded-pill bg-danger text-white position-absolute" style="bottom: -4px; right: -6px; padding: 0.2rem 0.35rem; font-size: 0.6rem; min-width: 18px; display: flex; align-items: center; justify-content: center;">
            {{ solicitudesPendientes.length }}
          </span>
        </div>
        <span v-else class="badge rounded-pill bg-light text-muted">Sin pendientes</span>
      </div>

      <div v-if="loading" class="text-center py-3 text-muted border-top">
        <span class="spinner-border spinner-border-sm me-2"></span>Cargando solicitudes...
      </div>
      <div v-else-if="!solicitudesPendientes.length" class="text-center text-muted small py-3 border-top">
        No hay solicitudes pendientes de aprobación.
      </div>
      <div v-else class="border-top">
        <div v-for="sol in solicitudesPendientes" :key="sol.id" class="solicitud-row d-flex flex-wrap align-items-center justify-content-between gap-2 p-3">
          <div>
            <div class="fw-semibold">{{ sol.nombre_equipo }}</div>
            <div class="small text-muted">
              <i class="bi bi-envelope me-1"></i>{{ sol.email_solicitante }}
              <span class="mx-1">·</span>
              <i class="bi bi-calendar3 me-1"></i>Recibido: {{ formatFecha(sol.fecha_creacion) }}
            </div>
          </div>
          <div class="d-flex align-items-center gap-2">
            <span class="badge rounded-pill" :class="badgeEstado(sol.estado)">{{ sol.estado }}</span>
            <span
              v-if="Number(sol.id_estado) === 7 && sol.tiene_docs_nuevas"
              class="badge bg-primary-subtle text-primary rounded-pill"
              title="El delegado actualizó documentación desde la última revisión"
            >Doc. a revisar</span>
            <button class="btn btn-sm btn-outline-warning" @click="abrirVisualizar(sol)">
              <i class="bi bi-eye me-1"></i>Revisar
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ============================================================
       MODAL PRINCIPAL: Visualizar solicitud
       ============================================================ -->
  <Teleport to="body">
    <div v-if="showVisualizarModal" class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,0.5)" @click.self="showVisualizarModal = false">
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
                <div v-if="solicitudActual?.nombre_capitan" class="col-sm-6 col-lg-3">
                  <span class="text-muted">Capitán:</span><br>
                  {{ solicitudActual.nombre_capitan }}
                </div>
                <div v-if="solicitudActual?.email_capitan" class="col-sm-6 col-lg-3">
                  <span class="text-muted">Email capitán:</span><br>
                  {{ solicitudActual.email_capitan }}
                </div>
                <div v-if="solicitudActual?.telefono_capitan" class="col-sm-6 col-lg-3">
                  <span class="text-muted">Teléfono capitán:</span><br>
                  {{ solicitudActual.telefono_capitan }}
                </div>
                <div v-if="solicitudActual?.email_solicitante" class="col-sm-6 col-lg-3">
                  <span class="text-muted">Email cuenta web:</span><br>
                  {{ solicitudActual.email_solicitante }}
                </div>
              </div>

              <!-- Comprobante de pago: destacado para que salte a la vista -->
              <div
                class="d-flex flex-wrap align-items-center justify-content-between gap-2 p-3 rounded border mt-3"
                :class="solicitudActual?.comprobante_pago ? 'bg-success-subtle border-success-subtle' : 'bg-warning-subtle border-warning-subtle'"
              >
                <div class="d-flex align-items-center gap-2">
                  <i
                    class="bi fs-3"
                    :class="solicitudActual?.comprobante_pago ? 'bi-file-earmark-check-fill text-success' : 'bi-exclamation-triangle-fill text-warning'"
                  ></i>
                  <div>
                    <div class="fw-bold">Comprobante de pago</div>
                    <div class="small" :class="solicitudActual?.comprobante_pago ? 'text-success-emphasis' : 'text-warning-emphasis'">
                      <template v-if="solicitudActual?.comprobante_pago">
                        Cargado{{ solicitudActual?.fecha_actualizacion_comprobante_pago ? ` el ${formatFecha(solicitudActual.fecha_actualizacion_comprobante_pago)}` : '' }}
                      </template>
                      <template v-else>
                        El delegado todavía no subió el comprobante de pago.
                      </template>
                    </div>
                  </div>
                </div>
                <a
                  v-if="solicitudActual?.comprobante_pago"
                  :href="resolveComprobantePagoUrl(solicitudActual.comprobante_pago)"
                  target="_blank"
                  rel="noopener"
                  class="btn btn-success"
                >
                  <i class="bi bi-eye me-1"></i>Ver comprobante
                </a>
              </div>

              <!-- Mensaje previo enviado al delegado -->
              <div v-if="solicitudActual?.observacion_admin" class="alert alert-warning small mt-3 mb-0">
                <strong>Último mensaje enviado al delegado:</strong>
                <pre class="mb-0 mt-1" style="white-space:pre-wrap;font-size:0.8rem">{{ solicitudActual.observacion_admin }}</pre>
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
                      <th>DNI</th>
                      <th>Edad</th>
                      <th>Email</th>
                      <th>Teléfono</th>
                      <th>Documento</th>
                      <th>Estado doc.</th>
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
                          :href="resolveArchivoUrl(j.archivo_documentacion)"
                          target="_blank"
                          rel="noopener"
                          class="btn btn-sm btn-outline-secondary"
                        >
                          <i class="bi bi-file-earmark-text me-1"></i>Ver
                        </a>
                        <span v-else class="small text-muted">Sin archivo</span>
                      </td>
                      <td @click.stop>
                        <span v-if="savingDocId === j.id" class="spinner-border spinner-border-sm text-secondary"></span>
                        <div v-else class="estado-doc-dropdown">
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
            </div>

            <!-- Sección 3: Cambio de estado -->
            <div class="border-top pt-3">
              <h6 class="fw-bold text-secondary mb-2">Cambio de estado</h6>

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
                <div v-if="!accionSeleccionada" class="d-flex flex-column gap-2">
                  <div
                    v-if="Number(solicitudActual?.id_estado) !== 8"
                    class="d-flex align-items-center gap-3 p-2 border rounded"
                  >
                    <button
                      class="btn btn-sm btn-success flex-shrink-0"
                      style="min-width:130px"
                      @click="continuarAprobar"
                    >
                      <i class="bi bi-check-lg me-1"></i>Aprobar
                    </button>
                    <span class="small text-muted">
                      Aprueba la inscripción: crea el equipo y los jugadores en el sistema y le avisa por mail al delegado que quedó confirmada.
                    </span>
                  </div>

                  <div class="d-flex align-items-center gap-3 p-2 border rounded">
                    <button
                      class="btn btn-sm btn-warning flex-shrink-0"
                      style="min-width:130px"
                      @click="abrirInformarPorMail"
                    >
                      <i class="bi bi-envelope me-1"></i>Informar
                    </button>
                    <span class="small text-muted">
                      Le manda un mail al delegado (por ejemplo para pedirle que corrija algo o suba un comprobante) sin aprobar ni rechazar todavía. La solicitud queda como "Observada".
                    </span>
                  </div>

                  <div
                    v-if="Number(solicitudActual?.id_estado) !== 5"
                    class="d-flex align-items-center gap-3 p-2 border rounded"
                  >
                    <button
                      class="btn btn-sm btn-danger flex-shrink-0"
                      style="min-width:130px"
                      @click="accionSeleccionada = 'rechazar'"
                    >
                      <i class="bi bi-x-lg me-1"></i>Rechazar
                    </button>
                    <span class="small text-muted">
                      Rechaza definitivamente la inscripción y le avisa por mail al delegado el motivo. El delegado no podrá volver a editarla.
                    </span>
                  </div>

                  <button v-if="desbloqueado" class="btn btn-sm btn-outline-secondary align-self-start" @click="desbloqueado = false">
                    <i class="bi bi-x me-1"></i>Cancelar
                  </button>
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
import { ref, computed, onMounted, watch } from 'vue'
import inscripcionesService from '@/services/torneos/inscripcionesService'
import { useToastStore } from '@/stores/toastStore'
import { resolveComprobantePagoUrl } from '@/utils/comprobantesPago'

const props = defineProps({
  idTorneo: { type: Number, required: true },
})

// 'rechazadas' se emite para que la vista padre pueda mostrar esa sección
// más abajo en la página (después de "Equipos confirmados").
const emit = defineEmits(['aprobada', 'rechazadas'])

const toast = useToastStore()

// --- Estado de lista ---
const solicitudes = ref([])
const loading = ref(false)

// --- Modal visualizar ---
const showVisualizarModal = ref(false)
const solicitudActual = ref(null)
const jugadores = ref([])
const loadingJugadores = ref(false)
const desbloqueado = ref(false)
const accionSeleccionada = ref(null) // 'aprobar' | 'observar' | 'rechazar' | null
const textoAccion = ref('')
const saving = ref(false)

// --- Estado doc dropdown ---
const savingDocId = ref(null)
const dropdownAbierto = ref(null)

// --- Modal email ---
const showEmailModal = ref(false)
const emailBody = ref('')
const accionPendiente = ref(null) // 'aprobar' | 'rechazar'

const estadosDoc = [
  { value: 'pendiente',  label: 'pendiente',  clase: 'text-secondary' },
  { value: 'aprobada',   label: 'aprobada',   clase: 'text-success'   },
  { value: 'rechazada',  label: 'rechazada',  clase: 'text-danger'    },
]

// -------------------------------------------------------
// Helpers
// -------------------------------------------------------

// Pendientes: cualquier estado que todavía no sea un cierre definitivo (aprobada/rechazada).
const solicitudesPendientes = computed(() =>
  solicitudes.value.filter(s => ![5, 8].includes(Number(s.id_estado)))
)

const solicitudesRechazadas = computed(() =>
  solicitudes.value.filter(s => Number(s.id_estado) === 5)
)

watch(solicitudesRechazadas, (val) => emit('rechazadas', val), { immediate: true })

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
  if (key === 'PENDIENTE') return 'bg-warning-subtle text-warning'
  if (key === 'PENDIENTE_PAGO') return 'bg-warning-subtle text-warning'
  if (key === 'PAGO_EN_REVISION') return 'bg-primary-subtle text-primary'
  if (key === 'OBSERVADA')  return 'bg-info-subtle text-info'
  if (key === 'APROBADA')   return 'bg-success-subtle text-success'
  if (key === 'RECHAZADA')  return 'bg-danger-subtle text-danger'
  return 'bg-secondary-subtle text-secondary'
}

const estadoDocClass = (estado) => {
  if (estado === 'aprobada')  return 'text-success border-success'
  if (estado === 'rechazada') return 'text-danger border-danger'
  return 'text-secondary border-secondary'
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
    ? '\nDocumentación pendiente de revisión:\n' + pendientes.map(j => `- ${j.apellido}, ${j.nombre} (DNI: ${j.dni})`).join('\n')
    : ''

  return `Hola, necesitamos algunos datos o documentos más para poder continuar con la inscripción del equipo ${nombre}.
${listaRechazados}
Motivo:

${listaPendientes}

Por favor, ingresá nuevamente a la web de inscripción y completá la información solicitada.

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
    ? '\nDocumentación pendiente de revisión:\n' + pendientes.map(j => `- ${j.apellido}, ${j.nombre} (DNI: ${j.dni})`).join('\n')
    : ''

  return `Hola,

Te informamos que la inscripción del equipo ${nombre} no pudo ser aprobada en esta instancia.

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
  accionSeleccionada.value = null
  textoAccion.value = ''
  jugadores.value = []
  dropdownAbierto.value = null
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

// Usado desde "Equipos confirmados": ahí solo se tiene el id de la solicitud
// original, hay que traerla antes de poder abrir el modal.
const abrirVisualizarPorId = async (id) => {
  if (!id) return
  try {
    const sol = await inscripcionesService.getById(id)
    await abrirVisualizar(sol)
  } catch {
    toast.error('No se pudo cargar el detalle de la inscripción')
  }
}

// -------------------------------------------------------
// Estado doc
// -------------------------------------------------------

const toggleDropdown = (id) => {
  dropdownAbierto.value = dropdownAbierto.value === id ? null : id
}

const seleccionarEstadoDoc = (jugador, nuevoEstado) => {
  dropdownAbierto.value = null
  if (jugador.estado_documentacion !== nuevoEstado) {
    cambiarEstadoDoc(jugador, nuevoEstado)
  }
}

const cambiarEstadoDoc = async (jugador, nuevoEstado) => {
  savingDocId.value = jugador.id
  try {
    await inscripcionesService.setEstadoDocumentacion(jugador.id, nuevoEstado)
    jugador.estado_documentacion = nuevoEstado
    toast.success(`Documentación ${nuevoEstado === 'aprobada' ? 'aprobada' : nuevoEstado === 'rechazada' ? 'rechazada' : 'actualizada'}.`)
  } catch {
    toast.error('Error al actualizar el estado del documento')
  } finally {
    savingDocId.value = null
  }
}

// -------------------------------------------------------
// Acciones de estado
// -------------------------------------------------------

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
      actualizarEstadoLocal(8, 'Aprobada', null)
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

// Permite que la vista padre abra el modal de una solicitud rechazada
// (esa sección se renderiza más abajo en la página, fuera de este componente).
defineExpose({ abrirVisualizar, abrirVisualizarPorId })
</script>

<style scoped>
.modal-xxl {
  max-width: 1500px;
}

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
  z-index: 1070;
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

.seccion-solicitudes {
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  overflow: hidden;
  background: #fff;
}

.seccion-pendientes {
  border-left: 4px solid #f59e0b;
}

.solicitud-row + .solicitud-row {
  border-top: 1px solid #f1f5f9;
}

.solicitud-row:hover {
  background: #f8fafc;
}
</style>
