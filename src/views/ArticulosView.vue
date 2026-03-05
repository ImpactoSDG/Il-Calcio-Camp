<template>
  <div class="container-fluid p-4 bg-white min-vh-100 position-relative animate-fade-in">

    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
      <div class="d-flex align-items-center">
        <button @click="$router.back()" class="btn-back-arrow me-2" title="Volver">
          <i class="bi bi-arrow-left"></i>
        </button>
        <h1 class="h5 fw-bold mb-0 text-secondary">ARTÍCULOS</h1>
      </div>
      <button @click="openModal()" class="btn-primary-modern d-flex align-items-center">
        <i class="bi bi-plus-circle-fill fs-6 me-2"></i> Nuevo
      </button>
    </div>

    <!-- Buscador -->
    <div class="mb-3">
      <FuzzySearch
        v-model="searchQuery"
        placeholder="Buscar por nombre, categoría o código de barras..."
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
            <tr v-for="item in articulosFiltrados" :key="item.id">
              <td class="ps-4 text-muted fw-bold">{{ item.id }}</td>
              <td class="fw-medium text-dark">{{ item.nombre }}</td>
              <td class="text-muted">
                <span class="badge bg-primary-subtle text-primary-custom rounded-pill px-3">
                  {{ item.categoria_descripcion || '—' }}
                </span>
              </td>
              <td class="text-end fw-semibold text-dark">
                {{ item.precio_actual != null ? `$${Number(item.precio_actual).toFixed(2)}` : '—' }}
              </td>
              <td class="text-end text-muted">
                {{ item.costo_actual != null ? `$${Number(item.costo_actual).toFixed(2)}` : '—' }}
              </td>
              <td class="text-muted font-monospace">{{ item.cod_barra || '—' }}</td>
              <td class="text-center">
                <span :class="item.activo ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary'" class="badge rounded-pill px-3">
                  {{ item.activo ? 'Activo' : 'Inactivo' }}
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
            <tr v-if="articulosFiltrados.length === 0 && !loading">
              <td colspan="8" class="text-center py-5 text-muted">
                No hay artículos que coincidan con la búsqueda.
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
              <i class="bi me-2" :class="isEditing ? 'bi-pencil-square' : 'bi-plus-circle'"></i>
              {{ isEditing ? 'Editar Artículo' : 'Nuevo Artículo' }}
            </h5>
            <button type="button" class="btn-close" @click="showFormModal = false"></button>
          </div>
          <form @submit.prevent="save">
            <div class="modal-body">
              <div class="row g-3">
                <div class="col-12">
                  <label class="form-label">Nombre <span class="text-danger">*</span></label>
                  <input v-model.trim="form.nombre" type="text" class="form-control" placeholder="Ej: Pelota de fútbol" required />
                </div>
                <div class="col-md-6">
                  <label class="form-label">Categoría</label>
                  <select v-model.number="form.id_categoria_articulo" class="form-select">
                    <option :value="null">Sin categoría</option>
                    <option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.descripcion }}</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Código de Barras</label>
                  <input v-model.trim="form.cod_barra" type="text" class="form-control" placeholder="Ej: 7790001234567" />
                </div>
                <div class="col-md-6">
                  <label class="form-label">Precio Actual ($)</label>
                  <input v-model.number="form.precio_actual" type="number" step="0.01" min="0" class="form-control" placeholder="0.00" />
                </div>
                <div class="col-md-6">
                  <label class="form-label">Costo Actual ($)</label>
                  <input v-model.number="form.costo_actual" type="number" step="0.01" min="0" class="form-control" placeholder="0.00" />
                </div>
                <div class="col-12">
                  <div class="form-check form-switch ps-4">
                    <input v-model="form.activo" class="form-check-input" type="checkbox" role="switch" id="chkActivo" />
                    <label class="form-check-label fw-semibold ms-2" for="chkActivo">
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

    <ConfirmModal
      v-model="showDeleteModal"
      title="Eliminar Artículo"
      message="¿Estás seguro de eliminar este artículo? Esta acción no se puede deshacer."
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
import datosMaestrosService from '@/services/datosMaestrosService';
import { useToastStore } from '@/stores/toastStore';

const toast = useToastStore();

const { sortKey, sortDir, handleSort, sortItems } = useSorting()

const columns = [
  { key: 'id',                   label: 'ID',         sortable: true,  thClass: 'ps-4 py-3 text-uppercase fs-xs fw-bold text-secondary', thStyle: 'width: 70px' },
  { key: 'nombre',               label: 'Nombre',     sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'categoria_descripcion',label: 'Categoría',  sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'precio_actual',        label: 'Precio',     sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary text-end' },
  { key: 'costo_actual',         label: 'Costo',      sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary text-end' },
  { key: 'cod_barra',            label: 'Cód. Barra', sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'activo',               label: 'Estado',     sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary text-center', thStyle: 'width: 100px' },
  { key: 'acciones',             label: 'Acciones',   sortable: false, thClass: 'pe-4 py-3 text-uppercase fs-xs fw-bold text-secondary text-end' },
]

const articulos = ref([]);
const categorias = ref([]);
const loading = ref(false);
const searchQuery = ref('');
const showFormModal = ref(false);
const showDeleteModal = ref(false);
const isEditing = ref(false);
const isSaving = ref(false);
const isDeleting = ref(false);
const idToDelete = ref(null);

const emptyForm = () => ({ nombre: '', precio_actual: null, costo_actual: null, cod_barra: '', id_categoria_articulo: null, activo: true });
const form = ref(emptyForm());
const originalForm = ref({});

const articulosFiltrados = computed(() => {
  let items = articulos.value;
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    items = items.filter(a =>
      a.nombre?.toLowerCase().includes(q) ||
      a.categoria_descripcion?.toLowerCase().includes(q) ||
      a.cod_barra?.toLowerCase().includes(q)
    );
  }
  return sortItems(items);
});

const fetchData = async () => {
  loading.value = true;
  try {
    [articulos.value, categorias.value] = await Promise.all([
      articulosService.getArticulos(),
      datosMaestrosService.getCategorias(),
    ]);
  } catch {
    toast.showToast({ message: 'Error al cargar los artículos.', type: 'danger' });
  } finally {
    loading.value = false;
  }
};

const openModal = (item = null) => {
  if (item) {
    isEditing.value = true;
    form.value = { ...item, activo: Boolean(Number(item.activo)) };
    originalForm.value = { ...form.value };
  } else {
    isEditing.value = false;
    form.value = emptyForm();
  }
  showFormModal.value = true;
};

const save = async () => {
  if (!form.value.nombre) {
    toast.showToast({ message: 'El nombre es obligatorio.', type: 'warning' });
    return;
  }
  if (isEditing.value && JSON.stringify(form.value) === JSON.stringify(originalForm.value)) {
    toast.showToast({ message: 'No se detectaron cambios.', type: 'info' });
    showFormModal.value = false;
    return;
  }

  isSaving.value = true;
  try {
    if (isEditing.value) {
      await articulosService.actualizarArticulo(form.value);
      toast.showToast({ message: 'Artículo actualizado correctamente.', type: 'success' });
    } else {
      await articulosService.crearArticulo(form.value);
      toast.showToast({ message: 'Artículo creado correctamente.', type: 'success' });
    }
    showFormModal.value = false;
    fetchData();
  } catch {
    toast.showToast({ message: 'Error al guardar el artículo.', type: 'danger' });
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
    await articulosService.eliminarArticulo(idToDelete.value);
    toast.showToast({ message: 'Artículo eliminado correctamente.', type: 'success' });
    showDeleteModal.value = false;
    fetchData();
  } catch {
    toast.showToast({ message: 'Error al eliminar el artículo.', type: 'danger' });
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
