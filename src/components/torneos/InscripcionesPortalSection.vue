<template>
  <div class="mt-4 pt-3 border-top">
    <!-- Encabezado -->
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
              <span
                v-if="Number(sol.id_estado) === 7 && sol.tiene_docs_nuevas"
                class="badge bg-primary-subtle text-primary rounded-pill ms-1"
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
        </tbody>
      </table>
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
                <div v-if="!accionSeleccionada" class="d-flex gap-2 flex-wrap">
                  <button
                    v-if="Number(solicitudActual?.id_estado) !== 8"
                    class="btn btn-sm btn-outline-success"
                    @click="accionSeleccionada = 'aprobar'"
                  >
                    <i class="bi bi-check-lg me-1"></i>Aprobar
                  </button>
                  <button
                    class="btn btn-sm btn-outline-warning"
                    @click="abrirInformarPorMail"
                  >
                    <i class="bi bi-envelope me-1"></i>Informar por mail
                  </button>
                  <button
                    v-if="Number(solicitudActual?.id_estado) !== 5"
                    class="btn btn-sm btn-outline-danger"
                    @click="accionSeleccionada = 'rechazar'"
                  >
                    <i class="bi bi-x-lg me-1"></i>Rechazar
                  </button>
                  <button v-if="desbloqueado" class="btn btn-sm btn-outline-secondary" @click="desbloqueado = false">
                    <i class="bi bi-x me-1"></i>Cancelar
                  </button>
                </div>

                <!-- Formulario: Aprobar -->
                <div v-if="accionSeleccionada === 'aprobar'" class="mt-2">
                  <label class="form-label small fw-semibold">Disciplina del equipo</label>
                  <div class="d-flex gap-2 align-items-center flex-wrap">
                    <select v-model="idDisciplina" class="form-select form-select-sm" style="max-width:240px">
                      <option v-for="d in disciplinas" :key="d.id" :value="d.id">{{ d.nombre }}</option>
                    </select>
                    <button class="btn btn-sm btn-success" :disabled="!idDisciplina" @click="continuarAprobar">
                      Continuar
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" @click="accionSeleccionada = null">Volver</button>
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
import datosMaestrosService from '@/services/datosMaestrosService'
import { useToastStore } from '@/stores/toastStore'

const props = defineProps({
  idTorneo: { type: Number, required: true },
  idDisciplinaDefault: { type: Number, default: null },
})

const emit = defineEmits(['aprobada'])

const toast = useToastStore()

// --- Estado de lista ---
const solicitudes = ref([])
const loading = ref(false)
const filtroEstado = ref(null)

// --- Modal visualizar ---
const showVisualizarModal = ref(false)
const solicitudActual = ref(null)
const jugadores = ref([])
const loadingJugadores = ref(false)
const disciplinas = ref([])
const desbloqueado = ref(false)
const accionSeleccionada = ref(null) // 'aprobar' | 'observar' | 'rechazar' | null
const textoAccion = ref('')
const idDisciplina = ref(null)
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

const solicitudesFiltradas = computed(() => {
  if (filtroEstado.value === null) return solicitudes.value
  return solicitudes.value.filter(s => Number(s.id_estado) === filtroEstado.value)
})

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
  idDisciplina.value = props.idDisciplinaDefault
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
      await inscripcionesService.aprobar(solicitudActual.value.id, idDisciplina.value, emailBody.value)
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
  datosMaestrosService.getDisciplinas().then(d => { disciplinas.value = d }).catch(() => {})
})
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
</style>
