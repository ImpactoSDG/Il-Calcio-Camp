<template>
  <div class="menu-container animate-in">
    
    <div v-for="(modulos, categoria) in menuEstructurado" :key="categoria" class="category-section mb-5">
      
      <div class="category-header d-flex align-items-center gap-3 mb-4">
        <h3 class="category-title mb-0">{{ categoria.toUpperCase() }}</h3>
        <div class="category-line flex-grow-1"></div>
      </div>

      <div class="menu-grid">
        <router-link
          v-for="modulo in modulos"
          :key="modulo.id"
          :to="modulo.ruta"
          class="menu-card"
        >
          <div class="icon-container" :style="`--icon-bg: ${getStyle(modulo.id).bg};`">
            <i :class="getStyle(modulo.id).icon" class="icon"></i>
          </div>
          <span class="menu-label">{{ modulo.nombre }}</span>
        </router-link>
      </div>
    </div>

    <p v-if="Object.keys(menuEstructurado).length === 0 && userStore.isLoggedIn" class="no-modules">
      No tienes módulos asignados. Contacta a un administrador.
    </p>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useUserStore } from '@/stores/userStore';

const userStore = useUserStore();

const panelStyles = {
    1: { icon: 'bi bi-receipt-cutoff', bg: 'var(--color-secondary, #2f656d)' },
    2: { icon: 'bi bi-people-fill', bg: '#ba3b21' },
    5: { icon: 'bi bi-gear text-primary-custom me-2', bg: '#0d6efd' },
    default: { icon: 'bi bi-app-indicator', bg: '#6c757d' }
};

const getStyle = (id) => panelStyles[id] || panelStyles.default;

const menuEstructurado = computed(() => {
    const modulosRaw = userStore.user?.modulos;
    if (!modulosRaw || !Array.isArray(modulosRaw)) return {};

    const tree = {};
    
    const padres = modulosRaw
        .filter(m => !m.id_padre || m.id_padre == 0 || m.id_padre == "0")
        .sort((a, b) => (Number(a.orden_visualizacion) || 0) - (Number(b.orden_visualizacion) || 0));

    padres.forEach(modulo => {
        const catNombre = modulo.categoria || 'GENERAL';
        if (!tree[catNombre]) tree[catNombre] = [];
        tree[catNombre].push(modulo);
    });

    return tree;
});
</script>

<style scoped>
.menu-container {
  max-width: 1000px;
  margin: 40px auto;
  padding: 20px;
}

.category-header {
  margin-bottom: 20px;
}

.category-title {
  font-size: 0.9rem;
  font-weight: 700;
  color: #6c757d;
  letter-spacing: 1px;
}

.category-line {
  height: 1px;
  background-color: #e0e0e0;
}

.menu-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
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
  display: block;
}

.no-modules {
  text-align: center;
  margin-top: 40px;
  font-size: 1.1em;
  color: #6c757d;
}

.animate-in {
  animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>