<template>
  <div class="container-fluid p-4 bg-white min-vh-100 animate-fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom flex-wrap gap-2">
      <div class="d-flex align-items-center">
        <button @click="$router.back()" class="btn-back-arrow me-2" title="Volver">
          <i class="bi bi-arrow-left"></i>
        </button>
        <div>
          <h1 class="h5 fw-bold mb-0 text-secondary">ASIGNACIONES DE EQUIPOS</h1>
          <div v-if="detalle" class="small text-muted">{{ detalle.torneo.nombre }}</div>
        </div>
      </div>
      <button class="btn btn-outline-secondary" @click="cargarDetalle()" :disabled="loadingDetalle || !idTorneoSeleccionado">
        <span v-if="loadingDetalle" class="spinner-border spinner-border-sm me-2"></span>
        Recargar
      </button>
    </div>

    <div v-if="!idTorneoSeleccionado" class="alert alert-info mb-0 d-flex align-items-center justify-content-between flex-wrap gap-2">
      <span>No hay un torneo seleccionado.</span>
      <button class="btn btn-sm btn-outline-primary" @click="$router.push('/gestiontorneos')">
        Ir a Gestión de torneos
      </button>
    </div>

    <div v-else-if="loadingDetalle" class="card shadow-sm border-0 rounded-lg">
      <div class="card-body py-4 text-center text-muted">
        <span class="spinner-border spinner-border-sm me-2"></span>
        Cargando...
      </div>
    </div>

    <div v-else-if="detalle" class="card shadow-sm border-0 rounded-lg">
      <div class="card-body p-4">
        <h2 class="h6 fw-bold text-secondary mb-3">{{ detalle.torneo.nombre }}</h2>

        <ul v-if="!esFormatoLiga" class="nav nav-pills mb-3 asignaciones-subtabs">
          <li class="nav-item">
            <button class="nav-link" :class="{ active: subTabAsignaciones === 'zonas' }" @click="subTabAsignaciones = 'zonas'">
              Asignaciones de zonas
            </button>
          </li>
          <li class="nav-item">
            <button class="nav-link" :class="{ active: subTabAsignaciones === 'cruces' }" @click="subTabAsignaciones = 'cruces'">
              Asignaciones de cruces
            </button>
          </li>
        </ul>

        <template v-if="esFormatoLiga || subTabAsignaciones === 'zonas'">
          <div v-if="!detalle.grupos?.length" class="alert alert-warning mb-0">
            {{ esFormatoLiga ? 'Este torneo no tiene fixture generado aún.' : 'Este torneo no tiene grupos configurados (fase de zonas).' }}
          </div>

          <template v-else-if="asignacionesCompletas">
            <div class="d-flex justify-content-between align-items-start mb-3">
              <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                  <span class="badge bg-success-subtle text-success rounded-pill"><i class="bi bi-check-circle me-1"></i>Asignaciones completas</span>
                  <span v-if="hayPartidosJugados" class="badge bg-danger-subtle text-danger rounded-pill">
                    <i class="bi bi-lock me-1"></i>Hay partidos jugados
                  </span>
                </div>
                <p class="small text-muted mb-0">Los equipos ya quedaron distribuidos en sus grupos.</p>
              </div>
              <button
                class="btn btn-outline-danger btn-sm"
                :disabled="hayPartidosJugados || savingEliminarAsignaciones"
                :title="hayPartidosJugados ? 'No se puede eliminar: hay partidos finalizados' : 'Eliminar todas las asignaciones'"
                @click="eliminarAsignaciones"
              >
                <span v-if="savingEliminarAsignaciones" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="bi bi-trash me-1"></i>
                Eliminar asignaciones
              </button>
            </div>

            <div class="row g-3">
              <div v-for="grupo in asignacionesPorGrupo" :key="grupo.id" class="col-12 col-md-6">
                <div class="group-card h-100">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <h3 class="h6 mb-0">{{ grupo.nombre }}</h3>
                    <small class="text-muted">{{ grupo.equipos.length }}/{{ grupo.cantidad_equipos_objetivo }}</small>
                  </div>
                  <div v-if="!grupo.equipos.length" class="text-muted small">Sin equipos asignados.</div>
                  <ul v-else class="list-unstyled mb-0">
                    <li
                      v-for="asig in grupo.equipos"
                      :key="asig.id_equipo"
                      class="d-flex align-items-center gap-2 py-1 border-bottom"
                    >
                      <span class="text-muted small" style="width:1.4rem;text-align:right;">{{ asig.posicion_inicial }}.</span>
                      <img v-if="asig.escudo" :src="resolveEscudoUrl(asig.escudo)" alt="escudo" class="escudo-thumb" />
                      <span v-else class="escudo-thumb-placeholder"><i class="bi bi-shield"></i></span>
                      <span class="fw-semibold">{{ asig.equipo_nombre }}</span>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </template>

          <template v-else>
            <div v-if="!esFormatoLiga" class="table-responsive mb-3">
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

            <div v-if="esFormatoLiga" class="alert alert-info mb-3">
              <i class="bi bi-info-circle me-1"></i>
              Hay <strong>{{ poolTeams.length }}</strong> equipos inscriptos. Hacé clic en <strong>Asignar aleatorio</strong> para distribuirlos en el fixture y luego en <strong>Guardar asignación</strong>.
            </div>

            <div v-if="!esFormatoLiga" class="pool-box mb-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <strong>Equipos para sorteo</strong>
                <small class="text-muted">{{ selectedPool.length }}/{{ totalSlots }} seleccionados</small>
              </div>
              <div class="row g-2">
                <div v-for="equipo in poolTeams" :key="equipo.id" class="col-12 col-md-6">
                  <label class="pool-item">
                    <input type="checkbox" :value="Number(equipo.id)" v-model="selectedPool" />
                    <img v-if="equipo.escudo" :src="resolveEscudoUrl(equipo.escudo)" alt="escudo" class="escudo-thumb" />
                    <span class="me-auto">{{ equipo.nombre }}</span>
                    <span v-if="equipo.asignado" class="badge rounded-pill bg-info-subtle text-info">Asignado</span>
                  </label>
                </div>
              </div>
            </div>

            <div v-if="!esFormatoLiga" class="row g-3">
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
                      <img :src="resolveEscudoUrl(getSelectedTeam(grupo.id, pos).escudo)" alt="escudo" class="escudo-thumb" />
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

        <template v-else-if="!esFormatoLiga">
          <!-- LIGA: sin cruces, solo tabla de posiciones -->
          <template v-if="esFormatoLiga">
            <div class="alert alert-info mb-3">
              <i class="bi bi-info-circle me-1"></i>
              Este torneo es de formato <strong>Liga (todos contra todos)</strong>. No hay fase de cruces eliminatorios. La clasificación final se determina por la tabla de posiciones.
            </div>
            <div v-if="detalle?.zonas?.length">
              <div v-for="zona in detalle.zonas" :key="zona.id_grupo_torneo">
                <div class="table-responsive">
                  <table class="table table-sm align-middle mb-0 custom-table">
                    <thead>
                      <tr>
                        <th>#</th>
                        <th>Equipo</th>
                        <th class="text-center">PJ</th>
                        <th class="text-center">PG</th>
                        <th class="text-center">PE</th>
                        <th class="text-center">PP</th>
                        <th class="text-center">GF</th>
                        <th class="text-center">GC</th>
                        <th class="text-center">Dif</th>
                        <th class="text-center fw-bold">Pts</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(eq, idx) in zona.equipos" :key="eq.id">
                        <td class="text-muted small">{{ idx + 1 }}</td>
                        <td>
                          <img v-if="eq.escudo" :src="resolveEscudoUrl(eq.escudo)" alt="escudo" class="escudo-thumb me-1" />
                          <span v-else class="escudo-thumb-placeholder me-1"><i class="bi bi-shield"></i></span>
                          {{ eq.nombre }}
                        </td>
                        <td class="text-center">{{ eq.pj }}</td>
                        <td class="text-center">{{ eq.pg }}</td>
                        <td class="text-center">{{ eq.pe }}</td>
                        <td class="text-center">{{ eq.pp }}</td>
                        <td class="text-center">{{ eq.gf }}</td>
                        <td class="text-center">{{ eq.gc }}</td>
                        <td class="text-center" :class="eq.dif > 0 ? 'text-success' : eq.dif < 0 ? 'text-danger' : ''">{{ eq.dif > 0 ? '+' : '' }}{{ eq.dif }}</td>
                        <td class="text-center fw-bold">{{ eq.pts }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
            <div v-else class="text-muted small">Los equipos aún no han sido asignados al torneo.</div>
          </template>

          <template v-else>
          <div v-if="!crucesHabilitados" class="alert alert-warning mb-3">
            Los cruces se habilitan cuando la fase de zonas está concretada (todos los partidos de zona finalizados) o cuando el torneo no tiene fase de zonas.
          </div>

          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="small text-muted">
              Cruces definidos: <strong>{{ crucesConEquiposDefinidos }}</strong>/<strong>{{ eventosCruceProgramacion.length }}</strong>
            </div>
            <div class="d-flex align-items-center gap-2">
              <span class="badge rounded-pill" :class="crucesHabilitados ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning'">
                {{ crucesHabilitados ? 'Cruces habilitados' : 'Cruces bloqueados' }}
              </span>
              <button
                class="btn btn-sm btn-outline-primary"
                @click="asignarCruces"
                :disabled="savingAsignacionCruces || !crucesHabilitados || !eventosCruceProgramacion.length"
              >
                <span v-if="savingAsignacionCruces" class="spinner-border spinner-border-sm me-2"></span>
                Asignar equipos a cruces
              </button>
            </div>
          </div>

          <div v-if="!eventosCruceProgramacion.length" class="alert alert-info mb-0">
            No hay cruces configurados para este torneo.
          </div>

          <template v-else>
            <div class="cruce-bracket-box" :class="esFormatoConsuelo && cruceRoundsConsuelo.length ? 'mb-2' : 'mb-3'">
              <div class="small fw-semibold text-secondary mb-2">
                {{ esFormatoConsuelo ? 'Zona Ganadores' : 'Visualización de llave' }}
              </div>
              <div class="cruce-bracket-scroll">
                <div class="cruce-bracket-grid" :style="{ '--round-count': Math.max(1, cruceRounds.length) }">
                  <div v-for="(ronda, roundIndex) in cruceRounds" :key="`g-${ronda.nombre}-${roundIndex}`" class="cruce-round-column">
                    <h3 class="cruce-round-title">{{ ronda.nombre }}</h3>
                    <div class="cruce-round-track" :style="getCruceRoundTrackStyle(roundIndex, ronda.partidos.length)">
                      <div
                        v-for="(partido, partidoIndex) in ronda.partidos"
                        :key="partido.id"
                        class="cruce-match-wrapper"
                        :style="getCruceMatchStyle(roundIndex, partidoIndex)"
                      >
                        <div class="cruce-match-card" :class="{ 'has-next': roundIndex < cruceRounds.length - 1, 'has-prev': roundIndex > 0 }">
                          <div class="cruce-team-line">
                            <img v-if="partido.local.escudo" :src="resolveEscudoUrl(partido.local.escudo)" alt="escudo local" class="escudo-thumb" />
                            <span v-else class="escudo-thumb-placeholder"><i class="bi bi-shield"></i></span>
                            <span class="team-name">{{ partido.local.nombre || 'Por definir' }}</span>
                          </div>
                          <div class="cruce-team-line">
                            <img v-if="partido.visitante.escudo" :src="resolveEscudoUrl(partido.visitante.escudo)" alt="escudo visitante" class="escudo-thumb" />
                            <span v-else class="escudo-thumb-placeholder"><i class="bi bi-shield"></i></span>
                            <span class="team-name">{{ partido.visitante.nombre || 'Por definir' }}</span>
                          </div>
                          <div v-if="partido.resultado !== null" class="cruce-score-pill">{{ partido.resultado }}</div>
                        </div>
                      </div>
                      <div
                        v-if="roundIndex < cruceRounds.length - 1"
                        v-for="pairIndex in Math.floor(ronda.partidos.length / 2)"
                        :key="`gmerge-${roundIndex}-${pairIndex}`"
                        class="cruce-round-merge"
                        :style="getCruceMergeStyle(roundIndex, pairIndex - 1)"
                      ></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="esFormatoConsuelo && cruceRoundsConsuelo.length" class="cruce-bracket-box mb-3" style="border-left: 3px solid #d97706;">
              <div class="small fw-semibold mb-2" style="color: #b45309;">Rueda Consuelo</div>
              <div class="cruce-bracket-scroll">
                <div class="cruce-bracket-grid" :style="{ '--round-count': Math.max(1, cruceRoundsConsuelo.length) }">
                  <div v-for="(ronda, roundIndex) in cruceRoundsConsuelo" :key="`c-${ronda.nombre}-${roundIndex}`" class="cruce-round-column">
                    <h3 class="cruce-round-title">{{ ronda.nombre }}</h3>
                    <div class="cruce-round-track" :style="getCruceRoundTrackStyle(roundIndex, ronda.partidos.length)">
                      <div
                        v-for="(partido, partidoIndex) in ronda.partidos"
                        :key="partido.id"
                        class="cruce-match-wrapper"
                        :style="getCruceMatchStyle(roundIndex, partidoIndex)"
                      >
                        <div class="cruce-match-card" :class="{ 'has-next': roundIndex < cruceRoundsConsuelo.length - 1, 'has-prev': roundIndex > 0 }">
                          <div class="cruce-team-line">
                            <img v-if="partido.local.escudo" :src="resolveEscudoUrl(partido.local.escudo)" alt="escudo local" class="escudo-thumb" />
                            <span v-else class="escudo-thumb-placeholder"><i class="bi bi-shield"></i></span>
                            <span class="team-name">{{ partido.local.nombre || 'Por definir' }}</span>
                          </div>
                          <div class="cruce-team-line">
                            <img v-if="partido.visitante.escudo" :src="resolveEscudoUrl(partido.visitante.escudo)" alt="escudo visitante" class="escudo-thumb" />
                            <span v-else class="escudo-thumb-placeholder"><i class="bi bi-shield"></i></span>
                            <span class="team-name">{{ partido.visitante.nombre || 'Por definir' }}</span>
                          </div>
                          <div v-if="partido.resultado !== null" class="cruce-score-pill">{{ partido.resultado }}</div>
                        </div>
                      </div>
                      <div
                        v-if="roundIndex < cruceRoundsConsuelo.length - 1"
                        v-for="pairIndex in Math.floor(ronda.partidos.length / 2)"
                        :key="`cmerge-${roundIndex}-${pairIndex}`"
                        class="cruce-round-merge"
                        :style="getCruceMergeStyle(roundIndex, pairIndex - 1)"
                      ></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </template>

          <div v-if="eventosCruceProgramacion.length" class="table-responsive">
            <table class="table table-sm align-middle mb-0">
              <thead>
                <tr>
                  <th>Cruce</th>
                  <th>Local</th>
                  <th>Visitante</th>
                  <th>Estado</th>
                  <th class="text-end">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="ev in eventosCruceProgramacion" :key="ev.id">
                  <td>{{ ev.titulo }}</td>
                  <td>
                    <template v-if="isEditingCruceManual(ev.id)">
                      <select
                        class="form-select form-select-sm"
                        :value="getCruceDraft(ev.id).id_equipo_local ?? ''"
                        @change="setCruceDraftField(ev.id, 'id_equipo_local', $event.target.value)"
                        :disabled="!crucesHabilitados || isSavingCruceManual(ev.id)"
                      >
                        <option value="">Por definir</option>
                        <option v-for="equipo in equiposCruceOptions" :key="`l-${ev.id}-${equipo.id}`" :value="equipo.id">
                          {{ equipo.nombre }}
                        </option>
                      </select>
                    </template>
                    <template v-else>
                      <span>{{ getCruceEquipoDisplay(ev, 'local').nombre || 'Por definir' }}</span>
                    </template>
                  </td>
                  <td>
                    <template v-if="isEditingCruceManual(ev.id)">
                      <select
                        class="form-select form-select-sm"
                        :value="getCruceDraft(ev.id).id_equipo_visitante ?? ''"
                        @change="setCruceDraftField(ev.id, 'id_equipo_visitante', $event.target.value)"
                        :disabled="!crucesHabilitados || isSavingCruceManual(ev.id)"
                      >
                        <option value="">Por definir</option>
                        <option v-for="equipo in equiposCruceOptions" :key="`v-${ev.id}-${equipo.id}`" :value="equipo.id">
                          {{ equipo.nombre }}
                        </option>
                      </select>
                    </template>
                    <template v-else>
                      <span>{{ getCruceEquipoDisplay(ev, 'visitante').nombre || 'Por definir' }}</span>
                    </template>
                  </td>
                  <td>
                    <span class="badge rounded-pill" :class="ev.equipo_local_nombre && ev.equipo_visitante_nombre ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary'">
                      {{ ev.equipo_local_nombre && ev.equipo_visitante_nombre ? 'Definido' : 'Pendiente' }}
                    </span>
                  </td>
                  <td class="text-end">
                    <div class="d-inline-flex gap-2">
                      <button
                        v-if="!isEditingCruceManual(ev.id)"
                        class="btn btn-sm btn-outline-primary"
                        @click="editarCruceManual(ev)"
                        :disabled="!crucesHabilitados || isSavingCruceManual(ev.id)"
                      >
                        Editar
                      </button>

                      <template v-else>
                        <button
                          class="btn btn-sm btn-outline-secondary"
                          @click="cancelarEdicionCruceManual(ev)"
                          :disabled="isSavingCruceManual(ev.id)"
                        >
                          Cancelar
                        </button>
                        <button
                          class="btn btn-sm btn-success"
                          @click="guardarAsignacionCruceManual(ev)"
                          :disabled="!crucesHabilitados || isSavingCruceManual(ev.id)"
                        >
                          <span v-if="isSavingCruceManual(ev.id)" class="spinner-border spinner-border-sm me-1"></span>
                          Guardar
                        </button>
                      </template>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          </template><!-- end v-else (no-liga) -->
        </template><!-- end v-else (no zonas tab) -->
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import planTorneoService from '@/services/torneos/planTorneoService'
import { useToastStore } from '@/stores/toastStore'
import { useTorneoGestionStore } from '@/stores/torneoGestionStore'

const toast = useToastStore()
const route = useRoute()
const torneoGestionStore = useTorneoGestionStore()

// El torneo lo elige el usuario en Gestión de torneos; acá solo se lee del store.
const idTorneoSeleccionado = computed(() => torneoGestionStore.idTorneoSeleccionado)
const detalle = ref(null)
const loadingDetalle = ref(false)

const savingAsignacion = ref(false)
const savingAsignacionCruces = ref(false)
const savingAsignacionCruceIds = ref([])
const savingEliminarAsignaciones = ref(false)
const selected = ref({})
const selectedPool = ref([])
const cruceDrafts = ref({})
const programacionData = ref(null)
const editingCruceManualIds = ref([])
const subTabAsignaciones = ref('zonas')

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

const keyOf = (idGrupo, pos) => `${idGrupo}-${pos}`

const buildCruceDrafts = () => {
  const next = {}
  for (const ev of (programacionData.value?.eventos || [])) {
    const titulo = String(ev?.titulo || '').trim()
    if (/^zona\s/i.test(titulo)) continue
    const id = Number(ev.id)
    next[id] = {
      id_equipo_local: ev?.id_equipo_local ? Number(ev.id_equipo_local) : null,
      id_equipo_visitante: ev?.id_equipo_visitante ? Number(ev.id_equipo_visitante) : null,
    }
  }
  cruceDrafts.value = next
  const permitidos = new Set(Object.keys(next).map(Number))
  editingCruceManualIds.value = editingCruceManualIds.value.filter(id => permitidos.has(Number(id)))
}

const cargarDetalle = async (idTorneo = null) => {
  if (idTorneo !== null) {
    torneoGestionStore.seleccionar(idTorneo)
  }

  if (!idTorneoSeleccionado.value) return
  loadingDetalle.value = true
  try {
    const [detalleData, dataProgramacion] = await Promise.all([
      planTorneoService.getDetalleGestion(idTorneoSeleccionado.value),
      planTorneoService.getProgramacionData(idTorneoSeleccionado.value, 'todas'),
    ])

    detalle.value = detalleData
    programacionData.value = dataProgramacion
    buildCruceDrafts()

    const next = {}
    for (const item of (detalleData.asignaciones || [])) {
      next[keyOf(item.id_grupo_torneo, item.posicion_inicial)] = Number(item.id_equipo)
    }
    selected.value = next
    selectedPool.value = (detalleData.inscriptos || []).map(i => Number(i.id_equipo))
  } catch (error) {
    detalle.value = null
    programacionData.value = null
    toast.showToast({ message: getApiMessage(error, 'No se pudo cargar el detalle del torneo.'), type: 'danger' })
  } finally {
    loadingDetalle.value = false
  }
}

const allTeamsMap = computed(() => {
  const map = {}
  for (const item of (detalle.value?.inscriptos || [])) {
    const id = Number(item.id_equipo)
    map[id] = {
      id,
      nombre: item.equipo_nombre,
      escudo: item.escudo || null,
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

const asignacionesCompletas = computed(() => {
  const grupos = detalle.value?.grupos || []
  if (!grupos.length) return false
  return grupos.every(g => Number(g.asignados) >= Number(g.cantidad_equipos_objetivo))
})

const hayPartidosJugados = computed(() => {
  return Number(detalle.value?.eventos?.finalizados || 0) > 0
})

const asignacionesPorGrupo = computed(() => {
  const grupos = detalle.value?.grupos || []
  const items = detalle.value?.asignaciones || []
  return grupos.map(g => ({
    ...g,
    equipos: items
      .filter(a => Number(a.id_grupo_torneo) === Number(g.id))
      .sort((a, b) => Number(a.posicion_inicial) - Number(b.posicion_inicial)),
  }))
})

const totalSlots = computed(() => {
  let total = 0
  for (const g of (detalle.value?.grupos || [])) {
    total += Number(g.cantidad_equipos_objetivo || 0)
  }
  return total
})

const programacionEventos = computed(() =>
  (programacionData.value?.eventos || []).map(ev => ({ ...ev, id: Number(ev.id) }))
)

const eventosZonaProgramacion = computed(() =>
  programacionEventos.value.filter(ev => /^zona\s/i.test(String(ev.titulo || '').trim()))
)

const eventosCruceProgramacion = computed(() =>
  programacionEventos.value.filter(ev => !/^zona\s/i.test(String(ev.titulo || '').trim()))
)

const esFormatoConsuelo = computed(() =>
  detalle.value?.torneo?.formato_manual === 'GRUPOS_CON_CONSUELO'
)

const esFormatoLiga = computed(() =>
  detalle.value?.torneo?.formato_manual === 'LIGA'
)

const esEventoConsuelo = (ev) => /^consuelo\s*-/i.test(String(ev?.titulo || '').trim())

const eventosCruceGanadores = computed(() =>
  eventosCruceProgramacion.value.filter(ev => !esEventoConsuelo(ev))
)

const eventosCruceConsuelo = computed(() =>
  eventosCruceProgramacion.value.filter(ev => esEventoConsuelo(ev))
)

const getNombreRondaCruce = (titulo, fallback) => {
  const value = String(titulo || '').trim()
  if (!value) return fallback
  const cleaned = value
    .replace(/^consuelo\s*-\s*/i, '')
    .replace(/\s*-\s*partido\s*\d+\s*$/i, '')
    .trim()
  return cleaned || fallback
}

const getCruceEquipoDisplay = (ev, side) => {
  const fieldId = side === 'local' ? 'id_equipo_local' : 'id_equipo_visitante'
  const fieldNombre = side === 'local' ? 'equipo_local_nombre' : 'equipo_visitante_nombre'
  const fieldEscudo = side === 'local' ? 'equipo_local_escudo' : 'equipo_visitante_escudo'

  const draft = getCruceDraft(ev.id)
  const idDraft = Number(draft?.[fieldId] || 0)
  if (idDraft > 0 && allTeamsMap.value[idDraft]) {
    return {
      id: idDraft,
      nombre: allTeamsMap.value[idDraft]?.nombre || '',
      escudo: allTeamsMap.value[idDraft]?.escudo || null,
    }
  }

  return {
    id: Number(ev?.[fieldId] || 0) || null,
    nombre: String(ev?.[fieldNombre] || ''),
    escudo: ev?.[fieldEscudo] || null,
  }
}

const CRUCE_BRACKET_CARD_HEIGHT = 86
const CRUCE_BRACKET_BASE_SLOT = 118

const buildCruceRounds = (eventos, nombreFallback = 'Ronda') => {
  const byFecha = new Map()
  const sorted = [...eventos].sort((a, b) => {
    const fa = Number(a?.numero_fecha || 0)
    const fb = Number(b?.numero_fecha || 0)
    if (fa !== fb) return fa - fb
    return Number(a?.id || 0) - Number(b?.id || 0)
  })

  for (const ev of sorted) {
    const fecha = Number(ev?.numero_fecha || 0)
    const key = Number.isFinite(fecha) ? fecha : 0
    if (!byFecha.has(key)) {
      byFecha.set(key, {
        fecha: key,
        nombre: getNombreRondaCruce(ev?.titulo, `${nombreFallback} ${byFecha.size + 1}`),
        partidos: [],
      })
    }

    const row = byFecha.get(key)
    const resultadoDisponible = ev?.resultado_local !== null
      && ev?.resultado_local !== undefined
      && ev?.resultado_visitante !== null
      && ev?.resultado_visitante !== undefined

    row.partidos.push({
      id: Number(ev.id),
      local: getCruceEquipoDisplay(ev, 'local'),
      visitante: getCruceEquipoDisplay(ev, 'visitante'),
      resultado: resultadoDisponible ? `${ev.resultado_local} - ${ev.resultado_visitante}` : null,
    })
  }

  return Array.from(byFecha.values())
}

const cruceRounds = computed(() =>
  buildCruceRounds(
    esFormatoConsuelo.value ? eventosCruceGanadores.value : eventosCruceProgramacion.value,
    'Ronda',
  )
)

const cruceRoundsConsuelo = computed(() =>
  buildCruceRounds(eventosCruceConsuelo.value, 'Ronda')
)

const getCruceRoundTrackStyle = (roundIndex, matchCount) => {
  const slot = CRUCE_BRACKET_BASE_SLOT * (2 ** roundIndex)
  return {
    height: `${slot * matchCount}px`,
  }
}

const getCruceMatchStyle = (roundIndex, matchIndex) => {
  const slot = CRUCE_BRACKET_BASE_SLOT * (2 ** roundIndex)
  const top = (slot / 2) - (CRUCE_BRACKET_CARD_HEIGHT / 2) + (matchIndex * slot)
  return {
    top: `${top}px`,
  }
}

const getCruceMergeStyle = (roundIndex, pairIndex) => {
  const slot = CRUCE_BRACKET_BASE_SLOT * (2 ** roundIndex)
  const top = (slot / 2) + (pairIndex * 2 * slot)
  return {
    top: `${top}px`,
    height: `${slot}px`,
  }
}

const zonasConcretadas = computed(() => {
  const hayFaseDeZonas = Boolean(detalle.value?.grupos?.length)
  if (!hayFaseDeZonas) return true
  if (!eventosZonaProgramacion.value.length) return false
  return eventosZonaProgramacion.value.every(ev => [4, 7].includes(Number(ev.id_estado_evento)))
})

const crucesHabilitados = computed(() => !detalle.value?.grupos?.length || zonasConcretadas.value)

const crucesConEquiposDefinidos = computed(() =>
  eventosCruceProgramacion.value.filter(ev => ev.equipo_local_nombre && ev.equipo_visitante_nombre).length
)

const equiposCruceOptions = computed(() =>
  Object.values(allTeamsMap.value)
    .map(item => ({ id: Number(item.id), nombre: item.nombre }))
    .sort((a, b) => String(a.nombre).localeCompare(String(b.nombre), 'es'))
)

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
  const pool = esFormatoLiga.value
    ? (detalle.value?.inscriptos || []).map(i => Number(i.id_equipo)).filter(v => v > 0)
    : Array.from(new Set(selectedPool.value.map(Number).filter(v => v > 0)))

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

const asignarCruces = async () => {
  if (!idTorneoSeleccionado.value) return
  if (!crucesHabilitados.value) {
    toast.showToast({ message: 'Los cruces aún están bloqueados. Debes finalizar la fase de zonas.', type: 'warning' })
    return
  }
  if (!eventosCruceProgramacion.value.length) {
    toast.showToast({ message: 'No hay cruces configurados para este torneo.', type: 'warning' })
    return
  }

  savingAsignacionCruces.value = true
  try {
    const response = await planTorneoService.asignarCruces({ id_torneo: idTorneoSeleccionado.value })
    toast.showToast({ message: response?.message || 'Cruces asignados correctamente.', type: 'success' })
    await cargarDetalle()
  } catch (error) {
    toast.showToast({ message: getApiMessage(error, 'No se pudieron asignar los cruces.'), type: 'danger' })
  } finally {
    savingAsignacionCruces.value = false
  }
}

const getCruceDraft = (idEvento) => {
  const id = Number(idEvento)
  if (!cruceDrafts.value[id]) {
    cruceDrafts.value[id] = {
      id_equipo_local: null,
      id_equipo_visitante: null,
    }
  }
  return cruceDrafts.value[id]
}

const setCruceDraftField = (idEvento, field, value) => {
  const draft = getCruceDraft(idEvento)
  const parsed = Number(value)
  draft[field] = Number.isFinite(parsed) && parsed > 0 ? parsed : null
}

const isEditingCruceManual = (idEvento) => editingCruceManualIds.value.includes(Number(idEvento))

const resetCruceDraftFromEvento = (evento) => {
  const id = Number(evento?.id || 0)
  if (!id) return
  cruceDrafts.value[id] = {
    id_equipo_local: evento?.id_equipo_local ? Number(evento.id_equipo_local) : null,
    id_equipo_visitante: evento?.id_equipo_visitante ? Number(evento.id_equipo_visitante) : null,
  }
}

const editarCruceManual = (evento) => {
  if (!crucesHabilitados.value) return
  const id = Number(evento?.id || 0)
  if (!id) return
  resetCruceDraftFromEvento(evento)
  if (!isEditingCruceManual(id)) {
    editingCruceManualIds.value = [...editingCruceManualIds.value, id]
  }
}

const cancelarEdicionCruceManual = (evento) => {
  const id = Number(evento?.id || 0)
  if (!id) return
  resetCruceDraftFromEvento(evento)
  editingCruceManualIds.value = editingCruceManualIds.value.filter(item => item !== id)
}

const isSavingCruceManual = (idEvento) => savingAsignacionCruceIds.value.includes(Number(idEvento))

const guardarAsignacionCruceManual = async (evento) => {
  const idEvento = Number(evento?.id || 0)
  if (!idEvento || !idTorneoSeleccionado.value) return
  if (!crucesHabilitados.value) {
    toast.showToast({ message: 'Los cruces aún están bloqueados para edición.', type: 'warning' })
    return
  }

  const draft = getCruceDraft(idEvento)
  if (
    draft.id_equipo_local !== null &&
    draft.id_equipo_visitante !== null &&
    Number(draft.id_equipo_local) === Number(draft.id_equipo_visitante)
  ) {
    toast.showToast({ message: 'El equipo local y visitante no pueden ser el mismo.', type: 'warning' })
    return
  }

  savingAsignacionCruceIds.value = Array.from(new Set([...savingAsignacionCruceIds.value, idEvento]))
  try {
    await planTorneoService.actualizarAsignacionCruce({
      id_torneo: Number(idTorneoSeleccionado.value),
      id_evento: idEvento,
      id_equipo_local: draft.id_equipo_local,
      id_equipo_visitante: draft.id_equipo_visitante,
    })
    editingCruceManualIds.value = editingCruceManualIds.value.filter(item => item !== idEvento)
    toast.showToast({ message: 'Asignación manual del cruce guardada.', type: 'success' })
    await cargarDetalle()
  } catch (error) {
    toast.showToast({ message: getApiMessage(error, 'No se pudo guardar la asignación manual del cruce.'), type: 'danger' })
  } finally {
    savingAsignacionCruceIds.value = savingAsignacionCruceIds.value.filter(id => id !== idEvento)
  }
}

const eliminarAsignaciones = async () => {
  if (!idTorneoSeleccionado.value) return
  const ok = window.confirm(
    '¿Eliminar todas las asignaciones de grupos de este torneo?\n\nEsta acción es reversible siempre que no haya partidos finalizados.',
  )
  if (!ok) return

  savingEliminarAsignaciones.value = true
  try {
    await planTorneoService.eliminarAsignaciones({ id_torneo: idTorneoSeleccionado.value })
    toast.showToast({ message: 'Asignaciones eliminadas. Podés volver a asignar.', type: 'success' })
    await cargarDetalle()
  } catch (error) {
    toast.showToast({ message: getApiMessage(error, 'No se pudieron eliminar las asignaciones.'), type: 'danger' })
  } finally {
    savingEliminarAsignaciones.value = false
  }
}

onMounted(async () => {
  // Prioridad: torneo pedido por URL (deep link) y, si no hay, el que ya
  // estaba elegido en Gestión de torneos.
  const idTorneoDesdeQuery = Number(route.query?.id_torneo || 0)
  const idCandidato = idTorneoDesdeQuery > 0 ? idTorneoDesdeQuery : Number(torneoGestionStore.idTorneoSeleccionado || 0)

  if (idCandidato > 0) {
    await cargarDetalle(idCandidato)
  }
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

.asignaciones-subtabs .nav-link {
  border-radius: 999px;
  color: #475569;
  font-weight: 600;
}

.asignaciones-subtabs .nav-link.active {
  background: #0ea5e9;
  color: #fff;
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

.escudo-thumb {
  width: 24px;
  height: 24px;
  object-fit: cover;
  border-radius: 50%;
  border: 1px solid #cbd5e1;
}

.escudo-thumb-placeholder {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  border: 1px solid #cbd5e1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #f1f5f9;
  color: #94a3b8;
  font-size: 0.78rem;
  flex-shrink: 0;
}

.cruce-bracket-box {
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 12px;
  background: #f8fafc;
}

.cruce-bracket-scroll {
  overflow-x: auto;
  overflow-y: hidden;
  padding-bottom: 6px;
}

.cruce-bracket-grid {
  --round-count: 1;
  display: grid;
  grid-template-columns: repeat(var(--round-count), minmax(220px, 1fr));
  gap: 22px;
  min-width: calc(var(--round-count) * 220px + (var(--round-count) - 1) * 22px);
}

.cruce-round-column {
  position: relative;
}

.cruce-round-title {
  font-size: 0.85rem;
  font-weight: 700;
  color: #334155;
  margin-bottom: 8px;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.cruce-round-track {
  position: relative;
}

.cruce-match-wrapper {
  position: absolute;
  left: 0;
  right: 0;
}

.cruce-match-card {
  background: #fff;
  border: 1px solid #dbe1ea;
  border-radius: 12px;
  padding: 8px;
  display: grid;
  gap: 4px;
  position: relative;
  min-height: 84px;
}

.cruce-match-card.has-next::after,
.cruce-match-card.has-prev::before {
  content: '';
  position: absolute;
  top: 50%;
  width: 14px;
  border-top: 2px solid #cbd5e1;
}

.cruce-match-card.has-next::after {
  right: -14px;
}

.cruce-match-card.has-prev::before {
  left: -14px;
}

.cruce-round-merge {
  position: absolute;
  right: -11px;
  width: 11px;
  border-right: 2px solid #cbd5e1;
  border-bottom: 2px solid #cbd5e1;
}

.cruce-team-line {
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
}

.cruce-team-line .team-name {
  min-width: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  font-size: 0.88rem;
}

.cruce-score-pill {
  justify-self: end;
  font-size: 0.78rem;
  border: 1px solid #bfdbfe;
  background: #eff6ff;
  color: #1d4ed8;
  border-radius: 999px;
  padding: 2px 8px;
  font-weight: 700;
}

@media (max-width: 768px) {
  .cruce-bracket-grid {
    gap: 14px;
    grid-template-columns: repeat(var(--round-count), minmax(190px, 1fr));
    min-width: calc(var(--round-count) * 190px + (var(--round-count) - 1) * 14px);
  }
}
</style>
