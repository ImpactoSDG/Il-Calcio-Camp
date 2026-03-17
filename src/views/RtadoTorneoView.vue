<template>
  <div class="torneo-dashboard">
    <div class="bg-shape bg-shape-a"></div>
    <div class="bg-shape bg-shape-b"></div>

    <div class="container-fluid py-4 px-3 px-md-4 position-relative">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div class="d-flex align-items-center gap-2">
          <button @click="$router.back()" class="btn-back-arrow" title="Volver">
            <i class="bi bi-arrow-left"></i>
          </button>
          <div>
            <h1 class="title-page mb-0">SEGUIMIENTO DEL TORNEO</h1>
            <p class="subtitle-page mb-0">Dashboard visual del transcurso competitivo</p>
          </div>
        </div>

        <div class="selector-wrap">
          <label class="form-label mb-1">Torneo</label>
          <select v-model.number="idTorneoSeleccionado" class="form-select" @change="cargarDashboardSeleccionado">
            <option :value="null">Seleccionar torneo</option>
            <option v-for="torneo in torneosActivos" :key="torneo.id" :value="Number(torneo.id)">
              {{ torneo.nombre }}
            </option>
          </select>
        </div>
      </div>

      <div v-if="loading" class="panel loading-panel shadow-sm">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Cargando...</span>
        </div>
        <div class="mt-2">Cargando dashboard...</div>
      </div>

      <template v-else-if="dashboard">
        <div class="hero mb-4">
          <div>
            <div class="hero-label">Torneo activo</div>
            <h2 class="hero-name">{{ dashboard.torneo?.nombre || '-' }}</h2>
            <div class="hero-meta">
              <span class="badge text-bg-light border me-2">Fase actual: {{ dashboard.fase_actual || 'Sin definir' }}</span>
              <span class="badge text-bg-light border">Finalizados: {{ resumen.partidos_finalizados }}/{{ resumen.total_partidos }}</span>
            </div>
          </div>
        </div>

        <div class="row g-4">
          <div class="col-12 col-xxl-6">
            <div class="panel h-100">
              <div class="section-head">
                <h3 class="section-title mb-0">Ultimos resultados</h3>
              </div>

              <div v-if="!ultimosResultados.length" class="empty-state">No hay resultados finalizados.</div>

              <div v-else class="results-list">
                <article
                  class="result-item clickable-match"
                  v-for="partido in ultimosResultados"
                  :key="partido.id"
                  @click="abrirDetalleResultado(partido)"
                  @keyup.enter="abrirDetalleResultado(partido)"
                  tabindex="0"
                  role="button"
                  title="Ver detalle del partido"
                >
                  <div class="team team-local">
                    <img v-if="partido.equipo_local_escudo" :src="resolveEscudoUrl(partido.equipo_local_escudo)" alt="escudo local" class="escudo" />
                    <span class="team-name">{{ partido.equipo_local_nombre || 'Local' }}</span>
                  </div>
                  <div class="result-center">
                    <div class="score">{{ marcador(partido) }}</div>
                    <div v-if="huboPenales(partido)" class="pens">Pen {{ partido.resultado_penales_local }}-{{ partido.resultado_penales_visitante }}</div>
                    <div class="estado-chip" :class="estadoClass(partido.id_estado_evento)">{{ partido.estado || `Estado ${partido.id_estado_evento}` }}</div>
                  </div>
                  <div class="team team-away">
                    <span class="team-name text-end">{{ partido.equipo_visitante_nombre || 'Visitante' }}</span>
                    <img v-if="partido.equipo_visitante_escudo" :src="resolveEscudoUrl(partido.equipo_visitante_escudo)" alt="escudo visitante" class="escudo" />
                  </div>
                </article>
              </div>
            </div>
          </div>

          <div class="col-12 col-xxl-6">
            <div class="panel h-100">
              <div class="section-head">
                <h3 class="section-title mb-0">Jugadores: Top 10 (goles)</h3>
              </div>
              <div v-if="!topJugadoresGoleadores.length" class="empty-state">Sin goles registrados para el torneo.</div>
              <div v-else class="goleadores-list mb-3">
                <div class="goleador-item" v-for="(jugador, idx) in topJugadoresGoleadores" :key="`gol-${jugador.id_jugador}-${idx}`">
                  <div class="goleador-pos">{{ idx + 1 }}</div>
                  <div class="goleador-data">
                    <div class="goleador-name">{{ jugador.nombre_mostrar }}</div>
                    <div class="goleador-team">{{ jugador.equipo_nombre || 'Sin equipo' }}</div>
                  </div>
                  <div class="goleador-goles">{{ jugador.goles }}</div>
                </div>
              </div>

              <hr class="my-3" />

              <div class="section-head">
                <h3 class="section-title mb-0">Próximos partidos</h3>
              </div>
              <div v-if="!proximosPartidos.length" class="empty-state">No hay partidos programados.</div>
              <div v-else class="results-list">
                <article
                  class="result-item clickable-match"
                  v-for="partido in proximosPartidos"
                  :key="`proximo-${partido.id}`"
                  @click="abrirDetalleResultado(partido)"
                  @keyup.enter="abrirDetalleResultado(partido)"
                  tabindex="0"
                  role="button"
                  title="Ver detalle del partido"
                >
                  <div class="team team-local">
                    <img v-if="partido.equipo_local_escudo" :src="resolveEscudoUrl(partido.equipo_local_escudo)" alt="escudo local" class="escudo" />
                    <span class="team-name">{{ partido.equipo_local_nombre || 'Local' }}</span>
                  </div>
                  <div class="result-center">
                    <div class="score">vs</div>
                    <div class="pens" v-if="partido.fecha_hora_inicio">{{ formatFechaHora(partido.fecha_hora_inicio) }}</div>
                    <div class="estado-chip" :class="estadoClass(partido.id_estado_evento)">{{ partido.estado || `Estado ${partido.id_estado_evento}` }}</div>
                  </div>
                  <div class="team team-away">
                    <span class="team-name text-end">{{ partido.equipo_visitante_nombre || 'Visitante' }}</span>
                    <img v-if="partido.equipo_visitante_escudo" :src="resolveEscudoUrl(partido.equipo_visitante_escudo)" alt="escudo visitante" class="escudo" />
                  </div>
                </article>
              </div>
            </div>
          </div>

          <div class="col-12">
            <div class="panel">
              <div class="section-head">
                <h3 class="section-title mb-0">Tabla de zonas</h3>
              </div>
              <div v-if="!zonas.length" class="empty-state">No hay fase de grupos confirmada.</div>
              <div v-else class="row g-3">
                <div class="col-12 col-xl-6" v-for="zona in zonas" :key="zona.id_grupo_torneo">
                  <div class="zona-card">
                    <div class="zona-title">{{ zona.nombre }}</div>
                    <div class="table-responsive">
                      <table class="table table-sm align-middle mb-0 custom-table">
                        <thead>
                          <tr>
                            <th>#</th>
                            <th>Equipo</th>
                            <th class="text-center">PJ</th>
                            <th class="text-center">DG</th>
                            <th class="text-center">PTS</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="(equipo, idx) in zona.equipos" :key="equipo.id">
                            <td>{{ idx + 1 }}</td>
                            <td>
                              <div class="d-flex align-items-center gap-2">
                                <img v-if="equipo.escudo" :src="resolveEscudoUrl(equipo.escudo)" alt="escudo equipo" class="escudo escudo-mini" />
                                <span>{{ equipo.nombre }}</span>
                              </div>
                            </td>
                            <td class="text-center">{{ equipo.pj }}</td>
                            <td class="text-center">{{ equipo.dif }}</td>
                            <td class="text-center fw-bold">{{ equipo.pts }}</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-12">
            <div class="panel">
              <div class="section-head">
                <h3 class="section-title mb-0">Llave eliminatoria</h3>
              </div>
              <div v-if="!llave.length" class="empty-state">No hay cruces para mostrar.</div>
              <div v-else class="bracket-wrap">
                <div class="bracket-board">
                  <div class="bracket-side bracket-side-left">
                    <section class="bracket-round" v-for="(ronda, rIdx) in llaveEstructura.izquierda" :key="`left-${ronda.round}-${rIdx}`">
                      <div class="round-title">{{ ronda.nombre }}</div>
                      <div class="round-matches" :style="getBracketRoundStyle(ronda.depth)">
                        <article
                          class="bracket-match-literal bml-left"
                          v-for="(match, mIdx) in ronda.partidos"
                          :key="match?.id_cruce || `left-${rIdx}-${mIdx}`"
                          :class="{ 'is-placeholder': !match }"
                          @click="match ? abrirDetalleLlave(match, ronda.nombre) : null"
                          @keyup.enter="match ? abrirDetalleLlave(match, ronda.nombre) : null"
                          :tabindex="match ? 0 : -1"
                          :role="match ? 'button' : undefined"
                          :title="match ? 'Ver detalle del partido' : undefined"
                        >
                          <template v-if="match">
                            <div class="team-line team-line-left" :class="{ winner: ganadorLocal(match) }">
                              <img v-if="match.equipo_local?.escudo" :src="resolveEscudoUrl(match.equipo_local.escudo)" alt="escudo local" class="escudo escudo-mini" />
                              <span class="team-line-name">{{ match.equipo_local?.nombre || 'Por definir' }}</span>
                              <span class="team-line-score">{{ safeNum(match.equipo_local?.resultado) }}</span>
                            </div>
                            <div class="team-line team-line-left" :class="{ winner: ganadorVisitante(match) }">
                              <img v-if="match.equipo_visitante?.escudo" :src="resolveEscudoUrl(match.equipo_visitante.escudo)" alt="escudo visitante" class="escudo escudo-mini" />
                              <span class="team-line-name">{{ match.equipo_visitante?.nombre || 'Por definir' }}</span>
                              <span class="team-line-score">{{ safeNum(match.equipo_visitante?.resultado) }}</span>
                            </div>
                          </template>
                          <template v-else>
                            <div class="match-placeholder">Cruce por definir</div>
                          </template>
                        </article>
                      </div>
                    </section>
                  </div>

                  <div class="bracket-center">
                    <section class="bracket-round bracket-round-final">
                      <div class="round-title text-center">{{ llaveEstructura.final.nombre }}</div>
                      <div class="round-matches final-matches" :style="finalRoundStyle">
                        <article
                          class="bracket-match-literal bml-final"
                          v-for="(match, mIdx) in llaveEstructura.final.partidos"
                          :key="match?.id_cruce || `final-${mIdx}`"
                          :class="{ 'is-placeholder': !match }"
                          @click="match ? abrirDetalleLlave(match, llaveEstructura.final.nombre) : null"
                          @keyup.enter="match ? abrirDetalleLlave(match, llaveEstructura.final.nombre) : null"
                          :tabindex="match ? 0 : -1"
                          :role="match ? 'button' : undefined"
                          :title="match ? 'Ver detalle del partido' : undefined"
                        >
                          <template v-if="match">
                            <div class="team-line team-line-center" :class="{ winner: ganadorLocal(match) }">
                              <img v-if="match.equipo_local?.escudo" :src="resolveEscudoUrl(match.equipo_local.escudo)" alt="escudo local" class="escudo escudo-mini" />
                              <span class="team-line-name">{{ match.equipo_local?.nombre || 'Por definir' }}</span>
                              <span class="team-line-score">{{ safeNum(match.equipo_local?.resultado) }}</span>
                            </div>
                            <div class="team-line team-line-center" :class="{ winner: ganadorVisitante(match) }">
                              <img v-if="match.equipo_visitante?.escudo" :src="resolveEscudoUrl(match.equipo_visitante.escudo)" alt="escudo visitante" class="escudo escudo-mini" />
                              <span class="team-line-name">{{ match.equipo_visitante?.nombre || 'Por definir' }}</span>
                              <span class="team-line-score">{{ safeNum(match.equipo_visitante?.resultado) }}</span>
                            </div>
                          </template>
                          <template v-else>
                            <div class="match-placeholder">Final pendiente</div>
                          </template>
                        </article>
                      </div>
                    </section>
                  </div>

                  <div class="bracket-side bracket-side-right">
                    <section class="bracket-round" v-for="(ronda, rIdx) in rondasDerechaRender" :key="`right-${ronda.round}-${rIdx}`">
                      <div class="round-title text-end">{{ ronda.nombre }}</div>
                      <div class="round-matches" :style="getBracketRoundStyle(ronda.depth)">
                        <article
                          class="bracket-match-literal bml-right"
                          v-for="(match, mIdx) in ronda.partidos"
                          :key="match?.id_cruce || `right-${rIdx}-${mIdx}`"
                          :class="{ 'is-placeholder': !match }"
                          @click="match ? abrirDetalleLlave(match, ronda.nombre) : null"
                          @keyup.enter="match ? abrirDetalleLlave(match, ronda.nombre) : null"
                          :tabindex="match ? 0 : -1"
                          :role="match ? 'button' : undefined"
                          :title="match ? 'Ver detalle del partido' : undefined"
                        >
                          <template v-if="match">
                            <div class="team-line team-line-right" :class="{ winner: ganadorLocal(match) }">
                              <span class="team-line-score">{{ safeNum(match.equipo_local?.resultado) }}</span>
                              <span class="team-line-name">{{ match.equipo_local?.nombre || 'Por definir' }}</span>
                              <img v-if="match.equipo_local?.escudo" :src="resolveEscudoUrl(match.equipo_local.escudo)" alt="escudo local" class="escudo escudo-mini" />
                            </div>
                            <div class="team-line team-line-right" :class="{ winner: ganadorVisitante(match) }">
                              <span class="team-line-score">{{ safeNum(match.equipo_visitante?.resultado) }}</span>
                              <span class="team-line-name">{{ match.equipo_visitante?.nombre || 'Por definir' }}</span>
                              <img v-if="match.equipo_visitante?.escudo" :src="resolveEscudoUrl(match.equipo_visitante.escudo)" alt="escudo visitante" class="escudo escudo-mini" />
                            </div>
                          </template>
                          <template v-else>
                            <div class="match-placeholder">Cruce por definir</div>
                          </template>
                        </article>
                      </div>
                    </section>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>

      <div v-else class="panel empty-panel shadow-sm">
        Selecciona un torneo para ver su dashboard.
      </div>

      <Teleport to="body">
        <div v-if="showDetalleModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.4); backdrop-filter: blur(2px);">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Detalle del partido</h5>
                <button type="button" class="btn-close" @click="cerrarDetalleModal"></button>
              </div>
              <div class="modal-body" v-if="partidoDetalle">
                <div class="small text-muted mb-2">{{ partidoDetalle.fase || 'Partido' }}</div>
                <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                  <div class="fw-semibold text-truncate">{{ partidoDetalle.local.nombre }}</div>
                  <div class="fs-5 fw-bold">{{ safeNum(partidoDetalle.local.resultado) }} - {{ safeNum(partidoDetalle.visitante.resultado) }}</div>
                  <div class="fw-semibold text-truncate text-end">{{ partidoDetalle.visitante.nombre }}</div>
                </div>

                <div v-if="partidoDetalle.huboPenales" class="mb-2">
                  <span class="badge text-bg-light border">Penales: {{ safeNum(partidoDetalle.local.penales) }} - {{ safeNum(partidoDetalle.visitante.penales) }}</span>
                </div>

                <div class="d-flex flex-wrap gap-2 mb-2">
                  <span class="badge" :class="estadoClass(partidoDetalle.idEstado)">
                    {{ partidoDetalle.estado || `Estado ${partidoDetalle.idEstado}` }}
                  </span>
                  <span v-if="partidoDetalle.titulo" class="badge text-bg-light border">{{ partidoDetalle.titulo }}</span>
                </div>

                <div class="small text-muted">
                  <div v-if="partidoDetalle.idEvento">ID Evento: {{ partidoDetalle.idEvento }}</div>
                  <div v-if="partidoDetalle.idCruce">ID Cruce: {{ partidoDetalle.idCruce }}</div>
                </div>

                <hr />

                <div class="d-flex align-items-center justify-content-between mb-2">
                  <div class="fw-semibold">Incidencias del partido</div>
                  <div v-if="loadingEventosPartido" class="small text-muted d-flex align-items-center gap-2">
                    <span class="spinner-border spinner-border-sm"></span>
                    Cargando...
                  </div>
                </div>

                <div v-if="!loadingEventosPartido && !eventosPartidoDetalle.length" class="small text-muted">
                  Sin incidencias registradas.
                </div>

                <div v-else-if="eventosPartidoDetalle.length" class="table-responsive">
                  <table class="table table-sm align-middle mb-0">
                    <thead>
                      <tr>
                        <th>Min</th>
                        <th>Equipo</th>
                        <th>Jugador</th>
                        <th>Tipo</th>
                        <th>Obs</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="incidencia in eventosPartidoDetalle" :key="incidencia.id">
                        <td>{{ incidencia.minuto ?? '-' }}</td>
                        <td>{{ incidencia.equipo_nombre || '-' }}</td>
                        <td>{{ formatIncidenciaJugador(incidencia) }}</td>
                        <td>{{ incidencia.tipo_evento_partido_descripcion || '-' }}</td>
                        <td>{{ incidencia.observacion || '-' }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-primary-modern" @click="cerrarDetalleModal">Cerrar</button>
              </div>
            </div>
          </div>
        </div>
      </Teleport>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import datosMaestrosService from '@/services/datosMaestrosService'
import torneoDashboardService from '@/services/torneoDashboardService'
import eventosService from '@/services/eventosService'
import { useToastStore } from '@/stores/toastStore'

const toast = useToastStore()

const loading = ref(false)
const torneos = ref([])
const idTorneoSeleccionado = ref(null)
const dashboard = ref(null)
const topJugadoresGoleadores = ref([])
const proximosPartidos = ref([])

const torneosActivos = computed(() => torneos.value.filter(item => Number(item.activo ?? 1) === 1))
const resumen = computed(() => dashboard.value?.resumen || {})
const ultimosResultados = computed(() => dashboard.value?.ultimos_resultados || [])
const zonas = computed(() => dashboard.value?.zonas || [])
const llave = computed(() => dashboard.value?.llave || [])
const partidoDetalle = ref(null)
const showDetalleModal = ref(false)
const eventosPartidoDetalle = ref([])
const loadingEventosPartido = ref(false)

const normalizeRoundName = (nombre, idx, totalRounds) => {
  const raw = String(nombre || '').trim()
  if (raw && !/^Ronda\s+\d+$/i.test(raw)) return raw

  const remaining = totalRounds - idx
  if (remaining <= 1) return 'Final'
  if (remaining === 2) return 'Semifinales'
  if (remaining === 3) return 'Cuartos de final'
  if (remaining === 4) return 'Octavos de final'
  return `Ronda ${idx + 1}`
}

const sortMatches = (partidos) => [...(Array.isArray(partidos) ? partidos : [])]
  .sort((a, b) => Number(a?.orden || 0) - Number(b?.orden || 0) || Number(a?.id_cruce || 0) - Number(b?.id_cruce || 0))

const fillMatchSlots = (matches, expected) => {
  const slots = Math.max(expected, matches.length)
  const out = new Array(slots).fill(null)
  matches.forEach((m, idx) => {
    if (idx < slots) out[idx] = m
  })
  return out
}

const llaveEstructura = computed(() => {
  const rounds = [...llave.value]
    .sort((a, b) => Number(a?.round || 0) - Number(b?.round || 0))
    .map(r => ({
      ...r,
      partidos: sortMatches(r.partidos),
    }))

  if (!rounds.length) {
    return {
      izquierda: [],
      derecha: [],
      final: { nombre: 'Final', partidos: [] },
    }
  }

  const totalRounds = rounds.length

  const expectedByRound = rounds.map((r, idx) => {
    const basedOnPower = Math.max(1, Math.pow(2, Math.max(0, totalRounds - idx - 1)))
    return Math.max(basedOnPower, r.partidos.length)
  })

  const nonFinalRounds = rounds.slice(0, -1).map((r, idx) => {
    const expected = expectedByRound[idx]
    const leftCount = Math.max(1, Math.floor(expected / 2))
    const rightCount = Math.max(1, expected - leftCount)
    const leftMatches = r.partidos.slice(0, leftCount)
    const rightMatches = [...r.partidos.slice(Math.max(0, r.partidos.length - rightCount))].reverse()

    return {
      round: r.round,
      nombre: normalizeRoundName(r.nombre, idx, totalRounds),
      izquierda: fillMatchSlots(leftMatches, leftCount),
      derecha: fillMatchSlots(rightMatches, rightCount),
    }
  })

  const finalRound = rounds[totalRounds - 1]
  const finalExpected = Math.max(1, expectedByRound[totalRounds - 1])

  return {
    izquierda: nonFinalRounds.map((r, idx) => ({ round: r.round, nombre: r.nombre, partidos: r.izquierda, depth: idx })),
    derecha: nonFinalRounds.map((r, idx) => ({ round: r.round, nombre: r.nombre, partidos: r.derecha, depth: idx })),
    final: {
      nombre: normalizeRoundName(finalRound.nombre, totalRounds - 1, totalRounds),
      partidos: fillMatchSlots(finalRound.partidos, finalExpected),
    },
  }
})

const rondasDerechaRender = computed(() => [...llaveEstructura.value.derecha].reverse())

const getBracketRoundStyle = (roundIdx) => {
  const baseUnit = 20
  const marginTop = Math.max(0, (Math.pow(2, roundIdx) - 1) * baseUnit)
  const gap = Math.max(12, (Math.pow(2, roundIdx + 1) - 1) * baseUnit)
  return {
    marginTop: `${marginTop}px`,
    rowGap: `${gap}px`,
  }
}

const finalRoundStyle = computed(() => {
  const finalDepth = llaveEstructura.value.izquierda.length
  const { marginTop } = getBracketRoundStyle(finalDepth)
  return { marginTop }
})

const getApiMessage = (error, fallback) => error?.response?.data?.message || fallback

const normalizeText = (txt) => String(txt || '')
  .normalize('NFD')
  .replace(/[\u0300-\u036f]/g, '')
  .toUpperCase()

const isTipoGol = (incidencia) => normalizeText(incidencia?.tipo_evento_partido_descripcion).includes('GOL')

const formatFechaHora = (value) => {
  if (!value) return '-'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return String(value)
  return date.toLocaleString('es-AR', {
    day: '2-digit',
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

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

const marcador = (partido) => `${safeNum(partido?.resultado_local)} - ${safeNum(partido?.resultado_visitante)}`
const huboPenales = (partido) => partido?.resultado_penales_local !== null || partido?.resultado_penales_visitante !== null
const safeNum = (n) => (n === null || n === undefined ? '-' : Number(n))

const estadoClass = (idEstado) => {
  if (Number(idEstado) === 7) return 'estado-reportado'
  if (Number(idEstado) === 4) return 'estado-finalizado'
  if (Number(idEstado) === 2) return 'estado-programado'
  return 'estado-default'
}

const ganadorLocal = (match) => {
  const rl = Number(match?.equipo_local?.resultado)
  const rv = Number(match?.equipo_visitante?.resultado)
  if (!Number.isFinite(rl) || !Number.isFinite(rv)) return false
  if (rl > rv) return true
  if (rl < rv) return false

  const pl = Number(match?.equipo_local?.penales)
  const pv = Number(match?.equipo_visitante?.penales)
  if (!Number.isFinite(pl) || !Number.isFinite(pv)) return false
  return pl > pv
}

const ganadorVisitante = (match) => {
  const rl = Number(match?.equipo_local?.resultado)
  const rv = Number(match?.equipo_visitante?.resultado)
  if (!Number.isFinite(rl) || !Number.isFinite(rv)) return false
  if (rv > rl) return true
  if (rv < rl) return false

  const pl = Number(match?.equipo_local?.penales)
  const pv = Number(match?.equipo_visitante?.penales)
  if (!Number.isFinite(pl) || !Number.isFinite(pv)) return false
  return pv > pl
}

const cerrarDetalleModal = () => {
  showDetalleModal.value = false
  partidoDetalle.value = null
  eventosPartidoDetalle.value = []
  loadingEventosPartido.value = false
}

const formatIncidenciaJugador = (incidencia) => {
  const apellido = String(incidencia?.jugador_apellido || '').trim()
  const nombre = String(incidencia?.jugador_nombre || '').trim()
  if (apellido && nombre) return `${apellido}, ${nombre}`
  if (apellido) return apellido
  if (nombre) return nombre
  return '-'
}

const cargarEventosPartidoDetalle = async (idEvento) => {
  if (!idEvento) {
    eventosPartidoDetalle.value = []
    return
  }

  loadingEventosPartido.value = true
  try {
    const data = await eventosService.getEventosPartido(Number(idEvento))
    eventosPartidoDetalle.value = Array.isArray(data) ? data : []
  } catch {
    eventosPartidoDetalle.value = []
  } finally {
    loadingEventosPartido.value = false
  }
}

const abrirDetalleResultado = async (partido) => {
  partidoDetalle.value = {
    fase: 'Ultimos resultados',
    idEvento: Number(partido?.id || 0) || null,
    idCruce: null,
    titulo: partido?.titulo || null,
    estado: partido?.estado || null,
    idEstado: Number(partido?.id_estado_evento || 0),
    local: {
      nombre: partido?.equipo_local_nombre || 'Local',
      resultado: partido?.resultado_local ?? null,
      penales: partido?.resultado_penales_local ?? null,
    },
    visitante: {
      nombre: partido?.equipo_visitante_nombre || 'Visitante',
      resultado: partido?.resultado_visitante ?? null,
      penales: partido?.resultado_penales_visitante ?? null,
    },
    huboPenales: partido?.resultado_penales_local !== null || partido?.resultado_penales_visitante !== null,
  }
  showDetalleModal.value = true
  await cargarEventosPartidoDetalle(partidoDetalle.value.idEvento)
}

const abrirDetalleLlave = async (match, fase) => {
  partidoDetalle.value = {
    fase: fase || 'Llave eliminatoria',
    idEvento: Number(match?.id_evento || 0) || null,
    idCruce: Number(match?.id_cruce || 0) || null,
    titulo: match?.titulo || match?.nombre || null,
    estado: match?.estado || null,
    idEstado: Number(match?.id_estado_evento || 0),
    local: {
      nombre: match?.equipo_local?.nombre || 'Por definir',
      resultado: match?.equipo_local?.resultado ?? null,
      penales: match?.equipo_local?.penales ?? null,
    },
    visitante: {
      nombre: match?.equipo_visitante?.nombre || 'Por definir',
      resultado: match?.equipo_visitante?.resultado ?? null,
      penales: match?.equipo_visitante?.penales ?? null,
    },
    huboPenales: match?.equipo_local?.penales !== null || match?.equipo_visitante?.penales !== null,
  }
  showDetalleModal.value = true
  await cargarEventosPartidoDetalle(partidoDetalle.value.idEvento)
}

const cargarTorneos = async () => {
  loading.value = true
  try {
    const data = await datosMaestrosService.getTorneos()
    torneos.value = Array.isArray(data) ? data : []

    if (torneosActivos.value.length) {
      idTorneoSeleccionado.value = Number(torneosActivos.value[0].id)
      await cargarDashboardSeleccionado()
    }
  } catch (error) {
    toast.showToast({ message: getApiMessage(error, 'No se pudo cargar la lista de torneos.'), type: 'danger' })
  } finally {
    loading.value = false
  }
}

const cargarDashboardSeleccionado = async () => {
  if (!idTorneoSeleccionado.value) {
    dashboard.value = null
    topJugadoresGoleadores.value = []
    proximosPartidos.value = []
    return
  }

  loading.value = true
  try {
    const idTorneo = Number(idTorneoSeleccionado.value)
    const [dashboardData, eventosData] = await Promise.all([
      torneoDashboardService.getDashboard(idTorneo),
      eventosService.getEventos(),
    ])

    dashboard.value = dashboardData

    const eventosTorneo = (Array.isArray(eventosData) ? eventosData : [])
      .filter(ev => Number(ev.id_torneo) === idTorneo)
      .filter(ev => String(ev.tipo_evento || '').toLowerCase() === 'partido')

    const pendientes = eventosTorneo
      .filter(ev => Number(ev.id_estado_evento) === 2)
      .sort((a, b) => String(a.fecha_hora_inicio || '').localeCompare(String(b.fecha_hora_inicio || '')))

    const pendientesConFechaNumero = pendientes.filter(ev => Number.isFinite(Number(ev.numero_fecha)) && Number(ev.numero_fecha) > 0)
    if (pendientesConFechaNumero.length) {
      const proximaFechaNumero = Math.min(...pendientesConFechaNumero.map(ev => Number(ev.numero_fecha)))
      proximosPartidos.value = pendientesConFechaNumero.filter(ev => Number(ev.numero_fecha) === proximaFechaNumero)
    } else if (pendientes.length) {
      const primerDia = String(pendientes[0].fecha_hora_inicio || '').slice(0, 10)
      proximosPartidos.value = pendientes.filter(ev => String(ev.fecha_hora_inicio || '').slice(0, 10) === primerDia)
    } else {
      proximosPartidos.value = []
    }

    const idsEventos = eventosTorneo.map(ev => Number(ev.id)).filter(id => id > 0)
    const incidenciasPorEvento = await Promise.all(idsEventos.map(idEvento => eventosService.getEventosPartido(idEvento).catch(() => [])))

    const goleadoresMap = new Map()
    incidenciasPorEvento.flat().filter(isTipoGol).forEach(inc => {
      const idJugador = Number(inc.id_jugador || 0)
      if (!idJugador) return

      const key = idJugador
      const anterior = goleadoresMap.get(key) || {
        id_jugador: idJugador,
        nombre_mostrar: formatIncidenciaJugador(inc),
        equipo_nombre: inc.equipo_nombre || '-',
        goles: 0,
      }

      anterior.goles += 1
      if (!anterior.equipo_nombre || anterior.equipo_nombre === '-') {
        anterior.equipo_nombre = inc.equipo_nombre || '-'
      }
      goleadoresMap.set(key, anterior)
    })

    topJugadoresGoleadores.value = [...goleadoresMap.values()]
      .sort((a, b) => Number(b.goles) - Number(a.goles) || String(a.nombre_mostrar).localeCompare(String(b.nombre_mostrar)))
      .slice(0, 10)
  } catch (error) {
    dashboard.value = null
    topJugadoresGoleadores.value = []
    proximosPartidos.value = []
    toast.showToast({ message: getApiMessage(error, 'No se pudo cargar el dashboard del torneo.'), type: 'danger' })
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  cargarTorneos()
})
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;700;800&family=Oswald:wght@500;700&display=swap');

.torneo-dashboard {
  --bg-a: #f6efe2;
  --bg-b: #f4f8ee;
  --panel: #ffffff;
  --ink: #1f2a33;
  --muted: #637382;
  --line: #dce4e8;
  --accent: #d15f28;
  --accent-soft: #ffe6d8;
  --blue-soft: #dff2ff;
  --green-soft: #dff7eb;
  --amber-soft: #fff5d8;

  min-height: 100vh;
  background: radial-gradient(circle at 20% 20%, var(--bg-a), transparent 42%), radial-gradient(circle at 80% 8%, #dceee0, transparent 38%), linear-gradient(150deg, #fbfcf7 0%, var(--bg-b) 100%);
  position: relative;
  overflow: hidden;
  font-family: 'Barlow', sans-serif;
  color: var(--ink);
}

.bg-shape {
  position: absolute;
  border-radius: 999px;
  filter: blur(50px);
  opacity: 0.45;
  pointer-events: none;
}

.bg-shape-a {
  width: 290px;
  height: 290px;
  background: #ffd5b9;
  top: -120px;
  right: -40px;
}

.bg-shape-b {
  width: 260px;
  height: 260px;
  background: #c9e7ff;
  bottom: -110px;
  left: -60px;
}

.title-page {
  font-family: 'Oswald', sans-serif;
  letter-spacing: 0.04em;
  font-size: 1.45rem;
}

.subtitle-page {
  color: var(--muted);
  font-size: 0.9rem;
}

.selector-wrap {
  min-width: 260px;
}

.btn-back-arrow {
  width: 36px;
  height: 36px;
  border: 1px solid var(--line);
  border-radius: 8px;
  background: #fff;
}

.panel {
  background: var(--panel);
  border: 1px solid var(--line);
  border-radius: 16px;
  padding: 1rem;
  box-shadow: 0 10px 24px rgba(22, 35, 46, 0.06);
}

.loading-panel,
.empty-panel {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 200px;
}

.hero {
  border: 1px solid rgba(0, 0, 0, 0.05);
  border-radius: 16px;
  padding: 1rem;
  background: linear-gradient(120deg, #fff 10%, #fffaf6 55%, #f1f9ff 100%);
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  align-items: center;
  flex-wrap: wrap;
}

.hero-label {
  color: var(--muted);
  font-size: 0.78rem;
  text-transform: uppercase;
  letter-spacing: 0.07em;
}

.hero-name {
  font-family: 'Oswald', sans-serif;
  font-size: 1.6rem;
  margin-bottom: 0.35rem;
}

.stat-card {
  padding: 0.9rem;
}

.stat-label {
  color: var(--muted);
  font-size: 0.85rem;
}

.stat-value {
  font-size: 1.6rem;
  font-weight: 800;
  line-height: 1.1;
}

.section-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.7rem;
}

.section-title {
  font-family: 'Oswald', sans-serif;
  letter-spacing: 0.03em;
  font-size: 1.2rem;
}

.results-list {
  display: grid;
  gap: 0.65rem;
}

.result-item {
  border: 1px solid var(--line);
  border-radius: 12px;
  padding: 0.65rem 0.75rem;
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  gap: 0.7rem;
  align-items: center;
}

.clickable-match {
  cursor: pointer;
  transition: border-color 0.12s ease, box-shadow 0.12s ease;
}

.clickable-match:hover,
.clickable-match:focus-visible {
  border-color: #b8d0de;
  box-shadow: 0 0 0 2px rgba(63, 134, 176, 0.12);
  outline: none;
}

.team {
  display: flex;
  align-items: center;
  gap: 0.45rem;
}

.team-away {
  justify-content: flex-end;
}

.team-name {
  font-weight: 600;
  font-size: 0.92rem;
}

.result-center {
  text-align: center;
  min-width: 130px;
}

.score {
  font-size: 1.1rem;
  font-weight: 800;
}

.pens {
  font-size: 0.75rem;
  color: var(--muted);
}

.estado-chip,
.match-foot {
  font-size: 0.73rem;
  border-radius: 999px;
  display: inline-block;
  padding: 0.18rem 0.5rem;
}

.estado-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 0.65rem;
}

.estado-card {
  border: 1px solid var(--line);
  border-radius: 12px;
  padding: 0.8rem;
  background: #fff;
}

.estado-name {
  font-size: 0.82rem;
  color: var(--muted);
}

.estado-count {
  font-size: 1.55rem;
  font-weight: 800;
}

.goleadores-list {
  display: grid;
  gap: 0.55rem;
}

.goleador-item {
  border: 1px solid var(--line);
  border-radius: 12px;
  padding: 0.55rem 0.7rem;
  display: grid;
  grid-template-columns: 30px 1fr auto;
  align-items: center;
  gap: 0.55rem;
  background: #fff;
}

.goleador-pos {
  width: 24px;
  height: 24px;
  border-radius: 999px;
  background: var(--accent-soft);
  color: #9a3d15;
  font-weight: 700;
  font-size: 0.78rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.goleador-name {
  font-weight: 700;
  line-height: 1.1;
}

.goleador-team {
  color: var(--muted);
  font-size: 0.8rem;
}

.goleador-goles {
  font-size: 1.15rem;
  font-weight: 800;
  color: #163552;
}

.zona-card {
  border: 1px solid var(--line);
  border-radius: 12px;
  padding: 0.7rem;
  background: #fff;
}

.zona-title {
  font-weight: 700;
  margin-bottom: 0.45rem;
}

.custom-table th {
  color: var(--muted);
  font-size: 0.78rem;
}

.bracket-wrap {
  overflow-x: auto;
  padding-bottom: 0.45rem;
}

.bracket-board {
  min-width: 1120px;
  min-height: 430px;
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  gap: 1.25rem;
  align-items: start;
}

.bracket-side {
  display: flex;
  gap: 1.1rem;
  align-items: flex-start;
}

.bracket-side-left {
  justify-content: flex-end;
}

.bracket-side-right {
  justify-content: flex-start;
}

.bracket-round {
  min-width: 255px;
}

.round-matches {
  display: flex;
  flex-direction: column;
}

.bracket-center {
  min-width: 320px;
  display: flex;
  justify-content: center;
  align-items: center;
}

.bracket-round-final {
  width: 100%;
}

.final-matches {
  gap: 20px;
}

.round-title {
  font-family: 'Oswald', sans-serif;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  font-size: 1.08rem;
  margin-bottom: 0.45rem;
}

.bracket-match-literal {
  position: relative;
  min-height: 104px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 0.3rem 0;
}

.bracket-match-literal[role='button'] {
  cursor: pointer;
}

.bracket-match-literal[role='button']:hover,
.bracket-match-literal[role='button']:focus-visible {
  background: rgba(255, 255, 255, 0.55);
  border-radius: 8px;
  outline: none;
}

.team-line {
  display: flex;
  align-items: center;
  gap: 0.42rem;
  min-height: 24px;
  font-size: 0.9rem;
}

.team-line + .team-line {
  margin-top: 22px;
}

.team-line-left {
  justify-content: flex-end;
  padding-right: 30px;
}

.team-line-right {
  justify-content: flex-start;
  padding-left: 30px;
}

.team-line-center {
  justify-content: center;
}

.team-line-name {
  max-width: 170px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.team-line-score {
  font-weight: 800;
  min-width: 20px;
  text-align: center;
}

.bml-left::before,
.bml-right::before,
.bml-final::before {
  content: '';
  position: absolute;
  border-left: 2px solid #d5e2ea;
}

.bml-left::before {
  right: 22px;
  top: 10px;
  bottom: 10px;
}

.bml-right::before {
  left: 22px;
  top: 10px;
  bottom: 10px;
}

.bml-final::before {
  display: none;
}

.bml-left::after,
.bml-right::after,
.bml-final::after {
  content: '';
  position: absolute;
  top: 50%;
  border-top: 2px solid #d5e2ea;
}

.bml-left::after {
  right: 0;
  width: 22px;
}

.bml-right::after {
  left: 0;
  width: 22px;
}

.bml-final::after {
  display: none;
}

.bracket-match-literal.is-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 104px;
}

.match-placeholder {
  font-size: 0.82rem;
  color: #8495a2;
  font-style: italic;
}

.winner {
  color: #0f6a41;
  font-weight: 700;
}

.match-head {
  padding: 0.5rem 0.65rem;
  font-weight: 700;
  font-size: 0.85rem;
  background: #f8fbfd;
  border-bottom: 1px solid var(--line);
}

.linea-equipo {
  padding: 0.5rem 0.65rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px dashed #e9eff3;
}

.line-left {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  font-size: 0.86rem;
}

.line-score {
  font-weight: 800;
}

.winner {
  background: #f2fff7;
}

.match-foot {
  margin: 0.45rem;
}

.escudo {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  object-fit: cover;
  border: 1px solid #d4e0e6;
}

.escudo-mini {
  width: 24px;
  height: 24px;
}

.empty-state {
  color: var(--muted);
  font-size: 0.9rem;
  padding: 0.8rem 0.2rem;
}

.estado-finalizado {
  background: var(--blue-soft);
  color: #1c4f8a;
}

.estado-reportado {
  background: var(--green-soft);
  color: #145b3c;
}

.estado-programado {
  background: var(--amber-soft);
  color: #815f13;
}

.estado-default {
  background: #eff3f6;
  color: #506272;
}

@media (max-width: 768px) {
  .result-item {
    grid-template-columns: 1fr;
    text-align: center;
  }

  .team,
  .team-away {
    justify-content: center;
  }

  .hero-name {
    font-size: 1.25rem;
  }

  .bracket-board {
    min-width: 980px;
  }
}
</style>
