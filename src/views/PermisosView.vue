<template>
  <div class="container-fluid p-4 bg-white min-vh-100">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
      <div>
        <h1 class="h3 fw-bold mb-0 text-primary-custom">Matriz de Permisos</h1>
      </div>
      
      <div class="d-flex gap-2">
        <router-link to="/menu" class="btn btn-outline-secondary d-flex align-items-center px-4">
          <i class="bi bi-arrow-left-circle fs-5 me-2"></i> Volver
        </router-link>
      </div>
    </div>

    <div class="card shadow-sm border-0 rounded-lg overflow-hidden">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-light">
            <tr>
              <th class="ps-4 py-3 text-uppercase fs-xs fw-bold text-secondary sticky-col-first">Módulo / Funcionalidad</th>
              <th class="py-3 text-uppercase fs-xs fw-bold text-secondary border-start text-center" style="width: 100px;">Orden</th>
              <th v-for="user in data.usuarios" :key="user.id" class="text-center py-3 text-uppercase fs-xs fw-bold text-secondary border-start user-th">
                <div class="px-2">
                  <span class="d-block text-dark">{{ user.nombre.split(' ')[0] }}</span>
                  <span class="badge bg-primary-soft text-primary-custom fw-normal" style="font-size: 0.6rem;">{{ user.rol_nombre }}</span>
                </div>
              </th>
            </tr>
          </thead>
          <tbody class="bg-white">
            <tr v-for="modulo in data.modulos" :key="modulo.id" :class="{'bg-parent': !modulo.id_padre}">
              <td class="ps-4 py-3 sticky-col-first">
                <div class="d-flex align-items-center">
                  <div v-if="!modulo.id_padre" class="indicator-dot me-2"></div>
                  <i v-else class="bi bi-arrow-return-right me-2 text-muted opacity-50 ms-3"></i>
                  <span :class="{'fw-bold text-dark': !modulo.id_padre, 'text-secondary': modulo.id_padre}">
                    {{ modulo.nombre }}
                  </span>
                </div>
              </td>

              <td class="text-center border-start border-light">
                <span class="text-muted small fw-bold">{{ modulo.orden_visualizacion || '-' }}</span>
              </td>

              <td v-for="user in data.usuarios" :key="user.id" class="text-center border-start border-light">
                <div class="form-check form-switch d-inline-block">
                  <input 
                    class="form-check-input custom-switch" 
                    type="checkbox" 
                    :checked="estaAsignado(user.id, modulo.id)"
                    @change="handleToggle(user.id, modulo.id, $event)"
                  >
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <ToastNotification />
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import permisosService from '@/services/permisosService';
import ToastNotification from '@/components/ToastNotification.vue';
import { useToastStore } from '@/stores/toastStore';
import { useUserStore } from '@/stores/userStore';

const toast = useToastStore();
const userStore = useUserStore();
const loading = ref(false);
const data = reactive({ usuarios: [], modulos: [], permisos: [] });

const cargarDatos = async () => {
  loading.value = true;
  try {
    const res = await permisosService.getMatrizPermisos();
    data.usuarios = res.usuarios || [];
    data.modulos = res.modulos || [];
    data.permisos = res.permisos || [];
  } catch (e) {
    toast.showToast({ message: "Error al sincronizar datos", type: "danger" });
  } finally {
    loading.value = false;
  }
};

const estaAsignado = (userId, moduloId) => data.permisos.includes(`${userId}_${moduloId}`);

const handleToggle = async (userId, moduloId, event) => {
  const nuevoEstado = event.target.checked;
  const key = `${userId}_${moduloId}`;
  
  try {
    await permisosService.togglePermiso(userId, moduloId, nuevoEstado);
    
    if (nuevoEstado) {
      if (!data.permisos.includes(key)) data.permisos.push(key);
    } else {
      data.permisos = data.permisos.filter(p => p !== key);
    }

    toast.showToast({ 
      message: `Permiso ${nuevoEstado ? 'asignado' : 'retirado'} con éxito`, 
      type: "success" 
    });

    // Si el permiso cambiado es del usuario logueado, actualizamos su menú en tiempo real
    if (Number(userId) === Number(userStore.user?.id)) {
        // Asumiendo que refreshModulos existe en tu store para actualizar user.modulos
        if (userStore.refreshModulos) await userStore.refreshModulos();
    }
  } catch (e) {
    toast.showToast({ message: "No se pudo actualizar el permiso", type: "danger" });
    event.target.checked = !nuevoEstado; // Revertir el switch si falla la API
  }
};

onMounted(cargarDatos);
</script>

<style scoped>
.text-primary-custom { color: var(--color-primary); }
.fs-xs { font-size: 0.75rem; }

.indicator-dot {
  width: 8px;
  height: 8px;
  background-color: var(--color-secondary);
  border-radius: 50%;
}

.bg-parent {
  background-color: rgba(248, 249, 250, 0.5);
}

.user-th {
  min-width: 100px;
}

.bg-primary-soft {
  background-color: rgba(0, 85, 140, 0.1);
}

/* Switch Estilizado */
.custom-switch {
  cursor: pointer;
  width: 2.5em;
  height: 1.25em;
}

.custom-switch:checked {
  background-color: var(--color-secondary);
  border-color: var(--color-secondary);
}

.sticky-col-first {
  position: sticky;
  left: 0;
  background-color: white;
  z-index: 5;
  box-shadow: 2px 0 5px rgba(0,0,0,0.05);
}

.bg-parent .sticky-col-first {
  background-color: #f8f9fa;
}

/* Animación de rotación para sincronizar */
.bi-spin {
  animation: spin 1s linear infinite;
  display: inline-block;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>