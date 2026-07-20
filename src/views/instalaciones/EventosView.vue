<template>
  <div class="container-fluid p-4 bg-white min-vh-100 position-relative animate-fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
      <div class="d-flex align-items-center">
        <button @click="$router.back()" class="btn-back-arrow me-2" title="Volver">
          <i class="bi bi-arrow-left"></i>
        </button>
        <h1 class="h5 fw-bold mb-0 text-secondary">EVENTOS</h1>
      </div>
      <div class="d-flex gap-2">
        <router-link
          :to="{ name: 'grillacanchas' }"
          class="btn btn-outline-secondary d-flex align-items-center"
        >
          <i class="bi bi-grid-3x3-gap me-2"></i> Grilla F11
        </router-link>
        <button @click="openModal()" class="btn-primary-modern d-flex align-items-center">
          <i class="bi bi-plus-circle-fill fs-6 me-2"></i> Nuevo
        </button>
      </div>
    </div>

    <div class="mb-3">
      <FuzzySearch v-model="searchQuery" placeholder="Buscar por título, tipo, torneo, cancha o equipos..." />
    </div>

    <div class="card shadow-sm border-0 rounded-lg overflow-hidden position-relative" :style="{ minHeight: loading ? '300px' : 'auto' }">
      <div v-if="loading" class="loading-overlay-local d-flex flex-column align-items-center justify-content-center">
        <div class="spinner-border text-primary-custom" role="status" style="width: 3rem; height: 3rem;">
          <span class="visually-hidden">Cargando...</span>
        </div>
      </div>

      <div class="table-responsive eventos-table-wrap">
        <table class="table table-hover align-middle mb-0">
          <SortableTableHead
            :columns="columns"
            :sort-key="sortKey"
            :sort-dir="sortDir"
            @sort="handleSort"
          />
          <tbody class="bg-white">
            <template v-for="grupo in eventosAgrupadosPorTorneo" :key="grupo.key">
              <tr class="torneo-group-row">
                <td colspan="8" class="p-0">
                  <button
                    type="button"
                    class="torneo-group-toggle px-4 py-3"
                    :aria-expanded="isGrupoAbierto(grupo.key)"
                    @click="toggleGrupo(grupo.key)"
                  >
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 w-100">
                    <div class="d-flex align-items-center gap-2">
                      <span class="torneo-group-icon">
                        <i class="bi bi-trophy"></i>
                      </span>
                      <div>
                        <div class="fw-bold text-dark">{{ grupo.nombre }}</div>
                      </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                      <span class="badge rounded-pill bg-white text-secondary border">{{ grupo.items.length }} evento{{ grupo.items.length === 1 ? '' : 's' }}</span>
                      <span v-if="grupo.rangoFechas" class="badge rounded-pill bg-white text-secondary border">{{ grupo.rangoFechas }}</span>
                      <span class="torneo-group-chevron">
                        <i class="bi" :class="isGrupoAbierto(grupo.key) ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                      </span>
                    </div>
                    </div>
                  </button>
                </td>
              </tr>
              <template v-if="isGrupoAbierto(grupo.key)">
                <tr v-for="item in grupo.items" :key="item.id">
                  <td class="fw-medium text-dark ps-4">{{ item.titulo }}</td>
                  <td class="text-muted text-capitalize">{{ item.tipo_evento }}</td>
                  <td class="text-muted">{{ item.estado_evento_descripcion || item.id_estado_evento }}</td>
                  <td class="text-muted">{{ formatDateTime(item.fecha_hora_inicio) }}</td>
                  <td class="text-muted">{{ formatTeams(item) }}</td>
                  <td class="text-muted">{{ formatResult(item) }}</td>
                  <td class="text-center">
                    <span class="badge rounded-pill px-3 bg-primary-subtle text-primary-custom">{{ item.numero_fecha || '-' }}</span>
                  </td>
                  <td class="pe-4 text-end">
                    <div class="d-flex gap-1 justify-content-end flex-nowrap">
                      <button @click="openDetalleEvento(item)" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 px-2 py-1" title="Ver detalle">
                        <i class="bi bi-eye fs-6"></i>
                        <span class="small fw-bold">Ver</span>
                      </button>
                      <button @click="openModal(item)" class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1 px-2 py-1" title="Editar">
                        <i class="bi bi-pencil fs-6"></i>
                        <span class="small fw-bold">Editar</span>
                      </button>
                      <button @click="prepareDelete(item.id)" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1 px-2 py-1" title="Eliminar">
                        <i class="bi bi-trash3 fs-6"></i>
                        <span class="small fw-bold">Eliminar</span>
                      </button>
                    </div>
                  </td>
                </tr>
              </template>
            </template>
            <tr v-if="eventosFiltrados.length === 0 && !loading">
              <td colspan="8" class="text-center py-5 text-muted">
                No hay eventos que coincidan con la búsqueda.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <Teleport to="body">
      <div v-if="showFormModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
        <div class="modal-dialog modal-dialog-centered modal-xl">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">
                <i class="bi me-2" :class="isEditing ? 'bi-pencil-square' : 'bi-plus-circle'"></i>
                {{ isEditing ? 'Editar Evento' : 'Nuevo Evento' }}
              </h5>
              <button type="button" class="btn-close" @click="showFormModal = false"></button>
            </div>
            <form @submit.prevent="save">
              <div class="modal-body">
                <div class="form-section">
                  <div class="form-section-title">
                    <span class="form-section-icon"><i class="bi bi-info-circle-fill"></i></span>
                    <span>Información general</span>
                  </div>
                  <div class="row g-3">
                    <div class="col-md-4">
                      <label class="form-label">Tipo de evento</label>
                      <select v-model="form.tipo_evento" class="form-select" required>
                        <option v-for="tipo in tiposEvento" :key="tipo" :value="tipo">{{ tipo }}</option>
                      </select>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Estado del evento</label>
                      <select v-model.number="form.id_estado_evento" class="form-select" required>
                        <option :value="null">Seleccionar estado</option>
                        <option v-for="estado in estadosEvento" :key="estado.id" :value="Number(estado.id)">
                          {{ estado.descripcion }}
                        </option>
                      </select>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Torneo</label>
                      <select v-model.number="form.id_torneo" class="form-select">
                        <option :value="null">Sin torneo</option>
                        <option v-for="torneo in torneos" :key="torneo.id" :value="Number(torneo.id)">
                          {{ torneo.nombre }}
                        </option>
                      </select>
                    </div>

                    <div class="col-md-8">
                      <label class="form-label">Titulo</label>
                      <input v-model.trim="form.titulo" type="text" class="form-control" placeholder="Ej: Fecha 1 - Partido inaugural" required />
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Numero de fecha</label>
                      <input v-model.number="form.numero_fecha" type="number" class="form-control" min="1" placeholder="Opcional" />
                    </div>

                    <div class="col-12">
                      <label class="form-label">Descripcion</label>
                      <textarea v-model.trim="form.descripcion" class="form-control" rows="2" placeholder="Detalle del evento"></textarea>
                    </div>
                  </div>
                </div>

                <div class="form-section">
                  <div class="form-section-title">
                    <span class="form-section-icon"><i class="bi bi-calendar-week-fill"></i></span>
                    <span>Fecha y ubicación</span>
                  </div>
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label">Fecha/Hora Inicio</label>
                      <input v-model="form.fecha_hora_inicio" type="datetime-local" class="form-control" required />
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Fecha/Hora Fin</label>
                      <input v-model="form.fecha_hora_fin" type="datetime-local" class="form-control" />
                    </div>

                    <div class="col-md-6">
                      <label class="form-label">Cancha</label>
                      <select v-model.number="form.id_cancha" class="form-select">
                        <option :value="null">Sin cancha</option>
                        <option v-for="cancha in canchas" :key="cancha.id" :value="Number(cancha.id)">
                          {{ cancha.nombre }}
                        </option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Arbitro</label>
                      <select v-model.number="form.id_arbitro" class="form-select">
                        <option :value="null">Sin asignar</option>
                        <option v-for="arbitro in arbitros" :key="arbitro.id" :value="Number(arbitro.id)">
                          {{ arbitro.apellido }}, {{ arbitro.nombre }}
                        </option>
                      </select>
                    </div>
                  </div>
                </div>

                <div class="form-section">
                  <div class="form-section-title">
                    <span class="form-section-icon"><i class="bi bi-people-fill"></i></span>
                    <span>Equipos</span>
                  </div>
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label">Equipo local</label>
                      <select v-model.number="form.id_equipo_local" class="form-select">
                        <option :value="null">Sin asignar</option>
                        <option v-for="equipo in equipos" :key="equipo.id" :value="Number(equipo.id)">
                          {{ equipo.nombre }}
                        </option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Equipo visitante</label>
                      <select v-model.number="form.id_equipo_visitante" class="form-select">
                        <option :value="null">Sin asignar</option>
                        <option v-for="equipo in equipos" :key="equipo.id" :value="Number(equipo.id)">
                          {{ equipo.nombre }}
                        </option>
                      </select>
                    </div>

                    <div v-if="isEditing && (form.resultado_local !== null || form.resultado_visitante !== null)" class="col-12">
                      <div class="alert alert-info py-2 mb-0">
                        Resultado actual: {{ form.resultado_local ?? '-' }} - {{ form.resultado_visitante ?? '-' }}.
                        Se modifica desde Resultado de Partido.
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button @click="showFormModal = false" type="button" class="btn btn-light px-4">Cancelar</button>
                <button type="submit" class="btn btn-primary-modern px-4" :disabled="isSaving">
                  <span v-if="isSaving" class="spinner-border spinner-border-sm me-2"></span>
                  {{ isEditing ? 'Actualizar' : 'Guardar' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </Teleport>

    <Teleport to="body">
      <div v-if="showDetalleModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
        <div class="modal-dialog modal-dialog-centered modal-xl">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">
                <i class="bi bi-eye me-2"></i>
                Detalle del evento
              </h5>
              <button type="button" class="btn-close" @click="showDetalleModal = false"></button>
            </div>
            <div class="modal-body">
              <div v-if="detalleEvento" class="row g-3 mb-4">
                <div class="col-12 col-lg-6">
                  <div class="detalle-box h-100">
                    <div class="small text-muted">Evento</div>
                    <div class="fw-bold text-dark">{{ detalleEvento.titulo }}</div>
                    <div class="text-muted small mt-1">{{ detalleEvento.descripcion || 'Sin descripcion' }}</div>
                  </div>
                </div>
                <div class="col-6 col-lg-3">
                  <div class="detalle-box h-100">
                    <div class="small text-muted">Torneo</div>
                    <div class="fw-semibold">{{ detalleEvento.torneo_nombre || 'Sin torneo' }}</div>
                  </div>
                </div>
                <div class="col-6 col-lg-3">
                  <div class="detalle-box h-100">
                    <div class="small text-muted">Estado</div>
                    <div class="fw-semibold">{{ detalleEvento.estado_evento_descripcion || detalleEvento.id_estado_evento || '-' }}</div>
                  </div>
                </div>
                <div class="col-6 col-lg-3">
                  <div class="detalle-box h-100">
                    <div class="small text-muted">Inicio</div>
                    <div class="fw-semibold">{{ formatDateTime(detalleEvento.fecha_hora_inicio) }}</div>
                  </div>
                </div>
                <div class="col-6 col-lg-3">
                  <div class="detalle-box h-100">
                    <div class="small text-muted">Cancha</div>
                    <div class="fw-semibold">{{ detalleEvento.cancha_nombre || 'Sin cancha' }}</div>
                  </div>
                </div>
                <div class="col-6 col-lg-3">
                  <div class="detalle-box h-100">
                    <div class="small text-muted">Equipos</div>
                    <div class="fw-semibold">{{ formatTeams(detalleEvento) }}</div>
                  </div>
                </div>
                <div class="col-6 col-lg-3">
                  <div class="detalle-box h-100">
                    <div class="small text-muted">Resultado</div>
                    <div class="fw-bold fs-5">{{ formatResult(detalleEvento) }}</div>
                    <div class="small text-muted">Penales: {{ formatPenales(detalleEvento) }}</div>
                  </div>
                </div>
              </div>

              <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold text-secondary mb-0">Detalle</h6>
                <span class="badge rounded-pill bg-light text-secondary border">{{ detalleIncidencias.length }} incidencia{{ detalleIncidencias.length === 1 ? '' : 's' }}</span>
              </div>

              <div v-if="detalleLoading" class="text-center py-4">
                <div class="spinner-border text-primary-custom" role="status">
                  <span class="visually-hidden">Cargando...</span>
                </div>
              </div>

              <div v-else class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>Tipo</th>
                      <th>Equipo</th>
                      <th>Jugador</th>
                      <th>Minuto</th>
                      <th>Observacion</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="incidencia in detalleIncidencias" :key="incidencia.id">
                      <td class="fw-semibold">{{ incidencia.tipo_evento_partido_descripcion || '-' }}</td>
                      <td>{{ incidencia.equipo_nombre || '-' }}</td>
                      <td>{{ formatJugadorIncidencia(incidencia) }}</td>
                      <td>{{ incidencia.minuto ?? '-' }}</td>
                      <td>{{ incidencia.observacion || '-' }}</td>
                    </tr>
                    <tr v-if="detalleIncidencias.length === 0">
                      <td colspan="5" class="text-center text-muted py-4">Sin incidencias registradas.</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-light px-4" @click="showDetalleModal = false">Cerrar</button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <ConfirmModal
      v-model="showDeleteModal"
      title="Eliminar Evento"
      message="¿Estás seguro de eliminar este evento? Si luego queda relacionado con cruces o detalle de partido, la base puede restringir la operación."
      confirm-button-text="Eliminar"
      variant="danger"
      :is-loading="isDeleting"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import ConfirmModal from '@/components/ConfirmModal.vue';
import FuzzySearch from '@/components/FuzzySearch.vue';
import SortableTableHead, { useSorting } from '@/components/SortableTableHead.vue';
import datosMaestrosService from '@/services/datosMaestrosService';
import eventosService from '@/services/instalaciones/eventosService';
import { useToastStore } from '@/stores/toastStore';

const toast = useToastStore();
const { sortKey, sortDir, handleSort, sortItems } = useSorting();

const tiposEvento = ['partido', 'festejo', 'reunion', 'otro'];

const columns = [
  { key: 'titulo',                   label: 'Titulo',  sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'tipo_evento',              label: 'Tipo',    sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'estado_evento_descripcion',label: 'Estado',  sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'fecha_hora_inicio',        label: 'Inicio',  sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'equipo_local_nombre',      label: 'Equipos', sortable: false, thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'resultado',                label: 'Resultado', sortable: false, thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'numero_fecha',             label: 'Fecha',   sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary text-center', thStyle: 'width: 100px' },
  { key: 'acciones',                 label: 'Acciones',sortable: false, thClass: 'pe-4 py-3 text-uppercase fs-xs fw-bold text-secondary text-end' },
];

const eventos = ref([]);
const arbitros = ref([]);
const equipos = ref([]);
const estadosEvento = ref([]);
const torneos = ref([]);
const canchas = ref([]);
const searchQuery = ref('');
const gruposAbiertos = ref([]);
const loading = ref(false);
const showFormModal = ref(false);
const showDetalleModal = ref(false);
const showDeleteModal = ref(false);
const isEditing = ref(false);
const isSaving = ref(false);
const isDeleting = ref(false);
const detalleLoading = ref(false);
const idToDelete = ref(null);
const detalleEvento = ref(null);
const detalleIncidencias = ref([]);

const emptyForm = () => ({
  id: null,
  id_torneo: null,
  id_estado_evento: null,
  tipo_evento: 'partido',
  titulo: '',
  descripcion: '',
  numero_fecha: null,
  fecha_hora_inicio: '',
  fecha_hora_fin: '',
  id_cancha: null,
  id_arbitro: null,
  id_equipo_local: null,
  id_equipo_visitante: null,
  resultado_local: null,
  resultado_visitante: null,
  resultado_penales_local: null,
  resultado_penales_visitante: null,
});

const form = ref(emptyForm());
const originalForm = ref({});

const toDateTimeLocal = (value) => {
  if (!value) return '';
  return String(value).replace(' ', 'T').slice(0, 16);
};

const formatDateTime = (value) => {
  if (!value) return '-';
  const date = new Date(String(value).replace(' ', 'T'));
  return Number.isNaN(date.getTime()) ? value : date.toLocaleString('es-AR');
};

const formatDateShort = (value) => {
  if (!value) return null;
  const date = new Date(String(value).replace(' ', 'T'));
  return Number.isNaN(date.getTime()) ? null : date.toLocaleDateString('es-AR');
};

const formatTeams = (item) => {
  if (!item.id_equipo_local && !item.id_equipo_visitante) return '-';
  return `${item.equipo_local_nombre || 'Local'} vs ${item.equipo_visitante_nombre || 'Visitante'}`;
};

const formatResult = (item) => {
  const local = item.resultado_local;
  const visitante = item.resultado_visitante;
  if (local == null && visitante == null) return '-';
  return `${local ?? '-'} - ${visitante ?? '-'}`;
};

const formatPenales = (item) => {
  const local = item?.resultado_penales_local;
  const visitante = item?.resultado_penales_visitante;
  if (local == null && visitante == null) return '-';
  return `${local ?? '-'} - ${visitante ?? '-'}`;
};

const formatJugadorIncidencia = (incidencia) => {
  const nombre = incidencia?.jugador_nombre || '';
  const apellido = incidencia?.jugador_apellido || '';
  if (!nombre && !apellido) return '-';
  return apellido ? `${apellido}${nombre ? `, ${nombre}` : ''}` : nombre;
};

const eventosFiltrados = computed(() => {
  let items = eventos.value;
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    items = items.filter(item =>
      item.titulo?.toLowerCase().includes(query) ||
      item.tipo_evento?.toLowerCase().includes(query) ||
      item.descripcion?.toLowerCase().includes(query) ||
      item.torneo_nombre?.toLowerCase().includes(query) ||
      item.cancha_nombre?.toLowerCase().includes(query) ||
      item.equipo_local_nombre?.toLowerCase().includes(query) ||
      item.equipo_visitante_nombre?.toLowerCase().includes(query) ||
      String(item.id).includes(query)
    );
  }
  return sortItems(items);
});

const getTorneoNombre = (item) => {
  if (item.torneo_nombre) return item.torneo_nombre;
  if (item.id_torneo) return `Torneo ${item.id_torneo}`;
  return 'Sin torneo';
};

const eventosAgrupadosPorTorneo = computed(() => {
  const grupos = new Map();

  eventosFiltrados.value.forEach((item) => {
    const key = item.id_torneo ? `torneo-${item.id_torneo}` : 'sin-torneo';
    if (!grupos.has(key)) {
      grupos.set(key, {
        key,
        nombre: getTorneoNombre(item),
        items: [],
      });
    }
    grupos.get(key).items.push(item);
  });

  return Array.from(grupos.values()).map((grupo) => {
    const fechas = grupo.items
      .map(item => ({
        raw: item.fecha_hora_inicio,
        time: new Date(String(item.fecha_hora_inicio || '').replace(' ', 'T')).getTime(),
      }))
      .filter(item => !Number.isNaN(item.time))
      .sort((a, b) => a.time - b.time);

    const primeraFecha = fechas[0]?.raw ? formatDateShort(fechas[0].raw) : null;
    const ultimaFecha = fechas[fechas.length - 1]?.raw ? formatDateShort(fechas[fechas.length - 1].raw) : null;
    const rangoFechas = primeraFecha && ultimaFecha
      ? primeraFecha === ultimaFecha ? primeraFecha : `${primeraFecha} - ${ultimaFecha}`
      : '';

    return {
      ...grupo,
      rangoFechas,
    };
  });
});

const isGrupoAbierto = (key) => {
  if (searchQuery.value.trim()) return true;
  return gruposAbiertos.value.includes(key);
};

const toggleGrupo = (key) => {
  if (gruposAbiertos.value.includes(key)) {
    gruposAbiertos.value = gruposAbiertos.value.filter(item => item !== key);
    return;
  }

  gruposAbiertos.value = [...gruposAbiertos.value, key];
};

const getApiMessage = (error, fallback) => error?.response?.data?.message || fallback;

const fetchData = async () => {
  loading.value = true;
  try {
    const [estadosEventoData, torneosData, canchasData, eventosData, arbitrosData, equiposData] = await Promise.all([
      datosMaestrosService.getEstadosEvento(),
      datosMaestrosService.getTorneos(),
      datosMaestrosService.getCanchas(),
      eventosService.getEventos(),
      datosMaestrosService.getArbitros(),
      datosMaestrosService.getEquipos(),
    ]);
    estadosEvento.value = estadosEventoData.filter(item => Boolean(Number(item.activo)));
    torneos.value = torneosData;
    canchas.value = canchasData.filter(item => Boolean(Number(item.activo)));
    eventos.value = eventosData;
    arbitros.value = arbitrosData.filter(item => Boolean(Number(item.activo)));
    equipos.value = equiposData.filter(item => Boolean(Number(item.activo)));
  } catch (error) {
    toast.showToast({ message: getApiMessage(error, 'Error al cargar los eventos.'), type: 'danger' });
  } finally {
    loading.value = false;
  }
};

const openModal = (item = null) => {
  if (item) {
    isEditing.value = true;
    form.value = {
      id: item.id,
      id_torneo: item.id_torneo ? Number(item.id_torneo) : null,
      id_estado_evento: item.id_estado_evento ? Number(item.id_estado_evento) : null,
      tipo_evento: item.tipo_evento || 'partido',
      titulo: item.titulo || '',
      descripcion: item.descripcion || '',
      numero_fecha: item.numero_fecha ? Number(item.numero_fecha) : null,
      fecha_hora_inicio: toDateTimeLocal(item.fecha_hora_inicio),
      fecha_hora_fin: toDateTimeLocal(item.fecha_hora_fin),
      id_cancha: item.id_cancha ? Number(item.id_cancha) : null,
      id_arbitro: item.id_arbitro ? Number(item.id_arbitro) : null,
      id_equipo_local: item.id_equipo_local ? Number(item.id_equipo_local) : null,
      id_equipo_visitante: item.id_equipo_visitante ? Number(item.id_equipo_visitante) : null,
      resultado_local: item.resultado_local ?? null,
      resultado_visitante: item.resultado_visitante ?? null,
      resultado_penales_local: item.resultado_penales_local ?? null,
      resultado_penales_visitante: item.resultado_penales_visitante ?? null,
    };
    originalForm.value = buildPayload();
  } else {
    isEditing.value = false;
    form.value = emptyForm();
  }
  showFormModal.value = true;
};

const openDetalleEvento = async (item) => {
  detalleEvento.value = item;
  detalleIncidencias.value = [];
  showDetalleModal.value = true;
  detalleLoading.value = true;

  try {
    const data = await eventosService.getEventosPartido(item.id);
    detalleIncidencias.value = Array.isArray(data) ? data : [];
  } catch (error) {
    toast.showToast({ message: getApiMessage(error, 'No se pudieron cargar las incidencias del evento.'), type: 'danger' });
  } finally {
    detalleLoading.value = false;
  }
};

const buildPayload = () => ({
  ...(isEditing.value ? { id: form.value.id } : {}),
  id_torneo: form.value.id_torneo || null,
  id_estado_evento: form.value.id_estado_evento || null,
  tipo_evento: form.value.tipo_evento,
  titulo: form.value.titulo.trim(),
  descripcion: form.value.descripcion?.trim() || null,
  numero_fecha: form.value.numero_fecha || null,
  fecha_hora_inicio: form.value.fecha_hora_inicio || null,
  fecha_hora_fin: form.value.fecha_hora_fin || null,
  id_cancha: form.value.id_cancha || null,
  id_arbitro: form.value.id_arbitro || null,
  id_equipo_local: form.value.id_equipo_local || null,
  id_equipo_visitante: form.value.id_equipo_visitante || null,
});

const save = async () => {
  if (!form.value.id_estado_evento || !form.value.tipo_evento || !form.value.titulo.trim() || !form.value.fecha_hora_inicio) {
    toast.showToast({ message: 'Estado, tipo, titulo y fecha/hora de inicio son obligatorios.', type: 'warning' });
    return;
  }

  if (form.value.fecha_hora_fin && new Date(form.value.fecha_hora_fin) < new Date(form.value.fecha_hora_inicio)) {
    toast.showToast({ message: 'La fecha/hora de fin no puede ser anterior al inicio.', type: 'warning' });
    return;
  }

  if (form.value.id_equipo_local && form.value.id_equipo_visitante && Number(form.value.id_equipo_local) === Number(form.value.id_equipo_visitante)) {
    toast.showToast({ message: 'El equipo local y visitante no pueden ser el mismo.', type: 'warning' });
    return;
  }

  if (isEditing.value && JSON.stringify(buildPayload()) === JSON.stringify(originalForm.value)) {
    toast.showToast({ message: 'No se detectaron cambios.', type: 'info' });
    showFormModal.value = false;
    return;
  }

  isSaving.value = true;
  try {
    if (isEditing.value) {
      await eventosService.actualizarEvento(buildPayload());
      toast.showToast({ message: 'Evento actualizado correctamente.', type: 'success' });
    } else {
      await eventosService.crearEvento(buildPayload());
      toast.showToast({ message: 'Evento creado correctamente.', type: 'success' });
    }
    showFormModal.value = false;
    fetchData();
  } catch (error) {
    toast.showToast({ message: getApiMessage(error, 'Error al guardar el evento.'), type: 'danger' });
  } finally {
    isSaving.value = false;
  }
};

const prepareDelete = (id) => {
  idToDelete.value = id;
  showDeleteModal.value = true;
};

const confirmDelete = async () => {
  isDeleting.value = true;
  try {
    await eventosService.eliminarEvento(idToDelete.value);
    toast.showToast({ message: 'Evento eliminado correctamente.', type: 'success' });
    showDeleteModal.value = false;
    fetchData();
  } catch (error) {
    toast.showToast({ message: getApiMessage(error, 'Error al eliminar el evento.'), type: 'danger' });
  } finally {
    isDeleting.value = false;
  }
};

onMounted(fetchData);
</script>

<style scoped>
.fs-xs { font-size: 0.75rem; }
.btn-link { text-decoration: none; }
.form-section + .form-section {
  margin-top: 1.75rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e2e8f0;
}
.form-section-title {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  font-size: 0.95rem;
  font-weight: 700;
  color: #334155;
  margin-bottom: 1.1rem;
}
.form-section-icon {
  width: 30px;
  height: 30px;
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #e8f1ff;
  color: #0d6efd;
  font-size: 0.9rem;
  flex-shrink: 0;
}
.detalle-box {
  border: 1px solid #dfe7f0;
  border-radius: 0.5rem;
  background: #f8fafc;
  padding: 0.85rem 1rem;
}
.eventos-table-wrap {
  max-height: calc(100vh - 220px);
  overflow: auto;
}
.eventos-table-wrap :deep(thead th) {
  position: sticky;
  top: 0;
  z-index: 4;
  background: #f8f9fa;
  box-shadow: 0 1px 0 #dfe7f0;
}
.torneo-group-row td {
  background: #f4f7fb;
  border-top: 1px solid #dfe7f0;
  border-bottom: 1px solid #dfe7f0;
}
.torneo-group-toggle {
  width: 100%;
  border: 0;
  background: transparent;
  color: inherit;
  text-align: left;
}
.torneo-group-toggle:hover {
  background: #eef4fb;
}
.torneo-group-toggle:focus-visible {
  outline: 2px solid #86b7fe;
  outline-offset: -2px;
}
.torneo-group-icon {
  width: 2rem;
  height: 2rem;
  border-radius: 0.5rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #0d6efd;
  background: #e8f1ff;
  flex: 0 0 auto;
}
.torneo-group-chevron {
  width: 1.75rem;
  height: 1.75rem;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #eef3f8;
  color: #5f7285;
}
.loading-overlay-local {
  position: absolute;
  top: 0; left: 0; width: 100%; height: 100%;
  background-color: rgba(255, 255, 255, 0.85);
  z-index: 10;
}
</style>
