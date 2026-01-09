<template>
  <div class="main-page-container animate-fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
      <h1 class="h5 fw-bold mb-0 text-secondary">USUARIOS</h1>
      
      <div class="d-flex gap-2">
        <button @click="$router.back()" class="btn-back">
          <i class="bi bi-arrow-left-circle fs-6"></i> Volver
        </button>
      </div>
    </div>

    <div class="card-grid">
      <router-link
        v-for="hijo in subModulos"
        :key="hijo.id"
        :to="hijo.ruta"
        class="action-card"
        :style="`--card-color: ${hijo.bg || '#6c757d'};`"
      >
        <div class="icon-container" style="background-color: var(--card-color);">
          <i :class="hijo.icon || 'bi bi-app-indicator'"></i>
        </div>
        <span class="card-label">{{ hijo.nombre }}</span>
      </router-link>

      <div v-if="subModulos.length === 0" class="no-modules w-100">
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

const subModulos = computed(() => {
  const modulos = userStore.user?.modulos || [];
  const ID_PADRE_USUARIOS = 2;

  return modulos
    .filter(m => Number(m.id_padre) === ID_PADRE_USUARIOS)
    .sort((a, b) => (Number(a.orden_visualizacion) || 0) - (Number(b.orden_visualizacion) || 0));
});
</script>

<style scoped>
.text-secondary {
  color: #6c757d !important;
}
</style>