<template>
  <div class="container-fluid p-4 bg-white min-vh-100 position-relative animate-fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
      <div class="d-flex align-items-center">
        <button @click="$router.back()" class="btn-back-arrow me-2" title="Volver">
          <i class="bi bi-arrow-left"></i>
        </button>
        <h1 class="h5 fw-bold mb-0 text-secondary">PLANIFICADOR DE TORNEO</h1>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-12 col-xl-4">
        <div class="card shadow-sm border-0 rounded-lg">
          <div class="card-body p-4">
            <h2 class="h6 fw-bold text-secondary mb-3">Configuración inicial</h2>

            <form @submit.prevent="simular" class="d-grid gap-3">
              <div>
                <label class="form-label">Formato del torneo</label>
                <select v-model="form.formato" class="form-select">
                  <option value="ELIMINACION">Solo eliminación directa</option>
                  <option value="MIXTO">Grupos + Llave eliminatoria</option>
                  <option value="GRUPOS_CON_CONSUELO">Grupos + Ganadores / Rueda Consuelo</option>
                  <option value="LIGA">Liga (todos contra todos)</option>
                </select>
              </div>

              <div>
                <label class="form-label">Cantidad total de equipos</label>
                <input v-model.number="form.cantidad_equipos" type="number" min="2" class="form-control" required />
              </div>

              <div v-if="formatoUsaZonas" class="row g-3">
                <template v-if="form.formato !== 'LIGA'">
                  <div class="col-md-6 col-xl-12">
                    <label class="form-label">Cantidad de zonas</label>
                    <input v-model.number="form.cantidad_zonas" type="number" min="1" class="form-control" placeholder="Opcional" />
                  </div>
                  <div class="col-md-6 col-xl-12">
                    <label class="form-label">Equipos por zona</label>
                    <input v-model.number="form.equipos_por_zona" type="number" min="2" class="form-control" placeholder="Opcional" />
                  </div>
                </template>
                <div v-if="form.formato !== 'GRUPOS_CON_CONSUELO' && form.formato !== 'LIGA'" class="col-md-6 col-xl-12">
                  <label class="form-label">Clasificados por zona</label>
                  <input v-model.number="form.clasificados_por_zona" type="number" min="1" class="form-control" required />
                </div>
                <template v-if="form.formato === 'GRUPOS_CON_CONSUELO'">
                  <div class="col-12">
                    <label class="form-label">Llave Zona Ganadores</label>
                    <select v-model.number="form.llave_ganadores" class="form-select">
                      <option :value="2">Final (2 equipos)</option>
                      <option :value="4">Semifinal (4 equipos)</option>
                      <option :value="8">Cuartos de final (8 equipos)</option>
                      <option :value="16">Octavos de final (16 equipos)</option>
                      <option :value="32">Dieciseisavos (32 equipos)</option>
                    </select>
                    <div v-if="avisoByesGanadores" class="form-text text-warning">
                      <i class="bi bi-exclamation-triangle me-1"></i>{{ avisoByesGanadores }}
                    </div>
                  </div>
                  <div class="col-12">
                    <label class="form-label">Llave Rueda Consuelo</label>
                    <select v-model.number="form.llave_consuelo" class="form-select">
                      <option :value="2">Final (2 equipos)</option>
                      <option :value="4">Semifinal (4 equipos)</option>
                      <option :value="8">Cuartos de final (8 equipos)</option>
                      <option :value="16">Octavos de final (16 equipos)</option>
                      <option :value="32">Dieciseisavos (32 equipos)</option>
                    </select>
                    <div v-if="avisoByesConsuelo" class="form-text text-warning">
                      <i class="bi bi-exclamation-triangle me-1"></i>{{ avisoByesConsuelo }}
                    </div>
                  </div>
                </template>
                <div class="col-md-6 col-xl-12 d-flex align-items-end">
                  <div class="form-check form-switch">
                    <input v-model="form.ida_vuelta_zonas" class="form-check-input" type="checkbox" id="ida-vuelta" />
                    <label class="form-check-label" for="ida-vuelta">Zonas ida y vuelta</label>
                  </div>
                </div>
              </div>

              <template v-if="form.formato !== 'GRUPOS_CON_CONSUELO' && form.formato !== 'LIGA'">
                <div>
                  <label class="form-label">Llave eliminatoria</label>
                  <select v-model.number="form.llave_equipos" class="form-select">
                    <option :value="null">Automática</option>
                    <option v-for="size in [2, 4, 8, 16, 32]" :key="size" :value="size">{{ size }} equipos</option>
                  </select>
                </div>

                <div class="form-check form-switch">
                  <input v-model="form.tercer_puesto" class="form-check-input" type="checkbox" id="tercer-puesto" />
                  <label class="form-check-label" for="tercer-puesto">Agregar partido por tercer puesto</label>
                </div>
              </template>

              <button type="submit" class="btn-primary-modern d-flex align-items-center justify-content-center" :disabled="loading">
                <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                Simular planificación
              </button>

              <button
                type="button"
                class="btn btn-outline-success d-flex align-items-center justify-content-center"
                :disabled="confirming || !resultado"
                @click="abrirConfirmacion"
              >
                Confirmar torneo
              </button>
            </form>
          </div>
        </div>
      </div>

      <div class="col-12 col-xl-8">
        <div v-if="resultado" class="d-flex align-items-center gap-2 mb-3">
          <span class="text-muted small">Total de partidos:</span>
          <span class="badge rounded-pill text-bg-light border">{{ resultado.resumen.total_partidos }}</span>
        </div>

        <p v-else class="text-muted mb-3">Cargá los parámetros y ejecutá una simulación para ver el resultado.</p>

        <div v-if="resultado?.observaciones?.length" class="alert alert-warning mb-3 py-2 px-3">
          <ul class="mb-0 ps-3">
            <li v-for="item in resultado.observaciones" :key="item">{{ item }}</li>
          </ul>
        </div>

        <div v-if="resultadoConfirmacion" class="alert alert-success mb-3 py-2 px-3">
          <div class="fw-semibold">Confirmación persistida</div>
          <div class="small">Generación: {{ resultadoConfirmacion.id_generacion_fixture }}</div>
          <div class="small">Fases: {{ resultadoConfirmacion.ids_fases?.length || 0 }} | Grupos: {{ resultadoConfirmacion.ids_grupos?.length || 0 }}</div>
          <div class="small">Cruces: {{ resultadoConfirmacion.ids_cruces?.length || 0 }} | Eventos: {{ resultadoConfirmacion.ids_eventos?.length || 0 }}</div>
        </div>

        <div v-if="resultado && resultado.input_normalizado?.formato === 'LIGA'" class="card shadow-sm border-0 rounded-lg mb-4">
          <div class="card-body p-4">
            <h2 class="h6 fw-bold text-secondary mb-3">Resumen de liga</h2>
            <div class="d-flex flex-wrap gap-3">
              <div class="text-center px-3 py-2 bg-light rounded">
                <div class="fw-bold fs-5">{{ resultado.resumen.total_partidos }}</div>
                <div class="small text-muted">Partidos totales</div>
              </div>
              <div class="text-center px-3 py-2 bg-light rounded">
                <div class="fw-bold fs-5">{{ resultado.resumen.jornadas }}</div>
                <div class="small text-muted">Jornadas</div>
              </div>
              <div class="text-center px-3 py-2 bg-light rounded">
                <div class="fw-bold fs-5">{{ resultado.resumen.minimo_partidos_por_equipo }}</div>
                <div class="small text-muted">Partidos por equipo</div>
              </div>
            </div>
          </div>
        </div>

        <div v-if="resultado?.zonas?.length && resultado.input_normalizado?.formato !== 'LIGA'" class="card shadow-sm border-0 rounded-lg mb-4">
          <div class="card-body p-4">
            <h2 class="h6 fw-bold text-secondary mb-3">Distribución de zonas</h2>
            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0">
                <thead>
                  <tr>
                    <th>Zona</th>
                    <th>Equipos</th>
                    <th>Partidos</th>
                    <th>Clasifican</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="zona in resultado.zonas" :key="zona.zona">
                    <td class="fw-semibold">{{ zona.zona }}</td>
                    <td>{{ zona.equipos }}</td>
                    <td>{{ zona.partidos }}</td>
                    <td>{{ zona.clasificados }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div v-if="resultado?.llave?.rondas?.length" class="card shadow-sm border-0 rounded-lg" :class="resultado?.consuelo ? 'mb-3' : ''">
          <div class="card-body p-4">
            <h2 class="h6 fw-bold text-secondary mb-3">
              {{ resultado?.consuelo ? 'Llave Zona Ganadores' : 'Llave estimada' }}
            </h2>
            <div class="bracket-scroll">
              <div class="bracket-grid" :style="{ '--round-count': resultado.llave.rondas.length }">
                <div v-for="(ronda, index) in resultado.llave.rondas" :key="ronda.nombre + index" class="round-column">
                  <h3 class="round-title">{{ ronda.nombre }}</h3>
                  <div class="round-track" :style="getRoundTrackStyle(index, ronda.partidos.length)">
                    <div
                      v-for="(partido, partidoIndex) in ronda.partidos"
                      :key="partido.id"
                      class="match-wrapper"
                      :style="getMatchStyle(index, partidoIndex)"
                    >
                      <div class="match-card bracket-match" :class="{ 'has-next': index < resultado.llave.rondas.length - 1, 'has-prev': index > 0 }">
                        <div class="team-line">{{ partido.local }}</div>
                        <div class="team-line">{{ partido.visitante }}</div>
                      </div>
                    </div>
                    <div
                      v-if="index < resultado.llave.rondas.length - 1"
                      v-for="pairIndex in Math.floor(ronda.partidos.length / 2)"
                      :key="`merge-${index}-${pairIndex}`"
                      class="round-merge"
                      :style="getMergeStyle(index, pairIndex - 1)"
                    ></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-if="resultado?.consuelo?.rondas?.length" class="card shadow-sm border-0 rounded-lg border-warning-subtle">
          <div class="card-body p-4">
            <h2 class="h6 fw-bold mb-3" style="color: #b45309;">Rueda Consuelo</h2>
            <div class="bracket-scroll">
              <div class="bracket-grid" :style="{ '--round-count': resultado.consuelo.rondas.length }">
                <div v-for="(ronda, index) in resultado.consuelo.rondas" :key="'c-' + ronda.nombre + index" class="round-column">
                  <h3 class="round-title">{{ ronda.nombre }}</h3>
                  <div class="round-track" :style="getRoundTrackStyle(index, ronda.partidos.length)">
                    <div
                      v-for="(partido, partidoIndex) in ronda.partidos"
                      :key="partido.id"
                      class="match-wrapper"
                      :style="getMatchStyle(index, partidoIndex)"
                    >
                      <div class="match-card bracket-match" :class="{ 'has-next': index < resultado.consuelo.rondas.length - 1, 'has-prev': index > 0 }">
                        <div class="team-line">{{ partido.local }}</div>
                        <div class="team-line">{{ partido.visitante }}</div>
                      </div>
                    </div>
                    <div
                      v-if="index < resultado.consuelo.rondas.length - 1"
                      v-for="pairIndex in Math.floor(ronda.partidos.length / 2)"
                      :key="`cmerge-${index}-${pairIndex}`"
                      class="round-merge"
                      :style="getMergeStyle(index, pairIndex - 1)"
                    ></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <Teleport to="body">
      <div v-if="showConfirmModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Confirmar torneo</h5>
              <button type="button" class="btn-close" @click="showConfirmModal = false"></button>
            </div>
            <form @submit.prevent="confirmarSimulacion">
              <div class="modal-body">
                <div class="d-grid gap-3">
                  <div>
                    <label class="form-label">Nombre del torneo</label>
                    <input v-model.trim="torneoForm.nombre" type="text" class="form-control" required />
                  </div>
                  <div>
                    <label class="form-label">Descripción</label>
                    <textarea v-model.trim="torneoForm.descripcion" class="form-control" rows="2"></textarea>
                  </div>
                  <div>
                    <label class="form-label">Disciplina</label>
                    <select v-model.number="torneoForm.id_disciplina" class="form-select" :disabled="loadingDisciplinas" required>
                      <option :value="null">Seleccionar disciplina</option>
                      <option v-for="disciplina in disciplinas" :key="disciplina.id" :value="Number(disciplina.id)">
                        {{ disciplina.nombre }}
                      </option>
                    </select>
                  </div>
                  <div>
                    <label class="form-label">Fecha inicio</label>
                    <input v-model="torneoForm.fecha_inicio" type="date" class="form-control" />
                  </div>
                  <div>
                    <label class="form-label">Valor inscripción <span class="text-muted fw-normal small">(por fecha)</span></label>
                    <CustomNumberInput v-model="torneoForm.valor_inscripcion" :decimals="2" placeholder="0.00" />
                  </div>
                  <div>
                    <label class="form-label">Reglamento <span class="text-muted fw-normal">(opcional)</span></label>
                    <textarea v-model.trim="torneoForm.reglamento" class="form-control" rows="4" placeholder="Ej: Categoría +30 años. 9 jugadores por equipo. Cambios libres..."></textarea>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-light" @click="showConfirmModal = false">Cancelar</button>
                <button type="submit" class="btn btn-success" :disabled="confirming">
                  <span v-if="confirming" class="spinner-border spinner-border-sm me-2"></span>
                  Crear y confirmar
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import datosMaestrosService from '@/services/datosMaestrosService'
import planTorneoService from '@/services/torneos/planTorneoService'
import { useToastStore } from '@/stores/toastStore'
import CustomNumberInput from '@/components/CustomNumberInput.vue'

const toast = useToastStore()
const router = useRouter()

const loading = ref(false)
const confirming = ref(false)
const resultado = ref(null)
const resultadoConfirmacion = ref(null)
const showConfirmModal = ref(false)
const disciplinas = ref([])
const loadingDisciplinas = ref(false)

const form = ref({
  formato: 'MIXTO',
  cantidad_equipos: 16,
  cantidad_zonas: 4,
  equipos_por_zona: null,
  clasificados_por_zona: 2,
  clasificados_consuelo: 4,
  llave_ganadores: 8,
  llave_consuelo: 8,
  ida_vuelta_zonas: false,
  llave_equipos: 8,
  tercer_puesto: false,
})

const torneoForm = ref({
  nombre: '',
  descripcion: '',
  id_disciplina: null,
  fecha_inicio: '',
  valor_inscripcion: 0,
  reglamento: '',
})

const formatoUsaZonas = computed(() =>
  form.value.formato === 'MIXTO' || form.value.formato === 'GRUPOS_CON_CONSUELO' || form.value.formato === 'LIGA'
)

const avisoByesGanadores = computed(() => {
  if (form.value.formato !== 'GRUPOS_CON_CONSUELO') return null
  const zonas = form.value.cantidad_zonas || 1
  if (form.value.llave_ganadores % zonas !== 0)
    return `${form.value.llave_ganadores} no divide exactamente entre ${zonas} zonas — se asignarán byes automáticamente.`
  return null
})

const avisoByesConsuelo = computed(() => {
  if (form.value.formato !== 'GRUPOS_CON_CONSUELO') return null
  const zonas = form.value.cantidad_zonas || 1
  if (form.value.llave_consuelo % zonas !== 0)
    return `${form.value.llave_consuelo} no divide exactamente entre ${zonas} zonas — se asignarán byes automáticamente.`
  return null
})

const getApiMessage = (error, fallback) => error?.response?.data?.message || fallback

const cargarDisciplinas = async () => {
  loadingDisciplinas.value = true
  try {
    disciplinas.value = await datosMaestrosService.getDisciplinas()
  } catch (error) {
    toast.showToast({
      message: getApiMessage(error, 'No se pudieron cargar las disciplinas.'),
      type: 'danger',
    })
  } finally {
    loadingDisciplinas.value = false
  }
}

const CARD_HEIGHT = 66
const BASE_SLOT = 96

const getRoundTrackStyle = (roundIndex, matchCount) => {
  const slot = BASE_SLOT * (2 ** roundIndex)
  return {
    height: `${slot * matchCount}px`,
  }
}

const getMatchStyle = (roundIndex, matchIndex) => {
  const slot = BASE_SLOT * (2 ** roundIndex)
  const top = (slot / 2) - (CARD_HEIGHT / 2) + (matchIndex * slot)
  return {
    top: `${top}px`,
  }
}

const getMergeStyle = (roundIndex, pairIndex) => {
  const slot = BASE_SLOT * (2 ** roundIndex)
  const top = (slot / 2) + (pairIndex * 2 * slot)
  return {
    top: `${top}px`,
    height: `${slot}px`,
  }
}

const buildPayload = () => {
  const usaZonas = formatoUsaZonas.value
  const esConsuelo = form.value.formato === 'GRUPOS_CON_CONSUELO'
  const esLiga = form.value.formato === 'LIGA'
  const zonas = form.value.cantidad_zonas || 1

  const clasificadosPorZona = esConsuelo
    ? Math.ceil(form.value.llave_ganadores / zonas)
    : (usaZonas && !esLiga ? form.value.clasificados_por_zona : 1)

  const clasificadosConsuelo = esConsuelo
    ? Math.ceil(form.value.llave_consuelo / zonas)
    : 0

  return {
    formato: form.value.formato,
    usa_zonas: usaZonas,
    cantidad_equipos: form.value.cantidad_equipos,
    cantidad_zonas: esLiga ? 1 : (usaZonas ? form.value.cantidad_zonas : null),
    equipos_por_zona: esLiga ? null : (usaZonas ? form.value.equipos_por_zona : null),
    clasificados_por_zona: clasificadosPorZona,
    clasificados_consuelo: clasificadosConsuelo,
    ida_vuelta_zonas: usaZonas ? form.value.ida_vuelta_zonas : false,
    llave_equipos: esLiga ? null : (esConsuelo ? form.value.llave_ganadores : form.value.llave_equipos),
    llave_consuelo: esConsuelo ? form.value.llave_consuelo : null,
    tercer_puesto: (!esConsuelo && !esLiga) ? form.value.tercer_puesto : false,
  }
}

const nearestPow2 = (n) => {
  if (n < 2) return 2
  let p = 1
  while (p * 2 <= n) p *= 2
  return p
}

watch(
  () => form.value.llave_ganadores,
  (ganadores) => {
    const restantes = (form.value.cantidad_equipos || 0) - ganadores
    form.value.llave_consuelo = nearestPow2(restantes)
  }
)

watch(
  () => [form.value.formato, form.value.cantidad_equipos, form.value.cantidad_zonas],
  ([formato, cantidadEquipos, cantidadZonas]) => {
    const usaZonas = formato === 'MIXTO' || formato === 'GRUPOS_CON_CONSUELO'
    if (!usaZonas) {
      form.value.equipos_por_zona = null
      return
    }
    if (!cantidadEquipos || !cantidadZonas || cantidadZonas < 1) return
    form.value.equipos_por_zona = Math.max(2, Math.floor(cantidadEquipos / cantidadZonas))
  },
  { immediate: true }
)

const simular = async () => {
  loading.value = true
  try {
    const data = await planTorneoService.simular(buildPayload())
    resultado.value = data
    resultadoConfirmacion.value = null
  } catch (error) {
    toast.showToast({
      message: getApiMessage(error, 'No se pudo simular la planificación del torneo.'),
      type: 'danger',
    })
  } finally {
    loading.value = false
  }
}

const abrirConfirmacion = () => {
  if (!resultado.value) {
    toast.showToast({
      message: 'Primero debes simular una planificación.',
      type: 'warning',
    })
    return
  }
  showConfirmModal.value = true
}

const confirmarSimulacion = async () => {
  if (!torneoForm.value.nombre.trim() || !torneoForm.value.id_disciplina) {
    toast.showToast({
      message: 'Nombre del torneo e ID de disciplina son obligatorios.',
      type: 'warning',
    })
    return
  }

  confirming.value = true
  try {
    const data = await planTorneoService.confirmar({
      torneo_nuevo: {
        nombre: torneoForm.value.nombre.trim(),
        descripcion: torneoForm.value.descripcion?.trim() || null,
        id_disciplina: Number(torneoForm.value.id_disciplina),
        fecha_inicio: torneoForm.value.fecha_inicio || null,
        valor_inscripcion: Number(torneoForm.value.valor_inscripcion || 0),
        reglamento: torneoForm.value.reglamento?.trim() || null,
      },
      parametros: buildPayload(),
      version_algoritmo: 'v1.1.0',
      motor_generacion: 'php',
      observacion: 'Torneo creado y confirmado desde vista de planificación',
    })

    resultadoConfirmacion.value = data
    showConfirmModal.value = false
    toast.showToast({
      message: 'Torneo creado y planificación confirmada correctamente.',
      type: 'success',
    })

    const idTorneo = Number(data?.id_torneo || 0)
    if (idTorneo > 0) {
      await router.push({
        name: 'gestiontorneos',
        query: { id_torneo: String(idTorneo) },
      })
    }
  } catch (error) {
    toast.showToast({
      message: getApiMessage(error, 'No se pudo confirmar la planificación.'),
      type: 'danger',
    })
  } finally {
    confirming.value = false
  }
}

onMounted(cargarDisciplinas)
</script>

<style scoped>
.metric-box {
  border: 1px solid #e9ecef;
  border-radius: 12px;
  padding: 10px 12px;
  background: #f8f9fa;
}

.metric-box small {
  color: #6c757d;
  display: block;
}

.metric-value {
  color: #1f2937;
  font-weight: 700;
  font-size: 1.1rem;
}

.bracket-scroll {
  overflow-x: auto;
  padding-bottom: 6px;
}

.bracket-grid {
  display: flex;
  gap: 28px;
  min-width: 700px;
  align-items: flex-start;
}

.round-column {
  width: 250px;
  min-width: 250px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 12px;
}

.round-track {
  position: relative;
  width: 100%;
  min-height: 120px;
}

.match-wrapper {
  position: absolute;
  width: 200px;
  left: 0;
}

.round-title {
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: #475569;
  margin-bottom: 10px;
}

.match-card {
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  background: #fff;
  overflow: hidden;
}

.bracket-match {
  position: relative;
}

.bracket-match.has-next::after,
.bracket-match.has-prev::before {
  content: '';
  position: absolute;
  top: 50%;
  width: 24px;
  border-top: 2px solid #94a3b8;
}

.bracket-match.has-next::after {
  right: -24px;
}

.bracket-match.has-prev::before {
  left: -24px;
}

.round-merge {
  position: absolute;
  left: 224px;
  width: 0;
  border-left: 2px solid #94a3b8;
}

.team-line {
  padding: 8px 10px;
  font-size: 0.9rem;
}

.team-line + .team-line {
  border-top: 1px dashed #e2e8f0;
}

@media (max-width: 575.98px) {
  .bracket-grid {
    min-width: 920px;
  }
}
</style>
