<template>
  <div id="app">
    <Header v-if="userStore.isLoggedIn" />

    <main class="container-fluid py-4 flex-grow-1">
      <router-view />
    </main>
    
    <footer class="bg-light text-center py-3 border-top mt-auto">
      <p class="mb-0 text-muted">
        &copy; {{ currentYear }}. ImpactoSDG | 
        Última actualización: {{ lastUpdate }}
      </p>
    </footer>
    
    <ToastNotification />
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
}

#app {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

main {
  padding-top: 20px;
}
</style>