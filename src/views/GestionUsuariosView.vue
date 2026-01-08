<template>
  <div class="container-fluid p-4 bg-white min-vh-100 position-relative">
    
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
      <h1 class="h3 fw-bold mb-0 text-primary-custom">Gestión de Usuarios</h1>
      
      <div class="d-flex gap-2">
        <button @click="openModal()" class="btn btn-primary d-flex align-items-center px-4 shadow-sm">
          <i class="bi bi-person-plus-fill fs-5 me-2"></i> Nuevo Usuario
        </button>
        <button @click="$router.back()" class="btn btn-outline-secondary d-flex align-items-center px-4 shadow-sm" style="border-radius: 10px;">
          <i class="bi bi-arrow-left-circle fs-5 me-2"></i> Volver
        </button>
      </div>
    </div>

    <div class="card shadow-sm border-0 rounded-lg overflow-hidden position-relative" :style="{ minHeight: loading ? '300px' : 'auto' }">
      
      <div v-if="loading" class="loading-overlay-local d-flex flex-column align-items-center justify-content-center">
        <div class="spinner-border text-primary-custom" role="status" style="width: 3rem; height: 3rem;">
          <span class="visually-hidden">Cargando...</span>
        </div>
        <p class="mt-3 fw-bold text-primary-custom">Cargando Gestión de Usuarios...</p>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-light">
            <tr>
              <th class="ps-4 py-3 text-uppercase fs-xs fw-bold text-secondary">Nombre</th>
              <th class="py-3 text-uppercase fs-xs fw-bold text-secondary">Email</th>
              <th class="py-3 text-uppercase fs-xs fw-bold text-secondary">Rol</th>
              <th class="pe-4 py-3 text-uppercase fs-xs fw-bold text-secondary text-end">Acciones</th>
            </tr>
          </thead>
          <tbody class="bg-white">
            <tr v-for="user in usuarios" :key="user.id">
              <td class="ps-4 fw-medium text-dark">{{ user.nombre }}</td>
              <td class="text-muted">{{ user.email }}</td>
              <td>
                <span :class="roleBadgeClass(user.id_rol)" class="badge rounded-pill px-3">
                  {{ user.rol_nombre }}
                </span>
              </td>
              <td class="pe-4 text-end">
                <button @click="openPasswordModal(user)" class="btn btn-link link-warning p-1 me-2" title="Cambiar Contraseña">
                  <i class="bi bi-key-fill fs-4"></i>
                </button>
                <button @click="openModal(user)" class="btn btn-link link-secondary p-1 me-2" title="Editar Usuario">
                  <i class="bi bi-pencil-square fs-4"></i>
                </button>
                <button @click="prepareDelete(user.id)" class="btn btn-link link-danger p-1" title="Eliminar Usuario">
                  <i class="bi bi-trash3 fs-4"></i>
                </button>
              </td>
            </tr>
            <tr v-if="usuarios.length === 0 && !loading">
              <td colspan="4" class="text-center py-5 text-muted">
                No se encontraron usuarios registrados.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="showFormModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5)">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title fw-bold">
              <i class="bi me-2" :class="isEditing ? 'bi-person-gear' : 'bi-person-plus'"></i>
              {{ isEditing ? 'Editar Usuario' : 'Crear Nuevo Usuario' }}
            </h5>
            <button type="button" class="btn-close btn-close-white" @click="showFormModal = false"></button>
          </div>
          <form @submit.prevent="saveUser">
            <div class="modal-body p-4">
              <div class="mb-3">
                <label class="form-label fw-bold">Nombre Completo</label>
                <input v-model="form.nombre" type="text" required class="form-control" placeholder="Ej: Juan Pérez" />
              </div>
              <div class="mb-3">
                <label class="form-label fw-bold">Correo Electrónico</label>
                <input v-model="form.email" type="email" required class="form-control" placeholder="juan@ejemplo.com" />
              </div>
              <div v-if="!isEditing" class="mb-3">
                <label class="form-label fw-bold">Contraseña Inicial</label>
                <input v-model="form.contrasena" type="password" required class="form-control" />
              </div>
              <div class="mb-0">
                <label class="form-label fw-bold">Rol</label>
                <select v-model.number="form.id_rol" class="form-select" required>
                  <option :value="1">Administrador</option>
                  <option :value="4">Usuario Estándar</option>
                </select>
              </div>
            </div>
            <div class="modal-footer bg-light">
              <button @click="showFormModal = false" type="button" class="btn btn-secondary">Cancelar</button>
              <button type="submit" class="btn btn-primary px-4" :disabled="isSaving">
                <span v-if="isSaving" class="spinner-border spinner-border-sm me-2"></span>
                {{ isEditing ? 'Actualizar' : 'Registrar' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div v-if="showPasswordModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5)">
      <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header bg-warning text-dark">
            <h5 class="modal-title fw-bold"><i class="bi bi-key me-2"></i>Nueva Contraseña</h5>
            <button type="button" class="btn-close" @click="showPasswordModal = false"></button>
          </div>
          <form @submit.prevent="handleUpdatePassword">
            <div class="modal-body p-4 text-center">
              <p class="text-muted small mb-3">Usuario: <b>{{ selectedUser?.nombre }}</b></p>
              <input v-model="newPassword" type="password" required class="form-control text-center" placeholder="Mínimo 6 caracteres" />
            </div>
            <div class="modal-footer bg-light justify-content-center">
              <button @click="showPasswordModal = false" type="button" class="btn btn-link text-muted text-decoration-none">Cancelar</button>
              <button type="submit" class="btn btn-warning px-4" :disabled="isSavingPass">
                <span v-if="isSavingPass" class="spinner-border spinner-border-sm me-2"></span>
                Actualizar
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <ConfirmModal 
      v-model="showConfirmModal"
      title="Eliminar Usuario"
      message="¿Estás seguro de que deseas eliminar este usuario? Esta acción no se puede deshacer."
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
import usuariosService from '@/services/usuariosService';
import ConfirmModal from '@/components/ConfirmModal.vue';
import ToastNotification from '@/components/ToastNotification.vue';
import { useToastStore } from '@/stores/toastStore';

const toast = useToastStore();
const usuarios = ref([]);
const loading = ref(false);
const showFormModal = ref(false);
const showConfirmModal = ref(false);
const showPasswordModal = ref(false);
const isEditing = ref(false);
const isSaving = ref(false);
const isDeleting = ref(false);
const isSavingPass = ref(false);
const userToDelete = ref(null);
const selectedUser = ref(null);
const newPassword = ref('');
const form = ref({ id: null, nombre: '', email: '', contrasena: '', id_rol: 4 });

const fetchUsuarios = async () => {
  loading.value = true;
  try {
    usuarios.value = await usuariosService.getAll();
  } catch (error) {
    toast.showToast({ message: "Error al cargar usuarios", type: "danger" });
  } finally {
    loading.value = false;
  }
};

const openModal = (user = null) => {
  if (user) {
    isEditing.value = true;
    form.value = { 
      id: user.id, 
      nombre: user.nombre, 
      email: user.email, 
      id_rol: Number(user.id_rol),
      contrasena: '' 
    };
  } else {
    isEditing.value = false;
    form.value = { id: null, nombre: '', email: '', contrasena: '', id_rol: 4 };
  }
  showFormModal.value = true;
};

const saveUser = async () => {
  isSaving.value = true;
  try {
    if (isEditing.value) {
      await usuariosService.update(form.value.id, form.value.nombre, form.value.email, form.value.id_rol);
      toast.showToast({ message: "Usuario actualizado correctamente", type: "success" });
    } else {
      await usuariosService.create(form.value);
      toast.showToast({ message: "Usuario creado correctamente", type: "success" });
    }
    showFormModal.value = false;
    fetchUsuarios();
  } catch (error) {
    toast.showToast({ message: "Error al procesar la solicitud", type: "danger" });
  } finally {
    isSaving.value = false;
  }
};

const openPasswordModal = (user) => {
  selectedUser.value = user;
  newPassword.value = '';
  showPasswordModal.value = true;
};

const handleUpdatePassword = async () => {
  if (newPassword.value.length < 6) {
    toast.showToast({ message: "La contraseña debe tener al menos 6 caracteres", type: "warning" });
    return;
  }
  isSavingPass.value = true;
  try {
    await usuariosService.updatePassword(selectedUser.value.id, newPassword.value);
    toast.showToast({ message: "Contraseña actualizada con éxito", type: "success" });
    showPasswordModal.value = false;
  } catch (error) {
    toast.showToast({ message: "Error al actualizar la contraseña", type: "danger" });
  } finally {
    isSavingPass.value = false;
  }
};

const prepareDelete = (id) => {
  userToDelete.value = id;
  showConfirmModal.value = true;
};

const handleDelete = async () => {
  isDeleting.value = true;
  try {
    await usuariosService.delete(userToDelete.value);
    toast.showToast({ message: "Usuario eliminado con éxito", type: "success" });
    showConfirmModal.value = false;
    fetchUsuarios();
  } catch (error) {
    toast.showToast({ message: "No se pudo eliminar el usuario", type: "danger" });
  } finally {
    isDeleting.value = false;
  }
};

const roleBadgeClass = (roleId) => {
  return Number(roleId) === 1 ? 'bg-primary-soft text-primary-custom' : 'bg-info-soft text-info-custom';
};

onMounted(fetchUsuarios);
</script>

<style scoped>
.text-primary-custom { color: var(--color-primary); }
.fs-xs { font-size: 0.75rem; }
.bg-primary-soft { background-color: rgba(0, 85, 140, 0.1); }
.text-info-custom { color: var(--color-secondary); }
.bg-info-soft { background-color: rgba(22, 156, 159, 0.1); }
.btn-link { text-decoration: none; border: none; background: none; }

/* Overlay de carga local para la tarjeta */
.loading-overlay-local {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(255, 255, 255, 0.85);
  z-index: 10;
  display: flex;
}

.card {
  border-radius: 12px;
}

.table thead th {
  border-top: none;
}
</style>