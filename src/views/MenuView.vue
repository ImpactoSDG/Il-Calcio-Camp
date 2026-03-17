<template>
  <div class="container py-4 animate-fade-in">
    
    <div v-for="(modulos, categoria) in menuEstructurado" :key="categoria" class="category-section mb-5">
      
      <div class="category-header d-flex align-items-center gap-3 mb-4">
        <h3 class="category-title mb-0">{{ categoria.toUpperCase() }}</h3>
        <div class="category-line flex-grow-1"></div>
      </div>

      <div class="card-grid">
        <template v-for="modulo in modulos" :key="modulo.id">
          <!-- Link Externo -->
          <div class="action-card-wrapper position-relative">
            <button 
              @click.stop.prevent="toggleFavorito(modulo)"
              class="favorite-btn"
              :class="{ 'is-favorite': Number(modulo.favorito) === 1 }"
              title="Marcar como favorito"
            >
              <i class="bi" :class="Number(modulo.favorito) === 1 ? 'bi-star-fill' : 'bi-star'"></i>
            </button>

            <a
              v-if="modulo.ruta && (modulo.ruta.startsWith('http://') || modulo.ruta.startsWith('https://'))"
              :href="modulo.ruta"
              target="_blank"
              class="action-card"
              :style="`--card-color: ${modulo.bg || '#6c757d'};`"
            >
              <div class="icon-container" style="background-color: var(--card-color);">
                <i :class="modulo.icon || 'bi bi-app-indicator'"></i>
              </div>
              <span class="card-label">{{ modulo.nombre }}</span>
            </a>

            <!-- Link Interno -->
            <router-link
              v-else
              :to="modulo.ruta"
              class="action-card"
              :style="`--card-color: ${modulo.bg || '#6c757d'};`"
            >
              <div class="icon-container" style="background-color: var(--card-color);">
                <i :class="modulo.icon || 'bi bi-app-indicator'"></i>
              </div>
              <span class="card-label">{{ modulo.nombre }}</span>
            </router-link>
          </div>
        </template>
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

const toggleFavorito = async (modulo) => {
  const nuevoEstado = Number(modulo.favorito) === 1 ? false : true;
  await userStore.toggleFavorito(modulo.id, nuevoEstado);
};

const menuEstructurado = computed(() => {
    const modulosRaw = userStore.user?.modulos;
    if (!modulosRaw || !Array.isArray(modulosRaw)) return {};

    const tree = {};
    
    const padres = modulosRaw
        .filter(m => !m.id_padre || m.id_padre == 0 || m.id_padre == "0")
        .sort((a, b) => (Number(a.orden_visualizacion) || 0) - (Number(b.orden_visualizacion) || 0));

    // Primero procesar Favoritos
    const favoritos = padres.filter(m => Number(m.favorito) === 1);
    if (favoritos.length > 0) {
      tree['FAVORITOS'] = favoritos;
    }

    padres.forEach(modulo => {
        const catNombre = modulo.categoria || 'GENERAL';
        if (!tree[catNombre]) tree[catNombre] = [];
        tree[catNombre].push(modulo);
    });

    return tree;
});
</script>

<style scoped>
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

.action-card-wrapper {
  position: relative;
}

.favorite-btn {
  position: absolute;
  top: 10px;
  right: 10px;
  z-index: 10;
  background: rgba(255, 255, 255, 0.8);
  border: none;
  border-radius: 50%;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #ccc;
  transition: all 0.2s ease;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.favorite-btn:hover {
  transform: scale(1.1);
  color: #ffc107;
}

.favorite-btn.is-favorite {
  color: #ffc107;
  background: white;
}
</style>