<template>
  <div class="menu-container animate-in">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
      <h1 class="h3 fw-bold mb-0 text-secondary">USUARIOS</h1>
      
      <div class="d-flex gap-2">
        <button @click="$router.back()" class="btn btn-outline-secondary d-flex align-items-center px-4 shadow-sm" style="border-radius: 10px;">
          <i class="bi bi-arrow-left-circle fs-5 me-2"></i> Volver
        </button>
      </div>
    </div>

    <div class="menu-grid justify-content-center">
      <router-link
        v-for="hijo in subModulos"
        :key="hijo.id"
        :to="hijo.ruta"
        class="menu-card"
      >
        <div class="icon-container" :style="`--icon-bg: ${getStyle(hijo.id).bg};`">
          <i :class="getStyle(hijo.id).icon" class="icon"></i>
        </div>
        <span class="menu-label text-uppercase">{{ hijo.nombre }}</span>
      </router-link>

      <div v-if="subModulos.length === 0" class="no-modules">
          <i class="bi bi-exclamation-circle d-block mb-2 fs-2"></i>
          No tienes sub-módulos asignados en esta sección.
        </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useUserStore } from '@/stores/userStore';

const userStore = useUserStore();

const panelStyles = {
  3: { icon: 'bi bi-person-gear', bg: '#198754' },
  4: { icon: 'bi bi-shield-lock', bg: '#dc3545' },
  default: { icon: 'bi bi-app-indicator', bg: '#6c757d' }
};

const getStyle = (id) => panelStyles[id] || panelStyles.default;

const subModulos = computed(() => {
  const modulos = userStore.user?.modulos || [];
  const ID_PADRE_USUARIOS = 2;

  return modulos
    .filter(m => Number(m.id_padre) === ID_PADRE_USUARIOS)
    .sort((a, b) => (Number(a.orden_visualizacion) || 0) - (Number(b.orden_visualizacion) || 0));
});
</script>

<style scoped>
.menu-container {
  max-width: 1000px;
  margin: 40px auto;
  padding: 20px;
}

.menu-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 250px));
  gap: 1.5rem;
  width: 100%;
}

.menu-card {
  background-color: #ffffff;
  border: 1px solid var(--color-border, #e0e0e0);
  border-radius: 10px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  padding: 2rem 1.5rem;
  text-align: center;
  text-decoration: none;
  color: var(--color-text-dark, #333);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.menu-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
}

.icon-container {
  width: 60px;
  height: 60px;
  margin: 0 auto 1rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: var(--icon-bg, #cccccc);
}

.icon {
  font-size: 2rem;
  color: #ffffff;
}

.menu-label {
  font-weight: 600;
  font-size: 0.9rem;
  letter-spacing: 0.5px;
}

.animate-in {
  animation: slideUp 0.5s ease-out forwards;
}

@keyframes slideUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

.text-secondary {
  color: #6c757d !important;
}

.no-modules {
  text-align: center;
  margin-top: 80px;
  font-size: 1.1rem;
  color: #6c757d;
  font-weight: 500;
}
</style>