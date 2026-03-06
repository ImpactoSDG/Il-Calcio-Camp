<template>
  <header class="app-header bg-light">
    <div v-if="userStore.isLoggedIn" class="user-info">
      <span>Bienvenido, <strong>{{ userStore.user.nombre }}</strong></span>
    </div>
    <div v-if="userStore.isLoggedIn" class="logout-section">
      <button @click="handleLogout" class="logout-btn" title="Cerrar Sesión">
        <i class="bi bi-box-arrow-right"></i>
      </button>
    </div>
  </header>
</template>

<script setup>
import { useUserStore } from '@/stores/userStore';

const userStore = useUserStore();

const handleLogout = () => {
  userStore.logout();
};
</script>

<style scoped>
.app-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 2rem;
  background-color: var(--color-background-soft);
  border-bottom: 1px solid var(--color-border);
  position: relative;
  z-index: 100; /* CAMBIO: de 1000 a 100 (menos que 9999 del modal) */
  flex-shrink: 0; /* Evitar que se comprima */
}

.user-info {
  font-size: 1.1em;
  color: var(--color-text-dark);
}

.user-info strong {
  color: var(--color-primary);
}

.logout-btn {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0;
  font-size: 1.8rem;
  color: var(--color-text-dark);
  transition: color 0.2s ease-in-out;
}

.logout-btn:hover {
  color: var(--color-text-dark);
}
</style>