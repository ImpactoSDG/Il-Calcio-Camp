<template>
  <div class="container-fluid p-4 bg-white min-vh-100 position-relative animate-fade-in">

    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
      <div class="d-flex align-items-center">
        <button @click="$router.back()" class="btn-back-arrow me-2" title="Volver">
          <i class="bi bi-arrow-left"></i>
        </button>
        <h1 class="h5 fw-bold mb-0 text-secondary">JUGADORES</h1>
      </div>
      <div class="d-flex gap-2">
        <button @click="openModal()" class="btn-primary-modern d-flex align-items-center">
          <i class="bi bi-plus-circle-fill fs-6 me-2"></i> Nuevo
        </button>
      </div>
    </div>

    <div class="mb-3">
      <FuzzySearch v-model="searchQuery" placeholder="Buscar por nombre, apellido o DNI..." />
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
            <tr v-for="item in jugadoresFiltrados" :key="item.id">
              <td class="ps-4 fw-medium text-dark">{{ item.apellido }}, {{ item.nombre }}</td>
              <td class="text-muted">{{ item.dni || '-' }}</td>
              <td class="text-muted">{{ item.equipo_nombre || '-' }}</td>
              <td class="text-muted">{{ formatDate(item.fecha_nac) }}</td>
              <td class="text-muted">{{ formatDate(item.fecha_alta) }}</td>
              <td class="text-center">
                <span :class="item.activo ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary'" class="badge rounded-pill px-3">
                  {{ item.activo ? 'Activo' : 'Inactivo' }}
                </span>
              </td>
              <td class="pe-4 text-end">
                <div class="d-flex gap-1 justify-content-end flex-nowrap">
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
            <tr v-if="jugadoresFiltrados.length === 0 && !loading">
              <td colspan="7" class="text-center py-5 text-muted">
                No hay jugadores que coincidan con la búsqueda.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <Teleport to="body">
      <div v-if="showFormModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
        <div class="modal-dialog modal-dialog-centered modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">
                <i class="bi me-2" :class="isEditing ? 'bi-pencil-square' : 'bi-plus-circle'"></i>
                {{ isEditing ? 'Editar Jugador' : 'Nuevo Jugador' }}
              </h5>
              <button type="button" class="btn-close" @click="showFormModal = false"></button>
            </div>
            <form @submit.prevent="save">
              <div class="modal-body">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">Nombre</label>
                    <input v-model.trim="form.nombre" type="text" class="form-control" placeholder="Ej: Lionel" required />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Apellido</label>
                    <input v-model.trim="form.apellido" type="text" class="form-control" placeholder="Ej: Messi" required />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">DNI</label>
                    <input v-model.trim="form.dni" type="text" class="form-control" placeholder="Ej: 30123456" maxlength="8" inputmode="numeric" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Fecha de Nacimiento</label>
                    <input v-model="form.fecha_nac" type="date" class="form-control" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Fecha de Alta</label>
                    <input v-model="form.fecha_alta" type="date" class="form-control" />
                  </div>
                  <div class="col-md-12">
                    <label class="form-label">Equipo actual</label>
                    <select v-model.number="form.id_equipo_actual" class="form-select">
                      <option :value="null">Sin asignar</option>
                      <option v-for="equipo in equipos" :key="equipo.id" :value="Number(equipo.id)">
                        {{ equipo.nombre }}
                        <template v-if="equipo.disciplina"> - {{ equipo.disciplina }}</template>
                      </option>
                    </select>
                  </div>
                  <div class="col-12">
                    <div class="form-check form-switch mb-0 d-flex align-items-center gap-2 jugador-switch-row">
                      <input v-model="form.activo" class="form-check-input mt-0" type="checkbox" role="switch" id="chkActivoJugador" />
                      <label class="form-check-label fw-semibold mb-0" for="chkActivoJugador">
                        {{ form.activo ? 'Activo' : 'Inactivo' }}
                      </label>
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

    <ConfirmModal
      v-model="showDeleteModal"
      title="Eliminar Jugador"
      message="¿Estás seguro de eliminar este jugador? Si luego se relaciona con equipos o eventos, esta acción puede quedar restringida por la base de datos."
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
import { useToastStore } from '@/stores/toastStore';

const toast = useToastStore();

const { sortKey, sortDir, handleSort, sortItems } = useSorting();

const columns = [
  { key: 'apellido',   label: 'Jugador',            sortable: true,  thClass: 'ps-4 py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'dni',        label: 'DNI',                sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'equipo_nombre', label: 'Equipo',          sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'fecha_nac',  label: 'Fecha Nac.',         sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'fecha_alta', label: 'Fecha Alta',         sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'activo',     label: 'Estado',             sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary text-center', thStyle: 'width: 120px' },
  { key: 'acciones',   label: 'Acciones',           sortable: false, thClass: 'pe-4 py-3 text-uppercase fs-xs fw-bold text-secondary text-end' },
];

const jugadores = ref([]);
const equipos = ref([]);
const searchQuery = ref('');
const jugadoresFiltrados = computed(() => {
  let items = jugadores.value;
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    items = items.filter(item =>
      item.nombre?.toLowerCase().includes(query) ||
      item.apellido?.toLowerCase().includes(query) ||
      item.dni?.toLowerCase().includes(query) ||
      item.equipo_nombre?.toLowerCase().includes(query)
    );
  }
  return sortItems(items);
});
const loading = ref(false);
const showFormModal = ref(false);
const showDeleteModal = ref(false);
const isEditing = ref(false);
const isSaving = ref(false);
const isDeleting = ref(false);
const idToDelete = ref(null);

const form = ref({
  id: null,
  nombre: '',
  apellido: '',
  dni: '',
  fecha_nac: '',
  fecha_alta: '',
  id_equipo_actual: null,
  activo: true,
});
const originalForm = ref({});

const normalizeDate = (value) => {
  if (!value) return '';
  return String(value).slice(0, 10);
};

const formatDate = (value) => {
  if (!value) return '-';
  const [year, month, day] = String(value).slice(0, 10).split('-');
  return year && month && day ? `${day}/${month}/${year}` : value;
};

const todayString = () => new Date().toISOString().slice(0, 10);

const fetchData = async () => {
  loading.value = true;
  try {
    const [jugadoresData, equiposData] = await Promise.all([
      datosMaestrosService.getJugadores(),
      datosMaestrosService.getEquipos(),
    ]);
    jugadores.value = jugadoresData.map(item => ({
      ...item,
      activo: Boolean(Number(item.activo)),
      id_equipo_actual: item.id_equipo_actual ? Number(item.id_equipo_actual) : null,
    }));
    equipos.value = equiposData.filter(item => Boolean(Number(item.activo)));
  } catch {
    toast.showToast({ message: 'Error al cargar los jugadores.', type: 'danger' });
  } finally {
    loading.value = false;
  }
};

const openModal = (item = null) => {
  if (item) {
    isEditing.value = true;
    form.value = {
      ...item,
      dni: item.dni ?? '',
      fecha_nac: normalizeDate(item.fecha_nac),
      fecha_alta: normalizeDate(item.fecha_alta),
      id_equipo_actual: item.id_equipo_actual ? Number(item.id_equipo_actual) : null,
      activo: Boolean(Number(item.activo)),
    };
    originalForm.value = { ...form.value };
  } else {
    isEditing.value = false;
    form.value = {
      id: null,
      nombre: '',
      apellido: '',
      dni: '',
      fecha_nac: '',
      fecha_alta: todayString(),
      id_equipo_actual: null,
      activo: true,
    };
  }
  showFormModal.value = true;
};

const buildPayload = () => ({
  ...(isEditing.value ? { id: form.value.id } : {}),
  nombre: form.value.nombre.trim(),
  apellido: form.value.apellido.trim(),
  dni: form.value.dni?.trim() || null,
  fecha_nac: form.value.fecha_nac || null,
  fecha_alta: form.value.fecha_alta || null,
  id_equipo_actual: form.value.id_equipo_actual || null,
  activo: Boolean(form.value.activo),
});

const save = async () => {
  if (!form.value.nombre?.trim() || !form.value.apellido?.trim()) {
    toast.showToast({ message: 'Nombre y apellido son obligatorios.', type: 'warning' });
    return;
  }

  if (form.value.dni && !/^\d{8}$/.test(form.value.dni.trim())) {
    toast.showToast({ message: 'El DNI debe tener exactamente 8 numeros.', type: 'warning' });
    return;
  }

  if (isEditing.value && JSON.stringify(buildPayload()) === JSON.stringify({
    id: originalForm.value.id,
    nombre: originalForm.value.nombre,
    apellido: originalForm.value.apellido,
    dni: originalForm.value.dni || null,
    fecha_nac: originalForm.value.fecha_nac || null,
    fecha_alta: originalForm.value.fecha_alta || null,
    id_equipo_actual: originalForm.value.id_equipo_actual || null,
    activo: Boolean(originalForm.value.activo),
  })) {
    toast.showToast({ message: 'No se detectaron cambios.', type: 'info' });
    showFormModal.value = false;
    return;
  }

  isSaving.value = true;
  try {
    if (isEditing.value) {
      await datosMaestrosService.actualizarJugador(buildPayload());
      toast.showToast({ message: 'Jugador actualizado correctamente.', type: 'success' });
    } else {
      await datosMaestrosService.crearJugador(buildPayload());
      toast.showToast({ message: 'Jugador creado correctamente.', type: 'success' });
    }
    showFormModal.value = false;
    fetchData();
  } catch {
    toast.showToast({ message: 'Error al guardar el jugador.', type: 'danger' });
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
    await datosMaestrosService.eliminarJugador(idToDelete.value);
    toast.showToast({ message: 'Jugador eliminado correctamente.', type: 'success' });
    showDeleteModal.value = false;
    fetchData();
  } catch {
    toast.showToast({ message: 'Error al eliminar el jugador.', type: 'danger' });
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

.jugador-switch-row .form-check-input {
  margin-left: 0;
}
</style>