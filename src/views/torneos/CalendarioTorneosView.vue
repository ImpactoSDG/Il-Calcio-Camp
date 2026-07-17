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
        </div>

        <ul class="nav nav-tabs mb-3" role="tablist" aria-label="Vista de calendario y programacion">
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
          <li class="nav-item" role="presentation" v-if="Number(idTorneoSeleccionado) > 0">
            <button
              class="nav-link"
              :class="{ active: pestanaActiva === 'programar' }"
              type="button"
              @click="pestanaActiva = 'programar'"
            >
              Programar
            </button>
          </li>
        </ul>

        <div v-if="pestanaActiva === 'calendario'" class="row g-3">
          <div class="col-12 col-lg-4 col-xl-3">
            <div class="cronograma-panel">
              <div class="small fw-semibold text-secondary text-capitalize mb-2">
                Cronograma de {{ mesCronogramaLabel }}
              </div>

              <div class="d-flex flex-column gap-2 mb-3">
                <input
                  v-model.trim="busquedaLista"
                  type="search"
                  class="form-control form-control-sm"
                  placeholder="Buscar por torneo, equipo, cancha o arbitro"
                  aria-label="Buscar eventos en el cronograma"
                />
                <select
                  v-model="ordenLista"
                  class="form-select form-select-sm"
                  aria-label="Ordenar cronograma por fecha"
                >
                  <option value="asc">Fecha ascendente</option>
                  <option value="desc">Fecha descendente</option>
                </select>
              </div>

              <div v-if="eventosCronograma.length === 0" class="alert alert-light border mb-0 small">
                No hay partidos para mostrar este mes.
              </div>

              <div v-else class="cronograma-dias">
                <div v-for="ev in eventosCronograma" :key="`cronograma-evento-${ev.id}`" class="evento-lista-item">
                  <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                    <div class="small text-muted text-capitalize">{{ ev.fechaHoraLabel }}</div>
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

          <div class="col-12 col-lg-8 col-xl-9">
            <TorneoCalendar
              v-model="mesCalendarioActual"
              :eventos="eventosCalendario"
              :torneo-nombre="torneoNombreCalendario"
              :show-title="false"
            />
          </div>
        </div>

        <div v-else-if="pestanaActiva === 'programar'">
          <div v-if="loadingProgramacion" class="text-center py-4 text-muted">
            <span class="spinner-border spinner-border-sm me-2"></span>
            Cargando datos de programación...
          </div>

          <template v-else>
            <div class="alert alert-info small mb-3">
              Acá ves todos los partidos del torneo. Los que todavía no tienen fecha/cancha/árbitro definidos aparecen como
              <span class="badge rounded-pill estado-badge estado-pendiente">Programación pendiente</span>.
              Tildá los que querés programar y usá <strong>"Programar seleccionados"</strong> para que el sistema les busque
              horario automáticamente, o usá <strong>"Editar"</strong> en la fila de un partido para definirlo vos a mano, uno por uno.
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
              <div class="small text-muted">
                Total partidos: <strong>{{ programacionEventos.length }}</strong> · Seleccionados: <strong>{{ selectedProgramacionIds.length }}</strong>
              </div>
              <div class="d-flex gap-2">
                <button class="btn btn-outline-danger" @click="deshacerProgramacion" :disabled="savingProgramacion">
                  <span v-if="savingProgramacion" class="spinner-border spinner-border-sm me-2"></span>
                  Deshacer programación seleccionados
                </button>
                <button class="btn btn-primary" @click="abrirModalProgramacionSeleccionados" :disabled="!selectedProgramacionIds.length || savingProgramacion">
                  <i class="bi bi-calendar-plus me-1"></i>Programar seleccionados
                </button>
              </div>
            </div>

            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0">
                <thead>
                  <tr>
                    <th style="width: 42px" class="text-center">
                      <input
                        type="checkbox"
                        class="form-check-input"
                        :checked="allProgramacionSelected"
                        :indeterminate.prop="hasSomeProgramacionSelected && !allProgramacionSelected"
                        @change="toggleSeleccionTodosProgramacion"
                      />
                    </th>
                    <th>Partido</th>
                    <th>Equipo local</th>
                    <th>Equipo visitante</th>
                    <th>Estado</th>
                    <th>Fecha/Hora</th>
                    <th>Cancha</th>
                    <th>Árbitro</th>
                    <th class="text-end">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="(ev, evIndex) in programacionEventos"
                    :key="ev.id"
                    :class="{ 'programacion-row-selected': selectedProgramacionIds.includes(Number(ev.id)) }"
                    style="cursor: pointer;"
                    @click="toggleSeleccionProgramacion(ev, evIndex, $event)"
                  >
                    <td class="text-center">
                      <input type="checkbox" class="form-check-input" :checked="selectedProgramacionIds.includes(Number(ev.id))" @click.stop="toggleSeleccionProgramacion(ev, evIndex, $event)" />
                    </td>
                    <td>{{ ev.titulo }}</td>
                    <td>{{ ev.equipo_local_nombre || 'Por definir' }}</td>
                    <td>{{ ev.equipo_visitante_nombre || 'Por definir' }}</td>
                    <td>
                      <span class="badge rounded-pill" :class="getEstadoEventoBadgeClass(ev)">{{ ev.estado_evento_descripcion || 'Sin estado' }}</span>
                    </td>
                    <td @click="isEditingProgramacion(ev.id) && $event.stopPropagation()">
                      <template v-if="isEditingProgramacion(ev.id)">
                        <input
                          type="datetime-local"
                          class="form-control form-control-sm"
                          :value="getProgramacionDraft(ev.id).fecha_hora_inicio"
                          @change="setProgramacionDraftField(ev.id, 'fecha_hora_inicio', $event.target.value)"
                        />
                      </template>
                      <template v-else>
                        <span>{{ formatearFechaHora(ev.fecha_hora_inicio) }}</span>
                      </template>
                    </td>
                    <td @click="isEditingProgramacion(ev.id) && $event.stopPropagation()">
                      <template v-if="isEditingProgramacion(ev.id)">
                        <select
                          class="form-select form-select-sm"
                          :value="getProgramacionDraft(ev.id).id_cancha"
                          @change="setProgramacionDraftField(ev.id, 'id_cancha', $event.target.value ? Number($event.target.value) : null)"
                        >
                          <option :value="null">Seleccionar cancha</option>
                          <option v-for="c in (programacionData?.canchas || [])" :key="c.id" :value="Number(c.id)">
                            {{ c.nombre }}
                          </option>
                        </select>
                      </template>
                      <template v-else>
                        <span>{{ getProgramacionCanchaLabel(ev) }}</span>
                      </template>
                    </td>
                    <td @click="isEditingProgramacion(ev.id) && $event.stopPropagation()">
                      <template v-if="isEditingProgramacion(ev.id)">
                        <select
                          class="form-select form-select-sm"
                          :value="getProgramacionDraft(ev.id).id_arbitro"
                          @change="setProgramacionDraftField(ev.id, 'id_arbitro', $event.target.value ? Number($event.target.value) : null)"
                        >
                          <option :value="null">Seleccionar árbitro</option>
                          <option v-for="a in (programacionData?.arbitros || [])" :key="a.id" :value="Number(a.id)">
                            {{ a.nombre_completo || `${a.apellido || ''} ${a.nombre || ''}`.trim() }}
                          </option>
                        </select>
                      </template>
                      <template v-else>
                        <span>{{ getProgramacionArbitroLabel(ev) }}</span>
                      </template>
                    </td>
                    <td class="text-end" @click.stop>
                      <div class="d-inline-flex gap-2">
                        <button
                          v-if="!isEditingProgramacion(ev.id)"
                          class="btn btn-sm btn-outline-primary"
                          @click.stop="editarProgramacionEvento(ev)"
                          :disabled="savingProgramacion"
                        >
                          Editar
                        </button>

                        <template v-else>
                          <button
                            class="btn btn-sm btn-outline-secondary"
                            @click.stop="cancelarEdicionProgramacionEvento(ev)"
                            :disabled="savingProgramacion"
                          >
                            Cancelar
                          </button>
                          <button
                            class="btn btn-sm btn-success"
                            @click.stop="guardarProgramacionEvento(ev.id)"
                            :disabled="savingProgramacion"
                          >
                            Guardar
                          </button>
                        </template>
                      </div>
                    </td>
                  </tr>
                  <tr v-if="!programacionEventos.length">
                    <td colspan="9" class="text-center text-muted py-3">No hay partidos para mostrar.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </template>
        </div>
      </div>
    </div>

    <Teleport to="body">
      <div v-if="showProgramacionModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Programar partidos seleccionados</h5>
              <button type="button" class="btn-close" @click="showProgramacionModal = false"></button>
            </div>

            <div class="modal-body">
              <div class="alert alert-info d-flex gap-2 mb-3">
                <i class="bi bi-info-circle-fill mt-1"></i>
                <div class="small">
                  Vas a programar <strong>{{ selectedProgramacionIds.length }}</strong> partido(s) de forma automática.
                  El sistema va a buscar huecos libres dentro de los días y horarios que definas más abajo, y le va a asignar a cada
                  partido una fecha, una cancha y un árbitro, sin repetir cancha ni árbitro en el mismo horario.
                  <strong>No elegís vos qué partido va en qué horario</strong>: se completan en orden, uno por uno, en el primer hueco libre que encuentran.
                </div>
              </div>

              <div class="row g-3">
                <div class="col-12 col-md-4">
                  <label class="form-label small mb-1">¿Desde qué fecha buscar?</label>
                  <input type="date" class="form-control" v-model="programacionForm.fecha_inicio" />
                  <div class="form-text">No se va a programar ningún partido antes de esta fecha.</div>
                </div>
                <div class="col-12 col-md-4">
                  <label class="form-label small mb-1">¿Hasta qué fecha buscar? <span class="text-muted">(opcional)</span></label>
                  <input type="date" class="form-control" v-model="programacionForm.fecha_hasta" :min="programacionForm.fecha_inicio" />
                  <div class="form-text">Si la dejás vacía, busca hasta 1 año hacia adelante.</div>
                </div>
                <div class="col-12 col-md-4">
                  <label class="form-label small mb-1">Duración de cada partido (minutos)</label>
                  <input type="number" min="20" max="240" step="5" class="form-control" v-model.number="programacionForm.duracion_minutos" />
                  <div class="form-text">Tiempo que ocupa la cancha por partido, para no superponer el siguiente.</div>
                </div>
              </div>

              <div class="mt-3">
                <div class="small fw-semibold mb-1">¿Qué días y horarios se pueden usar?</div>
                <div class="small text-muted mb-2">
                  Tildá los días en los que se puede jugar y definí el horario permitido. Fuera de estos rangos no se va a programar nada.
                </div>
                <div class="row g-2">
                  <div v-for="franja in programacionForm.franjas" :key="franja.dia_semana" class="col-12 col-lg-6">
                    <div class="franja-row">
                      <div class="d-flex align-items-center gap-2">
                        <input type="checkbox" v-model="franja.activa" />
                        <span class="small fw-semibold">{{ franja.nombre }}</span>
                      </div>
                      <div class="d-flex align-items-center gap-2">
                        <input type="time" class="form-control form-control-sm" style="max-width: 120px" v-model="franja.hora_inicio" :disabled="!franja.activa" />
                        <span class="small text-muted">a</span>
                        <input type="time" class="form-control form-control-sm" style="max-width: 120px" v-model="franja.hora_fin" :disabled="!franja.activa" />
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="row g-3 mt-1">
                <div class="col-12 col-lg-6">
                  <div class="small fw-semibold mb-1">¿Qué canchas se pueden usar?</div>
                  <div class="small text-muted mb-2">Solo se van a usar las canchas tildadas para completar los horarios.</div>
                  <div class="selector-box">
                    <label v-for="c in (programacionData?.canchas || [])" :key="c.id" class="selector-item">
                      <input type="checkbox" :value="Number(c.id)" v-model="programacionForm.id_canchas" />
                      <span>{{ c.nombre }}</span>
                    </label>
                  </div>
                </div>
                <div class="col-12 col-lg-6">
                  <div class="small fw-semibold mb-1">¿Qué árbitros se pueden usar?</div>
                  <div class="small text-muted mb-2">Se van a repartir entre los partidos rotando, sin cruzarlos con horarios en los que ya estén ocupados.</div>
                  <div class="selector-box">
                    <label v-for="a in (programacionData?.arbitros || [])" :key="a.id" class="selector-item">
                      <input type="checkbox" :value="Number(a.id)" v-model="programacionForm.id_arbitros" />
                      <span>{{ a.nombre_completo || `${a.apellido || ''} ${a.nombre || ''}`.trim() }}</span>
                    </label>
                  </div>
                </div>
              </div>

              <div class="alert alert-light border mt-3 mb-0 small">
                <strong>Resumen:</strong> {{ resumenProgramacion }}
              </div>

              <div class="alert alert-warning small mt-2 mb-0 d-flex gap-2">
                <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                <div>
                  <strong>Ojo:</strong> si no hay suficientes horarios libres con estas condiciones (pocos días, pocas canchas o pocos árbitros),
                  algunos partidos van a quedar <strong>sin programar</strong> y te lo va a avisar al terminar. En ese caso podés ampliar el rango
                  de fechas, sumar canchas/árbitros o volver a intentarlo. Si un partido queda en un horario que no te sirve, lo podés corregir
                  a mano después con el botón <strong>"Editar"</strong> de la tabla, o revertirlo con <strong>"Deshacer programación seleccionados"</strong>.
                </div>
              </div>
            </div>

            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" @click="showProgramacionModal = false">Cancelar</button>
              <button class="btn btn-primary" @click="programarSeleccionados" :disabled="savingProgramacion || !selectedProgramacionIds.length">
                <span v-if="savingProgramacion" class="spinner-border spinner-border-sm me-2"></span>
                Programar {{ selectedProgramacionIds.length }} partido(s)
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
import { useRoute } from 'vue-router'
import datosMaestrosService from '@/services/datosMaestrosService'
import planTorneoService from '@/services/torneos/planTorneoService'
import { useToastStore } from '@/stores/toastStore'
import TorneoCalendar from '@/components/torneos/TorneoCalendar.vue'

const route = useRoute()
const toast = useToastStore()

const torneos = ref([])
const idTorneoSeleccionado = ref(0)
const detalle = ref(null)
const loadingTorneos = ref(false)
const loadingDetalle = ref(false)
const errorMensaje = ref('')
const pestanaActiva = ref('calendario')
const busquedaLista = ref('')
const ordenLista = ref('asc')

const getApiMessage = (error, fallback) => error?.response?.data?.message || fallback

const programacionData = ref(null)
const loadingProgramacion = ref(false)
const savingProgramacion = ref(false)
const selectedProgramacionIds = ref([])
const lastClickedProgramacionIdx = ref(null)
const showProgramacionModal = ref(false)
const programacionDrafts = ref({})
const editingProgramacionIds = ref([])
const programacionForm = ref({
  fase_programar: 'todas',
  fecha_inicio: new Date().toISOString().slice(0, 10),
  fecha_hasta: '',
  duracion_minutos: 70,
  max_dias_busqueda: 365,
  id_canchas: [],
  id_arbitros: [],
  id_eventos: [],
  franjas: [
    { dia_semana: 1, nombre: 'Lunes', activa: true, hora_inicio: '13:00', hora_fin: '18:00' },
    { dia_semana: 2, nombre: 'Martes', activa: true, hora_inicio: '13:00', hora_fin: '18:00' },
    { dia_semana: 3, nombre: 'Miércoles', activa: true, hora_inicio: '13:00', hora_fin: '18:00' },
    { dia_semana: 4, nombre: 'Jueves', activa: true, hora_inicio: '13:00', hora_fin: '18:00' },
    { dia_semana: 5, nombre: 'Viernes', activa: true, hora_inicio: '13:00', hora_fin: '18:00' },
    { dia_semana: 6, nombre: 'Sábado', activa: true, hora_inicio: '13:00', hora_fin: '18:00' },
    { dia_semana: 7, nombre: 'Domingo', activa: true, hora_inicio: '13:00', hora_fin: '18:00' },
  ],
})

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
      const fechaLabel = dt.toLocaleDateString('es-AR', { weekday: 'long', day: 'numeric', month: 'long' })
      const horaLabel = dt.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit', hour12: false })
      return {
        ...ev,
        fecha_dt: dt,
        fechaHoraLabel: `${fechaLabel} - ${horaLabel} hs`,
      }
    })
)

const mesCalendarioActual = ref(new Date(new Date().getFullYear(), new Date().getMonth(), 1))

const mesCronogramaLabel = computed(() =>
  mesCalendarioActual.value.toLocaleDateString('es-AR', { month: 'long', year: 'numeric' })
)

const eventosCronograma = computed(() => {
  const anio = mesCalendarioActual.value.getFullYear()
  const mes = mesCalendarioActual.value.getMonth()
  const termino = busquedaLista.value.trim().toLowerCase()

  const eventosDelMes = eventosLista.value.filter((ev) => {
    if (ev.fecha_dt.getFullYear() !== anio || ev.fecha_dt.getMonth() !== mes) return false
    if (!termino) return true

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

  return [...eventosDelMes].sort((a, b) => {
    if (ordenLista.value === 'desc') return b.fecha_dt.getTime() - a.fecha_dt.getTime()
    return a.fecha_dt.getTime() - b.fecha_dt.getTime()
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

const toDateTimeLocal = (value) => {
  if (!value) return ''
  const txt = String(value).trim().replace(' ', 'T')
  return txt.length >= 16 ? txt.slice(0, 16) : txt
}

const buildProgramacionDrafts = () => {
  const next = {}
  for (const ev of (programacionData.value?.eventos || [])) {
    const id = Number(ev.id)
    next[id] = {
      fecha_hora_inicio: toDateTimeLocal(ev.fecha_hora_inicio),
      id_cancha: ev.id_cancha ? Number(ev.id_cancha) : null,
      id_arbitro: ev.id_arbitro ? Number(ev.id_arbitro) : null,
    }
  }
  programacionDrafts.value = next
  const permitidos = new Set(Object.keys(next).map(Number))
  selectedProgramacionIds.value = selectedProgramacionIds.value.filter(id => permitidos.has(Number(id)))
  editingProgramacionIds.value = editingProgramacionIds.value.filter(id => permitidos.has(Number(id)))
}

const cargarProgramacionData = async (idTorneo) => {
  const id = Number(idTorneo || 0)
  if (!id) {
    programacionData.value = null
    return
  }
  loadingProgramacion.value = true
  try {
    programacionData.value = await planTorneoService.getProgramacionData(id, programacionForm.value.fase_programar)
    buildProgramacionDrafts()
    programacionForm.value.id_canchas = (programacionData.value?.canchas || []).map(c => Number(c.id))
    programacionForm.value.id_arbitros = (programacionData.value?.arbitros || []).map(a => Number(a.id))
  } catch (error) {
    programacionData.value = null
    toast.showToast({ message: getApiMessage(error, 'No se pudo cargar la programación.'), type: 'danger' })
  } finally {
    loadingProgramacion.value = false
  }
}

const refrescarDetalleCalendario = async (idTorneo) => {
  const id = Number(idTorneo || 0)
  if (!id) return
  try {
    detalle.value = await planTorneoService.getDetalleGestion(id)
  } catch (error) {
    toast.showToast({ message: getApiMessage(error, 'No se pudo actualizar el calendario.'), type: 'danger' })
  }
}

const refrescarProgramacionYCalendario = async (idTorneo) => {
  await Promise.all([
    cargarProgramacionData(idTorneo),
    refrescarDetalleCalendario(idTorneo),
  ])
}

const getProgramacionDraft = (idEvento) => {
  const id = Number(idEvento)
  if (!programacionDrafts.value[id]) {
    programacionDrafts.value[id] = {
      fecha_hora_inicio: '',
      id_cancha: null,
      id_arbitro: null,
    }
  }
  return programacionDrafts.value[id]
}

const setProgramacionDraftField = (idEvento, field, value) => {
  const draft = getProgramacionDraft(idEvento)
  draft[field] = value
}

const isEditingProgramacion = (idEvento) => editingProgramacionIds.value.includes(Number(idEvento))

const resetProgramacionDraftFromEvento = (evento) => {
  const id = Number(evento?.id || 0)
  if (!id) return
  programacionDrafts.value[id] = {
    fecha_hora_inicio: toDateTimeLocal(evento?.fecha_hora_inicio),
    id_cancha: evento?.id_cancha ? Number(evento.id_cancha) : null,
    id_arbitro: evento?.id_arbitro ? Number(evento.id_arbitro) : null,
  }
}

const editarProgramacionEvento = (evento) => {
  const id = Number(evento?.id || 0)
  if (!id) return
  resetProgramacionDraftFromEvento(evento)
  if (!isEditingProgramacion(id)) {
    editingProgramacionIds.value = [...editingProgramacionIds.value, id]
  }
}

const cancelarEdicionProgramacionEvento = (evento) => {
  const id = Number(evento?.id || 0)
  if (!id) return
  resetProgramacionDraftFromEvento(evento)
  editingProgramacionIds.value = editingProgramacionIds.value.filter(item => item !== id)
}

const getCanchaNombreById = (idCancha) => {
  const id = Number(idCancha || 0)
  if (!id) return ''
  const cancha = (programacionData.value?.canchas || []).find(item => Number(item.id) === id)
  return cancha?.nombre || ''
}

const getArbitroNombreById = (idArbitro) => {
  const id = Number(idArbitro || 0)
  if (!id) return ''
  const arbitro = (programacionData.value?.arbitros || []).find(item => Number(item.id) === id)
  return arbitro?.nombre_completo || `${arbitro?.apellido || ''} ${arbitro?.nombre || ''}`.trim()
}

const getProgramacionCanchaLabel = (evento) => {
  const draft = getProgramacionDraft(evento?.id)
  return getCanchaNombreById(draft?.id_cancha || evento?.id_cancha) || 'Sin definir'
}

const getProgramacionArbitroLabel = (evento) => {
  const draft = getProgramacionDraft(evento?.id)
  return getArbitroNombreById(draft?.id_arbitro || evento?.id_arbitro) || 'Sin definir'
}

const programacionEventos = computed(() =>
  (programacionData.value?.eventos || []).map(ev => ({ ...ev, id: Number(ev.id) }))
)

const allProgramacionSelected = computed(() =>
  programacionEventos.value.length > 0 && selectedProgramacionIds.value.length === programacionEventos.value.length
)

const hasSomeProgramacionSelected = computed(() =>
  selectedProgramacionIds.value.length > 0 && selectedProgramacionIds.value.length < programacionEventos.value.length
)

const toggleSeleccionTodosProgramacion = () => {
  if (allProgramacionSelected.value) {
    selectedProgramacionIds.value = []
    return
  }
  selectedProgramacionIds.value = programacionEventos.value.map(ev => Number(ev.id))
}

const toggleSeleccionProgramacion = (ev, index, event) => {
  const id = Number(ev.id)
  if (event.shiftKey && lastClickedProgramacionIdx.value !== null) {
    const from = Math.min(lastClickedProgramacionIdx.value, index)
    const to = Math.max(lastClickedProgramacionIdx.value, index)
    const rangeIds = programacionEventos.value.slice(from, to + 1).map(e => Number(e.id))
    const allSelected = rangeIds.every(rid => selectedProgramacionIds.value.includes(rid))
    if (allSelected) {
      selectedProgramacionIds.value = selectedProgramacionIds.value.filter(rid => !rangeIds.includes(rid))
    } else {
      const merged = new Set([...selectedProgramacionIds.value, ...rangeIds])
      selectedProgramacionIds.value = [...merged]
    }
  } else {
    if (selectedProgramacionIds.value.includes(id)) {
      selectedProgramacionIds.value = selectedProgramacionIds.value.filter(rid => rid !== id)
    } else {
      selectedProgramacionIds.value.push(id)
    }
    lastClickedProgramacionIdx.value = index
  }
}

const formatearFechaCorta = (fechaYmd) => {
  if (!fechaYmd) return ''
  const dt = new Date(`${fechaYmd}T00:00:00`)
  if (Number.isNaN(dt.getTime())) return ''
  return dt.toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

const resumenProgramacion = computed(() => {
  const franjasActivas = (programacionForm.value.franjas || []).filter(f => f.activa)
  const diasTexto = franjasActivas.length
    ? franjasActivas.map(f => f.nombre).join(', ')
    : 'ningún día seleccionado todavía'

  const cantCanchas = (programacionForm.value.id_canchas || []).length
  const cantArbitros = (programacionForm.value.id_arbitros || []).length

  const fechaInicioTexto = formatearFechaCorta(programacionForm.value.fecha_inicio) || 'hoy'
  const fechaHastaTexto = programacionForm.value.fecha_hasta
    ? `hasta el ${formatearFechaCorta(programacionForm.value.fecha_hasta)}`
    : 'sin fecha límite (busca hasta 1 año hacia adelante)'

  return `Se va a intentar programar ${selectedProgramacionIds.value.length} partido(s), buscando horarios libres desde el `
    + `${fechaInicioTexto} ${fechaHastaTexto}, los días ${diasTexto}, usando ${cantCanchas} cancha(s) y ${cantArbitros} árbitro(s) `
    + `disponibles. Cada partido va a ocupar ${programacionForm.value.duracion_minutos || 0} minutos de cancha.`
})

const abrirModalProgramacionSeleccionados = () => {
  if (!selectedProgramacionIds.value.length) {
    toast.showToast({ message: 'Selecciona al menos un partido.', type: 'warning' })
    return
  }
  const fechaInicio = detalle.value?.torneo?.fecha_inicio
  if (fechaInicio) {
    programacionForm.value.fecha_inicio = String(fechaInicio).slice(0, 10)
  }
  showProgramacionModal.value = true
}

const programarAutomatico = async () => {
  if (!idTorneoSeleccionado.value) return

  const franjas = (programacionForm.value.franjas || [])
    .filter(f => f.activa)
    .map(f => ({ dia_semana: Number(f.dia_semana), hora_inicio: f.hora_inicio, hora_fin: f.hora_fin }))

  if (!franjas.length) {
    toast.showToast({ message: 'Activa al menos una franja horaria para programar.', type: 'warning' })
    return
  }
  if (!(programacionForm.value.id_canchas || []).length) {
    toast.showToast({ message: 'Selecciona al menos una cancha.', type: 'warning' })
    return
  }
  if (!(programacionForm.value.id_arbitros || []).length) {
    toast.showToast({ message: 'Selecciona al menos un árbitro.', type: 'warning' })
    return
  }

  savingProgramacion.value = true
  try {
    const payload = {
      id_torneo: idTorneoSeleccionado.value,
      fase_programar: programacionForm.value.fase_programar,
      fecha_inicio: programacionForm.value.fecha_inicio,
      duracion_minutos: Number(programacionForm.value.duracion_minutos || 70),
      max_dias_busqueda: Number(programacionForm.value.max_dias_busqueda || 365),
      id_canchas: (programacionForm.value.id_canchas || []).map(Number),
      id_arbitros: (programacionForm.value.id_arbitros || []).map(Number),
      id_eventos: (programacionForm.value.id_eventos || []).map(Number),
      franjas,
      force_reprogramar: false,
    }
    if (programacionForm.value.fecha_hasta) {
      payload.fecha_hasta = programacionForm.value.fecha_hasta
    }
    const resp = await planTorneoService.autoProgramar(payload)
    const sinProgramar = Number(resp?.sin_programar || 0)

    toast.showToast({
      message: sinProgramar > 0
        ? `${resp?.programados || 0} partido(s) programados. ${sinProgramar} quedaron sin programar por falta de horarios libres: probá ampliar el rango de fechas, días u horarios, o sumar canchas/árbitros.`
        : `${resp?.programados || 0} partido(s) programados automáticamente.`,
      type: sinProgramar > 0 ? 'warning' : 'success',
    })

    await refrescarProgramacionYCalendario(idTorneoSeleccionado.value)
  } catch (error) {
    toast.showToast({ message: getApiMessage(error, 'No se pudo ejecutar la programación automática.'), type: 'danger' })
  } finally {
    savingProgramacion.value = false
  }
}

const programarSeleccionados = async () => {
  if (!selectedProgramacionIds.value.length) {
    toast.showToast({ message: 'Selecciona al menos un partido para programar.', type: 'warning' })
    return
  }

  programacionForm.value.fase_programar = 'seleccionados'
  programacionForm.value.id_eventos = selectedProgramacionIds.value.map(Number)
  await programarAutomatico()
  programacionForm.value.fase_programar = 'todas'
  showProgramacionModal.value = false
}

const deshacerProgramacion = async () => {
  if (!idTorneoSeleccionado.value) return

  if (!selectedProgramacionIds.value.length) {
    toast.showToast({ message: 'Selecciona al menos un partido para deshacer la programación.', type: 'warning' })
    return
  }

  const ok = window.confirm(
    `Se deshará la programación de ${selectedProgramacionIds.value.length} partido(s) seleccionados en estado Programado.\n` +
      'Esto limpiará fecha/hora, cancha y árbitro, y los devolverá a Programación pendiente.\n\n' +
      '¿Deseas continuar?'
  )
  if (!ok) return

  savingProgramacion.value = true
  try {
    const resp = await planTorneoService.deshacerProgramacion({
      id_torneo: idTorneoSeleccionado.value,
      fase_programar: 'seleccionados',
      id_eventos: selectedProgramacionIds.value.map(Number),
    })

    toast.showToast({
      message: `${resp?.revertidos || 0} partidos pasaron a Programación pendiente.`,
      type: 'success',
    })

    selectedProgramacionIds.value = []
    lastClickedProgramacionIdx.value = null
    programacionForm.value.id_eventos = []
    await refrescarProgramacionYCalendario(idTorneoSeleccionado.value)
  } catch (error) {
    toast.showToast({ message: getApiMessage(error, 'No se pudo deshacer la programación.'), type: 'danger' })
  } finally {
    savingProgramacion.value = false
  }
}

const guardarProgramacionEvento = async (idEvento) => {
  if (!idTorneoSeleccionado.value || !idEvento) return

  const draft = getProgramacionDraft(idEvento)
  if (!draft.fecha_hora_inicio || !draft.id_cancha || !draft.id_arbitro) {
    toast.showToast({ message: 'Debes completar fecha/hora, cancha y árbitro.', type: 'warning' })
    return
  }

  savingProgramacion.value = true
  try {
    await planTorneoService.actualizarProgramacionEvento({
      id_torneo: idTorneoSeleccionado.value,
      id_evento: Number(idEvento),
      fecha_hora_inicio: draft.fecha_hora_inicio,
      id_cancha: Number(draft.id_cancha),
      id_arbitro: Number(draft.id_arbitro),
      duracion_minutos: Number(programacionForm.value.duracion_minutos || 70),
    })

    editingProgramacionIds.value = editingProgramacionIds.value.filter(item => item !== Number(idEvento))
    toast.showToast({ message: 'Partido actualizado correctamente.', type: 'success' })
    await refrescarProgramacionYCalendario(idTorneoSeleccionado.value)
  } catch (error) {
    toast.showToast({ message: getApiMessage(error, 'No se pudo actualizar el partido.'), type: 'danger' })
  } finally {
    savingProgramacion.value = false
  }
}

const cargarDetalle = async (idTorneo) => {
  const id = Number(idTorneo || 0)
  loadingDetalle.value = true
  errorMensaje.value = ''

  try {
    if (id > 0) {
      detalle.value = await planTorneoService.getDetalleGestion(id)
      await cargarProgramacionData(id)
      return
    }

    programacionData.value = null
    if (pestanaActiva.value === 'programar') {
      pestanaActiva.value = 'calendario'
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
  const idTorneoDesdeQuery = Number(route.query?.id_torneo || 0)
  if (idTorneoDesdeQuery > 0) {
    idTorneoSeleccionado.value = idTorneoDesdeQuery
  }
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

.cronograma-panel {
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #fff;
  padding: 0.9rem;
  height: 100%;
  max-height: 640px;
  overflow-y: auto;
}

.cronograma-dias {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}

.programacion-row-selected {
  background: #eff6ff;
}

.franja-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 8px 10px;
  background: #fff;
}

.selector-box {
  max-height: 180px;
  overflow: auto;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  background: #fff;
  padding: 8px;
}

.selector-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 4px 2px;
  font-size: 0.9rem;
}
</style>