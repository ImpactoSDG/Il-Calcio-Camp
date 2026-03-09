<template>
  <div class="container-fluid p-4 bg-white min-vh-100 position-relative animate-fade-in">

    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
      <div class="d-flex align-items-center">
        <button @click="$router.back()" class="btn-back-arrow me-2" title="Volver">
          <i class="bi bi-arrow-left"></i>
        </button>
        <h1 class="h5 fw-bold mb-0 text-secondary">CLIENTES</h1>
      </div>
      <button @click="openModal()" class="btn-primary-modern d-flex align-items-center">
        <i class="bi bi-plus-circle-fill fs-6 me-2"></i> Nuevo
      </button>
    </div>

    <div class="mb-3">
      <FuzzySearch v-model="searchQuery" placeholder="Buscar por nombre, dirección o condición IVA..." />
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
            <tr v-for="item in clientesFiltrados" :key="item.id">
              <td class="ps-4 text-muted fw-bold">{{ item.id }}</td>
              <td class="fw-medium text-dark">{{ item.nombre_cliente }}</td>
              <td class="text-muted">
                <span v-if="item.condicion_iva_descripcion" class="badge bg-primary-subtle text-primary-custom rounded-pill px-3">
                  {{ item.condicion_iva_descripcion }}
                </span>
                <span v-else class="text-muted">—</span>
              </td>
              <td class="text-muted">{{ item.provincia_nombre || '—' }}</td>
              <td class="text-muted">{{ item.direccion || '—' }}</td>
              <td class="pe-4 text-end">
                <button @click="openModal(item)" class="btn btn-link link-secondary p-1 me-2" title="Editar">
                  <i class="bi bi-pencil-square fs-4"></i>
                </button>
                <button @click="prepareDelete(item.id)" class="btn btn-link link-danger p-1" title="Eliminar">
                  <i class="bi bi-trash3 fs-4"></i>
                </button>
              </td>
            </tr>
            <tr v-if="clientesFiltrados.length === 0 && !loading">
              <td colspan="6" class="text-center py-5 text-muted">No hay clientes que coincidan con la búsqueda.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Formulario -->
    <Teleport to="body">
    <div v-if="showFormModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="bi me-2" :class="isEditing ? 'bi-pencil-square' : 'bi-plus-circle'"></i>
              {{ isEditing ? 'Editar Cliente' : 'Nuevo Cliente' }}
            </h5>
            <button type="button" class="btn-close" @click="showFormModal = false"></button>
          </div>
          <form @submit.prevent="save">
            <div class="modal-body">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label">ID <span class="text-danger">*</span></label>
                  <input
                    v-model.number="form.id"
                    type="number"
                    class="form-control"
                    :readonly="isEditing"
                    :class="{ 'bg-light text-muted': isEditing }"
                    required
                  />
                </div>
                <div class="col-md-8">
                  <label class="form-label">Nombre del Cliente <span class="text-danger">*</span></label>
                  <input v-model.trim="form.nombre_cliente" type="text" class="form-control" placeholder="Ej: Juan Pérez" required />
                </div>
                <div class="col-md-6">
                  <label class="form-label">Condición IVA (texto)</label>
                  <input v-model.trim="form.condicion_iva" type="text" class="form-control" placeholder="Ej: Responsable Inscripto" />
                </div>
                <div class="col-md-6">
                  <label class="form-label">Condición IVA (catálogo)</label>
                  <select v-model.number="form.id_condicion_iva_receptor" class="form-select">
                    <option :value="null">Sin seleccionar</option>
                    <option v-for="cond in condicionesIva" :key="cond.id" :value="cond.id">{{ cond.descripcion_condicion }}</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Provincia</label>
                  <select v-model.number="form.id_provinica" class="form-select">
                    <option :value="null">Sin seleccionar</option>
                    <option v-for="prov in provincias" :key="prov.id" :value="prov.id">{{ prov.provincia }}</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Dirección</label>
                  <input v-model.trim="form.direccion" type="text" class="form-control" placeholder="Ej: Av. Principal 123" />
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
      title="Eliminar Cliente"
      message="¿Estás seguro de eliminar este cliente? Esta acción puede afectar ventas y equipos asociados."
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
  { key: 'id',                        label: 'ID',            sortable: true,  thClass: 'ps-4 py-3 text-uppercase fs-xs fw-bold text-secondary', thStyle: 'width: 80px' },
  { key: 'nombre_cliente',            label: 'Nombre',        sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'condicion_iva_descripcion', label: 'Condición IVA', sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'provincia_nombre',          label: 'Provincia',     sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'direccion',                 label: 'Dirección',     sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'acciones',                  label: 'Acciones',      sortable: false, thClass: 'pe-4 py-3 text-uppercase fs-xs fw-bold text-secondary text-end' },
]

const clientes = ref([]);
const condicionesIva = ref([]);
const provincias = ref([]);
const loading = ref(false);
const searchQuery = ref('');
const showFormModal = ref(false);
const showDeleteModal = ref(false);
const isEditing = ref(false);
const isSaving = ref(false);
const isDeleting = ref(false);
const idToDelete = ref(null);

const emptyForm = () => ({ id: null, nombre_cliente: '', condicion_iva: '', id_condicion_iva_receptor: null, direccion: '', id_provinica: null });
const form = ref(emptyForm());
const originalForm = ref({});

const clientesFiltrados = computed(() => {
  let items = clientes.value;
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    items = items.filter(c =>
      c.nombre_cliente?.toLowerCase().includes(q) ||
      c.condicion_iva_descripcion?.toLowerCase().includes(q) ||
      c.direccion?.toLowerCase().includes(q)
    );
  }
  return sortItems(items);
});

const fetchData = async () => {
  loading.value = true;
  try {
    [clientes.value, condicionesIva.value, provincias.value] = await Promise.all([
      clientesService.getClientes(),
      datosMaestrosService.getCondicionesIva(),
      datosMaestrosService.getProvincias(),
    ]);
  } catch {
    toast.showToast({ message: 'Error al cargar los clientes.', type: 'danger' });
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
    form.value = emptyForm();
  }
  showFormModal.value = true;
};

const save = async () => {
  if (!form.value.id || !form.value.nombre_cliente) {
    toast.showToast({ message: 'ID y nombre del cliente son obligatorios.', type: 'warning' });
    return;
  }
  if (isEditing.value && JSON.stringify(form.value) === JSON.stringify(originalForm.value)) {
    toast.showToast({ message: 'No se detectaron cambios.', type: 'info' });
    showFormModal.value = false;
    return;
  }
  if (!isEditing.value) {
    const existe = clientes.value.some(c => Number(c.id) === Number(form.value.id));
    if (existe) {
      toast.showToast({ message: 'Ya existe un cliente con ese ID.', type: 'danger' });
      return;
    }
  }

  isSaving.value = true;
  try {
    if (isEditing.value) {
      await clientesService.actualizarCliente(form.value);
      toast.showToast({ message: 'Cliente actualizado correctamente.', type: 'success' });
    } else {
      await clientesService.crearCliente(form.value);
      toast.showToast({ message: 'Cliente creado correctamente.', type: 'success' });
    }
    showFormModal.value = false;
    fetchData();
  } catch {
    toast.showToast({ message: 'Error al guardar el cliente.', type: 'danger' });
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
    await clientesService.eliminarCliente(idToDelete.value);
    toast.showToast({ message: 'Cliente eliminado correctamente.', type: 'success' });
    showDeleteModal.value = false;
    fetchData();
  } catch {
    toast.showToast({ message: 'Error al eliminar el cliente.', type: 'danger' });
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
