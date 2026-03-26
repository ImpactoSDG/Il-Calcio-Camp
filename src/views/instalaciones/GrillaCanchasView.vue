<template>
  <div class="gc-page container-fluid p-4 bg-white min-vh-100 position-relative animate-fade-in">

    <!-- ── Header ─────────────────────────────────────────── -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
      <div class="d-flex align-items-center">
        <button @click="$router.back()" class="btn-back-arrow me-2" title="Volver">
          <i class="bi bi-arrow-left"></i>
        </button>
        <h1 class="h5 fw-bold mb-0 text-secondary">GRILLA DE CANCHAS DE F11</h1>
      </div>
    </div>

    <!-- ── Date navigator ────────────────────────────────── -->
    <div class="date-nav">
      <button class="date-arrow" @click="prevDay" title="Día anterior">
        <i class="bi bi-chevron-left"></i>
      </button>

      <div class="date-center-wrap" ref="calendarAnchor">
        <div class="date-center">
          <div class="date-dayname">{{ dayName }}</div>
          <div class="date-label">{{ dateLabel }}</div>
        </div>
        <button
          class="date-calendar-btn"
          type="button"
          title="Seleccionar fecha"
          :aria-expanded="calendarOpen ? 'true' : 'false'"
          @click="toggleCalendar"
        >
          <i class="bi bi-calendar3"></i>
        </button>

        <div v-if="calendarOpen" class="calendar-popover" @click.stop>
          <div class="calendar-header">
            <button class="cal-nav" type="button" @click="prevMonth" title="Mes anterior">
              <i class="bi bi-chevron-left"></i>
            </button>
            <div class="cal-title">{{ calendarMonthLabel }}</div>
            <button class="cal-nav" type="button" @click="nextMonth" title="Mes siguiente">
              <i class="bi bi-chevron-right"></i>
            </button>
          </div>
          <div class="calendar-weekdays">
            <span v-for="label in WEEKDAY_LABELS" :key="label">{{ label }}</span>
          </div>
          <div class="calendar-grid">
            <button
              v-for="day in calendarDays"
              :key="day.iso + (day.inMonth ? '-m' : '-o')"
              class="calendar-day"
              type="button"
              :class="{
                'is-other': !day.inMonth,
                'is-today': day.isToday,
                'is-selected': day.isSelected,
              }"
              @click="selectCalendarDay(day)"
            >
              <span class="calendar-day-num">{{ day.day }}</span>
              <span
                v-if="day.eventCount"
                class="calendar-event-dot"
                :class="{ 'has-count': day.eventCount > 1 }"
                :title="`${day.eventCount} eventos`"
              >
                <span v-if="day.eventCount > 1" class="calendar-event-count">{{ day.eventCount }}</span>
              </span>
            </button>
          </div>
          <div class="calendar-footer">
            <button class="cal-today-btn" type="button" @click="goToday">Hoy</button>
          </div>
        </div>
      </div>

      <button class="date-arrow" @click="nextDay" title="Día siguiente">
        <i class="bi bi-chevron-right"></i>
      </button>
    </div>

    <!-- ── Horarios bar ────────────────────────────────────── -->
    <div class="horarios-bar">
      <template v-if="horariosDisponibles.length">
        <button
          v-for="h in horariosDisponibles"
          :key="h"
          class="horario-chip"
          :class="{ active: horarioSel === h }"
          @click="horarioSel = h"
        >
          {{ h }}
        </button>
      </template>
      <div v-else class="horarios-empty">No hay eventos en la fecha seleccionada.</div>
    </div>

    <div class="cancha-filter-row">
      <div class="cancha-filter-wrap" ref="canchaFilterAnchor">
        <button
          class="cancha-filter-btn"
          type="button"
          :aria-expanded="canchaFilterOpen ? 'true' : 'false'"
          @click="toggleCanchaFilter"
        >
          <i class="bi bi-funnel"></i>
          {{ canchaFilterLabel }}
        </button>

        <div v-if="canchaFilterOpen" class="cancha-filter-popover" @click.stop>
          <div class="cancha-filter-popover-title">Mostrar canchas</div>

          <label class="cancha-filter-item">
            <input
              type="checkbox"
              :checked="isAllCanchasSelected"
              @change="toggleAllCanchas"
            />
            <span>Todas</span>
          </label>

          <label
            v-for="cancha in canchas"
            :key="`filter-${cancha.id}`"
            class="cancha-filter-item"
          >
            <input
              type="checkbox"
              :checked="selectedCanchasIds.includes(Number(cancha.id))"
              @change="toggleCanchaSelection(Number(cancha.id))"
            />
            <span>{{ cancha.nombre }}</span>
          </label>
        </div>
      </div>
    </div>

    <!-- ── Loading ────────────────────────────────────────── -->
    <div v-if="loading" class="gc-loading">
      <span class="spinner-border me-2"></span>Cargando…
    </div>

    <!-- ── Cancha grid ────────────────────────────────────── -->
    <div v-else class="cancha-grid">

      <div v-for="cancha in canchasMostradas" :key="cancha.id" class="cancha-panel">

        <!-- Cancha title -->
        <div class="cancha-label">
          <i class="bi bi-geo-alt-fill me-1"></i>{{ cancha.nombre }}
        </div>

        <!-- Field + overlay -->
        <div class="field-wrap">

          <!-- CSS-striped green background + SVG markings -->
          <svg
            class="field-svg"
            viewBox="0 0 360 200"
            preserveAspectRatio="xMidYMid meet"
            xmlns="http://www.w3.org/2000/svg"
          >
            <!-- Outer border -->
            <rect x="8" y="8" width="344" height="184"
                  fill="none" stroke="rgba(255,255,255,0.75)" stroke-width="2"/>
            <!-- Center line -->
            <line x1="180" y1="8" x2="180" y2="192"
                  stroke="rgba(255,255,255,0.75)" stroke-width="2"/>
            <!-- Center circle -->
            <circle cx="180" cy="100" r="30"
                    fill="none" stroke="rgba(255,255,255,0.75)" stroke-width="2"/>
            <circle cx="180" cy="100" r="3" fill="rgba(255,255,255,0.75)"/>
            <!-- Left penalty area -->
            <rect x="8" y="50" width="56" height="100"
                  fill="none" stroke="rgba(255,255,255,0.7)" stroke-width="1.8"/>
            <rect x="8" y="70" width="22" height="60"
                  fill="none" stroke="rgba(255,255,255,0.55)" stroke-width="1.3"/>
            <circle cx="43" cy="100" r="2.5" fill="rgba(255,255,255,0.55)"/>
            <!-- Right penalty area -->
            <rect x="296" y="50" width="56" height="100"
                  fill="none" stroke="rgba(255,255,255,0.7)" stroke-width="1.8"/>
            <rect x="330" y="70" width="22" height="60"
                  fill="none" stroke="rgba(255,255,255,0.55)" stroke-width="1.3"/>
            <circle cx="317" cy="100" r="2.5" fill="rgba(255,255,255,0.55)"/>
          </svg>

          <!-- Match info overlaid on field -->
          <div class="match-overlay">

            <template v-if="getPartido(cancha.id)">
              <!-- Top: torneo + titulo centrado, estado en esquina -->
              <div class="match-top-bar">
                <div class="match-top-center">
                  <div class="match-torneo-title">Torneo: {{ getPartido(cancha.id).torneo_nombre }}</div>
                  <div v-if="getPartido(cancha.id).titulo" class="match-evento-titulo">{{ getPartido(cancha.id).titulo }}</div>
                </div>
                <span class="meta-estado" :class="estadoClass(getPartido(cancha.id).id_estado_evento)">
                  {{ getPartido(cancha.id).estado_evento_descripcion }}
                </span>
              </div>
              <!-- Teams row -->
              <div class="teams-row">

                <!-- Local -->
                <div class="team-block">
                  <img
                    v-if="getEscudo(getPartido(cancha.id).id_equipo_local)"
                    :src="resolveEscudoUrl(getEscudo(getPartido(cancha.id).id_equipo_local))"
                    class="escudo-img" alt=""
                  />
                  <i v-else class="bi bi-shield-fill escudo-ph"></i>
                  <span class="team-nm local-nm">
                    {{ getPartido(cancha.id).equipo_local_nombre || 'Local' }}
                  </span>
                  <div
                    v-if="getIncidenciasEquipo(getPartido(cancha.id), getPartido(cancha.id).id_equipo_local).length"
                    class="incidencias-mini"
                  >
                    <span
                      v-for="item in getIncidenciasEquipo(getPartido(cancha.id), getPartido(cancha.id).id_equipo_local)"
                      :key="item.uid"
                      class="incidencia-mini-chip"
                      :class="`incidencia-mini-${item.tone}`"
                    >
                      <span v-if="item.icon === 'ball'" class="incidencia-mini-icon" aria-hidden="true">⚽</span>
                      <span v-else-if="item.icon === 'yellow-card'" class="incidencia-mini-card incidencia-mini-card-yellow" aria-hidden="true"></span>
                      <span v-else-if="item.icon === 'red-card'" class="incidencia-mini-card incidencia-mini-card-red" aria-hidden="true"></span>
                      <i v-else class="bi bi-record-circle incidencia-mini-icon" aria-hidden="true"></i>
                      <span class="incidencia-mini-player">{{ item.jugador }}</span>
                      <span v-if="item.minuto !== null && item.minuto !== undefined" class="incidencia-mini-min">({{ item.minuto }})</span>
                    </span>
                  </div>
                </div>

                <!-- Score / VS -->
                <div class="score-col">
                  <template v-if="hasResult(getPartido(cancha.id))">
                    <div class="score-text">
                      {{ getPartido(cancha.id).resultado_local }}
                      <span class="score-dash">-</span>
                      {{ getPartido(cancha.id).resultado_visitante }}
                    </div>
                  </template>
                  <template v-else>
                    <div class="vs-text">VS</div>
                  </template>
                  <div class="hora-text">{{ getHora(getPartido(cancha.id).fecha_hora_inicio) }}</div>
                </div>

                <!-- Visitante -->
                <div class="team-block">
                  <img
                    v-if="getEscudo(getPartido(cancha.id).id_equipo_visitante)"
                    :src="resolveEscudoUrl(getEscudo(getPartido(cancha.id).id_equipo_visitante))"
                    class="escudo-img" alt=""
                  />
                  <i v-else class="bi bi-shield-fill escudo-ph"></i>
                  <span class="team-nm visit-nm">
                    {{ getPartido(cancha.id).equipo_visitante_nombre || 'Visitante' }}
                  </span>
                  <div
                    v-if="getIncidenciasEquipo(getPartido(cancha.id), getPartido(cancha.id).id_equipo_visitante).length"
                    class="incidencias-mini"
                  >
                    <span
                      v-for="item in getIncidenciasEquipo(getPartido(cancha.id), getPartido(cancha.id).id_equipo_visitante)"
                      :key="item.uid"
                      class="incidencia-mini-chip"
                      :class="`incidencia-mini-${item.tone}`"
                    >
                      <span v-if="item.icon === 'ball'" class="incidencia-mini-icon" aria-hidden="true">⚽</span>
                      <span v-else-if="item.icon === 'yellow-card'" class="incidencia-mini-card incidencia-mini-card-yellow" aria-hidden="true"></span>
                      <span v-else-if="item.icon === 'red-card'" class="incidencia-mini-card incidencia-mini-card-red" aria-hidden="true"></span>
                      <i v-else class="bi bi-record-circle incidencia-mini-icon" aria-hidden="true"></i>
                      <span class="incidencia-mini-player">{{ item.jugador }}</span>
                      <span v-if="item.minuto !== null && item.minuto !== undefined" class="incidencia-mini-min">({{ item.minuto }})</span>
                    </span>
                  </div>
                </div>

              </div><!-- /teams-row -->


            </template>

            <!-- No match -->
            <div v-else class="libre-msg">
              <i class="bi bi-moon-stars-fill mb-1"></i>
              <span>Libre</span>
            </div>

          </div><!-- /match-overlay -->
        </div><!-- /field-wrap -->

      </div><!-- /cancha-panel -->

      <!-- Empty state -->
      <div v-if="!canchas.length" class="gc-empty">
        <i class="bi bi-exclamation-circle me-2"></i>No hay canchas configuradas.
      </div>

      <div v-else-if="!canchasMostradas.length" class="gc-empty">
        <i class="bi bi-funnel me-2"></i>No hay canchas visibles con el filtro actual.
      </div>

    </div><!-- /cancha-grid -->

  </div><!-- /gc-page -->
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import eventosService from '@/services/instalaciones/eventosService'
import datosMaestrosService from '@/services/datosMaestrosService'
import { getIncidenciaVisualMeta } from '@/utils/incidencias'

// ── Constants ──────────────────────────────────────────────
const SLOT_DURATION_MIN = 70

// ── State ──────────────────────────────────────────────────
const loading   = ref(false)
const eventos   = ref([])
const canchas   = ref([])
const equipos   = ref([])
const incidenciasByEvento = ref({})
const selectedDate = ref(todayDate())
const horarioSel   = ref(null)
const calendarOpen = ref(false)
const calendarAnchor = ref(null)
const calendarMonth = ref(startOfMonth(selectedDate.value))
const fetchingIncidenciasIds = new Set()
const canchaFilterOpen = ref(false)
const canchaFilterAnchor = ref(null)
const selectedCanchasIds = ref([])

// ── Date helpers ───────────────────────────────────────────
function todayDate() {
  const d = new Date()
  d.setHours(0, 0, 0, 0)
  return d
}

function formatDateISO(d) {
  const y   = d.getFullYear()
  const mon = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${mon}-${day}`
}

function startOfMonth(d) {
  const next = new Date(d)
  next.setDate(1)
  next.setHours(0, 0, 0, 0)
  return next
}

function timeToMin(hhmm) {
  const [h, m] = hhmm.split(':').map(Number)
  return h * 60 + m
}

const DAY_NAMES = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado']
const MONTH_NAMES = ['enero','febrero','marzo','abril','mayo','junio',
                     'julio','agosto','septiembre','octubre','noviembre','diciembre']

const dayName  = computed(() => DAY_NAMES[selectedDate.value.getDay()])
const dateLabel = computed(() => {
  const d = selectedDate.value
  return `${d.getDate()} de ${MONTH_NAMES[d.getMonth()]} ${d.getFullYear()}`
})

function prevDay() {
  const d = new Date(selectedDate.value)
  d.setDate(d.getDate() - 1)
  selectedDate.value = d
}
function nextDay() {
  const d = new Date(selectedDate.value)
  d.setDate(d.getDate() + 1)
  selectedDate.value = d
}

const WEEKDAY_LABELS = ['Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab', 'Dom']

const calendarMonthLabel = computed(() => {
  const d = calendarMonth.value
  return `${MONTH_NAMES[d.getMonth()]} ${d.getFullYear()}`
})

const eventCountsByDate = computed(() => {
  const map = {}
  eventos.value.forEach(ev => {
    const dateStr = String(ev.fecha_hora_inicio || '').substring(0, 10)
    if (!dateStr) return
    map[dateStr] = (map[dateStr] || 0) + 1
  })
  return map
})

const calendarDays = computed(() => {
  const base = startOfMonth(calendarMonth.value)
  const year = base.getFullYear()
  const month = base.getMonth()
  const firstDay = base.getDay()
  const offset = (firstDay - 1 + 7) % 7
  const daysInMonth = new Date(year, month + 1, 0).getDate()
  const totalCells = 42
  const todayStr = formatDateISO(todayDate())
  const selectedStr = formatDateISO(selectedDate.value)

  return Array.from({ length: totalCells }, (_, idx) => {
    const dayIndex = idx - offset + 1
    const date = new Date(year, month, dayIndex)
    const inMonth = dayIndex >= 1 && dayIndex <= daysInMonth
    const iso = formatDateISO(date)
    const eventCount = eventCountsByDate.value[iso] || 0
    return {
      date,
      day: date.getDate(),
      inMonth,
      iso,
      eventCount,
      isToday: iso === todayStr,
      isSelected: iso === selectedStr,
    }
  })
})

function toggleCalendar() {
  calendarOpen.value = !calendarOpen.value
  if (calendarOpen.value) {
    calendarMonth.value = startOfMonth(selectedDate.value)
  }
}

function prevMonth() {
  const d = new Date(calendarMonth.value)
  d.setMonth(d.getMonth() - 1)
  calendarMonth.value = startOfMonth(d)
}

function nextMonth() {
  const d = new Date(calendarMonth.value)
  d.setMonth(d.getMonth() + 1)
  calendarMonth.value = startOfMonth(d)
}

function selectCalendarDay(day) {
  if (!day?.date) return
  const d = new Date(day.date)
  d.setHours(0, 0, 0, 0)
  selectedDate.value = d
  calendarOpen.value = false
}

function goToday() {
  selectedDate.value = todayDate()
  calendarOpen.value = false
}

const horariosDisponibles = computed(() => {
  const dateStr = formatDateISO(selectedDate.value)
  const seen = new Set()
  eventos.value.forEach(ev => {
    if (!ev.fecha_hora_inicio) return
    const evDate = String(ev.fecha_hora_inicio).substring(0, 10)
    if (evDate !== dateStr) return
    const timePart = String(ev.fecha_hora_inicio).substring(11, 16)
    if (timePart) seen.add(timePart)
  })
  return Array.from(seen).sort((a, b) => timeToMin(a) - timeToMin(b))
})

const isAllCanchasSelected = computed(() => selectedCanchasIds.value.length === 0)

const canchaFilterLabel = computed(() => {
  if (selectedCanchasIds.value.length === 0) return 'Canchas: todas'
  return `Canchas: ${selectedCanchasIds.value.length}`
})

const canchasMostradas = computed(() => {
  if (selectedCanchasIds.value.length === 0) return canchas.value
  const selected = new Set(selectedCanchasIds.value.map(Number))
  return canchas.value.filter((c) => selected.has(Number(c.id)))
})

function toggleCanchaFilter() {
  canchaFilterOpen.value = !canchaFilterOpen.value
}

function toggleAllCanchas() {
  selectedCanchasIds.value = []
}

function toggleCanchaSelection(idCancha) {
  const id = Number(idCancha)
  if (!id) return

  const idx = selectedCanchasIds.value.findIndex((v) => Number(v) === id)
  if (idx >= 0) {
    selectedCanchasIds.value.splice(idx, 1)
    return
  }
  selectedCanchasIds.value.push(id)
}

// ── Auto-select current time slot ─────────────────────────
function currentSlot() {
  const todayStr = formatDateISO(todayDate())
  const selStr   = formatDateISO(selectedDate.value)
  const list = horariosDisponibles.value
  if (!list.length) return null
  if (selStr === todayStr) {
    const now = new Date()
    const mins = now.getHours() * 60 + now.getMinutes()
    for (let i = list.length - 1; i >= 0; i--) {
      if (mins >= timeToMin(list[i])) return list[i]
    }
  }
  return list[0]
}

// ── Equipos map (id → equipo) ──────────────────────────────
const equiposMap = computed(() => {
  const map = {}
  equipos.value.forEach(e => { map[Number(e.id)] = e })
  return map
})

function getEscudo(idEquipo) {
  return idEquipo ? (equiposMap.value[Number(idEquipo)]?.escudo ?? null) : null
}

// ── Events filtering ───────────────────────────────────────
const eventosFiltrados = computed(() => {
  const dateStr  = formatDateISO(selectedDate.value)
  const selected = horarioSel.value
  if (!selected) return []
  const slotMin  = timeToMin(selected)
  const slotEnd  = slotMin + SLOT_DURATION_MIN

  return eventos.value.filter(ev => {
    if (!ev.fecha_hora_inicio || !ev.id_cancha) return false
    // Date portion: handles 'YYYY-MM-DD HH:MM:SS' and 'YYYY-MM-DDTHH:MM:SS'
    const evDate = String(ev.fecha_hora_inicio).substring(0, 10)
    if (evDate !== dateStr) return false
    // Time portion: 'HH:MM'
    const timePart = String(ev.fecha_hora_inicio).substring(11, 16)
    const evMin = timeToMin(timePart)
    return evMin >= slotMin && evMin < slotEnd
  })
})

function getPartido(idCancha) {
  return eventosFiltrados.value.find(ev => Number(ev.id_cancha) === Number(idCancha)) ?? null
}

const eventosFiltradosIds = computed(() => (
  eventosFiltrados.value
    .map(ev => Number(ev.id))
    .filter(id => Number.isFinite(id) && id > 0)
))

const fetchIncidenciasForEventos = async (ids = []) => {
  const missingIds = ids.filter((id) => {
    const key = Number(id)
    return key > 0 && !Object.prototype.hasOwnProperty.call(incidenciasByEvento.value, key) && !fetchingIncidenciasIds.has(key)
  })

  if (!missingIds.length) return

  await Promise.all(missingIds.map(async (idEvento) => {
    fetchingIncidenciasIds.add(idEvento)
    try {
      const data = await eventosService.getEventosPartido(idEvento)
      incidenciasByEvento.value = {
        ...incidenciasByEvento.value,
        [idEvento]: Array.isArray(data) ? data : [],
      }
    } catch {
      incidenciasByEvento.value = {
        ...incidenciasByEvento.value,
        [idEvento]: [],
      }
    } finally {
      fetchingIncidenciasIds.delete(idEvento)
    }
  }))
}

watch(eventosFiltradosIds, (ids) => {
  fetchIncidenciasForEventos(ids)
})

watch(selectedDate, (value) => {
  if (!calendarOpen.value) {
    calendarMonth.value = startOfMonth(value)
  }
})

watch([selectedDate, eventos], () => {
  const next = currentSlot()
  if (next !== horarioSel.value) {
    horarioSel.value = next
  }
})

const getIncidenciasEquipo = (partido, idEquipo) => {
  const idEvento = Number(partido?.id)
  const equipo = Number(idEquipo)
  if (!idEvento || !equipo) return []

  const list = Array.isArray(incidenciasByEvento.value[idEvento]) ? incidenciasByEvento.value[idEvento] : []
  return list
    .filter(item => Number(item.id_equipo) === equipo)
    .sort((a, b) => {
      const minutoA = a?.minuto === null || a?.minuto === undefined ? 999 : Number(a.minuto)
      const minutoB = b?.minuto === null || b?.minuto === undefined ? 999 : Number(b.minuto)
      return minutoA - minutoB
    })
    .map((item, idx) => {
      const meta = getIncidenciaVisualMeta(item?.tipo_evento_partido_descripcion)
      const jugadorApellido = String(item?.jugador_apellido || '').trim()
      const jugadorNombre = String(item?.jugador_nombre || '').trim()
      const jugador = jugadorApellido || jugadorNombre
        ? `${jugadorApellido}${jugadorApellido && jugadorNombre ? ', ' : ''}${jugadorNombre}`
        : 'Jugador'
      return {
        uid: `${idEvento}-${equipo}-${item.id ?? idx}-${idx}`,
        icon: meta.icon,
        tone: meta.tone,
        jugador,
        minuto: item.minuto,
      }
    })
}

// ── Display helpers ────────────────────────────────────────
function hasResult(ev) {
  return ev && ev.resultado_local !== null && ev.resultado_local !== undefined
}

function getHora(fechaHoraInicio) {
  if (!fechaHoraInicio) return ''
  return String(fechaHoraInicio).substring(11, 16)
}

function estadoClass(idEstado) {
  const id = Number(idEstado)
  if ([4, 7].includes(id)) return 'estado-fin'
  if ([2, 3].includes(id)) return 'estado-curso'
  return 'estado-def'
}

// ── resolveEscudoUrl (same helper used across the app) ─────
function resolveEscudoUrl(escudo) {
  if (!escudo) return ''
  const value = String(escudo).trim()
  if (!value) return ''
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

// ── Data loading ───────────────────────────────────────────
onMounted(async () => {
  loading.value = true
  try {
    const [evData, canchaData, equipoData] = await Promise.all([
      eventosService.getEventos(),
      datosMaestrosService.getCanchas(),
      datosMaestrosService.getEquipos(),
    ])
    eventos.value  = evData      ?? []
    canchas.value  = (canchaData ?? []).filter(c => Number(c.activo) !== 0)
    equipos.value  = equipoData  ?? []
    selectedCanchasIds.value = []
    horarioSel.value = currentSlot()
    await fetchIncidenciasForEventos(eventosFiltradosIds.value)
  } catch (err) {
    console.error('GrillaCanchas: error al cargar datos', err)
  } finally {
    loading.value = false
  }
})

const onOutsideClick = (event) => {
  const target = event?.target

  if (calendarOpen.value && calendarAnchor.value && target && !calendarAnchor.value.contains(target)) {
    calendarOpen.value = false
  }

  if (canchaFilterOpen.value && canchaFilterAnchor.value && target && !canchaFilterAnchor.value.contains(target)) {
    canchaFilterOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', onOutsideClick)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', onOutsideClick)
})
</script>

<style scoped>
/* ── Page layout ── */
.gc-page {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  padding-bottom: 1rem;
  box-sizing: border-box;
}

.tracking-wide { letter-spacing: .08em; }

/* ── Back button ── */
.btn-back-arrow {
  background: none;
  border: none;
  border-radius: 8px;
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background-color 0.2s ease;
  flex-shrink: 0;
}
.btn-back-arrow:hover { background: #f1f5f9; }

/* ── Date navigator ── */
.date-nav {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
}
.date-center-wrap {
  position: relative;
  display: flex;
  align-items: center;
  gap: .6rem;
}
.date-arrow {
  background: none;
  border: 1px solid #dee2e6;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #495057;
  font-size: 1.1rem;
  transition: background .15s, border-color .15s;
}
.date-arrow:hover {
  background: #e9ecef;
  border-color: #adb5bd;
}
.date-center {
  text-align: center;
  min-width: 200px;
}
.date-calendar-btn {
  border: 1px solid #dee2e6;
  background: #fff;
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #495057;
  cursor: pointer;
  transition: background .15s, border-color .15s;
}
.date-calendar-btn:hover {
  background: #f0f0f0;
  border-color: #adb5bd;
}
.date-dayname {
  font-size: .85rem;
  color: #6c757d;
  text-transform: uppercase;
  letter-spacing: .06em;
}
.date-label {
  font-size: 1.15rem;
  font-weight: 600;
  color: #212529;
}

/* ── Calendar popover ── */
.calendar-popover {
  position: absolute;
  top: calc(100% + 10px);
  left: 50%;
  transform: translateX(-50%);
  background: #fff;
  border: 1px solid #dee2e6;
  border-radius: 12px;
  box-shadow: 0 12px 30px rgba(0, 0, 0, .15);
  padding: .75rem .75rem .6rem;
  z-index: 20;
  width: 280px;
}

.calendar-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: .5rem;
  margin-bottom: .5rem;
}

.cal-title {
  font-size: .95rem;
  font-weight: 700;
  text-transform: capitalize;
  color: #212529;
}

.cal-nav {
  border: 1px solid #dee2e6;
  background: #f8f9fa;
  width: 30px;
  height: 30px;
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #495057;
  cursor: pointer;
}

.calendar-weekdays {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: .25rem;
  margin-bottom: .35rem;
  text-align: center;
  font-size: .7rem;
  font-weight: 700;
  color: #6c757d;
  text-transform: uppercase;
  letter-spacing: .06em;
}

.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: .25rem;
}

.calendar-day {
  border: 1px solid transparent;
  background: #fff;
  height: 34px;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 1px;
  color: #212529;
  cursor: pointer;
  position: relative;
  transition: background .12s, border-color .12s, color .12s;
}

.calendar-day:hover {
  background: #f1f3f5;
  border-color: #dee2e6;
}

.calendar-day.is-other {
  color: #adb5bd;
}

.calendar-day.is-selected {
  background: #1a1a2e;
  color: #fff;
}

.calendar-day.is-today:not(.is-selected) {
  border-color: #1a1a2e;
}

.calendar-day-num {
  font-size: .75rem;
  font-weight: 700;
  line-height: 1;
}

.calendar-event-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #f4a261;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.calendar-event-dot.has-count {
  width: 14px;
  height: 14px;
  border-radius: 999px;
  background: #f4a261;
}

.calendar-event-count {
  font-size: .55rem;
  line-height: 1;
  color: #1a1a2e;
  font-weight: 800;
  transform: translateY(-.5px);
}

.calendar-footer {
  display: flex;
  justify-content: center;
  margin-top: .5rem;
}

.cal-today-btn {
  border: 1px solid #dee2e6;
  background: #fff;
  border-radius: 999px;
  padding: .25rem .85rem;
  font-size: .75rem;
  font-weight: 700;
  color: #495057;
  cursor: pointer;
}

/* ── Loading ── */
.gc-loading {
  text-align: center;
  padding: 3rem;
  color: #6c757d;
}

/* ── Cancha grid ── */
.cancha-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
}
@media (max-width: 700px) {
  .cancha-grid { grid-template-columns: 1fr; }
}

.cancha-panel {
  display: flex;
  flex-direction: column;
  width: 100%;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 12px rgba(0,0,0,.12);
}

.cancha-label {
  background: #1a1a2e;
  color: #fff;
  padding: .5rem .85rem;
  font-size: .85rem;
  font-weight: 600;
  letter-spacing: .04em;
  text-transform: uppercase;
  display: flex;
  align-items: center;
}
.cancha-label i { color: #f4a261; }

/* ── Field ── */
.field-wrap {
  position: relative;
  width: 100%;
  /* Green grass stripes via CSS — avoids SVG pattern-ID conflicts */
  background: repeating-linear-gradient(
    90deg,
    #2d8a4e 0px, #2d8a4e 22px,
    #267a44 22px, #267a44 44px
  );
}

.field-svg {
  display: block;
  width: 100%;
  /* maintain 360:200 aspect ratio */
  aspect-ratio: 360 / 200;
}

/* ── Match overlay ── */
.match-overlay {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: .35rem;
  padding: .5rem .75rem;
}

/* Teams row */
.teams-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  gap: .25rem;
}

.team-block {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: .3rem;
  flex: 1;
  min-width: 0;
}

.escudo-img {
  width: 44px;
  height: 44px;
  object-fit: contain;
  filter: drop-shadow(0 2px 4px rgba(0,0,0,.5));
  border-radius: 4px;
}

.escudo-ph {
  font-size: 2rem;
  color: rgba(255,255,255,.55);
}

.team-nm {
  font-size: .72rem;
  font-weight: 700;
  color: #fff;
  text-shadow: 0 1px 4px rgba(0,0,0,.8);
  text-align: center;
  line-height: 1.2;
  /* Clamp to 2 lines */
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  word-break: break-word;
}

.incidencias-mini {
  display: flex;
  flex-direction: column;
  align-items: stretch;
  gap: .18rem;
  margin-top: .15rem;
  width: 100%;
  max-width: 100%;
  padding: 0 .2rem;
}

.incidencia-mini-chip {
  display: inline-flex;
  align-items: center;
  justify-content: flex-start;
  gap: .12rem;
  padding: 1px 4px;
  border-radius: 999px;
  font-size: .58rem;
  font-weight: 700;
  letter-spacing: .02em;
  line-height: 1;
  border: 1px solid transparent;
  width: 100%;
}

.incidencia-mini-icon {
  font-size: .62rem;
  line-height: 1;
}

.incidencia-mini-player {
  max-width: none;
  overflow: visible;
  text-overflow: clip;
  white-space: normal;
  line-height: 1.15;
}

.incidencia-mini-min {
  line-height: 1;
}

.incidencia-mini-card {
  width: .42rem;
  height: .56rem;
  border-radius: .06rem;
  box-shadow: 0 0 0 1px rgba(0, 0, 0, .15) inset;
}

.incidencia-mini-card-yellow {
  background: #ffc107;
}

.incidencia-mini-card-red {
  background: #dc3545;
}

.incidencia-mini-success {
  background: rgba(0, 0, 0, .38);
  color: #fff;
  border-color: rgba(255, 255, 255, .15);
}

.incidencia-mini-warning {
  background: rgba(0, 0, 0, .38);
  color: #fff;
  border-color: rgba(255, 255, 255, .15);
}

.incidencia-mini-danger {
  background: rgba(0, 0, 0, .38);
  color: #fff;
  border-color: rgba(255, 255, 255, .15);
}

.incidencia-mini-muted {
  background: rgba(0, 0, 0, .38);
  color: #fff;
  border-color: rgba(255, 255, 255, .15);
}

/* Score / VS center column */
.score-col {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: .2rem;
  flex-shrink: 0;
  min-width: 58px;
}

.score-text {
  font-size: 1.6rem;
  font-weight: 900;
  color: #fff;
  text-shadow: 0 2px 6px rgba(0,0,0,.7);
  letter-spacing: .05em;
  line-height: 1;
}
.score-dash {
  margin: 0 .15rem;
}

.vs-text {
  font-size: 1.3rem;
  font-weight: 900;
  color: rgba(255,255,255,.9);
  text-shadow: 0 2px 6px rgba(0,0,0,.7);
  letter-spacing: .08em;
}

.hora-text {
  font-size: .7rem;
  color: rgba(255,255,255,.8);
  font-weight: 600;
  background: rgba(0,0,0,.35);
  border-radius: 4px;
  padding: 1px 6px;
  letter-spacing: .04em;
}

/* Top bar: torneo + estado */
.match-top-bar {
  position: relative;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  width: 100%;
  background: transparent;
  padding: 0;
}
.match-top-bar .meta-estado {
  position: absolute;
  right: 0;
  top: 0;
}
.match-top-center {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: .15rem;
}
.match-torneo-title {
  background: rgba(0,0,0,.58);
  color: rgba(255,255,255,.97);
  font-size: .68rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .04em;
  border-radius: 6px;
  padding: .22rem .65rem;
  text-align: center;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 200px;
}
.match-evento-titulo {
  background: rgba(0,0,0,.42);
  color: rgba(255,255,255,.82);
  font-size: .6rem;
  font-weight: 500;
  font-style: italic;
  border-radius: 4px;
  padding: .12rem .5rem;
  text-align: center;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 200px;
}
.meta-estado {
  font-size: .6rem;
  font-weight: 700;
  border-radius: 4px;
  padding: 2px 7px;
  flex-shrink: 0;
  text-transform: uppercase;
  letter-spacing: .04em;
}
.estado-fin   { background: #198754; color: #fff; }
.estado-curso { background: #ffc107; color: #000; }
.estado-def   { background: rgba(255,255,255,.2); color: #fff; }

/* Libre (no match) */
.libre-msg {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: .3rem;
  color: rgba(255,255,255,.55);
  font-size: .9rem;
  font-weight: 600;
}
.libre-msg i { font-size: 1.6rem; }

/* Empty state */
.gc-empty {
  grid-column: 1 / -1;
  text-align: center;
  padding: 2.5rem;
  color: #6c757d;
  font-size: .95rem;
}

/* ── Horarios bar ── */
.horarios-bar {
  display: flex;
  gap: .5rem;
  justify-content: center;
  flex-wrap: wrap;
  padding: .25rem 0 .5rem;
}

.horarios-empty {
  font-size: .9rem;
  font-weight: 600;
  color: #6c757d;
  padding: .4rem .8rem;
  border-radius: 999px;
  background: #f8f9fa;
  border: 1px dashed #dee2e6;
}

.cancha-filter-row {
  display: flex;
  justify-content: flex-end;
}

.cancha-filter-wrap {
  position: relative;
}

.cancha-filter-btn {
  border: 1px solid #d0d7de;
  background: #fff;
  border-radius: 999px;
  padding: .3rem .7rem;
  font-size: .8rem;
  font-weight: 600;
  color: #334155;
  display: inline-flex;
  align-items: center;
  gap: .35rem;
  cursor: pointer;
  transition: background .15s, border-color .15s;
}

.cancha-filter-btn:hover {
  background: #f8fafc;
  border-color: #b8c0cc;
}

.cancha-filter-popover {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  min-width: 220px;
  max-height: 250px;
  overflow: auto;
  background: #fff;
  border: 1px solid #dee2e6;
  border-radius: 12px;
  box-shadow: 0 12px 30px rgba(0, 0, 0, .14);
  padding: .55rem;
  z-index: 24;
}

.cancha-filter-popover-title {
  font-size: .75rem;
  text-transform: uppercase;
  letter-spacing: .05em;
  color: #64748b;
  margin-bottom: .35rem;
  font-weight: 700;
}

.cancha-filter-item {
  display: flex;
  align-items: center;
  gap: .45rem;
  font-size: .85rem;
  color: #1f2937;
  padding: .3rem .2rem;
  border-radius: 8px;
  cursor: pointer;
}

.cancha-filter-item:hover {
  background: #f8fafc;
}

.horario-chip {
  border: 2px solid #dee2e6;
  background: #fff;
  border-radius: 999px;
  padding: .4rem 1.1rem;
  font-size: .85rem;
  font-weight: 600;
  color: #495057;
  cursor: pointer;
  transition: background .15s, border-color .15s, color .15s;
}
.horario-chip:hover {
  background: #f0f0f0;
  border-color: #adb5bd;
}
.horario-chip.active {
  background: #1a1a2e;
  border-color: #1a1a2e;
  color: #fff;
}
</style>
