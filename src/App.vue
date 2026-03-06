<template>
  <div id="app" :class="{ 'modal-open': isModalActive }">
    <Header v-if="userStore.isLoggedIn" />
    <main class="flex-grow-1 position-relative">
      <!-- position-relative para que los z-index hijos funcionen correctamente -->
      <router-view @modal-state-change="handleModalStateChange" />
    </main>
    <footer class="bg-light text-center py-3 border-top mt-auto">
      <p class="mb-0 text-muted">
        &copy; {{ currentYear }}. ImpactoSDG | 
        Última actualización: {{ lastUpdate }}
      </p>
    </footer>
    <Teleport to="body">
      <ToastNotification />
    </Teleport>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useUserStore } from '@/stores/userStore';
import ToastNotification from '@/components/ToastNotification.vue';
import Header from '@/components/Header.vue';
import api from '@/services/api';

const userStore = useUserStore();
const currentYear = new Date().getFullYear();
const lastUpdate = ref("Cargando...");
const isModalActive = ref(false);

const handleModalStateChange = (isActive) => {
  isModalActive.value = isActive;
};

const fetchVersion = async () => {
  try {
    const response = await api.get(`/version?t=${new Date().getTime()}`);
    lastUpdate.value = response.data.timestamp;
  } catch (error) {
    lastUpdate.value = "08/01/2026, 11:00:00";
  }
};

onMounted(() => {
  fetchVersion();
});
</script>

<style>
body {
  background-color: #f8f9fa;
  margin: 0;
  padding: 0;
}

#app {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  overflow: hidden; /* NUEVO: Prevenir scrollbar global cuando modal está abierto */
}

main {
  padding-top: 0; /* CAMBIO: de 20px a 0 */
}

/* NUEVO: Prevenir scroll cuando el modal está abierto */
#app.modal-open {
  overflow: hidden;
}
</style>