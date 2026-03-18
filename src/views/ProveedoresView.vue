<template>
  <div class="container-fluid p-4 bg-white min-vh-100 position-relative animate-fade-in">

    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
      <div class="d-flex align-items-center">
        <button @click="$router.back()" class="btn-back-arrow me-2" title="Volver">
          <i class="bi bi-arrow-left"></i>
        </button>
        <h1 class="h5 fw-bold mb-0 text-secondary">PROVEEDORES</h1>
      </div>
      <button @click="openModal()" class="btn-primary-modern d-flex align-items-center">
        <i class="bi bi-plus-circle-fill fs-6 me-2"></i> Nuevo
      </button>
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
            <tr v-for="item in sortedProveedores" :key="item.id_proveedor">
              <td class="ps-4 fw-medium text-dark">{{ item.nombre }} {{ item.apellido }}</td>
              <td class="text-muted">{{ item.nombre_fantasia || '—' }}</td>
              <td class="text-muted">{{ item.telefono || '—' }}</td>
              <td class="text-muted">{{ item.direccion || '—' }}</td>
              <td class="text-center">
                <span :class="item.activo ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary'" class="badge rounded-pill px-3">
                  {{ Number(item.activo) ? 'Activo' : 'Inactivo' }}
                </span>
              </td>
              <td class="pe-4 text-end">
                <button @click="openModal(item)" class="btn btn-link link-secondary p-1 me-2" title="Editar">
                  <i class="bi bi-pencil-square fs-4"></i>
                </button>
                <button @click="prepareDelete(item.id_proveedor)" class="btn btn-link link-danger p-1" title="Desactivar">
                  <i class="bi bi-trash3 fs-4"></i>
                </button>
              </td>
            </tr>
            <tr v-if="proveedores.length === 0 && !loading">
              <td colspan="7" class="text-center py-5 text-muted">No hay proveedores registrados.</td>
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
                {{ isEditing ? 'Editar Proveedor' : 'Nuevo Proveedor' }}
              </h5>
              <button type="button" class="btn-close" @click="showFormModal = false"></button>
            </div>
            <form @submit.prevent="save">
              <div class="modal-body">
                <div class="row g-3">
                  <div class="col-6">
                    <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                    <input v-model.trim="form.nombre" type="text" class="form-control" placeholder="Ej: Juan" required />
                  </div>
                  <div class="col-6">
                    <label class="form-label fw-semibold">Apellido</label>
                    <input v-model.trim="form.apellido" type="text" class="form-control" placeholder="Ej: García" />
                  </div>
                  <div class="col-12">
                    <label class="form-label fw-semibold">Nombre Fantasía</label>
                    <input v-model.trim="form.nombre_fantasia" type="text" class="form-control" placeholder="Ej: Distribuidora García" />
                  </div>
                  <div class="col-6">
                    <label class="form-label fw-semibold">Teléfono</label>
                    <input v-model.trim="form.telefono" type="text" class="form-control" placeholder="Ej: 351-1234567" />
                  </div>
                  <div class="col-6">
                    <label class="form-label fw-semibold">Dirección</label>
                    <input v-model.trim="form.direccion" type="text" class="form-control" placeholder="Ej: Av. Colón 1234" />
                  </div>
                  <div class="col-12" v-if="isEditing">
                    <div class="form-check form-switch ps-4">
                      <input v-model="form.activo" class="form-check-input" type="checkbox" role="switch" id="chkActivoProveedor" />
                      <label class="form-check-label fw-semibold ms-2" for="chkActivoProveedor">
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
      title="Desactivar Proveedor"
      message="¿Estás seguro de desactivar este proveedor? No se eliminará físicamente."
      confirm-button-text="Desactivar"
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
import proveedoresService from '@/services/proveedoresService';
import { useToastStore } from '@/stores/toastStore';

const toast = useToastStore();
const { sortKey, sortDir, handleSort, sortItems } = useSorting();

const columns = [
  { key: 'nombre',          label: 'Nombre',          sortable: true,  thClass: 'ps-4 py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'nombre_fantasia', label: 'Nombre Fantasía', sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'telefono',        label: 'Teléfono',        sortable: false, thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'direccion',       label: 'Dirección',       sortable: false, thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'activo',          label: 'Estado',          sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary text-center', thStyle: 'width: 110px' },
  { key: 'acciones',        label: 'Acciones',        sortable: false, thClass: 'pe-4 py-3 text-uppercase fs-xs fw-bold text-secondary text-end' },
];

const proveedores = ref([]);
const sortedProveedores = computed(() => sortItems(proveedores.value));
const loading = ref(false);
const showFormModal = ref(false);
const showDeleteModal = ref(false);
const isEditing = ref(false);
const isSaving = ref(false);
const isDeleting = ref(false);
const idToDelete = ref(null);

const emptyForm = () => ({ id_proveedor: null, nombre: '', apellido: '', nombre_fantasia: '', telefono: '', direccion: '', activo: true });
const form = ref(emptyForm());
const originalForm = ref({});

const fetchData = async () => {
  loading.value = true;
  try {
    proveedores.value = await proveedoresService.getProveedores();
  } catch {
    toast.showToast({ message: 'Error al cargar proveedores.', type: 'danger' });
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
  if (!form.value.nombre.trim()) {
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
      await proveedoresService.actualizarProveedor(form.value);
      toast.showToast({ message: 'Proveedor actualizado correctamente.', type: 'success' });
    } else {
      await proveedoresService.crearProveedor(form.value);
      toast.showToast({ message: 'Proveedor creado correctamente.', type: 'success' });
    }
    showFormModal.value = false;
    fetchData();
  } catch {
    toast.showToast({ message: 'Error al guardar el proveedor.', type: 'danger' });
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
    await proveedoresService.eliminarProveedor(idToDelete.value);
    toast.showToast({ message: 'Proveedor desactivado correctamente.', type: 'success' });
    showDeleteModal.value = false;
    fetchData();
  } catch {
    toast.showToast({ message: 'Error al desactivar el proveedor.', type: 'danger' });
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
  inset: 0;
  background: rgba(255, 255, 255, 0.85);
  z-index: 10;
}
</style>
