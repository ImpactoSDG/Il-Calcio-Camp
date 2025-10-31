<template>
  <div id="app">

    <Header v-if="userStore.isLoggedIn" />

    <main class="container-fluid py-4 flex-grow-1">
      <router-view />
    </main>
    
    <footer class="bg-light text-center py-3 border-top mt-auto">
      <p class="mb-0 text-muted">&copy; 2025. Proyecto base</p>
    </footer>
    
    <ToastNotification />
  </div>
</template>

<script setup>
import { useUserStore } from '@/stores/userStore';
import ToastNotification from '@/components/ToastNotification.vue';
import Header from '@/components/Header.vue';

const userStore = useUserStore();
</script>

<style>
body {
  background-color: #f8f9fa;
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