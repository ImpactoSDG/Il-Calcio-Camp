<template>
  <div class="container-fluid p-4 bg-white min-vh-100 position-relative">
    
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
      <div class="d-flex align-items-center">
        <button @click="$router.back()" class="btn-back-arrow me-2" title="Volver">
          <i class="bi bi-arrow-left"></i>
        </button>
        <h1 class="h5 fw-bold mb-0 text-secondary">GESTIÓN DE USUARIOS</h1>
      </div>
      
      <div class="d-flex gap-2">
        <button @click="openModal()" class="btn-primary-modern d-flex align-items-center">
          <i class="bi bi-person-plus-fill fs-6 me-2"></i> Nuevo
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
            <tr v-for="user in sortedUsuarios" :key="user.id">
              <td class="ps-4 fw-medium text-dark">{{ user.nombre }}</td>
              <td class="text-muted">{{ user.email }}</td>
              <td>
                <span :class="roleBadgeClass(user.id_rol)" class="badge rounded-pill px-3">
                  {{ user.rol_nombre }}
                </span>
              </td>
              <td class="pe-4 text-end">
                <!-- Cambiar contraseña: Admin puede a todos, otros solo a no-admins o a sí mismos -->
                <button 
                  v-if="userStore.userRole === 'admin' || user.rol_nombre?.toLowerCase() !== 'admin' || userStore.user?.id === user.id"
                  @click="openPasswordModal(user)" 
                  class="btn btn-link link-warning p-1 me-2" 
                  title="Cambiar Contraseña"
                >
                  <i class="bi bi-key-fill fs-4"></i>
                </button>
                
                <!-- Editar: Admin puede a todos, otros solo a no-admins o a sí mismos -->
                <button 
                  v-if="userStore.userRole === 'admin' || user.rol_nombre?.toLowerCase() !== 'admin' || userStore.user?.id === user.id"
                  @click="openModal(user)" 
                  class="btn btn-link link-secondary p-1 me-2" 
                  title="Editar Usuario"
                >
                  <i class="bi bi-pencil-square fs-4"></i>
                </button>

                <!-- Eliminar: Admin puede a todos, otros solo a no-admins -->
                <button 
                  v-if="userStore.userRole === 'admin' || user.rol_nombre?.toLowerCase() !== 'admin'"
                  @click="prepareDelete(user.id)" 
                  class="btn btn-link link-danger p-1" 
                  title="Eliminar Usuario"
                >
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

    <Teleport to="body">
    <div v-if="showFormModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="bi me-2" :class="isEditing ? 'bi-person-gear' : 'bi-person-plus'"></i>
              {{ isEditing ? 'Editar Usuario' : 'Crear Nuevo Usuario' }}
            </h5>
            <button type="button" class="btn-close" @click="showFormModal = false"></button>
          </div>
          <form @submit.prevent="saveUser">
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">Nombre Completo</label>
                <input v-model.trim="form.nombre" type="text" class="form-control" placeholder="Ej: Juan Pérez" />
              </div>
              <div class="mb-3">
                <label class="form-label">Correo Electrónico</label>
                <input v-model.trim="form.email" type="email" class="form-control" placeholder="juan@ejemplo.com" />
              </div>
              <div v-if="!isEditing" class="mb-3">
                <label class="form-label">Contraseña Inicial</label>
                <input v-model.trim="form.contrasena" type="password" class="form-control" placeholder="Contraseña segura" />
              </div>
              <div class="mb-0">
                <label class="form-label fw-bold">Rol</label>
                <select 
                  v-model.number="form.id_rol" 
                  class="form-select" 
                  :disabled="form._lockRole"
                >
                  <option disabled value="">Seleccione un rol</option>
                  <option v-for="rol in filteredRoles" :key="rol.id" :value="rol.id">
                    {{ rol.nombre }}
                  </option>
                </select>
                <small v-if="form._lockRole" class="text-muted mt-1 d-block">
                  No tienes permisos para modificar el rol de un administrador.
                </small>
              </div>
            </div>
            <div class="modal-footer">
              <button @click="showFormModal = false" type="button" class="btn btn-light px-4">Cancelar</button>
              <button type="submit" class="btn btn-primary-modern px-4" :disabled="isSaving">
                <span v-if="isSaving" class="spinner-border spinner-border-sm me-2"></span>
                {{ isEditing ? 'Actualizar' : 'Registrar' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
    </Teleport>

    <Teleport to="body">
    <div v-if="showPasswordModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="bi bi-shield-lock-fill me-2"></i> Actualizar Seguridad
            </h5>
            <button type="button" class="btn-close" @click="showPasswordModal = false"></button>
          </div>
          <form @submit.prevent="handleUpdatePassword">
            <div class="modal-body">
              <div class="alert alert-info py-2 px-3 mb-3 border-0 bg-primary-soft">
                <p class="mb-0 small text-primary-custom">
                  Actualizando accesos para: <b>{{ selectedUser?.nombre }}</b>
                </p>
              </div>
              <div class="mb-0">
                <label class="form-label">Nueva Contraseña</label>
                <input 
                  v-model.trim="newPassword" 
                  type="password" 
                  class="form-control" 
                  placeholder="Ingrese nueva contraseña" 
                  required
                />
              </div>
            </div>
            <div class="modal-footer">
              <button @click="showPasswordModal = false" type="button" class="btn btn-light px-4">Cancelar</button>
              <button type="submit" class="btn btn-primary-modern px-4" :disabled="isSavingPass">
                <span v-if="isSavingPass" class="spinner-border spinner-border-sm me-2"></span>
                Actualizar
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
    </Teleport>

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
import { ref, onMounted, computed } from 'vue';
import usuariosService from '@/services/usuarios/usuariosService';
import ConfirmModal from '@/components/ConfirmModal.vue';
import SortableTableHead, { useSorting } from '@/components/SortableTableHead.vue';
import ToastNotification from '@/components/ToastNotification.vue';
import { useToastStore } from '@/stores/toastStore';
import { useUserStore } from '@/stores/userStore';

const toast = useToastStore();
const userStore = useUserStore();

const { sortKey, sortDir, handleSort, sortItems } = useSorting()

const columns = [
  { key: 'nombre',    label: 'Nombre',   sortable: true,  thClass: 'ps-4 py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'email',     label: 'Email',    sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'rol_nombre',label: 'Rol',      sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'acciones',  label: 'Acciones', sortable: false, thClass: 'pe-4 py-3 text-uppercase fs-xs fw-bold text-secondary text-end' },
]

const usuarios = ref([]);

const sortedUsuarios = computed(() => sortItems(usuarios.value))
const roles = ref([]);
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

// Para comparar cambios en edición
const originalForm = ref({});
const form = ref({ id: null, nombre: '', email: '', contrasena: '', id_rol: '' });

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

const fetchRoles = async () => {
  try {
    roles.value = await usuariosService.getRoles();
  } catch (error) {
    console.error("Error al cargar roles", error);
  }
};

const filteredRoles = computed(() => {
  if (userStore.userRole === 'admin') {
    return roles.value;
  }
  return roles.value.filter(role => role.nombre.toLowerCase() !== 'admin');
});

const openModal = (user = null) => {
  if (user) {
    isEditing.value = true;
    const userData = { 
      id: user.id, 
      nombre: user.nombre, 
      email: user.email, 
      id_rol: Number(user.id_rol),
      contrasena: '' 
    };
    // Si el usuario a editar es admin y el usuario logueado no es admin, bloquear el cambio de rol
    if (user.rol_nombre && user.rol_nombre.toLowerCase() === 'admin' && userStore.userRole !== 'admin') {
      form.value = { ...userData, _lockRole: true };
    } else {
      form.value = { ...userData, _lockRole: false };
    }
    originalForm.value = { ...userData };
  } else {
    isEditing.value = false;
    form.value = { id: null, nombre: '', email: '', contrasena: '', id_rol: '', _lockRole: false };
  }
  showFormModal.value = true;
};

const validateUserForm = () => {
  const { nombre, email, contrasena, id_rol } = form.value;

  if (!nombre || nombre.length < 3) {
    toast.showToast({ message: "El nombre es obligatorio (min. 3 caracteres)", type: "warning" });
    return false;
  }

  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!email || !emailRegex.test(email)) {
    toast.showToast({ message: "Ingrese un correo electrónico válido", type: "warning" });
    return false;
  }

  if (!isEditing.value && !contrasena) {
    toast.showToast({ message: "La contraseña es obligatoria", type: "warning" });
    return false;
  }

  if (!id_rol) {
    toast.showToast({ message: "Debe seleccionar un rol para el usuario", type: "warning" });
    return false;
  }

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

const saveUser = async () => {
  if (!validateUserForm()) return;

  isSaving.value = true;
  try {
    if (isEditing.value) {
      await usuariosService.update({
        id: form.value.id,
        nombre: form.value.nombre,
        email: form.value.email,
        id_rol: form.value.id_rol
      });
      toast.showToast({ message: "Usuario actualizado correctamente", type: "success" });
    } else {
      // Verificación de email duplicado local (opcional, el backend lo hace igual)
      if (usuarios.value.some(u => u.email === form.value.email)) {
        toast.showToast({ message: "Este correo electrónico ya está registrado", type: "danger" });
        isSaving.value = false;
        return;
      }
      await usuariosService.create(form.value);
      toast.showToast({ message: "Usuario creado correctamente", type: "success" });
    }
    showFormModal.value = false;
    fetchUsuarios();
  } catch (error) {
    const errorMsg = error.response?.data?.message || "Error al procesar la solicitud";
    toast.showToast({ message: errorMsg, type: "danger" });
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
  if (!newPassword.value) {
    toast.showToast({ message: "Debe ingresar una nueva contraseña", type: "warning" });
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

onMounted(() => {
  fetchUsuarios();
  fetchRoles();
});
</script>

<style scoped>
.fs-xs { font-size: 0.75rem; }
.btn-link { text-decoration: none; border: none; background: none; }

.loading-overlay-local {
  position: absolute;
  top: 0; left: 0; width: 100%; height: 100%;
  background-color: rgba(255, 255, 255, 0.85);
  z-index: 10;
  display: flex;
}

.card { border-radius: 12px; }
.table thead th { border-top: none; }
</style>