<template>
  <div class="container-fluid p-4 bg-white min-vh-100 position-relative">
    
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
      <div class="d-flex align-items-center">
        <button @click="$router.back()" class="btn-back-arrow me-2" title="Volver">
          <i class="bi bi-arrow-left"></i>
        </button>
        <h1 class="h5 fw-bold mb-0 text-secondary">GESTIÓN DE PERMISOS</h1>
      </div>
      <div class="d-flex gap-2">
      </div>
    </div>

    <div class="card shadow-sm border-0 rounded-lg overflow-hidden position-relative" :style="{ minHeight: loading ? '400px' : 'auto' }">
      
      <div v-if="loading" class="loading-overlay-local d-flex flex-column align-items-center justify-content-center">
        <div class="spinner-border text-primary-custom" role="status" style="width: 3rem; height: 3rem;">
          <span class="visually-hidden">Cargando...</span>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-light text-center">
            <tr>
              <th class="ps-4 py-3 text-uppercase fs-xs fw-bold text-secondary sticky-col-first text-start">Módulo / Funcionalidad</th>
              <th class="py-3 text-uppercase fs-xs fw-bold text-secondary border-start" style="width: 180px;">Categoría</th>
              <th class="py-3 text-uppercase fs-xs fw-bold text-secondary border-start" style="width: 80px;">Orden</th>
              <th class="py-3 text-uppercase fs-xs fw-bold text-secondary border-start" style="width: 110px;">Estilo</th>
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

              <td class="border-start p-0">
                <div class="d-flex align-items-center justify-content-center h-100 py-2 edit-input">
                  <div class="icon-style-container">
                    <!-- Círculo del Icono (abre modal de iconos) -->
                    <div 
                      class="icon-preview-circle" 
                      @click="openIconModal(modulo)"
                      :style="{ backgroundColor: modulo.bg || '#6C757D' }"
                      title="Cambiar icono"
                    >
                      <i :class="modulo.icon || 'bi-app-indicator'"></i>
                    </div>
                    
                    <!-- Trigger de Color (Overlay pequeño) -->
                    <div class="color-tag-wrapper" title="Cambiar color">
                      <input 
                        v-model="modulo.bg"
                        type="color"
                        class="color-input-hidden"
                        @change="updateModulo(modulo)"
                      />
                      <div class="color-indicator-dot" :style="{ backgroundColor: modulo.bg }">
                        <i class="bi bi-pencil-fill"></i>
                      </div>
                    </div>
                  </div>
                </div>
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

    <!-- Modal para Seleccionar Icono -->
    <Teleport to="body">
    <div v-if="showIconModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Seleccionar Icono</h5>
            <button type="button" class="btn-close" @click="showIconModal = false"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <input v-model="searchIcon" type="text" class="form-control" placeholder="Buscar icono (ej: house, person...)" />
            </div>
            <div class="icon-grid">
              <div 
                v-for="icon in filteredIcons" 
                :key="icon" 
                class="icon-item" 
                :class="{ active: selectedModuloForIcon?.icon === icon }"
                @click="selectIcon(icon)"
              >
                <i :class="icon"></i>
                <span class="icon-name">{{ icon.replace('bi-', '') }}</span>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-light" @click="showIconModal = false">Cerrar</button>
          </div>
        </div>
      </div>
    </div>
    </Teleport>

    <ToastNotification />
  </div>
</template>

<script setup>
import { onMounted, reactive, ref, computed } from 'vue';
import permisosService from '@/services/permisosService';
import moduloService from '@/services/moduloService';
import ToastNotification from '@/components/ToastNotification.vue';
import { useToastStore } from '@/stores/toastStore';
import { useUserStore } from '@/stores/userStore';

const toast = useToastStore();
const userStore = useUserStore();
const loading = ref(false);
const data = reactive({ usuarios: [], modulos: [], permisos: [] });

// Estado para Iconos
const showIconModal = ref(false);
const searchIcon = ref('');
const selectedModuloForIcon = ref(null);

const commonIcons = [
  'bi-house', 'bi-house-fill', 'bi-person', 'bi-person-fill', 'bi-people', 'bi-people-fill',
  'bi-gear', 'bi-gear-fill', 'bi-shield-lock', 'bi-shield-lock-fill', 'bi-key', 'bi-key-fill',
  'bi-envelope', 'bi-envelope-fill', 'bi-receipt', 'bi-receipt-cutoff', 'bi-cart', 'bi-cart-fill',
  'bi-calendar', 'bi-calendar-event', 'bi-chat', 'bi-chat-dots', 'bi-file-earmark', 'bi-file-text',
  'bi-search', 'bi-graph-up', 'bi-pie-chart', 'bi-bell', 'bi-bell-fill', 'bi-star', 'bi-star-fill',
  'bi-plus-circle', 'bi-plus-circle-fill', 'bi-pencil-square', 'bi-trash', 'bi-trash-fill',
  'bi-info-circle', 'bi-info-circle-fill', 'bi-exclamation-triangle', 'bi-exclamation-triangle-fill',
  'bi-app-indicator', 'bi-collection', 'bi-grid', 'bi-layers', 'bi-menu-button-wide',
  'bi-card-text', 'bi-list-ul', 'bi-speedometer2', 'bi-tools', 'bi-universal-access'
];

const filteredIcons = computed(() => {
  if (!searchIcon.value) return commonIcons;
  return commonIcons.filter(icon => icon.toLowerCase().includes(searchIcon.value.toLowerCase()));
});

const openIconModal = (modulo) => {
  selectedModuloForIcon.value = modulo;
  searchIcon.value = '';
  showIconModal.value = true;
};

const selectIcon = (icon) => {
  if (selectedModuloForIcon.value) {
    selectedModuloForIcon.value.icon = icon;
    updateModulo(selectedModuloForIcon.value);
  }
  showIconModal.value = false;
};

// Estado para controlar qué padres están expandidos (siempre como Number)
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
  // Normaliza a número para evitar problemas de tipo
  return data.modulos.some(m => Number(m.id_padre) === Number(moduloId));
};


const toggleExpand = (moduloId) => {
  const id = Number(moduloId);
  if (expandedParents.value.has(id)) {
    expandedParents.value.delete(id);
  } else {
    expandedParents.value.add(id);
  }
};

const isExpanded = (moduloId) => expandedParents.value.has(Number(moduloId));

const isRowVisible = (modulo) => {
  // Si no tiene padre o el id_padre es 0/null, es un módulo raíz y siempre es visible
  if (!modulo.id_padre || Number(modulo.id_padre) === 0) return true;
  // Si tiene padre, solo es visible si su padre está en el set de expandidos
  return expandedParents.value.has(Number(modulo.id_padre));
};
// --------------------------

const updateModulo = async (modulo) => {
  try {
    await moduloService.update({
      id: modulo.id,
      categoria: modulo.categoria,
      orden_visualizacion: modulo.orden_visualizacion,
      icon: modulo.icon,
      bg: modulo.bg
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

.icon-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
  gap: 10px;
  max-height: 350px;
  overflow-y: auto;
  padding: 10px;
}

.icon-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 10px;
  border: 1px solid #eee;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.icon-item i {
  font-size: 1.5rem;
  margin-bottom: 5px;
}

.icon-name {
  font-size: 0.65rem;
  text-align: center;
  color: #6c757d;
}

.icon-item:hover {
  background-color: #f8f9fa;
  border-color: var(--color-primary);
}

.icon-item.active {
  background-color: rgba(0, 85, 140, 0.1);
  border-color: var(--color-primary);
  color: var(--color-primary);
}

.icon-preview-circle {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.1rem;
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
  box-shadow: 0 2px 5px rgba(0,0,0,0.15);
}

.icon-preview-circle:hover {
  transform: scale(1.1);
  box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.icon-style-container {
  position: relative;
  width: 42px;
  height: 42px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.color-tag-wrapper {
  position: absolute;
  bottom: 0px;
  right: 0px;
  width: 18px;
  height: 18px;
  z-index: 2;
}

.color-input-hidden {
  position: absolute;
  top: 0; left: 0; width: 100%; height: 100%;
  opacity: 0;
  cursor: pointer;
  z-index: 3;
}

.color-indicator-dot {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  border: 2px solid white;
  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 0.55rem;
  pointer-events: none;
}

.mono-font {
  font-family: 'Courier New', Courier, monospace;
  font-weight: 600;
  letter-spacing: 0.5px;
}

.btn-icon-selector:hover {
  transform: scale(1.1);
  background-color: #f0f0f0;
}
</style>