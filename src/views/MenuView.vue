<template>
  <div class="menu-container">
    
    <div class="menu-grid">
      <router-link
        v-for="panel in visiblePanels"
        :key="panel.to"
        :to="panel.to"
        class="menu-card"
      >
        <div class="icon-container" :style="`--icon-bg: ${panel.bg};`">
          <i :class="panel.icon" class="icon"></i>
        </div>
        <span class="menu-label">{{ panel.label }}</span>
      </router-link>
    </div>
    
    <p v-if="visiblePanels.length === 0 && userStore.isLoggedIn" class="no-modules">
      No tienes módulos asignados. Contacta a un administrador.
    </p>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useUserStore } from '@/stores/userStore';

const userStore = useUserStore();

const panelStyles = {
    1: { icon: 'bi bi-receipt-cutoff', bg: 'var(--color-secondary)' },


};

const visiblePanels = computed(() => {
    if (!userStore.user?.modulos) {
        return [];
    }
  
    return userStore.user.modulos
        .map(modulo => {
            const style = panelStyles[modulo.id];
            if (style) {
                return {
                    label: modulo.nombre, 
                    to: modulo.ruta,
                    icon: style.icon,     
                    bg: style.bg          
                };
            }
            return null;
        })
        .filter(panel => panel !== null);
});
</script>

<style scoped>
.menu-container {
  max-width: 1000px;
  margin: 40px auto;
  padding: 20px;
}

h1 {
  color: var(--color-heading);
  text-align: center;
  margin-bottom: 40px;
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
  color: var(--color-text-dark);
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
  color: var(--color-text-light);
}

.menu-label {
  font-weight: 600;
  font-size: 1rem;
}

.no-modules {
  text-align: center;
  margin-top: 40px;
  font-size: 1.1em;
  color: #6c757d;
}
</style>