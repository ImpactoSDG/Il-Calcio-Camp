<template>
  <div class="container-fluid p-4 bg-white min-vh-100 position-relative animate-fade-in">

    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
      <div class="d-flex align-items-center">
        <button @click="$router.back()" class="btn-back-arrow me-2" title="Volver">
          <i class="bi bi-arrow-left"></i>
        </button>
        <h1 class="h5 fw-bold mb-0 text-secondary">CATEGORÍAS DE ARTÍCULO</h1>
      </div>
      <div class="d-flex gap-2">
        <button @click="openModal()" class="btn-primary-modern d-flex align-items-center">
          <i class="bi bi-plus-circle-fill fs-6 me-2"></i> Nueva
        </button>
      </div>
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
            <tr v-for="item in sortedCategorias" :key="item.id">
              <td class="ps-4 fw-medium text-dark">{{ item.descripcion }}</td>
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
            <tr v-if="categorias.length === 0 && !loading">
              <td colspan="2" class="text-center py-5 text-muted">
                No hay categorías de artículo registradas.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Formulario -->
    <Teleport to="body">
    <div v-if="showFormModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="bi me-2" :class="isEditing ? 'bi-pencil-square' : 'bi-plus-circle'"></i>
              {{ isEditing ? 'Editar Categoría' : 'Nueva Categoría' }}
            </h5>
            <button type="button" class="btn-close" @click="showFormModal = false"></button>
          </div>
          <form @submit.prevent="save">
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">Descripción</label>
                <input v-model.trim="form.descripcion" type="text" class="form-control" placeholder="Ej: Gaseosas" required />
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

    <!-- Modal Confirmación Eliminación -->
    <ConfirmModal
      v-model="showDeleteModal"
      title="Eliminar Categoría"
      message="¿Estás seguro de que deseas eliminar esta categoría? Esta acción no se puede deshacer."
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
import SortableTableHead, { useSorting } from '@/components/SortableTableHead.vue';
import datosMaestrosService from '@/services/datosMaestrosService';
import { useToastStore } from '@/stores/toastStore';

const toast = useToastStore();

const { sortKey, sortDir, handleSort, sortItems } = useSorting()

const columns = [
  { key: 'descripcion', label: 'Descripción', sortable: true,  thClass: 'ps-4 py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'acciones',    label: 'Acciones',    sortable: false, thClass: 'pe-4 py-3 text-uppercase fs-xs fw-bold text-secondary text-end' },
]

const categorias = ref([]);

const sortedCategorias = computed(() => sortItems(categorias.value))
const loading = ref(false);
const showFormModal = ref(false);
const showDeleteModal = ref(false);
const isEditing = ref(false);
const isSaving = ref(false);
const isDeleting = ref(false);
const idToDelete = ref(null);

const form = ref({ id: null, descripcion: '' });
const originalForm = ref({});

const fetchData = async () => {
  loading.value = true;
  try {
    categorias.value = await datosMaestrosService.getCategorias();
  } catch {
    toast.showToast({ message: 'Error al cargar las categorías.', type: 'danger' });
  } finally {
    loading.value = false;
  }
};

const openModal = (item = null) => {
  if (item) {
    isEditing.value = true;
    form.value = { ...item };
    originalForm.value = { ...item };
  } else {
    isEditing.value = false;
    form.value = { id: null, descripcion: '' };
  }
  showFormModal.value = true;
};

const save = async () => {
  if (!form.value.descripcion) {
    toast.showToast({ message: 'La descripción es obligatoria.', type: 'warning' });
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
      await datosMaestrosService.actualizarCategoria(form.value);
      toast.showToast({ message: 'Categoría actualizada correctamente.', type: 'success' });
    } else {
      await datosMaestrosService.crearCategoria({ descripcion: form.value.descripcion });
      toast.showToast({ message: 'Categoría creada correctamente.', type: 'success' });
    }
    showFormModal.value = false;
    fetchData();
  } catch {
    toast.showToast({ message: 'Error al guardar la categoría.', type: 'danger' });
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
    await datosMaestrosService.eliminarCategoria(idToDelete.value);
    toast.showToast({ message: 'Categoría eliminada correctamente.', type: 'success' });
    showDeleteModal.value = false;
    fetchData();
  } catch {
    toast.showToast({ message: 'Error al eliminar la categoría.', type: 'danger' });
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
