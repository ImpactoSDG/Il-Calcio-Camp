<template>
  <div class="container py-4 animate-fade-in">
    <div class="d-flex align-items-center mb-4 pb-3 border-bottom">
      <button @click="$router.back()" class="btn btn-link text-secondary p-0 me-3" title="Volver">
        <i class="bi bi-arrow-left fs-4"></i>
      </button>
      <h1 class="h5 fw-bold mb-0 text-secondary text-uppercase">{{ nombreModulo }}</h1>
    </div>

    <div class="card-grid">
      <template v-for="hijo in subModulos" :key="hijo.id">
        <!-- Link Externo -->
        <a
          v-if="hijo.ruta && (hijo.ruta.startsWith('http://') || hijo.ruta.startsWith('https://'))"
          :href="hijo.ruta"
          target="_blank"
          class="action-card"
          :style="`--card-color: ${hijo.bg || '#6c757d'};`"
        >
          <div class="icon-container" style="background-color: var(--card-color);">
            <i :class="hijo.icon || 'bi bi-app-indicator'"></i>
          </div>
          <span class="card-label">{{ hijo.nombre }}</span>
        </a>

        <!-- Link Interno -->
        <router-link
          v-else
          :to="hijo.ruta"
          class="action-card"
          :style="`--card-color: ${hijo.bg || '#6c757d'};`"
        >
          <div class="icon-container" style="background-color: var(--card-color);">
            <i :class="hijo.icon || 'bi bi-app-indicator'"></i>
          </div>
          <span class="card-label">{{ hijo.nombre }}</span>
        </router-link>
      </template>

      <div v-if="subModulos.length === 0" class="no-modules w-100 py-5 text-center text-muted">
        <i class="bi bi-exclamation-circle d-block mb-2 fs-2"></i>
        No tienes sub-módulos asignados en esta sección.
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { useUserStore } from '@/stores/userStore';

const route = useRoute();
const userStore = useUserStore();

// Obtenemos el ID del módulo actual desde el meta de la ruta o desde los parámetros
const idModuloActual = computed(() => {
  return Number(route.meta.useParamId ? route.params.id : route.meta.idModulo);
});

const moduloActual = computed(() => {
  return userStore.user?.modulos?.find(m => Number(m.id) === idModuloActual.value);
});

const nombreModulo = computed(() => moduloActual.value?.nombre || 'MENÚ');

const subModulos = computed(() => {
  const modulos = userStore.user?.modulos || [];
  
  return modulos
    .filter(m => Number(m.id_padre) === idModuloActual.value)
    .sort((a, b) => (Number(a.orden_visualizacion) || 0) - (Number(b.orden_visualizacion) || 0));
});
</script>

<style scoped>
.text-secondary {
  color: #6c757d !important;
}
</style>
