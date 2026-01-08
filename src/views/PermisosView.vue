<template>
  <div class="container-fluid p-4 bg-white min-vh-100 position-relative">
    
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
      <div>
        <h1 class="h3 fw-bold mb-0 text-primary-custom">Gestión de Permisos</h1>
      </div>
      <div class="d-flex gap-2">
        <button @click="$router.back()" class="btn btn-outline-secondary d-flex align-items-center px-4 shadow-sm" style="border-radius: 10px;">
          <i class="bi bi-arrow-left-circle fs-5 me-2"></i> Volver
        </button>
      </div>
    </div>

    <div class="card shadow-sm border-0 rounded-lg overflow-hidden position-relative" :style="{ minHeight: loading ? '400px' : 'auto' }">
      
      <div v-if="loading" class="loading-overlay-local d-flex flex-column align-items-center justify-content-center">
        <div class="spinner-border text-primary-custom" role="status" style="width: 3rem; height: 3rem;">
          <span class="visually-hidden">Cargando...</span>
        </div>
        <p class="mt-3 fw-bold text-primary-custom">Sincronizando matriz...</p>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-light text-center">
            <tr>
              <th class="ps-4 py-3 text-uppercase fs-xs fw-bold text-secondary sticky-col-first text-start">Módulo / Funcionalidad</th>
              <th class="py-3 text-uppercase fs-xs fw-bold text-secondary border-start" style="width: 180px;">Categoría</th>
              <th class="py-3 text-uppercase fs-xs fw-bold text-secondary border-start" style="width: 100px;">Orden</th>
              <th v-for="user in data.usuarios" :key="user.id" class="py-3 text-uppercase fs-xs fw-bold text-secondary border-start user-th">
                <div class="px-2">
                  <span class="d-block text-dark">{{ user.nombre.split(' ')[0] }}</span>
                  <span class="badge bg-primary-soft text-primary-custom fw-normal" style="font-size: 0.6rem;">{{ user.rol_nombre }}</span>
                </div>
              </th>
            </tr>
          </thead>
          <tbody class="bg-white">
            <tr 
              v-for="modulo in data.modulos" 
              :key="modulo.id" 
              v-show="isRowVisible(modulo)"
              :class="{'bg-parent': !modulo.id_padre}"
            >
              
              <td class="ps-4 py-3 sticky-col-first">
                <div class="d-flex align-items-center">
                  <div v-if="!modulo.id_padre" class="d-flex align-items-center">
                    <button 
                      v-if="hasChildren(modulo.id)"
                      @click="toggleExpand(modulo.id)"
                      class="btn btn-link p-0 me-2 text-decoration-none text-primary-custom shadow-none"
                    >
                      <i class="bi" :class="isExpanded(modulo.id) ? 'bi-chevron-down' : 'bi-chevron-right'"></i>
                    </button>
                    <div v-else class="indicator-dot me-2"></div>
                  </div>
                  
                  <i v-else class="bi bi-arrow-return-right me-2 text-muted opacity-50 ms-3"></i>
                  
                  <span :class="{'fw-bold text-dark': !modulo.id_padre, 'text-secondary': modulo.id_padre}">
                    {{ modulo.nombre }}
                  </span>
                </div>
              </td>

              <td class="border-start p-0">
                <input 
                  v-model="modulo.categoria"
                  type="text"
                  class="form-control form-control-sm border-0 bg-transparent text-center edit-input py-3"
                  placeholder="Sin categoría"
                  @blur="updateModulo(modulo)"
                  @keyup.enter="$event.target.blur()"
                />
              </td>

              <td class="border-start p-0">
                <input 
                  v-model.number="modulo.orden_visualizacion"
                  type="number"
                  class="form-control form-control-sm border-0 bg-transparent text-center edit-input py-3 fw-bold"
                  @blur="updateModulo(modulo)"
                  @keyup.enter="$event.target.blur()"
                />
              </td>

              <td v-for="user in data.usuarios" :key="user.id" class="text-center border-start border-light">
                <div class="form-check form-switch d-inline-block">
                  <input 
                    class="form-check-input custom-switch" 
                    type="checkbox" 
                    :checked="estaAsignado(user.id, modulo.id)" 
                    @change="handleToggle(user.id, modulo.id, $event)"
                    :disabled="modulo.id_padre && !estaAsignado(user.id, modulo.id_padre)"
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
import moduloService from '@/services/moduloService';
import ToastNotification from '@/components/ToastNotification.vue';
import { useToastStore } from '@/stores/toastStore';
import { useUserStore } from '@/stores/userStore';

const toast = useToastStore();
const userStore = useUserStore();
const loading = ref(false);
const data = reactive({ usuarios: [], modulos: [], permisos: [] });

// Estado para controlar qué padres están expandidos
const expandedParents = ref(new Set());

const cargarDatos = async () => {
  loading.value = true;
  try {
    const res = await permisosService.getMatrizPermisos();
    data.usuarios = res.usuarios || [];
    data.modulos = res.modulos || [];
    data.permisos = res.permisos || [];
    
    // Opcional: Expandir todos por defecto al cargar
    // data.modulos.filter(m => !m.id_padre).forEach(m => expandedParents.value.add(m.id));
  } catch (e) {
    toast.showToast({ message: "Error al sincronizar datos", type: "danger" });
  } finally {
    loading.value = false;
  }
};

// --- Lógica de Acordeón ---
const hasChildren = (moduloId) => {
  return data.modulos.some(m => Number(m.id_padre) === Number(moduloId));
};

const toggleExpand = (moduloId) => {
  if (expandedParents.value.has(moduloId)) {
    expandedParents.value.delete(moduloId);
  } else {
    expandedParents.value.add(moduloId);
  }
};

const isExpanded = (moduloId) => expandedParents.value.has(moduloId);

const isRowVisible = (modulo) => {
  // Si no tiene padre, es un módulo raíz y siempre es visible
  if (!modulo.id_padre) return true;
  // Si tiene padre, solo es visible si su padre está en el set de expandidos
  return expandedParents.value.has(Number(modulo.id_padre));
};
// --------------------------

const updateModulo = async (modulo) => {
  try {
    await moduloService.update({
      id: modulo.id,
      categoria: modulo.categoria,
      orden_visualizacion: modulo.orden_visualizacion
    });
    toast.showToast({ message: `Módulo "${modulo.nombre}" actualizado`, type: "success" });
    if (userStore.refreshModulos) await userStore.refreshModulos();
  } catch (error) {
    toast.showToast({ message: "Error al guardar cambios del módulo", type: "danger" });
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
      const hijos = data.modulos.filter(m => Number(m.id_padre) === Number(moduloId));
      for (const hijo of hijos) {
        const hijoKey = `${userId}_${hijo.id}`;
        if (data.permisos.includes(hijoKey)) {
          await permisosService.togglePermiso(userId, hijo.id, false);
          data.permisos = data.permisos.filter(p => p !== hijoKey);
        }
      }
    }
    toast.showToast({ message: "Permisos actualizados", type: "success" });
    if (Number(userId) === Number(userStore.user?.id)) {
      if (userStore.refreshModulos) await userStore.refreshModulos();
    }
  } catch (e) {
    toast.showToast({ message: "Error al actualizar permiso", type: "danger" });
    event.target.checked = !nuevoEstado;
  }
};

onMounted(cargarDatos);
</script>

<style scoped>
.text-primary-custom { color: var(--color-primary); }
.fs-xs { font-size: 0.75rem; }

.edit-input {
  border-radius: 0;
  transition: all 0.2s;
  font-size: 0.85rem;
}

.edit-input:hover {
  background-color: rgba(0, 85, 140, 0.05) !important;
  cursor: pointer;
}

.edit-input:focus {
  background-color: #fff !important;
  box-shadow: inset 0 0 0 2px var(--color-primary);
  z-index: 10;
}

.indicator-dot {
  width: 8px;
  height: 8px;
  background-color: var(--color-secondary);
  border-radius: 50%;
}

.bg-parent { background-color: rgba(248, 249, 250, 0.5); }

.user-th { min-width: 110px; }
.bg-primary-soft { background-color: rgba(0, 85, 140, 0.1); }

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
  box-shadow: 2px 0 5px rgba(0, 0, 0, 0.05);
}

.bg-parent .sticky-col-first { background-color: #f8f9fa; }

.loading-overlay-local {
  position: absolute;
  top: 0; left: 0; width: 100%; height: 100%;
  background-color: rgba(255, 255, 255, 0.85);
  z-index: 1000;
  display: flex;
}

/* Animación suave para la aparición de filas */
tr {
  transition: opacity 0.2s ease-in-out;
}
</style>