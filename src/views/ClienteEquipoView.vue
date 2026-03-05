<template>
  <div class="container-fluid p-4 bg-white min-vh-100 position-relative animate-fade-in">

    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
      <div class="d-flex align-items-center">
        <button @click="$router.back()" class="btn-back-arrow me-2" title="Volver">
          <i class="bi bi-arrow-left"></i>
        </button>
        <h1 class="h5 fw-bold mb-0 text-secondary">CLIENTES Y EQUIPOS</h1>
      </div>
      <button @click="openModal()" class="btn-primary-modern d-flex align-items-center">
        <i class="bi bi-plus-circle-fill fs-6 me-2"></i> Nueva Relación
      </button>
    </div>

    <div class="mb-3">
      <FuzzySearch v-model="searchQuery" placeholder="Buscar por cliente o equipo..." />
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
            <tr v-for="item in relacionesFiltradas" :key="item.id_cliente_equipo">
              <td class="ps-4 text-muted fw-bold">{{ item.id_cliente_equipo }}</td>
              <td class="fw-medium text-dark">{{ item.cliente_nombre }}</td>
              <td class="fw-medium text-dark">{{ item.equipo_nombre }}</td>
              <td class="text-muted">
                <span class="badge bg-primary-subtle text-primary-custom rounded-pill px-3">
                  {{ item.equipo_disciplina || '—' }}
                </span>
              </td>
              <td class="pe-4 text-end">
                <button @click="prepareDelete(item.id_cliente_equipo)" class="btn btn-link link-danger p-1" title="Eliminar relación">
                  <i class="bi bi-trash3 fs-4"></i>
                </button>
              </td>
            </tr>
            <tr v-if="relacionesFiltradas.length === 0 && !loading">
              <td colspan="5" class="text-center py-5 text-muted">No hay relaciones que coincidan con la búsqueda.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Formulario -->
    <div v-if="showFormModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.4); backdrop-filter: blur(4px); z-index: 900;">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="bi bi-link-45deg me-2"></i>
              Asociar Cliente a Equipo
            </h5>
            <button type="button" class="btn-close" @click="showFormModal = false"></button>
          </div>
          <form @submit.prevent="save">
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">Cliente <span class="text-danger">*</span></label>
                <select v-model.number="form.id_cliente" class="form-select" required>
                  <option :value="null" disabled>Seleccionar cliente...</option>
                  <option v-for="c in clientes" :key="c.id" :value="c.id">{{ c.nombre_cliente }}</option>
                </select>
              </div>
              <div class="mb-0">
                <label class="form-label">Equipo <span class="text-danger">*</span></label>
                <select v-model.number="form.id_equipo" class="form-select" required>
                  <option :value="null" disabled>Seleccionar equipo...</option>
                  <option v-for="e in equipos" :key="e.id" :value="e.id">{{ e.nombre }} ({{ e.disciplina }})</option>
                </select>
              </div>
            </div>
            <div class="modal-footer">
              <button @click="showFormModal = false" type="button" class="btn btn-light px-4">Cancelar</button>
              <button type="submit" class="btn btn-primary-modern px-4" :disabled="isSaving">
                <span v-if="isSaving" class="spinner-border spinner-border-sm me-2"></span>
                Asociar
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <ConfirmModal
      v-model="showDeleteModal"
      title="Eliminar Relación"
      message="¿Estás seguro de eliminar esta asociación entre cliente y equipo?"
      confirm-button-text="Eliminar"
      variant="danger"
      :is-loading="isDeleting"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import ConfirmModal from '@/components/ConfirmModal.vue';
import FuzzySearch from '@/components/FuzzySearch.vue';
import SortableTableHead, { useSorting } from '@/components/SortableTableHead.vue';
import clientesService from '@/services/clientesService';
import datosMaestrosService from '@/services/datosMaestrosService';
import { useToastStore } from '@/stores/toastStore';

const toast = useToastStore();

const { sortKey, sortDir, handleSort, sortItems } = useSorting()

const columns = [
  { key: 'id_cliente_equipo', label: 'ID',          sortable: true,  thClass: 'ps-4 py-3 text-uppercase fs-xs fw-bold text-secondary', thStyle: 'width: 80px' },
  { key: 'cliente_nombre',    label: 'Cliente',      sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary', icon: 'bi bi-person-fill me-1' },
  { key: 'equipo_nombre',     label: 'Equipo',       sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary', icon: 'bi bi-people-fill me-1' },
  { key: 'equipo_disciplina', label: 'Disciplina',   sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'acciones',          label: 'Acciones',     sortable: false, thClass: 'pe-4 py-3 text-uppercase fs-xs fw-bold text-secondary text-end' },
]

const relaciones = ref([]);
const clientes = ref([]);
const equipos = ref([]);
const loading = ref(false);
const searchQuery = ref('');
const showFormModal = ref(false);
const showDeleteModal = ref(false);
const isSaving = ref(false);
const isDeleting = ref(false);
const idToDelete = ref(null);

const form = ref({ id_cliente: null, id_equipo: null });

const relacionesFiltradas = computed(() => {
  let items = relaciones.value;
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    items = items.filter(r =>
      r.cliente_nombre?.toLowerCase().includes(q) ||
      r.equipo_nombre?.toLowerCase().includes(q) ||
      r.equipo_disciplina?.toLowerCase().includes(q)
    );
  }
  return sortItems(items);
});

const fetchData = async () => {
  loading.value = true;
  try {
    [relaciones.value, clientes.value, equipos.value] = await Promise.all([
      clientesService.getClienteEquipos(),
      clientesService.getClientes(),
      datosMaestrosService.getEquipos(),
    ]);
  } catch {
    toast.showToast({ message: 'Error al cargar las relaciones.', type: 'danger' });
  } finally {
    loading.value = false;
  }
};

const openModal = () => {
  form.value = { id_cliente: null, id_equipo: null };
  showFormModal.value = true;
};

const save = async () => {
  if (!form.value.id_cliente || !form.value.id_equipo) {
    toast.showToast({ message: 'Debe seleccionar un cliente y un equipo.', type: 'warning' });
    return;
  }

  const duplicado = relaciones.value.some(
    r => Number(r.id_cliente) === Number(form.value.id_cliente) && Number(r.id_equipo) === Number(form.value.id_equipo)
  );
  if (duplicado) {
    toast.showToast({ message: 'Esta relación ya existe.', type: 'danger' });
    return;
  }

  isSaving.value = true;
  try {
    await clientesService.crearClienteEquipo(form.value);
    toast.showToast({ message: 'Relación creada correctamente.', type: 'success' });
    showFormModal.value = false;
    fetchData();
  } catch {
    toast.showToast({ message: 'Error al crear la relación.', type: 'danger' });
  } finally {
    isSaving.value = false;
  }
};

const prepareDelete = (id) => {
  idToDelete.value = id;
  showFormModal.value = false;
  showDeleteModal.value = true;
};

const confirmDelete = async () => {
  isDeleting.value = true;
  try {
    await clientesService.eliminarClienteEquipo(idToDelete.value);
    toast.showToast({ message: 'Relación eliminada correctamente.', type: 'success' });
    showDeleteModal.value = false;
    fetchData();
  } catch {
    toast.showToast({ message: 'Error al eliminar la relación.', type: 'danger' });
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
