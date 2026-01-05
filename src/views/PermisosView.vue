<template>
  <div class="container-fluid py-4 bg-canvas min-vh-100">
    <div class="container-xl">
      
      <div class="d-flex align-items-center justify-content-between mb-4 px-2">
        <div>
          <h4 class="fw-bold text-dark m-0">Permisos del Sistema</h4>
          <p class="text-muted small m-0">Administra los accesos de cada integrante de forma global</p>
        </div>
        <button @click="cargarDatos" class="btn btn-sync ripple">
          <i class="bi bi-arrow-clockwise me-2" :class="{'bi-spin': loading}"></i>
          Sincronizar
        </button>
      </div>

      <div class="card border-0 shadow-soft rounded-4 overflow-hidden">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 custom-clean-table">
            <thead>
              <tr>
                <th class="ps-4 py-3 sticky-header">MODULO</th>
                <th class="text-center py-3 sticky-header border-start">ORDEN</th>
                <th class="ps-4 py-3 sticky-header border-start">CATEGORIA</th>
                <th v-for="user in data.usuarios" :key="user.id" class="text-center py-3 sticky-header border-start user-th">
                  <div class="user-pill mx-auto">
                    <span class="d-block name">{{ user.nombre.split(' ')[0] }}</span>
                    <span class="role text-uppercase">{{ user.rol_nombre }}</span>
                  </div>
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="modulo in data.modulos" :key="modulo.id" :class="{'parent-row': !modulo.id_padre}">
                <td class="ps-4 py-3">
                  <div class="d-flex align-items-center">
                    <div v-if="!modulo.id_padre" class="indicator-dot me-2 bg-primary"></div>
                    <i v-else class="bi bi-arrow-return-right me-2 text-muted opacity-50 ms-3"></i>
                    <span :class="{'fw-bold text-dark': !modulo.id_padre, 'text-secondary': modulo.id_padre}">
                      {{ modulo.nombre }}
                    </span>
                  </div>
                </td>

                <td class="text-center">
                  <span class="badge-order">{{ modulo.orden_visualizacion || '-' }}</span>
                </td>

                <td class="ps-4 text-muted small">
                  {{ modulo.categoria || 'Sin categoría' }}
                </td>

                <td v-for="user in data.usuarios" :key="user.id" class="text-center border-start-light">
                  <div class="form-check form-switch d-inline-block">
                    <input 
                      class="form-check-input clean-switch" 
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
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import permisosService from '@/services/permisosService';
import { useUserStore } from '@/stores/userStore';

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
  } catch (e) { console.error(e); }
  finally { loading.value = false; }
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
    if (Number(userId) === Number(userStore.user?.id)) await userStore.refreshModulos();
  } catch (e) {
    event.target.checked = !nuevoEstado;
  }
};

onMounted(cargarDatos);
</script>

<style scoped>
.bg-canvas { background-color: #f6f8fb; }

/* Sombras y Tarjetas */
.shadow-soft { box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04); }
.card { border: 1px solid #edf2f7; background: white; }

/* Tabla Estilizada */
.custom-clean-table thead th {
  background: #fff;
  color: #a0aec0;
  font-size: 0.65rem;
  letter-spacing: 1px;
  font-weight: 800;
  border-bottom: 2px solid #f7fafc;
}

.sticky-header {
  position: sticky;
  top: 0;
  z-index: 10;
}

.custom-clean-table tbody tr {
  transition: all 0.2s;
}

.parent-row { background-color: #fcfdfe; }

.indicator-dot {
  width: 6px; height: 6px;
  border-radius: 50%;
}

/* Info Usuario en Header */
.user-th { min-width: 110px; }
.user-pill .name { color: #2d3748; font-size: 0.75rem; font-weight: 700; }
.user-pill .role { color: #4361ee; font-size: 0.6rem; font-weight: 700; opacity: 0.7; }

/* Botón Sincronizar */
.btn-sync {
  background: white;
  border: 1px solid #e2e8f0;
  color: #4a5568;
  border-radius: 10px;
  font-size: 0.85rem;
  font-weight: 600;
  padding: 8px 16px;
}
.btn-sync:hover { background: #f8fafc; border-color: #cbd5e0; }

/* Badge de Orden */
.badge-order {
  background: #edf2f7;
  color: #718096;
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 0.75rem;
}

/* Switch Moderno y pequeño */
.clean-switch {
  width: 2.2rem !important;
  height: 1.1rem !important;
  cursor: pointer;
  border-color: #e2e8f0;
}
.clean-switch:checked {
  background-color: #4361ee;
  border-color: #4361ee;
}

.border-start-light { border-left: 1px solid #f7fafc; }

/* Animaciones */
.bi-spin { animation: rotation 2s infinite linear; display: inline-block; }
@keyframes rotation { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>