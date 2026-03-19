<template>
  <div class="torneo-calendar card border-0 shadow-sm">
    <div class="card-body p-3 p-md-4">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
          <h3 v-if="showTitle" class="h6 fw-bold mb-0">Calendario de partidos</h3>
          <p class="small text-muted mb-0">Mes: {{ monthLabel }}</p>
        </div>

        <div class="d-flex align-items-center gap-2">
          <button class="btn btn-sm btn-outline-secondary" @click="goPrevMonth" title="Mes anterior">
            <i class="bi bi-chevron-left"></i>
          </button>
          <button class="btn btn-sm btn-outline-secondary" @click="goToday">Hoy</button>
          <button class="btn btn-sm btn-outline-secondary" @click="goNextMonth" title="Mes siguiente">
            <i class="bi bi-chevron-right"></i>
          </button>
        </div>
      </div>

      <div class="calendar-grid-wrap">
        <div class="calendar-weekdays">
          <div v-for="dayLabel in weekDays" :key="dayLabel" class="calendar-weekday">{{ dayLabel }}</div>
        </div>

        <div class="calendar-grid">
          <template v-for="(cell, idx) in calendarCells" :key="`cell-${idx}`">
            <div v-if="!cell" class="calendar-cell calendar-cell-empty"></div>

            <button
              v-else
              type="button"
              class="calendar-cell"
              :class="{
                'calendar-cell-today': cell.isToday,
                'calendar-cell-selected': cell.key === selectedDateKey,
              }"
              @click="selectDate(cell.key)"
            >
              <div class="d-flex justify-content-between align-items-start">
                <span class="calendar-day-number">{{ cell.day }}</span>
                <span v-if="cell.eventsCount > 0" class="badge rounded-pill bg-primary-subtle text-primary">
                  {{ cell.eventsCount }}
                </span>
              </div>

              <div class="calendar-cell-preview text-start mt-2">
                <template v-if="cell.eventsCount > 0">
                  <div
                    v-for="ev in cell.previewEvents"
                    :key="`cell-event-${cell.key}-${ev.id}`"
                    class="calendar-mini-event"
                  >
                    <span class="calendar-mini-dot" :class="getEstadoEventoDotClass(ev)"></span>
                    <span class="calendar-mini-time">{{ ev.timeLabel }}</span>
                    <span class="calendar-mini-title">{{ ev.shortLabel }}</span>
                  </div>
                  <div v-if="cell.hiddenCount > 0" class="calendar-mini-more">+{{ cell.hiddenCount }} mas</div>
                </template>
              </div>
            </button>
          </template>
        </div>
      </div>

      <div class="mt-4">
        <h4 class="h6 fw-semibold mb-2">Partidos del {{ selectedDateLabel }}</h4>

        <div v-if="selectedEvents.length === 0" class="alert alert-light border mb-0">
          No hay partidos para la fecha seleccionada.
        </div>

        <div v-else class="d-flex flex-column gap-2">
          <div v-for="ev in selectedEvents" :key="`event-${ev.id}`" class="calendar-event-row">
            <div class="d-flex justify-content-between align-items-center gap-2">
              <div class="small text-muted">{{ ev.timeLabel }}</div>
              <span class="badge rounded-pill" :class="getEstadoEventoBadgeClass(ev)">
                {{ ev.estado_evento_descripcion || 'Sin estado' }}
              </span>
            </div>
            <div class="fw-semibold">{{ ev.titleLabel }}</div>
            <div v-if="ev.torneoNombre" class="small text-muted">Torneo: {{ ev.torneoNombre }}</div>
            <div class="small text-muted">
              <span v-if="ev.cancha_nombre"> · Cancha: {{ ev.cancha_nombre }}</span>
              <span v-if="ev.arbitro_nombre_completo"> · Arbitro: {{ ev.arbitro_nombre_completo }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'

const props = defineProps({
  eventos: {
    type: Array,
    default: () => [],
  },
  torneoNombre: {
    type: String,
    default: '',
  },
  showTitle: {
    type: Boolean,
    default: true,
  },
})

const weekDays = ['Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab', 'Dom']

const getDateKey = (dt) => {
  const y = dt.getFullYear()
  const m = String(dt.getMonth() + 1).padStart(2, '0')
  const d = String(dt.getDate()).padStart(2, '0')
  return `${y}-${m}-${d}`
}

const parseDateTime = (value) => {
  if (!value) return null
  const dt = new Date(String(value).replace(' ', 'T'))
  return Number.isNaN(dt.getTime()) ? null : dt
}

const abbreviateRoundTitle = (rawTitle) => {
  const title = String(rawTitle || '').trim()
  if (!title) return ''

  return title
    .replace(/dieciseisavos\s+de\s+final/gi, '16vos')
    .replace(/octavos\s+de\s+final/gi, '8vos')
    .replace(/cuartos\s+de\s+final/gi, '4tos')
    .replace(/semifinal(?:es)?/gi, 'Semi')
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

const getEstadoEventoDotClass = (ev) => {
  const key = getEstadoEventoKey(ev)
  if (key === 'finalizado') return 'calendar-mini-dot-finalizado'
  if (key === 'programado') return 'calendar-mini-dot-programado'
  if (key === 'pendiente') return 'calendar-mini-dot-pendiente'
  if (key === 'cancelado') return 'calendar-mini-dot-cancelado'
  return 'calendar-mini-dot-otro'
}

const getEstadoEventoBadgeClass = (ev) => {
  const key = getEstadoEventoKey(ev)
  if (key === 'finalizado') return 'bg-success-subtle text-success'
  if (key === 'programado') return 'bg-primary-subtle text-primary'
  if (key === 'pendiente') return 'bg-warning-subtle text-warning'
  if (key === 'cancelado') return 'bg-danger-subtle text-danger'
  return 'bg-secondary-subtle text-secondary'
}

const today = new Date()
const currentMonth = ref(new Date(today.getFullYear(), today.getMonth(), 1))
const selectedDateKey = ref(getDateKey(today))

const normalizedEvents = computed(() => {
  return (props.eventos || [])
    .map((ev) => {
      const dt = parseDateTime(ev.fecha_hora_inicio)
      if (!dt) return null

      const localName = String(ev.equipo_local_nombre || '-').trim() || '-'
      const visitanteName = String(ev.equipo_visitante_nombre || '-').trim() || '-'
      const title = abbreviateRoundTitle(ev.titulo)
      const fallbackVs = `${localName} vs ${visitanteName}`
      const torneoNombre = String(ev.torneo_nombre || props.torneoNombre || '').trim()
      const eventMainLabel = title || fallbackVs
      const miniLabel = torneoNombre ? `${torneoNombre} - ${eventMainLabel}` : eventMainLabel

      return {
        ...ev,
        id: Number(ev.id || 0),
        eventDate: dt,
        dateKey: getDateKey(dt),
        timeLabel: dt.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit', hour12: false }),
        titleLabel: eventMainLabel,
        shortLabel: miniLabel,
        torneoNombre,
      }
    })
    .filter(Boolean)
    .sort((a, b) => a.eventDate.getTime() - b.eventDate.getTime())
})

const eventsByDate = computed(() => {
  const grouped = {}
  for (const ev of normalizedEvents.value) {
    if (!grouped[ev.dateKey]) grouped[ev.dateKey] = []
    grouped[ev.dateKey].push(ev)
  }
  return grouped
})

const monthLabel = computed(() =>
  currentMonth.value.toLocaleDateString('es-AR', { month: 'long', year: 'numeric' })
)

const calendarCells = computed(() => {
  const y = currentMonth.value.getFullYear()
  const m = currentMonth.value.getMonth()

  const firstDay = new Date(y, m, 1)
  const daysInMonth = new Date(y, m + 1, 0).getDate()
  const offset = (firstDay.getDay() + 6) % 7

  const cells = []

  for (let i = 0; i < offset; i++) {
    cells.push(null)
  }

  const todayKey = getDateKey(today)

  for (let day = 1; day <= daysInMonth; day++) {
    const dt = new Date(y, m, day)
    const key = getDateKey(dt)
    const events = eventsByDate.value[key] || []
    const eventsCount = events.length
    const previewEvents = events.slice(0, 2)
    const hiddenCount = Math.max(0, eventsCount - previewEvents.length)

    cells.push({
      day,
      key,
      isToday: key === todayKey,
      eventsCount,
      previewEvents,
      hiddenCount,
    })
  }

  while (cells.length % 7 !== 0) {
    cells.push(null)
  }

  return cells
})

const selectedEvents = computed(() => eventsByDate.value[selectedDateKey.value] || [])

const selectedDateLabel = computed(() => {
  const dt = parseDateTime(`${selectedDateKey.value} 00:00:00`)
  if (!dt) return selectedDateKey.value
  return dt.toLocaleDateString('es-AR', {
    weekday: 'long',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
})

const selectDate = (dateKey) => {
  selectedDateKey.value = dateKey
}

const goPrevMonth = () => {
  currentMonth.value = new Date(currentMonth.value.getFullYear(), currentMonth.value.getMonth() - 1, 1)
}

const goNextMonth = () => {
  currentMonth.value = new Date(currentMonth.value.getFullYear(), currentMonth.value.getMonth() + 1, 1)
}

const goToday = () => {
  currentMonth.value = new Date(today.getFullYear(), today.getMonth(), 1)
  selectedDateKey.value = getDateKey(today)
}

watch(
  () => normalizedEvents.value,
  () => {
    if (selectedDateKey.value && eventsByDate.value[selectedDateKey.value]) return

    const firstEvent = normalizedEvents.value[0]
    if (firstEvent) {
      selectedDateKey.value = firstEvent.dateKey
      currentMonth.value = new Date(firstEvent.eventDate.getFullYear(), firstEvent.eventDate.getMonth(), 1)
      return
    }

    selectedDateKey.value = getDateKey(today)
    currentMonth.value = new Date(today.getFullYear(), today.getMonth(), 1)
  },
  { immediate: true }
)
</script>

<style scoped>
.calendar-grid-wrap {
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  overflow: hidden;
}

.calendar-weekdays {
  display: grid;
  grid-template-columns: repeat(7, minmax(0, 1fr));
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

.calendar-weekday {
  text-align: center;
  font-size: 0.78rem;
  font-weight: 600;
  color: #475569;
  padding: 0.55rem 0.35rem;
}

.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, minmax(0, 1fr));
}

.calendar-cell {
  min-height: 132px;
  border: 0;
  border-right: 1px solid #eef2f7;
  border-bottom: 1px solid #eef2f7;
  background: #fff;
  padding: 0.5rem;
  text-align: left;
  transition: background 0.15s ease, box-shadow 0.15s ease;
}

.calendar-cell:nth-child(7n) {
  border-right: 0;
}

.calendar-cell-empty {
  background: #f8fafc;
}

.calendar-cell:hover {
  background: #f8fbff;
}

.calendar-cell-selected {
  background: #eff6ff;
  box-shadow: inset 0 0 0 2px rgba(59, 130, 246, 0.25);
}

.calendar-cell-today {
  background: #f0fdf4;
}

.calendar-day-number {
  font-size: 0.85rem;
  font-weight: 700;
  color: #0f172a;
}

.calendar-cell-preview {
  min-height: 1.1rem;
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.calendar-event-row {
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 0.6rem 0.75rem;
  background: #fff;
}

.calendar-mini-event {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.68rem;
  line-height: 1.2;
}

.calendar-mini-dot {
  width: 7px;
  height: 7px;
  border-radius: 999px;
  flex: 0 0 auto;
}

.calendar-mini-dot-finalizado {
  background: #16a34a;
}

.calendar-mini-dot-programado {
  background: #2563eb;
}

.calendar-mini-dot-pendiente {
  background: #f59e0b;
}

.calendar-mini-dot-cancelado {
  background: #dc2626;
}

.calendar-mini-dot-otro {
  background: #64748b;
}

.calendar-mini-time {
  color: #475569;
  font-weight: 600;
  flex: 0 0 auto;
}

.calendar-mini-title {
  color: #334155;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.calendar-mini-more {
  font-size: 0.67rem;
  color: #64748b;
  margin-top: 0.05rem;
}

@media (max-width: 767px) {
  .calendar-cell {
    min-height: 112px;
    padding: 0.4rem;
  }

  .calendar-weekday {
    font-size: 0.72rem;
  }
}
</style>
