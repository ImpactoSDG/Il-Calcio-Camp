<template>
  <div class="container-fluid p-4 bg-white min-vh-100 position-relative animate-fade-in">

    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
      <div class="d-flex align-items-center">
        <button @click="$router.back()" class="btn-back-arrow me-2" title="Volver">
          <i class="bi bi-arrow-left"></i>
        </button>
        <h1 class="h5 fw-bold mb-0 text-secondary">EQUIPOS</h1>
      </div>
      <div class="d-flex gap-2">
        <button @click="openModal()" class="btn-primary-modern d-flex align-items-center">
          <i class="bi bi-plus-circle-fill fs-6 me-2"></i> Nuevo
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
            <tr v-for="item in sortedEquipos" :key="item.id">
              <td class="ps-4 text-muted fw-bold">{{ item.id }}</td>
              <td class="fw-medium text-dark">{{ item.nombre }}</td>
              <td class="text-muted">
                <span class="badge bg-primary-subtle text-primary-custom rounded-pill px-3">{{ item.disciplina }}</span>
              </td>
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
            <tr v-if="equipos.length === 0 && !loading">
              <td colspan="5" class="text-center py-5 text-muted">
                No hay equipos registrados.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Formulario -->
    <div v-if="showFormModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="bi me-2" :class="isEditing ? 'bi-pencil-square' : 'bi-plus-circle'"></i>
              {{ isEditing ? 'Editar Equipo' : 'Nuevo Equipo' }}
            </h5>
            <button type="button" class="btn-close" @click="showFormModal = false"></button>
          </div>
          <form @submit.prevent="save">
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">ID</label>
                <input
                  v-model.number="form.id"
                  type="number"
                  class="form-control"
                  placeholder="Ej: 1"
                  :readonly="isEditing"
                  :class="{ 'bg-light text-muted': isEditing }"
                  required
                />
              </div>
              <div class="mb-3">
                <label class="form-label">Nombre del Equipo</label>
                <input v-model.trim="form.nombre" type="text" class="form-control" placeholder="Ej: Sub-15 Fútbol" required />
              </div>
              <div class="mb-3">
                <label class="form-label">Disciplina</label>
                <input v-model.trim="form.disciplina" type="text" class="form-control" placeholder="Ej: Fútbol" required />
              </div>
              <div class="mb-0">
                <div class="form-check form-switch ps-4">
                  <input v-model="form.activo" class="form-check-input" type="checkbox" role="switch" id="chkActivoEquipo" />
                  <label class="form-check-label fw-semibold ms-2" for="chkActivoEquipo">
                    {{ form.activo ? 'Activo' : 'Inactivo' }}
                  </label>
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
      title="Eliminar Equipo"
      message="¿Estás seguro de eliminar este equipo? Puede afectar a clientes y ventas asociadas."
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
  { key: 'id',         label: 'ID',         sortable: true,  thClass: 'ps-4 py-3 text-uppercase fs-xs fw-bold text-secondary', thStyle: 'width: 80px' },
  { key: 'nombre',     label: 'Nombre',     sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'disciplina', label: 'Disciplina', sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'activo',     label: 'Estado',     sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary text-center', thStyle: 'width: 120px' },
  { key: 'acciones',   label: 'Acciones',   sortable: false, thClass: 'pe-4 py-3 text-uppercase fs-xs fw-bold text-secondary text-end' },
]

const equipos = ref([]);

const sortedEquipos = computed(() => sortItems(equipos.value))
const loading = ref(false);
const showFormModal = ref(false);
const showDeleteModal = ref(false);
const isEditing = ref(false);
const isSaving = ref(false);
const isDeleting = ref(false);
const idToDelete = ref(null);

const form = ref({ id: null, nombre: '', disciplina: '', activo: true });
const originalForm = ref({});

const fetchData = async () => {
  loading.value = true;
  try {
    equipos.value = await datosMaestrosService.getEquipos();
  } catch {
    toast.showToast({ message: 'Error al cargar los equipos.', type: 'danger' });
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
    form.value = { id: null, nombre: '', disciplina: '', activo: true };
  }
  showFormModal.value = true;
};

const save = async () => {
  if (!form.value.id || !form.value.nombre || !form.value.disciplina) {
    toast.showToast({ message: 'ID, nombre y disciplina son obligatorios.', type: 'warning' });
    return;
  }
  if (isEditing.value && JSON.stringify(form.value) === JSON.stringify(originalForm.value)) {
    toast.showToast({ message: 'No se detectaron cambios.', type: 'info' });
    showFormModal.value = false;
    return;
  }
  if (!isEditing.value) {
    const existe = equipos.value.some(e => Number(e.id) === Number(form.value.id));
    if (existe) {
      toast.showToast({ message: 'Ya existe un equipo con ese ID.', type: 'danger' });
      return;
    }
  }

  isSaving.value = true;
  try {
    if (isEditing.value) {
      await datosMaestrosService.actualizarEquipo(form.value);
      toast.showToast({ message: 'Equipo actualizado correctamente.', type: 'success' });
    } else {
      await datosMaestrosService.crearEquipo(form.value);
      toast.showToast({ message: 'Equipo creado correctamente.', type: 'success' });
    }
    showFormModal.value = false;
    fetchData();
  } catch {
    toast.showToast({ message: 'Error al guardar el equipo.', type: 'danger' });
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
    await datosMaestrosService.eliminarEquipo(idToDelete.value);
    toast.showToast({ message: 'Equipo eliminado correctamente.', type: 'success' });
    showDeleteModal.value = false;
    fetchData();
  } catch {
    toast.showToast({ message: 'Error al eliminar el equipo.', type: 'danger' });
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
