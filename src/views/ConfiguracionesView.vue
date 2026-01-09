<template>
  <div class="container-fluid p-4 bg-white min-vh-100 position-relative animate-fade-in">
    
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
      <div class="d-flex align-items-center">
        <button @click="$router.back()" class="btn-back-arrow me-2" title="Volver">
          <i class="bi bi-arrow-left"></i>
        </button>
        <h1 class="h5 fw-bold mb-0 text-secondary">CONFIGURACIONES</h1>
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
          <thead class="bg-light">
            <tr>
              <th class="ps-4 py-3 text-uppercase fs-xs fw-bold text-secondary sticky-col-first">Clave</th>
              <th class="py-3 text-uppercase fs-xs fw-bold text-secondary border-start">Valor / Ruta</th>
              <th class="py-3 text-uppercase fs-xs fw-bold text-secondary border-start">Descripción</th>
              <th class="pe-4 py-3 text-uppercase fs-xs fw-bold text-secondary text-end border-start">Acciones</th>
            </tr>
          </thead>
          <tbody class="bg-white">
            <tr v-for="conf in configs" :key="conf.id">
              <td class="ps-4 fw-bold text-primary-custom sticky-col-first">
                <code>{{ conf.clave }}</code>
              </td>
              <td class="text-muted border-start px-3">
                <span class="d-inline-block text-truncate" style="max-width: 400px;" :title="conf.valor">
                  {{ conf.valor }}
                </span>
              </td>
              <td class="small text-secondary border-start px-3">{{ conf.descripcion || '-' }}</td>
              <td class="pe-4 text-end border-start">
                <button @click="openModal(conf)" class="btn btn-link link-secondary p-1 me-2" title="Editar">
                  <i class="bi bi-pencil-square fs-4"></i>
                </button>
                <button @click="prepareDelete(conf.id)" class="btn btn-link link-danger p-1" title="Eliminar">
                  <i class="bi bi-trash3 fs-4"></i>
                </button>
              </td>
            </tr>
            <tr v-if="configs.length === 0 && !loading">
              <td colspan="4" class="text-center py-5 text-muted">
                No se encontraron configuraciones registradas.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="showFormModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="bi me-2" :class="isEditing ? 'bi-pencil-square' : 'bi-plus-circle'"></i>
              {{ isEditing ? 'Editar Configuración' : 'Nueva Configuración' }}
            </h5>
            <button type="button" class="btn-close" @click="showFormModal = false"></button>
          </div>
          <form @submit.prevent="saveConfig">
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">Clave (Snake case sin espacios)</label>
                <input 
                  v-model.trim="form.clave" 
                  type="text" 
                  class="form-control" 
                  :disabled="isEditing"
                  placeholder="ej: ruta_servidor_logos"
                />
                <div class="form-text">Ejemplo: nombre_de_variable</div>
              </div>
              <div class="mb-3">
                <label class="form-label">Valor</label>
                <textarea v-model.trim="form.valor" class="form-control" rows="3" placeholder="Ruta o valor del parámetro"></textarea>
              </div>
              <div class="mb-0">
                <label class="form-label">Descripción / Nota</label>
                <input v-model.trim="form.descripcion" type="text" class="form-control" placeholder="¿Para qué se usa?" />
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
      v-model="showConfirmModal"
      title="Eliminar Configuración"
      message="¿Estás seguro de que deseas eliminar este parámetro global? Esta acción no se puede deshacer."
      confirmButtonText="Eliminar"
      variant="danger"
      :isLoading="isDeleting"
      @confirm="handleDelete"
    />

    <ToastNotification />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import configuracionService from '@/services/configuracionService';
import ConfirmModal from '@/components/ConfirmModal.vue';
import ToastNotification from '@/components/ToastNotification.vue';
import { useToastStore } from '@/stores/toastStore';

const toast = useToastStore();
const configs = ref([]);
const loading = ref(false);
const showFormModal = ref(false);
const showConfirmModal = ref(false);
const isEditing = ref(false);
const isSaving = ref(false);
const isDeleting = ref(false);
const configToDelete = ref(null);
const originalForm = ref({});
const form = ref({ id: null, clave: '', valor: '', descripcion: '' });

const fetchConfigs = async () => {
  loading.value = true;
  try {
    configs.value = await configuracionService.getAll();
  } catch (error) {
    toast.showToast({ message: "Error al cargar configuraciones", type: "danger" });
  } finally {
    loading.value = false;
  }
};

const openModal = (conf = null) => {
  if (conf) {
    isEditing.value = true;
    form.value = { ...conf };
    originalForm.value = { ...conf };
  } else {
    isEditing.value = false;
    form.value = { id: null, clave: '', valor: '', descripcion: '' };
  }
  showFormModal.value = true;
};

/**
 * Validaciones de Frontend
 */
const validateForm = () => {
  const { clave, valor } = form.value;

  if (!clave) {
    toast.showToast({ message: "La clave es obligatoria", type: "warning" });
    return false;
  }

  // Verificar duplicado local (solo en creación)
  if (!isEditing.value) {
    const exists = configs.value.some(c => c.clave.toLowerCase() === clave.toLowerCase());
    if (exists) {
      toast.showToast({ message: "Esta clave ya existe en el sistema", type: "danger" });
      return false;
    }
  }

  if (!valor) {
    toast.showToast({ message: "El campo Valor no puede estar vacío", type: "warning" });
    return false;
  }

  // Verificar si hubo cambios reales en edición
  if (isEditing.value) {
    const hasChanged = JSON.stringify(form.value) !== JSON.stringify(originalForm.value);
    if (!hasChanged) {
      toast.showToast({ message: "No se detectaron cambios para actualizar", type: "info" });
      showFormModal.value = false;
      return false;
    }
  }

  return true;
};

const saveConfig = async () => {
  if (!validateForm()) return;

  isSaving.value = true;
  try {
    if (isEditing.value) {
      await configuracionService.update(form.value);
      toast.showToast({ message: "Configuración actualizada con éxito", type: "success" });
    } else {
      await configuracionService.create(form.value);
      toast.showToast({ message: "Parámetro global creado", type: "success" });
    }
    showFormModal.value = false;
    fetchConfigs();
  } catch (error) {
    toast.showToast({ message: "Error al procesar la solicitud en el servidor", type: "danger" });
  } finally {
    isSaving.value = false;
  }
};

const prepareDelete = (id) => {
  configToDelete.value = id;
  showConfirmModal.value = true;
};

const handleDelete = async () => {
  isDeleting.value = true;
  try {
    await configuracionService.delete(configToDelete.value);
    toast.showToast({ message: "Configuración eliminada correctamente", type: "success" });
    showConfirmModal.value = false;
    fetchConfigs();
  } catch (error) {
    toast.showToast({ message: "Error al eliminar el parámetro", type: "danger" });
  } finally {
    isDeleting.value = false;
  }
};

onMounted(fetchConfigs);
</script>

<style scoped>
.fs-xs { font-size: 0.75rem; }
.btn-link { text-decoration: none; }
.loading-overlay-local {
  position: absolute;
  top: 0; left: 0; width: 100%; height: 100%;
  background-color: rgba(255, 255, 255, 0.85);
  z-index: 10;
  display: flex;
}
code {
  background-color: #f8f9fa;
  padding: 0.2rem 0.4rem;
  border-radius: 4px;
  color: #d63384;
}
</style>