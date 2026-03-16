<template>
  <div class="container-fluid p-4 bg-white min-vh-100 position-relative animate-fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
      <div class="d-flex align-items-center">
        <button @click="$router.back()" class="btn-back-arrow me-2" title="Volver">
          <i class="bi bi-arrow-left"></i>
        </button>
        <h1 class="h5 fw-bold mb-0 text-secondary">RESULTADO DE PARTIDO</h1>
      </div>
    </div>

    <div class="card shadow-sm border-0 rounded-lg mb-4">
      <div class="card-body p-4">
        <h2 class="h6 fw-bold text-secondary mb-3">Selección de partido</h2>
        <div class="row g-3 align-items-end">
          <div class="col-12 col-md-5">
            <label class="form-label">Torneo</label>
            <select v-model.number="idTorneoSeleccionado" class="form-select" @change="onTorneoChange">
              <option :value="null">Seleccionar torneo</option>
              <option v-for="torneo in torneos" :key="torneo.id" :value="Number(torneo.id)">
                {{ torneo.nombre }}
              </option>
            </select>
          </div>
          <div class="col-12 col-md-7">
            <label class="form-label">Partido (programado o finalizado)</label>
            <select v-model.number="idPartidoSeleccionado" class="form-select" :disabled="!idTorneoSeleccionado" @change="onPartidoChange">
              <option :value="null">Seleccionar partido</option>
              <option v-for="partido in partidosFiltrados" :key="partido.id" :value="Number(partido.id)">
                #{{ partido.id }} - {{ partido.equipo_local_nombre || 'Local' }} vs {{ partido.equipo_visitante_nombre || 'Visitante' }}
                ({{ partido.estado_evento_descripcion || `Estado ${partido.id_estado_evento}` }})
              </option>
            </select>
          </div>
        </div>

        <div v-if="idTorneoSeleccionado && !partidosFiltrados.length" class="alert alert-warning mt-3 mb-0">
          El torneo seleccionado no tiene partidos en estado Programado o Finalizado.
        </div>
      </div>
    </div>

    <div v-if="partidoSeleccionado" class="row g-4">
      <div class="col-12 col-lg-6">
        <div class="card shadow-sm border-0 rounded-lg h-100">
          <div class="card-body p-4">
            <h2 class="h6 fw-bold text-secondary mb-3">Resultado del partido</h2>

            <div class="match-summary mb-3">
              <div class="small text-muted">Partido seleccionado</div>
              <div class="fw-semibold">
                {{ partidoSeleccionado.equipo_local_nombre || 'Local' }} vs {{ partidoSeleccionado.equipo_visitante_nombre || 'Visitante' }}
              </div>
              <div class="small text-muted">
                Estado actual: {{ partidoSeleccionado.estado_evento_descripcion || `Estado ${partidoSeleccionado.id_estado_evento}` }}
              </div>
            </div>

            <div class="row g-3">
                <div class="col-6">
                  <label class="form-label">Resultado local</label>
                  <input v-model.number="resultadoForm.resultado_local" type="number" min="0" class="form-control" />
                </div>
                <div class="col-6">
                  <label class="form-label">Resultado visitante</label>
                  <input v-model.number="resultadoForm.resultado_visitante" type="number" min="0" class="form-control" />
                </div>

                <div class="col-12">
                  <div class="form-check mt-1">
                    <input id="chkPenales" v-model="resultadoForm.hubo_penales" type="checkbox" class="form-check-input" />
                    <label for="chkPenales" class="form-check-label">Definición por penales (fase eliminatoria)</label>
                  </div>
                </div>

                <template v-if="resultadoForm.hubo_penales">
                  <div class="col-6">
                    <label class="form-label">Penales local</label>
                    <input v-model.number="resultadoForm.resultado_penales_local" type="number" min="0" class="form-control" />
                  </div>
                  <div class="col-6">
                    <label class="form-label">Penales visitante</label>
                    <input v-model.number="resultadoForm.resultado_penales_visitante" type="number" min="0" class="form-control" />
                  </div>
                </template>

            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-6">
        <div class="card shadow-sm border-0 rounded-lg h-100">
          <div class="card-body p-4">
            <h2 class="h6 fw-bold text-secondary mb-3">Incidencias del partido</h2>

            <form @submit.prevent="agregarIncidenciaBorrador" class="row g-3 mb-4">
              <div class="col-12">
                <label class="form-label">Equipo</label>
                <div class="equipo-options">
                  <button
                    v-for="equipo in equiposPartido"
                    :key="equipo.id"
                    type="button"
                    class="equipo-option"
                    :class="{ active: Number(incidenciaForm.id_equipo) === Number(equipo.id) }"
                    @click="setEquipoIncidencia(Number(equipo.id))"
                  >
                    <img
                      v-if="equipo.escudo"
                      :src="resolveEscudoUrl(equipo.escudo)"
                      alt="escudo"
                      class="escudo-thumb"
                    />
                    <span class="fw-semibold">{{ equipo.nombre }}</span>
                  </button>
                </div>
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label">Jugador</label>
                <select
                  v-model.number="incidenciaForm.id_jugador"
                  class="form-select"
                  :disabled="!incidenciaForm.id_equipo"
                >
                  <option :value="null">Seleccionar jugador (opcional)</option>
                  <option v-for="jugador in jugadoresFiltradosPorEquipo" :key="jugador.id" :value="Number(jugador.id)">
                    {{ jugador.apellido }}, {{ jugador.nombre }}
                  </option>
                </select>
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label">Tipo de incidencia</label>
                <select v-model.number="incidenciaForm.id_tipo_evento_partido" class="form-select" required>
                  <option :value="null">Seleccionar tipo</option>
                  <option v-for="tipo in tiposEventoPartidoActivos" :key="tipo.id" :value="Number(tipo.id)">
                    {{ tipo.descripcion }}
                  </option>
                </select>
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label">Minuto</label>
                <input v-model.number="incidenciaForm.minuto" type="number" min="0" max="200" class="form-control" placeholder="Opcional" />
              </div>
              <div class="col-12">
                <label class="form-label">Observación</label>
                <input v-model.trim="incidenciaForm.observacion" type="text" class="form-control" placeholder="Opcional" />
              </div>
              <div class="col-12">
                <button class="btn btn-outline-primary" :disabled="!tiposEventoPartidoActivos.length">
                  Agregar incidencia al borrador
                </button>
              </div>
            </form>

            <div v-if="!tiposEventoPartidoActivos.length" class="alert alert-warning py-2">
              No hay tipos de incidencia activos en el catálogo.
            </div>

            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0">
                <thead>
                  <tr>
                    <th>Estado</th>
                    <th>Equipo</th>
                    <th>Jugador</th>
                    <th>Tipo</th>
                    <th>Min</th>
                    <th>Observación</th>
                    <th class="text-end">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="incidencia in incidenciasMostradas" :key="incidencia.id">
                    <td>
                      <span class="badge rounded-pill" :class="incidencia.esPendiente ? 'bg-warning-subtle text-warning' : 'bg-success-subtle text-success'">
                        {{ incidencia.esPendiente ? 'Pendiente' : 'Guardada' }}
                      </span>
                    </td>
                    <td>{{ incidencia.equipo_nombre || '-' }}</td>
                    <td>
                      {{ incidencia.jugador_apellido || incidencia.jugador_nombre
                        ? `${incidencia.jugador_apellido || ''}${incidencia.jugador_apellido && incidencia.jugador_nombre ? ', ' : ''}${incidencia.jugador_nombre || ''}`
                        : '-' }}
                    </td>
                    <td>{{ incidencia.tipo_evento_partido_descripcion }}</td>
                    <td>{{ incidencia.minuto ?? '-' }}</td>
                    <td>{{ incidencia.observacion || '-' }}</td>
                    <td class="text-end">
                      <button class="btn btn-sm btn-outline-danger" @click="eliminarIncidencia(incidencia)">
                        Eliminar
                      </button>
                    </td>
                  </tr>
                  <tr v-if="!incidenciasMostradas.length">
                    <td colspan="7" class="text-center text-muted py-3">Sin incidencias registradas.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12">
        <div class="card shadow-sm border-0 rounded-lg">
          <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
              <div class="fw-semibold">Guardar cambios del partido</div>
              <div class="small text-muted">
                Se guardará resultado y {{ incidenciasPendientes.length }} incidencia(s) nueva(s)
                <span v-if="incidenciasEliminadasIds.length">, y se eliminarán {{ incidenciasEliminadasIds.length }} incidencia(s)</span>.
              </div>
            </div>
            <button class="btn btn-primary-modern" :disabled="savingTodo" @click="guardarTodo">
              <span v-if="savingTodo" class="spinner-border spinner-border-sm me-2"></span>
              Guardar todo
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="loading" class="loading-overlay-local d-flex flex-column align-items-center justify-content-center">
      <div class="spinner-border text-primary-custom" role="status" style="width: 3rem; height: 3rem;">
        <span class="visually-hidden">Cargando...</span>
      </div>
    </div>

    <Teleport to="body">
      <div v-if="showResumenModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.4); backdrop-filter: blur(3px);">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">
                <i class="bi bi-check-circle-fill text-success me-2"></i>
                Cambios guardados
              </h5>
              <button type="button" class="btn-close" @click="showResumenModal = false"></button>
            </div>
            <div class="modal-body">
              <pre class="resumen-pre mb-0">{{ resumenGuardado }}</pre>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-primary-modern" @click="showResumenModal = false">Aceptar</button>
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
import eventosService from '@/services/eventosService'
import { useToastStore } from '@/stores/toastStore'

const toast = useToastStore()

const ESTADOS_PARTIDO_HABILITADOS = [2, 4]
const ESTADO_FINALIZADO = 4
const ESTADO_FINALIZADO_REPORTADO = 7

const torneos = ref([])
const eventos = ref([])
const tiposEventoPartido = ref([])
const incidencias = ref([])
const incidenciasPendientes = ref([])
const incidenciasEliminadasIds = ref([])
const equipos = ref([])
const jugadores = ref([])

const loading = ref(false)
const savingTodo = ref(false)
const showResumenModal = ref(false)
const resumenGuardado = ref('')

const idTorneoSeleccionado = ref(null)
const idPartidoSeleccionado = ref(null)

const emptyResultadoForm = () => ({
  resultado_local: null,
  resultado_visitante: null,
  hubo_penales: false,
  resultado_penales_local: null,
  resultado_penales_visitante: null,
})

const emptyIncidenciaForm = () => ({
  id_equipo: null,
  id_jugador: null,
  id_tipo_evento_partido: null,
  minuto: null,
  observacion: '',
})

const resultadoForm = ref(emptyResultadoForm())
const incidenciaForm = ref(emptyIncidenciaForm())

const partidosFiltrados = computed(() => {
  if (!idTorneoSeleccionado.value) return []

  return eventos.value
    .filter(ev => Number(ev.id_torneo) === Number(idTorneoSeleccionado.value))
    .filter(ev => String(ev.tipo_evento).toLowerCase() === 'partido')
    // Solo reportables: programado y finalizado. El estado 7 ya reportado queda fuera.
    .filter(ev => ESTADOS_PARTIDO_HABILITADOS.includes(Number(ev.id_estado_evento)))
    .sort((a, b) => String(b.fecha_hora_inicio || '').localeCompare(String(a.fecha_hora_inicio || '')))
})

const partidoSeleccionado = computed(() => {
  if (!idPartidoSeleccionado.value) return null
  return partidosFiltrados.value.find(p => Number(p.id) === Number(idPartidoSeleccionado.value)) || null
})

const tiposEventoPartidoActivos = computed(() => (
  tiposEventoPartido.value.filter(item => Number(item.activo ?? 1) === 1)
))

const equiposPartido = computed(() => {
  const partido = partidoSeleccionado.value
  if (!partido) return []

  const ids = [Number(partido.id_equipo_local), Number(partido.id_equipo_visitante)]
    .filter(id => Number.isFinite(id) && id > 0)

  return ids
    .map((id, idx) => {
      const eq = equipos.value.find(item => Number(item.id) === id)
      if (eq) {
        return {
          id: Number(eq.id),
          nombre: eq.nombre,
          escudo: eq.escudo || null,
        }
      }

      return {
        id,
        nombre: idx === 0 ? (partido.equipo_local_nombre || 'Local') : (partido.equipo_visitante_nombre || 'Visitante'),
        escudo: null,
      }
    })
})

const jugadoresFiltradosPorEquipo = computed(() => {
  const idEquipo = Number(incidenciaForm.value.id_equipo)
  if (!idEquipo) return []

  return jugadores.value
    .filter(j => Number(j.id_equipo_actual) === idEquipo)
    .sort((a, b) => `${a.apellido || ''} ${a.nombre || ''}`.localeCompare(`${b.apellido || ''} ${b.nombre || ''}`))
})

const incidenciasMostradas = computed(() => {
  const idsEliminados = new Set(incidenciasEliminadasIds.value.map(id => Number(id)))
  const existentes = incidencias.value
    .filter(item => !idsEliminados.has(Number(item.id)))
    .map(item => ({ ...item, esPendiente: false }))

  return [...incidenciasPendientes.value, ...existentes]
})

const getApiMessage = (error, fallback) => error?.response?.data?.message || fallback

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

const cargarDatosIniciales = async () => {
  loading.value = true
  try {
    const [torneosData, eventosData, tiposData, equiposData, jugadoresData] = await Promise.all([
      datosMaestrosService.getTorneos(),
      eventosService.getEventos(),
      eventosService.getTiposEventoPartido(),
      datosMaestrosService.getEquipos(),
      datosMaestrosService.getJugadores(),
    ])
    torneos.value = Array.isArray(torneosData) ? torneosData : []
    eventos.value = Array.isArray(eventosData) ? eventosData : []
    tiposEventoPartido.value = Array.isArray(tiposData) ? tiposData : []
    equipos.value = Array.isArray(equiposData)
      ? equiposData.filter(item => Number(item.activo ?? 1) === 1)
      : []
    jugadores.value = Array.isArray(jugadoresData)
      ? jugadoresData.filter(item => Number(item.activo ?? 1) === 1)
      : []
  } catch (error) {
    toast.showToast({ message: getApiMessage(error, 'No se pudieron cargar los datos.'), type: 'danger' })
  } finally {
    loading.value = false
  }
}

const onTorneoChange = () => {
  idPartidoSeleccionado.value = null
  incidencias.value = []
  incidenciasPendientes.value = []
  incidenciasEliminadasIds.value = []
  resultadoForm.value = emptyResultadoForm()
  incidenciaForm.value = emptyIncidenciaForm()
}

const onPartidoChange = async () => {
  const partido = partidoSeleccionado.value
  if (!partido) {
    incidencias.value = []
    return
  }

  resultadoForm.value = {
    resultado_local: partido.resultado_local ?? null,
    resultado_visitante: partido.resultado_visitante ?? null,
    hubo_penales: partido.resultado_penales_local !== null || partido.resultado_penales_visitante !== null,
    resultado_penales_local: partido.resultado_penales_local ?? null,
    resultado_penales_visitante: partido.resultado_penales_visitante ?? null,
  }
  incidenciaForm.value = emptyIncidenciaForm()
  incidenciasPendientes.value = []
  incidenciasEliminadasIds.value = []

  try {
    incidencias.value = await eventosService.getEventosPartido(partido.id)
  } catch (error) {
    incidencias.value = []
    toast.showToast({ message: getApiMessage(error, 'No se pudieron cargar las incidencias.'), type: 'danger' })
  }
}

const parseNullableInt = (value) => {
  if (value === null || value === undefined || value === '') return null
  const parsed = Number(value)
  return Number.isNaN(parsed) ? null : parsed
}

const normalizeText = (txt) => String(txt || '')
  .normalize('NFD')
  .replace(/[\u0300-\u036f]/g, '')
  .toUpperCase()

const isTipoGol = (incidencia) => normalizeText(incidencia?.tipo_evento_partido_descripcion).includes('GOL')

const validarGolesVsMarcador = () => {
  const partido = partidoSeleccionado.value
  if (!partido) return false

  const idLocal = Number(partido.id_equipo_local)
  const idVisitante = Number(partido.id_equipo_visitante)

  const golesLocal = incidenciasMostradas.value.filter(item => isTipoGol(item) && Number(item.id_equipo) === idLocal).length
  const golesVisitante = incidenciasMostradas.value.filter(item => isTipoGol(item) && Number(item.id_equipo) === idVisitante).length

  const marcadorLocal = parseNullableInt(resultadoForm.value.resultado_local)
  const marcadorVisitante = parseNullableInt(resultadoForm.value.resultado_visitante)

  if (marcadorLocal !== golesLocal || marcadorVisitante !== golesVisitante) {
    toast.showToast({
      message: `El marcador no coincide con incidencias de gol. Marcador: ${marcadorLocal}-${marcadorVisitante} | Goles cargados: ${golesLocal}-${golesVisitante}.`,
      type: 'warning',
    })
    return false
  }

  return true
}

const validarResultado = () => {
  const partido = partidoSeleccionado.value
  if (!partido) {
    toast.showToast({ message: 'Selecciona un partido.', type: 'warning' })
    return false
  }

  if (parseNullableInt(resultadoForm.value.resultado_local) === null || parseNullableInt(resultadoForm.value.resultado_visitante) === null) {
    toast.showToast({ message: 'Completa resultado local y visitante.', type: 'warning' })
    return false
  }

  if (resultadoForm.value.hubo_penales) {
    if (parseNullableInt(resultadoForm.value.resultado_penales_local) === null || parseNullableInt(resultadoForm.value.resultado_penales_visitante) === null) {
      toast.showToast({ message: 'Si hubo penales, completa ambos resultados de penales.', type: 'warning' })
      return false
    }
  }

  return true
}

const buildResultadoPayload = () => {
  const partido = partidoSeleccionado.value
  const tieneIncidencias = incidenciasMostradas.value.length > 0

  let idEstadoEvento = Number(partido.id_estado_evento)
  if (tieneIncidencias) {
    idEstadoEvento = ESTADO_FINALIZADO_REPORTADO
  } else {
    idEstadoEvento = ESTADO_FINALIZADO
  }

  return {
    ...partido,
    resultado_local: parseNullableInt(resultadoForm.value.resultado_local),
    resultado_visitante: parseNullableInt(resultadoForm.value.resultado_visitante),
    resultado_penales_local: resultadoForm.value.hubo_penales
      ? parseNullableInt(resultadoForm.value.resultado_penales_local)
      : null,
    resultado_penales_visitante: resultadoForm.value.hubo_penales
      ? parseNullableInt(resultadoForm.value.resultado_penales_visitante)
      : null,
    id_estado_evento: idEstadoEvento,
  }
}

const agregarIncidenciaBorrador = () => {
  const partido = partidoSeleccionado.value
  if (!partido) {
    toast.showToast({ message: 'Selecciona un partido.', type: 'warning' })
    return
  }

  if (!incidenciaForm.value.id_tipo_evento_partido) {
    toast.showToast({ message: 'Selecciona un tipo de incidencia.', type: 'warning' })
    return
  }

  if (!incidenciaForm.value.id_equipo) {
    toast.showToast({ message: 'Selecciona un equipo (local o visitante).', type: 'warning' })
    return
  }

  const jugadorValido = !incidenciaForm.value.id_jugador || jugadoresFiltradosPorEquipo.value.some(
    j => Number(j.id) === Number(incidenciaForm.value.id_jugador)
  )
  if (!jugadorValido) {
    toast.showToast({ message: 'El jugador seleccionado no pertenece al equipo elegido.', type: 'warning' })
    return
  }

  const tipo = tiposEventoPartidoActivos.value.find(t => Number(t.id) === Number(incidenciaForm.value.id_tipo_evento_partido))
  const equipo = equiposPartido.value.find(e => Number(e.id) === Number(incidenciaForm.value.id_equipo))
  const jugador = jugadores.value.find(j => Number(j.id) === Number(incidenciaForm.value.id_jugador))

  incidenciasPendientes.value.unshift({
    id: `temp-${Date.now()}-${Math.random().toString(36).slice(2, 7)}`,
    esPendiente: true,
    id_evento: Number(partido.id),
    id_tipo_evento_partido: Number(incidenciaForm.value.id_tipo_evento_partido),
    id_equipo: Number(incidenciaForm.value.id_equipo),
    id_jugador: parseNullableInt(incidenciaForm.value.id_jugador),
    minuto: parseNullableInt(incidenciaForm.value.minuto),
    observacion: incidenciaForm.value.observacion || null,
    tipo_evento_partido_descripcion: tipo?.descripcion || '-',
    equipo_nombre: equipo?.nombre || '-',
    jugador_nombre: jugador?.nombre || null,
    jugador_apellido: jugador?.apellido || null,
  })

  incidenciaForm.value = emptyIncidenciaForm()
}

const guardarTodo = async () => {
  const partido = partidoSeleccionado.value
  if (!partido) {
    toast.showToast({ message: 'Selecciona un partido.', type: 'warning' })
    return
  }

  if (!validarResultado()) return
  if (!validarGolesVsMarcador()) return

  const payloadResultado = buildResultadoPayload()
  const cantidadNuevas = incidenciasPendientes.value.length
  const cantidadEliminadas = incidenciasEliminadasIds.value.length

  savingTodo.value = true
  try {
    await eventosService.actualizarEvento(payloadResultado)

    for (const id of incidenciasEliminadasIds.value) {
      await eventosService.eliminarEventoPartido(id)
    }

    for (const item of incidenciasPendientes.value) {
      await eventosService.crearEventoPartido({
        id_evento: Number(partido.id),
        id_tipo_evento_partido: Number(item.id_tipo_evento_partido),
        minuto: parseNullableInt(item.minuto),
        observacion: item.observacion || null,
        id_jugador: parseNullableInt(item.id_jugador),
        id_equipo: Number(item.id_equipo),
      })
    }

    incidencias.value = await eventosService.getEventosPartido(partido.id)
    incidenciasPendientes.value = []
    incidenciasEliminadasIds.value = []

    const idx = eventos.value.findIndex(ev => Number(ev.id) === Number(partido.id))
    if (idx !== -1) {
      eventos.value[idx] = {
        ...eventos.value[idx],
        ...payloadResultado,
        estado_evento_descripcion: payloadResultado.id_estado_evento === ESTADO_FINALIZADO_REPORTADO
          ? 'FINALIZADO Y REPORTADO'
          : payloadResultado.id_estado_evento === ESTADO_FINALIZADO
            ? 'FINALIZADO'
          : eventos.value[idx].estado_evento_descripcion,
      }
    }

    resumenGuardado.value = [
      'Se guardaron los cambios del partido.',
      '',
      `Partido: ${partido.equipo_local_nombre || 'Local'} vs ${partido.equipo_visitante_nombre || 'Visitante'}`,
      `Resultado: ${payloadResultado.resultado_local} - ${payloadResultado.resultado_visitante}`,
      payloadResultado.resultado_penales_local !== null || payloadResultado.resultado_penales_visitante !== null
        ? `Penales: ${payloadResultado.resultado_penales_local ?? '-'} - ${payloadResultado.resultado_penales_visitante ?? '-'}`
        : 'Penales: sin definición',
      `Estado guardado: ${payloadResultado.id_estado_evento === ESTADO_FINALIZADO_REPORTADO ? 'Finalizado y reportado' : payloadResultado.id_estado_evento === ESTADO_FINALIZADO ? 'Finalizado' : `Estado ${payloadResultado.id_estado_evento}`}`,
      `Incidencias nuevas: ${cantidadNuevas}`,
      `Incidencias eliminadas: ${cantidadEliminadas}`,
    ].join('\n')

    showResumenModal.value = true
    toast.showToast({ message: 'Todo guardado correctamente.', type: 'success' })
  } catch (error) {
    toast.showToast({ message: getApiMessage(error, 'No se pudieron guardar los cambios.'), type: 'danger' })
  } finally {
    savingTodo.value = false
  }
}

const eliminarIncidencia = (incidencia) => {
  if (incidencia.esPendiente) {
    incidenciasPendientes.value = incidenciasPendientes.value.filter(item => item.id !== incidencia.id)
    return
  }

  if (!window.confirm('¿Quitar esta incidencia? Se eliminará al guardar todo.')) return
  if (!incidenciasEliminadasIds.value.some(id => Number(id) === Number(incidencia.id))) {
    incidenciasEliminadasIds.value.push(Number(incidencia.id))
  }
}

const setEquipoIncidencia = (idEquipo) => {
  incidenciaForm.value.id_equipo = Number(idEquipo)
  incidenciaForm.value.id_jugador = null
}

onMounted(() => {
  cargarDatosIniciales()
})
</script>

<style scoped>
.match-summary {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  padding: 0.85rem 1rem;
}

.loading-overlay-local {
  position: fixed;
  inset: 0;
  background: rgba(255, 255, 255, 0.7);
  z-index: 1050;
}

.equipo-options {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.6rem;
}

.equipo-option {
  border: 1px solid #dbe5f0;
  border-radius: 0.7rem;
  padding: 0.55rem 0.75rem;
  background: #f8fafc;
  display: flex;
  align-items: center;
  gap: 0.6rem;
  transition: all 0.15s ease;
}

.equipo-option.active {
  border-color: #0d6efd;
  background: #e9f2ff;
}

.equipo-option:hover {
  border-color: #9fc1f3;
}

.escudo-thumb {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  object-fit: cover;
  border: 1px solid #dbe5f0;
}

.resumen-pre {
  white-space: pre-wrap;
  font-family: inherit;
  margin: 0;
}
</style>
