<template>
  <div class="container-fluid p-4 bg-white min-vh-100 position-relative animate-fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
      <div class="d-flex align-items-center">
        <button @click="$router.back()" class="btn-back-arrow me-2" title="Volver">
          <i class="bi bi-arrow-left"></i>
        </button>
        <h1 class="h5 fw-bold mb-0 text-secondary">CANCHAS</h1>
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
      <FuzzySearch v-model="searchQuery" placeholder="Buscar por nombre o descripcion..." />
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
            <tr v-for="item in canchasFiltradas" :key="item.id">
              <td class="ps-4 fw-medium text-dark">{{ item.nombre }}</td>
              <td class="text-muted">{{ item.disciplina_nombre || '-' }}</td>
              <td class="text-muted">{{ item.descripcion || '-' }}</td>
              <td class="text-center">
                <span :class="item.activo ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary'" class="badge rounded-pill px-3">
                  {{ item.activo ? 'Activa' : 'Inactiva' }}
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
            <tr v-if="canchasFiltradas.length === 0 && !loading">
              <td colspan="5" class="text-center py-5 text-muted">
                No hay canchas que coincidan con la busqueda.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <Teleport to="body">
      <div v-if="showFormModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">
                <i class="bi me-2" :class="isEditing ? 'bi-pencil-square' : 'bi-plus-circle'"></i>
                {{ isEditing ? 'Editar Cancha' : 'Nueva Cancha' }}
              </h5>
              <button type="button" class="btn-close" @click="showFormModal = false"></button>
            </div>
            <form @submit.prevent="save">
              <div class="modal-body">
                <div class="row g-3">
                  <div class="col-12">
                    <label class="form-label">Disciplina</label>
                    <select v-model.number="form.id_disciplina" class="form-select" required>
                      <option :value="null" disabled>Seleccionar disciplina</option>
                      <option v-for="disciplina in disciplinas" :key="disciplina.id" :value="Number(disciplina.id)">
                        {{ disciplina.nombre }}
                      </option>
                    </select>
                  </div>
                  <div class="col-12">
                    <label class="form-label">Nombre</label>
                    <input v-model.trim="form.nombre" type="text" class="form-control" placeholder="Ej: Cancha 1" required />
                  </div>
                  <div class="col-12">
                    <label class="form-label">Descripcion</label>
                    <textarea v-model.trim="form.descripcion" rows="3" class="form-control" placeholder="Ej: Futbol 5 techada"></textarea>
                  </div>
                  <div class="col-12">
                    <div class="form-check form-switch ps-4">
                      <input v-model="form.activo" class="form-check-input" type="checkbox" role="switch" id="chkActivoCancha" />
                      <label class="form-check-label fw-semibold ms-2" for="chkActivoCancha">
                        {{ form.activo ? 'Activa' : 'Inactiva' }}
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
      title="Eliminar Cancha"
      message="¿Estas seguro de eliminar esta cancha? Si esta relacionada con eventos, la base puede impedir esta accion."
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
  { key: 'nombre', label: 'Nombre', sortable: true, thClass: 'ps-4 py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'disciplina_nombre', label: 'Disciplina', sortable: true, thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'descripcion', label: 'Descripcion', sortable: true, thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'activo', label: 'Estado', sortable: true, thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary text-center', thStyle: 'width: 120px' },
  { key: 'acciones', label: 'Acciones', sortable: false, thClass: 'pe-4 py-3 text-uppercase fs-xs fw-bold text-secondary text-end' },
];

const canchas = ref([]);
const disciplinas = ref([]);
const searchQuery = ref('');
const canchasFiltradas = computed(() => {
  let items = canchas.value;
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    items = items.filter(item =>
      item.nombre?.toLowerCase().includes(query) ||
      item.disciplina_nombre?.toLowerCase().includes(query) ||
      item.descripcion?.toLowerCase().includes(query)
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
  id_disciplina: null,
  nombre: '',
  descripcion: '',
  activo: true,
});
const originalForm = ref({});

const fetchData = async () => {
  loading.value = true;
  try {
    const [data, disciplinasData] = await Promise.all([
      datosMaestrosService.getCanchas(),
      datosMaestrosService.getDisciplinas(),
    ]);

    canchas.value = data.map(item => ({
      ...item,
      id_disciplina: item.id_disciplina ? Number(item.id_disciplina) : null,
      activo: Boolean(Number(item.activo)),
    }));
    disciplinas.value = disciplinasData;
  } catch {
    toast.showToast({ message: 'Error al cargar las canchas.', type: 'danger' });
  } finally {
    loading.value = false;
  }
};

const openModal = (item = null) => {
  if (item) {
    isEditing.value = true;
    form.value = {
      ...item,
      id_disciplina: item.id_disciplina ? Number(item.id_disciplina) : null,
      descripcion: item.descripcion ?? '',
      activo: Boolean(Number(item.activo)),
    };
    originalForm.value = { ...form.value };
  } else {
    isEditing.value = false;
    form.value = {
      id: null,
      id_disciplina: null,
      nombre: '',
      descripcion: '',
      activo: true,
    };
  }
  showFormModal.value = true;
};

const buildPayload = () => ({
  ...(isEditing.value ? { id: form.value.id } : {}),
  id_disciplina: Number(form.value.id_disciplina),
  nombre: form.value.nombre.trim(),
  descripcion: form.value.descripcion?.trim() || null,
  activo: Boolean(form.value.activo),
});

const save = async () => {
  if (!form.value.nombre?.trim()) {
    toast.showToast({ message: 'El nombre es obligatorio.', type: 'warning' });
    return;
  }
  if (!form.value.id_disciplina) {
    toast.showToast({ message: 'La disciplina es obligatoria.', type: 'warning' });
    return;
  }

  if (isEditing.value && JSON.stringify(buildPayload()) === JSON.stringify({
    id: originalForm.value.id,
    id_disciplina: Number(originalForm.value.id_disciplina),
    nombre: originalForm.value.nombre,
    descripcion: originalForm.value.descripcion || null,
    activo: Boolean(originalForm.value.activo),
  })) {
    toast.showToast({ message: 'No se detectaron cambios.', type: 'info' });
    showFormModal.value = false;
    return;
  }

  isSaving.value = true;
  try {
    if (isEditing.value) {
      await datosMaestrosService.actualizarCancha(buildPayload());
      toast.showToast({ message: 'Cancha actualizada correctamente.', type: 'success' });
    } else {
      await datosMaestrosService.crearCancha(buildPayload());
      toast.showToast({ message: 'Cancha creada correctamente.', type: 'success' });
    }
    showFormModal.value = false;
    fetchData();
  } catch {
    toast.showToast({ message: 'Error al guardar la cancha.', type: 'danger' });
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
    await datosMaestrosService.eliminarCancha(idToDelete.value);
    toast.showToast({ message: 'Cancha eliminada correctamente.', type: 'success' });
    showDeleteModal.value = false;
    fetchData();
  } catch {
    toast.showToast({ message: 'Error al eliminar la cancha.', type: 'danger' });
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
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(255, 255, 255, 0.85);
  z-index: 10;
}
</style>
