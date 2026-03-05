<template>
  <div class="container-fluid p-4 bg-white min-vh-100 position-relative animate-fade-in">

    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
      <div class="d-flex align-items-center">
        <button @click="$router.back()" class="btn-back-arrow me-2" title="Volver">
          <i class="bi bi-arrow-left"></i>
        </button>
        <h1 class="h5 fw-bold mb-0 text-secondary">INGRESOS DE ARTÍCULO</h1>
      </div>
      <button @click="openModal()" class="btn-primary-modern d-flex align-items-center">
        <i class="bi bi-plus-circle-fill fs-6 me-2"></i> Nuevo Ingreso
      </button>
    </div>

    <!-- Buscador -->
    <div class="mb-3">
      <FuzzySearch
        v-model="searchQuery"
        placeholder="Buscar por artículo..."
      />
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
            <tr v-for="item in ingresosFiltrados" :key="item.id">
              <td class="ps-4 text-muted fw-bold">{{ item.id }}</td>
              <td class="fw-medium text-dark">{{ item.articulo_nombre }}</td>
              <td class="text-muted">{{ formatFecha(item.fecha_ingreso) }}</td>
              <td class="text-end fw-semibold">{{ item.cantidad }}</td>
              <td class="text-end text-muted">${{ Number(item.precio_unitario).toFixed(2) }}</td>
              <td class="text-end fw-semibold text-dark">${{ Number(item.total).toFixed(2) }}</td>
              <td class="text-center">
                <span :class="item.es_perecedero ? 'bg-warning-subtle text-warning' : 'bg-secondary-subtle text-secondary'" class="badge rounded-pill px-3">
                  {{ item.es_perecedero ? 'Sí' : 'No' }}
                </span>
              </td>
              <td class="text-center">
                <span :class="item.es_ajuste ? 'bg-info-subtle text-info' : 'bg-secondary-subtle text-secondary'" class="badge rounded-pill px-3">
                  {{ item.es_ajuste ? 'Sí' : 'No' }}
                </span>
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
            <tr v-if="ingresosFiltrados.length === 0 && !loading">
              <td colspan="9" class="text-center py-5 text-muted">
                No hay ingresos que coincidan con la búsqueda.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Formulario -->
    <div v-if="showFormModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="bi me-2" :class="isEditing ? 'bi-pencil-square' : 'bi-box-arrow-in-down'"></i>
              {{ isEditing ? 'Editar Ingreso' : 'Nuevo Ingreso de Artículo' }}
            </h5>
            <button type="button" class="btn-close" @click="showFormModal = false"></button>
          </div>
          <form @submit.prevent="save">
            <div class="modal-body">
              <div class="row g-3">
                <div class="col-md-8">
                  <label class="form-label">Artículo <span class="text-danger">*</span></label>
                  <select v-model.number="form.id_articulo" class="form-select" required>
                    <option :value="null" disabled>Seleccionar artículo...</option>
                    <option v-for="art in articulos" :key="art.id" :value="art.id">{{ art.nombre }}</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Fecha de Ingreso <span class="text-danger">*</span></label>
                  <input v-model="form.fecha_ingreso" type="date" class="form-control" required />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Cantidad <span class="text-danger">*</span></label>
                  <input v-model.number="form.cantidad" type="number" step="0.01" min="0" class="form-control" placeholder="0" required @input="calcularTotal" />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Precio Unitario ($) <span class="text-danger">*</span></label>
                  <input v-model.number="form.precio_unitario" type="number" step="0.01" min="0" class="form-control" placeholder="0.00" required @input="calcularTotal" />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Total ($)</label>
                  <input :value="totalCalculado" type="number" class="form-control bg-light text-muted" readonly />
                </div>
                <div class="col-md-6">
                  <label class="form-label">Vencimiento</label>
                  <input v-model="form.vencimiento" type="date" class="form-control" />
                </div>
                <div class="col-md-3 d-flex align-items-end pb-1">
                  <div class="form-check form-switch ps-4">
                    <input v-model="form.es_perecedero" class="form-check-input" type="checkbox" role="switch" id="chkPerecedero" />
                    <label class="form-check-label fw-semibold ms-2" for="chkPerecedero">Perecedero</label>
                  </div>
                </div>
                <div class="col-md-3 d-flex align-items-end pb-1">
                  <div class="form-check form-switch ps-4">
                    <input v-model="form.es_ajuste" class="form-check-input" type="checkbox" role="switch" id="chkAjuste" />
                    <label class="form-check-label fw-semibold ms-2" for="chkAjuste">Es Ajuste</label>
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

    <ConfirmModal
      v-model="showDeleteModal"
      title="Eliminar Ingreso"
      message="¿Estás seguro de eliminar este ingreso de artículo? Esta acción no se puede deshacer."
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
import articulosService from '@/services/articulosService';
import { useToastStore } from '@/stores/toastStore';

const toast = useToastStore();

const { sortKey, sortDir, handleSort, sortItems } = useSorting()

const columns = [
  { key: 'id',               label: 'ID',            sortable: true,  thClass: 'ps-4 py-3 text-uppercase fs-xs fw-bold text-secondary', thStyle: 'width: 70px' },
  { key: 'articulo_nombre',  label: 'Artículo',       sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'fecha_ingreso',    label: 'Fecha Ingreso',  sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'cantidad',         label: 'Cantidad',       sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary text-end' },
  { key: 'precio_unitario',  label: 'Precio Unit.',   sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary text-end' },
  { key: 'total',            label: 'Total',          sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary text-end' },
  { key: 'es_perecedero',    label: 'Perecedero',     sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary text-center' },
  { key: 'es_ajuste',        label: 'Ajuste',         sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary text-center' },
  { key: 'acciones',         label: 'Acciones',       sortable: false, thClass: 'pe-4 py-3 text-uppercase fs-xs fw-bold text-secondary text-end' },
]

const ingresos = ref([]);
const articulos = ref([]);
const loading = ref(false);
const searchQuery = ref('');
const showFormModal = ref(false);
const showDeleteModal = ref(false);
const isEditing = ref(false);
const isSaving = ref(false);
const isDeleting = ref(false);
const idToDelete = ref(null);

const emptyForm = () => ({
  id: null,
  id_articulo: null,
  fecha_ingreso: new Date().toISOString().split('T')[0],
  cantidad: null,
  precio_unitario: null,
  vencimiento: null,
  es_perecedero: false,
  es_ajuste: false,
});
const form = ref(emptyForm());
const originalForm = ref({});

const totalCalculado = computed(() => {
  const c = Number(form.value.cantidad) || 0;
  const p = Number(form.value.precio_unitario) || 0;
  return (c * p).toFixed(2);
});

const ingresosFiltrados = computed(() => {
  let items = ingresos.value;
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    items = items.filter(i => i.articulo_nombre?.toLowerCase().includes(q));
  }
  return sortItems(items);
});

const formatFecha = (fecha) => {
  if (!fecha) return '—';
  return new Date(fecha).toLocaleDateString('es-AR');
};

const fetchData = async () => {
  loading.value = true;
  try {
    [ingresos.value, articulos.value] = await Promise.all([
      articulosService.getIngresos(),
      articulosService.getArticulos(),
    ]);
  } catch {
    toast.showToast({ message: 'Error al cargar los ingresos.', type: 'danger' });
  } finally {
    loading.value = false;
  }
};

const openModal = (item = null) => {
  if (item) {
    isEditing.value = true;
    form.value = {
      ...item,
      es_perecedero: Boolean(Number(item.es_perecedero)),
      es_ajuste: Boolean(Number(item.es_ajuste)),
    };
    originalForm.value = { ...form.value };
  } else {
    isEditing.value = false;
    form.value = emptyForm();
  }
  showFormModal.value = true;
};

const save = async () => {
  if (!form.value.id_articulo || !form.value.fecha_ingreso || !form.value.cantidad || !form.value.precio_unitario) {
    toast.showToast({ message: 'Artículo, fecha, cantidad y precio son obligatorios.', type: 'warning' });
    return;
  }
  if (isEditing.value && JSON.stringify(form.value) === JSON.stringify(originalForm.value)) {
    toast.showToast({ message: 'No se detectaron cambios.', type: 'info' });
    showFormModal.value = false;
    return;
  }

  const payload = { ...form.value, total: totalCalculado.value };

  isSaving.value = true;
  try {
    if (isEditing.value) {
      await articulosService.actualizarIngreso(payload);
      toast.showToast({ message: 'Ingreso actualizado correctamente.', type: 'success' });
    } else {
      await articulosService.crearIngreso(payload);
      toast.showToast({ message: 'Ingreso registrado correctamente.', type: 'success' });
    }
    showFormModal.value = false;
    fetchData();
  } catch {
    toast.showToast({ message: 'Error al guardar el ingreso.', type: 'danger' });
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
    await articulosService.eliminarIngreso(idToDelete.value);
    toast.showToast({ message: 'Ingreso eliminado correctamente.', type: 'success' });
    showDeleteModal.value = false;
    fetchData();
  } catch {
    toast.showToast({ message: 'Error al eliminar el ingreso.', type: 'danger' });
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
