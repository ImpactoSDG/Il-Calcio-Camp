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

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <SortableTableHead
            :columns="columns"
            :sort-key="sortKey"
            :sort-dir="sortDir"
            @sort="handleSort"
          />
          <tbody class="bg-white">
            <tr v-for="item in eventosFiltrados" :key="item.id">
              <td class="fw-medium text-dark">{{ item.titulo }}</td>
              <td class="text-muted text-capitalize">{{ item.tipo_evento }}</td>
              <td class="text-muted">{{ item.estado_evento_descripcion || item.id_estado_evento }}</td>
              <td class="text-muted">{{ formatDateTime(item.fecha_hora_inicio) }}</td>
              <td class="text-muted">{{ formatTeams(item) }}</td>
              <td class="text-muted">{{ formatResult(item) }}</td>
              <td class="text-center">
                <span class="badge rounded-pill px-3 bg-primary-subtle text-primary-custom">{{ item.numero_fecha || '-' }}</span>
              </td>
              <td class="pe-4 text-end">
                <button @click="openModal(item)" class="btn btn-link link-secondary p-1 me-2" title="Editar">
                  <i class="bi bi-pencil-square fs-4"></i>
                </button>
                <button @click="prepareDelete(item.id)" class="btn btn-link link-danger p-1" title="Eliminar">
                  <i class="bi bi-trash3 fs-4"></i>
                </button>
              </td>
            </tr>
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

                  <div class="col-md-12">
                    <label class="form-label">Descripcion</label>
                    <textarea v-model.trim="form.descripcion" class="form-control" rows="2" placeholder="Detalle del evento"></textarea>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label">Fecha/Hora Inicio</label>
                    <input v-model="form.fecha_hora_inicio" type="datetime-local" class="form-control" required />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Fecha/Hora Fin</label>
                    <input v-model="form.fecha_hora_fin" type="datetime-local" class="form-control" />
                  </div>

                  <div class="col-md-4">
                    <label class="form-label">Cancha</label>
                    <select v-model.number="form.id_cancha" class="form-select">
                      <option :value="null">Sin cancha</option>
                      <option v-for="cancha in canchas" :key="cancha.id" :value="Number(cancha.id)">
                        {{ cancha.nombre }}
                      </option>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Arbitro</label>
                    <select v-model.number="form.id_arbitro" class="form-select">
                      <option :value="null">Sin asignar</option>
                      <option v-for="arbitro in arbitros" :key="arbitro.id" :value="Number(arbitro.id)">
                        {{ arbitro.apellido }}, {{ arbitro.nombre }}
                      </option>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Tipo partido</label>
                    <div class="form-text mt-2">Los equipos y resultados aplican sobre todo a eventos de tipo partido.</div>
                  </div>

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

                  <div class="col-md-3">
                    <label class="form-label">Resultado local</label>
                    <input v-model.number="form.resultado_local" type="number" class="form-control" min="0" placeholder="Opcional" />
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Resultado visitante</label>
                    <input v-model.number="form.resultado_visitante" type="number" class="form-control" min="0" placeholder="Opcional" />
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Penales local</label>
                    <input v-model.number="form.resultado_penales_local" type="number" class="form-control" min="0" placeholder="Opcional" />
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Penales visitante</label>
                    <input v-model.number="form.resultado_penales_visitante" type="number" class="form-control" min="0" placeholder="Opcional" />
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
import eventosService from '@/services/eventosService';
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
const loading = ref(false);
const showFormModal = ref(false);
const showDeleteModal = ref(false);
const isEditing = ref(false);
const isSaving = ref(false);
const isDeleting = ref(false);
const idToDelete = ref(null);

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
    originalForm.value = { ...form.value };
  } else {
    isEditing.value = false;
    form.value = emptyForm();
  }
  showFormModal.value = true;
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
  resultado_local: form.value.resultado_local ?? null,
  resultado_visitante: form.value.resultado_visitante ?? null,
  resultado_penales_local: form.value.resultado_penales_local ?? null,
  resultado_penales_visitante: form.value.resultado_penales_visitante ?? null,
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
.loading-overlay-local {
  position: absolute;
  top: 0; left: 0; width: 100%; height: 100%;
  background-color: rgba(255, 255, 255, 0.85);
  z-index: 10;
}
</style>